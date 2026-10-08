import { NextRequest } from "next/server";
import { authenticate, success, fail, notFoundResult, unauthenticated } from "@/lib/http";
import { db } from "@/lib/db";

/**
 * GET /api/operators/:id/profile
 * Returns operator with all relations + computed fields, matching
 * the Laravel Operator show blade (resources/views/operators/show.blade.php).
 */
export async function GET(
  req: NextRequest,
  { params }: { params: { id: string } }
) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();
  const { id } = params;

  if (!/^\d+$/.test(id)) return notFoundResult("Operator", id);

  const operator = await db.operators.findUnique({
    where: { id: Number(id) },
    include: {
      factories: true,
      departments: true,
      divisions: true,
      sections: true,
      production_lines: true,
      status_pkwtt: true,
      educational_levels: true,
    },
  });

  if (!operator) return notFoundResult("Operator", id);

  // Load ptms reports for history + organization fallback
  const ptmsReports = await db.ptms_reports.findMany({
    where: { operator_id: Number(id) },
    include: {
      factories: true,
      departments: true,
      production_lines: true,
      articles: true,
      process_versions: { include: { processes: true, gsd_elements: true } },
    },
    orderBy: { created_at: "desc" },
  });

  const latestReport = ptmsReports[0] ?? null;

  // Computed fields (matching Laravel Operator accessors)
  const dob = operator.date_of_birth;
  const startDate = operator.start_date;

  const calcDuration = (from: Date, to: Date) => {
    let years = to.getFullYear() - from.getFullYear();
    let months = to.getMonth() - from.getMonth();
    let days = to.getDate() - from.getDate();
    if (days < 0) { months--; days += new Date(to.getFullYear(), to.getMonth(), 0).getDate(); }
    if (months < 0) { years--; months += 12; }
    return { years, months, days };
  };

  const now = new Date();

  let working_age: string | null = null;
  let age: string | null = null;
  let age_year: number | null = null;
  let years_of_service: string | null = null;

  if (dob && startDate) {
    const d = calcDuration(new Date(dob), new Date(startDate));
    working_age = `${d.years} Yr ${d.months} Mth ${d.days} Day`;
  }
  if (dob) {
    const d = calcDuration(new Date(dob), now);
    age = `${d.years} Yr ${d.months} Mth ${d.days} Day`;
    age_year = d.years;
    // years_of_service = remaining until retirement (DOB + 59yr 20d)
    const retirement = new Date(new Date(dob).getTime());
    retirement.setFullYear(retirement.getFullYear() + 59);
    retirement.setDate(retirement.getDate() + 20);
    if (retirement > now) {
      const r = calcDuration(now, retirement);
      years_of_service = `${r.years} Yr ${r.months} Mth ${r.days} Day`;
    } else {
      years_of_service = "Retired";
    }
  }

  // Organization with fallback to latest report
  const factory_name = operator.factories?.factory_name ?? latestReport?.factories?.factory_name ?? null;
  const department_name = operator.departments?.department_name ?? latestReport?.departments?.department_name ?? null;
  const division_name = operator.divisions?.division ?? null;
  const section_name = operator.sections?.section ?? null;
  const line_name = operator.production_lines?.line_name ?? latestReport?.production_lines?.line_name ?? null;

  // History rows
  const history = ptmsReports.map((r) => ({
    id: Number(r.id),
    article_name: r.articles?.article_name ?? null,
    article_destination: r.articles?.destination ?? null,
    article_label_number: r.articles?.label_number ?? null,
    article_label_number_quty: r.articles?.label_number_quty ?? null,
    article_description: r.articles?.description ?? null,
    process_name: r.process_versions?.processes?.process_name ?? null,
    process_gsd_element: r.process_versions?.gsd_elements?.element_name ?? null,
    version_number: r.process_versions?.version_number ?? null,
  }));

  return success({
    id: Number(operator.id),
    employee_number: operator.employee_number,
    nik_karyawan: operator.nik_karyawan,
    operator_name: operator.operator_name,
    gender: operator.gender,
    role: operator.role,
    photo_path: operator.photo_path,
    status: operator.status,
    start_date: startDate ? new Date(startDate).toISOString().split("T")[0] : null,
    date_of_birth: dob ? new Date(dob).toISOString().split("T")[0] : null,
    working_age,
    age,
    age_year,
    years_of_service,
    status_pkwtt: operator.status_pkwtt?.pkwtt ?? null,
    educational_level: operator.educational_levels?.level ?? null,
    factory_name,
    department_name,
    division_name,
    section_name,
    line_name,
    history,
    created_at: operator.created_at?.toISOString() ?? null,
    updated_at: operator.updated_at?.toISOString() ?? null,
  });
}