"use client";

/**
 * Raw fetch wrapper for the Data Master API surface.
 *
 * Deliberately NOT axios: the axios response interceptor unwraps the
 * { data, message } envelope and discards `message`, while the Laravel
 * blades we replicate rely on the flash message text for banners.
 */
import { useAuthStore } from "@/store/auth-store";

export interface ApiResult {
  status: number;
  ok: boolean;
  message: string;
  errors: Record<string, string[]> | null;
  data: any;
  blob?: Blob;
  fileName?: string | null;
}

export async function masterFetch(path: string, init: RequestInit = {}): Promise<ApiResult> {
  const token = useAuthStore.getState().token;
  const headers = new Headers(init.headers);
  if (token) headers.set("Authorization", `Bearer ${token}`);
  let res: Response;
  try {
    res = await fetch(`/api/master${path}`, { ...init, headers });
  } catch {
    return { status: 0, ok: false, message: "Network error.", errors: null, data: null };
  }
  if (res.status === 401) {
    useAuthStore.getState().logout();
    window.location.href = "/login";
    return { status: 401, ok: false, message: "Unauthenticated.", errors: null, data: null };
  }
  const ctype = res.headers.get("content-type") ?? "";
  if (ctype.includes("application/json")) {
    const body = await res.json().catch(() => null);
    return {
      status: res.status,
      ok: res.ok,
      message: body?.message ?? "",
      errors: body?.errors ?? null,
      data: body?.data ?? null,
    };
  }
  const blob = await res.blob();
  const disp = res.headers.get("content-disposition") ?? "";
  const m = disp.match(/filename="?([^";]+)"?/i);
  return {
    status: res.status,
    ok: res.ok,
    message: "",
    errors: null,
    data: null,
    blob,
    fileName: m ? m[1] : null,
  };
}

/**
 * Laravel parity for flash banners:
 * - 2xx                -> green banner with `message`
 * - 422 WITH `errors`  -> SILENT (the blades never render $errors; the modal
 *                          just closes and nothing is shown)
 * - anything else      -> red banner with `message`
 */
export function bannerFor(r: ApiResult): { kind: "success" | "error"; text: string } | null {
  if (r.ok) return r.message ? { kind: "success", text: r.message } : null;
  if (r.status === 422 && r.errors) return null;
  return r.message ? { kind: "error", text: r.message } : null;
}

export function downloadBlob(blob: Blob, fileName: string | null, fallback: string) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = fileName || fallback;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
}
