import { NextRequest } from "next/server";
import { authenticate, notFoundResult, unauthenticated } from "@/lib/http";
import { db } from "@/lib/db";
import ExcelJS from "exceljs";

/* ── colour / style helpers ───────────────────────────────────── */
const rgb = (hex: string) => ({ argb: hex });
const sFill = (hex: string): ExcelJS.Fill => ({ type: "pattern", pattern: "solid", fgColor: rgb(hex) });
const thinSide: ExcelJS.Border = { style: "thin" } as ExcelJS.Border;
const borders: Partial<ExcelJS.Borders> = { top: thinSide, bottom: thinSide, left: thinSide, right: thinSide };
const al = (h: "center" | "left" | "right" = "center", v: "middle" | "top" = "middle", wrap = true): Partial<ExcelJS.Alignment> => ({ horizontal: h, vertical: v, wrapText: wrap });

function sc(cell: ExcelJS.Cell, opts: { font?: Partial<ExcelJS.Font>; fillC?: string; alignment?: Partial<ExcelJS.Alignment>; border?: boolean; numFmt?: string }) {
  if (opts.font) cell.font = opts.font;
  if (opts.fillC) cell.fill = sFill(opts.fillC);
  if (opts.alignment) cell.alignment = opts.alignment;
  if (opts.border) cell.border = borders;
  if (opts.numFmt) cell.numFmt = opts.numFmt;
}

function styleRange(ws: ExcelJS.Worksheet, range: string, opts: { font?: Partial<ExcelJS.Font>; fillC?: string; alignment?: Partial<ExcelJS.Alignment>; border?: boolean; numFmt?: string }) {
  // Apply to each cell in the range
  const parts = range.split(":");
  if (parts.length === 2) {
    const [startRef, endRef] = parts;
    const startCol = startRef.replace(/\d+/, "");
    const startRow = parseInt(startRef.replace(/\D+/g, ""));
    const endCol = endRef.replace(/\d+/, "");
    const endRow = parseInt(endRef.replace(/\D+/g, ""));
    const startColNum = startCol.charCodeAt(0);
    const endColNum = endCol.charCodeAt(0);
    for (let c = startColNum; c <= endColNum; c++) {
      for (let r = startRow; r <= endRow; r++) {
        sc(ws.getCell(`${String.fromCharCode(c)}${r}`), opts);
      }
    }
  } else {
    sc(ws.getCell(range), opts);
  }
}

/**
 * GET /api/line-balancing/:id/export
 * XLSX export replicating the Laravel "LB Livlig" template.
 */
