"use client";

import { useEffect, useMemo, useState } from "react";
import {
  Activity,
  ArrowRight,
  BarChart3,
  CheckCircle2,
  Clock3,
  Factory,
  Gauge,
  Hammer,
  Layers3,
  Plus,
  RotateCcw,
  Save,
  ShieldCheck,
  Sparkles,
  Trash2,
  Users,
  Wrench,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { cn } from "@/lib/utils";

type ModuleKey = "cycle" | "breakdown" | "balance" | "kaizen" | "people" | "tpm" | "materials" | "vsm";

type CycleRecord = { id: number; operation: string; observations: number[] };
type BreakdownRecord = { id: number; activity: string; minutes: number; category: string };
type KaizenRecord = { id: number; title: string; owner: string; impact: number; status: "Proposed" | "In progress" | "Completed" };
type TpmRecord = { id: number; equipment: string; nextDue: string; status: "Ready" | "Due soon" | "Overdue" };
type MaterialRecord = { id: number; code: string; name: string; usage: string };

const storageKey = "lean-enterprise-operations";
const moduleItems: { key: ModuleKey; label: string; eyebrow: string; icon: React.ElementType; tone: string }[] = [
  { key: "cycle", label: "Cycle Time", eyebrow: "Observe", icon: Clock3, tone: "text-cyan-700 bg-cyan-50" },
  { key: "breakdown", label: "Breakdown", eyebrow: "Diagnose", icon: Activity, tone: "text-amber-700 bg-amber-50" },
  { key: "balance", label: "Line Balance", eyebrow: "Optimize", icon: Gauge, tone: "text-emerald-700 bg-emerald-50" },
  { key: "kaizen", label: "Kaizen", eyebrow: "Improve", icon: Sparkles, tone: "text-rose-700 bg-rose-50" },
  { key: "people", label: "Skills & OSCP", eyebrow: "Develop", icon: Users, tone: "text-violet-700 bg-violet-50" },
  { key: "tpm", label: "TPM", eyebrow: "Maintain", icon: Wrench, tone: "text-orange-700 bg-orange-50" },
  { key: "materials", label: "Materials", eyebrow: "Control", icon: Layers3, tone: "text-blue-700 bg-blue-50" },
  { key: "vsm", label: "VSM", eyebrow: "See flow", icon: BarChart3, tone: "text-teal-700 bg-teal-50" },
];

const initialState = {
  cycle: [
    { id: 1, operation: "Collar attach", observations: [42, 44, 41, 43, 45] },
    { id: 2, operation: "Sleeve hem", observations: [28, 31, 29, 30, 28] },
  ] as CycleRecord[],
  breakdown: [
    { id: 1, activity: "Thread break correction", minutes: 12, category: "Machine" },
    { id: 2, activity: "Material replenishment", minutes: 8, category: "Material" },
  ] as BreakdownRecord[],
  kaizen: [
    { id: 1, title: "Reposition thread rack at station 04", owner: "Siti Rahayu", impact: 18, status: "In progress" },
    { id: 2, title: "Standardize collar bundle height", owner: "Budi Santoso", impact: 9, status: "Proposed" },
  ] as KaizenRecord[],
  tpm: [
    { id: 1, equipment: "Juki DDL-9000B / M-04", nextDue: "2026-09-12", status: "Due soon" },
    { id: 2, equipment: "Brother S-7200C / S-11", nextDue: "2026-09-26", status: "Ready" },
  ] as TpmRecord[],
  materials: [
    { id: 1, code: "MAT-001", name: "Cotton jersey 180 gsm", usage: "Polo Shirt Basic" },
    { id: 2, code: "MAT-014", name: "Poly core-spun thread", usage: "Collar attach" },
  ] as MaterialRecord[],
};

function mean(values: number[]) {
  return values.length ? values.reduce((total, value) => total + value, 0) / values.length : 0;
}

function stdDev(values: number[]) {
  if (!values.length) return 0;
  const average = mean(values);
  return Math.sqrt(mean(values.map((value) => (value - average) ** 2)));
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <label className="grid gap-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
      {label}
      {children}
    </label>
  );
}

