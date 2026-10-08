import { NextRequest } from "next/server";
import { authenticate, success, fail, notFoundResult, unauthenticated } from "@/lib/http";
import { db } from "@/lib/db";

/**
 * PATCH /api/line-balancing/:id/deactivate — set status=inactive.
 * Replica of Laravel LineBalancingController deactivate.
 */
export async function PATCH(
  req: NextRequest,
  { params }: { params: { id: string } }
) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();

  if (!/^\d+$/.test(params.id)) return notFoundResult("LineBalancingReport", params.id);
  const existing = await db.line_balancing_reports.findUnique({ where: { id: Number(params.id) } });
  if (!existing) return notFoundResult("LineBalancingReport", params.id);

  try {
    await db.line_balancing_reports.update({
      where: { id: Number(params.id) },
      data: { status: "inactive" },
    });
    return success(null, "Line Balancing Report deactivated successfully");
  } catch (e: any) {
    return fail(`Error: ${e?.message ?? e}`, 500);
  }
}