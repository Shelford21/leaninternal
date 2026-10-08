export interface User {
  id: number;
  name: string;
  username: string;
  employee_number: string;
  description: string;
  role_id: number;
  role?: Role;
  created_at?: string;
  updated_at?: string;
}

export interface Role {
  id: number;
  role_name: string;
  description: string;
}

export interface Factory {
  id: number;
  factory_name: string;
  description?: string;
  created_at?: string;
  updated_at?: string;
  departments?: Department[];
}

export interface Department {
  id: number;
  factory_id: number;
  department_name: string;
  desription?: string;
  factory?: Factory;
  production_lines?: ProductionLine[];
  created_at?: string;
  updated_at?: string;
}

export interface Division {
  id: number;
  division: string;
  description?: string;
  status: string;
  created_at?: string;
  updated_at?: string;
}

export interface ProductionLine {
  id: number;
  division_id: number;
  line_name: string;
  description?: string;
  status: string;
  division?: Division;
  created_at?: string;
  updated_at?: string;
}

export interface Article {
  id: number;
  article_name: string;
  label_number: string;
  destination: string;
  description?: string;
  status: string;
  created_at?: string;
  updated_at?: string;
}

export interface Operator {
  id: number;
  employee_number: string;
  operator_name: string;
  status: string;
  created_at?: string;
  updated_at?: string;
}

export interface Process {
  id: number;
  process_name: string;
  description?: string;
  status: string;
  created_at?: string;
  updated_at?: string;
  versions?: ProcessVersion[];
  latest_version?: ProcessVersion;
}

export interface ProcessVersion {
  id: number;
  process_id: number;
  version_number: number;
  notes?: string;
  status: string;
  created_by: number;
  process?: Process;
  creator?: User;
  created_at?: string;
  updated_at?: string;
}

export interface GsdCategory {
  id: number;
  category_name: string;
  description?: string;
  status: string;
  created_at?: string;
  updated_at?: string;
  elements?: GsdElement[];
}

export interface GsdElement {
  id: number;
  gsd_category_id: number;
  element_name: string;
  description?: string;
  code: string;
  tmu: number;
  seconds: number;
  motion_sequence?: string;
  status: string;
  gsd_category?: GsdCategory;
  created_at?: string;
  updated_at?: string;
}

export interface MtmElement {
  id: number;
  element_name: string;
  description?: string;
  code: string;
  tmu: number;
  seconds: number;
  status: string;
  created_at?: string;
  updated_at?: string;
}

export interface SewingFactor {
  id: number;
  factor_name: string;
  description?: string;
  factor_value: number;
  code: string;
  status: string;
  created_at?: string;
  updated_at?: string;
}

export interface SewingStopFactor {
  id: number;
  factor_name: string;
  description?: string;
  tolerance?: number;
  factor_value: number;
  code: string;
  status: string;
  created_at?: string;
  updated_at?: string;
}

export interface PtmsReport {
  id: number;
  report_number: string;
  article_id: number;
  process_version_id: number;
  operator_id: number;
  factory_id: number;
  department_id: number;
  line_id: number;
  created_by: number;
  machine_name?: string;
  feed_type?: string;
  rpm?: number;
  stitch_per_cm?: number;
  seam_width?: number;
  machine_delay_percent?: number;
  contingency_percent?: number;
  ra_percent?: number;
  machining_tmu?: number;
  handling_tmu?: number;
  bundle_tmu?: number;
  total_tmu?: number;
  bms?: number;
  smv?: number;
  status: string;
  remark?: string;
  article?: Article;
  process_version?: ProcessVersion;
  operator?: Operator;
  factory?: Factory;
  department?: Department;
  production_line?: ProductionLine;
  creator?: User;
  created_at?: string;
  updated_at?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

export interface ApiResponse<T> {
  data: T;
  message?: string;
}

export interface LoginRequest {
  username: string;
  password: string;
}

export interface LoginResponse {
  user: User;
  token: string;
  message: string;
}

export interface DashboardStats {
  factories: number;
  departments: number;
  production_lines: number;
  articles: number;
  operators: number;
  gsd_categories: number;
  gsd_elements: number;
  mtm_elements: number;
  sewing_factors: number;
  sewing_stop_factors: number;
  processes: number;
  process_versions: number;
  ptms_reports: number;
  roles: number;
  users: number;
  average_smv: number | null;
}

export interface QueryParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_order?: "asc" | "desc";
  [key: string]: string | number | undefined;
}
