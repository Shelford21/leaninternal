/**
 * Data Master configuration — exact parity with the Laravel Blade Data Master area.
 * Every label, header, placeholder, alert and message below is copied VERBATIM from
 * resources/views/master-data/*.blade.php and routes/web.php (including typos such as
 * "desription", "Pkwtt" and inconsistent capitalization). Do not "fix" any string here.
 */

export type Variant =
  | 'simple'
  | 'factories'
  | 'departments'
  | 'destinations'
  | 'production-lines'
  | 'mechanics'
  | 'articles'
  | 'gsd-elements'
  | 'operators'
  | 'processes';

export type CellRender = 'text' | 'desc' | 'status';

export interface ColumnDef {
  /** row property path (supports "relation.field") */
  key: string;
  /** exact table header text */
  header: string;
  /** sortable key (must appear in MasterDef.sortable); omitted = not sortable */
  sortKey?: string;
  render?: CellRender;
  /** display uppercase (mirrors inline style="text-transform:uppercase") */
  upper?: boolean;
  width?: string;
}

export interface FieldDef {
  name: string;
  label: string;
  type: 'text' | 'textarea';
  upper: boolean;
  required: boolean;
  maxLength: number;
  placeholder?: string;
}

export interface MasterDef {
  slug: string;
  /** Prisma model (= table) name */
  model: string;
  variant: Variant;
  displayName: string;
  /** exact page header, e.g. "Data Masters / Skill Gradings" */
  header: string;
  nameField: string;
  nameLabel: string;
  descField: string;
  descLabel: string;
  defaultSort: string;
  sortable: string[];
  columns: ColumnDef[];
  fields: FieldDef[];
  filterColumns: { value: string; label: string }[];
  totalRecords: boolean;
  searchPlaceholder: string;
  searchUppercase: boolean;
  showInactiveLabel: string;
  modalNew: string;
  modalEdit: string;
  modalImport: string;
  submitNew: string;
  submitEdit: string;
  actionHeader: string;
  emptyText: string;
  emptyColspan: number;
  bulkCounter: 'records' | 'selected';
  bulkCancel: string;
  zeroAlert: string;
  /** "{n}" is replaced with the count */
  confirmTemplate: string;
  /** lowercase Excel headers expected by the importer (help text) */
  importHeaders: string[];
  importHelp: 'chips' | 'footnote';
  importFootnote?: string;
  importFileLabel: string;
  exportFile: string;
  exportHeaders: string[];
  msg: {
    created: string;
    updated: string;
    /** non-null = has a ptmsReports() dependency guard on single deactivate */
    deactivateGuard: string | null;
    deactivated: string;
    bulkEmpty: string;
    bulkDone: string;
    /** optional suffix when protected records are skipped (operators bulk only) */
    bulkSkip?: string;
    hardDone: string;
    hardSkip: string;
    imported: string;
    logModule: string;
    /** activity-log verb for create/update/deactivate, e.g. "Created employee: {value}" */
    logCreate: string;
    logUpdate: string;
    logDeactivate: string;
    logBulk: string;
    logHard: string;
    logImport: string;
    /** null = export writes NO activity log (simple-master quirk) */
    logExport: string | null;
  };
}

/* ------------------------------------------------------------------ */
/* helpers                                                             */
/* ------------------------------------------------------------------ */

const f = (
  name: string,
  label: string,
  required: boolean,
  maxLength: number,
  upper = true,
  placeholder?: string
): FieldDef => ({
  name,
  label,
  type: name === 'description' || name === 'desription' ? 'textarea' : 'text',
  upper,
  required,
  maxLength,
  placeholder,
});

const simpleCols = (nameHeader: string): ColumnDef[] => [
  { key: '__name__', header: nameHeader, sortKey: '__name__', upper: true },
  { key: 'description', header: 'Descriptions', sortKey: 'description', render: 'desc' },
  { key: 'status', header: 'Status', sortKey: 'status', render: 'status' },
];

