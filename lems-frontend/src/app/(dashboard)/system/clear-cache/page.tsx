"use client";

import { useState } from "react";
import { useAuthStore } from "@/store/auth-store";
import { useRouter } from "next/navigation";

/**
 * Clear Cache page — faithful replica of
 * resources/views/system/clear-cache.blade.php.
 * Developer-only page with 6 cache clear checkboxes (cosmetic) + results.
 */

interface CacheResult {
  command: string;
  success: boolean;
  message: string;
}

const CACHE_OPTIONS = [
  { value: "config", label: "Config", description: "config:clear", checked: true },
  { value: "route", label: "Routes", description: "route:clear", checked: true },
  { value: "view", label: "Views", description: "view:clear", checked: true },
  { value: "cache", label: "Application Cache", description: "cache:clear", checked: false },
  { value: "compiled", label: "Compiled Classes", description: "clear-compiled", checked: false },
  { value: "event", label: "Events", description: "event:clear", checked: false },
];

export default function ClearCachePage() {
  const user = useAuthStore((s) => s.user);
  const token = useAuthStore((s) => s.token);
  const logout = useAuthStore((s) => s.logout);
  const router = useRouter();
  const roleName = user?.role?.role_name ?? "";

  const [selected, setSelected] = useState<Record<string, boolean>>(() => {
    const init: Record<string, boolean> = {};
    CACHE_OPTIONS.forEach((o) => (init[o.value] = o.checked));
    return init;
  });
  const [loading, setLoading] = useState(false);
  const [results, setResults] = useState<CacheResult[] | null>(null);
  const [banner, setBanner] = useState<{ kind: "success" | "error"; text: string } | null>(null);

  const toggle = (val: string) => setSelected((prev) => ({ ...prev, [val]: !prev[val] }));

  const handleSubmit = async () => {
    setLoading(true);
    setBanner(null);
    setResults(null);
    try {
      const res = await fetch("/api/system/clear-cache", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        body: JSON.stringify({ caches: Object.keys(selected).filter((k) => selected[k]) }),
      });
      if (res.status === 401) {
        logout();
        router.replace("/login");
        return;
      }
      const body = await res.json().catch(() => null);
      if (res.ok) {
        setBanner({ kind: "success", text: body?.message || "Caches cleared." });
        setResults(body?.data?.results ?? []);
      } else {
        setBanner({ kind: "error", text: body?.message || "Failed to clear caches." });
        setResults(body?.data?.results ?? []);
      }
    } catch {
      setBanner({ kind: "error", text: "Network error." });
    }
    setLoading(false);
  };

  if (roleName !== "developer") {
    return (
      <div className="py-8 px-4 sm:px-6 lg:px-8">
        <div className="max-w-3xl mx-auto">
          <div className="rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-300">
            You do not have permission to access this page. Please Change your account role to access this page. aowkwk ngakak
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-3xl mx-auto space-y-6">
        {banner && (
          <div
            className={`rounded-xl border px-4 py-3 text-sm ${
              banner.kind === "success"
                ? "border-emerald-200 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300"
                : "border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300"
            }`}
          >
            {banner.text}
          </div>
        )}

        <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
          <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-2">Cache Management</h3>
          <p className="text-sm text-slate-500 dark:text-slate-400 mb-6">
            Clear various Laravel caches. This is useful after deploying code changes or modifying configuration files.
          </p>

          <div className="space-y-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              {CACHE_OPTIONS.map((opt) => (
                <label
                  key={opt.value}
                  className="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-600 p-4 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                >
                  <input
                    type="checkbox"
                    checked={selected[opt.value] ?? false}
                    onChange={() => toggle(opt.value)}
                    className="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"
                  />
                  <div>
                    <div className="text-sm font-medium text-slate-900 dark:text-slate-100">{opt.label}</div>
                    <div className="text-xs text-slate-500 dark:text-slate-400">{opt.description}</div>
                  </div>
                </label>
              ))}
            </div>

            <div className="pt-4">
              <button
                type="button"
                onClick={handleSubmit}
                disabled={loading}
                className="inline-flex items-center gap-2 rounded-xl bg-red-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-red-500 transition-colors disabled:opacity-50"
              >
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                {loading ? "Clearing..." : "Clear Selected Caches"}
              </button>
            </div>
          </div>
        </div>

        {/* Results */}
        {results && results.length > 0 && (
          <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
            <h4 className="text-sm font-semibold text-slate-900 dark:text-slate-100 mb-4">Results</h4>
            <div className="space-y-2">
              {results.map((r) => (
                <div
                  key={r.command}
                  className={`rounded-lg px-4 py-2 text-sm ${
                    r.success
                      ? "bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300"
                      : "bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300"
                  }`}
                >
                  {r.message}
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
