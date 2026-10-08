"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useAuthStore } from "@/store/auth-store";

/**
 * /employees-profile — redirects to the first employee's detail page
 * from the employees data master list (sorted by operator_name asc).
 * Falls back to /master-data/operators if no employees exist.
 */
export default function EmployeesProfileRedirect() {
  const router = useRouter();
  const { token } = useAuthStore();

  useEffect(() => {
    const controller = new AbortController();

    async function lookup() {
      try {
        const res = await fetch("/api/master/operators?sort=operator_name&direction=asc&per_page=1", {
          headers: { Authorization: `Bearer ${token}` },
          signal: controller.signal,
        });
        if (!res.ok) throw new Error("fetch failed");
        const body = await res.json();
        const firstRow = body?.data?.rows?.[0];
        if (firstRow?.id) {
          router.replace(`/operators/${firstRow.id}`);
        } else {
          router.replace("/master-data/operators");
        }
      } catch {
        router.replace("/master-data/operators");
      }
    }

    lookup();
    return () => controller.abort();
  }, [token, router]);

  return (
    <div className="flex h-64 items-center justify-center">
      <div className="text-sm text-slate-500 animate-pulse">Loading profile…</div>
    </div>
  );
}