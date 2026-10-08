import { NextRequest } from "next/server";
import { handleAll, handleDestroy, handleShow, handleUpdate } from "@/lib/crud";
import { entities } from "@/lib/entities";
import { fail } from "@/lib/http";

export const dynamic = "force-dynamic";

/* GET/PUT/DELETE /api/{resource}/{id} — generic Laravel-style controller. */

const rolesFor = (resource: string) => (resource === "users" ? ["developer", "admin"] : undefined);

export async function GET(
  req: NextRequest,
  { params }: { params: { resource: string; id: string } }
) {
  const cfg = entities[params.resource];
  if (!cfg) return fail("", 404);
  if (params.id === "all") return handleAll(req, cfg, rolesFor(params.resource));
  return handleShow(req, cfg, params.id, rolesFor(params.resource));
}

export async function PUT(
  req: NextRequest,
  { params }: { params: { resource: string; id: string } }
) {
  const cfg = entities[params.resource];
  if (!cfg) return fail("", 404);
  return handleUpdate(req, cfg, params.id, rolesFor(params.resource));
}

export async function DELETE(
  req: NextRequest,
  { params }: { params: { resource: string; id: string } }
) {
  const cfg = entities[params.resource];
  if (!cfg) return fail("", 404);
  return handleDestroy(req, cfg, params.id, rolesFor(params.resource));
}
