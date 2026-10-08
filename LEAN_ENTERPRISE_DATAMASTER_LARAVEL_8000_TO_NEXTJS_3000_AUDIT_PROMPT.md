# LEAN ENTERPRISE — Data Master Audit & Exact Replication Prompt
## Laravel `localhost:8000` → Next.js `localhost:3000`

> **Purpose:** Give this Markdown file to an AI coding agent working on the Next.js version of LEAN ENTERPRISE. The agent must audit the existing Laravel application at `http://localhost:8000`, then reproduce the actual Data Master pages, logic, behavior, data relationships, and functions in the Next.js application at `http://localhost:3000`.

---

# 1. OBJECTIVE

Audit **only the Data Master area** of the existing Laravel application running at:

```text
http://localhost:8000
```

Then use the audit as the specification for the Next.js application running at:

```text
http://localhost:3000
```

The goal is an **exact functional replica of the Laravel Data Master area**.

Do not redesign the business logic. Do not invent new functionality. Do not remove existing functionality.

Do not assume that a Data Master exists merely because it was mentioned in an old specification. Determine what actually exists in the current Laravel application.

The audit must be based on:

1. Actual Laravel UI.
2. Actual Laravel routes.
3. Actual controllers.
4. Actual models.
5. Actual migrations/schema.
6. Actual seeders.
7. Actual validation/request classes.
8. Actual import/export classes.
9. Actual JavaScript/Alpine/Livewire behavior, if used.
10. Actual database records where safely accessible.
11. Actual relationships and foreign keys.
12. Actual permissions/middleware.
13. Actual browser behavior at `localhost:8000`.

---

# 2. CRITICAL RULES

## 2.1 Audit first

Before implementing anything in Next.js:

- Inspect the Laravel application.
- Inspect its source code.
- Inspect its routes.
- Inspect its database-related code.
- Inspect the rendered Data Master pages.
- Test the interactive functions where possible.
- Record the actual behavior.

Do not start by guessing the desired implementation.

## 2.2 Do not modify Laravel during the audit

The Laravel application at `localhost:8000` is the **reference/source application**.

During the audit:

- Do not edit Laravel code.
- Do not create migrations.
- Do not modify database records.
- Do not delete records.
- Do not change statuses.
- Do not import data.
- Do not reset the database.
- Do not run `php artisan migrate:fresh`.
- Do not run destructive database commands.

Use read-only inspection wherever possible.

If an interactive test would modify data, inspect the source code instead and document the behavior without performing the destructive action.

---

# 3. TARGET APPLICATION

The target is the Next.js version:

```text
http://localhost:3000
```

Target requirements:

- Next.js
- TypeScript
- React
- Existing MySQL database
- Existing data must be preserved
- No Laravel backend dependency
- No PHP runtime requirement
- No duplicate master tables
- No duplicate master records
- No database reset

The Next.js implementation must connect to the existing database safely.

If an ORM is used, map it to the existing schema instead of creating an unnecessary parallel schema.

---

# 4. WHAT COUNTS AS A DATA MASTER

Audit every page/function that is actually a Data Master.

At minimum, investigate whether the Laravel application contains any of the following:

- Process
- Employees / Operators
- Articles
- GSD Elements
- Factories
- Departments
- Divisions
- Sections
- Production Lines / Lines
- Skill Gradings
- Machine Types
- Components / Panels
- Machine Numbers
- Shifts
- Failure Modes
- Mechanics
- Spare Parts
- Destination
- Educational Level
- Status PKWTT
- Genders
- Production Roles
- Buyers / Buyer List
- Any other master-data page found in the Laravel application

**Important:** This is NOT an instruction to assume that all of the above exist. Determine which ones actually exist in the current Laravel project.

If a previously discussed Data Master no longer exists, document:

```text
NOT FOUND IN CURRENT LARAVEL APPLICATION
```

Do not recreate it merely because it appeared in an older requirement.

---

# 5. DATA MASTER INVENTORY

Create a complete inventory.

For every actual Data Master, record:

