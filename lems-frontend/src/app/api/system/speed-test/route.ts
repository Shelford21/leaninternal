import { NextRequest } from "next/server";
import { success, fail, unauthenticated, rateLimit, authenticate } from "@/lib/http";
import { db } from "@/lib/db";

export const dynamic = "force-dynamic";

/**
 * POST /api/system/speed-test — test DB connection and query performance.
 * Developer only. Replica of routes/web.php lines 293-326.
 * Laravel measures PDO connection time + SELECT COUNT(*) FROM processes.
 */
export async function POST(req: NextRequest) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();
  const limited = rateLimit(req, auth.user.id);
  if (limited) return limited;
  if (auth.user.role?.role_name !== "developer") {
    return fail(
      "You do not have permission to access this page. Please Change your account role to access this page. aowkwk ngakak",
      403
    );
  }

  try {
    // Measure connection time (simple query)
    const connStart = performance.now();
    await db.$queryRaw`SELECT 1`;
    const connTime = Math.round(performance.now() - connStart);

    // Measure query time (count processes — same as Laravel)
    const queryStart = performance.now();
    const result = await db.$queryRaw<[{ cnt: bigint }]>`SELECT COUNT(*) AS cnt FROM processes`;
    const queryTime = Math.round(performance.now() - queryStart);

    const totalTime = connTime + queryTime;

    return success({
      db_time: connTime,
      query_time: queryTime,
      total_time: totalTime,
      database: "lean_ie",
      driver: "mysql",
      node_version: process.version,
      server_time: new Date().toISOString().replace("T", " ").slice(0, 19),
      process_count: Number(result[0]?.cnt ?? 0),
    });
  } catch (e: any) {
    return fail(`Speed test failed: ${e?.message ?? "Unknown error"}`, 500);
  }
}