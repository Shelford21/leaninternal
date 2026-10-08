"use client";

import { useCallback, useEffect, useState } from "react";
import { useAuthStore } from "@/store/auth-store";
import { useRouter } from "next/navigation";

interface LoginLogRow {
  id: number;
  no: number;
  username: string;
  activity: string; // "Login" or "Logout"
  timestamp: string;
}

interface LogsResponse {
  data: LoginLogRow[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

export default function LoginLogsPage() {
  const user = useAuthStore((s) => s.user);
  const token = useAuthStore((s) => s.token);
  const logout = useAuthStore((s) => s.logout);
  const router = useRouter();
  const roleName = user?.role?.role_name ?? "";

  const [logs, setLogs] = useState<LogsResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [banner, setBanner] = useState<{ kind: "success" | "error"; text: string } | null>(null);

  const fetchLogs = useCallback(
    async (p: number) => {
      setLoading(true);
      try {
        const res = await fetch(`/api/system/login-logs?page=${p}`, {
          headers: token ? { Authorization: `Bearer ${token}` } : {},
        });
        if (res.status === 401) {
          logout();
          router.replace("/login");
          return;
        }
        if (res.status === 403) {
          setLogs(null);
          setLoading(false);
          return;
        }
        const body = await res.json().catch(() => null);
        if (res.ok && body?.data) setLogs(body.data);
      } catch {
        /* ignore */
      }
      setLoading(false);
    },
    [token, logout, router],
  );

  useEffect(() => {
    fetchLogs(page);
  }, [page, fetchLogs]);

  const handleClear = async () => {
    if (!confirm("Are you sure you want to clear ALL login logs? This action cannot be undone.")) return;
    try {
      const res = await fetch("/api/system/login-logs", {
        method: "DELETE",
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      });
      if (res.status === 401) {
        logout();
        router.replace("/login");
        return;
      }
      const body = await res.json().catch(() => null);
      if (res.ok) {
        setBanner({ kind: "success", text: body?.message || "Login logs cleared." });
        setPage(1);
        fetchLogs(1);
      } else {
        setBanner({ kind: "error", text: body?.message || "Failed to clear logs." });
      }
    } catch {
      setBanner({ kind: "error", text: "Network error." });
    }
  };

  if (roleName !== "developer") {
    return (
      <div className="py-8 px-4 sm:px-6 lg:px-8">
        <div className="max-w-7xl mx-auto">
          <div className="rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-300">
            You do not have permission to access this page. Please Change your account role to access this page. aowkwk ngakak
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-7xl mx-auto space-y-6">
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

        <div className="flex items-center justify-between">
          <p className="text-sm text-slate-500 dark:text-slate-400">
            Showing {logs?.total ?? 0} login log entries.
          </p>
          <button
            type="button"
            onClick={handleClear}
            className="inline-flex items-center gap-2 rounded-xl border border-red-200 dark:border-red-700 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Clear All Logs
          </button>
        </div>

        <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
          <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-left text-sm text-slate-700 dark:text-slate-300">
            <thead className="bg-slate-50 dark:bg-slate-700/50">
              <tr>
                <th className="px-4 py-3 font-semibold">No</th>
                <th className="px-4 py-3 font-semibold">Username</th>
                <th className="px-4 py-3 font-semibold">Activity</th>
                <th className="px-4 py-3 font-semibold">Timestamp</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 dark:divide-slate-700">
              {loading ? (
                <tr>
                  <td colSpan={4} className="px-4 py-8 text-center text-slate-500 dark:text-slate-400">Loading...</td>
                </tr>
              ) : !logs?.data?.length ? (
                <tr>
                  <td colSpan={4} className="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No login logs found.</td>
                </tr>
              ) : (
                logs.data.map((row) => (
                  <tr key={row.id}>
                    <td className="px-4 py-3">{row.no}</td>
                    <td className="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{row.username}</td>
                    <td className="px-4 py-3">
                      {row.activity === "Login" ? (
                        <span className="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                          Login
                        </span>
                      ) : (
                        <span className="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">
                          Logout
                        </span>
                      )}
                    </td>
                    <td className="px-4 py-3 text-slate-500 dark:text-slate-400">{row.timestamp}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {logs && logs.last_page > 1 && (
          <div className="flex items-center justify-center gap-2">
            <button
              type="button"
              disabled={page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              className="rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50"
            >
              Previous
            </button>
            <span className="text-sm text-slate-500 dark:text-slate-400">
              Page {logs.current_page} of {logs.last_page}
            </span>
            <button
              type="button"
              disabled={page >= logs.last_page}
              onClick={() => setPage((p) => p + 1)}
              className="rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50"
            >
              Next
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
