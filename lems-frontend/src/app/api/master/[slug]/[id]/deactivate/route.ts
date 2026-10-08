import { NextRequest } from "next/server";
import { fail } from "@/lib/http";
import { MASTERS } from "@/lib/master-config";
import { deactivateMaster } from "@/server/master";

export async function PATCH(req: NextRequest, { params }: { params: { slug: string; id: string } }) {
  if (!MASTERS[params.slug]) return fail("Invalid data master.", 404);
  return deactivateMaster(params.slug, params.id, req);
}
