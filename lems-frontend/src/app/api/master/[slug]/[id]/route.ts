import { NextRequest } from "next/server";
import { fail } from "@/lib/http";
import { MASTERS } from "@/lib/master-config";
import { updateMaster } from "@/server/master";

export async function PUT(req: NextRequest, { params }: { params: { slug: string; id: string } }) {
  if (!MASTERS[params.slug]) return fail("Invalid data master.", 404);
  return updateMaster(params.slug, params.id, req);
}
