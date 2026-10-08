import { NextRequest, NextResponse } from "next/server";
import fs from "fs";
import path from "path";

/**
 * GET /api/uploads/[...path] — serve uploaded files from public/uploads/
 * dynamically, bypassing Next.js production-mode static file caching.
 */
export async function GET(
  _req: NextRequest,
  { params }: { params: { path: string[] } }
) {
  const parts = params.path;
  if (!parts || parts.length === 0) return new NextResponse("Not found", { status: 404 });

  // Prevent directory traversal
  const joined = parts.join("/");
  if (joined.includes("..") || joined.includes("\\")) {
    return new NextResponse("Forbidden", { status: 403 });
  }

  const filePath = path.join(process.cwd(), "public", "uploads", joined);
  if (!fs.existsSync(filePath)) return new NextResponse("Not found", { status: 404 });

  const stat = fs.statSync(filePath);
  if (!stat.isFile()) return new NextResponse("Not found", { status: 404 });

  const ext = path.extname(filePath).toLowerCase();
  const mimeMap: Record<string, string> = {
    ".jpg": "image/jpeg",
    ".jpeg": "image/jpeg",
    ".png": "image/png",
    ".gif": "image/gif",
    ".webp": "image/webp",
  };
  const contentType = mimeMap[ext] ?? "application/octet-stream";

  const buffer = fs.readFileSync(filePath);
  return new NextResponse(buffer, {
    status: 200,
    headers: {
      "Content-Type": contentType,
      "Content-Length": String(stat.size),
      "Cache-Control": "public, max-age=3600",
    },
  });
}