/** display names + name fields for the 13 simple masters (verbatim from simple-master.blade.php) */
const SIMPLE_MASTERS: {
  slug: string;
  model: string;
  displayName: string;
  nameField: string;
  nameLabel: string;
}[] = [
  { slug: 'skill-gradings', model: 'skill_gradings', displayName: 'Skill Gradings', nameField: 'skill_grade', nameLabel: 'Skill Grade' },
  { slug: 'divisions', model: 'divisions', displayName: 'Divisions', nameField: 'division', nameLabel: 'Division' },
  { slug: 'sections', model: 'sections', displayName: 'Sections', nameField: 'section', nameLabel: 'Section' },
  { slug: 'machine-types', model: 'machine_types', displayName: 'Machine Types', nameField: 'machine_type', nameLabel: 'Machine Type' },
  { slug: 'components-panels', model: 'components_panels', displayName: 'Components/Panels', nameField: 'component_panel', nameLabel: 'Component Panel' },
  { slug: 'machine-numbers', model: 'machine_numbers', displayName: 'Machine Numbers', nameField: 'machine_number', nameLabel: 'Machine Number' },
  { slug: 'shifts', model: 'shifts', displayName: 'Shifts', nameField: 'shift', nameLabel: 'Shift' },
  { slug: 'failure-modes', model: 'failure_modes', displayName: 'Failure Modes / Kerusakan', nameField: 'failure_mode', nameLabel: 'Failure Mode' },
  { slug: 'spare-parts', model: 'spare_parts', displayName: 'Spare Parts', nameField: 'spare_part', nameLabel: 'Spare Part' },
  { slug: 'genders', model: 'genders', displayName: 'Genders', nameField: 'gender', nameLabel: 'Gender' },
  { slug: 'production-roles', model: 'production_roles', displayName: 'Production Roles', nameField: 'production_role', nameLabel: 'Production Role' },
  { slug: 'educational-levels', model: 'educational_levels', displayName: 'Educational Level', nameField: 'level', nameLabel: 'Level' },
  { slug: 'status-pkwtt', model: 'status_pkwtt', displayName: 'Status PKWTT', nameField: 'pkwtt', nameLabel: 'Pkwtt' },
];

function simpleDef(s: (typeof SIMPLE_MASTERS)[number]): MasterDef {
  const mod = s.nameField.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
  return {
    slug: s.slug,
    model: s.model,
    variant: 'simple',
    displayName: s.displayName,
    header: `Data Masters / ${s.displayName}`,
    nameField: s.nameField,
    nameLabel: s.nameLabel,
    descField: 'description',
    descLabel: 'Descriptions',
    defaultSort: s.nameField,
    sortable: [s.nameField, 'description', 'status'],
    columns: simpleCols(s.nameLabel),
    fields: [f(s.nameField, s.nameLabel, true, 200), f('description', 'Descriptions', false, 255)],
    filterColumns: [{ value: s.nameField, label: s.nameLabel }],
    totalRecords: true,
    searchPlaceholder: `Search ${s.displayName.toLowerCase()}...`,
    searchUppercase: true,
    showInactiveLabel: 'Show Inactive',
    modalNew: `New ${s.displayName}`,
    modalEdit: `Edit ${s.displayName}`,
    modalImport: `Import ${s.displayName}`,
    submitNew: 'Save',
    submitEdit: 'Save',
    actionHeader: 'Action',
    emptyText: 'No records found.',
    emptyColspan: 6,
    bulkCounter: 'records',
    bulkCancel: 'Cancel Delete',
    zeroAlert: 'Please select at least one record to delete.',
    confirmTemplate: 'Are you sure you want to mark {n} record(s) as inactive?',
    importHeaders: [s.nameLabel.toLowerCase(), 'descriptions'],
    importHelp: 'chips',
    importFileLabel: 'Excel File (.xlsx, .xls, .csv)',
    exportFile: `${s.slug}.xlsx`,
    exportHeaders: ['No', s.nameLabel, 'Descriptions'],
    msg: {
      created: `${s.nameField} created successfully.`,
      updated: `${s.nameField} updated successfully.`,
      deactivateGuard: null,
      deactivated: 'Record marked as inactive.',
      bulkEmpty: 'No records selected.',
      bulkDone: '{n} record(s) marked as inactive.',
      hardDone: '{n} record(s) permanently deleted.',
      hardSkip: ' {k} skipped (active records cannot be hard deleted).',
      imported: 'Imported {n} records.',
      logModule: mod,
      logCreate: `Created ${s.slug}: {value}`,
      logUpdate: `Updated ${s.slug}: {value}`,
      logDeactivate: `Deactivated ${s.slug}: {value}`,
      logBulk: `Bulk deactivated {n} ${mod}`,
      logHard: `Hard deleted {n} ${mod}`,
      logImport: `Imported {n} ${mod} from Excel`,
      logExport: null,
    },
  };
}

/* ------------------------------------------------------------------ */
/* the 22 masters                                                      */
/* ------------------------------------------------------------------ */

export const MASTERS: Record<string, MasterDef> = {};

for (const s of SIMPLE_MASTERS) MASTERS[s.slug] = simpleDef(s);

