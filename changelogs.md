# Changelog

## 2026-10-11

### Line Balancing — Employee Search Autocomplete, Joint Process Removal, Wider Columns & Row Deletion Fix

- **Migration** (`2026_10_10_000002_add_employee_id_to_line_balancing_report_rows_table`):
  - Adds `employee_id` (unsignedBigInteger, nullable, FK → `operators.id` with nullOnDelete) to `line_balancing_report_rows` table.
- **Model** (`app/Models/LineBalancingReportRow.php`):
  - Added `employee_id` to `$fillable`.
  - Added `employee()` belongsTo relationship to `Operator` model.
- **Controller** (`app/Http/Controllers/LineBalancingController.php`):
  - `edit()` now eager-loads `rows.employee` and passes `$employees` (active operators) to the view.
  - `saveRows()` resolves `employee_id` → `operator_name` to auto-populate `name` field.
  - `export()` now includes Employee column instead of Joint Process; eager-loads `rows.employee`.
  - Validation rules include `rows.*.employee_id` → `nullable|exists:operators,id`.
- **Edit View** (`resources/views/operations/line-balancing/edit.blade.php`):
  - Removed `Joint Process` column from table (header, body rows, addRow JS).
  - `Name` column replaced with **autocomplete search field** using the same pattern as Operator Data master page — debounced `fetch()` to `/master-data/operators/search` endpoint, shows operator name only (no employee number prefix), stores `employee_id` and `name` in hidden inputs.
  - Column widths widened: Machine w-32, Process w-36, Employee w-44, calculated columns w-24/w-28.
  - Cell padding increased from `px-2` to `px-3` across all columns.
  - Table `min-width: 1400px` with horizontal scrollbar (`overflow-x-auto` container).
  - `removeRow()` properly removes the row and triggers full re-indexing.
  - `renumberRows()` now skips `#total-row` and updates all `rows[N]` input/select name attributes.
  - `updateStopwatchTargetOptions()` skips `#total-row`.
  - Total row `colspan` updated from 4 to 3 (No + Machine + Process).

## 2026-10-10

### Line Balancing — Configurable Settings, Joint Process, Total Row & Export Overhaul

- **Migration** (`2026_10_10_000001_add_settings_and_joint_process_to_line_balancing_tables`):
  - Adds `working_hours_per_day` (decimal 5,2, default 8), `allowance_percent` (decimal 5,2, default 15), `update_date` (date, nullable) to `line_balancing_reports` table.
  - Adds `joint_process` (string 150, nullable) to `line_balancing_report_rows` table.
- **Model** (`app/Models/LineBalancingReport.php`):
  - Added new fields to `$fillable` and `$casts`.
  - Added `allowance_multiplier` accessor: `1 + allowance_percent / 100`.
  - Added `working_seconds_per_day` accessor: `working_hours_per_day × 3600`.
- **Model** (`app/Models/LineBalancingReportRow.php`):
  - Added `joint_process` to `$fillable`.
  - All calculation accessors now read configurable `allowance_multiplier` and `working_hours_per_day` from parent report instead of hardcoded 1.15 and 8.
- **Controller** (`app/Http/Controllers/LineBalancingController.php`):
  - `store()`, `update()`, `saveRows()` updated to handle `working_hours_per_day`, `allowance_percent`, `update_date`, `joint_process`.
  - `export()` completely rewritten: metadata rows with update date/allowance/working hours, Joint Process column, Total row, configurable formulas, proper column widths, styled headers.
- **Edit View** (`resources/views/operations/line-balancing/edit.blade.php`):
  - Added `Update Date` to header info grid (5-column layout).
  - Target form now includes `Working Hours/Day`, `Allowance (%)`, and `Update Date` fields.
  - Added `PPH (Productivity)` display to target section.
  - Added `Joint Process` column to process table between Name and Operator.
  - Added formula tooltips on all calculated column headers.
  - Added Total Row at bottom of process table (Total Operator, Total Avg CT+Allow, Total Output Proc/Hr, Total Req Opr, Total Potential Output).
  - JavaScript calculation engine updated to use configurable `allowanceMultiplier`, `workingHoursPerDay`, `WORKING_SECONDS` from report data.
  - Chart dataset label now shows dynamic allowance percent (e.g., "Avg CT +15%").
  - Target display (Target/Day) now uses configurable working hours.
- **Seeder** (`database/seeders/LineBalancingSeeder.php`): Rewritten with 21 process rows using `ct_1`–`ct_3` (ct_4/ct_5 null), matching Excel template spec (Livlig Husky article, SNLS machine, 93 target output, 15% allowance, 8 hours).

## 2026-10-01

### Operators — Sorting, Start Date Column & Filter Bug Fix

- **Blade View** (`resources/views/master-data/operators.blade.php`):
  - **Start Date column restored** to the table (between Educational Level and Date of Birth).
  - **Sort links added to ALL column headers** — Factory, Department, Division, Section, Line, Status PKWTT, Educational Level, Start Date, Date of Birth now all support ascending/descending sorting via clickable column headers.
  - **Filter bug fixed**: When selecting a date-based filter column (Start Date or Date of Birth), the search bar and filter fields were disappearing because `valueSelect.parentElement.style.display = 'none'` was hiding the entire form element. Fixed by changing to `valueSelect.style.display = 'none'` to only hide the filter value select.
- **Routes** (`routes/web.php`):
  - Updated `$sortableColumns` to use relationship names (`factory`, `department`, `division`, `section`, `line`, `status_pkwtt`, `educational_level`) instead of FK column names.
  - Added `$sortMap` mapping relationship sort names to actual table/column pairs for proper alphabetical sorting via `leftJoin`.
  - Sorting by relationship columns now joins the related table and sorts by the name column (e.g., `factories.factory_name`, `departments.department_name`) instead of sorting by FK ID.

### Operators — Division & Section Columns Added

- **Migration**: `2026_10_01_115628_add_division_section_to_operators_table` — adds `division_id` (FK → `divisions`) and `section_id` (FK → `sections`) to `operators` table.
- **Model** (`app/Models/Operator.php`): Added `division_id`, `section_id` to `$fillable`; added `division()` and `section()` belongsTo relationships.
- **Blade View** (`resources/views/master-data/operators.blade.php`): Full rewrite — 2-column grid modal layout, table now includes Factory, Department, Division, Section, Line columns. Date range filters updated. New org filters (factory, department, division, section, line). Viewer guards on Import, New, Delete, Edit buttons. Hard delete fully removed. Uppercase on search and text inputs.
- **Partial** (`resources/views/master-data/partials/operator-selects.blade.php`): Added Factory, Department, Division, Section, Line dropdowns before Gender.
- **Routes** (`routes/web.php`): Operators GET updated with eager loading for org relationships, org filter handling, date range filters. POST/PUT updated with org field validation and strtoupper. Import/export updated with org columns. Viewer permission checks (abort 403) on all modifying routes. Developer-only hard delete check. Employee profile redirect route added.

### Departments — Factory Removed

- **Blade View** (`resources/views/master-data/departments.blade.php`): Removed Factory from filter dropdown, table column, Create modal, Edit modal, and import instructions. Updated openEdit() JS function. Added viewer guards on Import, New, Delete buttons. Added uppercase to text inputs.
- **Routes**: Departments store/update validation no longer requires factory_id.