| Field | Required |
|---|---|
| Data Master Name | Yes |
| Exact UI Page Name | Yes |
| Sidebar Parent | Yes |
| Sidebar Position | Yes |
| URL | Yes |
| Route Name | Yes |
| HTTP Method(s) | Yes |
| Controller | Yes |
| Model | Yes |
| Database Table | Yes |
| Primary Key | Yes |
| Permission | Yes |
| Middleware | Yes |
| Status | Yes |
| Notes | Yes |

Also identify whether the page is:

- Main Data Master page
- Nested Data Master
- Reference/master lookup
- Modal-only master
- Hidden/secondary master
- Developer-only master
- Admin-only master
- Viewer-readable master

---

# 6. SIDEBAR AND NAVIGATION AUDIT

For every Data Master document exactly where it appears.

Record:

- Exact sidebar label.
- Icon.
- Parent menu.
- Child menu.
- Order.
- Whether expandable/collapsible.
- Selected/active state.
- URL when clicked.
- Visibility for Developer.
- Visibility for Admin.
- Visibility for Viewer.

Do not change wording. Use the actual Laravel wording.

---

# 7. PAGE HEADER AUDIT

For every Data Master page record:

- Browser/page title.
- Breadcrumb, if present.
- Main heading.
- Description/subtitle.
- Buttons.
- Button order.
- Button labels.
- Button icons.
- Search bar placement.
- Filter placement.
- Import button placement.
- Export button placement.
- Add New button placement.
- Other actions.

Record exact capitalization.

---

# 8. TABLE AUDIT

For every Data Master table, record the **exact column order**.

Use:

| No. | UI Column | Database Field | Data Type | Formatting | Sortable | Searchable | Notes |
|---:|---|---|---|---|---|---|---|

Include:

- No.
- Photo/image columns.
- ID if displayed.
- Name.
- Code.
- Description.
- Relationships.
- Status.
- Actions.
- Calculated fields.
- Hidden fields affecting behavior.

Do not omit a column because it looks visually unimportant.

---

# 9. TABLE BEHAVIOR

Audit:

## Sorting

Determine:

- Sortable columns.
- Default sort.
- First click behavior.
- Second click behavior.
- Ascending/descending indicators.
- Whether sorting persists after search/filter.
- Server-side vs client-side sorting.

## Pagination

Determine:

- Whether pagination exists.
- Default page size.
- Available page sizes.
- Previous/next controls.
- First/last controls.
- Page number controls.
- Whether filters preserve pagination state.

## Row count

Determine:

- Whether total row count is shown.
- Whether it counts all rows or filtered rows.
- Exact label.
- Placement.

## Empty state

Document:

- Exact message.
- Icon.
- Buttons.
- Difference between empty database and no search results.

## Loading state

Document:

- Spinner.
- Skeleton.
- Disabled controls.
- Loading text.

## Error state

Document:

- Error message.
- Toast.
- Alert.
- Inline validation.
- Retry behavior.

---

# 10. SEARCH BAR AUDIT

For every Data Master document:

- Search input label/placeholder.
- Search icon.
- Debounce behavior.
- Whether search happens automatically.
- Whether Enter is required.
- Search button.
- Clear button.
- Columns searched.
- Case sensitivity.
- Partial matching.
- Exact matching.
- Numeric searching.
- Code searching.
- Description searching.
- Relationship searching.

Trace the actual Laravel query. Do not assume search covers every column.

---

# 11. FILTER AUDIT

For every Data Master identify every filter.

Use:

| Filter | Control Type | Source | Default | Behavior |
|---|---|---|---|---|
| | | | | |

Audit:

- Filter label.
- Dropdown/input type.
- Available values.
- Database vs hardcoded values.
- Default value.
- Multi-select or single-select.
- Searchable dropdown.
- Clear/reset option.
- Auto filtering.
- Apply button.
- Reset button.
- Interaction with search.
- Interaction with sorting.
- Interaction with pagination.

If Laravel uses `Search`, `Filter By`, and `Show Inactive`, preserve that behavior exactly.

Do not add an Apply button if Laravel does not use one.

