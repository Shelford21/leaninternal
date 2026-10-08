import bcrypt from "bcryptjs";
import { dec2, iso, num } from "./http";
import type { Rules } from "./validation";

type Row = Record<string, any>;

/*
 * Registry driving the generic CRUD route handlers. Everything mirrors the
 * Laravel API contract extracted from app/Http/Controllers/Api/* and the
 * Resource classes: response field sets, decimal:2 strings, the `desription`
 * typo, validation rules and server-generated values are all preserved.
 */

export interface EntityConfig {
  /** Prisma delegate key (== introspected table name). */
  model: string;
  /** Laravel model class name used in 404 messages. */
  modelClass: string;
  /** Label used in "X created successfully" style messages. */
  label: string;
  searchFields?: string[];
  /** Query params mapped to equality filters. */
  filters?: string[];
  defaultSort?: { field: string; order: "asc" | "desc" };
  /** Prisma relation names eagerly loaded when `with` is absent (index). */
  defaultInclude?: string[];
  /** Accepted `?with=` tokens -> Prisma relation names. */
  includeAliases?: Record<string, string>;
  /**
   * Fixed relation sets for the non-index endpoints. Laravel loads relations
   * per method and ignores ?with= outside index; undefined means "load none"
   * for store/update and "same as defaultInclude" for show.
   */
  showInclude?: string[];
  storeInclude?: string[];
  updateInclude?: string[];
  /** Prisma relation names -> resource keys. */
  relationKeys?: Record<string, string>;
  storeRules: Rules;
  updateRules: Rules;
  serialize: (row: Row) => Row;
  beforeCreate?: (data: Row, userId: number) => Row | Promise<Row>;
  beforeUpdate?: (data: Row) => Row | Promise<Row>;
  /** Return an error message to block update, or null to allow. Receives the
   * existing row and the authenticated user object (id + role). */
  updateGuard?: (existing: Row, user: { id: number; role?: { role_name: string } | null }) => string | null;
  /** Return an error message to block destroy, or null to allow. */
  destroyGuard?: (row: Row, userId: number) => string | null;
  /** Supports GET /{resource}/all returning a plain array (SPA dropdowns). */
  allowAll?: boolean;
}

/* ------------------------- relation serializers -------------------------- */

const roleResource = (r: Row) =>
  r && {
    id: num(r.id),
    role_name: r.role_name,
    description: r.description,
    created_at: iso(r.created_at),
    updated_at: iso(r.updated_at),
  };

const factoryResource = (r: Row) =>
  r && {
    id: num(r.id),
    factory_name: r.factory_name,
    description: r.description,
    created_at: iso(r.created_at),
    updated_at: iso(r.updated_at),
  };

const departmentResource = (r: Row) =>
  r && {
    id: num(r.id),
    factory_id: num(r.factory_id),
    department_name: r.department_name,
    desription: r.desription, // NOTE: typo preserved from the Laravel API
    created_at: iso(r.created_at),
    updated_at: iso(r.updated_at),
  };

const divisionResource = (r: Row) =>
  r && {
    id: num(r.id),
    division: r.division,
    description: r.description,
    status: r.status,
    created_at: iso(r.created_at),
    updated_at: iso(r.updated_at),
  };

const processVersionResource = (r: Row): Row =>
  r && {
    id: num(r.id),
    process_id: num(r.process_id),
    version_number: r.version_number,
    notes: r.notes,
    status: r.status,
    created_by: num(r.created_by),
    created_at: iso(r.created_at),
    updated_at: iso(r.updated_at),
  };

const gsdCategoryResource = (r: Row) =>
  r && {
    id: num(r.id),
    category_name: r.category_name,
    description: r.description,
    status: r.status,
    created_at: iso(r.created_at),
    updated_at: iso(r.updated_at),
  };

const articleResource = (r: Row) =>
  r && {
    id: num(r.id),
    article_name: r.article_name,
    label_number: r.label_number,
    destination: r.destination,
    description: r.description,
    status: r.status,
    created_at: iso(r.created_at),
    updated_at: iso(r.updated_at),
  };