### Login Page — Modern Dark Gradient Redesign

- **Layout** (`resources/views/layouts/guest.blade.php`): Complete redesign — dark gradient background (slate-900 → indigo-950), glassmorphism card (bg-white/5 backdrop-blur), LEAN ENTERPRISE branding with lightning bolt icon, Inter font.
- **Login** (`resources/views/auth/login.blade.php`): Redesigned form — translucent inputs with white/5 backgrounds, indigo focus states, full-width login button with shadow glow, inline validation messages.

### Sidebar — Employees Profile & Hard Delete

- **Desktop Sidebar** (`resources/views/layouts/app.blade.php`): Added "Employees Profile" link under Lean Operations (points to operators route). Added "Hard Delete" link under Management (developer only, red accent).
- **Mobile Sidebar**: Same additions mirrored in mobile navigation.

### Centralized Hard Delete Page

- **Blade View** (`resources/views/hard-delete/index.blade.php`): New page showing all Data Masters as cards with inactive record counts. Each card has a "Delete X Record(s)" button with confirmation dialog. Danger zone warning banner. Developer-only access.
- **Routes** (`routes/web.php`): Added `GET /hard-delete` (index) and `DELETE /hard-delete` (destroy) routes with developer-only middleware. Supports 22 Data Masters.

### All Data Masters — Viewer Permissions, Uppercase, Hard Delete Cleanup

- **Viewer Guards**: Added `@if (auth()->user()->role->role_name !== 'viewer')` around Import, New, Delete buttons in all custom Data Master blades: articles, destinations, production-lines, factories, processes, mechanics, gsd-elements. Export remains accessible to all roles.
- **Uppercase Inputs**: Added `style="text-transform:uppercase"` to all text/textarea inputs in Create and Edit modals across all custom Data Master blades.
- **Hard Delete Removed**: Removed hard delete buttons, bars, forms, and JS functions from articles, destinations, production-lines blades. Cleaned up orphaned JS functions in simple-master blade.
- **Server-Side**: Viewer permission checks (abort 403) added to all modifying routes (store, update, deactivate, import) for operators. Developer-only check on hard delete routes.

### Educational Level & Status PKWTT — Display Fix

- **Blade View** (`resources/views/master-data/simple-master.blade.php`): Added missing entries for `educational-levels` and `status-pkwtt` in both `$displayNames` and `$nameFields` arrays, fixing the display bug where these masters showed incorrect column names.

---

## 2026-09-29

### Employees (Operators) — Status PKWTT & Educational Level

- **Migration**: `2026_09_29_000001_add_status_pkwtt_educational_level_to_operators_table` — adds `status_pkwtt_id` (FK → `status_pkwtt`) and `educational_level_id` (FK → `educational_levels`) to `operators` table.
- **Model** (`app/Models/Operator.php`): Added `status_pkwtt_id` and `educational_level_id` to `$fillable`; added `statusPkwtt()` and `educationalLevel()` belongsTo relationships.
- **Blade View** (`resources/views/master-data/operators.blade.php`): 
  - Header renamed from "Operators" to "Employees".
  - Removed Line, Department, Factory columns from table.
  - Added Status PKWTT and Educational Level columns to table, create/edit modals, filters, and import/export.
  - Added row count display (`Total Records`).
  - Added hard-delete mode for inactive records (with FK dependency checks).
  - Added date range filters (start date, DOB), working age, age, age year, and years-of-service filters.
- **Partial** (`resources/views/master-data/partials/operator-selects.blade.php`): Removed Factory, Department, Line dropdowns. Added Status PKWTT and Educational Level dropdowns.
- **Routes** (`routes/web.php`): Operators GET updated — removed factory/department/line, added status_pkwtt/educational_level + new filters. Operators POST/PUT updated. Added hard-delete, import/export updates.

### Articles — Simplified

- **Blade View** (`resources/views/master-data/articles.blade.php`): Removed Destination, Label Code, Label + Quty Code columns from table, filters, create/edit modals. Added row count display and hard-delete mode.
- **Routes**: Articles GET — removed destination/label fields from search, sort, filter. Articles POST/PUT — removed destination/label validation. Articles import/export simplified to Article Name only. Added hard-delete route.

### Production Lines — Division Removed

- **Blade View** (`resources/views/master-data/production-lines.blade.php`): Removed Division column from table, filters, create/edit modals. Added row count display and hard-delete mode.
- **Routes**: Production Lines GET — removed division from query, filter, sort. POST/PUT — removed division_id. Import/export — removed Division column. Added hard-delete route.

### Destinations — New Data Master

- **Migration**: `2026_09_29_000002_create_destinations_table` — creates `destinations` table (id, destination, description, status, timestamps).
- **Model** (`app/Models/Destination.php`): New model with fillable: `destination`, `description`, `status`.
- **Blade View** (`resources/views/master-data/destinations.blade.php`): Full Data Master page with search, autocomplete, filters, import/export, soft delete, hard delete.
- **Routes**: Full CRUD + deactivate, bulk-deactivate, hard-delete, import, export, autocomplete search.
- **Seeder** (`database/seeders/DestinationSeeder.php`): Seeds EU, AP, ME, NA, GB.
- **Sidebar**: Added "Destinations" link in both desktop and mobile sidebars.

### All Simple Masters — Row Count & Hard Delete

- **Blade View** (`resources/views/master-data/simple-master.blade.php`): Added row count display and hard-delete mode for all 13 shared Data Masters (Skill Gradings, Divisions, Sections, Machine Types, Components/Panels, Machine Numbers, Shifts, Failure Modes, Spare Parts, Genders, Production Roles, Educational Level, Status PKWTT).
- **Routes**: Added `$totalCount` and hard-delete routes for all simple masters, plus Factories, Departments, GSD Elements, Mechanics, Processes.

### Seeders Updated

- **DivisionSeeder**: Replaced with [Warehouse, Cutting, Sewing, Finshing, Packing]. Rows not in new list are deleted.
- **DepartmentSeeder**: Replaced with [Business Development, HRD, Production, Logistics, PPIC, Purchasing, IT, Accounting, LEAN & IE, Sustainability]. Rows not in new list are deleted.
- **SectionSeeder**: Replaced with [Computer Stitching, Cutting Gerber, Cutting Laser, Embroidery, Stuffing, Reverse Skin, Finishing Line, Finished Goods]. Rows not in new list are deleted.
- **DestinationSeeder**: New seeder for destinations.
- **DatabaseSeeder**: Added DestinationSeeder call.

### Sidebar & Home

- **Sidebar** (`resources/views/layouts/app.blade.php`): "Operators" renamed to "Employees" in both desktop and mobile sidebars. Added "Destinations" link after "Departments".
- **Home** (`resources/views/home.blade.php`): "Operators" renamed to "Employees" in stat card label and quick action link.

---

## 2026-09-18

### Production Lines — Department to Division Migration

