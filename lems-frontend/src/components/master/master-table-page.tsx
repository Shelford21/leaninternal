"use client";

/**
 * Generic Data Master table page — a faithful replica of the Laravel Blade
 * masters (simple-master.blade.php + the per-master blades under
 * resources/views/master-data/). All labels, strings and layout classes come
 * from master-config.ts / the blades verbatim. Do not "improve" them.
 */
import { useCallback, useEffect, useRef, useState, type FormEvent, type ReactNode } from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { MASTERS, fill, type ColumnDef, type FieldDef, type MasterDef } from "@/lib/master-config";
import { useAuthStore } from "@/store/auth-store";
import { bannerFor, downloadBlob, masterFetch, type ApiResult } from "./master-api";

type AnyRow = Record<string, any>;
type Banner = { kind: "success" | "error"; text: string } | null;

/* ------------------------------------------------------------------ */
/* shared class strings (verbatim from the blades)                     */
/* ------------------------------------------------------------------ */

const INPUT =
  "w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 focus:border-indigo-500 focus:outline-none";
const SMALL_SELECT =
  "rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none";
const LABEL = "mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300";
const BTN_PRIMARY = "rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500";
const BTN_CANCEL =
  "rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700";
const ACTIVE_PILL =
  "inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300 ring-1 ring-inset ring-emerald-600/20";
const INACTIVE_PILL =
  "inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/30 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-300 ring-1 ring-inset ring-red-600/20";
const EDIT_LINK = "text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300";

/**
 * Filter-value select display quirks after every page load (blade JS):
 * - re-select group (articles, gsd-elements, mechanics, operators, processes):
 *   `updateXXXFilterValues()` re-selects the APPLIED filter value when the
 *   current column's freshly built options contain it (also on column change);
 * - URL-restore group (departments, factories, production-lines, simple-master):
 *   restored from the URL on page load only — a column change resets it to
 *   "All values";
 * - destinations: never restores anything — the select always shows
 *   "All values" after a submit even while the filter is applied.
 */
const RESELECT_FILTER_VALUE = new Set(["articles", "gsd-elements", "mechanics", "operators", "processes"]);

const fmtDate = (v: any): string => (v ? String(v).slice(0, 10) : "—");
const photoSrc = (p: any): string | null => {
  if (!p) return null;
  const clean = String(p).replace(/^\/+/, "");
  // Route through API to serve files uploaded after production build
  if (clean.startsWith("uploads/")) return "/api/" + clean;
  return "/" + clean;
};
const initials = (name: any): string => String(name ?? "").substring(0, 2).toUpperCase();

/* ------------------------------------------------------------------ */