export async function GET(
  req: NextRequest,
  { params }: { params: { id: string } }
) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();
  if (!/^\d+$/.test(params.id)) return notFoundResult("LineBalancingReport", params.id);

  const report = await db.line_balancing_reports.findUnique({
    where: { id: Number(params.id) },
    include: {
      factories: true,
      articles: true,
      production_lines: true,
      line_balancing_report_rows: {
        include: { machine_types: true, operators: true },
        orderBy: { row_number: "asc" },
      },
    },
  });
  if (!report) return notFoundResult("LineBalancingReport", params.id);

  const rows = report.line_balancing_report_rows ?? [];
  const factoryName = report.factories?.factory_name ?? "-";
  const articleName = report.articles?.article_name ?? "-";
  const lineName = report.production_lines?.line_name ?? "-";
  const targetOutput = report.target_output_per_hour ?? 0;
  const workHrs = report.working_hours_per_day != null ? Number(report.working_hours_per_day) : 8;
  const outputActual = report.output_actual ?? 0;
  const updateDate = report.update_date;

  /* ── workbook setup ─────────────────────────────────────────── */
  const wb = new ExcelJS.Workbook();
  const ws = wb.addWorksheet("LB Livlig", { pageSetup: { orientation: "landscape" } });

  /* Column widths (A–V, spec-exact) */
  const cw: Record<string, number> = { A:2.57,B:5.86,C:12.71,D:76.43,E:19.86,F:14.14,G:13.43,H:7.71,I:13,J:13,K:13,L:13,M:14.29,N:13,O:14,P:19.29,Q:13.57,R:13,S:16.57,T:22.29,U:13,V:14.29 };
  for (const [col, w] of Object.entries(cw)) ws.getColumn(col).width = w;
  ws.getColumn("F").hidden = true;
  ws.getColumn("S").hidden = true;

  /* Row heights */
  const rh: Record<number, number> = {2:23.25,3:43.15,4:31.5,5:21,6:21.75,7:22.15,8:27.6,9:36.75,10:24,17:28.5,19:28.5,24:27,27:30,28:26.25,29:29.25,30:29.25,32:31.5,33:18.75,44:3.75};
  for (let r = 11; r <= 31; r++) if (!rh[r]) rh[r] = 25.15;
  for (let r = 35; r <= 43; r++) rh[r] = 25.15;
  for (const [r, h] of Object.entries(rh)) ws.getRow(Number(r)).height = h;

  /* Font/fill shortcuts */
  const df: Partial<ExcelJS.Font> = { name: "Arial", size: 12 };
  const dfn: Partial<ExcelJS.Font> = { name: "Arial Narrow", size: 11 };
  const hf: Partial<ExcelJS.Font> = { name: "Arial", size: 12, bold: true, color: rgb("FFFFFFFF") };
  const sf: Partial<ExcelJS.Font> = { name: "Arial", size: 10, bold: true, color: rgb("FFFFFFFF") };
  const s2f: Partial<ExcelJS.Font> = { name: "Arial", size: 11, bold: true, color: rgb("FFFFFFFF") };

  /* ═══ ROW 2 — Title ═══ */
  ws.mergeCells("B2:Q2");
  ws.getCell("B2").value = "LINE BALANCING SEWING";
  sc(ws.getCell("B2"), { font: { name: "Arial", size: 18, bold: true, color: rgb("FFFFFFFF") }, fillC: "FF4472C4", alignment: al() });

  /* ═══ ROW 3 ═══ */
  ws.getCell("D3").value = report.report_name;
  sc(ws.getCell("D3"), { font: { name: "Arial", size: 14, bold: true }, alignment: al("left") });

  ws.mergeCells("E3:G3"); ws.getCell("E3").value = "Line";
  styleRange(ws, "E3:G3", { font: { name: "Arial", size: 11, bold: true }, fillC: "FFD9E2F3", alignment: al(), border: true });

  ws.mergeCells("H3:K3"); ws.getCell("H3").value = lineName;
  styleRange(ws, "H3:K3", { font: { name: "Arial", size: 11 }, alignment: al(), border: true });

  ws.mergeCells("L3:O3"); ws.getCell("L3").value = "PPH";
  styleRange(ws, "L3:O3", { font: { name: "Arial", size: 11, bold: true }, fillC: "FFD9E2F3", alignment: al(), border: true });

  ws.getCell("P3").value = "Target Output /Hours";
  sc(ws.getCell("P3"), { font: { name: "Arial", size: 10, bold: true }, fillC: "FFD9E2F3", alignment: al(), border: true });

  ws.getCell("Q3").value = targetOutput;
  sc(ws.getCell("Q3"), { font: { name: "Arial", size: 12, bold: true }, fillC: "FFFFF2CC", alignment: al(), border: true, numFmt: '0" Pcs "' });

  /* ═══ ROW 4 ═══ */
  ws.getCell("D4").value = factoryName;
  sc(ws.getCell("D4"), { font: { name: "Arial", size: 12, bold: true }, alignment: al("left") });

  ws.mergeCells("E4:G4"); ws.getCell("E4").value = "Article";
  styleRange(ws, "E4:G4", { font: { name: "Arial", size: 11, bold: true }, fillC: "FFD9E2F3", alignment: al(), border: true });

  ws.mergeCells("H4:K4"); ws.getCell("H4").value = articleName;
  styleRange(ws, "H4:K4", { font: { name: "Arial", size: 11 }, alignment: al(), border: true });

  ws.mergeCells("L4:O6"); ws.getCell("L4").value = { formula: "Q3/H6" };
  sc(ws.getCell("L4"), { font: { name: "Arial", size: 14, bold: true }, fillC: "FFB4C6E7", alignment: al(), border: true, numFmt: "0.00" });
  // Apply fill/align/border to merged range
  styleRange(ws, "L4:O6", { fillC: "FFB4C6E7", alignment: al(), border: true });

  ws.getCell("P4").value = "Target Output / Day";
  sc(ws.getCell("P4"), { font: { name: "Arial", size: 10, bold: true }, fillC: "FFD9E2F3", alignment: al(), border: true });

  ws.getCell("Q4").value = { formula: `Q3*${workHrs}` };
  sc(ws.getCell("Q4"), { font: { name: "Arial", size: 12, bold: true }, fillC: "FFFFF2CC", alignment: al(), border: true, numFmt: "0" });

  /* ═══ ROW 5 ═══ */
  ws.getCell("D5").value = "CYCLE TIME ANALYZE";
  sc(ws.getCell("D5"), { font: { name: "Arial", size: 12, bold: true, color: rgb("FF1F4E79") }, alignment: al("left") });

  ws.mergeCells("E5:G5"); ws.getCell("E5").value = "Update";
  styleRange(ws, "E5:G5", { font: { name: "Arial", size: 11, bold: true }, fillC: "FFD9E2F3", alignment: al(), border: true });

  ws.mergeCells("H5:K5");
  if (updateDate) { ws.getCell("H5").value = new Date(updateDate); ws.getCell("H5").numFmt = "d mmmm yyyy"; } else { ws.getCell("H5").value = "-"; }
  styleRange(ws, "H5:K5", { font: { name: "Arial", size: 11 }, alignment: al(), border: true });

  ws.getCell("P5").value = "Time 1 Hours";
  sc(ws.getCell("P5"), { font: { name: "Arial", size: 10, bold: true }, fillC: "FFD9E2F3", alignment: al(), border: true });

  ws.getCell("Q5").value = 3600;
  sc(ws.getCell("Q5"), { font: { name: "Arial", size: 12, bold: true }, fillC: "FFFFF2CC", alignment: al(), border: true, numFmt: "0" });

  /* ═══ ROW 6 ═══ */
  ws.mergeCells("E6:G6"); styleRange(ws, "E6:G6", { border: true, alignment: al() });
  ws.mergeCells("H6:K6");

  ws.getCell("P6").value = "Takt Time";
  sc(ws.getCell("P6"), { font: { name: "Arial", size: 10, bold: true }, fillC: "FFD9E2F3", alignment: al(), border: true });

  ws.getCell("Q6").value = { formula: "Q5/Q3" };
  sc(ws.getCell("Q6"), { font: { name: "Arial", size: 12, bold: true }, fillC: "FFFFF2CC", alignment: al(), border: true, numFmt: "0.00" });

  /* ═══ TABLE HEADERS (Rows 7–10) ═══ */
  const hdrFillC = "FF4472C4";
  const subFillC = "FF2F5597";
  const sub2FillC = "FF5B9BD5";
  const grayFillC = "FFD6DCE4";
  const hOpts = { font: hf, fillC: hdrFillC, alignment: al(), border: true };
  const sOpts = { font: sf, fillC: subFillC, alignment: al(), border: true };
  const s2Opts = { font: s2f, fillC: sub2FillC, alignment: al(), border: true };

  const merges = ["B7:B10","C7:C10","D7:D10","E7:E10","F7:F10","G7:G9","H7:L9","M7:M9","N7:N9","O7:O9","P7:P9","Q7:Q9","R7:R10","S7:S9","T7:T9"];
  for (const m of merges) ws.mergeCells(m);

  ws.getCell("B7").value = "No"; styleRange(ws,"B7:B10",hOpts);
  ws.getCell("C7").value = "Machine"; styleRange(ws,"C7:C10",hOpts);
  ws.getCell("D7").value = "Process"; styleRange(ws,"D7:D10",hOpts);
  ws.getCell("E7").value = "Name"; styleRange(ws,"E7:E10",hOpts);
  ws.getCell("F7").value = "Joint process"; styleRange(ws,"F7:F10",hOpts);
  ws.getCell("G7").value = "Operator"; styleRange(ws,"G7:G9",hOpts);
  ws.getCell("G10").value = { formula: "SUM(G11)" }; sc(ws.getCell("G10"), { font:{name:"Arial",size:11,bold:true}, fillC:grayFillC, alignment:al(), border:true, numFmt:"0" });

  ws.getCell("H7").value = "Cycle Time"; styleRange(ws,"H7:L9",hOpts);
  for (const [col,n] of [["H",1],["I",2],["J",3],["K",4],["L",5]] as [string,number][]) ws.getCell(`${col}10`).value = n;
  styleRange(ws,"H10:L10",sOpts);

  ws.getCell("M7").value = "Average Cycle Time"; styleRange(ws,"M7:M9",s2Opts);
  ws.getCell("N7").value = "Average Cycle Time + Allowance 15 %"; styleRange(ws,"N7:N9",sOpts);
  ws.getCell("O7").value = "Average CT / Process"; styleRange(ws,"O7:O9",s2Opts);
  ws.getCell("P7").value = "Output / Hours"; styleRange(ws,"P7:P9",s2Opts);
  ws.getCell("Q7").value = "Output Process / Hours"; styleRange(ws,"Q7:Q9",s2Opts);
  ws.getCell("R7").value = "Request Operator"; styleRange(ws,"R7:R10",hOpts);
  ws.getCell("S7").value = "Average Output/ Hour"; styleRange(ws,"S7:S9",hOpts);
  ws.getCell("T7").value = "Potential Output/Process"; styleRange(ws,"T7:T9",hOpts);

  /* Row 10 helper formulas */
  styleRange(ws,"M10:T10",{ font:{name:"Arial",size:10,bold:true}, fillC:grayFillC, alignment:al(), border:true });
  ws.getCell("N10").value={formula:"MAX(N11)"}; ws.getCell("N10").numFmt="0.00";
  ws.getCell("O10").value={formula:"MAX(O11)"}; ws.getCell("O10").numFmt="0.00";
  ws.getCell("P10").value={formula:"MAX(P11)"}; ws.getCell("P10").numFmt="0";
  ws.getCell("Q10").value={formula:"MIN(Q11)"}; ws.getCell("Q10").numFmt="0";
  ws.getCell("T10").value={formula:"MIN(T11)"}; ws.getCell("T10").numFmt="0";

  /* ═══ DATA ROWS (Row 11+) ═══ */
  const processFillC = "FFDCE6F1";
  const outputFillC = "FFE2EFDA";

  rows.forEach((row, i) => {
    const r = 11 + i;
    ws.getRow(r).height = 25.15;

    ws.getCell(`B${r}`).value = row.row_number;
    sc(ws.getCell(`B${r}`), { font:df, alignment:al(), border:true });

    ws.getCell(`C${r}`).value = row.machine_types?.machine_type ?? "-";
    sc(ws.getCell(`C${r}`), { font:df, alignment:al(), border:true });

    ws.getCell(`D${r}`).value = row.process ?? "-";
    sc(ws.getCell(`D${r}`), { font:df, alignment:al("left","middle",true), border:true });

    ws.getCell(`E${r}`).value = row.operators?.operator_name ?? "-";
    sc(ws.getCell(`E${r}`), { font:df, alignment:al(), border:true });

    ws.getCell(`F${r}`).value = row.joint_process ?? "";
    sc(ws.getCell(`F${r}`), { font:df, alignment:al(), border:true });

    ws.getCell(`G${r}`).value = row.operator ?? 1;
    sc(ws.getCell(`G${r}`), { font:df, alignment:al(), border:true, numFmt:"0" });

    const ctVals: Record<string,any> = { H:row.ct_1, I:row.ct_2, J:row.ct_3, K:row.ct_4, L:row.ct_5 };
    for (const [col,val] of Object.entries(ctVals)) {
      if (val != null && val !== "") ws.getCell(`${col}${r}`).value = Number(val);
      sc(ws.getCell(`${col}${r}`), { font:dfn, alignment:al(), border:true, numFmt:"0.00" });
    }

    ws.getCell(`M${r}`).value = { formula: `AVERAGE(H${r}:L${r})` };
    sc(ws.getCell(`M${r}`), { font:dfn, fillC:processFillC, alignment:al(), border:true, numFmt:"0.00" });

    ws.getCell(`N${r}`).value = { formula: `M${r}*1.15` };
    sc(ws.getCell(`N${r}`), { font:dfn, fillC:processFillC, alignment:al(), border:true, numFmt:"0.00" });

    ws.getCell(`O${r}`).value = { formula: `M${r}` };
    sc(ws.getCell(`O${r}`), { font:dfn, fillC:processFillC, alignment:al(), border:true, numFmt:"0.00" });

    ws.getCell(`P${r}`).value = { formula: `3600/N${r}` };
    sc(ws.getCell(`P${r}`), { font:dfn, fillC:outputFillC, alignment:al(), border:true, numFmt:"0" });

    ws.getCell(`Q${r}`).value = { formula: `P${r}` };
    sc(ws.getCell(`Q${r}`), { font:dfn, fillC:outputFillC, alignment:al(), border:true, numFmt:"0" });

    ws.getCell(`R${r}`).value = { formula: `O${r}/$Q$6` };
    sc(ws.getCell(`R${r}`), { font:dfn, alignment:al(), border:true, numFmt:"0.00" });

    sc(ws.getCell(`S${r}`), { font:{name:"Arial",size:16,bold:true}, alignment:al(), border:true, numFmt:"0" });

    ws.getCell(`T${r}`).value = { formula: `Q${r}*8` };
    sc(ws.getCell(`T${r}`), { font:dfn, alignment:al(), border:true, numFmt:"0" });
  });

  /* ═══ TOTAL ROW ═══ */
  const lastDataRow = 10 + rows.length;
  const totalRow = lastDataRow + 1;
  ws.getRow(totalRow).height = 31.5;

  ws.mergeCells(`E${totalRow}:T${totalRow}`);
  ws.getCell(`E${totalRow}`).value = "Total";
  for (let c = "E".charCodeAt(0); c <= "T".charCodeAt(0); c++) {
    sc(ws.getCell(`${String.fromCharCode(c)}${totalRow}`), { font:{name:"Arial",size:12,bold:true}, fillC:grayFillC, alignment:al(), border:true });
  }

  ws.getCell(`G${totalRow}`).value={formula:`SUM(G11:G${lastDataRow})`}; ws.getCell(`G${totalRow}`).numFmt="0";
  ws.getCell(`M${totalRow}`).value={formula:`SUM(M11:M${lastDataRow})`}; ws.getCell(`M${totalRow}`).numFmt="0.00";
  ws.getCell(`N${totalRow}`).value={formula:`SUM(N11:N${lastDataRow})`}; ws.getCell(`N${totalRow}`).numFmt="0.00";
  ws.getCell(`O${totalRow}`).value={formula:`MAX(O11:O${lastDataRow})`}; ws.getCell(`O${totalRow}`).numFmt="0.00";
  ws.getCell(`P${totalRow}`).value={formula:`MAX(P11:P${lastDataRow})`}; ws.getCell(`P${totalRow}`).numFmt="0";
  ws.getCell(`Q${totalRow}`).value={formula:`MIN(Q11:Q${lastDataRow})`}; ws.getCell(`Q${totalRow}`).numFmt="0";
  ws.getCell(`R${totalRow}`).value={formula:`SUM(R11:R${lastDataRow})`}; ws.getCell(`R${totalRow}`).numFmt="0.00";
  ws.getCell(`T${totalRow}`).value={formula:`MIN(T11:T${lastDataRow})`}; ws.getCell(`T${totalRow}`).numFmt="0";

  /* H6:K6 = Total Manpower (set after data rows so formula references correct totalRow) */
  ws.getCell("H6").value = { formula: `G${totalRow}` };
  styleRange(ws,"H6:K6",{ font:{name:"Arial",size:11,bold:true}, fillC:"FFD9E2F3", alignment:al(), border:true });
  ws.getCell("H6").numFmt = "0.00";

  /* ═══ SUMMARY KPI (Rows 35–43) ═══ */
  const labelOpts = { font:{name:"Arial",size:12,bold:true,color:rgb("FFFFFFFF")}, fillC:"FF4472C4", alignment:al(), border:true };
  const valueOpts = { font:{name:"Arial",size:13,bold:true}, fillC:"FFFFF2CC", alignment:al(), border:true, numFmt:"0.00" };
  const unitOpts = { font:{name:"Arial",size:10}, fillC:grayFillC, alignment:al(), border:true };

  const summary: [string,string|number,string][] = [
    ["TOTAL CYCLE TIME",`=N${totalRow}`,"SEC"],
    ["TOTAL MANPOWER",`=G${totalRow}`,"PERSON"],
    ["AVERAGE STANDARD TIME","=R35/R36","SEC / PERSON"],
    ["TOTAL WORKING TIME",28800,"SEC / DAY / PERSON"],
    ["TARGET PER PCS","=ROUND(R38/R35,0)","PCS / DAY / PERSON"],
    ["TARGET LINE PER DAY","=R36*R39","PCS/ DAY"],
    ["TARGET LINE PER HOUR","=R40/8","PCS/HOUR"],
    ["MAXIMUM BASE ON CYLE TIME","=3600/N10","PCS/HOUR"],
    ["OUTPUT ACTUAL",outputActual||0,"PCS/HOUR"],
  ];

  summary.forEach(([label,value,unit],idx) => {
    const r = 35 + idx;
    ws.mergeCells(`M${r}:Q${r}`);
    ws.getCell(`M${r}`).value = label;
    styleRange(ws,`M${r}:Q${r}`,labelOpts);

    ws.getCell(`R${r}`).value = typeof value === "string" ? { formula: value.slice(1) } : value;
    sc(ws.getCell(`R${r}`), valueOpts);

    ws.mergeCells(`S${r}:T${r}`);
    ws.getCell(`S${r}`).value = unit;
    styleRange(ws,`S${r}:T${r}`,unitOpts);
  });

  ws.getCell("R36").numFmt="0";
  ws.getCell("R38").numFmt="0";
  ws.getCell("R39").numFmt="0";
  ws.getCell("R40").numFmt="0";
  ws.getCell("R43").numFmt="0";

  for (const [rr,u] of [["37","SEC"],["38","SEC"],["39","PCS"]] as [string,string][]) {
    ws.getCell(`T${rr}`).value = u;
    sc(ws.getCell(`T${rr}`), unitOpts);
  }

  ws.mergeCells("S11");

  /* ═══ OUTPUT ═══ */
  const ts = new Date();
  const pad = (n: number) => String(n).padStart(2, "0");
  const dateStr = `${ts.getFullYear()}${pad(ts.getMonth()+1)}${pad(ts.getDate())}_${pad(ts.getHours())}${pad(ts.getMinutes())}${pad(ts.getSeconds())}`;
  const filename = `LineBalancing_${report.report_name.replace(/[^A-Za-z0-9_]/g,"_")}_${dateStr}.xlsx`;

  const buffer = await wb.xlsx.writeBuffer();

  return new Response(buffer, {
    headers: {
      "Content-Type": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      "Content-Disposition": `attachment; filename="${filename}"`,
    },
  });
}