MASTERS.factories = {
  slug: 'factories',
  model: 'factories',
  variant: 'factories',
  displayName: 'Factories',
  header: 'Data Masters / Factories',
  nameField: 'factory_name',
  nameLabel: 'Factory Name',
  descField: 'description',
  descLabel: 'Descriptions',
  defaultSort: 'factory_name',
  sortable: ['factory_name', 'description', 'status'],
  columns: [
    { key: 'factory_name', header: 'Factory Name', sortKey: 'factory_name', upper: true },
    { key: 'description', header: 'Descriptions', sortKey: 'description', render: 'desc' },
    { key: 'status', header: 'Status', sortKey: 'status', render: 'status' },
  ],
  fields: [f('factory_name', 'Factory Name', true, 100), f('description', 'Descriptions', false, 255)],
  filterColumns: [{ value: 'factory_name', label: 'Factory Name' }],
  totalRecords: true,
  searchPlaceholder: 'Search factories...',
  searchUppercase: true,
  showInactiveLabel: 'Show Inactive',
  modalNew: 'New Factory',
  modalEdit: 'Edit Factory',
  modalImport: 'Import Factories',
  submitNew: 'Save',
  submitEdit: 'Save',
  actionHeader: 'Action',
  emptyText: 'No factories found.',
  emptyColspan: 6,
  bulkCounter: 'selected',
  bulkCancel: 'Cancel',
  zeroAlert: 'No records selected.',
  confirmTemplate: 'Mark {n} factory(ies) as inactive?',
  importHeaders: ['factory name', 'descriptions'],
  importHelp: 'chips',
  importFileLabel: 'Excel File (.xlsx, .xls, .csv)',
  exportFile: 'factories.xlsx',
  exportHeaders: ['No', 'Factory Name', 'Descriptions'],
  msg: {
    created: 'Factory created successfully.',
    updated: 'Factory updated successfully.',
    deactivateGuard: null,
    deactivated: 'Factory marked as inactive.',
    bulkEmpty: 'No records selected.',
    bulkDone: '{n} record(s) marked as inactive.',
    hardDone: '{n} factory/factories permanently deleted.',
    hardSkip: ' {k} skipped (active or have dependencies).',
    imported: 'Imported {n} factories.',
    logModule: 'Factories',
    logCreate: 'Created factory: {value}',
    logUpdate: 'Updated factory: {value}',
    logDeactivate: 'Deactivated factory: {value}',
    logBulk: 'Bulk deactivated {n} factories',
    logHard: 'Hard deleted {n} factories',
    logImport: 'Imported {n} factories from Excel',
    logExport: 'Exported factories to Excel',
  },
};

MASTERS.departments = {
  slug: 'departments',
  model: 'departments',
  variant: 'departments',
  displayName: 'Departments',
  header: 'Data Masters / Departments',
  nameField: 'department_name',
  nameLabel: 'Department Name',
  descField: 'desription', // typo is REAL (DB column is "desription")
  descLabel: 'Descriptions',
  defaultSort: 'department_name',
  sortable: ['department_name', 'desription', 'status'],
  columns: [
    { key: 'department_name', header: 'Department Name', sortKey: 'department_name', upper: true },
    { key: 'desription', header: 'Descriptions', sortKey: 'desription', render: 'desc' },
    { key: 'status', header: 'Status', sortKey: 'status', render: 'status' },
  ],
  fields: [f('department_name', 'Department Name', true, 100), f('desription', 'Descriptions', false, 255, false)],
  filterColumns: [{ value: 'department_name', label: 'Department Name' }],
  totalRecords: true,
  searchPlaceholder: 'Search departments...',
  searchUppercase: true,
  showInactiveLabel: 'Show Inactive',
  modalNew: 'New Department',
  modalEdit: 'Edit Department',
  modalImport: 'Import Departments',
  submitNew: 'Save',
  submitEdit: 'Save',
  actionHeader: 'Action',
  emptyText: 'No departments found.',
  emptyColspan: 6,
  bulkCounter: 'records',
  bulkCancel: 'Cancel Delete',
  zeroAlert: 'No records selected.',
  confirmTemplate: 'Mark {n} department(s) as inactive?',
  importHeaders: ['department name', 'descriptions', 'factory'],
  importHelp: 'chips',
  importFileLabel: 'Excel File (.xlsx, .xls, .csv)',
  exportFile: 'departments.xlsx',
  exportHeaders: ['No', 'Department Name', 'Descriptions'],
  msg: {
    created: 'Department created successfully.',
    updated: 'Department updated successfully.',
    deactivateGuard: null,
    deactivated: 'Department marked as inactive.',
    bulkEmpty: 'No records selected.',
    bulkDone: '{n} record(s) marked as inactive.',
    hardDone: '{n} department(s) permanently deleted.',
    hardSkip: ' {k} skipped (active or have dependencies).',
    imported: 'Imported {n} departments.',
    logModule: 'Departments',
    logCreate: 'Created department: {value}',
    logUpdate: 'Updated department: {value}',
    logDeactivate: 'Deactivated department: {value}',
    logBulk: 'Bulk deactivated {n} departments',
    logHard: 'Hard deleted {n} departments',
    logImport: 'Imported {n} departments from Excel',
    logExport: 'Exported departments to Excel',
  },
};