const operatorResource = (r: Row) =>
  r && {
    id: num(r.id),
    employee_number: r.employee_number,
    operator_name: r.operator_name,
    status: r.status,
    created_at: iso(r.created_at),
    updated_at: iso(r.updated_at),
  };

const userResource = (r: Row): Row =>
  r && {
    id: num(r.id),
    name: r.name,
    username: r.username,
    employee_number: r.employee_number,
    description: r.description,
    role_id: num(r.role_id),
    created_at: iso(r.created_at),
    updated_at: iso(r.updated_at),
  };

/* ------------------------------ registry -------------------------------- */

const STATUS_RULE = "in:active,inactive";

export const entities: Record<string, EntityConfig> = {
  factories: {
    model: "factories",
    modelClass: "Factory",
    label: "Factory",
    searchFields: ["factory_name"],
    defaultInclude: [],
    includeAliases: { departments: "departments" },
    // FactoryController::show always loads('departments'); store/update load none.
    showInclude: ["departments"],
    relationKeys: { departments: "departments" },
    storeRules: { factory_name: "required|max:255", description: "nullable|max:500" },
    updateRules: { factory_name: "sometimes|max:255", description: "nullable|max:500" },
    serialize: (r) => ({
      id: num(r.id),
      factory_name: r.factory_name,
      description: r.description,
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
      ...(r.departments ? { departments: r.departments.map(departmentResource) } : {}),
    }),
    allowAll: true,
  },

  departments: {
    model: "departments",
    modelClass: "Department",
    label: "Department",
    searchFields: ["department_name"],
    filters: ["factory_id"],
    defaultInclude: ["factories"],
    includeAliases: { factory: "factories", factories: "factories" },
    relationKeys: { factories: "factory" },
    storeRules: {
      department_name: "required|max:255",
      factory_id: "required|exists:factories,id",
      desription: "nullable|max:500",
    },
    updateRules: {
      department_name: "sometimes|max:255",
      factory_id: "sometimes|exists:factories,id",
      desription: "nullable|max:500",
    },
    serialize: (r) => ({
      id: num(r.id),
      factory_id: num(r.factory_id),
      department_name: r.department_name,
      desription: r.desription,
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
      ...(r.factories ? { factory: factoryResource(r.factories) } : {}),
    }),
    allowAll: true,
  },

  "production-lines": {
    model: "production_lines",
    modelClass: "ProductionLine",
    label: "Production line",
    searchFields: ["line_name"],
    filters: ["division_id"],
    defaultInclude: ["divisions"],
    includeAliases: { division: "divisions", divisions: "divisions" },
    relationKeys: { divisions: "division" },
    storeRules: {
      line_name: "required|max:255",
      division_id: "required|exists:divisions,id",
      description: "nullable",
    },
    updateRules: {
      line_name: "sometimes|max:255",
      division_id: "sometimes|exists:divisions,id",
      description: "nullable",
    },
    serialize: (r) => ({
      id: num(r.id),
      division_id: num(r.division_id),
      line_name: r.line_name,
      description: r.description,
      status: r.status,
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
      ...(r.divisions ? { division: divisionResource(r.divisions) } : {}),
    }),
  },

  divisions: {
    model: "divisions",
    modelClass: "Division",
    label: "Division",
    searchFields: ["division"],
    // NOTE: Laravel's API does not expose divisions at all (no apiResource).
    // Exposed here only to serve the SPA line form dropdown; documented as an
    // additive deviation.
    storeRules: {
      division: "required|max:100",
      description: "nullable|max:255",
      status: STATUS_RULE,
    },
    updateRules: {
      division: "sometimes|max:100",
      description: "nullable|max:255",
      status: STATUS_RULE,
    },
    serialize: (r) => ({
      id: num(r.id),
      division: r.division,
      description: r.description,
      status: r.status,
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
    }),
    allowAll: true,
  },

  articles: {
    model: "articles",
    modelClass: "Article",
    label: "Article",
    searchFields: ["article_name", "label_number"],
    storeRules: {
      article_name: "required|max:150",
      label_number: "required|unique:articles,label_number|max:100",
      destination: "required|max:100",
      description: "nullable",
      status: STATUS_RULE,
    },
    updateRules: {
      article_name: "sometimes|max:150",
      label_number: "sometimes|unique:articles,label_number,{id}|max:100",
      destination: "sometimes|max:100",
      description: "nullable",
      status: STATUS_RULE,
    },
    serialize: (r) => ({
      id: num(r.id),
      article_name: r.article_name,
      label_number: r.label_number,
      destination: r.destination,
      description: r.description,
      status: r.status,
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
    }),
    allowAll: true,
  },

  operators: {
    model: "operators",
    modelClass: "Operator",
    label: "Operator",
    searchFields: ["operator_name", "employee_number"],
    storeRules: {
      employee_number: "required|unique:operators,employee_number|max:20",
      operator_name: "required|max:100",
      status: STATUS_RULE,
    },
    updateRules: {
      employee_number: "sometimes|unique:operators,employee_number,{id}|max:20",
      operator_name: "sometimes|max:100",
      status: STATUS_RULE,
    },
    serialize: (r) => ({
      id: num(r.id),
      employee_number: r.employee_number,
      operator_name: r.operator_name,
      status: r.status,
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
    }),
    allowAll: true,
  },

  "gsd-categories": {
    model: "gsd_categories",
    modelClass: "GsdCategory",
    label: "GSD category",
    searchFields: ["category_name"],
    storeRules: {
      category_name: "required|unique:gsd_categories,category_name|max:150",
      description: "nullable",
    },
    updateRules: {
      category_name: "sometimes|unique:gsd_categories,category_name,{id}|max:150",
      description: "nullable",
    },
    serialize: (r) => ({
      id: num(r.id),
      category_name: r.category_name,
      description: r.description,
      status: r.status,
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
    }),
    allowAll: true,
  },

  "gsd-elements": {
    model: "gsd_elements",
    modelClass: "GsdElement",
    label: "GSD element",
    searchFields: ["element_name", "code"],
    filters: ["gsd_category_id"],
    defaultInclude: ["gsd_categories"],
    includeAliases: { gsdCategory: "gsd_categories", gsd_categories: "gsd_categories" },
    relationKeys: { gsd_categories: "gsd_category" },
    storeRules: {
      gsd_category_id: "required|exists:gsd_categories,id",
      element_name: "required|max:200",
      code: "required|max:50",
      tmu: "required|numeric|min:0",
      seconds: "required|numeric|min:0",
      description: "nullable",
      motion_sequence: "nullable",
      status: STATUS_RULE,
    },
    updateRules: {
      gsd_category_id: "sometimes|exists:gsd_categories,id",
      element_name: "sometimes|max:200",
      code: "sometimes|max:50",
      tmu: "sometimes|numeric|min:0",
      seconds: "sometimes|numeric|min:0",
      description: "nullable",
      motion_sequence: "nullable",
      status: STATUS_RULE,
    },
    serialize: (r) => ({
      id: num(r.id),
      gsd_category_id: num(r.gsd_category_id),
      element_name: r.element_name,
      description: r.description,
      code: r.code,
      tmu: dec2(r.tmu),
      seconds: dec2(r.seconds),
      motion_sequence: r.motion_sequence,
      status: r.status,
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
      ...(r.gsd_categories ? { gsd_category: gsdCategoryResource(r.gsd_categories) } : {}),
    }),
  },

  "mtm-elements": {
    model: "mtm_elements",
    modelClass: "MtmElement",
    label: "MTM element",
    searchFields: ["element_name", "code"],
    storeRules: {
      element_name: "required|max:200",
      code: "required|unique:mtm_elements,code|max:50",
      tmu: "required|numeric|min:0",
      seconds: "required|numeric|min:0",
      description: "nullable",
      status: STATUS_RULE,
    },
    updateRules: {
      element_name: "sometimes|max:200",
      code: "sometimes|unique:mtm_elements,code,{id}|max:50",
      tmu: "sometimes|numeric|min:0",
      seconds: "sometimes|numeric|min:0",
      description: "nullable",
      status: STATUS_RULE,
    },
    serialize: (r) => ({
      id: num(r.id),
      element_name: r.element_name,
      description: r.description,
      code: r.code,
      tmu: dec2(r.tmu),
      seconds: dec2(r.seconds),
      status: r.status,
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
    }),
  },

  "sewing-factors": {
    model: "sewing_factors",
    modelClass: "SewingFactor",
    label: "Sewing factor",
    searchFields: ["factor_name", "code"],
    storeRules: {
      factor_name: "required|max:100",
      code: "required|unique:sewing_factors,code|max:50",
      factor_value: "required|numeric|min:0",
      description: "nullable",
      status: STATUS_RULE,
    },
    updateRules: {
      factor_name: "sometimes|max:100",
      code: "sometimes|unique:sewing_factors,code,{id}|max:50",
      factor_value: "sometimes|numeric|min:0",
      description: "nullable",
      status: STATUS_RULE,
    },
    serialize: (r) => ({
      id: num(r.id),
      factor_name: r.factor_name,
      description: r.description,
      factor_value: dec2(r.factor_value),
      code: r.code,
      status: r.status,
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
    }),
  },

  "sewing-stop-factors": {
    model: "sewing_stop_factors",
    modelClass: "SewingStopFactor",
    label: "Sewing stop factor",
    searchFields: ["factor_name", "code"],
    storeRules: {
      factor_name: "required|max:150",
      code: "required|unique:sewing_stop_factors,code|max:50",
      factor_value: "required|numeric|min:0",
      description: "nullable",
      tolerance: "nullable",
      status: STATUS_RULE,
    },
    updateRules: {
      factor_name: "sometimes|max:150",
      code: "sometimes|unique:sewing_stop_factors,code,{id}|max:50",
      factor_value: "sometimes|numeric|min:0",
      description: "nullable",
      tolerance: "nullable",
      status: STATUS_RULE,
    },
    serialize: (r) => ({
      id: num(r.id),
      factor_name: r.factor_name,
      description: r.description,
      tolerance: r.tolerance,
      factor_value: dec2(r.factor_value),
      code: r.code,
      status: r.status,
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
    }),
  },

  processes: {
    model: "processes",
    modelClass: "Process",
    label: "Process",
    searchFields: ["process_name"],
    includeAliases: { versions: "process_versions", latestVersion: "process_versions" },
    // ProcessController::show always loads('latestVersion','versions') which the
    // ProcessResource renders as `versions` + `latest_version`; store/update load none.
    showInclude: ["process_versions"],
    storeRules: {
      process_name: "required|unique:processes,process_name|max:200",
      description: "nullable",
      status: STATUS_RULE,
    },
    updateRules: {
      process_name: "sometimes|unique:processes,process_name,{id}|max:200",
      description: "nullable",
      status: STATUS_RULE,
    },
    serialize: (r) => ({
      id: num(r.id),
      process_name: r.process_name,
      description: r.description,
      status: r.status,
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
      ...(r.process_versions
        ? {
            versions: r.process_versions.map(processVersionResource),
            // Laravel wraps whenLoaded('latestVersion') in a resource: empty
            // relation serializes as null (key present), not omitted.
            latest_version:
              [...r.process_versions]
                .sort((a: Row, b: Row) => b.version_number - a.version_number)
                .map(processVersionResource)[0] ?? null,
          }
        : {}),
    }),
  },

  "process-versions": {
    model: "process_versions",
    modelClass: "ProcessVersion",
    label: "Process version",
    filters: ["process_id"],
    defaultSort: { field: "version_number", order: "desc" },
    defaultInclude: ["processes", "users"],
    includeAliases: {
      process: "processes",
      processes: "processes",
      creator: "users",
      user: "users",
    },
    relationKeys: { processes: "process", users: "creator" },
    storeRules: {
      process_id: "required|exists:processes,id",
      notes: "nullable|max:255",
      status: "in:draft,active,archived",
    },
    updateRules: {
      process_id: "sometimes|exists:processes,id",
      notes: "nullable|max:255",
      status: "in:draft,active,archived",
    },
    serialize: (r) => ({
      id: num(r.id),
      process_id: num(r.process_id),
      version_number: r.version_number,
      notes: r.notes,
      status: r.status,
      created_by: num(r.created_by),
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
      ...(r.processes
        ? {
            process: {
              id: num(r.processes.id),
              process_name: r.processes.process_name,
              description: r.processes.description,
              status: r.processes.status,
              created_at: iso(r.processes.created_at),
              updated_at: iso(r.processes.updated_at),
            },
          }
        : {}),
      ...(r.users ? { creator: userResource(r.users) } : {}),
    }),
    // Server-generated: created_by = current user, version_number = max+1 per process.
    beforeCreate: async (data, userId) => {
      const { table } = await import("./db");
      const agg = await table("process_versions").aggregate({
        where: { process_id: data.process_id },
        _max: { version_number: true },
      });
      return {
        ...data,
        created_by: userId,
        version_number: (agg._max.version_number ?? 0) + 1,
      };
    },
  },

  "ptms-reports": {
    model: "ptms_reports",
    modelClass: "PtmsReport",
    label: "PTMS report",
    searchFields: ["report_number"],
    filters: ["article_id", "line_id", "factory_id", "department_id", "operator_id", "status"],
    defaultSort: { field: "created_at", order: "desc" },
    defaultInclude: [
      "articles",
      "process_versions",
      "operators",
      "factories",
      "departments",
      "production_lines",
      "users",
    ],
    // PtmsReportController store/update/show all `load()` the 7 relations.
    storeInclude: [
      "articles",
      "process_versions",
      "operators",
      "factories",
      "departments",
      "production_lines",
      "users",
    ],
    updateInclude: [
      "articles",
      "process_versions",
      "operators",
      "factories",
      "departments",
      "production_lines",
      "users",
    ],
    includeAliases: {
      article: "articles",
      articles: "articles",
      processVersion: "process_versions",
      process_version: "process_versions",
      operator: "operators",
      operators: "operators",
      factory: "factories",
      factories: "factories",
      department: "departments",
      departments: "departments",
      productionLine: "production_lines",
      production_line: "production_lines",
      creator: "users",
      user: "users",
    },
    relationKeys: {
      articles: "article",
      process_versions: "process_version",
      operators: "operator",
      factories: "factory",
      departments: "department",
      production_lines: "production_line",
      users: "creator",
    },
    storeRules: {
      article_id: "required|exists:articles,id",
      process_version_id: "required|exists:process_versions,id",
      operator_id: "required|exists:operators,id",
      factory_id: "required|exists:factories,id",
      department_id: "required|exists:departments,id",
      line_id: "required|exists:production_lines,id",
      machine_name: "nullable",
      feed_type: "nullable",
      rpm: "nullable|numeric",
      stitch_per_cm: "nullable|numeric",
      seam_width: "nullable|numeric",
      machine_delay_percent: "nullable|numeric",
      contingency_percent: "nullable|numeric",
      ra_percent: "nullable|numeric",
      machining_tmu: "nullable|numeric",
      handling_tmu: "nullable|numeric",
      bundle_tmu: "nullable|numeric",
      total_tmu: "nullable|numeric",
      bms: "nullable|numeric",
      smv: "nullable|numeric",
      status: "in:draft,final,archived",
    },
    updateRules: {
      article_id: "sometimes|exists:articles,id",
      process_version_id: "sometimes|exists:process_versions,id",
      operator_id: "sometimes|exists:operators,id",
      factory_id: "sometimes|exists:factories,id",
      department_id: "sometimes|exists:departments,id",
      line_id: "sometimes|exists:production_lines,id",
      machine_name: "nullable",
      feed_type: "nullable",
      rpm: "nullable|numeric",
      stitch_per_cm: "nullable|numeric",
      seam_width: "nullable|numeric",
      machine_delay_percent: "nullable|numeric",
      contingency_percent: "nullable|numeric",
      ra_percent: "nullable|numeric",
      machining_tmu: "nullable|numeric",
      handling_tmu: "nullable|numeric",
      bundle_tmu: "nullable|numeric",
      total_tmu: "nullable|numeric",
      bms: "nullable|numeric",
      smv: "nullable|numeric",
      status: "in:draft,final,archived",
    },
    serialize: (r) => ({
      id: num(r.id),
      report_number: r.report_number,
      article_id: num(r.article_id),
      process_version_id: num(r.process_version_id),
      operator_id: num(r.operator_id),
      factory_id: num(r.factory_id),
      department_id: num(r.department_id),
      line_id: num(r.line_id),
      created_by: num(r.created_by),
      machine_name: r.machine_name,
      feed_type: r.feed_type,
      rpm: dec2(r.rpm),
      stitch_per_cm: dec2(r.stitch_per_cm),
      seam_width: dec2(r.seam_width),
      machine_delay_percent: dec2(r.machine_delay_percent),
      contingency_percent: dec2(r.contingency_percent),
      ra_percent: dec2(r.ra_percent),
      machining_tmu: dec2(r.machining_tmu),
      handling_tmu: dec2(r.handling_tmu),
      bundle_tmu: dec2(r.bundle_tmu),
      total_tmu: dec2(r.total_tmu),
      bms: dec2(r.bms),
      smv: dec2(r.smv),
      status: r.status,
      created_at: iso(r.created_at),
      updated_at: iso(r.updated_at),
      ...(r.articles ? { article: articleResource(r.articles) } : {}),
      ...(r.process_versions
        ? { process_version: processVersionResource(r.process_versions) }
        : {}),
      ...(r.operators ? { operator: operatorResource(r.operators) } : {}),
      ...(r.factories ? { factory: factoryResource(r.factories) } : {}),
      ...(r.departments ? { department: departmentResource(r.departments) } : {}),
      ...(r.production_lines
        ? {
            production_line: {
              id: num(r.production_lines.id),
              division_id: num(r.production_lines.division_id),
              line_name: r.production_lines.line_name,
              description: r.production_lines.description,
              status: r.production_lines.status,
              created_at: iso(r.production_lines.created_at),
              updated_at: iso(r.production_lines.updated_at),
            },
          }
        : {}),
      ...(r.users ? { creator: userResource(r.users) } : {}),
    }),
    // Server-generated: created_by + report_number (Laravel model boot():
    // "PTMS-YYYY-NNNN" where NNNN = count of this year's reports + 1).
    beforeCreate: async (data, userId) => {
      const { table } = await import("./db");
      const year = new Date().getFullYear();
      const count = await table("ptms_reports").count({
        where: {
          created_at: {
            gte: new Date(`${year}-01-01T00:00:00.000Z`),
            lt: new Date(`${year + 1}-01-01T00:00:00.000Z`),
          },
        },
      });
      return {
        ...data,
        created_by: userId,
        report_number: `PTMS-${year}-${String(count + 1).padStart(4, "0")}`,
      };
    },
  },

  users: {
    model: "users",
    modelClass: "User",
    label: "User",
    searchFields: ["name", "username", "employee_number"],
    defaultInclude: ["roles"],
    includeAliases: { role: "roles", roles: "roles" },
    // UserController store/update/show all load('role').
    storeInclude: ["roles"],
    updateInclude: ["roles"],
    relationKeys: { roles: "role" },
    storeRules: {
      name: "required|max:255",
      username: "required|unique:users,username|max:255",
      employee_number: "required|unique:users,employee_number|max:20",
      password: "required|min:6|confirmed",
      role_id: "required|exists:roles,id",
      description: "nullable",
    },
    updateRules: {
      name: "sometimes|max:255",
      username: "sometimes|unique:users,username,{id}|max:255",
      employee_number: "sometimes|unique:users,employee_number,{id}|max:20",
      password: "sometimes|min:6|confirmed",
      role_id: "sometimes|exists:roles,id",
      description: "nullable",
    },
    serialize: (r) => ({
      ...userResource(r),
      ...(r.roles ? { role: roleResource(r.roles) } : {}),
    }),
    beforeCreate: (data) => ({
      ...data,
      password: bcrypt.hashSync(String(data.password), 10),
    }),
    beforeUpdate: (data) =>
      data.password !== undefined
        ? { ...data, password: bcrypt.hashSync(String(data.password), 10) }
        : data,
    destroyGuard: (row, userId) =>
      num(row.id) === userId ? "You cannot delete your own account" : null,
    updateGuard: (existing, user) => {
      // Admin cannot edit developer accounts (replica of Laravel CredentialController).
      const targetRole = existing.roles?.role_name ?? "";
      if (user.role?.role_name === "admin" && targetRole === "developer") {
        return "You cannot edit a developer account.";
      }
      return null;
    },
    allowAll: true,
  },
};

export const PTMS_STATUSES = ["draft", "final", "archived"] as const;
