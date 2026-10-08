/**
 * Data Master server engine — an exact behavioural replica of the Laravel
 * `routes/web.php` master-data closures (lists, CRUD, soft delete, hard delete,
 * Excel import/export, autocomplete). Every success/error message, log string
 * and edge-case quirk below mirrors the Laravel source literally (see
 * documentation/11_datamaster_audit.md). Do not "improve" any behaviour here.
 *
 * Documented deviations (all confined to dead Laravel code paths):
 *  - Laravel's per-page hard-delete guards call missing model methods
 *    (Process::ptmsReports, Department::operators, Mechanic::ptmsReports) and
 *    would throw; those routes are unreachable from the Laravel UI. Here the
 *    intended dependency checks run against the real relations instead.
 *  - Article/Operator photo files are stored under /public/uploads/... because
 *    there is no Laravel storage symlink in Next.js.
 */
import fs from "fs";
import path from "path";
import { NextRequest, NextResponse } from "next/server";
import * as XLSX from "xlsx";
import ExcelJS from "exceljs";
import { table, db } from "@/lib/db";
import {
  authenticate,
  fail,
  notFoundResult,
  num,
  success,
  validationFail,
  type AuthedUser,
} from "@/lib/http";
import { validate, type Rules } from "@/lib/validation";
import { MASTERS, HARD_DELETE_CARDS, fill, type MasterDef } from "@/lib/master-config";

/* ------------------------------------------------------------------ */
/* shared helpers                                                      */
/* ------------------------------------------------------------------ */

/** JSON-safe row: BigInt ids -> number, Decimal -> string, Date -> ISO. */
function ser(value: any): any {
  if (value === null || value === undefined) return value;
  if (typeof value === "bigint") return Number(value);
  if (value instanceof Date) return value.toISOString();
  if (Array.isArray(value)) return value.map(ser);
  if (typeof value === "object") {
    // decimal.js Decimal (Prisma @db.Decimal). The constructor name is mangled
    // in the minified Prisma runtime, so detect by decimal.js shape instead.
    if (
      typeof value.toJSON === "function" &&
      typeof value.toFixed === "function" &&
      typeof value.e === "number" &&
      Array.isArray(value.d)
    ) {
      // Laravel `decimal:2` casts (GsdElement tmu/seconds) render "43.00".
      return Number(value.toString()).toFixed(2);
    }
    const out: Record<string, any> = {};
    for (const [k, v] of Object.entries(value)) out[k] = ser(v);
    return out;
  }
  return value;
}

/**
 * Distinct non-null values of a column (Laravel `whereNotNull(...)->distinct()`).
 * Prisma rejects `{ not: null }` on required columns, so filter in JS instead.
 */
async function distinctRows(model: string, col: string): Promise<any[]> {
  const rows = await table(model).findMany({
    distinct: [col],
    orderBy: { [col]: 'asc' },
    select: { [col]: true },
  });
  return rows.filter((r: any) => r[col] !== null && r[col] !== undefined);
}

/** Laravel logActivity() -> activity_logs. */
async function logActivity(user: AuthedUser | null, activity: string, module: string | null) {
  try {
    await table("activity_logs").create({
      data: {
        user_id: user ? BigInt(user.id) : null,
        username: user?.username ?? user?.name ?? "system",
        activity: activity.slice(0, 500),
        module,
        created_at: new Date(),
        updated_at: new Date(),
      } as any,
    });
  } catch {
    /* logging must never break the request */
  }
}

/** "Y-m-d" for @db.Date columns. */
const dateOnly = (v: string | Date | null | undefined) => {
  if (!v) return null;
  const d = v instanceof Date ? v : new Date(v);
  return isNaN(d.getTime()) ? null : d.toISOString().slice(0, 10);
};

/** Date object for Prisma @db.Date filters/writes (bare "Y-m-d" strings are
 *  rejected: Prisma expects a full ISO-8601 DateTime). UTC midnight keeps the
 *  stored DATE identical to what Laravel's raw "Y-m-d" string would store. */
const dateVal = (v: string | Date | null | undefined): Date | null => {
  const s = dateOnly(v);
  return s ? new Date(`${s}T00:00:00.000Z`) : null;
};

const rand = (n: number) => {
  const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  let out = "";
  for (let i = 0; i < n; i++) out += chars[Math.floor(Math.random() * chars.length)];
  return out;
};

interface Auth {
  user: AuthedUser;
  tokenId: bigint;
}

async function authOf(req: NextRequest): Promise<Auth | NextResponse> {
  const auth = await authenticate(req);
  if (!auth) return fail("Unauthenticated.", 401);
  return auth;
}

/** Laravel `abort(403, 'Unauthorized. Viewer role is read-only.')` (operators only). */
function viewerGuard(user: AuthedUser): NextResponse | null {
  if (user.role?.role_name === "viewer") {
    return fail("Unauthorized. Viewer role is read-only.", 403);
  }
  return null;
}

/** Laravel operators.hard-delete developer gate. */
function developerGuard(user: AuthedUser): NextResponse | null {
  if (user.role?.role_name !== "developer") {
    return fail("Only developers can perform hard deletes.", 403);
  }
  return null;
}

/* ------------------------------------------------------------------ */
/* validation (mirrors the closure $request->validate([...]) arrays)    */
/* ------------------------------------------------------------------ */

function rulesFor(def: MasterDef, updateId?: string): Rules {
  const uniq = (t: string) =>
    updateId ? `nullable|string|max:50|unique:${t},nik_karyawan,${updateId}` : `nullable|string|max:50|unique:${t},nik_karyawan`;
  switch (def.variant) {
    case 'simple':
      return { [def.nameField]: 'required|string|max:200', description: 'nullable|string|max:255' };
    case 'factories':
      return { factory_name: 'required|string|max:100', description: 'nullable|string|max:255' };
    case 'departments':
      return {
        factory_id: 'required|exists:factories,id',
        department_name: 'required|string|max:100',
        desription: 'nullable|string|max:255',
      };
    case 'destinations':
      return { destination: 'required|string|max:100', description: 'nullable|string|max:255' };
    case 'production-lines':
      return { line_name: 'required|string|max:100', description: 'nullable|string|max:255' };
    case 'mechanics':
      return {
        nik_karyawan: uniq('mechanics'),
        mechanic: 'required|string|max:200',
        description: 'nullable|string|max:255',
      };
    case 'articles':
      return { article_name: 'required|string|max:150', description: 'nullable|string|max:255' };
    case 'gsd-elements':
      return {
        element_name: 'required|string|max:200',
        description: 'nullable|string|max:255',
        code: 'required|string|max:50',
        tmu: 'required|numeric|min:0',
        seconds: 'required|numeric|min:0',
        motion_sequence: 'nullable|string|max:100',
        gsd_category_id: 'required|exists:gsd_categories,id',
      };
    case 'operators':
      return {
        operator_name: 'required|string|max:100',
        nik_karyawan: uniq('operators'),
        gender: 'nullable|string|max:30',
        role: 'nullable|string|max:100',
        start_date: 'nullable|string',
        date_of_birth: 'nullable|string',
        status_pkwtt_id: 'nullable|exists:status_pkwtt,id',
        educational_level_id: 'nullable|exists:educational_levels,id',
        factory_id: 'nullable|exists:factories,id',
        department_id: 'nullable|exists:departments,id',
        division_id: 'nullable|exists:divisions,id',
        section_id: 'nullable|exists:sections,id',
        line_id: 'nullable|exists:production_lines,id',
      };
    case 'processes':
      return {
        process_name: 'required|string|max:200',
        version_number: updateId ? 'required|integer|min:1' : 'nullable|integer|min:1',
      };
    default:
      return {};
  }
}

/** Laravel messages for rules the shared validator does not cover. */
function extraValidation(def: MasterDef, body: Record<string, any>): Record<string, string[]> {
  const errors: Record<string, string[]> = {};
  const attr = (f: string) => f.replace(/_/g, ' ');
  const dateFields = def.variant === 'operators' ? ['start_date', 'date_of_birth'] : [];
  for (const field of dateFields) {
    const v = body[field];
    if (v !== undefined && v !== null && v !== '' && isNaN(new Date(String(v)).getTime())) {
      errors[field] = [`The ${attr(field)} is not a valid date.`];
    }
  }
  if (def.variant === 'processes' && body.version_number !== undefined && body.version_number !== null && body.version_number !== '') {
    const n = Number(body.version_number);
    if (!Number.isInteger(n)) errors.version_number = ['The version number must be an integer.'];
    else if (n < 1) errors.version_number = ['The version number must be at least 1.'];
  }
  if (def.variant === 'processes') {
    const ids = body.gsd_element_ids;
    if (ids !== undefined && ids !== null && !Array.isArray(ids)) {
      errors.gsd_element_ids = ['The gsd element ids must be an array.'];
    }
  }
  return errors;
}

