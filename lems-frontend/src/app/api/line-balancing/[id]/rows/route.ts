import { NextRequest } from "next/server";
import { authenticate, success, fail, notFoundResult, unauthenticated } from "@/lib/http";
import { db } from "@/lib/db";

/**
 * POST /api/line-balancing/:id/rows — bulk-replace all rows.
 * Replica of Laravel LineBalancingController saveRows.
 */
export async function POST(
  req: NextRequest,
  { params }: { params: { id: string } }
) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();

  if (!/^\d+$/.test(params.id)) return notFoundResult("LineBalancingReport", params.id);
  const existing = await db.line_balancing_reports.findUnique({ where: { id: Number(params.id) } });
  if (!existing) return notFoundResult("LineBalancingReport", params.id);

  const body = await req.json().catch(() => ({}));
  const rows: any[] = Array.isArray(body.rows) ? body.rows : [];

  // Update target_output_per_hour and output_actual if provided
  const headerUpdate: any = {};
  if (body.target_output_per_hour !== undefined) headerUpdate.target_output_per_hour = parseInt(body.target_output_per_hour) || 0;
  if (body.output_actual !== undefined) headerUpdate.output_actual = body.output_actual != null ? parseInt(body.output_actual) : null;

  try {
    // Transaction: delete existing rows + insert new ones + update header
    const result = await db.$transaction(async (tx) => {
      // Delete existing rows
      await tx.line_balancing_report_rows.deleteMany({
        where: { line_balancing_report_id: Number(params.id) },
      });

      // Insert new rows
      if (rows.length > 0) {
        await tx.line_balancing_report_rows.createMany({
          data: rows.map((row: any, idx: number) => ({
            line_balancing_report_id: Number(params.id),
            row_number: row.row_number ?? idx + 1,
            machine_type_id: row.machine_type_id ? BigInt(row.machine_type_id) : null,
            process: row.process ?? null,
            name: row.name ?? null,
            employee_id: row.employee_id ? BigInt(row.employee_id) : null,
            joint_process: row.joint_process ?? null,
            operator: row.operator ?? 1,
            ct_1: row.ct_1 != null ? parseFloat(row.ct_1) : null,
            ct_2: row.ct_2 != null ? parseFloat(row.ct_2) : null,
            ct_3: row.ct_3 != null ? parseFloat(row.ct_3) : null,
            ct_4: row.ct_4 != null ? parseFloat(row.ct_4) : null,
            ct_5: row.ct_5 != null ? parseFloat(row.ct_5) : null,
          })),
        });
      }

      // Update header if needed
      if (Object.keys(headerUpdate).length > 0) {
        await tx.line_balancing_reports.update({
          where: { id: Number(params.id) },
          data: headerUpdate,
        });
      }

      return rows.length;
    });

    return success({ saved: result }, "Rows saved successfully");
  } catch (e: any) {
    return fail(`SQLSTATE[23000]: Integrity constraint violation: ${e?.message ?? e}`, 500);
  }
}