- **Migration**: `2026_09_18_000001_add_division_id_to_production_lines_table` — replaces `department_id` FK with `division_id` FK on `production_lines` table.
  - Data migrated via Department→Division name mapping: Sewing→Cutting Press, Finishing→Finishing Lines, Cutting Laser→Cutting Press, Cutting Gerber→Cutting Press, Embroidery→Reverse Skin, Warehouse→Stuffing, Packing→Finishing Lines, Finished Goods→Finishing Lines.
  - Unmapped departments fall back to the first active division.
  - Old `department_id` column dropped after migration.
- **Model** (`app/Models/ProductionLine.php`): `fillable` changed from `department_id` to `division_id` + `status`; added `division()` relationship (belongsTo Division); kept deprecated `department()` proxy.
- **Blade View** (`resources/views/master-data/production-lines.blade.php`): All UI references changed from Department to Division — filter, table header, table body, create/edit modals (dropdown from `$divisions`), import hint text, edit button JS.
- **API Controller** (`app/Http/Controllers/Api/ProductionLineController.php`): Filter changed from `department_id` to `division_id`; eager loads changed from `department.factory` to `division`.
- **API Resource** (`app/Http/Resources/Api/ProductionLineResource.php`): Returns `division_id` + `division` (DivisionResource) instead of `department_id` + `department`.
- **API Requests**: `StoreProductionLineRequest` and `UpdateProductionLineRequest` now validate `division_id` (exists:divisions,id) instead of `department_id`.
- **New File**: `app/Http/Resources/Api/DivisionResource.php` — API resource for Division model.
- **Division Model** (`app/Models/Division.php`): Added `productionLines()` hasMany relationship.
- **Department Model** (`app/Models/Department.php`): Removed broken `productionLines()` relationship (column no longer exists).
- **Seeder** (`database/seeders/ProductionLineSeeder.php`): Changed from `department_id` to `division_id`; uses first active division as default; added `status => 'active'`.

### GSD Elements — Excel Import/Export Updated

- **Import**: Updated to parse TMU and Seconds columns in addition to existing fields. Columns: Element Name, Code, TMU, Seconds, Motion Sequence, Category, Descriptions.
- **Export**: Added TMU and Seconds columns to export output (columns A–H instead of A–F).

### Excel Export Formatting — All Data Masters

- Added `applyExcelFormatting($spreadsheet)` helper function in `routes/web.php` that applies:
  - Thin borders (`BORDER_THIN`) to all cells in the used range.
  - Horizontal center alignment on all cells.
  - Vertical center alignment on all cells.
  - Auto-width on all columns.
- Applied to ALL 8 Data Master export routes: Factories, Departments, Operators, Articles, Processes, GSD Elements, Production Lines, and the generic simple-master export.

### Data Integrity Verification

- All 32 existing Production Lines preserved with correct division mappings.
- 4 Divisions present (Cutting Press, Reverse Skin, Stuffing, Finishing Lines).
- 8 Departments preserved (not deleted — still used by PtmsReports and Operators).
- All other master data intact: Factories (3), Operators (1), GSD Elements (39).
- `php artisan db:seed` — all 20 seeders pass with no duplicates.
- `php artisan route:list` — 261 routes load without errors.

### Known Limitations

- Department→Division mapping is best-effort. Some departments (e.g., Sewing, Cutting Laser, Cutting Gerber) map to the same division (Cutting Press). If finer-grained divisions are added later, re-migration may be needed.
- The deprecated `department()` proxy method on ProductionLine model is kept for backward compatibility with any code that hasn't been updated yet.

---

## 2026-09-18 (Part 2)

### Operators — Factory Field Connected to Factories Data Master

- **Operators Page** (`routes/web.php` GET `/master-data/operators`):
  - `$factories` query changed from hardcoded `whereIn('factory_name', ['Factory 1', 'Factory 2'])` to `where('status', 'active')` — now loads ALL active factories from the Factories Data Master.
  - `$departments` query changed from hardcoded `whereIn('department_name', [...])` to `where('status', 'active')` — now loads ALL active departments.
  - `$productionLines` query changed from hardcoded `whereIn('line_name', [...])` to `where('status', 'active')` — now loads ALL active production lines.
  - Filter values for `factory_name`, `department_name`, `line_name` updated similarly to use all active records.
- **Operators Create/Update Validation** (`routes/web.php` POST/PUT `/master-data/operators`):
  - `factory_id` validation changed from `Rule::exists('factories', 'id')->whereIn(...)` to `'exists:factories,id'` — accepts any active factory.
  - `department_id` validation simplified to `'exists:departments,id'`.
  - `line_id` validation simplified to `'exists:production_lines,id'`.
- **No changes needed to `operator-selects.blade.php`** — the partial already iterated `$factories` from the controller; the fix was in the route query that provides the data.

### Excel Import — Operators & Departments Updated

- **Operators Import** (`routes/web.php` POST `/master-data/operators/import`):
  - Added `factory`, `department`, `line` column parsing from Excel.
  - Each FK is resolved by name lookup (e.g., `Factory::where('factory_name', $name)->first()`).
  - Missing/empty values gracefully set FK to `null`.
  - Updated blade hint text: `Columns: Operator Name, NIK Karyawan, Gender, Role, Factory, Department, Line`.
- **Departments Import** (`routes/web.php` POST `/master-data/departments/import`):
  - Added `factory` column parsing from Excel.
  - Factory FK resolved by name lookup instead of assigning to first factory.
  - Updated blade hint text to include `factory` column.
- **Articles Blade Hint Fix** (`resources/views/master-data/articles.blade.php`):
  - Corrected hint from "Article Number, Article Description, SMV" to "Article Name, Destination, Label Code" to match actual import columns.

### Files Modified

- `routes/web.php` — Operators GET route (factory/dept/line queries), Operators POST validation, Operators PUT validation, Operators import route, Departments import route.
- `resources/views/master-data/operators.blade.php` — Import modal hint text.
- `resources/views/master-data/departments.blade.php` — Import modal hint text.
- `resources/views/master-data/articles.blade.php` — Import modal hint text correction.

### Verification

- `php artisan route:list` — All routes load correctly.
- `php artisan db:seed` — All seeders pass with no duplicates.
- No existing data affected by these changes.

## 2026-10-08

### Live Autocomplete Search on All Data Master Pages

- Added live autocomplete/typeahead to the search field on ALL 19 Data Master pages.
- As user types, a debounced (300ms) AJAX fetch queries the backend for matching records.
- Dropdown shows up to 10 results with label + description, styled with hover highlight.
- Clicking a result fills the search field and submits the form instantly.
- Clicking outside or clearing the input closes the dropdown.
- **Backend**: 19 new JSON search API endpoints (`/{slug}/search`) returning `{id, label, description}` — active records only, limited to 10.
- **Frontend**: Autocomplete JS added to all 8 blade view files (1 simple-master template covers 12 pages, plus 7 custom pages).
- `autocomplete="off"` on all search inputs to prevent browser native autocomplete interference.

### Search Field — Enter-to-Submit (No Auto-Reload)

- Changed search field behavior on ALL 19 Data Master pages: typing no longer triggers page reload.
- Search now only executes when user presses **Enter** key in the search field.
- Filter dropdowns and Show Inactive checkbox still auto-submit instantly (no change).
- Removed 300ms debounced auto-submit from all 8 blade view files.
- Added `keydown` event listener with `e.key === 'Enter'` check on all search inputs.

### Column Sorting on All Data Master Pages