async function runValidation(
  def: MasterDef,
  body: Record<string, any>,
  updateId?: string
): Promise<{ data: Record<string, any>; error: NextResponse | null }> {
  const { data, errors } = await validate(body, rulesFor(def, updateId), updateId);
  const extra = extraValidation(def, body);
  const all = { ...(errors ?? {}), ...extra };
  if (Object.keys(all).length > 0) return { data: {}, error: validationFail(all) };
  return { data, error: null };
}

/* ------------------------------------------------------------------ */
/* list                                                                */
/* ------------------------------------------------------------------ */

const like = (v: string) => ({ contains: v });

async function listMaster(slug: string, req: NextRequest) {
  const def = MASTERS[slug];
  const auth = await authOf(req);
  if ('json' in auth) return auth;
  const sp = req.nextUrl.searchParams;
  const search = (sp.get('search') ?? '').trim();
  const filterColumn = sp.get('filter_column') ?? '';
  const filterValue = sp.get('filter_value') ?? '';
  const showInactive = sp.get('show_inactive') === '1' || sp.get('show_inactive') === 'true';
  const dateFrom = sp.get('date_from') ?? '';
  const dateTo = sp.get('date_to') ?? '';
  let sort = sp.get('sort') ?? def.defaultSort;
  let direction = sp.get('direction') ?? 'asc';
  if (!def.sortable.includes(sort)) sort = def.defaultSort;
  if (!['asc', 'desc'].includes(direction)) direction = 'asc';
  const dir = direction === 'desc' ? 'desc' : 'asc';

  let rows: any[] = [];
  let filterValues: Record<string, any[]> = {};
  let options: Record<string, any[]> = {};

  if (def.variant === 'simple' || def.variant === 'factories' || def.variant === 'destinations' || def.variant === 'production-lines') {
    const where: any = {};
    if (!showInactive) where.status = 'active';
    if (search !== '') {
      where.OR = [
        { [def.nameField]: like(search) },
        { [def.descField]: like(search) },
      ];
    }
    if (filterValue !== '' && (filterColumn === def.nameField || filterColumn === 'factory_name')) {
      where[filterColumn] = filterValue;
    }
    rows = await table(def.model).findMany({ where, orderBy: { [sort]: dir } });
    const names = await distinctRows(def.model, def.nameField);
    filterValues = { [def.nameField]: names.map((r: any) => r[def.nameField]) };
  } else if (def.variant === 'departments') {
    const where: any = {};
    if (!showInactive) where.status = 'active';
    if (search !== '') {
      where.OR = [
        { department_name: like(search) },
        { desription: like(search) },
        { factories: { factory_name: like(search) } },
      ];
    }
    if (filterValue !== '') {
      if (filterColumn === 'department_name') where.department_name = filterValue;
      if (filterColumn === 'factory_name') where.factories = { factory_name: filterValue };
    }
    rows = await table('departments').findMany({
      where,
      include: { factories: true },
      orderBy: { [sort]: dir },
    });
    const deptNames = await distinctRows('departments', 'department_name');
    // NOTE: Laravel plucks ALL factory names (not active-only) here.
    const factoryNames = await distinctRows('factories', 'factory_name');
    filterValues = {
      department_name: deptNames.map((r: any) => r.department_name),
      factory_name: factoryNames.map((r: any) => r.factory_name),
    };
  } else if (def.variant === 'mechanics') {
    const where: any = {};
    if (!showInactive) where.status = 'active';
    if (search !== '') {
      where.OR = [
        { nik_karyawan: like(search) },
        { mechanic: like(search) },
        { description: like(search) },
      ];
    }
    if (filterValue !== '' && ['nik_karyawan', 'mechanic'].includes(filterColumn)) {
      where[filterColumn] = filterValue;
    }
    rows = await table('mechanics').findMany({ where, orderBy: { [sort]: dir } });
    filterValues = {
      nik_karyawan: (await distinctRows('mechanics', 'nik_karyawan')).map((r: any) => r.nik_karyawan),
      mechanic: (await distinctRows('mechanics', 'mechanic')).map((r: any) => r.mechanic),
    };
  } else if (def.variant === 'articles') {
    const where: any = {};
    if (!showInactive) where.status = 'active';
    if (search !== '') where.OR = [{ article_name: like(search) }];
    if (filterValue !== '' && filterColumn === 'article_name') where.article_name = filterValue;
    rows = await table('articles').findMany({ where, orderBy: { [sort]: dir } });
    filterValues = {
      article_name: (await distinctRows('articles', 'article_name')).map((r: any) => r.article_name),
    };
  } else if (def.variant === 'gsd-elements') {
    const where: any = {};
    if (!showInactive) where.status = 'active';
    if (search !== '') {
      where.OR = [
        { element_name: like(search) },
        { description: like(search) },
        { code: like(search) },
        { motion_sequence: like(search) },
      ];
    }
    if (filterValue !== '' && ['element_name', 'code', 'motion_sequence'].includes(filterColumn)) {
      where[filterColumn] = filterValue;
    }
    rows = await table('gsd_elements').findMany({
      where,
      include: { gsd_categories: true },
      orderBy: { [sort]: dir },
    });
    filterValues = {
      element_name: (await distinctRows('gsd_elements', 'element_name')).map((r: any) => r.element_name),
      code: (await distinctRows('gsd_elements', 'code')).map((r: any) => r.code),
      motion_sequence: (await distinctRows('gsd_elements', 'motion_sequence')).map((r: any) => r.motion_sequence),
    };
    options = {
      gsdCategories: ser(await table('gsd_categories').findMany({ where: { status: 'active' }, orderBy: { category_name: 'asc' } })),
    };
  } else if (def.variant === 'operators') {
    const where: any = {};
    if (!showInactive) where.status = 'active';
    if (search !== '') {
      where.OR = [
        { operator_name: like(search) },
        { nik_karyawan: like(search) },
        { gender: like(search) },
        { role: like(search) },
        { status_pkwtt: { pkwtt: like(search) } },
        { educational_levels: { level: like(search) } },
        { factories: { factory_name: like(search) } },
        { departments: { department_name: like(search) } },
        { divisions: { division: like(search) } },
        { sections: { section: like(search) } },
        { production_lines: { line_name: like(search) } },
      ];
    }
    const exact: Record<string, string> = {
      operator_name: 'operator_name',
      nik_karyawan: 'nik_karyawan',
      gender: 'gender',
      role: 'role',
    };
    const relFilter: Record<string, [string, string]> = {
      status_pkwtt: ['status_pkwtt', 'pkwtt'],
      educational_level: ['educational_levels', 'level'],
      factory: ['factories', 'factory_name'],
      department: ['departments', 'department_name'],
      division: ['divisions', 'division'],
      section: ['sections', 'section'],
      line: ['production_lines', 'line_name'],
    };
    if (filterValue !== '') {
      if (exact[filterColumn]) where[exact[filterColumn]] = filterValue;
      else if (relFilter[filterColumn]) {
        const [rel, col] = relFilter[filterColumn];
        where[rel] = { [col]: filterValue };
      }
    }
    // Date range filters apply only when the date column is the active filter.
    // Laravel: where('start_date', '>=', $dateFrom) / where('start_date', '<=', $dateTo).
    if (filterColumn === 'start_date' || filterColumn === 'date_of_birth') {
      const from = dateVal(dateFrom);
      const to = dateVal(dateTo);
      if (from) where[filterColumn] = { ...(where[filterColumn] ?? {}), gte: from };
      if (to) where[filterColumn] = { ...(where[filterColumn] ?? {}), lte: to };
    }
    const include = {
      status_pkwtt: true,
      educational_levels: true,
      factories: true,
      departments: true,
      divisions: true,
      sections: true,
      production_lines: true,
    };
    rows = await table('operators').findMany({ where, include });
    // Laravel LEFT JOINs the related table for relation sorts.
    const sortMap: Record<string, [string, string]> = {
      status_pkwtt: ['status_pkwtt', 'pkwtt'],
      educational_level: ['educational_levels', 'level'],
      factory: ['factories', 'factory_name'],
      department: ['departments', 'department_name'],
      division: ['divisions', 'division'],
      section: ['sections', 'section'],
      line: ['production_lines', 'line_name'],
    };
    if (sortMap[sort]) {
      const [rel, col] = sortMap[sort];
      rows.sort((a, b) => {
        const av = a[rel]?.[col] ?? null;
        const bv = b[rel]?.[col] ?? null;
        if (av === bv) return 0;
        if (av === null) return 1;
        if (bv === null) return -1;
        const c = String(av).localeCompare(String(bv), 'en');
        return dir === 'desc' ? -c : c;
      });
    } else if (sort === 'start_date' || sort === 'date_of_birth') {
      rows.sort((a, b) => {
        const av = a[sort] ? new Date(a[sort]).getTime() : null;
        const bv = b[sort] ? new Date(b[sort]).getTime() : null;
        if (av === bv) return 0;
        if (av === null) return 1;
        if (bv === null) return -1;
        return dir === 'desc' ? bv - av : av - bv;
      });
    } else {
      rows.sort((a, b) => {
        const av = a[sort] ?? null;
        const bv = b[sort] ?? null;
        if (av === bv) return 0;
        if (av === null) return 1;
        if (bv === null) return -1;
        const c = typeof av === 'number' && typeof bv === 'number' ? av - bv : String(av).localeCompare(String(bv), 'en');
        return dir === 'desc' ? -c : c;
      });
    }
    const distinct = async (t: string, col: string) =>
      (await distinctRows(t, col)).map((r: any) => r[col]);
    const activeNames = async (t: string, col: string) =>
      (await table(t).findMany({ where: { status: 'active' }, orderBy: { [col]: 'asc' }, select: { [col]: true } })).map((r: any) => r[col]);
    filterValues = {
      operator_name: await distinct('operators', 'operator_name'),
      nik_karyawan: await distinct('operators', 'nik_karyawan'),
      gender: await distinct('operators', 'gender'),
      role: await distinct('operators', 'role'),
      status_pkwtt: await activeNames('status_pkwtt', 'pkwtt'),
      educational_level: await activeNames('educational_levels', 'level'),
      factory: await activeNames('factories', 'factory_name'),
      department: await activeNames('departments', 'department_name'),
      division: await activeNames('divisions', 'division'),
      section: await activeNames('sections', 'section'),
      line: await activeNames('production_lines', 'line_name'),
    };
    options = {
      genders: ser(await table('genders').findMany({ where: { status: 'active' }, orderBy: { gender: 'asc' } })),
      productionRoles: ser(await table('production_roles').findMany({ where: { status: 'active' }, orderBy: { production_role: 'asc' } })),
      statusPkwttList: ser(await table('status_pkwtt').findMany({ where: { status: 'active' }, orderBy: { pkwtt: 'asc' } })),
      educationalLevels: ser(await table('educational_levels').findMany({ where: { status: 'active' }, orderBy: { level: 'asc' } })),
      factories: ser(await table('factories').findMany({ where: { status: 'active' }, orderBy: { factory_name: 'asc' } })),
      departments: ser(await table('departments').findMany({ where: { status: 'active' }, orderBy: { department_name: 'asc' } })),
      divisions: ser(await table('divisions').findMany({ where: { status: 'active' }, orderBy: { division: 'asc' } })),
      sections: ser(await table('sections').findMany({ where: { status: 'active' }, orderBy: { section: 'asc' } })),
      productionLines: ser(await table('production_lines').findMany({ where: { status: 'active' }, orderBy: { line_name: 'asc' } })),
    };
  } else if (def.variant === 'processes') {
    const where: any = {};
    if (!showInactive) where.status = 'active';
    if (search !== '') {
      where.OR = [
        { process_name: like(search) },
        {
          process_versions: {
            some: {
              OR: [
                { version_number: { equals: isNaN(Number(search)) ? -1 : Number(search) } },
                { process_version_gsd_elements: { some: { gsd_elements: { OR: [{ code: like(search) }, { element_name: like(search) }] } } } },
              ],
            },
          },
        },
      ];
    }
    if (filterValue !== '') {
      if (filterColumn === 'process_name') where.process_name = filterValue;
      if (filterColumn === 'version_number') where.process_versions = { some: { version_number: isNaN(Number(filterValue)) ? -1 : Number(filterValue) } };
      if (filterColumn === 'gsd_code') {
        where.process_versions = { some: { process_version_gsd_elements: { some: { gsd_elements: { code: filterValue } } } } };
      }
    }
    rows = await table('processes').findMany({
      where,
      include: {
        process_versions: {
          include: {
            process_version_gsd_elements: { include: { gsd_elements: true } },
            gsd_elements: true,
          },
        },
      },
      orderBy: { [sort === 'status' ? 'status' : 'process_name']: dir },
    });
    filterValues = {
      process_name: (await distinctRows('processes', 'process_name')).map((r: any) => r.process_name),
      version_number: (await distinctRows('process_versions', 'version_number')).map((r: any) => Number(r.version_number)),
      gsd_code: (await distinctRows('gsd_elements', 'code')).map((r: any) => r.code),
    };
    options = {
      gsdCategories: ser(await table('gsd_categories').findMany({ where: { status: 'active' }, orderBy: { category_name: 'asc' } })),
      gsdElements: ser(
        await table('gsd_elements').findMany({
          where: { status: 'active' },
          include: { gsd_categories: true },
          orderBy: { element_name: 'asc' },
        })
      ),
    };
  }

  return success({
    rows: ser(rows),
    totalCount: rows.length,
    filterValues: ser(filterValues),
    options: ser(options),
  });
}