function SectionTitle({ icon: Icon, title, detail }: { icon: React.ElementType; title: string; detail: string }) {
  return (
    <div className="flex items-start gap-3">
      <div className="mt-0.5 rounded-lg bg-slate-900 p-2 text-white"><Icon className="h-4 w-4" /></div>
      <div><h2 className="text-lg font-semibold text-slate-950">{title}</h2><p className="text-sm text-slate-500">{detail}</p></div>
    </div>
  );
}

export default function OperationsPage() {
  const [active, setActive] = useState<ModuleKey>("cycle");
  const [data, setData] = useState(initialState);
  const [hydrated, setHydrated] = useState(false);
  const [cycleOperation, setCycleOperation] = useState("");
  const [cycleValues, setCycleValues] = useState("");
  const [breakdownActivity, setBreakdownActivity] = useState("");
  const [breakdownMinutes, setBreakdownMinutes] = useState("");
  const [kaizenTitle, setKaizenTitle] = useState("");
  const [kaizenOwner, setKaizenOwner] = useState("");
  const [kaizenImpact, setKaizenImpact] = useState("");
  const [equipment, setEquipment] = useState("");
  const [nextDue, setNextDue] = useState("");
  const [materialCode, setMaterialCode] = useState("");
  const [materialName, setMaterialName] = useState("");
  const [materialUsage, setMaterialUsage] = useState("");

  useEffect(() => {
    const saved = window.localStorage.getItem(storageKey);
    if (saved) setData({ ...initialState, ...JSON.parse(saved) });
    setHydrated(true);
  }, []);

  useEffect(() => {
    if (hydrated) window.localStorage.setItem(storageKey, JSON.stringify(data));
  }, [data, hydrated]);

  const cycleSummary = useMemo(() => {
    const all = data.cycle.flatMap((record) => record.observations);
    return { average: mean(all), samples: all.length, operations: data.cycle.length };
  }, [data.cycle]);
  const breakdownTotal = data.breakdown.reduce((total, record) => total + record.minutes, 0);
  const addCycle = () => {
    const observations = cycleValues.split(",").map(Number).filter((value) => Number.isFinite(value) && value > 0);
    if (!cycleOperation.trim() || !observations.length) return;
    setData((current) => ({ ...current, cycle: [...current.cycle, { id: Date.now(), operation: cycleOperation.trim(), observations }] }));
    setCycleOperation(""); setCycleValues("");
  };
  const addBreakdown = () => {
    const minutes = Number(breakdownMinutes);
    if (!breakdownActivity.trim() || !minutes) return;
    setData((current) => ({ ...current, breakdown: [...current.breakdown, { id: Date.now(), activity: breakdownActivity.trim(), minutes, category: "New observation" }] }));
    setBreakdownActivity(""); setBreakdownMinutes("");
  };
  const addKaizen = () => {
    const impact = Number(kaizenImpact);
    if (!kaizenTitle.trim() || !kaizenOwner.trim() || !impact) return;
    setData((current) => ({ ...current, kaizen: [...current.kaizen, { id: Date.now(), title: kaizenTitle.trim(), owner: kaizenOwner.trim(), impact, status: "Proposed" }] }));
    setKaizenTitle(""); setKaizenOwner(""); setKaizenImpact("");
  };
  const addTpm = () => {
    if (!equipment.trim() || !nextDue) return;
    setData((current) => ({ ...current, tpm: [...current.tpm, { id: Date.now(), equipment: equipment.trim(), nextDue, status: "Ready" }] }));
    setEquipment(""); setNextDue("");
  };
  const addMaterial = () => {
    if (!materialCode.trim() || !materialName.trim() || !materialUsage.trim()) return;
    setData((current) => ({ ...current, materials: [...current.materials, { id: Date.now(), code: materialCode.trim(), name: materialName.trim(), usage: materialUsage.trim() }] }));
    setMaterialCode(""); setMaterialName(""); setMaterialUsage("");
  };
  const remove = (key: "cycle" | "breakdown" | "kaizen" | "tpm" | "materials", id: number) => setData((current) => ({ ...current, [key]: current[key].filter((item) => item.id !== id) }));
  const reset = () => { setData(initialState); window.localStorage.removeItem(storageKey); };

  return (
    <div className="min-h-[calc(100vh-7rem)] space-y-6">
      <section className="relative overflow-hidden rounded-2xl bg-slate-950 px-6 py-7 text-white shadow-xl md:px-8">
        <div className="absolute right-0 top-0 h-full w-2/5 bg-[radial-gradient(circle_at_center,rgba(20,184,166,0.32),transparent_65%)]" />
        <div className="relative flex flex-col justify-between gap-6 md:flex-row md:items-end">
          <div className="max-w-2xl">
            <div className="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-teal-300"><Factory className="h-4 w-4" /> Operations workspace</div>
            <h1 className="text-3xl font-semibold tracking-tight md:text-4xl">From observation to improvement.</h1>
            <p className="mt-3 max-w-xl text-sm leading-6 text-slate-300">Run the operational work plan in one place. Capture cycle time, find loss, balance the line, and turn findings into owned improvements.</p>
          </div>
          <Button variant="outline" className="relative w-fit border-slate-700 bg-slate-900/60 text-slate-100 hover:bg-slate-800" onClick={reset}><RotateCcw className="mr-2 h-4 w-4" /> Reset workspace</Button>
        </div>
      </section>

      <div className="grid gap-3 sm:grid-cols-3">
        <div className="rounded-xl border border-slate-200 bg-white p-4"><p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Cycle average</p><p className="mt-2 text-2xl font-semibold text-slate-950">{cycleSummary.average.toFixed(1)} <span className="text-sm font-normal text-slate-500">sec</span></p><p className="mt-1 text-xs text-slate-500">{cycleSummary.samples} observations across {cycleSummary.operations} operations</p></div>
        <div className="rounded-xl border border-slate-200 bg-white p-4"><p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Breakdown loss</p><p className="mt-2 text-2xl font-semibold text-slate-950">{breakdownTotal} <span className="text-sm font-normal text-slate-500">min</span></p><p className="mt-1 text-xs text-slate-500">Recorded downtime to investigate</p></div>
        <div className="rounded-xl border border-slate-200 bg-white p-4"><p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Open improvements</p><p className="mt-2 text-2xl font-semibold text-slate-950">{data.kaizen.filter((item) => item.status !== "Completed").length}</p><p className="mt-1 text-xs text-slate-500">Kaizen actions requiring ownership</p></div>
      </div>

      <div className="grid gap-6 lg:grid-cols-[240px_1fr]">
        <aside className="h-fit rounded-2xl border border-slate-200 bg-white p-2 lg:sticky lg:top-6">
          <p className="px-3 pb-2 pt-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Work plan modules</p>
          {moduleItems.map(({ key, label, eyebrow, icon: Icon, tone }) => (
            <button key={key} onClick={() => setActive(key)} className={cn("flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition-colors", active === key ? "bg-slate-950 text-white" : "text-slate-600 hover:bg-slate-50")}>
              <span className={cn("rounded-lg p-2", active === key ? "bg-white/10 text-teal-300" : tone)}><Icon className="h-4 w-4" /></span>
              <span className="min-w-0"><span className="block text-sm font-semibold">{label}</span><span className={cn("block text-[11px]", active === key ? "text-slate-400" : "text-slate-400")}>{eyebrow}</span></span>
              {active === key && <ArrowRight className="ml-auto h-4 w-4 text-teal-300" />}
            </button>
          ))}
        </aside>

        <main className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-7">
          {active === "cycle" && <CycleModule data={data.cycle} operation={cycleOperation} values={cycleValues} setOperation={setCycleOperation} setValues={setCycleValues} add={addCycle} remove={(id) => remove("cycle", id)} />}
          {active === "breakdown" && <BreakdownModule data={data.breakdown} activity={breakdownActivity} minutes={breakdownMinutes} setActivity={setBreakdownActivity} setMinutes={setBreakdownMinutes} add={addBreakdown} remove={(id) => remove("breakdown", id)} total={breakdownTotal} />}
          {active === "balance" && <BalanceModule />}
          {active === "kaizen" && <KaizenModule data={data.kaizen} title={kaizenTitle} owner={kaizenOwner} impact={kaizenImpact} setTitle={setKaizenTitle} setOwner={setKaizenOwner} setImpact={setKaizenImpact} add={addKaizen} remove={(id) => remove("kaizen", id)} setData={setData} />}
          {active === "people" && <PeopleModule />}
          {active === "tpm" && <TpmModule data={data.tpm} equipment={equipment} nextDue={nextDue} setEquipment={setEquipment} setNextDue={setNextDue} add={addTpm} remove={(id) => remove("tpm", id)} />}
          {active === "materials" && <MaterialModule data={data.materials} code={materialCode} name={materialName} usage={materialUsage} setCode={setMaterialCode} setName={setMaterialName} setUsage={setMaterialUsage} add={addMaterial} remove={(id) => remove("materials", id)} />}
          {active === "vsm" && <VsmModule />}
        </main>
      </div>
    </div>
  );
}

