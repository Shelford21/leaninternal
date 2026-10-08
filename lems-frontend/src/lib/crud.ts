import { NextRequest } from "next/server";
import { EntityConfig } from "./entities";
import {
  fail,
  notFoundResult,
  paginated,
  rateLimit,
  success,
  unauthenticated,
  validationFail,
  authenticate,
  AuthedUser,
} from "./http";
import { table } from "./db";
import { validate } from "./validation";

/* Generic CRUD handlers reproducing the Laravel Api resource controllers. */

function buildInclude(cfg: EntityConfig, withParam: string | null) {
  const relations = new Set<string>();
  if (withParam) {
    for (const token of withParam.split(",")) {
      const rel = cfg.includeAliases?.[token.trim()];
      if (rel) relations.add(rel);
    }
  } else {
    (cfg.defaultInclude ?? []).forEach((r) => relations.add(r));
  }
  const include: Record<string, boolean> = {};
  relations.forEach((r) => (include[r] = true));
  return include;
}

/**
 * Fixed include set for a write/read endpoint. Laravel controllers load
 * relations per method (e.g. FactoryController::show always loads
 * 'departments' but store/update load nothing) and IGNORE ?with= outside
 * index. `fallback` is used when the per-method override is undefined.
 */
function fixedInclude(rels: string[] | undefined, fallback: string[] = []) {
  const include: Record<string, boolean> = {};
  (rels ?? fallback).forEach((r) => (include[r] = true));
  return include;
}

/**
 * Coerce validated values to the DB column scalar types so Prisma does not
 * throw (Laravel lets MySQL coerce, e.g. factory_name: 5 is accepted and
 * stored as "5"). Returns the coerced payload plus `echo`, the raw values to
 * mirror back in the response (Eloquent echoes request values in memory).
 */
function coerceForWrite(
  cfg: EntityConfig,
  data: Record<string, any>
): { payload: Record<string, any>; echo: Record<string, any> } {
  const rules = { ...cfg.storeRules, ...cfg.updateRules };
  const payload: Record<string, any> = { ...data };
  const echo: Record<string, any> = {};
  for (const [key, value] of Object.entries(data)) {
    if (value === null || value === undefined) continue;
    const rule = rules[key] ?? "";
    const coerced =
      rule.includes("numeric") || /_id$/.test(key)
        ? typeof value === "number"
          ? value
          : Number(value)
        : typeof value === "string"
          ? value
          : String(value);
    payload[key] = Number.isNaN(coerced as number) ? value : coerced;
    if (payload[key] !== value) echo[key] = value;
  }
  return { payload, echo };
}

function attachRelations(cfg: EntityConfig, row: any) {
  // Rename Prisma relation keys to the Laravel resource keys.
  if (!cfg.relationKeys) return row;
  const out: any = { ...row };
  for (const [prismaKey, resourceKey] of Object.entries(cfg.relationKeys)) {
    if (prismaKey in out && resourceKey !== prismaKey) {
      out[prismaKey] = out[prismaKey]; // kept: serializers read Prisma keys
    }
  }
  return out;
}

async function gate(
  req: NextRequest,
  roles?: string[]
): Promise<{ user: AuthedUser } | { response: Response }> {
  const auth = await authenticate(req);
  if (!auth) return { response: unauthenticated() };
  const limited = rateLimit(req, auth.user.id);
  if (limited) return { response: limited };
  if (roles && roles.length > 0) {
    const forbidden = requireRoleLocal(auth.user, roles);
    if (forbidden) return { response: forbidden };
  }
  return { user: auth.user };
}

function requireRoleLocal(user: AuthedUser, roles: string[]) {
  if (!user.role || !roles.includes(user.role.role_name)) {
    return fail(
      "You do not have permission to access this page. Please Change your account role to access this page. aowkwk ngakak",
      403
    );
  }
  return null;
}

export async function handleIndex(
  req: NextRequest,
  cfg: EntityConfig,
  roles?: string[]
): Promise<Response> {
  const gateResult = await gate(req, roles);
  if ("response" in gateResult) return gateResult.response;

  const sp = req.nextUrl.searchParams;
  const page = Math.max(1, parseInt(sp.get("page") ?? "1", 10) || 1);
  const perPage = Math.min(parseInt(sp.get("per_page") ?? "15", 10) || 15, 100);

  const where: Record<string, any> = {};
  const search = sp.get("search");
  if (search && cfg.searchFields?.length) {
    where.OR = cfg.searchFields.map((f) => ({ [f]: { contains: search } }));
  }
  for (const f of cfg.filters ?? []) {
    const v = sp.get(f);
    if (v !== null && v !== "") where[f] = /^\d+$/.test(v) ? Number(v) : v;
  }

  const sortField = sp.get("sort") ?? cfg.defaultSort?.field ?? "id";
  const sortOrder =
    (sp.get("order") ?? cfg.defaultSort?.order ?? "asc") === "desc" ? "desc" : "asc";

  const include = buildInclude(cfg, sp.get("with"));

  let total: number;
  let rows: any[];
  try {
    [total, rows] = await Promise.all([
      table(cfg.model).count({ where }),
      table(cfg.model).findMany({
        where,
        include,
        orderBy: { [sortField]: sortOrder },
        skip: (page - 1) * perPage,
        take: perPage,
      }),
    ]);
  } catch (e: any) {
    // Laravel surfaces bad sort columns as a 500 SQL error.
    return fail(`SQLSTATE[42S22]: Column not found: ${e?.message ?? e}`, 500);
  }

  return paginated(
    req,
    rows.map((r: any) => cfg.serialize(attachRelations(cfg, r))),
    { page, perPage, total }
  );
}