/* ------------------------------------------------------------------ */
/* create / update                                                     */
/* ------------------------------------------------------------------ */

async function readBody(req: NextRequest): Promise<{ fields: Record<string, any>; file: File | null }> {
  const ctype = req.headers.get('content-type') ?? '';
  if (ctype.includes('multipart/form-data')) {
    const form = await req.formData();
    const fields: Record<string, any> = {};
    let file: File | null = null;
    for (const [k, v] of Array.from(form.entries()) as [string, FormDataEntryValue][]) {
      if (v instanceof File) file = v;
      else fields[k] = v;
    }
    // multi-select arrays: gsd_element_ids[]
    const ids = form.getAll('gsd_element_ids[]').map((v) => Number(v));
    if (ids.length) fields.gsd_element_ids = ids;
    return { fields, file };
  }
  const body = await req.json().catch(() => ({}));
  return { fields: body, file: null };
}

async function savePhoto(file: File, dir: 'articles' | 'operators'): Promise<string> {
  const ext = file.name.toLowerCase().endsWith('.png') ? 'png' : file.name.toLowerCase().endsWith('.jpg') ? 'jpg' : 'jpeg';
  const name = `${rand(20)}.${ext}`;
  const rel = `uploads/${dir}/${name}`;
  const abs = path.join(process.cwd(), 'public', 'uploads', dir, name);
  fs.mkdirSync(path.dirname(abs), { recursive: true });
  fs.writeFileSync(abs, Buffer.from(await file.arrayBuffer()));
  return rel;
}

function photoError(file: File | null): Record<string, string[]> | null {
  if (!file || file.size === 0) return null;
  const okTypes = ['image/jpeg', 'image/jpg', 'image/png'];
  if (!okTypes.includes(file.type)) return { photo: ['The photo must be a file of type: jpg, jpeg, png.'] };
  if (file.size > 5120 * 1024) return { photo: ['The photo may not be greater than 5120 kilobytes.'] };
  return null;
}