MASTERS.destinations = {
  slug: 'destinations',
  model: 'destinations',
  variant: 'destinations',
  displayName: 'Destinations',
  header: 'Data Masters / Destinations',
  nameField: 'destination',
  nameLabel: 'Destination',
  descField: 'description',
  descLabel: 'Descriptions',
  defaultSort: 'destination',
  sortable: ['destination', 'description', 'status'],
  columns: [
    { key: 'destination', header: 'Destination', sortKey: 'destination', upper: true },
    { key: 'description', header: 'Descriptions', sortKey: 'description', render: 'desc' },
    { key: 'status', header: 'Status', sortKey: 'status', render: 'status' },
  ],
  fields: [f('destination', 'Destination', true, 100), f('description', 'Descriptions', false, 255)],
  filterColumns: [{ value: 'destination', label: 'Destination' }],
  totalRecords: true,
  searchPlaceholder: 'Search destinations...',
  searchUppercase: true,
  showInactiveLabel: 'Show Inactive',
  modalNew: 'New Destination',
  modalEdit: 'Edit Destination',
  modalImport: 'Import Destinations',
  submitNew: 'Save',
  submitEdit: 'Save',
  actionHeader: 'Action',
  emptyText: 'No destinations found.',
  emptyColspan: 6,
  bulkCounter: 'records',
  bulkCancel: 'Cancel',
  zeroAlert: 'No records selected.',
  confirmTemplate: 'Mark {n} destination(s) as inactive?',
  importHeaders: ['destination', 'descriptions'],
  importHelp: 'footnote',
  importFootnote: 'Columns: Destination, Descriptions',
  importFileLabel: 'Excel File (.xlsx, .xls, .csv)',
  exportFile: 'destinations.xlsx',
  exportHeaders: ['No', 'Destination', 'Descriptions'],
  msg: {
    created: 'Destination created successfully.',
    updated: 'Destination updated successfully.',
    deactivateGuard: null,
    deactivated: 'Destination marked as inactive.',
    bulkEmpty: 'No records selected.',
    bulkDone: '{n} record(s) marked as inactive.',
    hardDone: '{n} destination(s) permanently deleted.',
    hardSkip: ' {k} skipped (active records cannot be hard deleted).',
    imported: 'Imported {n} destinations.',
    logModule: 'Destinations',
    logCreate: 'Created destination: {value}',
    logUpdate: 'Updated destination: {value}',
    logDeactivate: 'Deactivated destination: {value}',
    logBulk: 'Bulk deactivated {n} destinations',
    logHard: 'Hard deleted {n} destinations',
    logImport: 'Imported {n} destinations from Excel',
    logExport: 'Exported destinations to Excel',
  },
};

MASTERS['production-lines'] = {
  slug: 'production-lines',
  model: 'production_lines',
  variant: 'production-lines',
  displayName: 'Production Lines',
  header: 'Data Masters / Production Lines',
  nameField: 'line_name',
  nameLabel: 'Line Name',
  descField: 'description',
  descLabel: 'Descriptions',
  defaultSort: 'line_name',
  sortable: ['line_name', 'description', 'status'],
  columns: [
    { key: 'line_name', header: 'Line Name', sortKey: 'line_name', upper: true },
    { key: 'description', header: 'Descriptions', sortKey: 'description', render: 'desc' },
    { key: 'status', header: 'Status', sortKey: 'status', render: 'status' },
  ],
  fields: [f('line_name', 'Line Name', true, 100), f('description', 'Descriptions', false, 255)],
  filterColumns: [{ value: 'line_name', label: 'Line Name' }],
  totalRecords: true,
  searchPlaceholder: 'Search production lines...',
  searchUppercase: true,
  showInactiveLabel: 'Show Inactive',
  modalNew: 'New Production Line',
  modalEdit: 'Edit Production Line',
  modalImport: 'Import Production Lines',
  submitNew: 'Save',
  submitEdit: 'Save',
  actionHeader: 'Action',
  emptyText: 'No production lines found.',
  emptyColspan: 6,
  bulkCounter: 'records',
  bulkCancel: 'Cancel',
  zeroAlert: 'No records selected.',
  confirmTemplate: 'Mark {n} production line(s) as inactive?',
  importHeaders: ['line name', 'descriptions'],
  importHelp: 'footnote',
  importFootnote: 'Columns: Line Name, Descriptions',
  importFileLabel: 'Excel File (.xlsx)',
  exportFile: 'production-lines.xlsx',
  exportHeaders: ['No', 'Line Name', 'Descriptions'],
  msg: {
    created: 'Production line created successfully.',
    updated: 'Production line updated successfully.',
    deactivateGuard:
      'This production line cannot be deactivated because historical reports reference the record.',
    deactivated: 'Production line deactivated successfully.',
    bulkEmpty: 'No records selected.',
    bulkDone: '{n} record(s) marked as inactive.',
    hardDone: '{n} production line(s) permanently deleted.',
    hardSkip: ' {k} skipped (active or have dependencies).',
    imported: 'Imported {n} production lines.',
    logModule: 'Production Lines',
    logCreate: 'Created production line: {value}',
    logUpdate: 'Updated production line: {value}',
    logDeactivate: 'Deactivated production line: {value}',
    logBulk: 'Bulk deactivated {n} production lines',
    logHard: 'Hard deleted {n} production lines',
    logImport: 'Imported {n} production lines from Excel',
    logExport: 'Exported production lines to Excel',
  },
};

