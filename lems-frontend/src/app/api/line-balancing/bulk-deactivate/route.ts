import { NextRequest } from "next/server";
import { authenticate, success, fail, unauthenticated } from "@/lib/http";
import { db } from "@/lib/db";

/**
 * PATCH /api/line-balancing/bulk-deactivate — set status=inactive for multiple reports.
 * Replica of Laravel LineBalancingController bulkDeactivate.
 */
export async function PATCH(req: NextRequest) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();

  const body = await req.json().catch(() => ({}));
  const ids: number[] = Array.isArray(body.ids) ? body.ids.map(Number).filter((n: number) => !isNaN(n)) : [];

  if (ids.length === 0) return fail("No IDs provided", 422);

  try {
    const result = await db.line_balancing_reports.updateMany({
      where: { id: { in: ids.map(BigInt) } },
      data: { status: "inactive" },
    });
    return success({ deactivated: result.count }, `${result.count} report(s) deactivated successfully`);
  } catch (e: any) {
    return fail(`Error: ${e?.message ?? e}`, 500);
  }
}