async function createMaster(slug: string, req: NextRequest) {
  const def = MASTERS[slug];
  const auth = await authOf(req);
  if ('json' in auth) return auth;
  const { user } = auth;

  // Server-side viewer gate exists ONLY on the operators routes (web.php).
  if (def.variant === 'operators') {
    const guard = viewerGuard(user);
    if (guard) return guard;
  }

  const { fields, file } = await readBody(req);
  const { data, error } = await runValidation(def, fields);
  if (error) return error;
  const perr = photoError(file);
  if (perr) return validationFail(perr);

  const model = table(def.model);

  switch (def.variant) {
    case 'simple': {
      await model.create({ data: { [def.nameField]: data[def.nameField], description: data.description ?? null, status: 'active', created_at: new Date(), updated_at: new Date() } });
      await logActivity(user, fill(def.msg.logCreate, { value: data[def.nameField] }), def.msg.logModule);
      return success(null, def.msg.created, 201);
    }
    case 'factories': {
      await model.create({ data: { factory_name: data.factory_name, description: data.description ?? null, status: 'active', created_at: new Date(), updated_at: new Date() } });
      await logActivity(user, fill(def.msg.logCreate, { value: data.factory_name }), def.msg.logModule);
      return success(null, def.msg.created, 201);
    }
    case 'departments': {
      await model.create({
        data: {
          factory_id: BigInt(data.factory_id),
          department_name: data.department_name,
          desription: data.desription ?? null,
          status: 'active',
          created_at: new Date(),
          updated_at: new Date(),
        },
      });
      await logActivity(user, fill(def.msg.logCreate, { value: data.department_name }), def.msg.logModule);
      return success(null, def.msg.created, 201);
    }
    case 'destinations': {
      await model.create({ data: { destination: data.destination, description: data.description ?? null, status: 'active', created_at: new Date(), updated_at: new Date() } });
      await logActivity(user, fill(def.msg.logCreate, { value: data.destination }), def.msg.logModule);
      return success(null, def.msg.created, 201);
    }
    case 'production-lines': {
      // Laravel: ProductionLine::create([...$data, 'status' => 'active']) omits the
      // NOT NULL division_id column -> MySQL rejects the row (FK/implicit default).
      // Reproduced via a raw insert that omits the column exactly like Eloquent.
      try {
        await db.$executeRawUnsafe(
          `INSERT INTO production_lines (line_name, description, status, created_at, updated_at) VALUES (?, ?, 'active', NOW(), NOW())`,
          data.line_name,
          data.description ?? null
        );
      } catch (e: any) {
        return fail(`QueryException: ${e?.message ?? String(e)}`, 500);
      }
      await logActivity(user, fill(def.msg.logCreate, { value: data.line_name }), def.msg.logModule);
      return success(null, def.msg.created, 201);
    }
    case 'mechanics': {
      await model.create({
        data: {
          nik_karyawan: data.nik_karyawan ?? null,
          mechanic: data.mechanic,
          description: data.description ?? null,
          status: 'active',
          created_at: new Date(),
          updated_at: new Date(),
        },
      });
      await logActivity(user, fill(def.msg.logCreate, { value: data.mechanic }), def.msg.logModule);
      return success(null, def.msg.created, 201);
    }
    case 'articles': {
      const label = `LBL-${rand(10).toUpperCase()}`;
      await model.create({
        data: {
          article_name: data.article_name,
          description: data.description ?? null,
          label_number: label,
          label_number_quty: `${label}17596`,
          destination: '',
          photo_path: file ? await savePhoto(file, 'articles') : null,
          status: 'active',
          created_at: new Date(),
          updated_at: new Date(),
        },
      });
      await logActivity(user, fill(def.msg.logCreate, { value: data.article_name }), def.msg.logModule);
      return success(null, def.msg.created, 201);
    }
    case 'gsd-elements': {
      await model.create({
        data: {
          element_name: data.element_name,
          description: data.description ?? null,
          code: data.code,
          tmu: data.tmu,
          seconds: data.seconds,
          motion_sequence: data.motion_sequence ?? null,
          gsd_category_id: BigInt(data.gsd_category_id),
          status: 'active',
          created_at: new Date(),
          updated_at: new Date(),
        },
      });
      await logActivity(user, fill(def.msg.logCreate, { value: data.element_name }), def.msg.logModule);
      return success(null, def.msg.created, 201);
    }
    case 'operators': {
      const empNo = `OP-${rand(12).toUpperCase()}`;
      await model.create({
        data: {
          employee_number: empNo,
          operator_name: String(data.operator_name).toUpperCase(),
          nik_karyawan: data.nik_karyawan ? String(data.nik_karyawan).toUpperCase() : null,
          gender: data.gender ? String(data.gender).toUpperCase() : null,
          role: data.role ? String(data.role).toUpperCase() : null,
          photo_path: file ? await savePhoto(file, 'operators') : null,
          status_pkwtt_id: data.status_pkwtt_id ? BigInt(data.status_pkwtt_id) : null,
          educational_level_id: data.educational_level_id ? BigInt(data.educational_level_id) : null,
          start_date: dateVal(data.start_date),
          date_of_birth: dateVal(data.date_of_birth),
          factory_id: data.factory_id ? BigInt(data.factory_id) : null,
          department_id: data.department_id ? BigInt(data.department_id) : null,
          division_id: data.division_id ? BigInt(data.division_id) : null,
          section_id: data.section_id ? BigInt(data.section_id) : null,
          line_id: data.line_id ? BigInt(data.line_id) : null,
          status: 'active',
          created_at: new Date(),
          updated_at: new Date(),
        },
      });
      await logActivity(user, fill(def.msg.logCreate, { value: String(data.operator_name).toUpperCase() }), def.msg.logModule);
      return success(null, def.msg.created, 201);
    }
    case 'processes': {
      const process = await table('processes').create({
        data: { process_name: data.process_name, description: null, status: 'active', created_at: new Date(), updated_at: new Date() },
      });
      const version = await table('process_versions').create({
        data: {
          process_id: process.id,
          version_number: data.version_number ?? 1,
          notes: null,
          status: 'draft',
          created_by: BigInt(user.id),
          created_at: new Date(),
          updated_at: new Date(),
        },
      });
      const ids: number[] = Array.from(new Set<number>((data.gsd_element_ids ?? []).map((v: any) => Number(v)))).filter((n) => !isNaN(n));
      for (const gid of ids) {
        await table('process_version_gsd_elements').create({
          data: { process_version_id: version.id, gsd_element_id: BigInt(gid), created_at: new Date(), updated_at: new Date() },
        });
      }
      await logActivity(user, fill(def.msg.logCreate, { value: data.process_name }), def.msg.logModule);
      return success(null, def.msg.created, 201);
    }
  }
  return fail('Invalid data master.', 404);
}

async function updateMaster(slug: string, id: string, req: NextRequest) {
  const def = MASTERS[slug];
  const auth = await authOf(req);
  if ('json' in auth) return auth;
  const { user } = auth;
  if (def.variant === 'operators') {
    const guard = viewerGuard(user);
    if (guard) return guard;
  }

  const model = table(def.model);
  const row = await model.findUnique({ where: { id: BigInt(id) } }).catch(() => null);
  if (!row) return notFoundResult(modelName(def), id);

  const { fields, file } = await readBody(req);
  const { data, error } = await runValidation(def, fields, id);
  if (error) return error;
  const perr = photoError(file);
  if (perr) return validationFail(perr);

  switch (def.variant) {
    case 'simple': {
      await model.update({ where: { id: row.id }, data: { [def.nameField]: data[def.nameField], description: data.description ?? null, updated_at: new Date() } });
      await logActivity(user, fill(def.msg.logUpdate, { value: data[def.nameField] }), def.msg.logModule);
      return success(null, def.msg.updated);
    }
    case 'factories':
    case 'destinations':
    case 'production-lines': {
      await model.update({ where: { id: row.id }, data: { [def.nameField]: data[def.nameField], description: data.description ?? null, updated_at: new Date() } });
      await logActivity(user, fill(def.msg.logUpdate, { value: data[def.nameField] }), def.msg.logModule);
      return success(null, def.msg.updated);
    }
    case 'departments': {
      await model.update({
        where: { id: row.id },
        data: {
          factory_id: BigInt(data.factory_id),
          department_name: data.department_name,
          desription: data.desription ?? null,
          updated_at: new Date(),
        },
      });
      await logActivity(user, fill(def.msg.logUpdate, { value: data.department_name }), def.msg.logModule);
      return success(null, def.msg.updated);
    }
    case 'mechanics': {
      await model.update({
        where: { id: row.id },
        data: { nik_karyawan: data.nik_karyawan ?? null, mechanic: data.mechanic, description: data.description ?? null, updated_at: new Date() },
      });
      await logActivity(user, fill(def.msg.logUpdate, { value: data.mechanic }), def.msg.logModule);
      return success(null, def.msg.updated);
    }
    case 'articles': {
      const patch: any = { article_name: data.article_name, description: data.description ?? null, updated_at: new Date() };
      if (file) {
        patch.photo_path = await savePhoto(file, 'articles');
      }
      await model.update({ where: { id: row.id }, data: patch });
      await logActivity(user, fill(def.msg.logUpdate, { value: data.article_name }), def.msg.logModule);
      return success(null, def.msg.updated);
    }
    case 'gsd-elements': {
      await model.update({
        where: { id: row.id },
        data: {
          element_name: data.element_name,
          description: data.description ?? null,
          code: data.code,
          tmu: data.tmu,
          seconds: data.seconds,
          motion_sequence: data.motion_sequence ?? null,
          gsd_category_id: BigInt(data.gsd_category_id),
          updated_at: new Date(),
        },
      });
      await logActivity(user, fill(def.msg.logUpdate, { value: data.element_name }), def.msg.logModule);
      return success(null, def.msg.updated);
    }
    case 'operators': {
      const patch: any = {
        operator_name: String(data.operator_name).toUpperCase(),
        nik_karyawan: data.nik_karyawan ? String(data.nik_karyawan).toUpperCase() : null,
        gender: data.gender ? String(data.gender).toUpperCase() : null,
        role: data.role ? String(data.role).toUpperCase() : null,
        status_pkwtt_id: data.status_pkwtt_id ? BigInt(data.status_pkwtt_id) : null,
        educational_level_id: data.educational_level_id ? BigInt(data.educational_level_id) : null,
        start_date: dateVal(data.start_date),
        date_of_birth: dateVal(data.date_of_birth),
        factory_id: data.factory_id ? BigInt(data.factory_id) : null,
        department_id: data.department_id ? BigInt(data.department_id) : null,
        division_id: data.division_id ? BigInt(data.division_id) : null,
        section_id: data.section_id ? BigInt(data.section_id) : null,
        line_id: data.line_id ? BigInt(data.line_id) : null,
        updated_at: new Date(),
      };
      if (file) patch.photo_path = await savePhoto(file, 'operators');
      await model.update({ where: { id: row.id }, data: patch });
      await logActivity(user, fill(def.msg.logUpdate, { value: patch.operator_name }), def.msg.logModule);
      return success(null, def.msg.updated);
    }
    case 'processes': {
      await table('processes').update({ where: { id: row.id }, data: { process_name: data.process_name, updated_at: new Date() } });
      const versions = await table('process_versions').findMany({
        where: { process_id: row.id },
        orderBy: { version_number: 'desc' },
      });
      let version = versions[0];
      if (!version) {
        version = await table('process_versions').create({
          data: {
            process_id: row.id,
            version_number: data.version_number,
            status: 'draft',
            created_by: BigInt(user.id),
            created_at: new Date(),
            updated_at: new Date(),
          },
        });
      } else {
        version = await table('process_versions').update({
          where: { id: version.id },
          data: { version_number: data.version_number, updated_at: new Date() },
        });
      }
      const ids: number[] = Array.from(new Set<number>((data.gsd_element_ids ?? []).map((v: any) => Number(v)))).filter((n) => !isNaN(n));
      await table('process_version_gsd_elements').deleteMany({ where: { process_version_id: version.id } });
      for (const gid of ids) {
        await table('process_version_gsd_elements').create({
          data: { process_version_id: version.id, gsd_element_id: BigInt(gid), created_at: new Date(), updated_at: new Date() },
        });
      }
      await logActivity(user, fill(def.msg.logUpdate, { value: data.process_name }), def.msg.logModule);
      return success(null, def.msg.updated);
    }
  }
  return fail('Invalid data master.', 404);
}