---

# 12. STATUS AND ACTIVE/INACTIVE LOGIC

Determine exactly how status works.

Audit:

- Status field.
- Possible values.
- Default status.
- Whether inactive records remain in the DB.
- Whether Delete changes status.
- Whether Delete actually deletes.
- Whether inactive records are hidden by default.
- Whether a Show Inactive checkbox exists.
- Whether inactive records can be edited.
- Whether inactive records can be reactivated.
- Whether inactive records can be exported.
- Whether inactive records can be imported/updated.

If Laravel uses soft delete, document the actual mechanism, e.g. `deleted_at`.

If it uses a status field, document the actual field and values.

Do not replace one mechanism with another without evidence.

---

# 13. DELETE AUDIT

For every Data Master determine:

- Delete button label.
- Icon.
- Who can delete.
- Whether normal delete is soft delete.
- Whether delete changes status.
- Confirmation modal.
- Confirmation wording.
- Cancel wording.
- Success message.
- Error message.
- Related-record restrictions.
- Foreign-key constraints.
- Disabled delete conditions.

Search for any developer-only `Hard Delete` mechanism.

If it exists, document:

- Page name.
- Sidebar position.
- Permission.
- Tables affected.
- Selection behavior.
- Whether it deletes all inactive rows.
- Confirmation behavior.
- Warning text.

---

# 14. ADD NEW BUTTON AUDIT

For every Data Master document:

- Exact button label.
- Position.
- Icon.
- Modal or separate page.
- Modal width.
- Form layout.
- Field order.
- Required fields.
- Optional fields.
- Default values.
- Placeholder values.
- Input types.
- Dropdown sources.
- Relationship behavior.
- Validation.
- Error messages.
- Success message.
- Cancel behavior.
- Submit behavior.
- Loading state.
- Duplicate handling.

Use:

```text
+ New [Master]

Field 1:
- Label:
- Type:
- Required:
- Default:
- Placeholder:
- Validation:
- Source:

Field 2:
...
```

---

# 15. EDIT FUNCTION AUDIT

Document:

- Action button label/icon.
- Modal/page used.
- Editable fields.
- Read-only fields.
- Hidden fields.
- Existing values loaded.
- Validation.
- Duplicate handling.
- Save behavior.
- Success notification.
- Error notification.
- Cancel behavior.
- Relationship changes.

Pay particular attention to automatic/generated values.

---

# 16. AUTOMATIC FIELD LOGIC

Identify every automatically generated field.

Examples:

- IDs.
- Codes.
- Label+Quantity codes.
- Timestamps.
- Status.
- Slugs.
- Derived values.
- Calculated age.
- Years of service.
- Version numbers.

For each:

```text
Field:
Trigger:
Input:
Formula:
Output:
Can user override?:
Database value:
UI display:
```

Do not reimplement formulas from memory. Trace the Laravel implementation.

---

# 17. VALIDATION AUDIT

For every form identify:

- Required fields.
- Maximum length.
- Minimum length.
- Numeric limits.
- Decimal precision.
- Unique constraints.
- Foreign-key constraints.
- Allowed values.
- File type.
- File size.
- Date restrictions.
- Case transformation.
- Whitespace behavior.

Find validation in:

- Form Requests.
- Controllers.
- Models.
- Database constraints.
- JavaScript.

Document both UI and server-side validation.

---

# 18. UPPERCASE / CASE TRANSFORMATION

Audit whether Laravel automatically transforms input.

Check:

- Uppercase.
- Lowercase.
- Title Case.
- Trim whitespace.
- Code normalization.
- Search normalization.

Do not assume all fields should be uppercase.

Document behavior field-by-field.

If Laravel currently uses uppercase transformation for Data Masters, preserve it in Next.js.

---

# 19. IMPORT FUNCTION

For every Data Master that supports import, audit the complete Excel import flow.

## Button

- Exact label.
- Icon.
- Placement.
- Permission.

## Supported files

Determine whether it accepts:

- `.xlsx`
- `.xls`
- `.csv`

Do not assume.