MASTERS.mechanics = {
  slug: 'mechanics',
  model: 'mechanics',
  variant: 'mechanics',
  displayName: 'Mechanics',
  header: 'Data Masters / Mechanics',
  nameField: 'mechanic',
  nameLabel: 'Mechanic',
  descField: 'description',
  descLabel: 'Descriptions',
  defaultSort: 'mechanic',
  sortable: ['nik_karyawan', 'mechanic', 'description', 'status'],
  columns: [
    { key: 'nik_karyawan', header: 'NIK KARYAWAN', sortKey: 'nik_karyawan', upper: true },
    { key: 'mechanic', header: 'Mechanic', sortKey: 'mechanic', upper: true },
    { key: 'description', header: 'Descriptions', sortKey: 'description', render: 'desc' },
    { key: 'status', header: 'Status', sortKey: 'status', render: 'status' },
  ],
  fields: [
    f('nik_karyawan', 'NIK KARYAWAN', false, 50, true, 'Optional'),
    f('mechanic', 'Mechanic', true, 200),
    { name: 'description', label: 'Descriptions', type: 'text', upper: true, required: false, maxLength: 255, placeholder: 'Optional' },
  ],
  filterColumns: [
    { value: 'nik_karyawan', label: 'NIK KARYAWAN' },
    { value: 'mechanic', label: 'Mechanic' },
  ],
  totalRecords: true,
  searchPlaceholder: 'Search mechanics...',
  searchUppercase: true,
  showInactiveLabel: 'Show Inactive',
  modalNew: 'New Mechanic',
  modalEdit: 'Edit Mechanic',
  modalImport: 'Import Mechanics',
  submitNew: 'Save',
  submitEdit: 'Save',
  actionHeader: 'Actions',
  emptyText: 'No mechanics found.',
  emptyColspan: 7,
  bulkCounter: 'selected',
  bulkCancel: 'Cancel',
  zeroAlert: 'No records selected.',
  confirmTemplate: 'Mark {n} mechanic(s) as inactive?',
  importHeaders: ['nik karyawan', 'mechanic', 'descriptions'],
  importHelp: 'footnote',
  importFootnote: 'Columns: NIK KARYAWAN, Mechanic, Descriptions',
  importFileLabel: 'Excel File (.xlsx, .xls, .csv)',
  exportFile: 'mechanics.xlsx',
  exportHeaders: ['No', 'NIK KARYAWAN', 'Mechanic', 'Descriptions'],
  msg: {
    created: 'Mechanic created successfully.',
    updated: 'Mechanic updated successfully.',
    deactivateGuard: null,
    deactivated: 'Record marked as inactive.',
    bulkEmpty: 'No records selected.',
    bulkDone: '{n} record(s) marked as inactive.',
    hardDone: '{n} mechanic(s) permanently deleted.',
    hardSkip: ' {k} skipped (active or have dependencies).',
    imported: 'Imported {n} mechanics.',
    logModule: 'Mechanics',
    logCreate: 'Created mechanic: {value}',
    logUpdate: 'Updated mechanic: {value}',
    logDeactivate: 'Deactivated mechanic: {value}',
    logBulk: 'Bulk deactivated {n} mechanics',
    logHard: 'Hard deleted {n} mechanics',
    logImport: 'Imported {n} mechanics from Excel',
    logExport: 'Exported mechanics to Excel',
  },
};

