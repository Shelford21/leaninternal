import { NextRequest } from "next/server";
import { table } from "@/lib/db";
import { entities } from "@/lib/entities";
import {
  authenticate,
  notFoundResult,
  rateLimit,
  success,
  unauthenticated,
  validationFail,
} from "@/lib/http";
import { validate } from "@/lib/validation";

export const dynamic = "force-dynamic";

/** PUT /api/ptms-reports/{id}/status — status-only update for any authed user. */
export async function PUT(req: NextRequest, { params }: { params: { id: string } }) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();
  const limited = rateLimit(req, auth.user.id);
  if (limited) return limited;

  const cfg = entities["ptms-reports"];
  if (!/^\d+$/.test(params.id)) return notFoundResult(cfg.modelClass, params.id);
  const existing = await table(cfg.model).findUnique({ where: { id: Number(params.id) } });
  if (!existing) return notFoundResult(cfg.modelClass, params.id);

  const body = await req.json().catch(() => ({}));
  const { data, errors } = await validate(body, { status: "required|in:draft,final,archived" });
  if (errors) return validationFail(errors);

  const row = await table(cfg.model).update({
    where: { id: Number(params.id) },
    data: { status: data.status },
  });
  return success(cfg.serialize(row), "PTMS report status updated successfully");
}
