import bcrypt from "bcryptjs";
import { NextRequest } from "next/server";
import { table } from "@/lib/db";
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

/** PUT /api/me/password — Laravel AuthController@changePassword. */
export async function PUT(req: NextRequest) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();
  const limited = rateLimit(req, auth.user.id);
  if (limited) return limited;

  const body = await req.json().catch(() => ({}));
  const { data, errors } = await validate(body, {
    current_password: "required|string",
    password: "required|string|min:6|confirmed",
  });
  if (errors) return validationFail(errors);

  const user = await table("users").findUnique({ where: { id: auth.user.id } });
  const hash = user?.password ? String(user.password).replace(/^\$2y\$/, "$2b$") : "";
  const ok = user && bcrypt.compareSync(String(data.current_password), hash);
  if (!ok) return fail("Current password is incorrect", 422);

  await table("users").update({
    where: { id: auth.user.id },
    data: { password: bcrypt.hashSync(String(data.password), 10) },
  });
  return success(null, "Password changed successfully");
}
