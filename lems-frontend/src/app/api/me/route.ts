import { NextRequest } from "next/server";
import bcrypt from "bcryptjs";
import { table } from "@/lib/db";
import { entities } from "@/lib/entities";
import {
  authenticate,
  fail,
  rateLimit,
  success,
  unauthenticated,
  validationFail,
} from "@/lib/http";
import { validate } from "@/lib/validation";

export const dynamic = "force-dynamic";

/** GET /api/me — the authenticated user with its role. */
export async function GET(req: NextRequest) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();
  const limited = rateLimit(req, auth.user.id);
  if (limited) return limited;

  const user = await table("users").findUnique({
    where: { id: auth.user.id },
    include: { roles: true },
  });
  return success(entities.users.serialize(user));
}
