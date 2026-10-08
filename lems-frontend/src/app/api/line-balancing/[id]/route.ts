import { NextRequest } from "next/server";
import { authenticate, success, fail, notFoundResult, unauthenticated } from "@/lib/http";
import { db } from "@/lib/db";

/**
 * GET  /api/line-balancing/:id — single report with rows + all relations.
 * PUT  /api/line-balancing/:id — update header fields.
 * PATCH /api/line-balancing/:id/deactivate — soft-delete (status=inactive).
 *
 * Replica of Laravel LineBalancingController edit/update/deactivate.
 */

const toNum = (v: any) => (v != null ? Number(v) : null);
const toStr = (v: any) => (v != null ? String(v) : null);

async function getReport(id: string) {
  if (!/^\d+$/.test(id)) return null;
  return db.line_balancing_reports.findUnique({
    where: { id: Number(id) },
    include: {
      factories: true,
      articles: true,
      production_lines: true,
      users: true,
      line_balancing_report_rows: {
        include: { machine_types: true, operators: true },
        orderBy: { row_number: "asc" },
      },
    },
  });
}

function serializeReport(r: any) {
  return {
    id: toNum(r.id),
    factory_id: toNum(r.factory_id),
    factory_name: r.factories?.factory_name ?? null,
    article_id: toNum(r.article_id),
    article_name: r.articles?.article_name ?? null,
    line_id: toNum(r.line_id),
    line_name: r.production_lines?.line_name ?? null,
    report_name: r.report_name,
    target_output_per_hour: r.target_output_per_hour,
    output_actual: r.output_actual,
    working_hours_per_day: toNum(r.working_hours_per_day),
    allowance_percent: toNum(r.allowance_percent),
    update_date: r.update_date ? new Date(r.update_date).toISOString().split("T")[0] : null,
    status: r.status,
    created_by: toNum(r.created_by),
    created_by_name: r.users?.name ?? null,
    created_at: r.created_at?.toISOString() ?? null,
    updated_at: r.updated_at?.toISOString() ?? null,
    rows: (r.line_balancing_report_rows ?? []).map((row: any) => ({
      id: toNum(row.id),
      row_number: row.row_number,
      machine_type_id: toNum(row.machine_type_id),
      machine_type_name: row.machine_types?.machine_type ?? null,
      process: row.process,
      name: row.name,
      employee_id: toNum(row.employee_id),
      employee_name: row.operators?.operator_name ?? null,
      joint_process: row.joint_process,
      operator: row.operator,
      ct_1: toNum(row.ct_1),
      ct_2: toNum(row.ct_2),
      ct_3: toNum(row.ct_3),
      ct_4: toNum(row.ct_4),
      ct_5: toNum(row.ct_5),
    })),
    // Dropdown data for the edit page
    machine_types: [],
    operators_list: [],
  };
}

export async function GET(
  req: NextRequest,
  { params }: { params: { id: string } }
) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();

  const report = await getReport(params.id);
  if (!report) return notFoundResult("LineBalancingReport", params.id);

  // Also load active machine types and operators for dropdowns
  const [machineTypes, operatorsList] = await Promise.all([
    db.machine_types.findMany({ where: { status: "active" }, orderBy: { machine_type: "asc" } }),
    db.operators.findMany({ where: { status: "active" }, orderBy: { operator_name: "asc" } }),
  ]);

  const result = serializeReport(report) as any;
  result.machine_types = machineTypes.map((m) => ({ id: toNum(m.id), machine_type: m.machine_type }));
  result.operators_list = operatorsList.map((o) => ({ id: toNum(o.id), operator_name: o.operator_name, employee_number: o.employee_number, nik_karyawan: o.nik_karyawan }));

  return success(result);
}

export async function PUT(
  req: NextRequest,
  { params }: { params: { id: string } }
) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();

  if (!/^\d+$/.test(params.id)) return notFoundResult("LineBalancingReport", params.id);
  const existing = await db.line_balancing_reports.findUnique({ where: { id: Number(params.id) } });
  if (!existing) return notFoundResult("LineBalancingReport", params.id);

  const body = await req.json().catch(() => ({}));
  const updateData: any = {};
  if (body.target_output_per_hour !== undefined) updateData.target_output_per_hour = parseInt(body.target_output_per_hour) || 0;
  if (body.output_actual !== undefined) updateData.output_actual = body.output_actual != null ? parseInt(body.output_actual) : null;
  if (body.working_hours_per_day !== undefined) updateData.working_hours_per_day = parseFloat(body.working_hours_per_day) || 8.0;
  if (body.allowance_percent !== undefined) updateData.allowance_percent = parseFloat(body.allowance_percent) || 15.0;
  if (body.update_date !== undefined) updateData.update_date = body.update_date ? new Date(body.update_date) : null;

  try {
    const updated = await db.line_balancing_reports.update({
      where: { id: Number(params.id) },
      data: updateData,
      include: { factories: true, articles: true, production_lines: true },
    });
    return success(
      {
        id: toNum(updated.id),
        report_name: updated.report_name,
        factory_name: updated.factories?.factory_name ?? null,
        article_name: updated.articles?.article_name ?? null,
        line_name: updated.production_lines?.line_name ?? null,
      },
      "Line Balancing Report updated successfully"
    );
  } catch (e: any) {
    return fail(`SQLSTATE[23000]: Integrity constraint violation: ${e?.message ?? e}`, 500);
  }
}