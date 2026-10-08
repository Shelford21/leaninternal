import { NextRequest } from "next/server";
import { table } from "@/lib/db";
import { authenticate, rateLimit, success, unauthenticated } from "@/lib/http";

export const dynamic = "force-dynamic";

/** GET /api/dashboard/stats — Laravel DashboardController@stats. */
export async function GET(req: NextRequest) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();
  const limited = rateLimit(req, auth.user.id);
  if (limited) return limited;

  const [
    factories,
    departments,
    production_lines,
    articles,
    operators,
    gsd_categories,
    gsd_elements,
    mtm_elements,
    sewing_factors,
    sewing_stop_factors,
    processes,
    process_versions,
    ptms_reports,
    roles,
    users,
    smvAgg,
  ] = await Promise.all([
    table("factories").count(),
    table("departments").count(),
    table("production_lines").count(),
    table("articles").count(),
    table("operators").count(),
    table("gsd_categories").count(),
    table("gsd_elements").count(),
    table("mtm_elements").count(),
    table("sewing_factors").count(),
    table("sewing_stop_factors").count(),
    table("processes").count(),
    table("process_versions").count(),
    table("ptms_reports").count(),
    table("roles").count(),
    table("users").count(),
    table("ptms_reports").aggregate({ _avg: { smv: true } }),
  ]);

  return success(
    {
      factories,
      departments,
      production_lines,
      articles,
      operators,
      gsd_categories,
      gsd_elements,
      mtm_elements,
      sewing_factors,
      sewing_stop_factors,
      processes,
      process_versions,
      ptms_reports,
      roles,
      users,
      average_smv: smvAgg._avg.smv === null ? null : Number(smvAgg._avg.smv),
    },
    "Dashboard statistics retrieved successfully"
  );
}
