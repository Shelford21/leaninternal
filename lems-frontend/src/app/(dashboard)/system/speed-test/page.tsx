"use client";

import { useState } from "react";
import { useAuthStore } from "@/store/auth-store";
import { useRouter } from "next/navigation";

/**
 * Speed Test page — faithful replica of
 * resources/views/system/speed-test.blade.php.
 * Developer-only page that tests DB connection + query performance.
 */

interface SpeedResults {
  db_time: number;
  query_time: number;
  total_time: number;
  database: string;
  driver: string;
  node_version: string;
  server_time: string;
  process_count: number;
}

export default function SpeedTestPage() {
  const user = useAuthStore((s) => s.user);
  const token = useAuthStore((s) => s.token);
  const logout = useAuthStore((s) => s.logout);
  const router = useRouter();
  const roleName = user?.role?.role_name ?? "";

  const [loading, setLoading] = useState(false);
  const [results, setResults] = useState<SpeedResults | null>(null);
  const [banner, setBanner] = useState<{ kind: "success" | "error"; text: string } | null>(null);

  const handleRun = async () => {
    setLoading(true);
    setBanner(null);
    setResults(null);
    try {
      const res = await fetch("/api/system/speed-test", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
      });
      if (res.status === 401) {
        logout();
        router.replace("/login");
        return;
      }
      const body = await res.json().catch(() => null);
      if (res.ok && body?.data) {
        setResults(body.data);
        setBanner({ kind: "success", text: body?.message || "Speed test completed." });
      } else {
        setBanner({ kind: "error", text: body?.message || "Speed test failed." });
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
          <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-2">Database Speed Test</h3>
          <p className="text-sm text-slate-500 dark:text-slate-400 mb-6">
            Test database connection and query performance. This runs a simple query and measures response time.
          </p>

          <button
            type="button"
            onClick={handleRun}
            disabled={loading}
            className="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 transition-colors disabled:opacity-50"
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            {loading ? "Running..." : "Run Speed Test"}
          </button>
        </div>

        {/* Results */}
        {results && (
          <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
            <h4 className="text-sm font-semibold text-slate-900 dark:text-slate-100 mb-4">Results</h4>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
              <div className="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4 text-center">
                <div className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{results.db_time}ms</div>
                <div className="text-xs text-slate-500 dark:text-slate-400 mt-1">DB Connection</div>
              </div>
              <div className="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4 text-center">
                <div className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{results.query_time}ms</div>
                <div className="text-xs text-slate-500 dark:text-slate-400 mt-1">Query Time</div>
              </div>
              <div className="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4 text-center">
                <div className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{results.total_time}ms</div>
                <div className="text-xs text-slate-500 dark:text-slate-400 mt-1">Total Time</div>
              </div>
            </div>

            <div className="space-y-2 text-sm">
              <div className="flex justify-between py-2 border-b border-slate-100 dark:border-slate-700">
                <span className="text-slate-500 dark:text-slate-400">Database</span>
                <span className="font-medium text-slate-900 dark:text-slate-100">{results.database}</span>
              </div>
              <div className="flex justify-between py-2 border-b border-slate-100 dark:border-slate-700">
                <span className="text-slate-500 dark:text-slate-400">Driver</span>
                <span className="font-medium text-slate-900 dark:text-slate-100">{results.driver}</span>
              </div>
              <div className="flex justify-between py-2 border-b border-slate-100 dark:border-slate-700">
                <span className="text-slate-500 dark:text-slate-400">Node.js Version</span>
                <span className="font-medium text-slate-900 dark:text-slate-100">{results.node_version}</span>
              </div>
              <div className="flex justify-between py-2 border-b border-slate-100 dark:border-slate-700">
                <span className="text-slate-500 dark:text-slate-400">Server Time</span>
                <span className="font-medium text-slate-900 dark:text-slate-100">{results.server_time}</span>
              </div>
              <div className="flex justify-between py-2">
                <span className="text-slate-500 dark:text-slate-400">Process Count</span>
                <span className="font-medium text-slate-900 dark:text-slate-100">{results.process_count}</span>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
