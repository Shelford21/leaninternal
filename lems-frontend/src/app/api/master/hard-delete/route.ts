import { NextRequest } from "next/server";
import { hardDeleteCounts, hardDeleteRun } from "@/server/master";

/** Central "Delete Inactive" page: per-master inactive counts + bulk purge. */
export async function GET(req: NextRequest) {
  return hardDeleteCounts(req);
}

export async function DELETE(req: NextRequest) {
  return hardDeleteRun(req);
}