- Added ascending/descending column sorting to ALL 19 Data Master pages via clickable table headers.
- Click a column header to sort ascending, click again for descending. Active sort shows ▲/▼ indicator.
- Sort state preserved across search, filter, and pagination via query parameters.
- Sortable columns use column validation whitelist to prevent SQL injection on invalid sort values.
- **Simple Masters (12 pages)**: Skill Gradings, Divisions, Sections, Machine Types, Components/Panels, Machine Numbers, Shifts, Failure Modes, Mechanics, Spare Parts, Genders, Production Roles — sortable on name, description, status columns.
- **Custom Pages (7 pages)**: Processes, Operators, Articles, GSD Elements, Factories, Departments, Production Lines — sortable on all direct columns; relationship columns (Line, Department, Factory, Category) are NOT sortable.
- Updated both route handlers (`routes/web.php`) and blade views.

### Auto-Execute Search/Filter (Remove Apply Button)

- Removed the "Apply" button from ALL Data Master pages. Search and filter now auto-execute:
  - **Search input**: Debounced at 300ms — triggers form submission automatically after user stops typing.
  - **Filter dropdowns**: Instant submission on change (column selector and value selector).
  - **Show Inactive checkbox**: Instant submission on toggle.
- Added hidden `<input>` fields for `sort` and `direction` to preserve sort state during form submissions.
- Applied to all 19 Data Master pages (routes + views).

### Show Inactive Checkbox on All Data Master Pages

- Added "Show Inactive" checkbox to ALL Data Master pages that previously lacked it.
- When unchecked (default): only active records are shown (`WHERE status = 'active'`).
- When checked: all records including inactive are shown.
- **Previously**: Only GSD Elements had this feature.
- **Now**: All 19 pages have it — Skill Gradings, Divisions, Sections, Machine Types, Components/Panels, Machine Numbers, Shifts, Failure Modes, Mechanics, Spare Parts, Genders, Production Roles, Processes, Operators, Articles, GSD Elements, Factories, Departments, Production Lines.
- Checkbox state preserved across sort/filter interactions via query parameter.

### Skill Gradings — S/A/B/C Seeding

- Updated `SkillGradingSeeder` to seed 4 skill grade values:
  - **S** — Superior: Highest skill level, capable of all operations and training others
  - **A** — Advanced: High skill level, capable of complex operations with minimal supervision
  - **B** — Basic: Standard skill level, capable of regular operations with some supervision
  - **C** — Clerical: Entry level, requires close supervision and training support
- Seeder uses `updateOrInsert` for idempotent re-runs.
- All 4 grades seeded with `status = 'active'`.

### Bug Fix — Deactivate Route Closure Missing Variable

- Fixed pre-existing bug: `deactivate` route closure in `$simpleMasters` loop was missing `$nameField` in its `use` clause, causing runtime errors when logging deactivation activity.

## 2026-10-07

### Status Column on All Data Master Pages

- Added `status` column (active/inactive with colored badge) to ALL data master table views:
  - Factories, Departments, Processes, Operators, Articles, Production Lines, GSD Elements
  - All 12 simple masters (Skill Gradings, Divisions, Sections, Machine Types, Components/Panels, Machine Numbers, Shifts, Failure Modes, Mechanics, Spare Parts, Genders, Production Roles)
- Active = green badge, Inactive = red badge (consistent with Tailwind dark-mode design).

### Soft-Delete for All Data Masters

- Converted hard DELETE routes to soft-delete (PATCH deactivate) for: Processes, Operators, Articles, Production Lines.
- All deactivate routes now set `status = 'inactive'` instead of deleting the record.
- Delete button hidden for already-inactive records on all pages.
- Confirmation message changed from "Delete" to "Mark as inactive" on all pages.
- All deactivate routes use `PATCH` method with `/deactivate` suffix.

### New Data Master — Genders

- Created `genders` table with columns: `id`, `gender` (unique), `description`, `status`, timestamps.
- Created `Gender` model (`app/Models/Gender.php`).
- Created `GenderSeeder` with initial values: L (Laki-Laki), P (Perempuan).
- Added to `$simpleMasters` array in `routes/web.php` — full CRUD + Import/Export + Search/Filter.
- Added to sidebar navigation (desktop + mobile).

### New Data Master — Production Roles

- Created `production_roles` table with columns: `id`, `production_role` (unique), `description`, `status`, timestamps.
- Created `ProductionRole` model (`app/Models/ProductionRole.php`).
- Created `ProductionRoleSeeder` with 8 roles: Helper, Operator, Quality Control, Leader, Assistant Supervisor, Supervisor, Assistant Manager, Manager.
- Added to `$simpleMasters` array in `routes/web.php` — full CRUD + Import/Export + Search/Filter.
- Added to sidebar navigation (desktop + mobile).

### Operators — Gender/Role Dropdowns from Database

- Changed Gender and Role fields from free-text inputs to database-driven dropdown selects.
- Gender dropdown populated from `genders` table (active records only), shows "L - Laki-Laki" format.
- Role dropdown populated from `production_roles` table (active records only).
- Updated `operator-selects.blade.php` partial with new Gender and Role selects.
- Operators route now passes `$genders` and `$productionRoles` to the view.

### Clear Logs — UI Buttons + Activity Logging

- Added "Clear All Logs" button with trash icon to `login-logs.blade.php` and `activity-logs.blade.php`.
- Button includes confirmation prompt before clearing.
- Added `logActivity()` calls to both clear routes for audit trail.

### Database Migrations

- `2026_09_17_000016_add_status_to_production_lines_table` — adds `status` enum column.
- `2026_09_17_000017_create_genders_table` — creates `genders` table.
- `2026_09_17_000018_create_production_roles_table` — creates `production_roles` table.

## 2026-10-06

### Search/Filter for All Data Master Pages (12 pages)

- Added search bar and column-value filters to all remaining data master pages that lacked them:
  - Factories, Departments, Skill Gradings, Divisions, Sections, Machine Types, Components/Panels, Machine Numbers, Shifts, Failure Modes, Mechanics, Spare Parts
- Search searches across all displayed table columns.
- Filter uses two dropdowns: column selector + value selector (dynamically populated from database distinct values).
- Uses server-side `->when()` conditional queries for combined search + filter.
- Applied to both routes (`routes/web.php`) and views.

### Excel Import/Export for Processes, Operators, Articles, GSD Elements

- Added import/export routes for 4 pages that previously lacked them: Processes, Operators, Articles, GSD Elements.
- Import uses PhpSpreadsheet to parse `.xlsx` files with column matching.
- Export generates `.xlsx` downloads with all displayed columns.
- Added Import/Export buttons and Import modal to blade views: `operators.blade.php`, `articles.blade.php`, `gsd-elements.blade.php`.
- Processes view (`processes.blade.php`) also received Import/Export buttons and modal.
- Import columns documented in modal: Process Name/Version, Operator Name/NIK/Gender/Role, Article Name/Destination/Label Code, Element Name/Code/Motion/Category.

### Data Master — Production Lines

