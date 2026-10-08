"use client";

import { useState, useEffect, useRef, useCallback } from "react";
import { useRouter } from "next/navigation";
import { useAuthStore } from "@/store/auth-store";
import Link from "next/link";

/**
 * Line Balancing index page — faithful replica of
 * resources/views/operations/line-balancing/index.blade.php.
 * List with search/filter/sort/pagination, create modal, bulk delete.
 */

interface LBRow {
  id: number;
  factory_id: number;
  factory_name: string | null;
  article_id: number;
  article_name: string | null;
  line_id: number;
  line_name: string | null;
  report_name: string;
  status: string;
  created_by: number;
  created_by_name: string | null;
  created_at: string | null;
  updated_at: string | null;
}

interface Option { id: number; name: string; }

export default function LineBalancingIndexPage() {
  const router = useRouter();
  const token = useAuthStore((s) => s.token);
  const logout = useAuthStore((s) => s.logout);
  const user = useAuthStore((s) => s.user);

  // List state
  const [rows, setRows] = useState<LBRow[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [filterColumn, setFilterColumn] = useState("");
  const [filterValue, setFilterValue] = useState("");
  const [sortBy, setSortBy] = useState("id");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");
  const [showInactive, setShowInactive] = useState(false);
  const [banner, setBanner] = useState<{ kind: "success" | "error"; text: string } | null>(null);

  // Autocomplete
  const [acItems, setAcItems] = useState<{ id: number; label: string; description?: string }[]>([]);
  const [acOpen, setAcOpen] = useState(false);
  const acTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Create modal
  const [createOpen, setCreateOpen] = useState(false);
  const [createForm, setCreateForm] = useState({ factory_id: "", article_id: "", line_id: "", report_name: "" });

  // Dropdown options
  const [factories, setFactories] = useState<Option[]>([]);
  const [articles, setArticles] = useState<Option[]>([]);
  const [lines, setLines] = useState<Option[]>([]);

  // Bulk delete mode
  const [deleteMode, setDeleteMode] = useState(false);
  const [selected, setSelected] = useState<number[]>([]);

  const authHeaders = { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) };

  // Fetch list
  const fetchList = useCallback(async () => {
    setLoading(true);
    const sp = new URLSearchParams();
    sp.set("page", String(page));
    if (search) sp.set("search", search);
    if (filterColumn && filterValue) sp.set(filterColumn, filterValue);
    sp.set("sort", sortBy);
    sp.set("order", sortOrder);
    if (showInactive) sp.set("show_inactive", "1");

    try {
      const res = await fetch(`/api/line-balancing?${sp}`, { headers: authHeaders });
      if (res.status === 401) { logout(); router.replace("/login"); return; }
      const body = await res.json().catch(() => null);
      if (res.ok && body?.data) {
        setRows(body.data.data ?? []);
        setTotal(body.data.total ?? 0);
        setLastPage(body.data.last_page ?? 1);
      } else {
        setRows([]);
        setTotal(0);
      }
    } catch {
      setRows([]);
    }
    setLoading(false);
  }, [page, search, filterColumn, filterValue, sortBy, sortOrder, showInactive, token, logout, router]);

  useEffect(() => { fetchList(); }, [fetchList]);

  // Fetch dropdown options on mount
  useEffect(() => {
    const load = async () => {
      try {
        const [fRes, aRes] = await Promise.all([
          fetch("/api/master/factories/all", { headers: authHeaders }),
          fetch("/api/master/articles/all", { headers: authHeaders }),
        ]);
        if (fRes.ok) { const b = await fRes.json(); setFactories(Array.isArray(b?.data) ? b.data.map((x: any) => ({ id: x.id, name: x.factory_name ?? x.name ?? x.label ?? String(x.id) })) : []); }
        if (aRes.ok) { const b = await aRes.json(); setArticles(Array.isArray(b?.data) ? b.data.map((x: any) => ({ id: x.id, name: x.article_name ?? x.name ?? x.label ?? String(x.id) })) : []); }
      } catch { /* ignore */ }
    };
    load();
  }, [token]);

  // Fetch lines when factory changes in create modal
  useEffect(() => {
    if (!createForm.factory_id) { setLines([]); return; }
    const load = async () => {
      try {
        const res = await fetch(`/api/line-balancing/lines-by-factory/${createForm.factory_id}`, { headers: authHeaders });
        if (res.ok) { const b = await res.json(); setLines(Array.isArray(b?.data) ? b.data : []); }
      } catch { setLines([]); }
    };
    load();
  }, [createForm.factory_id, token]);

  // Search autocomplete
  const handleSearchInput = (v: string) => {
    setSearch(v);
    if (acTimer.current) clearTimeout(acTimer.current);
    if (v.trim().length < 1) { setAcItems([]); setAcOpen(false); return; }
    acTimer.current = setTimeout(async () => {
      try {
        const res = await fetch(`/api/line-balancing?search=${encodeURIComponent(v)}&page=1`, { headers: authHeaders });
        if (res.ok) {
          const b = await res.json();
          const items = (b?.data?.data ?? []).map((r: LBRow) => ({ id: r.id, label: r.report_name, description: `${r.factory_name ?? ""} — ${r.article_name ?? ""}` }));
          setAcItems(items);
          setAcOpen(items.length > 0);
        }
      } catch { /* ignore */ }
    }, 300);
  };

  // Sort
  const handleSort = (key: string) => {
    if (sortBy === key) setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    else { setSortBy(key); setSortOrder("asc"); }
    setPage(1);
  };

  // Create
  const handleCreate = async () => {
    try {
      const res = await fetch("/api/line-balancing", {
        method: "POST",
        headers: authHeaders,
        body: JSON.stringify(createForm),
      });
      if (res.status === 401) { logout(); router.replace("/login"); return; }
      const body = await res.json().catch(() => null);
      if (res.ok && body?.data) {
        setBanner({ kind: "success", text: body?.message || "Report created." });
        setCreateOpen(false);
        setCreateForm({ factory_id: "", article_id: "", line_id: "", report_name: "" });
        // Navigate to edit page
        router.push(`/operations/line-balancing/${body.data.id}`);
      } else {
        setBanner({ kind: "error", text: body?.message || "Failed to create report." });
      }
    } catch {
      setBanner({ kind: "error", text: "Network error." });
    }
  };

  // Bulk deactivate
  const handleBulkDeactivate = async () => {
    if (selected.length === 0) return;
    try {
      const res = await fetch("/api/line-balancing/bulk-deactivate", {
        method: "PATCH",
        headers: authHeaders,
        body: JSON.stringify({ ids: selected }),
      });
      if (res.status === 401) { logout(); router.replace("/login"); return; }
      const body = await res.json().catch(() => null);
      if (res.ok) {
        setBanner({ kind: "success", text: body?.message || "Reports deactivated." });
        setSelected([]);
        setDeleteMode(false);
        fetchList();
      } else {
        setBanner({ kind: "error", text: body?.message || "Failed to deactivate." });
      }
    } catch {
      setBanner({ kind: "error", text: "Network error." });
    }
  };

  const toggleRow = (id: number, checked: boolean) => {
    setSelected(checked ? [...selected, id] : selected.filter((x) => x !== id));
  };

  const SMALL_SELECT = "rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-2 py-1.5 text-sm";
  const EDIT_LINK = "text-indigo-600 dark:text-indigo-400 hover:underline text-sm font-medium";

  // Filter value options for factory/article/created_by
  const filterOptions = filterColumn === "factory_id" ? factories
    : filterColumn === "article_id" ? articles
    : null;

  return (
    <div className="py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-7xl mx-auto space-y-6">
        {/* Header banner */}
        <div className="rounded-2xl bg-gradient-to-r from-slate-800 to-slate-700 dark:from-slate-700 dark:to-slate-600 p-6 text-white">
          <div className="text-xs text-slate-300 mb-1">Lean Operations / Line Balancing</div>
          <h2 className="text-2xl font-bold">Line Balancing Reports</h2>
          <p className="text-sm text-slate-300 mt-1">Manage line balancing reports and analysis</p>
        </div>

        {banner && (
          <div className={`rounded-xl border px-4 py-3 text-sm ${
            banner.kind === "success"
              ? "border-emerald-200 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300"
              : "border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300"
          }`}>
            {banner.text}
          </div>
        )}

        <p className="text-sm text-slate-600 dark:text-slate-400">
          Total Records: <span className="font-semibold text-slate-900 dark:text-slate-100">{total}</span>
        </p>

        {/* Toolbar */}
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="flex flex-1 flex-wrap items-center gap-2">
            <div className="relative" id="autocomplete-wrapper">
              <input
                type="search"
                value={search}
                onChange={(e) => handleSearchInput(e.target.value)}
                onKeyDown={(e) => { if (e.key === "Enter") { e.preventDefault(); setPage(1); fetchList(); } }}
                onBlur={() => setTimeout(() => setAcOpen(false), 150)}
                placeholder="Search report..."
                autoComplete="off"
                className="w-56 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none"
              />
              {acOpen && acItems.length > 0 && (
                <div className="absolute left-0 top-full mt-1 z-50 w-full max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 shadow-lg">
                  {acItems.map((item) => (
                    <div
                      key={item.id}
                      className="cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-slate-600"
                      onMouseDown={() => { setSearch(item.label); setAcOpen(false); setPage(1); }}
                    >
                      <div className="font-medium text-slate-800 dark:text-slate-200">{item.label}</div>
                      {item.description && <div className="text-xs text-slate-500 dark:text-slate-400 truncate">{item.description}</div>}
                    </div>
                  ))}
                </div>
              )}
            </div>

            <select value={filterColumn} onChange={(e) => { setFilterColumn(e.target.value); setFilterValue(""); setPage(1); }} className={SMALL_SELECT}>
              <option value="">Filter by...</option>
              <option value="factory_id">Factory</option>
              <option value="article_id">Article</option>
              <option value="report_name">Report Name</option>
              <option value="created_by">Created By</option>
              <option value="status">Status</option>
            </select>

            {filterOptions ? (
              <select value={filterValue} onChange={(e) => { setFilterValue(e.target.value); setPage(1); }} className={SMALL_SELECT}>
                <option value="">Select...</option>
                {filterOptions.map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}
              </select>
            ) : filterColumn ? (
              <input
                type="text"
                value={filterValue}
                onChange={(e) => setFilterValue(e.target.value)}
                onKeyDown={(e) => { if (e.key === "Enter") { e.preventDefault(); setPage(1); fetchList(); } }}
                placeholder="Filter value..."
                className="w-40 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none"
              />
            ) : null}

            <label className="flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-400">
              <input type="checkbox" checked={showInactive} onChange={(e) => { setShowInactive(e.target.checked); setPage(1); }} className="rounded border-slate-300 dark:border-slate-600" />
              Show Inactive
            </label>
          </div>

          <div className="flex items-center gap-2">
            {deleteMode ? (
              <>
                <button type="button" onClick={handleBulkDeactivate} disabled={selected.length === 0} className="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-500 transition-colors disabled:opacity-50">
                  Delete Selected ({selected.length})
                </button>
                <button type="button" onClick={() => { setDeleteMode(false); setSelected([]); }} className="rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                  Cancel
                </button>
              </>
            ) : (
              <>
                <button type="button" onClick={() => setDeleteMode(true)} className="rounded-lg border border-red-200 dark:border-red-700 px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                  Delete
                </button>
                <button type="button" onClick={() => setCreateOpen(true)} className="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-500 transition-colors">
                  + New
                </button>
              </>
            )}
          </div>
        </div>

        {/* Table */}
        <div className="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
                <th className={`px-4 py-3 font-semibold w-10${deleteMode ? "" : " hidden"}`}></th>
                <th className="px-4 py-3 font-semibold text-left">No</th>
                {[
                  { key: "factory_name", label: "Factory", sortKey: "factory" },
                  { key: "article_name", label: "Article", sortKey: "article" },
                  { key: "report_name", label: "LB Report Name", sortKey: "report_name" },
                  { key: "created_at", label: "Created Date", sortKey: "created_date" },
                  { key: "updated_at", label: "Edited Date", sortKey: "edited_date" },
                  { key: "created_by_name", label: "Created By", sortKey: "created_by" },
                  { key: "status", label: "Status", sortKey: "status" },
                ].map((col) => (
                  <th
                    key={col.key}
                    className="px-4 py-3 font-semibold text-left cursor-pointer select-none hover:text-indigo-600 dark:hover:text-indigo-400"
                    onClick={() => handleSort(col.sortKey)}
                  >
                    {col.label}
                    {sortBy === col.sortKey && (
                      <span className="ml-1">{sortOrder === "asc" ? "↑" : "↓"}</span>
                    )}
                  </th>
                ))}
                <th className="px-4 py-3 font-semibold text-left">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 dark:divide-slate-700">
              {loading ? (
                <tr><td colSpan={10} className="px-4 py-8 text-center text-slate-500 dark:text-slate-400">Loading...</td></tr>
              ) : rows.length === 0 ? (
                <tr><td colSpan={10} className="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No data found.</td></tr>
              ) : (
                rows.map((row, idx) => (
                  <tr key={row.id} className="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <td className={`px-4 py-3 w-10${deleteMode ? "" : " hidden"}`}>
                      <input
                        type="checkbox"
                        checked={selected.includes(row.id)}
                        onChange={(e) => toggleRow(row.id, e.target.checked)}
                        className="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"
                      />
                    </td>
                    <td className="px-4 py-3">{(page - 1) * 15 + idx + 1}</td>
                    <td className="px-4 py-3">{row.factory_name ?? "—"}</td>
                    <td className="px-4 py-3">{row.article_name ?? "—"}</td>
                    <td className="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{row.report_name}</td>
                    <td className="px-4 py-3">{row.created_at ? new Date(row.created_at).toLocaleDateString() : "—"}</td>
                    <td className="px-4 py-3">{row.updated_at ? new Date(row.updated_at).toLocaleDateString() : "—"}</td>
                    <td className="px-4 py-3">{row.created_by_name ?? "—"}</td>
                    <td className="px-4 py-3">
                      <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                        row.status === "active" ? "bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300" : "bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300"
                      }`}>
                        {row.status}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        <Link href={`/operations/line-balancing/${row.id}`} className={EDIT_LINK}>Edit</Link>
                        <a href={`/api/line-balancing/${row.id}/export`} target="_blank" rel="noopener noreferrer" className="text-emerald-600 dark:text-emerald-400 hover:underline text-sm" title="Export">
                          📊
                        </a>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {lastPage > 1 && (
          <div className="flex items-center justify-between">
            <p className="text-sm text-slate-500 dark:text-slate-400">
              Showing {(page - 1) * 15 + 1}–{Math.min(page * 15, total)} of {total}
            </p>
            <div className="flex items-center gap-1">
              <button type="button" disabled={page <= 1} onClick={() => setPage(page - 1)} className="rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-1.5 text-sm disabled:opacity-50 hover:bg-slate-50 dark:hover:bg-slate-700">Prev</button>
              <span className="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-400">{page} / {lastPage}</span>
              <button type="button" disabled={page >= lastPage} onClick={() => setPage(page + 1)} className="rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-1.5 text-sm disabled:opacity-50 hover:bg-slate-50 dark:hover:bg-slate-700">Next</button>
            </div>
          </div>
        )}

        {/* Create Modal */}
        {createOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40" onClick={() => setCreateOpen(false)}>
            <div className="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 shadow-xl p-6 space-y-4" onClick={(e) => e.stopPropagation()}>
              <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-100">New Line Balancing Report</h3>

              <div className="space-y-3">
                <div>
                  <label className="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Factory</label>
                  <select value={createForm.factory_id} onChange={(e) => setCreateForm({ ...createForm, factory_id: e.target.value, line_id: "" })} className="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm">
                    <option value="">Select Factory</option>
                    {factories.map((f) => <option key={f.id} value={f.id}>{f.name}</option>)}
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Article</label>
                  <select value={createForm.article_id} onChange={(e) => setCreateForm({ ...createForm, article_id: e.target.value })} className="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm">
                    <option value="">Select Article</option>
                    {articles.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Production Line</label>
                  <select value={createForm.line_id} onChange={(e) => setCreateForm({ ...createForm, line_id: e.target.value })} className="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm">
                    <option value="">Select Line</option>
                    {lines.map((l) => <option key={l.id} value={l.id}>{l.name}</option>)}
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Report Name</label>
                  <input type="text" value={createForm.report_name} onChange={(e) => setCreateForm({ ...createForm, report_name: e.target.value })} placeholder="e.g. LB Report - May 2025" className="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm" />
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button type="button" onClick={() => setCreateOpen(false)} className="rounded-lg border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</button>
                <button type="button" onClick={handleCreate} disabled={!createForm.factory_id || !createForm.article_id || !createForm.line_id || !createForm.report_name} className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">Create</button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}