function CycleModule({ data, operation, values, setOperation, setValues, add, remove }: { data: CycleRecord[]; operation: string; values: string; setOperation: (value: string) => void; setValues: (value: string) => void; add: () => void; remove: (id: number) => void }) {
  return <><SectionTitle icon={Clock3} title="Cycle time study" detail="Record repeated observations and get instant statistical summaries." /><div className="mt-6 grid gap-6 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]"><div className="rounded-xl bg-slate-50 p-4"><div className="grid gap-4"><Field label="Operation"><Input value={operation} onChange={(event) => setOperation(event.target.value)} placeholder="e.g. Collar attach" /></Field><Field label="Observations in seconds"><Input value={values} onChange={(event) => setValues(event.target.value)} placeholder="42, 44, 41, 43, 45" /></Field><Button onClick={add}><Plus className="mr-2 h-4 w-4" /> Add observation set</Button></div><p className="mt-4 text-xs leading-5 text-slate-500">Use comma-separated readings from the stopwatch. Average, range, and standard deviation are calculated automatically.</p></div><div className="overflow-x-auto rounded-xl border border-slate-200"><table className="w-full text-left text-sm"><thead className="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th className="px-4 py-3">Operation</th><th className="px-4 py-3">Avg</th><th className="px-4 py-3">Range</th><th className="px-4 py-3">Std dev</th><th /></tr></thead><tbody>{data.map((record) => <tr key={record.id} className="border-t border-slate-100"><td className="px-4 py-3 font-medium text-slate-900">{record.operation}<span className="block text-xs font-normal text-slate-400">{record.observations.length} samples</span></td><td className="px-4 py-3">{mean(record.observations).toFixed(1)} s</td><td className="px-4 py-3">{Math.min(...record.observations)} - {Math.max(...record.observations)} s</td><td className="px-4 py-3">{stdDev(record.observations).toFixed(1)} s</td><td className="px-4 py-3 text-right"><Button size="icon" variant="ghost" onClick={() => remove(record.id)} aria-label={`Delete ${record.operation}`}><Trash2 className="h-4 w-4 text-slate-400" /></Button></td></tr>)}</tbody></table></div></div></>;
}