function modelName(def: MasterDef) {
  return def.model.replace(/(^|_)([a-z])/g, (_, __, c) => c.toUpperCase()).replace(/_/g, '');
}

/* ------------------------------------------------------------------ */
/* deactivate (single + bulk)                                          */
/* ------------------------------------------------------------------ */

async function deactivateMaster(slug: string, id: string, req: NextRequest) {
  const def = MASTERS[slug];
  const auth = await authOf(req);
  if ('json' in auth) return auth;
  const { user } = auth;
  if (def.variant === 'operators') {
    const guard = viewerGuard(user);
    if (guard) return guard;
  }

  const model = table(def.model);
  const row = await model.findUnique({ where: { id: BigInt(id) } }).catch(() => null);
  if (!row) return notFoundResult(modelName(def), id);

  const guardMsg = def.msg.deactivateGuard;
  if (guardMsg) {
    let referenced = false;
    if (def.variant === 'articles') {
      referenced = (await table('ptms_reports').count({ where: { article_id: row.id } })) > 0;
    } else if (def.variant === 'production-lines') {
      referenced = (await table('ptms_reports').count({ where: { line_id: row.id } })) > 0;
    } else if (def.variant === 'operators') {
      referenced = (await table('ptms_reports').count({ where: { operator_id: row.id } })) > 0;
    } else if (def.variant === 'processes') {
      referenced =
        (await table('ptms_reports').count({
          where: { process_versions: { process_id: row.id } },
        })) > 0;
    }
    if (referenced) return fail(guardMsg, 422);
  }

  await model.update({ where: { id: row.id }, data: { status: 'inactive', updated_at: new Date() } });
  await logActivity(user, fill(def.msg.logDeactivate, { value: row[def.nameField] }), def.msg.logModule);
  return success(null, def.msg.deactivated);
}

async function bulkDeactivate(slug: string, req: NextRequest) {
  const def = MASTERS[slug];
  const auth = await authOf(req);
  if ('json' in auth) return auth;
  const { user } = auth;
  if (def.variant === 'operators') {
    const guard = viewerGuard(user);
    if (guard) return guard;
  }

  const body = await req.json().catch(() => ({}));
  const ids: any[] = Array.isArray(body.ids) ? body.ids : [];
  if (ids.length === 0) return fail(def.msg.bulkEmpty, 422);

  const bigIds = ids.map((v) => BigInt(v));
  if (def.variant === 'operators') {
    // web.php 722-740: employees referenced by historical reports are skipped.
    const protectedRows = await table('ptms_reports').findMany({
      where: { operator_id: { in: bigIds } },
      select: { operator_id: true },
      distinct: ['operator_id'],
    });
    const protectedIds = new Set(protectedRows.map((r: any) => String(r.operator_id)));
    const deactivatable = bigIds.filter((id) => !protectedIds.has(String(id)));
    const res = await table(def.model).updateMany({
      where: { id: { in: deactivatable } },
      data: { status: 'inactive', updated_at: new Date() },
    });
    let msg = fill(def.msg.bulkDone, { n: res.count });
    if (protectedIds.size > 0 && def.msg.bulkSkip) msg += fill(def.msg.bulkSkip, { k: protectedIds.size });
    await logActivity(user, fill(def.msg.logBulk, { n: res.count }), def.msg.logModule);
    return success(null, msg);
  }

  const result = await table(def.model).updateMany({
    where: { id: { in: bigIds } },
    data: { status: 'inactive', updated_at: new Date() },
  });
  const count = result.count;
  await logActivity(user, fill(def.msg.logBulk, { n: count }), def.msg.logModule);
  return success(null, fill(def.msg.bulkDone, { n: count }));
}

/* ------------------------------------------------------------------ */
/* hard delete (per-page route + central page)                         */
/* ------------------------------------------------------------------ */

/** intended dependency checks for the dead Laravel guards (see file header) */
async function hasDependencies(def: MasterDef, id: bigint): Promise<boolean> {
  switch (def.variant) {
    case 'articles':
      return (await table('ptms_reports').count({ where: { article_id: id } })) > 0;
    case 'production-lines':
      return (await table('ptms_reports').count({ where: { line_id: id } })) > 0;
    case 'operators':
      return (await table('ptms_reports').count({ where: { operator_id: id } })) > 0;
    case 'processes':
      return (await table('ptms_reports').count({ where: { process_versions: { process_id: id } } })) > 0;
    case 'factories':
      return (await table('departments').count({ where: { factory_id: id } })) > 0;
    case 'departments': {
      const ops = await table('operators').count({ where: { department_id: id } });
      const pts = await table('ptms_reports').count({ where: { department_id: id } });
      return ops + pts > 0;
    }
    default:
      return false;
  }
}

async function hardDeleteMaster(slug: string, req: NextRequest) {
  const def = MASTERS[slug];
  const auth = await authOf(req);
  if ('json' in auth) return auth;
  const { user } = auth;
  // web.php: only operators.hard-delete carries the developer gate.
  if (def.variant === 'operators') {
    const guard = developerGuard(user);
    if (guard) return guard;
  }

  const body = await req.json().catch(() => ({}));
  const ids: any[] = Array.isArray(body.ids) ? body.ids : [];
  if (ids.length === 0) return fail(def.msg.bulkEmpty, 422);

  const model = table(def.model);
  const inactive = await model.findMany({
    where: { id: { in: ids.map((v) => BigInt(v)) }, status: 'inactive' },
    select: { id: true },
  });
  const skipped = ids.length - inactive.length;
  let deleted = 0;
  for (const { id } of inactive) {
    if (await hasDependencies(def, id)) continue;
    if (def.variant === 'articles') {
      const a = await model.findUnique({ where: { id } });
      if (a?.photo_path) {
        const abs = path.join(process.cwd(), 'public', a.photo_path);
        if (fs.existsSync(abs)) fs.unlinkSync(abs);
      }
    }
    await model.delete({ where: { id } });
    deleted++;
  }
  let msg = fill(def.msg.hardDone, { n: deleted });
  if (skipped > 0) msg += fill(def.msg.hardSkip, { k: skipped });
  await logActivity(user, fill(def.msg.logHard, { n: deleted }), def.msg.logModule);
  return success(null, msg);
}

