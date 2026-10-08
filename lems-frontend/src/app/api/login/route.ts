import bcrypt from "bcryptjs";
import { NextRequest } from "next/server";
import { table } from "@/lib/db";
import { entities } from "@/lib/entities";
import {
  fail,
  generateTokenPlain,
  rateLimit,
  sha256,
  success,
  validationFail,
} from "@/lib/http";
import { validate } from "@/lib/validation";

export const dynamic = "force-dynamic";

/** POST /api/login — Laravel AuthController@login, Sanctum-compatible tokens. */
export async function POST(req: NextRequest) {
  const limited = rateLimit(req);
  if (limited) return limited;

  const body = await req.json().catch(() => ({}));
  const { data, errors } = await validate(body, {
    username: "required|string",
    password: "required|string",
  });
  if (errors) return validationFail(errors);

  const user = await table("users").findUnique({
    where: { username: String(data.username) },
    include: { roles: true },
  });

  // Laravel hashes are bcrypt `$2y$`; normalize the variant prefix for bcryptjs.
  const hash = user?.password ? String(user.password).replace(/^\$2y\$/, "$2b$") : "";
  const ok = user && bcrypt.compareSync(String(data.password), hash);
  if (!ok) return fail("Invalid credentials", 401);

  // Sanctum token format "<id>|<secret>", stored as sha256 of the plain string.
  const secret = generateTokenPlain(0).split("|")[1];
  const token = await table("personal_access_tokens").create({
    data: {
      tokenable_type: "App\\Models\\User",
      tokenable_id: user!.id,
      name: "auth-token",
      token: "pending",
      abilities: '["*"]',
    },
  });
  const plain = `${Number(token.id)}|${secret}`;
  await table("personal_access_tokens").update({
    where: { id: token.id },
    data: { token: sha256(plain) },
  });

  return success({ user: entities.users.serialize(user!), token: plain }, "Login successful");
}
