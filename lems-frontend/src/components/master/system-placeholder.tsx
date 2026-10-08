"use client";

/**
 * Placeholder for developer-only system tools (Clear Cache, Speed Test,
 * Login Logs, Activity Logs) — these are outside the Data Master replica
 * scope (audit §14), only the sidebar entry + page shell are replicated.
 */
import { useAuthStore } from "@/store/auth-store";

export default function SystemPlaceholder({ title }: { title: string }) {
  const user = useAuthStore((s) => s.user);

  if (user?.role?.role_name !== "developer") {
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

  return (
    <div className="py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-7xl mx-auto space-y-6">
        <h2 className="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">{title}</h2>
        <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-5 py-4 text-sm text-slate-500 dark:text-slate-400 shadow-sm">
          This page is outside the scope of the Data Master replica.
        </div>
      </div>
    </div>
  );
}