- Created complete Production Lines page: CRUD + Search/Filter + Import/Export.
- Routes: GET/POST/PUT/DELETE + Import/Export under `master-data.production-lines.*`.
- View: `resources/views/master-data/production-lines.blade.php` — full page with modals, search/filter, import/export.
- Uses existing `production_lines` table and `ProductionLine` model with `department_id`, `line_name`, `description` columns.
- Added to sidebar navigation (both desktop and mobile) after Departments.
- Created `ProductionLineSeeder` using `updateOrInsert` for idempotent seeding (A1-A10, B1-B10, C1-C10).
- Added seeder to `DatabaseSeeder.php`.

### Register/Login Lowercase Normalization

- `RegisteredUserController::store()` now applies `strtolower()` to username and email before saving.
- Login validation adds `lowercase` rule to username field.
- `LoginRequest::authenticate()` normalizes username/email to lowercase before `Auth::attempt()`.
- Ensures case-insensitive login: `User@Example.com` → `user@example.com`.

### Activity Logging Integration

- Added `module` column to `activity_logs` table via migration (`2026_10_06_000001_add_module_to_activity_logs_table`).
- Updated `ActivityLog` model fillable to include `module`.
- Added `logActivity(string $activity, ?string $module)` helper function in `routes/web.php`.
- Integrated logging into ALL master data CRUD operations:
  - Create, Update, Delete for: Processes, Operators, Articles, GSD Elements, Factories, Departments, Production Lines, and all 10 simple masters.
  - Import and Export operations logged with count.
- Updated `resources/views/system/activity-logs.blade.php` to display Module column.
- Logs identify: User, Username, Activity, Module, Timestamp.

### Translation Expansion

- Expanded `resources/lang/en/master-data.php` and `resources/lang/id/master-data.php` with:
  - Import/Export labels (Import, Export, Import Excel, Export Excel, Import Records)
  - Production Lines labels (Line Name, Descriptions, New/Edit Production Line)
  - System section labels (System, Login Logs, Activity Logs, Clear Cache, Speed Test)
  - Table headers (No, NIK Karyawan, Operator Name, Process Name, Version, GSD Codes, Module, Timestamp)
  - Misc UI labels (Confirm Delete, Required Field, Optional, Back, Home, Welcome, Close, Loading, Success, Error, Warning, Info)

### Seeders Verification

- All 18 seeders run successfully (including new ProductionLineSeeder).
- All seeders use idempotent patterns (`updateOrInsert`/`firstOrCreate`).
- No existing data was lost — `db:seed` is safe to re-run.

### Database Changes

- New migration: `2026_10_06_000001_add_module_to_activity_logs_table` — adds nullable `module` column.
- New seeder: `ProductionLineSeeder` — seeds 30 production lines (A1-A10, B1-B10, C1-C10).
- No destructive operations used. All existing data preserved.

### Route Name Fixes (Previous Session)

- Fixed `system.clear-cache` route naming mismatch.
- Fixed `system.speed-test` POST route to use session-based results.
- Fixed view paths for `system.login-logs` and `system.activity-logs`.

---

## 2026-09-17

### Developer Logs (Login & Activity Logs)

- Added `login_logs` table to track all user login/logout events (user_id, username, activity type, timestamps).
- Added `activity_logs` table for granular activity tracking (user_id, username, activity description, timestamps).
- Login/logout events are automatically recorded via `AuthenticatedSessionController`.
- Added `LogsActivity` trait for models/controllers to log custom activities.
- New pages: `/system/login-logs` and `/system/activity-logs` with searchable/filterable tables.
- Sidebar: Added "Logs" dropdown (developer-only) under System section with Login Logs and Activity Logs links.

### Clear Cache Page

- New developer-only page at `/system/clear-cache` with checkbox selection for cache types.
- Supports: config, route, view, application cache, compiled classes, and event cache.
- Each cache clear operation runs independently with success/failure feedback.

### Speed Test Page

- New developer-only page at `/system/speed-test` for performance benchmarking.
- Tests database connection time, query execution time, and displays server info (PHP version, Laravel version, memory usage).

### Operator NIK Karyawan Field

- Added `nik_karyawan` column (string, 50 chars, nullable, unique) to operators table.
- Operators table view now shows NIK Karyawan column.
- Create and Edit operator modals include NIK Karyawan input field.

### Sidebar & Navigation Updates

- **Data Masters**: Changed from flat links to a dropdown menu listing all 16 data masters.
- **Lean Operations**: Removed "Operations Overview", "Cycle Time", and "Materials" links; renamed "Breakdown" to "Operational Breakdown".
- **Management > System**: Added Clear Cache and Speed Test links (developer-only).
- **Root URL**: `/` now redirects to `/master-data/processes` (authenticated) or `/login` (guest).
- All sidebar changes applied to both desktop and mobile sidebars.

### 12 New Data Masters

- Created complete CRUD + Import/Export Excel for all new data masters:
  1. **Factories** — `/master-data/factories` (factory_name, status)
  2. **Departments** — `/master-data/departments` (department_name, factory relationship, status)
  3. **Skill Gradings** — `/master-data/skill-gradings`
  4. **Divisions** — `/master-data/divisions` (seeded: Cutting Press, Reverse Skin, Stuffing, Finishing Lines)
  5. **Sections** — `/master-data/sections` (seeded: computer stitching)
  6. **Machine Types** — `/master-data/machine-types` (seeded: SN/ZigZag)
  7. **Components/Panels** — `/master-data/components-panels` (seeded: 28 items)
  8. **Machine Numbers** — `/master-data/machine-numbers` (seeded: 1-21, 1a-15b pairs)
  9. **Shifts** — `/master-data/shifts` (seeded: Pagi, Siang, Malam)
  10. **Failure Modes** — `/master-data/failure-modes` (seeded: 13 items)
  11. **Mechanics** — `/master-data/mechanics` (seeded: 13 names)
  12. **Spare Parts** — `/master-data/spare-parts` (seeded: 5 items)
- All masters support: list view with search/filter, create/edit modals, soft-delete (status toggle), Excel import, Excel export.
- Factories and Departments have dedicated views with extra fields (factory_name, factory relationship).
- Other 10 masters share a reusable `simple-master.blade.php` generic view.
- All seeders use `updateOrInsert` for idempotent re-runs.

### Database Migrations (15 total)

- `add_status_to_factories_table` — adds `status` enum (active/inactive) with default 'active'
- `add_status_to_departments_table` — adds `status` enum to departments
- `add_nik_karyawan_to_operators_table` — adds NIK Karyawan field
- `create_login_logs_table` — for login/logout tracking
- `create_activity_logs_table` — for general activity tracking
- `create_skill_gradings_table` through `create_spare_parts_table` — 10 new data master tables

### Installed Dependencies

- `phpoffice/phpspreadsheet` ^1.29 — Excel import/export for all data masters.

---

## 2026-09-14

### Search and Filter for Master Data Pages

- Added search bar above the table on Process, Operators, and Articles pages.
- Added two-dropdown filter pattern: one dropdown to select the column to filter by, and one dropdown (dynamically populated by JavaScript) to select the matching value for that column.
- Filters operate server-side using query parameters (`search`, `filter_column`, `filter_value`) and persist across page reloads.
- Added "Apply" and "Clear" buttons for explicit filtering and one-click reset.
- All filter dropdown options reflect distinct values currently in the database.

### Favicon