export async function handleStore(
  req: NextRequest,
  cfg: EntityConfig,
  roles?: string[]
): Promise<Response> {
  const gateResult = await gate(req, roles);
  if ("response" in gateResult) return gateResult.response;
  const { user } = gateResult;

  const body = await req.json().catch(() => ({}));
  const { data, errors } = await validate(body, cfg.storeRules);
  if (errors) return validationFail(errors);

  const { payload: coerced, echo } = coerceForWrite(cfg, data);
  let payload = coerced;
  if (cfg.beforeCreate) payload = await cfg.beforeCreate(payload, user.id);
  // Eloquent sets both timestamps on create ($timestamps = true on all models).
  const now = new Date();
  payload = { ...payload, created_at: now, updated_at: now };

  try {
    // Laravel store() uses a fixed relation set (often none) and ignores ?with=.
    const include = fixedInclude(cfg.storeInclude);
    const row = await table(cfg.model).create({ data: payload, include });
    return success(
      cfg.serialize(attachRelations(cfg, { ...row, ...echo })),
      `${cfg.label} created successfully`,
      201
    );
  } catch (e: any) {
    return fail(`SQLSTATE[23000]: Integrity constraint violation: ${e?.message ?? e}`, 500);
  }
}

export async function handleShow(
  req: NextRequest,
  cfg: EntityConfig,
  id: string,
  roles?: string[]
): Promise<Response> {
  const gateResult = await gate(req, roles);
  if ("response" in gateResult) return gateResult.response;

  if (!/^\d+$/.test(id)) return notFoundResult(cfg.modelClass, id);

  const row = await table(cfg.model).findUnique({
    where: { id: Number(id) },
    // Laravel show() always loads its fixed relation set and ignores ?with=.
    include: fixedInclude(cfg.showInclude, cfg.defaultInclude),
  });
  if (!row) return notFoundResult(cfg.modelClass, id);

  return success(cfg.serialize(attachRelations(cfg, row)));
}

export async function handleAll(
  req: NextRequest,
  cfg: EntityConfig,
  roles?: string[]
): Promise<Response> {
  const gateResult = await gate(req, roles);
  if ("response" in gateResult) return gateResult.response;

  // NOTE: Laravel has no /{resource}/all endpoints (the SPA calls them and gets
  // 404s). Provided here so the SPA dropdowns work. Documented deviation.
  const rows = await table(cfg.model).findMany({
    include: buildInclude(cfg, null),
    orderBy: { id: "asc" },
  });
  return new Response(JSON.stringify(rows.map((r: any) => cfg.serialize(attachRelations(cfg, r)))), {
    status: 200,
    headers: { "Content-Type": "application/json" },
  });
}

export async function handleUpdate(
  req: NextRequest,
  cfg: EntityConfig,
  id: string,
  roles?: string[]
): Promise<Response> {
  const gateResult = await gate(req, roles);
  if ("response" in gateResult) return gateResult.response;
  const { user } = gateResult;

  if (!/^\d+$/.test(id)) return notFoundResult(cfg.modelClass, id);
  const existing = await table(cfg.model).findUnique({
    where: { id: Number(id) },
    include: fixedInclude(cfg.updateInclude ?? cfg.defaultInclude),
  });
  if (!existing) return notFoundResult(cfg.modelClass, id);

  const guardMessage = cfg.updateGuard?.(existing, { id: user.id, role: user.role });
  if (guardMessage) return fail(guardMessage, 403);

  const body = await req.json().catch(() => ({}));
  const { data, errors } = await validate(body, cfg.updateRules, id);
  if (errors) return validationFail(errors);

  const { payload: coerced, echo } = coerceForWrite(cfg, data);
  let payload = coerced;
  if (cfg.beforeUpdate) payload = await cfg.beforeUpdate(payload);
  // Eloquent touches updated_at on every save() (updateTimestamps()).
  payload = { ...payload, updated_at: new Date() };

  try {
    // Laravel update() uses a fixed relation set (often none) and ignores ?with=.
    const include = fixedInclude(cfg.updateInclude);
    const row = await table(cfg.model).update({
      where: { id: Number(id) },
      data: payload,
      include,
    });
    return success(
      cfg.serialize(attachRelations(cfg, { ...row, ...echo })),
      `${cfg.label} updated successfully`
    );
  } catch (e: any) {
    return fail(`SQLSTATE[23000]: Integrity constraint violation: ${e?.message ?? e}`, 500);
  }
}

export async function handleDestroy(
  req: NextRequest,
  cfg: EntityConfig,
  id: string,
  roles?: string[]
): Promise<Response> {
  const gateResult = await gate(req, roles);
  if ("response" in gateResult) return gateResult.response;
  const { user } = gateResult;

  if (!/^\d+$/.test(id)) return notFoundResult(cfg.modelClass, id);
  const existing = await table(cfg.model).findUnique({ where: { id: Number(id) } });
  if (!existing) return notFoundResult(cfg.modelClass, id);

  const guardMessage = cfg.destroyGuard?.(existing, user.id);
  if (guardMessage) return fail(guardMessage, 403);

  try {
    // HARD DELETE — no SoftDeletes in the Laravel models (preserved).
    await table(cfg.model).delete({ where: { id: Number(id) } });
  } catch (e: any) {
    // FK-restricted parents surface as an SQL integrity error in Laravel too.
    return fail(`SQLSTATE[23000]: Integrity constraint violation: ${e?.message ?? e}`, 500);
  }

  return success(null, `${cfg.label} deleted successfully`);
}