MASTERS.articles = {
  slug: 'articles',
  model: 'articles',
  variant: 'articles',
  displayName: 'Articles',
  header: 'Data Masters / Articles',
  nameField: 'article_name',
  nameLabel: 'Nama Article',
  descField: 'description',
  descLabel: 'Description',
  defaultSort: 'article_name',
  sortable: ['article_name', 'status'],
  columns: [
    { key: 'photo_path', header: 'Photo' },
    { key: 'article_name', header: 'Nama Article', sortKey: 'article_name', upper: true },
    { key: 'status', header: 'Status', sortKey: 'status', render: 'status' },
  ],
  fields: [
    f('article_name', 'Nama Articles', true, 150),
    { name: 'description', label: 'Description', type: 'text', upper: true, required: false, maxLength: 255 },
  ],
  filterColumns: [{ value: 'article_name', label: 'Nama Article' }],
  totalRecords: true,
  searchPlaceholder: 'Search articles...',
  searchUppercase: true,
  showInactiveLabel: 'Show Inactive',
  modalNew: 'New Article',
  modalEdit: 'Edit Article',
  modalImport: 'Import Articles',
  submitNew: 'Save',
  submitEdit: 'Save',
  actionHeader: 'Action',
  emptyText: 'No articles found.',
  emptyColspan: 7,
  bulkCounter: 'records',
  bulkCancel: 'Cancel',
  zeroAlert: 'No records selected.',
  confirmTemplate: 'Mark {n} article(s) as inactive?',
  importHeaders: ['article name', 'label number', 'destination', 'description'],
  importHelp: 'footnote',
  importFootnote: 'Columns: Article Name',
  importFileLabel: 'Excel File (.xlsx)',
  exportFile: 'articles.xlsx',
  exportHeaders: ['No', 'Article Name'],
  msg: {
    created: 'Article created successfully.',
    updated: 'Article updated successfully.',
    deactivateGuard:
      'This article cannot be deactivated because historical reports reference the record.',
    deactivated: 'Article deactivated successfully.',
    bulkEmpty: 'No records selected.',
    bulkDone: '{n} record(s) marked as inactive.',
    hardDone: '{n} article(s) permanently deleted.',
    hardSkip: ' {k} skipped (active or have dependencies).',
    imported: 'Imported {n} articles.',
    logModule: 'Articles',
    logCreate: 'Created article: {value}',
    logUpdate: 'Updated article: {value}',
    logDeactivate: 'Deactivated article: {value}',
    logBulk: 'Bulk deactivated {n} articles',
    logHard: 'Hard deleted {n} articles',
    logImport: 'Imported {n} articles from Excel',
    logExport: 'Exported articles to Excel',
  },
};

MASTERS['gsd-elements'] = {
  slug: 'gsd-elements',
  model: 'gsd_elements',
  variant: 'gsd-elements',
  displayName: 'GSD Elements',
  header: 'Data Masters / GSD Elements',
  nameField: 'element_name',
  nameLabel: 'Element Name',
  descField: 'description',
  descLabel: 'Description',
  defaultSort: 'element_name',
  sortable: ['element_name', 'description', 'code', 'tmu', 'seconds', 'motion_sequence', 'status'],
  columns: [
    { key: 'element_name', header: 'Element Name', sortKey: 'element_name', upper: true },
    { key: 'description', header: 'Description', sortKey: 'description', render: 'desc' },
    { key: 'code', header: 'Code', sortKey: 'code', upper: true },
    { key: 'tmu', header: 'TMU', sortKey: 'tmu' },
    { key: 'seconds', header: 'Seconds', sortKey: 'seconds' },
    { key: 'motion_sequence', header: 'Motion Sequence', sortKey: 'motion_sequence' },
    { key: 'status', header: 'Status', sortKey: 'status', render: 'status' },
  ],
  fields: [f('element_name', 'Element Name', true, 200)],
  filterColumns: [
    { value: 'element_name', label: 'Element Name' },
    { value: 'code', label: 'Code' },
    { value: 'motion_sequence', label: 'Motion Sequence' },
  ],
  totalRecords: false,
  searchPlaceholder: 'Search GSD elements...',
  searchUppercase: true,
  showInactiveLabel: 'Show inactive',
  modalNew: 'New GSD Element',
  modalEdit: 'Edit GSD Element',
  modalImport: 'Import GSD Elements',
  submitNew: 'Create',
  submitEdit: 'Update',
  actionHeader: 'Action',
  emptyText: 'No GSD elements found.',
  emptyColspan: 10,
  bulkCounter: 'records',
  bulkCancel: 'Cancel Delete',
  zeroAlert: 'No records selected.',
  confirmTemplate: 'Mark {n} GSD element(s) as inactive?',
  importHeaders: ['element name', 'code', 'tmu', 'seconds', 'motion sequence', 'descriptions', 'category'],
  importHelp: 'footnote',
  importFootnote: 'Columns: Element Name, Code, TMU, Seconds, Motion Sequence, Category, Descriptions',
  importFileLabel: 'Excel File (.xlsx, .xls, .csv)',
  exportFile: 'gsd-elements.xlsx',
  exportHeaders: ['No', 'Element Name', 'Code', 'TMU', 'Seconds', 'Motion Sequence', 'Category', 'Descriptions'],
  msg: {
    created: 'GSD Element created successfully.',
    updated: 'GSD Element updated successfully.',
    deactivateGuard: null,
    deactivated: 'GSD Element marked as inactive.',
    bulkEmpty: 'No records selected.',
    bulkDone: '{n} record(s) marked as inactive.',
    hardDone: '{n} GSD element(s) permanently deleted.',
    hardSkip: ' {k} skipped (active records cannot be hard deleted).',
    imported: 'Imported {n} GSD elements.',
    logModule: 'GSD Elements',
    logCreate: 'Created GSD element: {value}',
    logUpdate: 'Updated GSD element: {value}',
    logDeactivate: 'Deactivated GSD element: {value}',
    logBulk: 'Bulk deactivated {n} GSD elements',
    logHard: 'Hard deleted {n} GSD elements',
    logImport: 'Imported {n} GSD elements from Excel',
    logExport: 'Exported GSD elements to Excel',
  },
};