- Added a professional indigo geometric LE monogram favicon at `public/favicon.svg`.
- Integrated the favicon link into the authenticated layout, guest layout, and welcome page.

### Verification

- Added regression test covering text search, column-value filtering, and filter data availability for all three master-data pages.

## 2026-09-15

### GSD Elements Master Data (CRUD)

- Added full CRUD page for GSD Elements at `/master-data/gsd-elements` with routes for list, create, update, and soft-delete.
- List view includes search bar, column filter dropdowns (element_name, code, motion_sequence), and "Show inactive" toggle.
- Create/Edit modals with form fields: category dropdown, element_name, description, code, tmu, seconds, motion_sequence.
- Soft-delete implemented by setting `status` to `inactive` (no physical row deletion).
- Added sidebar navigation link for GSD Elements in both desktop and mobile sidebars.

### Dark Mode

- Configured `darkMode: 'class'` in `tailwind.config.js`.
- Added dark/light toggle button in the top header bar (moon/sun icon) with localStorage persistence.
- Dark mode persists across page reloads and respects system preference on first visit.
- Applied comprehensive `dark:` Tailwind variants to all master-data views (Processes, Operators, Articles, GSD Elements) including: header, alerts, search inputs, filter dropdowns, tables, modals, form labels, form inputs, action links, empty states, and dynamic JS-generated selects.
- Applied dark mode to the layout header bar, sidebar, user dropdown, and mobile sidebar.

### Language Selector (English / Indonesian)

- Created `resources/lang/en/master-data.php` and `resources/lang/id/master-data.php` with translations for navigation, common UI labels, and all master-data pages.
- Added `SetLocale` middleware to persist locale in session and set `App::setLocale()`.
- Registered middleware in `app/Http/Kernel.php` web group.
- Added `POST /language/{locale}` route to switch between `en` and `id`.
- Added language globe icon dropdown in the top header bar (desktop) with EN/ID buttons showing active state.
- Added compact EN/ID toggle in the mobile sidebar.
- Sidebar navigation labels now use `__()` translation helper.

### Verification

- All 31 existing tests pass (134 assertions) — no regressions.
- Frontend assets build successfully with Vite.
- All existing database data preserved — no `migrate:fresh`, `db:wipe`, or destructive operations used.
- Existing CRUD, dependency-safe delete, and Quty code tests remain unchanged.

## 2026-09-09

### Data Master Uploads and Reusable Choices

- Replaced Operator and Article text photo paths with secure JPG/JPEG/PNG uploads using Laravel's `public` disk.
- Added generated storage paths, public thumbnails, edit previews, replacement handling, and preservation when no replacement is selected.
- Removed Employee Number from the Operator creation form while retaining an internal generated identifier for existing schema compatibility.
- Added the exact Factory 1/Factory 2, Department, and A1-C10/B1-C10/C1-C10 master choices using the existing foreign-key structures.
- Added server-side image validation with a 5 MB limit and rejected non-image uploads.

### Process and GSD

- Replaced the single Process Version GSD Element link with the `process_version_gsd_elements` many-to-many relationship.
- Added repeatable GSD Element selectors to Process create and edit modals.
- Added GSD Element codes to the Process table.
- Removed Notes and GSD Category inputs from Process forms.
- Made Process deletion dependency-safe when historical PTMS reports exist.

### Operators

- Added nullable gender, role, photo, factory, department, and production line assignment fields.
- Added Operator create, edit, and delete workflows.
- Added dependency-safe Operator deletion when historical reports exist.
- Updated the Operators table and profile to use current assignment data with historical fallback.

### Articles

- Added Article name and photo fields to the table and forms.
- Label + Quty Code is now generated automatically as `Label Code + 17596` on every model save.
- Removed manual Label + Quty Code inputs.
- Added dependency-safe Article deletion for historical reports.

### Verification

- Added regression coverage for multi-element GSD links, Operator CRUD, automatic Quty generation, duplicate label validation, and dependency-safe deletion.

---

## 2026-09-28

### Application Timezone — UTC+7

- **`config/app.php`**: Changed `timezone` from `'UTC'` to `'Asia/Jakarta'` (UTC+7).
- All Laravel timestamps, `now()`, `Carbon::now()`, and date calculations now use UTC+7.
- Affects: Login Logs, Activity Logs, `created_at`/`updated_at` timestamps, Operator computed date fields.

### New Data Master — Educational Level

- **Migration**: `2026_10_09_000001_create_educational_levels_table` — creates `educational_levels` table with `id`, `level` (unique, string 100), `description` (nullable), `status` (enum active/inactive), timestamps.
- **Model**: `app/Models/EducationalLevel.php` — fillable: `level`, `description`, `status`.
- **Seeder**: `database/seeders/EducationalLevelSeeder.php` — seeds 13 values: D1, D3, MA, MTS, S1, SD, SLTA, SLTP, SMA, SMK, SMKN, SMP, STM. Uses `updateOrInsert` for idempotency.
- **Routes**: Added to `$simpleMasters` array in `routes/web.php` — full CRUD + Import/Export + Search/Filter/Sort + Autocomplete + Show Inactive.
- **Navigation**: Added to `$dataMasterLinks` in `resources/views/layouts/app.blade.php` (both desktop and mobile sidebars).

### New Data Master — Status PKWTT

- **Migration**: `2026_10_09_000002_create_status_pkwtt_table` — creates `status_pkwtt` table with `id`, `pkwtt` (unique, string 100), `description` (nullable), `status` (enum active/inactive), timestamps.
- **Model**: `app/Models/StatusPkwtt.php` — fillable: `pkwtt`, `description`, `status`.
- **Seeder**: `database/seeders/StatusPkwttSeeder.php` — seeds 2 values: TETAP, KONTRAK. Uses `updateOrInsert` for idempotency.
- **Routes**: Added to `$simpleMasters` array in `routes/web.php` — full CRUD + Import/Export + Search/Filter/Sort + Autocomplete + Show Inactive.
- **Navigation**: Added to `$dataMasterLinks` in `resources/views/layouts/app.blade.php` (both desktop and mobile sidebars).

### Production Roles — 5 New Values

- **Seeder** (`database/seeders/ProductionRoleSeeder.php`): Added 5 new roles: ADM, ANGGOTA, CHECKER SEWING, DANRU, KASAT. Total: 13 roles (8 existing + 5 new). Uses `updateOrInsert` for idempotency.

### Departments — 12 New Values

- **Seeder** (`database/seeders/DepartmentSeeder.php`): Added 12 new departments: GAEBASIL, GEMZAH, KEBERSIHAN UMUM, LOGISTIK, MEKANIK, QC FINISHING, QC SEWING, SATPAM, SEWING BALIK, KAPAS, SUPIR, STAFF. Total: 20 departments (8 existing + 12 new). Uses `updateOrInsert` with `factory_id` defaulting to first active factory.

### Mechanics — NIK KARYAWAN Field

