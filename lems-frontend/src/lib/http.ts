import crypto from "crypto";
import { NextRequest, NextResponse } from "next/server";
import { table } from "./db";

/* ---------------------------------------------------------------------------
 * Laravel-compatible JSON envelopes (preserved from the Laravel API):
 *   success: { data, message }            (200/201/etc.)
 *   errors:  { message } or { message, errors } (401/403/404/422/429)
 *   index:   { data, links, meta }        (Laravel paginator resource)
 * ------------------------------------------------------------------------ */

export function success(data: unknown, message = "Success", status = 200) {
  return NextResponse.json({ data, message }, { status });
}

export function fail(message: string, status: number, errors?: Record<string, string[]>) {
  return NextResponse.json(errors ? { message, errors } : { message }, { status });
}

export const unauthenticated = () => fail("Unauthenticated.", 401);

export function notFoundResult(modelClass: string, id: string | number) {
  return fail(`No query results for model [App\\Models\\${modelClass}] ${id}`, 404);
}

export function validationFail(errors: Record<string, string[]>) {
  const messages = Object.values(errors).flat();
  const summary =
    messages.length > 1 ? `${messages[0]} (and ${messages.length - 1} more errors)` : messages[0];
  return fail(summary, 422, errors);
}

/* ------------------------- serialization helpers ------------------------- */

/** BigInt ids -> JSON-safe number. */
export const num = (v: unknown) => (v === null || v === undefined ? null : Number(v));

/** Laravel `decimal:2` casts serialize as fixed 2-decimals strings ("12.50"). */
export const dec2 = (v: unknown) =>
  v === null || v === undefined ? null : Number(v).toFixed(2);

/** Laravel Carbon datetime JSON: "2026-10-07T12:34:56.000000Z". */
export const iso = (v: unknown) => {
  if (!v) return null;
  return (v as Date).toISOString().replace(/\.\d+Z$/, ".000000Z");
};

/* ------------------------------ rate limit ------------------------------- */
/* Laravel `throttle:api`: 60 requests / minute per user id or IP.           */

const buckets = new Map<string, { count: number; resetAt: number }>();

export function rateLimit(req: NextRequest, userId?: number): NextResponse | null {
  const key = userId ? `u:${userId}` : `ip:${req.ip ?? "0.0.0.0"}`;
  const now = Date.now();
  const b = buckets.get(key);
  if (!b || now >= b.resetAt) {
    buckets.set(key, { count: 1, resetAt: now + 60_000 });
    return null;
  }
  b.count += 1;
  if (b.count > 60) return fail("Too Many Attempts.", 429);
  return null;
}

/* --------------------------------- auth ---------------------------------- */
/*
 * Tokens are Sanctum-compatible rows in the existing `personal_access_tokens`
 * table: plain token = "<id>|<secret>", stored value = sha256(plain). Tokens
 * issued here can be read by Laravel and vice versa.
 */

export interface AuthedUser {
  id: number;
  role_id: number;
  name: string;
  username: string | null;
  employee_number: string | null;
  description: string | null;
  role: { id: number; role_name: string; description: string | null } | null;
}

export function sha256(value: string) {
  return crypto.createHash("sha256").update(value).digest("hex");
}

export function generateTokenPlain(id: number) {
  return `${id}|${crypto.randomBytes(20).toString("hex")}`; // 40-char secret like Sanctum
}

export async function authenticate(
  req: NextRequest
): Promise<{ user: AuthedUser; tokenId: bigint } | null> {
  const header = req.headers.get("authorization") ?? "";
  const raw = header.startsWith("Bearer ") ? header.slice(7).trim() : "";
  if (!raw.includes("|")) return null;

  const idPart = raw.split("|")[0];
  if (!/^\d+$/.test(idPart)) return null;

  const hashed = sha256(raw);
  const token = await table("personal_access_tokens").findUnique({ where: { id: Number(idPart) } });
  if (!token || token.token !== hashed) return null;

  const user = await table("users").findUnique({
    where: { id: Number(token.tokenable_id) },
    include: { roles: true },
  });
  if (!user) return null;

  // Sanctum updates last_used_at on access.
  await table("personal_access_tokens").update({
    where: { id: token.id },
    data: { last_used_at: new Date() },
  });

  return {
    user: {
      id: num(user.id) as number,
      role_id: num(user.role_id) as number,
      name: user.name,
      username: user.username,
      employee_number: user.employee_number,
      description: user.description,
      role: user.roles
        ? {
            id: num(user.roles.id) as number,
            role_name: user.roles.role_name,
            description: user.roles.description,
          }
        : null,
    },
    tokenId: token.id,
  };
}

/** Laravel RoleMiddleware: DB role name must be in the allowed list. */
export function requireRole(user: AuthedUser, roles: string[]): NextResponse | null {
  if (!user.role || !roles.includes(user.role.role_name)) {
    return fail(
      "You do not have permission to access this page. Please Change your account role to access this page. aowkwk ngakak",
      403
    );
  }
  return null;
}

/* ------------------------------- pagination ------------------------------ */

export function paginated(
  req: NextRequest,
  rows: unknown[],
  meta: { page: number; perPage: number; total: number }
) {
  const lastPage = Math.max(1, Math.ceil(meta.total / meta.perPage));
  const path = `${req.nextUrl.origin}${req.nextUrl.pathname}`;
  const urlFor = (p: number) => `${path}?page=${p}`;

  const from = meta.total === 0 ? 0 : (meta.page - 1) * meta.perPage + 1;
  const to = Math.min(meta.page * meta.perPage, meta.total);

  const links: { url: string | null; label: string; active: boolean }[] = [
    {
      url: meta.page > 1 ? urlFor(meta.page - 1) : null,
      label: "&laquo; Previous",
      active: false,
    },
  ];
  for (let i = 1; i <= lastPage; i++) {
    links.push({ url: urlFor(i), label: String(i), active: i === meta.page });
  }
  links.push({
    url: meta.page < lastPage ? urlFor(meta.page + 1) : null,
    label: "Next &raquo;",
    active: false,
  });

  return NextResponse.json({
    data: rows,
    links: {
      first: urlFor(1),
      last: urlFor(lastPage),
      prev: meta.page > 1 ? urlFor(meta.page - 1) : null,
      next: meta.page < lastPage ? urlFor(meta.page + 1) : null,
    },
    meta: {
      current_page: meta.page,
      from,
      last_page: lastPage,
      links,
      path,
      per_page: meta.perPage,
      to,
      total: meta.total,
    },
  });
}