async function hardDeleteCounts(req: NextRequest) {
  const auth = await authOf(req);
  if ('json' in auth) return auth;
  const { user } = auth;
  // Laravel: Route::get('/hard-delete', ...)->middleware('role:developer').
  if (user.role?.role_name !== 'developer') {
    return fail('You do not have permission to access this page. Please Change your account role to access this page. aowkwk ngakak', 403);
  }
  const counts: Record<string, number> = {};
  for (const card of HARD_DELETE_CARDS) {
    counts[card.key] = await table(card.model).count({ where: { status: 'inactive' } });
  }
  return success({ counts });
}

async function hardDeleteRun(req: NextRequest) {
  const auth = await authOf(req);
  if ('json' in auth) return auth;
  const { user } = auth;
  if (user.role?.role_name !== 'developer') {
    return fail('You do not have permission to access this page. Please Change your account role to access this page. aowkwk ngakak', 403);
  }
  const body = await req.json().catch(() => ({}));
  const key = String(body.master_key ?? '');
  const card = HARD_DELETE_CARDS.find((c) => c.key === key);
  if (!card) return fail('Invalid data master.', 422);

  const count = await table(card.model).count({ where: { status: 'inactive' } });
  if (count === 0) return fail('No inactive records to delete.', 422);

  const label = key.replace(/_/g, ' ');
  try {
    const result = await table(card.model).deleteMany({ where: { status: 'inactive' } });
    const deleted = result.count;
    await logActivity(user, `Hard deleted ${deleted} inactive record(s) from ${label}`, 'Hard Delete');
    return success(null, `Permanently deleted ${deleted} inactive record(s) from ${label}.`);
  } catch (e: any) {
    const code = e?.code ?? '';
    // Prisma P2003 == MySQL 23000 (FK violation).
    if (code === 'P2003' || String(e?.message ?? '').includes('Foreign key')) {
      return fail(`Cannot delete inactive ${label} — some records are still referenced by other data. Remove dependent records first.`, 422);
    }
    return fail(`Failed to delete inactive ${label}: ${e?.message ?? String(e)}`, 500);
  }
}

/* ------------------------------------------------------------------ */
/* import (xlsx/xls/csv via SheetJS)                                   */
/* ------------------------------------------------------------------ */

function sheetRows(file: File, buf: ArrayBuffer): string[][] {
  const wb = XLSX.read(buf, { type: 'buffer' });
  const ws = wb.Sheets[wb.SheetNames[0]];
  const rows = XLSX.utils.sheet_to_json<any[]>(ws, { header: 1, raw: false, defval: '' });
  return rows.map((r) => (r ?? []).map((c) => String(c ?? '').trim()));
}

/** Carbon::parse equivalent for import date cells. */
function parseImportDate(v: string): string | null {
  if (!v) return null;
  const d = new Date(v);
  return isNaN(d.getTime()) ? null : d.toISOString().slice(0, 10);
}

