import { NextRequest } from "next/server";
import { table } from "@/lib/db";
import { authenticate, rateLimit, success, unauthenticated } from "@/lib/http";

export const dynamic = "force-dynamic";

/** POST /api/logout — revokes the current access token (Sanctum behavior). */
export async function POST(req: NextRequest) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();
  const limited = rateLimit(req, auth.user.id);
  if (limited) return limited;

  await table("personal_access_tokens").delete({ where: { id: auth.tokenId } });
  return success(null, "Logged out successfully");
}
