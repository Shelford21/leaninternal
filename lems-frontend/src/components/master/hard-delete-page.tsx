"use client";

/**
 * Central "Hard Delete" page — replica of
 * resources/views/hard-delete/index.blade.php (developer-only).
 */
import { useCallback, useEffect, useState } from "react";
import { HARD_DELETE_CARDS } from "@/lib/master-config";
import { bannerFor, masterFetch, type ApiResult } from "./master-api";

type Banner = { kind: "success" | "error"; text: string } | null;

export default function HardDeletePage() {
  const [counts, setCounts] = useState<Record<string, number>>({});
  const [banner, setBanner] = useState<Banner>(null);
  const [forbidden, setForbidden] = useState(false);

  const load = useCallback(async () => {
    const r = await masterFetch("/hard-delete");
    if (r.status === 403) {
      setForbidden(true);
      return;
    }
    setForbidden(false);
    if (r.ok && r.data) setCounts(r.data.counts ?? {});
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  if (forbidden) {
    return (
      <div className="py-8 px-4 sm:px-6 lg:px-8">
        <div className="max-w-7xl mx-auto">
          <div className="rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-300">
            You do not have permission to access this page. Please Change your account role to access this page. aowkwk
            ngakak
          </div>
        </div>
      </div>
    );
  }

  const runDelete = async (key: string, label: string, count: number) => {
    if (
      !window.confirm(
        `Are you sure you want to permanently delete ALL ${count} inactive ${label.toLowerCase()} records? This cannot be undone.`
      )
    )
      return;
    const r: ApiResult = await masterFetch("/hard-delete", {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ master_key: key }),
    });
    setBanner(bannerFor(r) ?? { kind: "error", text: r.message || "Internal Server Error." });
    load();
  };

  return (
    <div className="py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-7xl mx-auto space-y-6">
        <h2 className="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">
          Management / Hard Delete
        </h2>

        {/* Warning Banner */}
        <div className="rounded-xl border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-5 py-4">
          <div className="flex items-start gap-3">
            <svg className="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"
              />
            </svg>
            <div>
              <h3 className="text-sm font-semibold text-red-800 dark:text-red-300">Danger Zone — Hard Delete</h3>
              <p className="text-sm text-red-700 dark:text-red-400 mt-1">
                This page permanently removes records from the database. This action <strong>cannot be undone</strong>.
                Only inactive (soft-deleted) records are shown below. This page is accessible only to the Developer role.
              </p>
            </div>
          </div>
        </div>

        {banner && (
          <div
            className={
              banner.kind === "success"
                ? "rounded-xl border border-emerald-200 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300"
                : "rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-300"
            }
          >
            {banner.text}
          </div>
        )}

        {/* Data Masters Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {HARD_DELETE_CARDS.map((master) => {
            const count = counts[master.key] ?? 0;
            return (
              <div key={master.key} className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm overflow-hidden">
                <div className="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                  <div className="flex items-center justify-between">
                    <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-100">{master.label}</h3>
                    <span
                      className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                        count > 0
                          ? "bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 ring-1 ring-inset ring-red-600/20"
                          : "bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400"
                      }`}
                    >
                      {count} inactive
                    </span>
                  </div>
                </div>
                <div className="px-5 py-4">
                  {count > 0 ? (
                    <button
                      type="button"
                      onClick={() => runDelete(master.key, master.label, count)}
                      className="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-800"
                    >
                      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                      </svg>
                      Delete {count} Record(s)
                    </button>
                  ) : (
                    <p className="text-sm text-slate-500 dark:text-slate-400 text-center py-2">No inactive records to delete.</p>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}