export default function MasterTablePage({ slug }: { slug: string }) {
  const def = MASTERS[slug];
  const user = useAuthStore((s) => s.user);
  const roleName = user?.role?.role_name ?? "";
  const isViewer = roleName === "viewer";

  /* list state */
  const [rows, setRows] = useState<AnyRow[]>([]);
  const [totalCount, setTotalCount] = useState(0);
  const [filterValues, setFilterValues] = useState<Record<string, any[]>>({});
  const [options, setOptions] = useState<AnyRow>({});
  const [banner, setBanner] = useState<Banner>(null);

  /* APPLIED query state — the URL query string is the single source of truth
     (Laravel GET-form parity: submit / sort / clear are all navigations). */
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const appliedSearch = searchParams.get("search") ?? "";
  const appliedFilterValue = searchParams.get("filter_value") ?? "";
  const appliedFilterColumn = searchParams.get("filter_column") ?? "";
  const appliedInactive = searchParams.get("show_inactive") === "1";
  const appliedDateFrom = searchParams.get("date_from") ?? "";
  const appliedDateTo = searchParams.get("date_to") ?? "";
  const appliedSort = searchParams.get("sort") || def.defaultSort;
  const appliedDirection: "asc" | "desc" = searchParams.get("direction") === "desc" ? "desc" : "asc";

  /* live filter form controls ("pending" values — applied on submit only) */
  const [searchInput, setSearchInput] = useState(appliedSearch);
  const [filterColumn, setFilterColumn] = useState(appliedFilterColumn);
  const [filterValue, setFilterValue] = useState("");
  const [showInactive, setShowInactive] = useState(appliedInactive);
  const [dateFrom, setDateFrom] = useState(appliedDateFrom);
  const [dateTo, setDateTo] = useState(appliedDateTo);

  /* delete mode */
  const [deleteMode, setDeleteMode] = useState(false);
  const [selected, setSelected] = useState<number[]>([]);

  /* modals */
  const [modal, setModal] = useState<null | "create" | "edit" | "import">(null);
  const [editRow, setEditRow] = useState<AnyRow | null>(null);

  /* autocomplete */
  const [acItems, setAcItems] = useState<AnyRow[]>([]);
  const [acOpen, setAcOpen] = useState(false);
  const acTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const load = useCallback(async () => {
    const qs = new URLSearchParams();
    if (appliedSearch) qs.set("search", appliedSearch);
    if (appliedFilterColumn) qs.set("filter_column", appliedFilterColumn);
    if (appliedFilterValue) qs.set("filter_value", appliedFilterValue);
    if (appliedInactive) qs.set("show_inactive", "1");
    if (appliedSort) qs.set("sort", appliedSort);
    if (appliedDirection) qs.set("direction", appliedDirection);
    if (appliedDateFrom) qs.set("date_from", appliedDateFrom);
    if (appliedDateTo) qs.set("date_to", appliedDateTo);
    const r = await masterFetch(`/${slug}?${qs.toString()}`);
    if (r.ok && r.data) {
      const fv: Record<string, any[]> = r.data.filterValues ?? {};
      setRows(r.data.rows ?? []);
      setTotalCount(r.data.totalCount ?? 0);
      setFilterValues(fv);
      setOptions(r.data.options ?? {});
      /* Blade parity: every page load re-renders the filter form from the URL. */
      setSearchInput(appliedSearch);
      setFilterColumn(appliedFilterColumn);
      setShowInactive(appliedInactive);
      setDateFrom(appliedDateFrom);
      setDateTo(appliedDateTo);
      /* value-select display quirks — see RESELECT_FILTER_VALUE */
      const opts = (fv[appliedFilterColumn] ?? []).map((v: any) => String(v));
      setFilterValue(
        def.variant !== "destinations" && appliedFilterValue !== "" && opts.includes(String(appliedFilterValue))
          ? appliedFilterValue
          : ""
      );
      setAcOpen(false);
    } else if (!r.ok) {
      setBanner(bannerFor(r) ?? { kind: "error", text: r.message || "Internal Server Error." });
    }
  }, [slug, def.variant, appliedSearch, appliedFilterColumn, appliedFilterValue, appliedInactive, appliedSort, appliedDirection, appliedDateFrom, appliedDateTo]);

  useEffect(() => {
    load();
  }, [load]);

  /* ------------------------------------------------------------------ */
  /* filter form semantics (blade parity)                                */
  /*                                                                     */
  /* The blade filter form is a GET form: `form.submit()` serializes every */
  /* control (including empty ones) in DOM order and reloads the page.    */
  /* Sorting and Clear are plain links (fullUrlWithQuery / bare index     */
  /* route); changing the filter column or a date input never submits.    */
  /* ------------------------------------------------------------------ */

  const navigateTo = (qs: string) => {
    setAcOpen(false);
    if (qs === searchParams.toString()) {
      /* same URL — Laravel still reloads and re-queries */
      load();
    } else {
      router.push(qs ? `${pathname}?${qs}` : pathname);
    }
  };

  /** GET-form submit: serializes the live controls like the blade form. */
  const submitForm = (over?: { search?: string; filterValue?: string; showInactive?: boolean }) => {
    const p = new URLSearchParams();
    p.set("search", over?.search ?? searchInput);
    p.set("filter_value", over?.filterValue ?? filterValue);
    if (def.variant === "operators") {
      p.set("date_from", dateFrom);
      p.set("date_to", dateTo);
    }
    p.set("filter_column", filterColumn);
    p.set("sort", appliedSort);
    p.set("direction", appliedDirection);
    if (over?.showInactive ?? showInactive) p.set("show_inactive", "1");
    navigateTo(p.toString());
  };

  /**
   * `updateXXXFilterValues()` only repopulates the value select — it never
   * submits. The re-select group re-selects the APPLIED value when the new
   * column's options contain it; every other master resets to "All values".
   */
  const onColumnChange = (col: string) => {
    setFilterColumn(col);
    const opts = (filterValues[col] ?? []).map((v: any) => String(v));
    setFilterValue(
      RESELECT_FILTER_VALUE.has(def.variant) && appliedFilterValue !== "" && opts.includes(String(appliedFilterValue))
        ? appliedFilterValue
        : ""
    );
  };

  const clearFilters = () => {
    /* blade: <a href="{{ route('master-data.X') }}"> — the bare index route */
    navigateTo("");
  };

  /** Blade sort links: request()->fullUrlWithQuery([...]) + direction toggle. */
  const toggleSort = (key: string) => {
    const dir: "asc" | "desc" = appliedSort === key && appliedDirection === "asc" ? "desc" : "asc";
    const p = new URLSearchParams(searchParams.toString());
    p.set("sort", key);
    p.set("direction", dir);
    navigateTo(p.toString());
  };

  const enterDeleteMode = () => {
    setDeleteMode(true);
    setSelected([]);
  };
  const exitDeleteMode = () => {
    setDeleteMode(false);
    setSelected([]);
  };
  const toggleRow = (id: number, checked: boolean) => {
    setSelected((prev) => (checked ? [...prev, id] : prev.filter((x) => x !== id)));
  };
  const toggleAll = (checked: boolean) => {
    setSelected(checked ? rows.map((r) => Number(r.id)) : []);
  };

  const confirmBulkDelete = async () => {
    if (selected.length === 0) {
      window.alert(def.zeroAlert);
      return;
    }
    if (!window.confirm(fill(def.confirmTemplate, { n: selected.length }))) return;
    const r = await masterFetch(`/${slug}/bulk-deactivate`, {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ ids: selected }),
    });
    finishAction(r);
  };

  const exportFile = async () => {
    const r = await masterFetch(`/${slug}/export`);
    if (r.ok && r.blob) downloadBlob(r.blob, r.fileName ?? null, def.exportFile);
    else setBanner(bannerFor(r) ?? { kind: "error", text: r.message || "Internal Server Error." });
  };

  const openEdit = (row: AnyRow) => {
    setEditRow(row);
    setModal("edit");
  };

  const closeModal = () => {
    setModal(null);
    setEditRow(null);
  };

  /**
   * Blade parity: create/update/import/bulk-deactivate all end in
   * `redirect()->route('master-data.X')` — the bare index route (search,
   * filters and sorting reset) plus a flash message. A ValidationException
   * redirects BACK to the same URL and the blades never render $errors, so
   * the modal closes silently and the list reloads unchanged.
   */
  const finishAction = (r: ApiResult) => {
    setBanner(bannerFor(r));
    closeModal();
    exitDeleteMode();
    if (r.status === 422 && r.errors) load();
    else navigateTo("");
  };

  /* ---------------------------------------------------------------- */
  /* rendering                                                         */
  /* ---------------------------------------------------------------- */

  const cellValue = (row: AnyRow, col: ColumnDef): ReactNode => {
    if (def.variant === "articles" && col.key === "photo_path") return <PhotoCell row={row} nameKey="article_name" />;
    if (def.variant === "operators") return renderOperatorCell(row, col);
    if (def.variant === "processes") return renderProcessCell(row, col);
    if (def.variant === "gsd-elements" && col.key === "code") {
      return (
        <code className="rounded bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 text-xs">{row.code}</code>
      );
    }
    if (col.render === "status") return <Pill status={row.status} />;
    if (col.render === "desc") return <>{row[col.key] ?? "—"}</>;
    const key = col.key === "__name__" ? def.nameField : col.key;
    return <>{row[key] ?? ""}</>;
  };

  const renderOperatorCell = (row: AnyRow, col: ColumnDef): ReactNode => {
    switch (col.key) {
      case "__photo__":
        return <PhotoCell row={row} nameKey="operator_name" photoKey="photo_path" />;
      case "operator_name":
        return <>{row.operator_name}</>;
      case "nik_karyawan":
        return <>{row.nik_karyawan ?? "—"}</>;
      case "gender":
        return <>{row.gender ?? "—"}</>;
      case "role":
        return <>{row.role ?? "—"}</>;
      case "factory":
        return <>{row.factories?.factory_name ?? "—"}</>;
      case "department":
        return <>{row.departments?.department_name ?? "—"}</>;
      case "division":
        return <>{row.divisions?.division ?? "—"}</>;
      case "section":
        return <>{row.sections?.section ?? "—"}</>;
      case "line":
        return <>{row.production_lines?.line_name ?? "—"}</>;
      case "status_pkwtt":
        return <>{row.status_pkwtt?.pkwtt ?? "—"}</>;
      case "educational_level":
        return <>{row.educational_levels?.level ?? "—"}</>;
      case "start_date":
        return <>{fmtDate(row.start_date)}</>;
      case "date_of_birth":
        return <>{fmtDate(row.date_of_birth)}</>;
      case "status":
        return <Pill status={row.status} />;
      default:
        return <>{row[col.key] ?? ""}</>;
    }
  };

  const renderProcessCell = (row: AnyRow, col: ColumnDef): ReactNode => {
    if (col.key === "__versions__") {
      const versions: AnyRow[] = [...(row.process_versions ?? [])].sort(
        (a, b) => Number(a.id ?? 0) - Number(b.id ?? 0)
      );
      if (versions.length === 0) return <span className="text-slate-500 dark:text-slate-400">—</span>;
      return (
        <>
          {versions.map((v, i) => {
            const el = (options.gsdElements ?? []).find((e: AnyRow) => Number(e.id) === Number(v.gsd_element_id));
            return (
              <span
                key={v.id ?? i}
                className={`mr-2 inline-flex rounded-full bg-slate-100 dark:bg-slate-700 px-2 py-1 text-xs font-medium text-slate-700 dark:text-slate-300${i === 0 ? "" : ""}`}
                title={el?.element_name ?? "No GSD element"}
              >
                V{v.version_number}
              </span>
            );
          })}
        </>
      );
    }
    if (col.key === "__gsd__") {
      const codes: string[] = [];
      for (const v of row.process_versions ?? []) {
        for (const e of v.gsd_elements ?? []) {
          if (e?.code && !codes.includes(e.code)) codes.push(e.code);
        }
      }
      return <>{codes.length ? codes.join(" - ") : "—"}</>;
    }
    return cellValueDefault(row, col);
  };

  const cellValueDefault = (row: AnyRow, col: ColumnDef): ReactNode => {
    if (col.render === "status") return <Pill status={row.status} />;
    if (col.render === "desc") return <>{row[col.key] ?? "—"}</>;
    return <>{row[col.key] ?? ""}</>;
  };

  /* operator column layout (No + 15 cols + Actions, verbatim) */
  const operatorCols: ColumnDef[] = def.variant === "operators"
    ? [
        { key: "__photo__", header: "Photo" },
        { key: "operator_name", header: "Nama", sortKey: "operator_name" },
        { key: "nik_karyawan", header: "NIK Karyawan", sortKey: "nik_karyawan" },
        { key: "gender", header: "Gender", sortKey: "gender" },
        { key: "role", header: "Role", sortKey: "role" },
        { key: "factory", header: "Factory", sortKey: "factory" },
        { key: "department", header: "Department", sortKey: "department" },
        { key: "division", header: "Division", sortKey: "division" },
        { key: "section", header: "Section", sortKey: "section" },
        { key: "line", header: "Line", sortKey: "line" },
        { key: "status_pkwtt", header: "Status PKWTT", sortKey: "status_pkwtt" },
        { key: "educational_level", header: "Educational Level", sortKey: "educational_level" },
        { key: "start_date", header: "Start Date", sortKey: "start_date" },
        { key: "date_of_birth", header: "Date of Birth", sortKey: "date_of_birth" },
        { key: "status", header: "Status", sortKey: "status" },
      ]
    : [];

  const cols: ColumnDef[] = def.variant === "operators" ? operatorCols : def.columns;
  const isDateFilter = filterColumn === "start_date" || filterColumn === "date_of_birth";

  return (
    <div className="py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-7xl mx-auto space-y-6">
        <h2 className="font-semibold text-lg text-slate-800 dark:text-slate-200 leading-tight">{def.header}</h2>

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

        {def.totalRecords && (
          <p className="text-sm text-slate-600 dark:text-slate-400">
            Total Records: <span className="font-semibold text-slate-900">{totalCount}</span>
          </p>
        )}

        {/* toolbar */}
        <div className="flex flex-wrap items-center justify-between gap-3">
          <form
            className="flex flex-1 flex-wrap items-center gap-2"
            onSubmit={(e) => {
              e.preventDefault();
              submitForm();
            }}
          >
            <div className="relative" id="autocomplete-wrapper">
              <input
                type="search"
                name="search"
                value={searchInput}
                onChange={(e) => {
                  const v = e.target.value;
                  setSearchInput(v);
                  /* blade autocomplete: 300ms debounce, typing only */
                  if (acTimer.current) clearTimeout(acTimer.current);
                  const q = v.trim();
                  if (q.length < 1) {
                    setAcItems([]);
                    setAcOpen(false);
                    return;
                  }
                  acTimer.current = setTimeout(async () => {
                    const r = await masterFetch(`/${slug}/search?q=${encodeURIComponent(q)}`);
                    const results: AnyRow[] = r.ok && Array.isArray(r.data) ? r.data : [];
                    setAcItems(results);
                    setAcOpen(results.length > 0);
                  }, 300);
                }}
                onKeyDown={(e) => {
                  if (e.key === "Enter") {
                    e.preventDefault();
                    submitForm();
                  }
                }}
                onBlur={() => setTimeout(() => setAcOpen(false), 150)}
                placeholder={def.searchPlaceholder}
                autoComplete="off"
                className={`w-56 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none${def.searchUppercase ? " uppercase" : ""}`}
                style={def.searchUppercase ? { textTransform: "uppercase" } : undefined}
              />
              {acOpen && acItems.length > 0 && (
                <div className="absolute left-0 top-full mt-1 z-50 w-full max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 shadow-lg">
                  {acItems.map((item) => (
                    <div
                      key={item.id}
                      className="ac-item cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-slate-700"
                      onMouseDown={() => {
                        setSearchInput(item.label);
                        submitForm({ search: item.label });
                      }}
                    >
                      <div className="font-medium text-slate-800 dark:text-slate-200">{item.label}</div>
                      {item.description ? (
                        <div className="text-xs text-slate-500 dark:text-slate-400 truncate">{item.description}</div>
                      ) : null}
                    </div>
                  ))}
                </div>
              )}
            </div>

            <select
              id="filter-column"
              value={filterColumn}
              onChange={(e) => onColumnChange(e.target.value)}
              className={SMALL_SELECT}
            >
              <option value="">Filter by...</option>
              {def.filterColumns.map((c) => (
                <option key={c.value} value={c.value}>
                  {c.label}
                </option>
              ))}
            </select>

            {!isDateFilter && (
              <select
                id="filter-value"
                value={filterValue}
                onChange={(e) => {
                  /* every blade auto-submits the form when the value changes */
                  const v = e.target.value;
                  setFilterValue(v);
                  submitForm({ filterValue: v });
                }}
                className={SMALL_SELECT}
              >
                <option value="">All values</option>
                {(filterValues[filterColumn] ?? []).map((v: any) => (
                  <option key={String(v)} value={String(v)}>
                    {String(v)}
                  </option>
                ))}
              </select>
            )}

            {def.variant === "operators" && (
              <div id="operator-date-range-filters" className={isDateFilter ? "flex items-center gap-2" : "hidden"}>
                <input
                  type="date"
                  value={dateFrom}
                  onChange={(e) => setDateFrom(e.target.value)}
                  className="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none"
                />
                <span className="text-sm text-slate-500">to</span>
                <input
                  type="date"
                  value={dateTo}
                  onChange={(e) => setDateTo(e.target.value)}
                  className="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 focus:border-indigo-500 focus:outline-none"
                />
              </div>
            )}

            <label className="ml-2 inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
              <input
                type="checkbox"
                checked={showInactive}
                onChange={(e) => {
                  /* blade: onchange="this.form.submit()" */
                  const checked = e.target.checked;
                  setShowInactive(checked);
                  submitForm({ showInactive: checked });
                }}
                className="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"
              />
              {def.showInactiveLabel}
            </label>

            <button
              type="button"
              onClick={clearFilters}
              className="rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700"
            >
              Clear
            </button>
          </form>

          <div className="ml-auto flex gap-2">
            {!isViewer && (
              <button
                type="button"
                onClick={() => setModal("import")}
                className="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700"
              >
                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-4-4m4 4l4-4" />
                </svg>
                Import
              </button>
            )}
            <button
              type="button"
              onClick={exportFile}
              className="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700"
            >
              <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l4-4m-4 4l-4-4" />
              </svg>
              Export
            </button>
            {!isViewer && (
              <button
                type="button"
                onClick={() => setModal("create")}
                className="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
              >
                + New
              </button>
            )}
            {!isViewer && !deleteMode && (
              <button
                type="button"
                onClick={enterDeleteMode}
                className="inline-flex items-center gap-2 rounded-xl border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-600 dark:text-red-400 dark:hover:bg-red-900/20"
              >
                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Delete
              </button>
            )}
          </div>
        </div>

        {/* bulk delete bar */}
        <div
          id="bulk-delete-bar"
          className={`${deleteMode ? "flex" : "hidden"} flex items-center justify-between rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-4 py-3`}
        >
          <div className="text-sm text-red-700 dark:text-red-300">
            {def.bulkCounter === "selected" ? (
              <>
                Selected: <strong>{selected.length}</strong>
              </>
            ) : (
              <span>
                <span>{selected.length}</span> record(s) selected
              </span>
            )}
          </div>
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={confirmBulkDelete}
              className={`rounded-xl bg-red-600 px-4 text-sm font-medium text-white hover:bg-red-500${
                def.bulkCounter === "selected" ? " py-1.5" : " py-2"
              }`}
            >
              Confirm Delete
            </button>
            <button
              type="button"
              onClick={exitDeleteMode}
              className={`rounded-xl border border-slate-200 dark:border-slate-600 px-4 text-sm font-medium text-slate-600 dark:text-slate-300 dark:hover:bg-slate-700${
                def.bulkCounter === "selected" ? " py-1.5" : " py-2"
              }`}
            >
              {def.bulkCancel}
            </button>
          </div>
        </div>

        {/* table */}
        <div className="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
          <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-left text-sm text-slate-700 dark:text-slate-300">
            <thead className="bg-slate-50 dark:bg-slate-700/50">
              <tr>
                <th className={`px-4 py-3 font-semibold w-10${deleteMode ? "" : " hidden"}`}>
                  <input
                    type="checkbox"
                    checked={rows.length > 0 && selected.length === rows.length}
                    onChange={(e) => toggleAll(e.target.checked)}
                    className="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"
                  />
                </th>
                <th className="px-4 py-3 font-semibold">No</th>
                {cols.map((col) => {
                  /* simple-master name column uses the '__name__' placeholder key;
                     the blade sort link sends the REAL field name ($nameField). */
                  const sortKey = col.sortKey === "__name__" ? def.nameField : col.sortKey;
                  const sortable = sortKey && def.sortable.includes(sortKey);
                  const active = sortKey && appliedSort === sortKey;
                  return (
                    <th key={col.key} className="px-4 py-3 font-semibold">
                      {sortable ? (
                        <button type="button" onClick={() => toggleSort(sortKey!)} className="hover:text-indigo-600 dark:hover:text-indigo-400">
                          {col.header}
                          {active && <span className="text-xs">{appliedDirection === "asc" ? "▲" : "▼"}</span>}
                        </button>
                      ) : (
                        col.header
                      )}
                    </th>
                  );
                })}
                <th className="px-4 py-3 font-semibold">{def.actionHeader}</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 dark:divide-slate-700">
              {rows.length === 0 ? (
                <tr>
                  <td colSpan={def.emptyColspan} className="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                    {def.emptyText}
                  </td>
                </tr>
              ) : (
                rows.map((row, idx) => (
                  <tr key={row.id}>
                    <td className={`px-4 py-3 w-10${deleteMode ? "" : " hidden"}`}>
                      <input
                        type="checkbox"
                        checked={selected.includes(Number(row.id))}
                        onChange={(e) => toggleRow(Number(row.id), e.target.checked)}
                        className="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"
                      />
                    </td>
                    <td className="px-4 py-3">{idx + 1}</td>
                    {cols.map((col, ci) => {
                      const isName = col.key === def.nameField || col.key === "__name__";
                      return (
                        <td
                          key={col.key}
                          className={`px-4 py-3${isName ? " font-medium text-slate-900 dark:text-slate-100" : ""}`}
                        >
                          {cellValue(row, col)}
                        </td>
                      );
                    })}
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        {def.variant === "operators" && (
                          <a href={`/operators/${row.id}`} className={EDIT_LINK}>
                            Details
                          </a>
                        )}
                        {!(def.variant === "operators" && isViewer) && (
                          <button type="button" onClick={() => openEdit(row)} className={EDIT_LINK}>
                            Edit
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {modal === "create" && (
        <FormModal def={def} mode="create" row={null} options={options} onClose={closeModal} onSaved={finishAction} />
      )}
      {modal === "edit" && editRow && (
        <FormModal def={def} mode="edit" row={editRow} options={options} onClose={closeModal} onSaved={finishAction} />
      )}
      {modal === "import" && <ImportModal def={def} onClose={closeModal} onSaved={finishAction} />}
    </div>
  );
}

/* ------------------------------------------------------------------ */
/* small presentational pieces                                         */
/* ------------------------------------------------------------------ */

function Pill({ status }: { status: any }) {
  return <span className={status === "active" ? ACTIVE_PILL : INACTIVE_PILL}>{status === "active" ? "Active" : "Inactive"}</span>;
}

function PhotoCell({ row, nameKey, photoKey = "photo_path" }: { row: AnyRow; nameKey: string; photoKey?: string }) {
  const src = photoSrc(row[photoKey]);
  if (src) {
    return <img src={src} alt={row[nameKey]} className="h-10 w-10 rounded-full object-cover" />;
  }
  return (
    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600">
      {initials(row[nameKey])}
    </div>
  );
}

/* ------------------------------------------------------------------ */
/* create / edit form modal                                            */
/* ------------------------------------------------------------------ */

function FormModal({
  def,
  mode,
  row,
  options,
  onClose,
  onSaved,
}: {
  def: MasterDef;
  mode: "create" | "edit";
  row: AnyRow | null;
  options: AnyRow;
  onClose: () => void;
  onSaved: (r: ApiResult) => void;
}) {
  const isEdit = mode === "edit";
  const [busy, setBusy] = useState(false);
  const [values, setValues] = useState<Record<string, string>>(() => initialValues(def, row));
  const [file, setFile] = useState<File | null>(null);
  const [gsdRows, setGsdRows] = useState<string[]>(() => initialGsdRows(def, row, options));

  const set = (name: string, v: string) => setValues((prev) => ({ ...prev, [name]: v }));

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    setBusy(true);
    const fd = new FormData();
    if (def.variant === "operators") {
      for (const k of [
        "operator_name", "nik_karyawan", "gender", "role", "status_pkwtt_id", "educational_level_id",
        "start_date", "date_of_birth", "factory_id", "department_id", "division_id", "section_id", "line_id",
      ]) fd.append(k, values[k] ?? "");
      if (file) fd.append("photo", file);
    } else if (def.variant === "articles") {
      fd.append("article_name", values.article_name ?? "");
      fd.append("description", values.description ?? "");
      if (file) fd.append("photo", file);
    } else if (def.variant === "gsd-elements") {
      for (const k of ["gsd_category_id", "element_name", "description", "code", "tmu", "seconds", "motion_sequence"]) {
        fd.append(k, values[k] ?? "");
      }
    } else if (def.variant === "processes") {
      fd.append("process_name", values.process_name ?? "");
      fd.append("version_number", values.version_number ?? "1");
      for (const id of gsdRows) if (id) fd.append("gsd_element_ids[]", id);
    } else {
      for (const f of def.fields) fd.append(f.name, values[f.name] ?? "");
    }
    const r = await masterFetch(isEdit ? `/${def.slug}/${row!.id}` : `/${def.slug}`, {
      method: isEdit ? "PUT" : "POST",
      body: fd,
    });
    setBusy(false);
    onSaved(r);
  };

  const isGsd = def.variant === "gsd-elements";
  const title = isEdit ? def.modalEdit : def.modalNew;

  return (
    <div className={`fixed inset-0 z-50 ${isGsd ? "bg-black/40 flex items-center justify-center" : "bg-slate-900/40"}`}>
      {!isGsd && (
        <div className="flex min-h-full items-center justify-center p-4">
          <div className="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
            <FormHeader title={title} onClose={onClose} />
            <FormBody
              def={def} mode={mode} values={values} set={set} file={file} setFile={setFile}
              gsdRows={gsdRows} setGsdRows={setGsdRows} options={options} row={row}
              busy={busy} submit={submit} onClose={onClose} />
          </div>
        </div>
      )}
      {isGsd && (
        <div className="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
          <div className="mb-5 flex items-center justify-between">
            <h3 className="text-lg font-semibold text-slate-800 dark:text-slate-200">{title}</h3>
            <button type="button" onClick={onClose} className="text-2xl leading-none text-slate-500 dark:text-slate-400">
              &times;
            </button>
          </div>
          <FormBody
            def={def} mode={mode} values={values} set={set} file={file} setFile={setFile}
            gsdRows={gsdRows} setGsdRows={setGsdRows} options={options} row={row}
            busy={busy} submit={submit} onClose={onClose} />
        </div>
      )}
    </div>
  );
}

function FormHeader({ title, onClose }: { title: string; onClose: () => void }) {
  return (
    <div className="mb-5 flex items-center justify-between">
      <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-100">{title}</h3>
      <button type="button" onClick={onClose} className="text-slate-500 dark:text-slate-400">
        ✕
      </button>
    </div>
  );
}

function FormBody(props: {
  def: MasterDef;
  mode: "create" | "edit";
  values: Record<string, string>;
  set: (name: string, v: string) => void;
  file: File | null;
  setFile: (f: File | null) => void;
  gsdRows: string[];
  setGsdRows: (rows: string[]) => void;
  options: AnyRow;
  row: AnyRow | null;
  busy: boolean;
  submit: (e: FormEvent) => void;
  onClose: () => void;
}) {
  const { def, mode, values, set, file, setFile, gsdRows, setGsdRows, options, row, busy, submit, onClose } = props;
  const isEdit = mode === "edit";

  return (
    <form onSubmit={submit} className="space-y-4">
      {def.variant === "operators" && (
        <>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label className={LABEL}>Nama</label>
              <input
                type="text" required value={values.operator_name ?? ""} maxLength={200}
                onChange={(e) => set("operator_name", e.target.value)}
                className={`${INPUT} uppercase`} style={{ textTransform: "uppercase" }} />
            </div>
            <div>
              <label className={LABEL}>NIK Karyawan</label>
              <input
                type="text" value={values.nik_karyawan ?? ""} maxLength={50} placeholder="Optional"
                onChange={(e) => set("nik_karyawan", e.target.value)}
                className={`${INPUT} uppercase`} style={{ textTransform: "uppercase" }} />
            </div>
            <div>
              <label className={LABEL}>{isEdit ? "Replace Photo" : "Photo"}</label>
              {isEdit && row?.photo_path && !file && (
                <div className="mb-2">
                  <img src={photoSrc(row.photo_path) ?? ""} alt="Current photo" className="h-16 w-16 rounded-lg object-cover" />
                </div>
              )}
              {file && (
                <div className="mb-2">
                  <img src={URL.createObjectURL(file)} alt="Preview" className="h-16 w-16 rounded-lg object-cover" />
                  <p className="text-xs text-slate-500 mt-1">{file.name}</p>
                </div>
              )}
              <input
                type="file" accept=".jpg,.jpeg,.png"
                onChange={(e) => setFile(e.target.files?.[0] ?? null)}
                className="w-full text-sm text-slate-600 dark:text-slate-300" />
            </div>
            <div>
              <label className={LABEL}>Start Date</label>
              <input
                type="date" value={values.start_date ?? ""} onChange={(e) => set("start_date", e.target.value)}
                className={INPUT} />
            </div>
            <div>
              <label className={LABEL}>Date of Birth</label>
              <input
                type="date" value={values.date_of_birth ?? ""} onChange={(e) => set("date_of_birth", e.target.value)}
                className={INPUT} />
            </div>
          </div>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label className={LABEL}>Factory</label>
              <select value={values.factory_id ?? ""} onChange={(e) => set("factory_id", e.target.value)} className={INPUT}>
                <option value="">Select factory</option>
                {(options.factories ?? []).map((o: AnyRow) => (
                  <option key={o.id} value={o.id}>{o.factory_name}</option>
                ))}
              </select>
            </div>
            <div>
              <label className={LABEL}>Department</label>
              <select value={values.department_id ?? ""} onChange={(e) => set("department_id", e.target.value)} className={INPUT}>
                <option value="">Select department</option>
                {(options.departments ?? []).map((o: AnyRow) => (
                  <option key={o.id} value={o.id}>{o.department_name}</option>
                ))}
              </select>
            </div>
            <div>
              <label className={LABEL}>Division</label>
              <select value={values.division_id ?? ""} onChange={(e) => set("division_id", e.target.value)} className={INPUT}>
                <option value="">Select division</option>
                {(options.divisions ?? []).map((o: AnyRow) => (
                  <option key={o.id} value={o.id}>{o.division}</option>
                ))}
              </select>
            </div>
            <div>
              <label className={LABEL}>Section</label>
              <select value={values.section_id ?? ""} onChange={(e) => set("section_id", e.target.value)} className={INPUT}>
                <option value="">Select section</option>
                {(options.sections ?? []).map((o: AnyRow) => (
                  <option key={o.id} value={o.id}>{o.section}</option>
                ))}
              </select>
            </div>
            <div>
              <label className={LABEL}>Line</label>
              <select value={values.line_id ?? ""} onChange={(e) => set("line_id", e.target.value)} className={INPUT}>
                <option value="">Select line</option>
                {(options.productionLines ?? []).map((o: AnyRow) => (
                  <option key={o.id} value={o.id}>{o.line_name}</option>
                ))}
              </select>
            </div>
            <div>
              <label className={LABEL}>Gender</label>
              <select value={values.gender ?? ""} onChange={(e) => set("gender", e.target.value)} className={INPUT}>
                <option value="">Select gender</option>
                {(options.genders ?? []).map((o: AnyRow) => (
                  <option key={o.id} value={o.gender}>{o.gender} - {o.description}</option>
                ))}
              </select>
            </div>
            <div>
              <label className={LABEL}>Role</label>
              <select value={values.role ?? ""} onChange={(e) => set("role", e.target.value)} className={INPUT}>
                <option value="">Select role</option>
                {(options.productionRoles ?? []).map((o: AnyRow) => (
                  <option key={o.id} value={o.production_role}>{o.production_role}</option>
                ))}
              </select>
            </div>
            <div>
              <label className={LABEL}>Status PKWTT</label>
              <select value={values.status_pkwtt_id ?? ""} onChange={(e) => set("status_pkwtt_id", e.target.value)} className={INPUT}>
                <option value="">Select status</option>
                {(options.statusPkwttList ?? []).map((o: AnyRow) => (
                  <option key={o.id} value={o.id}>{o.pkwtt} - {o.description}</option>
                ))}
              </select>
            </div>
            <div>
              <label className={LABEL}>Educational Level</label>
              <select value={values.educational_level_id ?? ""} onChange={(e) => set("educational_level_id", e.target.value)} className={INPUT}>
                <option value="">Select level</option>
                {(options.educationalLevels ?? []).map((o: AnyRow) => (
                  <option key={o.id} value={o.id}>{o.level} - {o.description}</option>
                ))}
              </select>
            </div>
          </div>
        </>
      )}

      {def.variant === "articles" && (
        <>
          <div>
            <label className={LABEL}>{isEdit ? "Replace Photo" : "Photo"}</label>
            {isEdit && row?.photo_path && !file && (
              <div id="article-edit-preview" className="mb-2">
                <img src={photoSrc(row.photo_path) ?? ""} alt="Current photo" className="h-16 w-16 rounded-lg object-cover" />
              </div>
            )}
            {file && (
              <div className="mb-2">
                <img src={URL.createObjectURL(file)} alt="Preview" className="h-16 w-16 rounded-lg object-cover" />
                <p className="text-xs text-slate-500 mt-1">{file.name}</p>
              </div>
            )}
            <input
              type="file" accept=".jpg,.jpeg,.png"
              onChange={(e) => setFile(e.target.files?.[0] ?? null)}
              className="w-full text-sm text-slate-600 dark:text-slate-300" />
          </div>
          <div>
            <label className={LABEL}>Nama Articles</label>
            <input
              type="text" required value={values.article_name ?? ""} maxLength={150}
              onChange={(e) => set("article_name", e.target.value)}
              className={`${INPUT} uppercase`} style={{ textTransform: "uppercase" }} />
          </div>
          <div>
            <label className={LABEL}>Description</label>
            <input
              type="text" value={values.description ?? ""} maxLength={255}
              onChange={(e) => set("description", e.target.value)}
              className={`${INPUT} uppercase`} style={{ textTransform: "uppercase" }} />
          </div>
        </>
      )}

      {def.variant === "gsd-elements" && (
        <>
          <div>
            <label className={LABEL}>Category *</label>
            <select
              required value={values.gsd_category_id ?? ""} onChange={(e) => set("gsd_category_id", e.target.value)}
              className="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2">
              <option value="">Select category</option>
              {(options.gsdCategories ?? []).map((o: AnyRow) => (
                <option key={o.id} value={o.id}>{o.category_name}</option>
              ))}
            </select>
          </div>
          <div>
            <label className={LABEL}>Element Name *</label>
            <input
              type="text" required maxLength={200} value={values.element_name ?? ""}
              onChange={(e) => set("element_name", e.target.value)}
              className="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
              style={{ textTransform: "uppercase" }} />
          </div>
          <div>
            <label className={LABEL}>Description</label>
            <input
              type="text" maxLength={255} value={values.description ?? ""}
              onChange={(e) => set("description", e.target.value)}
              className="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
              style={{ textTransform: "uppercase" }} />
          </div>
          <div className="grid grid-cols-3 gap-3">
            <div>
              <label className={LABEL}>Code *</label>
              <input
                type="text" required maxLength={50} value={values.code ?? ""}
                onChange={(e) => set("code", e.target.value)}
                className="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
                style={{ textTransform: "uppercase" }} />
            </div>
            <div>
              <label className={LABEL}>TMU *</label>
              <input
                type="number" required step={0.01} min={0} value={values.tmu ?? ""}
                onChange={(e) => set("tmu", e.target.value)}
                className="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
            </div>
            <div>
              <label className={LABEL}>Seconds *</label>
              <input
                type="number" required step={0.01} min={0} value={values.seconds ?? ""}
                onChange={(e) => set("seconds", e.target.value)}
                className="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2" />
            </div>
          </div>
          <div>
            <label className={LABEL}>Motion Sequence</label>
            <input
              type="text" maxLength={100} value={values.motion_sequence ?? ""}
              onChange={(e) => set("motion_sequence", e.target.value)}
              className="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2"
              style={{ textTransform: "uppercase" }} />
          </div>
        </>
      )}

      {def.variant === "processes" && (
        <>
          <div>
            <label className={LABEL}>Process</label>
            <input
              type="text" required value={values.process_name ?? ""}
              onChange={(e) => set("process_name", e.target.value)}
              className={`${INPUT}`} style={{ textTransform: "uppercase" }} />
          </div>
          <div>
            <label className={LABEL}>Process Version</label>
            <input
              type="number" min={1} required={isEdit} value={values.version_number ?? "1"}
              onChange={(e) => set("version_number", e.target.value)}
              className={INPUT} />
          </div>
          <div>
            <label className={LABEL}>GSD Elements</label>
            <div id="gsd-elements-container" className="space-y-2">
              {gsdRows.map((id, i) => (
                <div key={i} className="flex gap-2">
                  <select
                    required value={id}
                    onChange={(e) => {
                      const next = [...gsdRows];
                      next[i] = e.target.value;
                      setGsdRows(next);
                    }}
                    className={INPUT}
                  >
                    <option value="">Select element</option>
                    {(options.gsdElements ?? []).map((o: AnyRow) => (
                      <option key={o.id} value={String(o.id)}>
                        {o.code} — {o.element_name}
                      </option>
                    ))}
                  </select>
                  <button
                    type="button"
                    onClick={() => setGsdRows(gsdRows.filter((_, j) => j !== i))}
                    className="rounded-xl border border-red-200 dark:border-red-600 px-3 text-sm text-red-600 dark:text-red-400"
                  >
                    Remove
                  </button>
                </div>
              ))}
            </div>
            <button
              type="button"
              onClick={() => setGsdRows([...gsdRows, ""])}
              className="mt-2 text-sm font-medium text-indigo-600 hover:text-indigo-800"
            >
              + Add GSD Element
            </button>
          </div>
        </>
      )}

      {(def.variant === "simple" ||
        def.variant === "factories" ||
        def.variant === "departments" ||
        def.variant === "destinations" ||
        def.variant === "production-lines" ||
        def.variant === "mechanics") && (
        <>
          {def.fields.map((f: FieldDef) => (
            <div key={f.name}>
              <label className={LABEL}>
                {f.label}
                {def.variant === "mechanics" && f.required && <span className="text-red-500">*</span>}
              </label>
              {f.type === "textarea" ? (
                <textarea
                  rows={2}
                  required={f.required}
                  maxLength={f.maxLength}
                  placeholder={f.placeholder}
                  value={values[f.name] ?? ""}
                  onChange={(e) => set(f.name, e.target.value)}
                  className={INPUT}
                />
              ) : (
                <input
                  type="text"
                  required={f.required}
                  maxLength={f.maxLength}
                  placeholder={f.placeholder}
                  value={values[f.name] ?? ""}
                  onChange={(e) => set(f.name, e.target.value)}
                  className={`${INPUT}${f.upper ? " uppercase" : ""}`}
                  style={f.upper ? { textTransform: "uppercase" } : undefined}
                />
              )}
            </div>
          ))}
        </>
      )}

      <div className="flex justify-end gap-3 pt-2">
        <button type="button" onClick={onClose} className={BTN_CANCEL}>
          Cancel
        </button>
        <button type="submit" disabled={busy} className={BTN_PRIMARY}>
          {isEdit ? def.submitEdit : def.submitNew}
        </button>
      </div>
    </form>
  );
}

function initialValues(def: MasterDef, row: AnyRow | null): Record<string, string> {
  const v: Record<string, string> = {};
  if (def.variant === "operators") {
    for (const k of [
      "operator_name", "nik_karyawan", "gender", "role", "status_pkwtt_id", "educational_level_id",
      "start_date", "date_of_birth", "factory_id", "department_id", "division_id", "section_id", "line_id",
    ]) {
      const raw = row?.[k];
      v[k] = raw === null || raw === undefined ? "" : String(typeof raw === "string" && raw.includes("T") ? raw.slice(0, 10) : raw);
    }
    return v;
  }
  if (def.variant === "articles") {
    v.article_name = row?.article_name ?? "";
    v.description = row?.description ?? "";
    return v;
  }
  if (def.variant === "gsd-elements") {
    for (const k of ["gsd_category_id", "element_name", "description", "code", "tmu", "seconds", "motion_sequence"]) {
      const raw = row?.[k];
      v[k] = raw === null || raw === undefined ? "" : String(raw);
    }
    return v;
  }
  if (def.variant === "processes") {
    v.process_name = row?.process_name ?? "";
    const versions: AnyRow[] = [...(row?.process_versions ?? [])].sort(
      (a, b) => Number(b.version_number ?? 0) - Number(a.version_number ?? 0)
    );
    v.version_number = String(versions[0]?.version_number ?? 1);
    return v;
  }
  for (const f of def.fields) {
    const raw = row?.[f.name];
    v[f.name] = raw === null || raw === undefined ? "" : String(raw);
  }
  return v;
}

function initialGsdRows(def: MasterDef, row: AnyRow | null, _options: AnyRow): string[] {
  if (def.variant !== "processes" || !row) return [""];
  const versions: AnyRow[] = [...(row.process_versions ?? [])].sort(
    (a, b) => Number(b.version_number ?? 0) - Number(a.version_number ?? 0)
  );
  const top = versions[0];
  const ids = (top?.gsd_elements ?? []).map((e: AnyRow) => String(e.id));
  return ids.length ? ids : [""];
}

/* ------------------------------------------------------------------ */
/* import modal                                                        */
/* ------------------------------------------------------------------ */

function ImportModal({ def, onClose, onSaved }: { def: MasterDef; onClose: () => void; onSaved: (r: ApiResult) => void }) {
  const [file, setFile] = useState<File | null>(null);
  const [busy, setBusy] = useState(false);

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    if (!file) return;
    setBusy(true);
    const fd = new FormData();
    fd.append("file", file);
    const r = await masterFetch(`/${def.slug}/import`, { method: "POST", body: fd });
    setBusy(false);
    onSaved(r);
  };

  return (
    <div className="fixed inset-0 z-50 bg-slate-900/40">
      <div className="flex min-h-full items-center justify-center p-4">
        <div className="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-800 p-6 shadow-xl">
          <FormHeader title={def.modalImport} onClose={onClose} />
          <form onSubmit={submit} className="space-y-4">
            <div>
              <label className={LABEL}>{def.importFileLabel}</label>
              <input
                type="file"
                accept=".xlsx,.xls,.csv"
                onChange={(e) => setFile(e.target.files?.[0] ?? null)}
                className="w-full text-sm text-slate-600 dark:text-slate-300"
              />
              {def.importHelp === "footnote" && def.importFootnote && (
                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{def.importFootnote}</p>
              )}
            </div>
            {def.importHelp === "chips" && (
              <div className="rounded-lg bg-slate-50 dark:bg-slate-700/50 p-3 text-xs text-slate-600 dark:text-slate-400">
                <p className="font-semibold mb-1">Expected Excel columns (row 1 = header):</p>
                {def.importHeaders.map((h) => (
                  <code key={h} className="bg-slate-200 dark:bg-slate-600 px-1 rounded">
                    {h}
                  </code>
                ))}
                {" "}
              </div>
            )}
            <div className="flex justify-end gap-3 pt-2">
              <button type="button" onClick={onClose} className={BTN_CANCEL}>
                Cancel
              </button>
              <button type="submit" disabled={busy} className={BTN_PRIMARY}>
                Import
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
