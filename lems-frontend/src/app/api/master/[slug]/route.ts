import { NextRequest } from "next/server";
import { fail } from "@/lib/http";
import { MASTERS } from "@/lib/master-config";
import { createMaster, listMaster } from "@/server/master";

export async function GET(req: NextRequest, { params }: { params: { slug: string } }) {
  if (!MASTERS[params.slug]) return fail("Invalid data master.", 404);
  return listMaster(params.slug, req);
}

export async function POST(req: NextRequest, { params }: { params: { slug: string } }) {
  if (!MASTERS[params.slug]) return fail("Invalid data master.", 404);
  return createMaster(params.slug, req);
}
