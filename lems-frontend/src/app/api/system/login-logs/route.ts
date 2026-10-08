import { NextRequest } from "next/server";
import { success, fail, unauthenticated, rateLimit } from "@/lib/http";
import { authenticate } from "@/lib/http";
import { db } from "@/lib/db";

export const dynamic = "force-dynamic";

/**
 * GET /api/system/login-logs — paginated login logs (developer only).
 * Replica of the Laravel inline closure at routes/web.php lines 239-242.
 */
export async function GET(req: NextRequest) {
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

  const sp = req.nextUrl.searchParams;
  const page = Math.max(1, parseInt(sp.get("page") ?? "1", 10) || 1);
  const perPage = Math.min(parseInt(sp.get("per_page") ?? "50", 10) || 50, 100);

  const [total, rows] = await Promise.all([
    db.login_logs.count(),
    db.login_logs.findMany({
      orderBy: { created_at: "desc" },
      skip: (page - 1) * perPage,
      take: perPage,
    }),
  ]);

  const data = rows.map((r, i) => ({
    id: Number(r.id),
    no: (page - 1) * perPage + i + 1,
    username: r.username,
    activity: r.activity, // "Login" or "Logout"
    timestamp: r.created_at
      ? new Date(r.created_at).toISOString().replace("T", " ").slice(0, 19)
      : "—",
  }));

  return success({
    data,
    total,
    current_page: page,
    last_page: Math.ceil(total / perPage),
    per_page: perPage,
  });
}

/**
 * DELETE /api/system/login-logs — clear all login logs (developer only).
 * Replica of routes/web.php: DELETE /system/logs/login-logs/clear
 */
export async function DELETE(req: NextRequest) {
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

  const result = await db.login_logs.deleteMany();
  return success(null, `Cleared ${result.count} login log entries.`);
}