import { NextRequest } from "next/server";
import { authenticate, success, unauthenticated } from "@/lib/http";
import { db } from "@/lib/db";

/**
 * GET /api/line-balancing/lines-by-factory/:factoryId
 * Returns active production lines.
 * Replica of Laravel LineBalancingController getLinesByFactory.
 */
export async function GET(
  req: NextRequest,
  { params }: { params: { factoryId: string } }
) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();

  // Laravel ignores factoryId (noted as TODO), returns all active lines
  const lines = await db.production_lines.findMany({
    where: { status: "active" },
    orderBy: { line_name: "asc" },
  });

  return success(
    lines.map((l) => ({ id: Number(l.id), line_name: l.line_name }))
  );
}