## Template

Determine:

- Whether a template can be downloaded.
- Exact filename.
- Exact columns.
- Exact column order.
- Example values.
- Instructions.

## Import columns

Document:

| Excel Column | Database Field | Required | Ignored? | Validation |
|---|---|---|---|---|

Explicitly identify excluded columns such as No., Photo, Status, Actions, or generated fields.

Do not assume exclusions; verify.

## Validation

Audit:

- Missing required values.
- Invalid foreign keys.
- Duplicate values.
- Duplicate combinations.
- Invalid dates.
- Invalid numbers.
- Invalid enum/status.
- Blank rows.
- Extra columns.
- Wrong headers.
- Wrong file type.

## Duplicate behavior

Determine whether duplicates:

- Are rejected.
- Update existing records.
- Are skipped.
- Create duplicates.
- Produce an error.

## Transaction behavior

Determine whether import:

- Is atomic.
- Partially imports valid rows.
- Rolls back on any error.

## Result

Document:

- Success message.
- Imported count.
- Updated count.
- Skipped count.
- Failed count.
- Error report.
- Error row numbers.

---

# 20. EXPORT FUNCTION

For every Data Master with export document:

- Exact button label.
- Export format.
- Filename pattern.
- Included columns.
- Column order.
- Whether No. is included.
- Whether Actions is excluded.
- Whether Status is included.
- Whether photo is included.
- Whether inactive rows are included.
- Whether export respects search.
- Whether export respects filters.
- Whether export respects sorting.
- Whether all records are exported.
- Date/time in filename.
- Spreadsheet formatting.

Audit:

- Borders.
- Alignment.
- Header formatting.
- Number formatting.
- Date formatting.
- Column widths.
- Row heights.
- Freeze panes.
- Auto filters.
- Merged cells.
- Images.
- Formula cells.

If Laravel export uses formatting, reproduce it.

---

# 21. PHOTO / FILE FUNCTIONALITY

For Data Masters with images/files document:

- Upload control.
- Accepted extensions.
- MIME types.
- Maximum size.
- Required/optional.
- Preview behavior.
- Image display size.
- Storage location.
- Filename generation.
- Edit replacement.
- Delete behavior.
- Broken-image fallback.
- Export behavior.

Do not expose internal storage paths unless Laravel does.

---

# 22. DROPDOWN AND RELATIONSHIP AUDIT

For every dropdown identify:

```text
UI field
    ↓
Referenced master
    ↓
Database table
    ↓
Referenced column
    ↓
Stored value
```

Audit:

- Foreign key.
- Display label.
- Stored value.
- Sort order.
- Active/inactive filtering.
- Search.
- Dependent dropdown behavior.
- Inactive referenced values.

Do not create duplicate lookup tables in Next.js.

---

# 23. DATABASE SCHEMA AUDIT

For every Data Master table record:

```text
Table:
Primary Key:

Columns:
- name
- type
- nullable
- default
- unique
- index
- foreign key
- on delete
- on update

Timestamps:
- created_at
- updated_at

Soft delete:
- deleted_at

Status:
- ...
```

Identify:

- Single-column unique constraints.
- Composite unique constraints.
- Foreign keys.
- Nullable foreign keys.

---

# 24. EXISTING DATA AUDIT

For each master, record actual current records where safely possible.

Do not expose passwords, tokens, credentials, or secrets.

For ordinary master data capture:

- Existing IDs.
- Names.
- Codes.
- Descriptions.
- Status.
- Relationships.

If there are many rows, provide counts plus representative records.

Do not alter data while collecting it.

---

# 25. SEEDER AUDIT

Inspect Laravel seeders.

Identify:

- Seed files.
- Seeder classes.
- Seeded values.
- Whether values are still present.
- Whether seeders create duplicates.
- Whether seeders are safe to rerun.

Do not execute destructive seeders merely for inspection.

The Next.js version must preserve existing DB values.

---

# 26. ROLE / PERMISSION AUDIT

For every Data Master determine actual permissions for:

### Developer
- View
- Add
- Edit
- Delete
- Hard Delete
- Import
- Export

### Admin
- View
- Add
- Edit
- Delete
- Hard Delete
- Import
- Export

### Viewer
- View
- Add
- Edit
- Delete
- Hard Delete
- Import
- Export

Record the actual Laravel behavior.

Do not infer permissions from role names.

Inspect:

- Middleware.
- Gates.
- Policies.
- Blade permission checks.
- Controller authorization.

---

# 27. UI INTERACTION AUDIT

Document:

- Modal.
- Drawer.
- Dropdown.
- Tooltip.
- Toast.
- Alert.
- Confirmation dialog.
- Tabs.
- Accordion.
- Inline editing.
- Date picker.
- File picker.
- Search dropdown.
- Clear filter.
- Refresh button.

Record exact labels/messages where practical.

---

# 28. RESPONSIVE BEHAVIOR

Test Data Master pages at:

- Desktop.
- Tablet-width.
- Narrow/mobile-width.

Document:

- Horizontal scrolling.
- Responsive table.
- Hidden columns.
- Stacked forms.
- Modal resizing.
- Sidebar behavior.
- Search/filter wrapping.

The target should reproduce functional behavior.

---

# 29. DATA MASTER DEPENDENCIES

Trace where each master is used.

Identify:

- Foreign keys.
- Dropdown references.
- Query filters.
- Validation dependencies.
- Delete dependencies.

Example:

```text
Departments
    ↓
Employees
    ↓
Production Lines
    ↓
Process / Operations
```

This is critical because deleting or renaming a master record may affect other modules.

---

# 30. SOURCE CODE SEARCH

Use repository-wide searches for:

```text
Data Master
```

and each actual master name.

Also search:

```text
Route::resource
Route::get
Route::post
Route::put
Route::patch
Route::delete
```

and:

```text
Controller
Model
Request
Policy
Gate
middleware
import
export
Excel
maatwebsite
WithMapping
FromCollection
ToModel
WithHeadingRow
```

Also search Blade/JS for:

```text
search
filter
sort
pagination
inactive
status
delete
import
export
modal
confirm
```

---

# 31. ROUTE AUDIT

Create:

| Page | Method | URL | Route Name | Controller | Middleware |
|---|---|---|---|---|---|

Include CRUD/import/export routes relevant to Data Masters.

Use actual routes, not example routes.

---

# 32. CONTROLLER AUDIT

For each Data Master determine:

- Index query.
- Search query.
- Filter query.
- Sort logic.
- Pagination.
- Create.
- Validation.
- Update.
- Delete.
- Import.
- Export.
- Special calculations.
- Relationship loading.
- Status handling.

Record exact logic affecting user-visible behavior.

---

# 33. MODEL AUDIT

For each model record:

- Table.
- Fillable.
- Guarded.
- Casts.
- Relationships.
- Accessors.
- Mutators.
- Scopes.
- Events.
- Soft deletes.
- Attribute transformations.

Pay attention to:

- Uppercase mutators.
- Automatic codes.
- Status behavior.
- Relationship accessors.

---

# 34. MIGRATION AUDIT

Compare Laravel migrations against the actual database.

Do not assume migrations perfectly represent the current DB.

Record discrepancies.

Example:

```text
Migration:
status VARCHAR(20)

Actual database:
status ENUM(...)
```

The actual current DB must be treated as authoritative for preserving existing data.

---

# 35. NEXT.JS IMPLEMENTATION MAPPING

After the Laravel audit, produce:

| Laravel | Next.js |
|---|---|
| Blade page | React/Next.js page |
| Controller | Server Action/API/route handler |
| Model | ORM model/query layer |
| Form Request | Zod/server validation |
| Policy/Gate | Authorization layer |
| Laravel route | Next.js route |
| Excel import | Next.js server-side import |
| Excel export | Next.js server-side export |
| Eloquent relationship | ORM relationship/query |
| Session auth | Next.js auth/session |
| Flash message | Toast/notification |
| Modal | React modal/dialog |

Preserve behavior.

---

