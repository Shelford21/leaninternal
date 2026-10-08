"use client";

import { useState, useEffect, useCallback } from "react";
import { useParams, useRouter } from "next/navigation";
import { useAuthStore } from "@/store/auth-store";
import Link from "next/link";

/**
 * Employee Profile details page — faithful replica of
 * resources/views/operators/show.blade.php.
 * Shows operator profile card, personal & employment details,
 * organization info, and history table.
 */

interface ProfileData {
  id: number;
  employee_number: string | null;
  nik_karyawan: string | null;
  operator_name: string | null;
  gender: string | null;
  role: string | null;
  photo_path: string | null;
  status: string | null;
  start_date: string | null;
  date_of_birth: string | null;
  working_age: string | null;
  age: string | null;
  age_year: number | null;
  years_of_service: string | null;
  status_pkwtt: string | null;
  educational_level: string | null;
  factory_name: string | null;
  department_name: string | null;
  division_name: string | null;
  section_name: string | null;
  line_name: string | null;
  history: {
    id: number;
    article_name: string | null;
    article_destination: string | null;
    article_label_number: string | null;
    article_label_number_quty: string | null;
    article_description: string | null;
    process_name: string | null;
    process_gsd_element: string | null;
    version_number: number | null;
  }[];
  created_at: string | null;
  updated_at: string | null;
}

