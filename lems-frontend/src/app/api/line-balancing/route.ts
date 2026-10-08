import { NextRequest } from "next/server";
import { authenticate, success, fail, unauthenticated, validationFail } from "@/lib/http";
import { validate } from "@/lib/validation";
import { db } from "@/lib/db";

/**
 * GET /api/line-balancing — paginated list with search/filter/sort.
 * POST /api/line-balancing — create new report.
 *
 * Replica of Laravel LineBalancingController index + store.
 */

const STORE_RULES = {
  factory_id: "required|exists:factories,id",
  article_id: "required|exists:articles,id",
  line_id: "required|exists:production_lines,id",
  report_name: "required|max:150",
  working_hours_per_day: "nullable",
  allowance_percent: "nullable",
  update_date: "nullable",
};

export async function GET(req: NextRequest) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();

  const sp = req.nextUrl.searchParams;
  const page = Math.max(1, Number(sp.get("page") ?? 1));
  const perPage = 15;
  const search = sp.get("search")?.trim() ?? "";
  const showInactive = sp.get("show_inactive") === "1" || sp.get("show_inactive") === "true";

  // Filters
  const filterFactory = sp.get("factory_id");
  const filterArticle = sp.get("article_id");
  const filterReportName = sp.get("report_name");
  const filterCreatedBy = sp.get("created_by");
  const filterStatus = sp.get("status");

  const where: any = {};

  // Active/inactive toggle
  if (!showInactive) {
    where.status = "active";
  } else if (filterStatus) {
    where.status = filterStatus;
  }

  // Search across report_name, factory.factory_name, article.article_name
  if (search) {
    where.OR = [
      { report_name: { contains: search } },
      { factories: { factory_name: { contains: search } } },
      { articles: { article_name: { contains: search } } },
    ];
  }

  // Column filters
  if (filterFactory) where.factory_id = BigInt(filterFactory);
  if (filterArticle) where.article_id = BigInt(filterArticle);
  if (filterReportName) where.report_name = { contains: filterReportName };
  if (filterCreatedBy) where.created_by = BigInt(filterCreatedBy);

  // Sort
  const sortField = sp.get("sort") ?? "id";
  const sortOrder = (sp.get("order") ?? "desc") === "desc" ? "desc" : "asc";
  // Map sort field names
  const sortMap: Record<string, any> = {
    id: { id: sortOrder },
    report_name: { report_name: sortOrder },
    factory: { factories: { factory_name: sortOrder } },
    article: { articles: { article_name: sortOrder } },
    created_date: { created_at: sortOrder },
    edited_date: { updated_at: sortOrder },
    created_by: { created_by: sortOrder },
    status: { status: sortOrder },
  };
  const orderBy = sortMap[sortField] ?? { id: sortOrder };

  const include = {
    factories: true,
    articles: true,
    production_lines: true,
    users: true,
  };

  try {
    const [total, rows] = await Promise.all([
      db.line_balancing_reports.count({ where }),
      db.line_balancing_reports.findMany({
        where,
        include,
        orderBy,
        skip: (page - 1) * perPage,
        take: perPage,
      }),
    ]);

    const data = rows.map((r) => ({
      id: Number(r.id),
      factory_id: Number(r.factory_id),
      factory_name: r.factories?.factory_name ?? null,
      article_id: Number(r.article_id),
      article_name: r.articles?.article_name ?? null,
      line_id: Number(r.line_id),
      line_name: r.production_lines?.line_name ?? null,
      report_name: r.report_name,
      target_output_per_hour: r.target_output_per_hour,
      output_actual: r.output_actual,
      working_hours_per_day: r.working_hours_per_day != null ? Number(r.working_hours_per_day) : null,
      allowance_percent: r.allowance_percent != null ? Number(r.allowance_percent) : null,
      update_date: r.update_date ? new Date(r.update_date).toISOString().split("T")[0] : null,
      status: r.status,
      created_by: Number(r.created_by),
      created_by_name: r.users?.name ?? null,
      created_at: r.created_at?.toISOString() ?? null,
      updated_at: r.updated_at?.toISOString() ?? null,
    }));

    return success({
      data,
      total,
      current_page: page,
      last_page: Math.ceil(total / perPage),
      per_page: perPage,
    });
  } catch (e: any) {
    return fail(`Query error: ${e?.message ?? e}`, 500);
  }
}

export async function POST(req: NextRequest) {
  const auth = await authenticate(req);
  if (!auth) return unauthenticated();

  const body = await req.json().catch(() => ({}));
  const { data, errors } = await validate(body, STORE_RULES);
  if (errors) return validationFail(errors);

  try {
    const report = await db.line_balancing_reports.create({
      data: {
        factory_id: BigInt(data.factory_id),
        article_id: BigInt(data.article_id),
        line_id: BigInt(data.line_id),
        report_name: data.report_name,
        target_output_per_hour: 0,
        working_hours_per_day: data.working_hours_per_day ? parseFloat(data.working_hours_per_day) : 8.0,
        allowance_percent: data.allowance_percent ? parseFloat(data.allowance_percent) : 15.0,
        update_date: data.update_date ? new Date(data.update_date) : null,
        status: "active",
        created_by: BigInt(auth.user.id),
      },
      include: { factories: true, articles: true, production_lines: true },
    });

    return success(
      {
        id: Number(report.id),
        report_name: report.report_name,
        factory_name: report.factories?.factory_name ?? null,
        article_name: report.articles?.article_name ?? null,
        line_name: report.production_lines?.line_name ?? null,
      },
      "Line Balancing Report created successfully",
      201
    );
  } catch (e: any) {
    return fail(`SQLSTATE[23000]: Integrity constraint violation: ${e?.message ?? e}`, 500);
  }
}