# 36. NEXT.JS DATABASE REQUIREMENT

The target application must use the existing MySQL database.

Do not:

```text
DROP DATABASE
DROP TABLE
migrate:fresh
```

Do not recreate existing master tables unnecessarily.

Do not duplicate existing master records.

If Prisma or another ORM is used:

- Inspect current DB first.
- Map existing tables.
- Preserve existing IDs.
- Preserve foreign keys.
- Preserve unique constraints.
- Preserve nullable fields.
- Preserve records.

---

# 37. EXACT FUNCTIONAL PARITY

The target must preserve:

- Page names.
- Navigation.
- Table columns.
- Column order.
- Search.
- Filters.
- Sorting.
- Pagination.
- Row counts.
- Add New.
- Edit.
- Delete.
- Status behavior.
- Inactive behavior.
- Import.
- Export.
- Validation.
- Duplicate handling.
- Relationships.
- Permissions.
- Error handling.
- Success notifications.
- Automatic values.
- File/image behavior.
- Excel behavior.

Do not replace existing functions with simpler approximations.

---

# 38. DO NOT INVENT BUSINESS LOGIC

If Laravel does not support a function, do not add it merely because it seems useful.

If behavior is unclear:

1. Inspect source.
2. Inspect route.
3. Inspect controller.
4. Inspect model.
5. Inspect migration.
6. Inspect browser behavior.
7. If still unclear, mark:

```text
UNKNOWN — REQUIRES HUMAN VERIFICATION
```

Do not guess.

---

# 39. DO NOT SILENTLY CHANGE LABELS

Preserve exact Laravel labels unless a target requirement explicitly changes them.

Preserve exact capitalization.

---

# 40. AUDIT OUTPUT FORMAT

Create a comprehensive Markdown audit report:

```text
# LEAN ENTERPRISE Data Master Audit

## 1. Audit Metadata
## 2. Laravel Environment
## 3. Data Master Inventory
## 4. Sidebar / Navigation
## 5. Data Master Pages

### 5.1 [Actual Master Name]
#### Page Information
#### Route
#### Permission
#### Database Table
#### Table Columns
#### Search
#### Filters
#### Sorting
#### Pagination
#### Add New
#### Edit
#### Delete
#### Status
#### Import
#### Export
#### Validation
#### Relationships
#### Existing Data
#### Special Logic
#### UI Behavior
#### Responsive Behavior

### 5.2 [Next Master]
...

## 6. Cross-Master Relationships
## 7. Role / Permission Matrix
## 8. Import/Export Comparison
## 9. Database Schema Mapping
## 10. Laravel → Next.js Mapping
## 11. Discrepancies / Unknowns
## 12. Next.js Implementation Checklist
```

---

# 41. PER-MASTER SUMMARY TABLE

Every actual Data Master should have:

| Property | Laravel Behavior |
|---|---|
| Page Name | |
| URL | |
| Route | |
| Controller | |
| Model | |
| Table | |
| Primary Key | |
| Permission | |
| Search | |
| Filter | |
| Sort | |
| Pagination | |
| Add New | |
| Edit | |
| Delete | |
| Status | |
| Import | |
| Export | |
| Special Logic | |

Then provide detailed sections underneath.

---

# 42. IMPLEMENTATION CHECKLIST

At the end create:

```text
[ ] Data Master sidebar matches Laravel
[ ] Every Laravel Data Master page exists
[ ] URLs/routes implemented
[ ] Role permissions implemented
[ ] Tables match Laravel
[ ] Columns match Laravel
[ ] Search matches Laravel
[ ] Filters match Laravel
[ ] Sorting matches Laravel
[ ] Pagination matches Laravel
[ ] Row count matches Laravel
[ ] Add New matches Laravel
[ ] Edit matches Laravel
[ ] Delete matches Laravel
[ ] Status behavior matches Laravel
[ ] Inactive behavior matches Laravel
[ ] Import matches Laravel
[ ] Export matches Laravel
[ ] Validation matches Laravel
[ ] Relationships preserved
[ ] Existing DB records preserved
[ ] Existing IDs preserved
[ ] No duplicate master tables
[ ] No destructive migration
[ ] No database reset
```

