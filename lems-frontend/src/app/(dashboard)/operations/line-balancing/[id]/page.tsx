"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { useAuthStore } from "@/store/auth-store";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Loader2, ArrowLeft, Save, Plus, X, Play, Pause, RotateCcw, Timer } from "lucide-react";
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, ReferenceLine } from "recharts";

/* ──────── Types ──────── */
interface MachineType { id: number; machine_type: string }
interface OperatorItem { id: number; operator_name: string; employee_number?: string; nik_karyawan?: string }
interface ReportRow {
  id?: number; row_number: number; machine_type_id: number | null; machine_type_name: string | null;
  process: string; name: string; employee_id: number | null; employee_name: string | null;
  joint_process: string | null; operator: number;
  ct_1: number | null; ct_2: number | null; ct_3: number | null; ct_4: number | null; ct_5: number | null;
}
interface Report {
  id: number; factory_id: number; factory_name: string | null; article_id: number;
  article_name: string | null; line_id: number; line_name: string | null;
  report_name: string; target_output_per_hour: number; output_actual: number | null;
  working_hours_per_day: number; allowance_percent: number; update_date: string | null;
  status: string; created_by: number; created_by_name: string | null;
  rows: ReportRow[]; machine_types: MachineType[]; operators_list: OperatorItem[];
}

interface EditRow {
  key: string; id?: number; row_number: number; machine_type_id: string; process: string;
  employee_search: string; employee_id: string; employee_name: string; operator: number;
  ct_1: string; ct_2: string; ct_3: string; ct_4: string; ct_5: string;
}

/* ──────── Helpers ──────── */
const num = (v: string | null | undefined) => { const n = parseFloat(v ?? ""); return isNaN(n) ? 0 : n; };
const fmt = (v: number, d = 2) => (v > 0 ? v.toFixed(d) : "—");
const fmtInt = (v: number) => (v > 0 ? v.toLocaleString() : "—");

/* ──────── Stopwatch Hook ──────── */
function useStopwatch() {
  const [running, setRunning] = useState(false);
  const [elapsed, setElapsed] = useState(0);
  const [laps, setLaps] = useState<number[]>([]);
  const startRef = useRef(0);
  const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const start = useCallback(() => {
    if (running) return;
    if (laps.length >= 5) { reset(); }
    setRunning(true);
    startRef.current = Date.now() - elapsed;
    intervalRef.current = setInterval(() => setElapsed(Date.now() - startRef.current), 10);
  }, [running, elapsed, laps.length]);

  const stop = useCallback(() => {
    if (!running) return;
    setRunning(false);
    if (intervalRef.current) clearInterval(intervalRef.current);
    setElapsed(Date.now() - startRef.current);
  }, [running]);

  const lap = useCallback(() => {
    if (!running) return;
    if (laps.length >= 5) return;
    const now = Date.now() - startRef.current;
    setLaps((prev) => [...prev, now]);
  }, [running, laps.length]);

  const reset = useCallback(() => {
    setRunning(false);
    if (intervalRef.current) clearInterval(intervalRef.current);
    setElapsed(0);
    setLaps([]);
  }, []);

  // Auto-stop after 5 laps
  useEffect(() => { if (laps.length >= 5 && running) { stop(); } }, [laps.length, running, stop]);

  const formatTime = (ms: number) => {
    const m = Math.floor(ms / 60000);
    const s = Math.floor((ms % 60000) / 1000);
    const h = Math.floor((ms % 1000) / 10);
    return `${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}.${String(h).padStart(2, "0")}`;
  };

  const getLapDurations = useCallback(() => {
    return laps.map((ts, i) => { const prev = i > 0 ? laps[i - 1] : 0; return parseFloat(((ts - prev) / 1000).toFixed(2)); });
  }, [laps]);

  return { running, elapsed, laps, start, stop, lap, reset, formatTime, getLapDurations };
}

