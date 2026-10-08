import { NextRequest } from "next/server";
import { success, fail, unauthenticated, rateLimit, authenticate } from "@/lib/http";
import { execFile } from "child_process";
import path from "path";

export const dynamic = "force-dynamic";

const PHP_PATH = "D:\\xampp\\php\\php.exe";
const LARAVEL_ROOT = path.resolve(process.cwd(), "..");

/**
 * POST /api/system/clear-cache — run Laravel artisan cache clear commands.
 * Developer only. Replica of routes/web.php lines 263-290.
 * NOTE: Laravel POST handler runs ALL commands unconditionally regardless
 * of checkbox values (cosmetic checkboxes). Preserved here.
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

  const commands = ["cache:clear", "config:clear", "route:clear", "view:clear"];
  const results: { command: string; success: boolean; message: string }[] = [];

  for (const cmd of commands) {
    try {
      const output = execFile(PHP_PATH, ["artisan", cmd], {
        cwd: LARAVEL_ROOT,
        timeout: 15000,
      });
      // execFile returns a ChildProcess; we need to await it.
      const result = await new Promise<{ ok: boolean; out: string }>((resolve) => {
        let stdout = "";
        let stderr = "";
        output.stdout?.on("data", (d: Buffer) => (stdout += d.toString()));
        output.stderr?.on("data", (d: Buffer) => (stderr += d.toString()));
        output.on("close", (code: number | null) => {
          resolve({ ok: code === 0, out: (stdout + stderr).trim() });
        });
      });
      results.push({
        command: cmd,
        success: result.ok,
        message: result.ok ? `✓ ${cmd} cleared successfully.` : `✕ ${cmd} failed: ${result.out}`,
      });
    } catch (e: any) {
      results.push({
        command: cmd,
        success: false,
        message: `✕ ${cmd} failed: ${e?.message ?? "Unknown error"}`,
      });
    }
  }

  const allOk = results.every((r) => r.success);
  return success(
    { results },
    allOk ? "All caches cleared successfully." : "Some caches failed to clear.",
  );
}