- **Migration**: `2026_10_09_000003_add_nik_karyawan_to_mechanics_table` — adds `nik_karyawan` (string 50, nullable, unique) after `id` column. Uses `Schema::hasColumn` guard.
- **Model** (`app/Models/Mechanic.php`): Added `'nik_karyawan'` to fillable array (positioned first).
- **Routes**: Removed Mechanics from `$simpleMasters` generic loop. Added custom routes block with full CRUD, Import/Export, Search/Filter/Sort — all include `nik_karyawan`.
- **View**: Created `resources/views/master-data/mechanics.blade.php` — custom page with NIK KARYAWAN column before Mechanic, create/edit modals with NIK field, import modal with NIK column hint, export with NIK column, autocomplete search on NIK + name.
- **Import**: Columns: NIK KARYAWAN, Mechanic, Descriptions. NIK parsed from Excel and saved to `nik_karyawan`.
- **Export**: Columns: No, NIK KARYAWAN, Mechanic, Descriptions.
- **Search**: Autocomplete searches both `nik_karyawan` and `mechanic` fields.
- **Filter**: Filterable by NIK KARYAWAN and Mechanic name.
- **Sort**: Sortable on all 4 columns (nik_karyawan, mechanic, description, status).

### Operators — Workforce Date/Duration Fields

- **Migration**: `2026_10_09_000004_add_date_fields_to_operators_table` — adds `start_date` (DATE, nullable) and `date_of_birth` (DATE, nullable) after `line_id`. Uses `Schema::hasColumn` guards.
- **Model** (`app/Models/Operator.php`):
  - Added `start_date`, `date_of_birth` to fillable array.
  - Added date casts for both fields.
  - Added 4 computed accessors:
    - `working_age` — duration from `date_of_birth` to `start_date`, returns `"{y} Yr {m} Mth {d} Day"` format.
    - `age` — duration from `date_of_birth` to `now()`, returns `"{y} Yr {m} Mth {d} Day"` format.
    - `age_year` — integer years from `date_of_birth` to today.
    - `years_of_service` — remaining time until retirement (DOB + 59yr 20day − today).
- **Routes** (`routes/web.php`):
  - GET `/operators`: Added `start_date`, `date_of_birth` to sortable columns, search fields, and filter values.
  - POST/PUT `/operators`: Added `start_date` and `date_of_birth` validation rules (`nullable`, `date`).
  - Import: Added `start date` and `date of birth` column parsing with `Carbon::parse()` date validation.
  - Export: Added 6 new columns: Start Date, Date of Birth, Working Age, Age, Age (Year), Years of Service.
- **View** (`resources/views/master-data/operators.blade.php`):
  - Table: Added 6 new columns — Start Date, Date of Birth, Working Age, Age, Age (Yr), Years of Service. Date columns sortable.
  - Create/Edit forms: Added Start Date and Date of Birth date-picker fields.
  - `openOperatorEdit()` function: Updated to accept and populate `startDate` and `date_of_birth` parameters.
  - Import modal hint: Updated to include "Start Date, Date of Birth".
  - Filter dropdown: Added "Start Date" and "Date of Birth" options.
  - Empty state colspan updated from 12 to 18.
- **Date Calculations**: Use `Carbon::diff()` for accurate duration calculation handling leap years, month boundaries, and different month lengths.

### Years of Service — Implemented

- **Business rule confirmed**: Worker retirement limit is age 59 years 0 months 20 days.
- **Calculation**: `Retirement Date = Date of Birth + 59 years + 20 days`. Years of Service = `Retirement Date − Today`.
- **Display**: `{years} Yr {months} Mth {days} Day` — shows remaining time until retirement.
- If operator has already passed retirement date, displays `0 Yr 0 Mth 0 Day`.
- Returns `null` if `date_of_birth` is not set.
- Uses `Carbon::diff()` for accurate calculation (handles leap years, month boundaries).
- Included in Operators table, export, and is dynamically calculated (not stored).

### DatabaseSeeder

- **`database/seeders/DatabaseSeeder.php`**: Added 3 new seeders to the call array: `EducationalLevelSeeder`, `StatusPkwttSeeder`, `DepartmentSeeder`. Total: 23 seeders.

### Files Modified/Created

**New files:**
- `database/migrations/2026_10_09_000001_create_educational_levels_table.php`
- `database/migrations/2026_10_09_000002_create_status_pkwtt_table.php`
- `database/migrations/2026_10_09_000003_add_nik_karyawan_to_mechanics_table.php`
- `database/migrations/2026_10_09_000004_add_date_fields_to_operators_table.php`
- `app/Models/EducationalLevel.php`
- `app/Models/StatusPkwtt.php`
- `database/seeders/EducationalLevelSeeder.php`
- `database/seeders/StatusPkwttSeeder.php`
- `database/seeders/DepartmentSeeder.php`
- `resources/views/master-data/mechanics.blade.php`

**Modified files:**
- `config/app.php` — timezone changed to `Asia/Jakarta`
- `app/Models/Mechanic.php` — added `nik_karyawan` to fillable
- `app/Models/Operator.php` — added date fields to fillable, date casts, 4 computed accessors
- `database/seeders/ProductionRoleSeeder.php` — added 5 new role values
- `database/seeders/DatabaseSeeder.php` — added 3 new seeders
- `routes/web.php` — added model imports, updated `$simpleMasters` (removed mechanics, added educational-levels + status-pkwtt), added custom mechanics routes, updated operators routes (sort/search/filter/validation/import/export)
- `resources/views/master-data/operators.blade.php` — added 6 table columns, date form fields, filter options, import hint, updated JS
- `resources/views/layouts/app.blade.php` — added Educational Level + Status PKWTT to both sidebar nav arrays

### Verification

- `php artisan migrate` — all 4 migrations ran successfully.
- `php artisan db:seed` — all 23 seeders passed with no duplicates.
- Educational Levels: 13 records seeded.
- Status PKWTT: 2 records seeded.
- Production Roles: 13 total (8 existing + 5 new).
- Departments: 20 total (8 existing + 12 new).
- Existing data preserved — no destructive resets used.

---

## 2026-09-28 (Part 2)

### Operators Table — Horizontal Scrollbar

- Added `overflow-x-auto` to the operators table wrapper div so the table scrolls horizontally on smaller screens.
- Set `min-w-[1400px]` on the table to ensure all 18 columns (including Actions) are accessible via horizontal scroll.

### Years of Service — Calculation Implemented

- **`app/Models/Operator.php`**: `getYearsOfServiceAttribute()` now calculates remaining time until retirement.
- **Formula**: Retirement Date = `date_of_birth + 59 years + 20 days`. Years of Service = `retirement_date − today`.
- **Display**: `X Yr X Mth X Day` format using `Carbon::diff()`.
- If operator is past retirement limit, returns `0 Yr 0 Mth 0 Day`.
- Returns `null` if `date_of_birth` is not set.

---

## 2026-09-28 (Part 3)

### Data Masters � Bulk Soft Delete

- **Removed** individual Delete buttons from the Actions column on all Data Master pages.
- **Added** bulk delete mode with checkbox-based row selection across all Data Master pages:
  - **Delete button** in toolbar to enter bulk-delete selection mode.
  - **Bulk delete bar** with Confirm Delete / Cancel buttons and selected count display.
  - **Checkbox column** (hidden by default, shown only in delete mode) with select-all header checkbox.
  - **Row checkboxes** for multi-select support.
- **Soft delete** implemented � records set to `status: inactive`, not hard-deleted. Preserved with Show Inactive toggle.