export default function EmployeeProfilePage() {
  const params = useParams();
  const router = useRouter();
  const token = useAuthStore((s) => s.token);
  const logout = useAuthStore((s) => s.logout);

  const id = params?.id as string;
  const [data, setData] = useState<ProfileData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Editable date fields
  const [editStartDate, setEditStartDate] = useState("");
  const [editDob, setEditDob] = useState("");
  const [savingDates, setSavingDates] = useState(false);
  const [saveMsg, setSaveMsg] = useState<{ ok: boolean; text: string } | null>(null);

  // Employee search
  const [searchQuery, setSearchQuery] = useState("");
  const [searchResults, setSearchResults] = useState<{ id: number; label: string }[]>([]);
  const [searchOpen, setSearchOpen] = useState(false);

  const fetchProfile = useCallback(async (operatorId: string) => {
    setLoading(true);
    setError(null);
    try {
      const res = await fetch(`/api/operators/${operatorId}/profile`, {
        headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
      });
      if (res.status === 401) { logout(); router.replace("/login"); return; }
      const body = await res.json().catch(() => null);
      if (res.ok && body?.data) {
        setData(body.data);
        // Initialize editable date fields
        setEditStartDate(body.data.start_date ?? "");
        setEditDob(body.data.date_of_birth ?? "");
        setSaveMsg(null);
      } else {
        setError(body?.message || "Operator not found.");
      }
    } catch {
      setError("Network error.");
    }
    setLoading(false);
  }, [token, logout, router]);

  useEffect(() => {
    if (id) fetchProfile(id);
  }, [id, fetchProfile]);

  // Employee search autocomplete
  const handleSearch = async (q: string) => {
    setSearchQuery(q);
    if (q.trim().length < 1) { setSearchResults([]); setSearchOpen(false); return; }
    try {
      const res = await fetch(`/api/master/operators/search?q=${encodeURIComponent(q)}`, {
        headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
      });
      if (res.status === 401) { logout(); router.replace("/login"); return; }
      const body = await res.json().catch(() => null);
      const results = res.ok && Array.isArray(body?.data) ? body.data : [];
      setSearchResults(results);
      setSearchOpen(results.length > 0);
    } catch {
      setSearchResults([]);
    }
  };

  // Initials for avatar fallback
  const initials = data?.operator_name
    ? data.operator_name.split(" ").map((w) => w[0]).join("").toUpperCase().slice(0, 2)
    : "??";

  if (loading) {
    return (
      <div className="py-8 px-4 sm:px-6 lg:px-8">
        <div className="max-w-5xl mx-auto space-y-6">
          <div className="animate-pulse space-y-4">
            <div className="h-8 w-48 bg-slate-200 dark:bg-slate-700 rounded" />
            <div className="h-64 bg-slate-200 dark:bg-slate-700 rounded-xl" />
          </div>
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="py-8 px-4 sm:px-6 lg:px-8">
        <div className="max-w-5xl mx-auto">
          <div className="rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-300">
            {error || "Operator not found."}
          </div>
          <Link href="/master-data/operators" className="inline-block mt-4 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
            ← Back to Employees
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-5xl mx-auto space-y-6">
        {/* Employee Search */}
        <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-4">
          <div className="flex items-center gap-3">
            <div className="relative flex-1">
              <input
                type="search"
                value={searchQuery}
                onChange={(e) => handleSearch(e.target.value)}
                onFocus={() => searchResults.length > 0 && setSearchOpen(true)}
                onBlur={() => setTimeout(() => setSearchOpen(false), 150)}
                placeholder="Search employee by name or NIK..."
                className="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
              />
              {searchOpen && searchResults.length > 0 && (
                <div className="absolute left-0 top-full mt-1 z-50 w-full max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 shadow-lg">
                  {searchResults.map((item) => (
                    <div
                      key={item.id}
                      className="cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-slate-600"
                      onMouseDown={() => {
                        setSearchOpen(false);
                        router.push(`/operators/${item.id}`);
                      }}
                    >
                      <div className="font-medium text-slate-800 dark:text-slate-200">{item.label}</div>
                    </div>
                  ))}
                </div>
              )}
            </div>
            <Link
              href="/master-data/operators"
              className="shrink-0 rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
            >
              View All Employees
            </Link>
          </div>
        </div>

        {/* Profile Card */}
        <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
          <div className="flex items-start gap-6">
            {/* Avatar */}
            <div className="shrink-0">
              {data.photo_path ? (
                <img
                  src={(() => {
                    const clean = (data.photo_path ?? "").replace(/^\/+/, "");
                    return clean.startsWith("uploads/") ? "/api/" + clean : "/" + clean;
                  })()}
                  alt={data.operator_name ?? ""}
                  className="h-24 w-24 rounded-full object-cover border-2 border-slate-200 dark:border-slate-600"
                />
              ) : (
                <div className="h-24 w-24 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-2xl font-bold text-indigo-600 dark:text-indigo-400 border-2 border-slate-200 dark:border-slate-600">
                  {initials}
                </div>
              )}
            </div>

            {/* Info */}
            <div className="flex-1 space-y-2">
              <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-100">{data.operator_name}</h2>
              {data.nik_karyawan && (
                <p className="text-sm text-slate-500 dark:text-slate-400">NIK: {data.nik_karyawan}</p>
              )}
              <div className="flex flex-wrap gap-2 mt-2">
                {data.gender && (
                  <span className="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-700 px-2.5 py-0.5 text-xs font-medium text-slate-700 dark:text-slate-300">
                    {data.gender}
                  </span>
                )}
                {data.role && (
                  <span className="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300">
                    {data.role}
                  </span>
                )}
                {data.status_pkwtt && (
                  <span className="inline-flex items-center rounded-full bg-purple-100 dark:bg-purple-900/30 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:text-purple-300">
                    {data.status_pkwtt}
                  </span>
                )}
                {data.educational_level && (
                  <span className="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">
                    {data.educational_level}
                  </span>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Personal & Employment Details */}
        <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Personal &amp; Employment Details</h3>
            <button
              type="button"
              disabled={savingDates}
              onClick={async () => {
                setSavingDates(true);
                setSaveMsg(null);
                try {
                  const res = await fetch(`/api/operators/${id}/update-details`, {
                    method: "PUT",
                    headers: {
                      "Content-Type": "application/json",
                      ...(token ? { Authorization: `Bearer ${token}` } : {}),
                    },
                    body: JSON.stringify({ start_date: editStartDate, date_of_birth: editDob }),
                  });
                  if (res.status === 401) { logout(); router.replace("/login"); return; }
                  const body = await res.json().catch(() => null);
                  if (res.ok) {
                    setSaveMsg({ ok: true, text: body?.message || "Employee details updated successfully." });
                    // Refresh profile data to reflect computed fields
                    fetchProfile(id);
                  } else {
                    setSaveMsg({ ok: false, text: body?.message || "Failed to update." });
                  }
                } catch {
                  setSaveMsg({ ok: false, text: "Network error." });
                }
                setSavingDates(false);
              }}
              className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition disabled:opacity-50"
            >
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              {savingDates ? "Saving…" : "Save Changes"}
            </button>
          </div>

          {saveMsg && (
            <div className={`mb-4 rounded-lg px-3 py-2 text-sm ${saveMsg.ok
              ? "bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300"
              : "bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300"
            }`}>
              {saveMsg.text}
            </div>
          )}

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div className="rounded-xl border border-slate-100 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 px-4 py-3">
              <label htmlFor="start_date" className="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                Start Date
              </label>
              <input
                type="date"
                id="start_date"
                value={editStartDate}
                onChange={(e) => setEditStartDate(e.target.value)}
                className="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition"
              />
            </div>
            <div className="rounded-xl border border-slate-100 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 px-4 py-3">
              <label htmlFor="date_of_birth" className="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                Date of Birth
              </label>
              <input
                type="date"
                id="date_of_birth"
                value={editDob}
                onChange={(e) => setEditDob(e.target.value)}
                className="mt-1 block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition"
              />
            </div>
            <div className="rounded-xl border border-slate-100 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 px-4 py-3">
              <div className="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Working Age</div>
              <div className="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{data.working_age ?? "—"}</div>
              <div className="text-xs text-slate-400 mt-0.5">DOB → Start Date</div>
            </div>
            <div className="rounded-xl border border-slate-100 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 px-4 py-3">
              <div className="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Age</div>
              <div className="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
                {data.age ?? "—"}{data.age_year !== null ? ` (${data.age_year} years)` : ""}
              </div>
              <div className="text-xs text-slate-400 mt-0.5">DOB → Today</div>
            </div>
            <div className="rounded-xl border border-slate-100 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 px-4 py-3">
              <div className="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Years of Service</div>
              <div className="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{data.years_of_service ?? "—"}</div>
              <div className="text-xs text-slate-400 mt-0.5">Remaining until retirement (DOB + 59yr 20d)</div>
            </div>
          </div>
        </div>

        {/* Organization */}
        <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
          <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Organization</h3>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {[
              { label: "Factory", value: data.factory_name },
              { label: "Department", value: data.department_name },
              { label: "Division", value: data.division_name },
              { label: "Section", value: data.section_name },
              { label: "Line", value: data.line_name },
            ].map((item) => (
              <div key={item.label} className="rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 p-4">
                <div className="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{item.label}</div>
                <div className="text-sm font-semibold text-slate-900 dark:text-slate-100">{item.value ?? "—"}</div>
              </div>
            ))}
          </div>
        </div>

        {/* History Table */}
        <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
          <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">History</h3>
          {data.history.length === 0 ? (
            <p className="text-sm text-slate-500 dark:text-slate-400">No history records found.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-slate-200 dark:border-slate-700">
                    <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-400">No</th>
                    <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-400">Articles</th>
                    <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-400">Process</th>
                    <th className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-400">Process Version</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-200 dark:divide-slate-700">
                  {data.history.map((h, idx) => (
                    <tr key={h.id}>
                      <td className="px-4 py-3">{idx + 1}</td>
                      <td className="px-4 py-3">
                        <div className="font-medium text-slate-900 dark:text-slate-100">{h.article_name ?? "—"}</div>
                        {h.article_destination && <div className="text-xs text-slate-500 dark:text-slate-400">Destination: {h.article_destination}</div>}
                        {h.article_label_number && <div className="text-xs text-slate-500 dark:text-slate-400">Label: {h.article_label_number}</div>}
                      </td>
                      <td className="px-4 py-3">
                        <div className="font-medium text-slate-900 dark:text-slate-100">{h.process_name ?? "—"}</div>
                        {h.process_gsd_element && <div className="text-xs text-slate-500 dark:text-slate-400">GSD: {h.process_gsd_element}</div>}
                      </td>
                      <td className="px-4 py-3">
                        {h.version_number !== null ? `V${h.version_number}` : "—"}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>

        {/* Back link */}
        <Link
          href="/master-data/operators"
          className="inline-flex items-center gap-1 text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
        >
          ← Back to Employees
        </Link>
      </div>
    </div>
  );
}