function BreakdownModule({ data, activity, minutes, setActivity, setMinutes, add, remove, total }: { data: BreakdownRecord[]; activity: string; minutes: string; setActivity: (value: string) => void; setMinutes: (value: string) => void; add: () => void; remove: (id: number) => void; total: number }) {
  return <><SectionTitle icon={Activity} title="Operational breakdown" detail="Make hidden losses visible by category and duration." /><div className="mt-6 grid gap-6 xl:grid-cols-[0.75fr_1.25fr]"><div className="rounded-xl bg-amber-50/70 p-4"><div className="grid gap-4"><Field label="Breakdown activity"><Input value={activity} onChange={(event) => setActivity(event.target.value)} placeholder="e.g. Needle change" /></Field><Field label="Observed minutes"><Input type="number" min="1" value={minutes} onChange={(event) => setMinutes(event.target.value)} placeholder="10" /></Field><Button onClick={add} className="bg-amber-600 hover:bg-amber-700"><Plus className="mr-2 h-4 w-4" /> Record loss</Button></div></div><div className="space-y-3">{data.map((record) => <div key={record.id} className="flex items-center gap-4 rounded-xl border border-slate-200 p-4"><div className="min-w-0 flex-1"><div className="flex items-center justify-between gap-4"><p className="truncate text-sm font-semibold text-slate-900">{record.activity}</p><span className="text-sm font-semibold text-amber-700">{record.minutes} min</span></div><div className="mt-2 h-2 rounded-full bg-slate-100"><div className="h-2 rounded-full bg-amber-500" style={{ width: `${Math.min(100, (record.minutes / Math.max(total, 1)) * 100)}%` }} /></div><p className="mt-2 text-xs text-slate-400">{record.category}</p></div><Button size="icon" variant="ghost" onClick={() => remove(record.id)} aria-label={`Delete ${record.activity}`}><Trash2 className="h-4 w-4 text-slate-400" /></Button></div>)}</div></div></>;
}

