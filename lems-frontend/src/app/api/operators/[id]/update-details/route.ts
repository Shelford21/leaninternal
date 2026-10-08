import { NextRequest } from "next/server";
import { authenticate, unauthenticated, fail, success } from "@/lib/http";
import { db } from "@/lib/db";

/**
 * PUT /api/operators/:id/update-details
 * Laravel parity: operators/{operator}/update-details
 * Updates only start_date and date_of_birth from the profile page.
 * Viewer role is blocked (403).
 */
export async function PUT(
  req: NextRequest,
  { params }: { params: { id: string } }
) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();
  const { user } = auth;

  // Viewer gate — matches Laravel: if (auth()->user()->role->role_name === 'viewer') abort(403)
  if (user.role?.role_name === "viewer") {
    return fail("Unauthorized. Viewer role is read-only.", 403);
  }

  const id = Number(params.id);
  if (!id) return fail("Invalid operator ID.", 400);

  const operator = await db.operators.findUnique({ where: { id } });
  if (!operator) return fail("Operator not found.", 404);

  const body = await req.json().catch(() => ({}));
  const dateVal = (v: any) => {
    if (!v || v === "") return null;
    const d = new Date(v);
    return isNaN(d.getTime()) ? null : d;
  };

  const patch: Record<string, any> = {
    updated_at: new Date(),
  };

  // Only update if explicitly provided
  if ("start_date" in body) patch.start_date = dateVal(body.start_date);
  if ("date_of_birth" in body) patch.date_of_birth = dateVal(body.date_of_birth);

  await db.operators.update({ where: { id }, data: patch });

  return success(null, "Employee details updated successfully.");
}