MASTERS.operators = {
  slug: 'operators',
  model: 'operators',
  variant: 'operators',
  displayName: 'Employees',
  header: 'Data Masters / Employees',
  nameField: 'operator_name',
  nameLabel: 'Nama',
  descField: 'description',
  descLabel: 'Description',
  defaultSort: 'operator_name',
  sortable: [
    'operator_name',
    'nik_karyawan',
    'gender',
    'role',
    'factory',
    'department',
    'division',
    'section',
    'line',
    'status_pkwtt',
    'educational_level',
    'start_date',
    'date_of_birth',
    'status',
  ],
  columns: [],
  fields: [],
  filterColumns: [
    { value: 'operator_name', label: 'Nama' },
    { value: 'gender', label: 'Gender' },
    { value: 'role', label: 'Role' },
    { value: 'status_pkwtt', label: 'Status PKWTT' },
    { value: 'educational_level', label: 'Educational Level' },
    { value: 'factory', label: 'Factory' },
    { value: 'department', label: 'Department' },
    { value: 'division', label: 'Division' },
    { value: 'section', label: 'Section' },
    { value: 'line', label: 'Line' },
    { value: 'start_date', label: 'Start Date' },
    { value: 'date_of_birth', label: 'Date of Birth' },
  ],
  totalRecords: true,
  searchPlaceholder: 'Search employees...',
  searchUppercase: true,
  showInactiveLabel: 'Show Inactive',
  modalNew: 'New Employee',
  modalEdit: 'Edit Employee',
  modalImport: 'Import Employees',
  submitNew: 'Save',
  submitEdit: 'Save',
  actionHeader: 'Actions',
  emptyText: 'No employees found.',
  emptyColspan: 19,
  bulkCounter: 'records',
  bulkCancel: 'Cancel',
  zeroAlert: 'Please select at least one record.',
  confirmTemplate: 'Are you sure you want to mark {n} employee(s) as inactive?',
  importHeaders: [
    'operator name', 'nik karyawan', 'gender', 'role', 'status pkwtt', 'educational level',
    'start date', 'date of birth', 'factory', 'department', 'division', 'section', 'line',
  ],
  importHelp: 'footnote',
  importFootnote:
    'Columns: Employee Name, NIK Karyawan, Gender, Role, Status PKWTT, Educational Level, Factory, Department, Division, Section, Line, Start Date, Date of Birth',
  importFileLabel: 'Excel File (.xlsx, .xls, .csv)',
  exportFile: 'employees.xlsx',
  exportHeaders: [
    'No', 'Employee Name', 'NIK Karyawan', 'Gender', 'Role', 'Factory', 'Department',
    'Division', 'Section', 'Line', 'Status PKWTT', 'Educational Level', 'Start Date', 'Date of Birth',
  ],
  msg: {
    created: 'Employee created successfully.',
    updated: 'Employee updated successfully.',
    deactivateGuard:
      'This employee cannot be deactivated because historical reports reference the record.',
    deactivated: 'Employee deactivated successfully.',
    bulkEmpty: 'No records selected.',
    bulkDone: '{n} employee(s) marked as inactive.',
    bulkSkip: ' {k} skipped (have historical reports).',
    hardDone: '{n} employee(s) permanently deleted.',
    hardSkip: ' {k} skipped (active or have dependencies).',
    imported: 'Imported {n} employees.',
    logModule: 'Employees',
    logCreate: 'Created employee: {value}',
    logUpdate: 'Updated employee: {value}',
    logDeactivate: 'Deactivated employee: {value}',
    logBulk: 'Bulk deactivated {n} employees',
    logHard: 'Hard deleted {n} employees',
    logImport: 'Imported {n} employees from Excel',
    logExport: 'Exported employees to Excel',
  },
};