function BalanceModule() {
  const [takt, setTakt] = useState("45");
  const [times, setTimes] = useState("42, 48, 39, 44, 31");
  const stationTimes = times.split(",").map(Number).filter((value) => Number.isFinite(value) && value > 0);
  const taktValue = Number(takt) || 1;
  const efficiency = (stationTimes.reduce((sum, value) => sum + value, 0) / (stationTimes.length * taktValue)) * 100;
  return <><SectionTitle icon={Gauge} title="Line balancing" detail="Compare station cycle time to takt and surface bottlenecks immediately." /><div className="mt-6 grid gap-6 xl:grid-cols-[0.75fr_1.25fr]"><div className="rounded-xl bg-emerald-50/70 p-4"><div className="grid gap-4"><Field label="Takt time (seconds)"><Input type="number" value={takt} onChange={(event) => setTakt(event.target.value)} /></Field><Field label="Station cycle times"><Input value={times} onChange={(event) => setTimes(event.target.value)} /></Field></div><div className="mt-6 rounded-xl bg-white p-4"><p className="text-xs font-semibold uppercase tracking-wider text-slate-500">Balance efficiency</p><p className="mt-1 text-3xl font-semibold text-emerald-700">{efficiency.toFixed(1)}%</p><p className="mt-1 text-xs text-slate-500">Formula: total cycle time / (stations × takt)</p></div></div><div className="space-y-3">{stationTimes.map((time, index) => { const bottleneck = time > taktValue; return <div key={`${time}-${index}`} className="flex items-center gap-4 rounded-xl border border-slate-200 p-4"><div className={cn("flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold", bottleneck ? "bg-red-100 text-red-700" : "bg-emerald-100 text-emerald-700")}>{index + 1}</div><div className="flex-1"><div className="flex justify-between text-sm"><span className="font-medium text-slate-900">Station {String(index + 1).padStart(2, "0")}</span><span className={bottleneck ? "font-semibold text-red-600" : "text-slate-500"}>{time}s / {taktValue}s</span></div><div className="mt-2 h-2 rounded-full bg-slate-100"><div className={cn("h-2 rounded-full", bottleneck ? "bg-red-500" : "bg-emerald-500")} style={{ width: `${Math.min(100, (time / taktValue) * 100)}%` }} /></div></div><span className="text-xs font-semibold uppercase text-slate-400">{bottleneck ? "Bottleneck" : "On takt"}</span></div> })}</div></div></>;
}

function KaizenModule({ data, title, owner, impact, setTitle, setOwner, setImpact, add, remove, setData }: { data: KaizenRecord[]; title: string; owner: string; impact: string; setTitle: (value: string) => void; setOwner: (value: string) => void; setImpact: (value: string) => void; add: () => void; remove: (id: number) => void; setData: React.Dispatch<React.SetStateAction<typeof initialState>> }) {
  const advance = (id: number) => setData((current) => ({ ...current, kaizen: current.kaizen.map((item) => item.id === id ? { ...item, status: item.status === "Proposed" ? "In progress" : item.status === "In progress" ? "Completed" : "Completed" } : item) }));
  return <><SectionTitle icon={Sparkles} title="Kaizen improvement board" detail="Create improvement proposals, assign ownership, and move them through verification." /><div className="mt-6 grid gap-6 xl:grid-cols-[0.75fr_1.25fr]"><div className="rounded-xl bg-rose-50/70 p-4"><div className="grid gap-4"><Field label="Improvement title"><Input value={title} onChange={(event) => setTitle(event.target.value)} placeholder="What should improve?" /></Field><Field label="Owner"><Input value={owner} onChange={(event) => setOwner(event.target.value)} placeholder="Responsible person" /></Field><Field label="Expected seconds saved"><Input type="number" value={impact} onChange={(event) => setImpact(event.target.value)} placeholder="10" /></Field><Button onClick={add} className="bg-rose-600 hover:bg-rose-700"><Plus className="mr-2 h-4 w-4" /> Propose kaizen</Button></div></div><div className="space-y-3">{data.map((item) => <div key={item.id} className="rounded-xl border border-slate-200 p-4"><div className="flex items-start gap-3"><div className="flex-1"><div className="flex flex-wrap items-center gap-2"><p className="font-semibold text-slate-900">{item.title}</p><span className={cn("rounded-full px-2 py-1 text-[11px] font-semibold", item.status === "Completed" ? "bg-emerald-100 text-emerald-700" : item.status === "In progress" ? "bg-blue-100 text-blue-700" : "bg-slate-100 text-slate-600")}>{item.status}</span></div><p className="mt-2 text-xs text-slate-500">Owner: {item.owner} · Expected gain: {item.impact}s</p></div><Button size="icon" variant="ghost" onClick={() => remove(item.id)} aria-label={`Delete ${item.title}`}><Trash2 className="h-4 w-4 text-slate-400" /></Button></div><Button size="sm" variant="outline" className="mt-4" onClick={() => advance(item.id)} disabled={item.status === "Completed"}>{item.status === "Proposed" ? "Start work" : item.status === "In progress" ? "Mark completed" : "Completed"}</Button></div>)}</div></div></>;
}