async function importMaster(slug: string, req: NextRequest) {
  const def = MASTERS[slug];
  const auth = await authOf(req);
  if ('json' in auth) return auth;
  const { user } = auth;
  if (def.variant === 'operators') {
    const guard = viewerGuard(user);
    if (guard) return guard;
  }

  const { fields, file } = await readBody(req);
  if (!file || file.size === 0) return validationFail({ file: ['The file field is required.'] });
  const name = (file.name || '').toLowerCase();
  if (!/\.(xlsx|xls|csv)$/.test(name)) {
    return validationFail({ file: ['The file must be a file of type: xlsx, xls, csv.'] });
  }

  const buf = await file.arrayBuffer();
  let rows: string[][];
  try {
    rows = sheetRows(file, buf);
  } catch {
    return validationFail({ file: ['The file must be a file of type: xlsx, xls, csv.'] });
  }
  if (rows.length === 0) return success(null, fill(def.msg.imported, { n: 0 }));

  const header = rows[0].map((h) => h.toLowerCase());
  const idx = (name: string) => header.indexOf(name);
  let imported = 0;

  switch (def.variant) {
    case 'simple': {
      const nameIdx = idx(def.nameLabel.toLowerCase());
      const descIdx = idx('descriptions');
      for (let i = 1; i < rows.length; i++) {
        const nameVal = (rows[i][nameIdx] ?? '').trim();
        if (nameVal === '') continue;
        const payload = { description: (rows[i][descIdx] ?? '').trim(), status: 'active', updated_at: new Date(), created_at: new Date() };
        const existing = await table(def.model).findFirst({ where: { [def.nameField]: nameVal } });
        if (existing) {
          await table(def.model).update({ where: { id: existing.id }, data: { [def.nameField]: nameVal, ...payload } });
        } else {
          await table(def.model).create({ data: { [def.nameField]: nameVal, ...payload } });
        }
        imported++;
      }
      break;
    }
    case 'factories': {
      const nameIdx = idx('factory name');
      const descIdx = idx('descriptions');
      for (let i = 1; i < rows.length; i++) {
        const nameVal = (rows[i][nameIdx] ?? '').trim();
        if (nameVal === '') continue;
        const payload = { description: (rows[i][descIdx] ?? '').trim(), status: 'active', updated_at: new Date(), created_at: new Date() };
        const existing = await table('factories').findFirst({ where: { factory_name: nameVal } });
        if (existing) {
          await table('factories').update({ where: { id: existing.id }, data: payload });
        } else {
          await table('factories').create({ data: { factory_name: nameVal, ...payload } });
        }
        imported++;
      }
      break;
    }
    case 'departments': {
      const nameIdx = idx('department name');
      const descIdx = idx('descriptions');
      const factoryIdx = idx('factory');
      for (let i = 1; i < rows.length; i++) {
        const nameVal = (rows[i][nameIdx] ?? '').trim();
        if (nameVal === '') continue;
        let factoryId: bigint | null = null;
        if (factoryIdx !== -1) {
          const fn = (rows[i][factoryIdx] ?? '').trim();
          if (fn !== '') {
            const f = await table('factories').findFirst({ where: { factory_name: fn, status: 'active' } });
            factoryId = f?.id ?? null;
          }
        }
        const payload = { factory_id: factoryId, desription: (rows[i][descIdx] ?? '').trim(), status: 'active' };
        const existing = await table('departments').findFirst({ where: { department_name: nameVal } });
        if (existing) {
          await table('departments').update({ where: { id: existing.id }, data: { ...payload, updated_at: new Date(), created_at: new Date() } });
        } else {
          await table('departments').create({ data: { department_name: nameVal, ...payload, created_at: new Date(), updated_at: new Date() } as any });
        }
        imported++;
      }
      break;
    }
    case 'destinations': {
      const nameIdx = idx('destination');
      const descIdx = idx('descriptions');
      for (let i = 1; i < rows.length; i++) {
        const nameVal = (rows[i][nameIdx] ?? '').trim();
        if (nameVal === '') continue;
        const payload = { description: (rows[i][descIdx] ?? '').trim(), status: 'active', updated_at: new Date(), created_at: new Date() };
        const existing = await table('destinations').findFirst({ where: { destination: nameVal } });
        if (existing) {
          await table('destinations').update({ where: { id: existing.id }, data: payload });
        } else {
          await table('destinations').create({ data: { destination: nameVal, ...payload } });
        }
        imported++;
      }
      break;
    }
    case 'production-lines': {
      const nameIdx = idx('line name');
      const descIdx = idx('descriptions');
      for (let i = 1; i < rows.length; i++) {
        const nameVal = (rows[i][nameIdx] ?? '').trim();
        if (nameVal === '') continue;
        const existing = await table('production_lines').findFirst({ where: { line_name: nameVal } });
        if (existing) {
          await table('production_lines').update({ where: { id: existing.id }, data: { description: (rows[i][descIdx] ?? '').trim(), status: 'active', updated_at: new Date(), created_at: new Date() } });
        } else {
          // Laravel updateOrInsert also omits division_id here -> same DB failure as store.
          await db.$executeRawUnsafe(
            `INSERT INTO production_lines (line_name, description, status, created_at, updated_at) VALUES (?, ?, 'active', NOW(), NOW())`,
            nameVal,
            (rows[i][descIdx] ?? '').trim()
          );
        }
        imported++;
      }
      break;
    }
    case 'mechanics': {
      const nameIdx = idx('mechanic');
      const nikIdx = idx('nik karyawan');
      const descIdx = idx('descriptions');
      for (let i = 1; i < rows.length; i++) {
        const nameVal = (rows[i][nameIdx] ?? '').trim();
        if (nameVal === '') continue;
        const nik = nikIdx !== -1 ? (rows[i][nikIdx] ?? '').trim() : '';
        const payload = { nik_karyawan: nik !== '' ? nik : null, description: (rows[i][descIdx] ?? '').trim(), status: 'active', updated_at: new Date(), created_at: new Date() };
        const existing = await table('mechanics').findFirst({ where: { mechanic: nameVal } });
        if (existing) {
          await table('mechanics').update({ where: { id: existing.id }, data: payload });
        } else {
          await table('mechanics').create({ data: { mechanic: nameVal, ...payload } });
        }
        imported++;
      }
      break;
    }
    case 'articles': {
      const nameIdx = idx('article name');
      const labelIdx = idx('label number');
      const destIdx = idx('destination');
      const descIdx = idx('description');
      for (let i = 1; i < rows.length; i++) {
        const nameVal = (rows[i][nameIdx] ?? '').trim();
        if (nameVal === '') continue;
        const label = labelIdx !== -1 ? (rows[i][labelIdx] ?? '').trim() : '';
        const destination = destIdx !== -1 ? (rows[i][destIdx] ?? '').trim() : '';
        // web.php 1240: header absent -> null (keeps existing), header present -> trimmed string ('' overwrites).
        const description = descIdx !== -1 ? (rows[i][descIdx] ?? '').trim() : null;
        const existing = await table('articles').findFirst({ where: { article_name: nameVal } });
        if (existing) {
          // NOTE: blank description string overwrites the existing value in Laravel
          // too (the `??` operator only checks null) — replicated literally.
          await table('articles').update({
            where: { id: existing.id },
            data: {
              destination: destination !== '' ? destination : existing.destination,
              description: description ?? existing.description,
              status: 'active',
              updated_at: new Date(),
            },
          });
        } else {
          const autoLabel = label !== '' ? label : `LBL-${rand(10).toUpperCase()}`;
          await table('articles').create({
            data: {
              article_name: nameVal,
              label_number: autoLabel,
              label_number_quty: `${autoLabel}17596`,
              destination: destination,
              description,
              status: 'active',
              created_at: new Date(),
              updated_at: new Date(),
            },
          });
        }
        imported++;
      }
      break;
    }
    case 'gsd-elements': {
      const nameIdx = idx('element name');
      const codeIdx = idx('code');
      const tmuIdx = idx('tmu');
      const secIdx = idx('seconds');
      const motionIdx = idx('motion sequence');
      const descIdx = idx('descriptions');
      const catIdx = idx('category');
      for (let i = 1; i < rows.length; i++) {
        const nameVal = (rows[i][nameIdx] ?? '').trim();
        if (nameVal === '') continue;
        const tmuRaw = (rows[i][tmuIdx] ?? '').trim();
        const secRaw = (rows[i][secIdx] ?? '').trim();
        const tmu = isNaN(Number(tmuRaw)) ? 0 : Number(tmuRaw);
        const seconds = isNaN(Number(secRaw)) ? 0 : Number(secRaw);
        let categoryId: bigint | null = null;
        if (catIdx !== -1) {
          const catName = (rows[i][catIdx] ?? '').trim();
          if (catName !== '') {
            // Laravel GsdCategory::firstOrCreate(...) — existing rows untouched,
            // new rows are stamped like Eloquent.
            const cat =
              (await table('gsd_categories').findFirst({ where: { category_name: catName } })) ??
              (await table('gsd_categories').create({
                data: { category_name: catName, status: 'active', created_at: new Date(), updated_at: new Date() },
              }));
            categoryId = cat.id;
          }
        }
        // Laravel stores the trimmed strings verbatim (blank cell -> '' not NULL).
        const base = {
          code: (rows[i][codeIdx] ?? '').trim(),
          tmu,
          seconds,
          motion_sequence: motionIdx !== -1 ? (rows[i][motionIdx] ?? '').trim() : '',
          description: descIdx !== -1 ? (rows[i][descIdx] ?? '').trim() : '',
          status: 'active',
          updated_at: new Date(),
          created_at: new Date(),
        };
        const existing = await table('gsd_elements').findFirst({ where: { element_name: nameVal } });
        if (existing) {
          await table('gsd_elements').update({
            where: { id: existing.id },
            data: { ...base, ...(categoryId ? { gsd_category_id: categoryId } : {}), updated_at: new Date(), created_at: new Date() },
          });
        } else if (categoryId) {
          await table('gsd_elements').create({ data: { element_name: nameVal, gsd_category_id: categoryId, ...base } as any });
        } else {
          continue; // NOT NULL gsd_category_id cannot be satisfied (Laravel errors here too)
        }
        imported++;
      }
      break;
    }
    case 'operators': {
      const nameIdx = idx('operator name');
      const nikIdx = idx('nik karyawan');
      const genderIdx = idx('gender');
      const roleIdx = idx('role');
      const pkwttIdx = idx('status pkwtt');
      const eduIdx = idx('educational level');
      const startIdx = idx('start date');
      const dobIdx = idx('date of birth');
      const factoryIdx = idx('factory');
      const deptIdx = idx('department');
      const divIdx = idx('division');
      const secIdx = idx('section');
      const lineIdx = idx('line');
      for (let i = 1; i < rows.length; i++) {
        const nameVal = (rows[i][nameIdx] ?? '').trim();
        if (nameVal === '') continue;
        const findActive = async (t: string, col: string, val: string) => {
          if (val === '') return null;
          const r = await table(t).findFirst({ where: { [col]: val, status: 'active' } });
          return r?.id ?? null;
        };
        const data = {
          nik_karyawan: (rows[i][nikIdx] ?? '').trim().toUpperCase(),
          gender: (rows[i][genderIdx] ?? '').trim().toUpperCase(),
          role: (rows[i][roleIdx] ?? '').trim().toUpperCase(),
          status_pkwtt_id: await findActive('status_pkwtt', 'pkwtt', (rows[i][pkwttIdx] ?? '').trim()),
          educational_level_id: await findActive('educational_levels', 'level', (rows[i][eduIdx] ?? '').trim()),
          start_date: dateVal(parseImportDate((rows[i][startIdx] ?? '').trim())),
          date_of_birth: dateVal(parseImportDate((rows[i][dobIdx] ?? '').trim())),
          factory_id: await findActive('factories', 'factory_name', (rows[i][factoryIdx] ?? '').trim()),
          department_id: await findActive('departments', 'department_name', (rows[i][deptIdx] ?? '').trim()),
          division_id: await findActive('divisions', 'division', (rows[i][divIdx] ?? '').trim()),
          section_id: await findActive('sections', 'section', (rows[i][secIdx] ?? '').trim()),
          line_id: await findActive('production_lines', 'line_name', (rows[i][lineIdx] ?? '').trim()),
          employee_number: `OP-${rand(12).toUpperCase()}`,
          status: 'active',
        };
        const upperName = nameVal.toUpperCase();
        const existing = await table('operators').findFirst({ where: { operator_name: upperName } });
        if (existing) {
          await table('operators').update({ where: { id: existing.id }, data: { ...data, updated_at: new Date(), created_at: new Date() } });
        } else {
          await table('operators').create({ data: { operator_name: upperName, ...data, created_at: new Date(), updated_at: new Date() } as any });
        }
        imported++;
      }
      break;
    }
    case 'processes': {
      const nameIdx = idx('process name');
      const versionIdx = idx('version');
      for (let i = 1; i < rows.length; i++) {
        const nameVal = (rows[i][nameIdx] ?? '').trim();
        if (nameVal === '') continue;
        let process = await table('processes').findFirst({ where: { process_name: nameVal } });
        if (!process) {
          process = await table('processes').create({ data: { process_name: nameVal, description: null, status: 'active', created_at: new Date(), updated_at: new Date() } });
        }
        const versionNum = parseInt((rows[i][versionIdx] ?? '').trim(), 10) || 1;
        const existingVersion = await table('process_versions').findFirst({
          where: { process_id: process.id, version_number: versionNum },
        });
        if (!existingVersion) {
          await table('process_versions').create({
            data: { process_id: process.id, version_number: versionNum, notes: null, status: 'draft', created_by: BigInt(user.id), created_at: new Date(), updated_at: new Date() },
          });
        }
        imported++;
      }
      break;
    }
  }

  await logActivity(user, fill(def.msg.logImport, { n: imported }), def.msg.logModule);
  return success(null, fill(def.msg.imported, { n: imported }));
}

