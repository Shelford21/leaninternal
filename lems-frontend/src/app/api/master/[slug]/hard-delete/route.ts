import { NextRequest } from "next/server";
import { fail } from "@/lib/http";
import { MASTERS } from "@/lib/master-config";
import { hardDeleteMaster } from "@/server/master";

export async function DELETE(req: NextRequest, { params }: { params: { slug: string } }) {
  if (!MASTERS[params.slug]) return fail("Invalid data master.", 404);
  return hardDeleteMaster(params.slug, req);
}