function PeopleModule() {
  const [ratings, setRatings] = useState({ "Budi Santoso": [4, 3, 5, 2], "Siti Rahayu": [5, 4, 3, 4], "Agus Wijaya": [2, 3, 2, 1] });
  const skills = ["Collar attach", "Sleeve hem", "Overlock", "Final check"];
  return <><SectionTitle icon={Users} title="Skills & OSCP matrix" detail="Review operator certification coverage and spot skill gaps by operation." /><div className="mt-6 overflow-x-auto rounded-xl border border-slate-200"><table className="w-full min-w-[680px] text-left text-sm"><thead className="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th className="px-4 py-3">Operator</th>{skills.map((skill) => <th key={skill} className="px-4 py-3">{skill}</th>)}<th className="px-4 py-3">Coverage</th></tr></thead><tbody>{Object.entries(ratings).map(([name, values]) => { const coverage = Math.round((values.filter((value) => value >= 3).length / skills.length) * 100); return <tr key={name} className="border-t border-slate-100"><td className="px-4 py-4 font-semibold text-slate-900">{name}<span className="block text-xs font-normal text-slate-400">OSCP active</span></td>{values.map((value, index) => <td key={skills[index]} className="px-4 py-4"><button onClick={() => setRatings((current) => ({ ...current, [name]: current[name].map((rating, ratingIndex) => ratingIndex === index ? rating === 5 ? 1 : rating + 1 : rating) }))} className={cn("flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition-transform hover:scale-110", value >= 4 ? "bg-emerald-100 text-emerald-700" : value >= 3 ? "bg-amber-100 text-amber-700" : "bg-red-100 text-red-700")} aria-label={`Change ${name} ${skills[index]} rating`}>{value}</button></td>)}<td className="px-4 py-4 font-semibold text-slate-700">{coverage}%</td></tr> })}</tbody></table></div><p className="mt-4 flex items-center gap-2 text-xs text-slate-500"><ShieldCheck className="h-4 w-4 text-emerald-600" /> Click a rating to cycle it from 1 to 5 and keep the training view current.</p></>;
}

function TpmModule({ data, equipment, nextDue, setEquipment, setNextDue, add, remove }: { data: TpmRecord[]; equipment: string; nextDue: string; setEquipment: (value: string) => void; setNextDue: (value: string) => void; add: () => void; remove: (id: number) => void }) {
  return <><SectionTitle icon={Wrench} title="TPM maintenance register" detail="Track equipment readiness and preventive maintenance due dates." /><div className="mt-6 grid gap-6 xl:grid-cols-[0.75fr_1.25fr]"><div className="rounded-xl bg-orange-50/70 p-4"><div className="grid gap-4"><Field label="Equipment"><Input value={equipment} onChange={(event) => setEquipment(event.target.value)} placeholder="Machine / asset" /></Field><Field label="Next due date"><Input type="date" value={nextDue} onChange={(event) => setNextDue(event.target.value)} /></Field><Button onClick={add} className="bg-orange-600 hover:bg-orange-700"><Plus className="mr-2 h-4 w-4" /> Add schedule</Button></div></div><div className="space-y-3">{data.map((item) => <div key={item.id} className="flex items-center gap-4 rounded-xl border border-slate-200 p-4"><div className="rounded-lg bg-orange-100 p-2 text-orange-700"><Wrench className="h-4 w-4" /></div><div className="flex-1"><p className="text-sm font-semibold text-slate-900">{item.equipment}</p><p className="mt-1 text-xs text-slate-500">Preventive maintenance due {item.nextDue}</p></div><span className={cn("rounded-full px-2 py-1 text-[11px] font-semibold", item.status === "Ready" ? "bg-emerald-100 text-emerald-700" : "bg-amber-100 text-amber-700")}>{item.status}</span><Button size="icon" variant="ghost" onClick={() => remove(item.id)} aria-label={`Delete ${item.equipment}`}><Trash2 className="h-4 w-4 text-slate-400" /></Button></div>)}</div></div></>;
}