/* ------------------------------------------------------------------ */
/* export (xlsx via exceljs, applyExcelFormatting parity)              */
/* ------------------------------------------------------------------ */

function buildWorkbook(headers: string[], rows: (string | number | null)[][]): ExcelJS.Workbook {
  const wb = new ExcelJS.Workbook();
  const ws = wb.addWorksheet('Sheet1');
  ws.addRow(headers);
  for (const r of rows) ws.addRow(r);
  // applyExcelFormatting(): thin borders + center/vertical-center + autoSize
  ws.eachRow((row) => {
    row.eachCell((cell) => {
      cell.border = {
        top: { style: 'thin' },
        left: { style: 'thin' },
        bottom: { style: 'thin' },
        right: { style: 'thin' },
      };
      cell.alignment = { horizontal: 'center', vertical: 'middle' };
    });
  });
  ws.columns.forEach((col) => {
    let max = 10;
    col.eachCell?.({ includeEmpty: true }, (cell) => {
      const len = String(cell.value ?? '').length;
      if (len > max) max = len;
    });
    col.width = max + 2;
  });
  return wb;
}

async function exportMaster(slug: string, req: NextRequest) {
  const def = MASTERS[slug];
  const auth = await authOf(req);
  if ('json' in auth) return auth;
  const { user } = auth;

  const model = table(def.model);
  let headers = def.exportHeaders;
  let data: (string | number | null)[][] = [];

  if (def.variant === 'processes') {
    const processes = await model.findMany({
      where: { status: 'active' },
      include: { process_versions: { include: { process_version_gsd_elements: { include: { gsd_elements: true } } } } },
      orderBy: { process_name: 'asc' },
    });
    let rowNo = 1;
    for (const p of processes) {
      for (const v of p.process_versions ?? []) {
        const codes = (v.process_version_gsd_elements ?? [])
          .map((x: any) => x.gsd_elements?.code)
          .filter(Boolean)
          .join(', ');
        data.push([rowNo++, p.process_name, Number(v.version_number), codes]);
      }
    }
  } else if (def.variant === 'operators') {
    const ops = await model.findMany({
      where: { status: 'active' },
      include: { status_pkwtt: true, educational_levels: true, factories: true, departments: true, divisions: true, sections: true, production_lines: true },
      orderBy: { operator_name: 'asc' },
    });
    data = ops.map((o: any, i: number) => [
      i + 1,
      o.operator_name,
      o.nik_karyawan,
      o.gender,
      o.role,
      o.factories?.factory_name ?? null,
      o.departments?.department_name ?? null,
      o.divisions?.division ?? null,
      o.sections?.section ?? null,
      o.production_lines?.line_name ?? null,
      o.status_pkwtt?.pkwtt ?? null,
      o.educational_levels?.level ?? null,
      o.start_date ? dateOnly(o.start_date) : null,
      o.date_of_birth ? dateOnly(o.date_of_birth) : null,
    ]);
  } else if (def.variant === 'gsd-elements') {
    const items = await model.findMany({
      where: { status: 'active' },
      include: { gsd_categories: true },
      orderBy: { element_name: 'asc' },
    });
    data = items.map((e: any, i: number) => [
      i + 1,
      e.element_name,
      e.code,
      e.tmu !== null && e.tmu !== undefined ? Number(e.tmu) : null,
      e.seconds !== null && e.seconds !== undefined ? Number(e.seconds) : null,
      e.motion_sequence,
      e.gsd_categories?.category_name ?? null,
      e.description,
    ]);
  } else if (def.variant === 'articles') {
    const items = await model.findMany({ where: { status: 'active' }, orderBy: { article_name: 'asc' } });
    data = items.map((a: any, i: number) => [i + 1, a.article_name]);
  } else if (def.variant === 'mechanics') {
    const items = await model.findMany({ where: { status: 'active' }, orderBy: { mechanic: 'asc' } });
    data = items.map((m: any, i: number) => [i + 1, m.nik_karyawan, m.mechanic, m.description]);
  } else if (def.variant === 'departments') {
    const items = await model.findMany({ where: { status: 'active' }, orderBy: { department_name: 'asc' } });
    data = items.map((d: any, i: number) => [i + 1, d.department_name, d.desription]);
  } else {
    // simple / factories / destinations / production-lines.
    // NOTE: production-lines export has NO active filter in Laravel (quirk #16).
    const where = def.variant === 'production-lines' ? {} : { status: 'active' };
    const items = await model.findMany({ where, orderBy: { [def.nameField]: 'asc' } });
    data = items.map((r: any, i: number) => [i + 1, r[def.nameField], r[def.descField]]);
  }

  const wb = buildWorkbook(headers, data);
  const buf = Buffer.from(await wb.xlsx.writeBuffer());
  if (def.msg.logExport) {
    await logActivity(user, def.msg.logExport, def.msg.logModule);
  }
  return new NextResponse(buf, {
    headers: {
      'Content-Type': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'Content-Disposition': `attachment; filename="${def.exportFile}"`,
    },
  });
}

/* ------------------------------------------------------------------ */
/* autocomplete search                                                 */
/* ------------------------------------------------------------------ */

async function searchMaster(slug: string, req: NextRequest) {
  const def = MASTERS[slug];
  const auth = await authOf(req);
  if ('json' in auth) return auth;

  const q = (req.nextUrl.searchParams.get('q') ?? '').trim();
  if (q === '') return NextResponse.json([]);

  let items: { id: any; label: any; description: any }[] = [];
  const active = { status: 'active' };

  switch (def.variant) {
    case 'simple':
    case 'factories':
    case 'destinations':
    case 'production-lines': {
      const rows = await table(def.model).findMany({
        where: {
          ...active,
          OR: [{ [def.nameField]: like(q) }, { [def.descField]: like(q) }],
        },
        orderBy: { [def.nameField]: 'asc' },
        take: 10,
      });
      items = rows.map((r: any) => ({ id: r.id, label: r[def.nameField], description: r[def.descField] ?? '' }));
      break;
    }
    case 'departments': {
      const rows = await table('departments').findMany({
        where: { ...active, department_name: like(q) },
        orderBy: { department_name: 'asc' },
        take: 10,
      });
      items = rows.map((r: any) => ({ id: r.id, label: r.department_name, description: r.desription ?? '' }));
      break;
    }
    case 'mechanics': {
      const rows = await table('mechanics').findMany({
        where: { ...active, OR: [{ nik_karyawan: like(q) }, { mechanic: like(q) }] },
        orderBy: { mechanic: 'asc' },
        take: 10,
      });
      items = rows.map((r: any) => ({ id: r.id, label: r.mechanic, description: r.nik_karyawan ?? '' }));
      break;
    }
    case 'articles': {
      const rows = await table('articles').findMany({
        where: { ...active, OR: [{ article_name: like(q) }, { destination: like(q) }] },
        orderBy: { article_name: 'asc' },
        take: 10,
      });
      items = rows.map((r: any) => ({ id: r.id, label: r.article_name, description: r.destination ?? '' }));
      break;
    }
    case 'gsd-elements': {
      const rows = await table('gsd_elements').findMany({
        where: {
          ...active,
          OR: [{ element_name: like(q) }, { code: like(q) }, { motion_sequence: like(q) }],
        },
        orderBy: { element_name: 'asc' },
        take: 10,
      });
      items = rows.map((r: any) => ({
        id: r.id,
        label: r.element_name,
        description: `${r.code ?? ''}${r.motion_sequence ? ' — ' + r.motion_sequence : ''}`,
      }));
      break;
    }
    case 'operators': {
      const rows = await table('operators').findMany({
        where: { ...active, OR: [{ operator_name: like(q) }, { nik_karyawan: like(q) }] },
        orderBy: { operator_name: 'asc' },
        take: 10,
      });
      items = rows.map((r: any) => ({ id: r.id, label: r.operator_name, description: r.nik_karyawan ?? '' }));
      break;
    }
    case 'processes': {
      const rows = await table('processes').findMany({
        where: { ...active, process_name: like(q) },
        orderBy: { process_name: 'asc' },
        take: 10,
      });
      items = rows.map((r: any) => ({ id: r.id, label: r.process_name, description: r.description ?? '' }));
      break;
    }
  }
  return NextResponse.json(ser(items));
}

export {
  listMaster,
  createMaster,
  updateMaster,
  deactivateMaster,
  bulkDeactivate,
  hardDeleteMaster,
  hardDeleteCounts,
  hardDeleteRun,
  importMaster,
  exportMaster,
  searchMaster,
};