/* ──────── Employee Autocomplete ──────── */
function EmployeeAC({ value, operators, onSelect }: { value: string; operators: OperatorItem[]; onSelect: (op: OperatorItem) => void }) {
  const [q, setQ] = useState(value);
  const [open, setOpen] = useState(false);
  const [items, setItems] = useState<OperatorItem[]>([]);
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => { setQ(value); }, [value]);

  const search = (v: string) => {
    setQ(v);
    if (timer.current) clearTimeout(timer.current);
    if (v.trim().length < 1) { setItems([]); setOpen(false); return; }
    timer.current = setTimeout(() => {
      const lower = v.toLowerCase();
      const filtered = operators.filter((op) =>
        op.operator_name.toLowerCase().includes(lower) ||
        (op.nik_karyawan && op.nik_karyawan.toLowerCase().includes(lower)) ||
        (op.employee_number && op.employee_number.toLowerCase().includes(lower))
      ).slice(0, 20);
      setItems(filtered);
      setOpen(filtered.length > 0);
    }, 200);
  };

  return (
    <div className="relative">
      <Input value={q} onChange={(e) => search(e.target.value)} onBlur={() => setTimeout(() => setOpen(false), 150)}
        onFocus={() => { if (items.length > 0) setOpen(true); }} placeholder="Search employee..." autoComplete="off"
        className="h-7 text-xs px-2" />
      {open && items.length > 0 && (
        <div className="absolute left-0 top-full mt-1 z-50 w-full max-h-48 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 shadow-lg">
          {items.map((op) => (
            <div key={op.id} className="cursor-pointer px-3 py-2 text-xs hover:bg-indigo-50 dark:hover:bg-slate-600"
              onMouseDown={() => { onSelect(op); setQ(op.operator_name); setOpen(false); }}>
              <div className="font-medium text-slate-800 dark:text-slate-200">{op.operator_name}</div>
              <div className="text-[10px] text-slate-500">{op.nik_karyawan || op.employee_number || ""}</div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

/* ──────── Main Page ──────── */
export default function LineBalancingEditPage() {
  const params = useParams();
  const router = useRouter();
  const { token } = useAuthStore();
  const id = params.id as string;

  const [report, setReport] = useState<Report | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [banner, setBanner] = useState<{ kind: "success" | "error"; text: string } | null>(null);

  // Settings form state
  const [targetOutput, setTargetOutput] = useState(0);
  const [outputActual, setOutputActual] = useState(0);
  const [workingHours, setWorkingHours] = useState(8);
  const [allowance, setAllowance] = useState(15);
  const [updateDate, setUpdateDate] = useState("");

  // Rows state
  const [rows, setRows] = useState<EditRow[]>([]);
  const rowKeyRef = useRef(0);
  const nextKey = () => `row_${++rowKeyRef.current}`;

  // Stopwatch
  const sw = useStopwatch();
  const [swTarget, setSwTarget] = useState<string>("");

  // Computed values
  const allowanceMultiplier = 1 + allowance / 100;
  const taktTime = targetOutput > 0 ? 3600 / targetOutput : 0;
  const workingSeconds = workingHours * 3600;

  // Fetch report
  const fetchReport = useCallback(async () => {
    setLoading(true);
    try {
      const res = await fetch(`/api/line-balancing/${id}`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      });
      if (!res.ok) { setBanner({ kind: "error", text: "Report not found" }); setLoading(false); return; }
      const body = await res.json();
      const r: Report = body.data ?? body;
      setReport(r);
      setTargetOutput(r.target_output_per_hour ?? 0);
      setOutputActual(r.output_actual ?? 0);
      setWorkingHours(r.working_hours_per_day ?? 8);
      setAllowance(r.allowance_percent ?? 15);
      setUpdateDate(r.update_date ?? "");
      setRows((r.rows ?? []).map((row) => ({
        key: nextKey(), id: row.id, row_number: row.row_number,
        machine_type_id: row.machine_type_id != null ? String(row.machine_type_id) : "none",
        process: row.process ?? "", employee_search: row.employee_name ?? "",
        employee_id: row.employee_id != null ? String(row.employee_id) : "",
        employee_name: row.employee_name ?? "", operator: row.operator ?? 1,
        ct_1: row.ct_1 != null ? String(row.ct_1) : "", ct_2: row.ct_2 != null ? String(row.ct_2) : "",
        ct_3: row.ct_3 != null ? String(row.ct_3) : "", ct_4: row.ct_4 != null ? String(row.ct_4) : "",
        ct_5: row.ct_5 != null ? String(row.ct_5) : "",
      })));
    } catch { setBanner({ kind: "error", text: "Failed to load report" }); }
    setLoading(false);
  }, [id, token]);

  useEffect(() => { fetchReport(); }, [fetchReport]);

  // ── Row CRUD ──
  const addRow = () => {
    setRows((prev) => [...prev, {
      key: nextKey(), row_number: prev.length + 1, machine_type_id: "none", process: "",
      employee_search: "", employee_id: "", employee_name: "", operator: 1,
      ct_1: "", ct_2: "", ct_3: "", ct_4: "", ct_5: "",
    }]);
  };
  const removeRow = (idx: number) => {
    setRows((prev) => prev.filter((_, i) => i !== idx).map((r, i) => ({ ...r, row_number: i + 1 })));
  };
  const updateRow = (idx: number, field: keyof EditRow, value: string | number) => {
    setRows((prev) => prev.map((r, i) => (i === idx ? { ...r, [field]: value } : r)));
  };

  // ── Stopwatch → CT cells ──
  const applyStopwatch = () => {
    if (!swTarget) return;
    const idx = parseInt(swTarget);
    if (isNaN(idx) || idx < 0 || idx >= rows.length) return;
    const durations = sw.getLapDurations();
    if (durations.length === 0) return;
    setRows((prev) => prev.map((r, i) => {
      if (i !== idx) return r;
      return {
        ...r,
        ct_1: durations[0] != null ? String(durations[0]) : r.ct_1,
        ct_2: durations[1] != null ? String(durations[1]) : r.ct_2,
        ct_3: durations[2] != null ? String(durations[2]) : r.ct_3,
        ct_4: durations[3] != null ? String(durations[4]) : r.ct_4,
        ct_5: durations[4] != null ? String(durations[4]) : r.ct_5,
      };
    }));
  };
  useEffect(() => { if (sw.laps.length >= 5 && !sw.running) { applyStopwatch(); } }, [sw.laps.length, sw.running]); // eslint-disable-line

  // ── Calculations ──
  const calcRow = (r: EditRow) => {
    const cts = [num(r.ct_1), num(r.ct_2), num(r.ct_3), num(r.ct_4), num(r.ct_5)].filter((v) => v > 0);
    const avgCt = cts.length > 0 ? cts.reduce((a, b) => a + b, 0) / cts.length : 0;
    const avgCtAllow = avgCt * allowanceMultiplier;
    const avgCtOpr = avgCt;
    const outputHr = avgCtAllow > 0 ? 3600 / avgCtAllow : 0;
    const outputProcHr = outputHr;
    const reqOpr = taktTime > 0 ? avgCtOpr / taktTime : 0;
    const potentialOutput = outputProcHr * workingHours;
    return { avgCt, avgCtAllow, avgCtOpr, outputHr, outputProcHr, reqOpr, potentialOutput };
  };

  const { summary, chartData, totalRow } = useMemo(() => {
    let totalCtAllow = 0, totalManpower = 0, maxCtAllow = 0;
    let totalOutputProcHr = 0, totalReqOpr = 0, totalPotential = 0;
    const cd: { label: string; avgCt: number; avgCtAllow: number }[] = [];

    rows.forEach((r, i) => {
      const c = calcRow(r);
      totalCtAllow += c.avgCtAllow;
      totalManpower += r.operator;
      totalOutputProcHr += c.outputProcHr;
      totalReqOpr += c.reqOpr;
      totalPotential += c.potentialOutput;
      if (c.avgCtAllow > maxCtAllow) maxCtAllow = c.avgCtAllow;
      cd.push({ label: r.process || `Row ${i + 1}`, avgCt: c.avgCt, avgCtAllow: c.avgCtAllow });
    });

    const avgStdTime = totalManpower > 0 ? totalCtAllow / totalManpower : 0;
    const targetPerPCS = totalCtAllow > 0 ? Math.round(workingSeconds / totalCtAllow) : 0;
    const targetLineDay = totalManpower * targetPerPCS;
    const targetLineHour = targetLineDay > 0 ? targetLineDay / workingHours : 0;
    const maxBasedCT = maxCtAllow > 0 ? 3600 / maxCtAllow : 0;
    const subTotalOp = totalManpower;
    const productivity = subTotalOp > 0 ? targetOutput / subTotalOp : 0;

    return {
      summary: { totalCtAllow, totalManpower, avgStdTime, targetPerPCS, targetLineDay, targetLineHour, maxBasedCT, subTotalOp, productivity },
      chartData: cd,
      totalRow: { operator: totalManpower, avgCtAllow: totalCtAllow, outputProcHr: totalOutputProcHr, reqOpr: totalReqOpr, potential: totalPotential },
    };
  }, [rows, allowanceMultiplier, taktTime, workingHours, workingSeconds, targetOutput]);

  // ── Save settings ──
  const saveSettings = async () => {
    try {
      const res = await fetch(`/api/line-balancing/${id}`, {
        method: "PUT", headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
        body: JSON.stringify({ target_output_per_hour: targetOutput, output_actual: outputActual, working_hours_per_day: workingHours, allowance_percent: allowance, update_date: updateDate || null }),
      });
      if (res.ok) setBanner({ kind: "success", text: "Settings updated" });
      else setBanner({ kind: "error", text: "Failed to update settings" });
    } catch { setBanner({ kind: "error", text: "Network error" }); }
  };

  // ── Save rows ──
  const saveRows = async () => {
    setSaving(true);
    try {
      const payload = {
        target_output_per_hour: targetOutput, output_actual: outputActual,
        rows: rows.map((r, i) => ({
          row_number: i + 1, machine_type_id: r.machine_type_id && r.machine_type_id !== "none" ? parseInt(r.machine_type_id) : null,
          process: r.process || null, name: r.employee_name || null,
          employee_id: r.employee_id ? parseInt(r.employee_id) : null,
          operator: r.operator, ct_1: num(r.ct_1) || null, ct_2: num(r.ct_2) || null,
          ct_3: num(r.ct_3) || null, ct_4: num(r.ct_4) || null, ct_5: num(r.ct_5) || null,
        })),
      };
      const res = await fetch(`/api/line-balancing/${id}/rows`, {
        method: "POST", headers: { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) },
        body: JSON.stringify(payload),
      });
      if (res.ok) setBanner({ kind: "success", text: "Report saved successfully" });
      else { const b = await res.json().catch(() => null); setBanner({ kind: "error", text: b?.message || "Failed to save" }); }
    } catch { setBanner({ kind: "error", text: "Network error" }); }
    setSaving(false);
  };

  // ── Render ──
  if (loading) return <div className="flex items-center justify-center h-96"><Loader2 className="h-8 w-8 animate-spin text-indigo-600" /></div>;
  if (!report) return <div className="py-8 px-4"><p className="text-red-500">Report not found.</p></div>;

  const targetPerDay = targetOutput > 0 ? targetOutput * workingHours : 0;

  return (
    <div className="space-y-6">
      {/* Banner */}
      {banner && (
        <div className={`rounded-xl border px-4 py-3 text-sm ${banner.kind === "success" ? "border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300" : "border-red-200 bg-red-50 text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300"}`}>
          {banner.text}
          <button onClick={() => setBanner(null)} className="float-right font-bold">×</button>
        </div>
      )}

      {/* Breadcrumb */}
      <div className="flex items-center gap-2 text-sm">
        <button onClick={() => router.push("/operations")} className="text-teal-600 hover:text-teal-700">Lean Operations</button>
        <span className="text-slate-300">/</span>
        <button onClick={() => router.push("/operations/line-balancing")} className="text-teal-600 hover:text-teal-700">Line Balancing</button>
        <span className="text-slate-300">/</span>
        <span className="font-semibold text-slate-800 dark:text-slate-200">{report.report_name}</span>
      </div>

      {/* Header Banner */}
      <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm overflow-hidden">
        <div className="bg-slate-950 p-6 text-white">
          <div className="flex items-start justify-between">
            <div>
              <div className="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300">
                <span className="text-lg">⇄</span> Line Balancing Report
              </div>
              <h1 className="text-2xl font-semibold tracking-tight">{report.report_name}</h1>
            </div>
            <div className="flex items-center gap-3">
              <button onClick={async () => {
                try {
                  const token = useAuthStore.getState().token;
                  const res = await fetch(`/api/line-balancing/${report.id}/export`, {
                    headers: { Authorization: `Bearer ${token}` },
                  });
                  if (!res.ok) throw new Error("Export failed");
                  const blob = await res.blob();
                  const disp = res.headers.get("content-disposition") ?? "";
                  const m = disp.match(/filename="?([^";]+)"?/i);
                  const url = URL.createObjectURL(blob);
                  const a = document.createElement("a");
                  a.href = url;
                  a.download = m ? m[1] : `LineBalancing_${report.id}.xlsx`;
                  document.body.appendChild(a);
                  a.click();
                  a.remove();
                  URL.revokeObjectURL(url);
                } catch { alert("Export failed. Please try again."); }
              }}
                className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
                📊 Export XLSX
              </button>
              <div className="flex h-16 w-16 items-center justify-center rounded-xl bg-slate-800 ring-2 ring-white/20">
                <span className="text-2xl text-slate-500">📷</span>
              </div>
            </div>
          </div>
        </div>
        <div className="grid grid-cols-2 gap-4 p-6 sm:grid-cols-5">
          {[
            { label: "Factory", value: report.factory_name },
            { label: "Line", value: report.line_name },
            { label: "Article", value: report.article_name },
            { label: "Update Date", value: report.update_date },
            { label: "Created By", value: report.created_by_name },
          ].map((item) => (
            <div key={item.label}>
              <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">{item.label}</span>
              <p className="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{item.value ?? "—"}</p>
            </div>
          ))}
        </div>
      </div>

      {/* Settings */}
      <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-sm">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <Label className="text-xs font-semibold uppercase tracking-wider text-slate-500">Target Output / Hours (PPH)</Label>
            <Input type="number" value={targetOutput} onChange={(e) => setTargetOutput(parseInt(e.target.value) || 0)} min={0} className="mt-1" />
          </div>
          <div>
            <Label className="text-xs font-semibold uppercase tracking-wider text-slate-500">Output Actual (PCS/Hour)</Label>
            <Input type="number" value={outputActual} onChange={(e) => setOutputActual(parseInt(e.target.value) || 0)} min={0} className="mt-1" />
          </div>
          <div>
            <Label className="text-xs font-semibold uppercase tracking-wider text-slate-500">Working Hours / Day</Label>
            <Input type="number" value={workingHours} onChange={(e) => setWorkingHours(parseFloat(e.target.value) || 8)} min={0} max={24} step={0.5} className="mt-1" />
          </div>
          <div>
            <Label className="text-xs font-semibold uppercase tracking-wider text-slate-500">Allowance (%)</Label>
            <Input type="number" value={allowance} onChange={(e) => setAllowance(parseFloat(e.target.value) || 15)} min={0} max={100} step={0.5} className="mt-1" />
          </div>
        </div>
        <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <Label className="text-xs font-semibold uppercase tracking-wider text-slate-500">Update Date</Label>
            <Input type="date" value={updateDate} onChange={(e) => setUpdateDate(e.target.value)} className="mt-1" />
          </div>
          <div>
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Target Output / Day</span>
            <p className="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{fmtInt(targetPerDay)}</p>
          </div>
          <div>
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Takt Time (seconds)</span>
            <p className="mt-1 text-lg font-semibold text-emerald-600 dark:text-emerald-400">{taktTime > 0 ? taktTime.toFixed(2) : "—"}</p>
          </div>
          <div>
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">PPH (Productivity)</span>
            <p className="mt-1 text-lg font-semibold text-indigo-600 dark:text-indigo-400">{summary.subTotalOp > 0 ? (targetOutput / summary.subTotalOp).toFixed(2) : "—"}</p>
          </div>
        </div>
        <div className="mt-4 flex justify-end">
          <Button onClick={saveSettings} className="bg-indigo-600 hover:bg-indigo-500">Update Settings</Button>
        </div>
      </div>

      {/* Stopwatch */}
      <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50 p-4">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-100">Cycle Time Stopwatch</h3>
            <p className="text-xs text-slate-500 dark:text-slate-400">Start → Lap 5 times (fills CT 1–5) → Stop to apply.</p>
          </div>
          <div className="text-right">
            <p className="text-3xl font-mono font-bold text-slate-900 dark:text-slate-100">{sw.formatTime(sw.elapsed)}</p>
            <p className="text-sm text-slate-500 dark:text-slate-400">
              {sw.laps.map((ts, i) => { const prev = i > 0 ? sw.laps[i - 1] : 0; return `CT${i + 1}: ${((ts - prev) / 1000).toFixed(2)}s`; }).join(" | ")}
            </p>
          </div>
        </div>
        <div className="mt-3 flex items-center gap-2 flex-wrap">
          <Button onClick={sw.start} disabled={sw.running} className="bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50"><Play className="h-4 w-4 mr-1" /> Start</Button>
          <Button onClick={sw.stop} disabled={!sw.running} className="bg-red-600 hover:bg-red-500 disabled:opacity-50"><Pause className="h-4 w-4 mr-1" /> Stop</Button>
          <Button onClick={sw.lap} disabled={!sw.running || sw.laps.length >= 5} className="bg-amber-600 hover:bg-amber-500 disabled:opacity-50"><Timer className="h-4 w-4 mr-1" /> Lap</Button>
          <Button onClick={sw.reset} variant="outline"><RotateCcw className="h-4 w-4 mr-1" /> Reset</Button>
          <Select value={swTarget} onValueChange={setSwTarget}>
            <SelectTrigger className="ml-4 w-60"><SelectValue placeholder="Select target row..." /></SelectTrigger>
            <SelectContent>
              {rows.map((r, i) => (<SelectItem key={r.key} value={String(i)}>Row {i + 1} — {r.process || "No process"}</SelectItem>))}
            </SelectContent>
          </Select>
          <span className="ml-2 text-xs font-semibold text-slate-500">{sw.laps.length} / 5 laps</span>
        </div>
      </div>

      {/* Report Table */}
      <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm overflow-hidden">
        <div className="p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Operator / Process Table</h2>
            <Button onClick={addRow} className="bg-emerald-600 hover:bg-emerald-500"><Plus className="h-4 w-4 mr-1" /> Add Row</Button>
          </div>
          <div className="overflow-x-auto">
            <table className="divide-y divide-slate-200 dark:divide-slate-700 text-left text-sm" style={{ minWidth: 1400 }}>
              <thead className="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                  <th className="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-10 text-center">No</th>
                  <th className="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-32">Machine</th>
                  <th className="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-36">Process</th>
                  <th className="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-44">Employee (Name)</th>
                  <th className="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-16 text-center">Opr</th>
                  {[1, 2, 3, 4, 5].map((n) => (
                    <th key={n} className="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-20 text-center bg-blue-50 dark:bg-blue-900/20">CT {n}</th>
                  ))}
                  <th className="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-24 text-center" title="Average CT">Avg CT</th>
                  <th className="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-24 text-center" title="Avg CT × (1 + Allowance%)">Avg CT +Allow.</th>
                  <th className="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-24 text-center" title="Avg CT per process">Avg CT/Opr</th>
                  <th className="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-24 text-center" title="3600 ÷ (Avg CT + Allowance)">Output/Hr</th>
                  <th className="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-28 text-center" title="Output Process/Hour">Output Proc/Hr</th>
                  <th className="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-20 text-center" title="Avg CT ÷ Takt Time">Req Opr</th>
                  <th className="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300 w-28 text-center" title="Output Proc/Hr × Working Hours">Potential Output</th>
                  <th className="px-3 py-3 w-12"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200 dark:divide-slate-700">
                {rows.map((r, idx) => {
                  const c = calcRow(r);
                  return (
                    <tr key={r.key}>
                      <td className="px-3 py-2 text-center"><span className="text-sm font-medium text-slate-500">{idx + 1}</span></td>
                      <td className="px-3 py-2">
                        <Select value={r.machine_type_id} onValueChange={(v) => updateRow(idx, "machine_type_id", v)}>
                          <SelectTrigger className="h-7 text-xs"><SelectValue placeholder="—" /></SelectTrigger>
                          <SelectContent>
                            <SelectItem value="none">—</SelectItem>
                            {(report.machine_types ?? []).map((mt) => (<SelectItem key={mt.id} value={String(mt.id)}>{mt.machine_type}</SelectItem>))}
                          </SelectContent>
                        </Select>
                      </td>
                      <td className="px-3 py-2"><Input value={r.process} onChange={(e) => updateRow(idx, "process", e.target.value)} className="h-7 text-xs px-2" /></td>
                      <td className="px-3 py-2">
                        <EmployeeAC value={r.employee_search} operators={report.operators_list ?? []}
                          onSelect={(op) => { updateRow(idx, "employee_search", op.operator_name); updateRow(idx, "employee_id", String(op.id)); updateRow(idx, "employee_name", op.operator_name); }} />
                      </td>
                      <td className="px-3 py-2"><Input type="number" value={r.operator} onChange={(e) => updateRow(idx, "operator", parseInt(e.target.value) || 1)} min={1} className="h-7 text-xs px-2 text-center" /></td>
                      {([1, 2, 3, 4, 5] as const).map((n) => (
                        <td key={n} className="px-3 py-2 bg-blue-50/50 dark:bg-blue-900/10">
                          <Input type="number" value={r[`ct_${n}` as keyof EditRow] as string} onChange={(e) => updateRow(idx, `ct_${n}` as keyof EditRow, e.target.value)} min={0} step={0.01} className="h-7 text-xs px-2 text-center" />
                        </td>
                      ))}
                      <td className="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300">{fmt(c.avgCt)}</td>
                      <td className="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300">{fmt(c.avgCtAllow)}</td>
                      <td className="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300">{fmt(c.avgCtOpr)}</td>
                      <td className="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300">{fmt(c.outputHr, 1)}</td>
                      <td className="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300">{fmt(c.outputProcHr, 1)}</td>
                      <td className="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300">{fmt(c.reqOpr)}</td>
                      <td className="px-3 py-2 text-xs text-center text-slate-600 dark:text-slate-300">{fmtInt(c.potentialOutput)}</td>
                      <td className="px-3 py-2 text-center"><button onClick={() => removeRow(idx)} className="text-red-400 hover:text-red-600"><X className="h-4 w-4" /></button></td>
                    </tr>
                  );
                })}
                {/* Total Row */}
                <tr className="bg-slate-100 dark:bg-slate-700 font-semibold">
                  <td className="px-3 py-2 text-center text-xs text-slate-700 dark:text-slate-200" colSpan={3}>TOTAL</td>
                  <td className="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200"></td>
                  <td className="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200">{totalRow.operator || "—"}</td>
                  <td className="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200" colSpan={5}></td>
                  <td className="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200">{fmt(totalRow.avgCtAllow)}</td>
                  <td className="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200"></td>
                  <td className="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200"></td>
                  <td className="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200">{fmt(totalRow.outputProcHr, 1)}</td>
                  <td className="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200">{fmt(totalRow.reqOpr)}</td>
                  <td className="px-3 py-2 text-xs text-center text-slate-700 dark:text-slate-200">{fmtInt(totalRow.potential)}</td>
                  <td className="px-3 py-2"></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div className="mt-4 flex justify-end">
            <Button onClick={saveRows} disabled={saving} className="bg-indigo-600 hover:bg-indigo-500">
              {saving ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <Save className="h-4 w-4 mr-2" />} Save Report
            </Button>
          </div>
        </div>
      </div>

      {/* Summary Metrics */}
      <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-sm">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Summary Metrics</h2>
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
          {[
            { label: "Total Cycle Time", value: summary.totalCtAllow > 0 ? `${summary.totalCtAllow.toFixed(2)} s` : "—" },
            { label: "Total Manpower", value: String(summary.totalManpower || "—") },
            { label: "Average Standard Time", value: summary.avgStdTime > 0 ? `${summary.avgStdTime.toFixed(2)} s` : "—" },
            { label: "Working Time", value: `${workingSeconds.toLocaleString()} s` },
            { label: "Target per PCS", value: fmtInt(summary.targetPerPCS) },
            { label: "Target Line / Day", value: fmtInt(summary.targetLineDay) },
            { label: "Target Line / Hour", value: summary.targetLineHour > 0 ? summary.targetLineHour.toFixed(1) : "—" },
            { label: "Max Based on CT", value: summary.maxBasedCT > 0 ? summary.maxBasedCT.toFixed(1) : "—" },
            { label: "Output Actual", value: outputActual > 0 ? outputActual.toLocaleString() : "—" },
            { label: "Sub Total Operator", value: String(summary.subTotalOp || "—"), highlight: "emerald" },
            { label: "Productivity (PPH)", value: summary.productivity > 0 ? summary.productivity.toFixed(2) : "—", highlight: "indigo" },
          ].map((item) => (
            <div key={item.label} className="rounded-xl bg-slate-50 dark:bg-slate-700/50 p-4">
              <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">{item.label}</span>
              <p className={`mt-1 text-xl font-bold ${item.highlight === "emerald" ? "text-emerald-600 dark:text-emerald-400" : item.highlight === "indigo" ? "text-indigo-600 dark:text-indigo-400" : "text-slate-900 dark:text-slate-100"}`}>{item.value}</p>
            </div>
          ))}
        </div>
      </div>

      {/* Chart */}
      <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-sm">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Cycle Time Chart</h2>
        <div style={{ height: 400 }}>
          {chartData.length > 0 ? (
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                <YAxis label={{ value: "Seconds", angle: -90, position: "insideLeft" }} />
                <Tooltip />
                <Legend />
                <Bar dataKey="avgCt" name="Average CT (s)" fill="#10b981" />
                <Bar dataKey="avgCtAllow" name={`Avg CT +${allowance}% (s)`} fill="#3b82f6" />
                {taktTime > 0 && <ReferenceLine y={taktTime} stroke="#ef4444" strokeDasharray="6 6" label={{ value: `Takt: ${taktTime.toFixed(1)}s`, position: "right", fill: "#ef4444", fontSize: 12 } as any} />}
              </BarChart>
            </ResponsiveContainer>
          ) : (
            <div className="flex items-center justify-center h-full text-slate-400">No data to display</div>
          )}
        </div>
      </div>

      {/* Back button */}
      <div className="flex justify-start">
        <Button variant="outline" onClick={() => router.push("/operations/line-balancing")}><ArrowLeft className="h-4 w-4 mr-1" /> Back to Line Balancing</Button>
      </div>
    </div>
  );
}