function MaterialModule({ data, code, name, usage, setCode, setName, setUsage, add, remove }: { data: MaterialRecord[]; code: string; name: string; usage: string; setCode: (value: string) => void; setName: (value: string) => void; setUsage: (value: string) => void; add: () => void; remove: (id: number) => void }) {
  return <><SectionTitle icon={Layers3} title="Material database" detail="Keep material masters linked to the products and operations that consume them." /><div className="mt-6 grid gap-6 xl:grid-cols-[0.75fr_1.25fr]"><div className="rounded-xl bg-blue-50/70 p-4"><div className="grid gap-4"><Field label="Material code"><Input value={code} onChange={(event) => setCode(event.target.value)} placeholder="MAT-025" /></Field><Field label="Material name"><Input value={name} onChange={(event) => setName(event.target.value)} placeholder="Material specification" /></Field><Field label="Used by article / process"><Input value={usage} onChange={(event) => setUsage(event.target.value)} placeholder="Article or operation" /></Field><Button onClick={add} className="bg-blue-600 hover:bg-blue-700"><Plus className="mr-2 h-4 w-4" /> Add material</Button></div></div><div className="space-y-3">{data.map((item) => <div key={item.id} className="flex items-center gap-4 rounded-xl border border-slate-200 p-4"><div className="rounded-lg bg-blue-100 px-2 py-1 text-xs font-bold text-blue-700">{item.code}</div><div className="flex-1"><p className="text-sm font-semibold text-slate-900">{item.name}</p><p className="mt-1 text-xs text-slate-500">Linked to {item.usage}</p></div><Button size="icon" variant="ghost" onClick={() => remove(item.id)} aria-label={`Delete ${item.name}`}><Trash2 className="h-4 w-4 text-slate-400" /></Button></div>)}</div></div></>;
}

function VsmModule() {
  const [stages, setStages] = useState("Cutting, Sewing, Inspection, Packing");
  const items = stages.split(",").map((stage) => stage.trim()).filter(Boolean);
  return <><SectionTitle icon={BarChart3} title="Value stream map" detail="Sketch the current flow and make lead-time conversations concrete." /><div className="mt-6 rounded-xl bg-teal-50/70 p-4"><Field label="Process stages"><Input value={stages} onChange={(event) => setStages(event.target.value)} placeholder="Raw material, Process, Finished goods" /></Field></div><div className="mt-8 flex flex-wrap items-center gap-3">{items.map((stage, index) => <div key={`${stage}-${index}`} className="flex items-center gap-3"><div className="min-w-[150px] rounded-xl border-2 border-teal-200 bg-white p-4 shadow-sm"><p className="text-xs font-semibold uppercase tracking-wider text-teal-600">Stage {index + 1}</p><p className="mt-2 font-semibold text-slate-900">{stage}</p><p className="mt-2 text-xs text-slate-500">Cycle time: define</p></div>{index < items.length - 1 && <ArrowRight className="h-5 w-5 text-teal-500" />}</div>)}</div><div className="mt-8 grid gap-3 sm:grid-cols-3"><div className="rounded-xl border border-slate-200 p-4"><p className="text-xs uppercase tracking-wider text-slate-400">Current state</p><p className="mt-2 font-semibold text-slate-900">{items.length} process stages</p></div><div className="rounded-xl border border-slate-200 p-4"><p className="text-xs uppercase tracking-wider text-slate-400">Improvement lens</p><p className="mt-2 font-semibold text-slate-900">Flow and waiting</p></div><div className="rounded-xl border border-slate-200 p-4"><p className="text-xs uppercase tracking-wider text-slate-400">Next action</p><p className="mt-2 flex items-center gap-2 font-semibold text-slate-900"><Save className="h-4 w-4 text-teal-600" /> Add stage metrics</p></div></div></>;
}