MASTERS.processes = {
  slug: 'processes',
  model: 'processes',
  variant: 'processes',
  displayName: 'Processes',
  header: 'Data Masters / Processes',
  nameField: 'process_name',
  nameLabel: 'Process Name',
  descField: 'description',
  descLabel: 'Description',
  defaultSort: 'process_name',
  sortable: ['process_name', 'status'],
  columns: [
    { key: 'process_name', header: 'Process Name', sortKey: 'process_name', upper: true },
    { key: '__versions__', header: 'Process Version' },
    { key: '__gsd__', header: 'GSD Elements' },
    { key: 'status', header: 'Status', sortKey: 'status', render: 'status' },
  ],
  fields: [],
  filterColumns: [
    { value: 'process_name', label: 'Process' },
    { value: 'version_number', label: 'Version' },
    { value: 'gsd_code', label: 'GSD Code' },
  ],
  totalRecords: true,
  searchPlaceholder: 'Search processes...',
  searchUppercase: true,
  showInactiveLabel: 'Show Inactive',
  modalNew: 'New Process',
  modalEdit: 'Edit Process',
  modalImport: 'Import Processes',
  submitNew: 'Save',
  submitEdit: 'Save',
  actionHeader: 'Action',
  emptyText: 'No process data found.',
  emptyColspan: 7,
  bulkCounter: 'records',
  bulkCancel: 'Cancel Delete',
  zeroAlert: 'No records selected.',
  confirmTemplate: 'Mark {n} process(es) as inactive?',
  importHeaders: ['process name', 'version'],
  importHelp: 'footnote',
  importFootnote: 'Columns: Process Name, Version',
  importFileLabel: 'Excel File (.xlsx, .xls, .csv)',
  exportFile: 'processes.xlsx',
  exportHeaders: ['No', 'Process Name', 'Version', 'GSD Codes'],
  msg: {
    created: 'Process created successfully.',
    updated: 'Process updated successfully.',
    deactivateGuard:
      'This process cannot be deactivated because it is used by historical reports.',
    deactivated: 'Process deactivated successfully.',
    bulkEmpty: 'No records selected.',
    bulkDone: '{n} record(s) marked as inactive.',
    hardDone: '{n} process(es) permanently deleted.',
    hardSkip: ' {k} skipped (active or have dependencies).',
    imported: 'Imported {n} processes.',
    logModule: 'Processes',
    logCreate: 'Created process: {value}',
    logUpdate: 'Updated process: {value}',
    logDeactivate: 'Deactivated process: {value}',
    logBulk: 'Bulk deactivated {n} processes',
    logHard: 'Hard deleted {n} processes',
    logImport: 'Imported {n} processes from Excel',
    logExport: 'Exported processes to Excel',
  },
};

/** ordered slug list for the sidebar (Data Masters group, Laravel order) */
export const MASTER_SLUGS: string[] = [
  'processes',
  'operators',
  'articles',
  'gsd-elements',
  'factories',
  'departments',
  'destinations',
  'production-lines',
  'skill-gradings',
  'divisions',
  'sections',
  'machine-types',
  'components-panels',
  'machine-numbers',
  'shifts',
  'failure-modes',
  'mechanics',
  'spare-parts',
  'genders',
  'production-roles',
  'educational-levels',
  'status-pkwtt',
];

/* ------------------------------------------------------------------ */
/* central hard-delete page (routes/web.php lines 139-223, verbatim)   */
/* ------------------------------------------------------------------ */

export const HARD_DELETE_CARDS: { key: string; label: string; model: string }[] = [
  { key: 'operators', label: 'Operators', model: 'operators' },
  { key: 'processes', label: 'Processes', model: 'processes' },
  { key: 'destinations', label: 'Destinations', model: 'destinations' },
  { key: 'articles', label: 'Articles', model: 'articles' },
  { key: 'gsd_elements', label: 'GSD Elements', model: 'gsd_elements' },
  { key: 'factories', label: 'Factories', model: 'factories' },
  { key: 'departments', label: 'Departments', model: 'departments' },
  { key: 'divisions', label: 'Divisions', model: 'divisions' },
  { key: 'sections', label: 'Sections', model: 'sections' },
  { key: 'production_lines', label: 'Production Lines', model: 'production_lines' },
  { key: 'mechanics', label: 'Mechanics', model: 'mechanics' },
  { key: 'failure_modes', label: 'Failure Modes', model: 'failure_modes' },
  { key: 'spare_parts', label: 'Spare Parts', model: 'spare_parts' },
  { key: 'machine_types', label: 'Machine Types', model: 'machine_types' },
  { key: 'machine_numbers', label: 'Machine Numbers', model: 'machine_numbers' },
  { key: 'skill_gradings', label: 'Skill Gradings', model: 'skill_gradings' },
  { key: 'components_panels', label: 'Components Panels', model: 'components_panels' },
  { key: 'shifts', label: 'Shifts', model: 'shifts' },
  { key: 'genders', label: 'Genders', model: 'genders' },
  { key: 'production_roles', label: 'Production Roles', model: 'production_roles' },
  { key: 'educational_levels', label: 'Educational Levels', model: 'educational_levels' },
  { key: 'status_pkwtt', label: 'Status PKWTT', model: 'status_pkwtt' },
];

/** replace "{n}" / "{k}" / "{value}" placeholders */
export function fill(template: string, values: Record<string, string | number>): string {
  return template.replace(/\{(\w+)\}/g, (_, k) => String(values[k] ?? ''));
}
