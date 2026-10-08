import { NextRequest } from "next/server";
import { handleIndex, handleStore } from "@/lib/crud";
import { entities } from "@/lib/entities";
import { fail } from "@/lib/http";

export const dynamic = "force-dynamic";

/* GET/POST /api/{resource} — generic Laravel-style resource controller. */

const rolesFor = (resource: string) => (resource === "users" ? ["developer", "admin"] : undefined);

export async function GET(req: NextRequest, { params }: { params: { resource: string } }) {
  const cfg = entities[params.resource];
  if (!cfg) return fail("", 404);
  return handleIndex(req, cfg, rolesFor(params.resource));
}

export async function POST(req: NextRequest, { params }: { params: { resource: string } }) {
  const cfg = entities[params.resource];
  if (!cfg) return fail("", 404);
  return handleStore(req, cfg, rolesFor(params.resource));
}