#### Pages Modified
- `resources/views/master-data/simple-master.blade.php` � covers 13 simple data masters (failure_modes, machine_numbers, models, components_panels, failure_mode_equipments, shift_types, line_names, departments (gsd), ds_categories, project_statuses, production_roles, stations, numbering_systems).
- `resources/views/master-data/operators.blade.php`
- `resources/views/master-data/articles.blade.php`
- `resources/views/master-data/factories.blade.php`
- `resources/views/master-data/departments.blade.php`
- `resources/views/master-data/gsd-elements.blade.php`
- `resources/views/master-data/processes.blade.php`
- `resources/views/master-data/production-lines.blade.php`
- `resources/views/master-data/mechanics.blade.php`

#### Routes Added (`routes/web.php`)
- `PATCH /master-data/{slug}/bulk-deactivate` � generic route for all simple data masters (foreach loop).
- `PATCH /master-data/operators/bulk-deactivate` � with PtmsReport protection check.
- `PATCH /master-data/articles/bulk-deactivate`
- `PATCH /master-data/factories/bulk-deactivate`
- `PATCH /master-data/departments/bulk-deactivate`
- `PATCH /master-data/gsd-elements/bulk-deactivate`
- `PATCH /master-data/processes/bulk-deactivate`
- `PATCH /master-data/production-lines/bulk-deactivate`
- `PATCH /master-data/mechanics/bulk-deactivate`

#### Preserved Features
- Search, filters, sorting, pagination, Show Inactive toggle, Edit, Import, Export all remain functional.
- Existing authorization (developer|admin role middleware) enforced on all bulk-deactivate routes.

---

## 2026-09-28 (Part 4) � Line Balancing Module Rebuild

### Overview
Complete rebuild of the Line Balancing module as specified in documentation/ui/update.md sections 9-50. Replaced the placeholder Alpine.js implementation with a full-featured report management system.

### Database Changes

#### New Migrations
- 2026_10_09_000005_create_line_balancing_reports_table.php � Main report table with factory, article, line, report_name, target_output_per_hour, status, created_by.
- 2026_10_09_000006_create_line_balancing_report_rows_table.php � Report rows with machine_type_id, process, name, operator count, cycle_time.

#### New Models
- pp/Models/LineBalancingReport.php � Eloquent model with relationships to Factory, Article, ProductionLine, User (created_by), and Rows.
- pp/Models/LineBalancingReportRow.php � Eloquent model with calculated attributes: avg_cycle_time, avg_cycle_time_allowance (+15%), avg_ct_per_process, output_per_hour, output_process_per_hour, request_operator, potential_output_per_process.

### Controller
- pp/Http/LineBalancingController.php � Full CRUD controller with:
  - index() � List page with server-side search, filter (factory, article, report_name, created_by, status), sorting, pagination.
  - store() � Create new report with validation.
  - edit() � Report editor with all related data.
  - update() � Update target output per hour.
  - saveRows() � Bulk save report rows with validation.
  - deactivate() � Single soft delete.
  - ulkDeactivate() � Bulk soft delete.
  - getLinesByFactory() � AJAX endpoint for production lines.

### Routes (
outes/web.php)
- GET /operations/line-balancing � List page.
- POST /operations/line-balancing � Create report.
- GET /operations/line-balancing/{id} � Report editor.
- PUT /operations/line-balancing/{id} � Update target.
- POST /operations/line-balancing/{id}/rows � Save rows.
- PATCH /operations/line-balancing/{id}/deactivate � Single deactivate.
- PATCH /operations/line-balancing/bulk-deactivate � Bulk deactivate.
- GET /operations/line-balancing/lines-by-factory/{factoryId} � AJAX lines.

### Blade Views
- 
esources/views/operations/line-balancing/index.blade.php � List page with:
  - Header banner (consistent with operations module style).
  - Search bar with auto-submit on Enter.
  - Filter dropdown (factory, article, report_name, created_by, status).
  - Sortable columns (factory, article, created_date, edited_date, created_by, status).
  - Bulk delete mode with select-all checkbox.
  - Status badges (Active/Inactive).
  - Clickable LB_ReportName linking to report editor.
  - Pagination.

- 
esources/views/operations/line-balancing/edit.blade.php � Report editor with:
  - Report header showing Factory, Line, Article, Created By, Article image.
  - Target Output/Hours input with auto-calculated Target/Day and Takt Time.
  - Operator/Process table with columns: No, Machine (dropdown from MachineTypes), Process, Name, Operator, Cycle Time, and 7 auto-calculated columns.
  - Cycle Time Stopwatch with Start/Stop/Lap/Reset and target row selector.
  - Add Row / Remove Row functionality.
  - Auto-recalculation on any input change.
  - Summary metrics: Total CT, Total Manpower, Avg Standard Time, Total Working Time, Target per PCS, Target Line/Day, Target Line/Hour, Max Based on CT, Output Actual, Sub Total Operator.
  - Chart.js bar chart with Cycle Time and Avg CT +15% bars, Takt Time reference line.
  - Save button with server-side validation.

### Seeder
- database/seeders/LineBalancingSeeder.php � Idempotent seeder creating 1 sample report with 5 rows. Skips if reports already exist.
- Added to database/seeders/DatabaseSeeder.php.

### UI/UX Consistency
- Uses existing x-app-layout component.
- Uses existing Tailwind dark mode classes (dark: variants).
- Uses existing 
ounded-xl, 
ounded-2xl, shadow-sm patterns.
- Uses existing status badge styling (emerald/red).
- Uses existing modal pattern.
- Uses existing search/filter/sort conventions.
- Uses vanilla JavaScript (no jQuery).

### Formulas Implemented
- Average Cycle Time = cycle_time
- Average Cycle Time + Allowance 15% = avg_ct � 1.15
- Average CT / Process = avg_ct_allowance / operator
- Output / Hours = 3600 / cycle_time
- Output Process / Hours = 3600 / avg_ct_process
- Request Operator = target_output_per_hour / output_per_hour
- Potential Output / Process = 3600 / avg_ct_process
- Total Cycle Time = sum of all cycle_times
- Total Manpower = sum of all operators
- Average Standard Time = average of avg_ct_allowance
- Total Working Time = 28,800 seconds (8 hours)
- Target per PCS = 3600 / target_output_per_hour
- Target Line per Day = target_output_per_hour � 8
- Target Line per Hour = target_output_per_hour
- Maximum Based on Cycle Time = max(3600 / cycle_time)

### Files Affected
- database/migrations/2026_10_09_000005_create_line_balancing_reports_table.php (NEW)
- database/migrations/2026_10_09_000006_create_line_balancing_report_rows_table.php (NEW)
- pp/Models/LineBalancingReport.php (NEW)
- pp/Models/LineBalancingReportRow.php (NEW)
- pp/Http/Controllers/LineBalancingController.php (NEW)
- 
outes/web.php (MODIFIED � replaced placeholder route with CRUD routes)
- 
esources/views/operations/line-balancing/index.blade.php (NEW)
- 
esources/views/operations/line-balancing/edit.blade.php (NEW)
- database/seeders/LineBalancingSeeder.php (NEW)
- database/seeders/DatabaseSeeder.php (MODIFIED � added LineBalancingSeeder)