Add all additional requirements discovered during the audit.

---

# 43. FINAL ACCEPTANCE CRITERIA

The Next.js implementation is complete only when:

### Navigation
- Data Master navigation matches Laravel.
- All actual Data Master pages are available.
- Unauthorized users cannot access restricted functions.

### Tables
- Same columns.
- Same column order.
- Same labels.
- Same relationships.
- Same status behavior.

### Search
- Same searchable fields.
- Same matching behavior.
- Same automatic/manual behavior.

### Filters
- Same filters.
- Same values.
- Same defaults.
- Same inactive behavior.

### CRUD
- Add New behaves the same.
- Edit behaves the same.
- Delete behaves the same.
- Validation behaves the same.

### Import
- Same supported file types.
- Same columns.
- Same validation.
- Same duplicate behavior.
- Same result handling.

### Export
- Same columns.
- Same order.
- Same filtered/unfiltered behavior.
- Equivalent formatting.

### Database
- Existing MySQL data remains intact.
- No unnecessary duplicate tables.
- No unnecessary duplicate records.
- Existing relationships remain valid.

### Security
- Role permissions are enforced server-side.
- UI hiding alone is not authorization.
- Passwords/secrets are never exposed in audit output.

---

# 44. IMPORTANT AGENT INSTRUCTION

**Do not begin by coding the Next.js Data Masters.**

First produce the audit.

Correct sequence:

```text
Laravel localhost:8000
        ↓
Inspect UI
        ↓
Inspect routes
        ↓
Inspect controllers
        ↓
Inspect models
        ↓
Inspect migrations
        ↓
Inspect validation
        ↓
Inspect import/export
        ↓
Inspect database
        ↓
Test safe UI behavior
        ↓
Document actual behavior
        ↓
Create replication specification
        ↓
Implement Next.js localhost:3000
        ↓
Compare Laravel vs Next.js
        ↓
Fix differences
```

---

# 45. FINAL COMPARISON TEST

After implementation, test the same scenario in both applications:

```text
Laravel localhost:8000
        VS
Next.js localhost:3000
```

Compare:

1. Page title.
2. Sidebar location.
3. URL.
4. Table columns.
5. Existing records.
6. Search.
7. Filters.
8. Sorting.
9. Pagination.
10. Row count.
11. Add New.
12. Validation.
13. Edit.
14. Delete.
15. Status.
16. Inactive rows.
17. Import.
18. Export.
19. Permissions.
20. Error handling.
21. Success notifications.
22. Relationships.
23. Automatic fields.

Any difference must be documented and corrected unless required by the new Next.js architecture.

---

# 46. SCOPE LIMITATION

This task is specifically about **Data Master pages and their supporting logic**.

Do not redesign unrelated modules such as:

- Line Balancing
- Operational Breakdown
- Cycle Time
- Kaizen
- OSCP
- Skill Matrix
- TPM
- VSM
- Reports
- Dashboard

However, if a non-Data-Master module directly depends on a Data Master, document that dependency so the Next.js implementation does not break it.

---

# 47. SOURCE OF TRUTH PRIORITY

When information conflicts, use:

```text
1. Actual current MySQL schema/data
2. Actual Laravel browser behavior
3. Laravel controller/model/request logic
4. Laravel migrations
5. Laravel seeders
6. Existing documentation/specifications
7. General assumptions
```

Never override actual current application behavior with an old specification without explicitly documenting the discrepancy.

---

# 48. END GOAL

The final result should allow a developer to open:

```text
http://localhost:3000
```

and use the Next.js Data Master area as if they were using:

```text
http://localhost:8000
```

with the same:

- master pages,
- data,
- table structures,
- CRUD behavior,
- search,
- filters,
- sorting,
- status handling,
- import,
- export,
- validation,
- permissions,
- relationships,
- and special business logic.

The Next.js application may use different internal technologies, but the **observable Data Master functionality must remain equivalent to the Laravel source application**.
