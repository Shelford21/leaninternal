# LEAN ENTERPRISE — AI Agent Implementation Task
## Data Master Uppercase, Employees Structure, Login UI, Viewer Permissions, Employee Profiles & Developer Hard Delete

**Project:** LEAN ENTERPRISE  
**Stack:** Laravel 10, PHP 8.1+, MySQL, Blade/Tailwind/Vite

---

# 0. IMPORTANT RULES — AUDIT FIRST

Before changing anything, inspect the existing implementation:

- Routes
- Controllers
- Models
- Migrations
- Seeders
- Existing MySQL tables/columns
- Blade views/components
- Data Master shared components
- Authentication and role middleware
- Search/filter/sort implementation
- Import/export implementation
- Add/New forms
- Existing soft-delete/status logic
- Existing employee/operator structures
- Existing sidebar/navigation

The existing MySQL database contains values that must be preserved unless the user explicitly asks to delete them.

## DO NOT

Do not run:

```bash
php artisan migrate:fresh
```

Do not:

- Drop unrelated tables.
- Truncate unrelated tables.
- Recreate the whole database.
- Delete existing user-entered values unless explicitly requested.
- Replace working relationships blindly.
- Create duplicate master tables when an existing table can be reused.
- Hard-delete records through ordinary Data Master Delete buttons.

Use incremental migrations only when schema changes are necessary.

The database remains the source of truth.

---

# PART 1 — UPPERCASE ALL DATA MASTER INPUTS AND EXISTING VALUES

## 1. Uppercase Every Data Master Input

Every input field on every Data Master page must automatically convert user-entered text to uppercase.

This applies to:

- New forms
- Edit forms
- Search/filter inputs where text values are entered
- Import values
- Other text inputs used by Data Masters

Example:

```text
user enters:
lean & ie

stored/displayed:
LEAN & IE
```

## Important

Do not apply uppercase blindly to:

- Passwords
- Email addresses if the authentication system requires their original/canonical handling
- File paths
- URLs
- System-generated IDs
- Numeric values
- Dates
- Boolean values
- Non-text fields

The requirement specifically concerns Data Master text values.

---

# 2. Uppercase Existing Data Master Rows

Every row that already exists in every Data Master must be normalized to uppercase for applicable text fields.

Before modifying existing data:

1. Audit each Data Master table.
2. Identify text columns that represent Data Master values.
3. Create a safe migration/normalization strategy if needed.
4. Preserve IDs and relationships.
5. Convert applicable existing text values to uppercase.

Examples:

```text
Warehouse -> WAREHOUSE
Sewing -> SEWING
Computer Stitching -> COMPUTER STITCHING
EU -> EU
```

Do not uppercase fields where capitalization has technical meaning or where the field is not a Data Master value.

## Important

Do not change existing database relationships merely to perform this normalization.

If a value is referenced by a foreign key, preserve the relationship.

---

# PART 2 — EMPLOYEES DATA MASTER

# 3. Employees — Start Date Filter

On the Employees Data Master page, Start Date must use a date-range filter.

Use a calendar/date-picker style UI.

Provide:

```text
Start Date From
Start Date To
```

Example:

```text
From: 01/01/2026
To:   31/03/2026
```

The filter must work with:

- From only
- To only
- Both From and To
- Empty range

Use actual date comparisons, not text comparisons.

---

# 4. Employees — Date of Birth Filter

Date of Birth must also use a calendar/date-range filter.

Provide:

```text
Date of Birth From
Date of Birth To
```

The filtering must operate against the actual Date of Birth database field.

Use a date-picker/calendar control consistent with the Start Date filter.

---

# 5. Add Employees Organizational Columns

Add these columns to the Employees Data Master:

```text
Factory
Department
Division
Section
Line
```

The Employees table should contain these organizational values in addition to the existing required Employee fields.

---

# 6. Employees Relationships

Do not create duplicate free-text master values if corresponding Data Masters already exist.

Use existing master data/relationships for:

- Factory
- Department
- Division
- Section
- Production Line

Audit the current schema first.

If the existing Employees/Operators table already contains some of these relationships, reuse them.

If some fields do not currently exist, add them using incremental migrations.

Prefer foreign keys to master records where the existing architecture uses foreign keys.

---

# 7. Employees New/Edit Form

Update the Employees New/Edit form so the user can manage:

```text
Factory
Department
Division
Section
Line
```

Use dropdown/select fields populated from existing Data Master values.

Do not hard-code the lists if the corresponding Data Master already exists.

Respect inactive master records according to the existing application convention.

---

# 8. Employees Import

Update Employees import to support:

```text
Factory
Department
Division
Section
Line
```

The import must resolve the values against existing Data Masters where applicable.

Do not create duplicate master values from an employee import.

All applicable imported text values must follow the uppercase requirement.

---

# 9. Employees Export

Update Employees export to include:

```text
Factory
Department
Division
Section
Line
```

The export must reflect the actual Employee data.

Use the existing Data Master export formatting/convention.

---

# 10. Remove Start Date Column From Employees Table

The user specifically requested that the Employees Data Master no longer display:

```text
Start Date
```

Remove Start Date from the visible Employees table.

However, **do not automatically delete the Start Date database field** if it is required for:

- Date-range filtering
- Working Age
- Years of Service
- Employee history
- Existing reports
- Existing calculations

The field may remain as an internal/source field while being hidden from the main table.

Also adjust New/Edit/Import/Export only according to the existing business logic and the user's latest requirements. Do not remove the underlying source field merely because the table column is hidden.

---

# PART 3 — LOGIN PAGE REDESIGN

# 11. Modern Responsive Login Page

Redesign the login page to look modern and professional.

Requirements:

- Dark gradient background
- Modern card/container
- Clear LEAN ENTERPRISE branding
- Clean username field
- Clean password field
- Login button
- Appropriate validation/error messages
- Responsive design
- Desktop support
- Tablet support
- Mobile support

Use the existing Laravel authentication flow.

Do not change authentication logic unnecessarily just to redesign the UI.

## Responsive behavior

The login page must remain usable at small mobile widths without:

- Horizontal overflow
- Cropped form
- Overlapping controls
- Unreadable text
- Broken buttons

Use the existing Tailwind setup.

---

# PART 4 — NORMAL USER / VIEWER PERMISSIONS

# 12. Viewer Is Read-Only

The normal user/viewer role must ONLY be able to:

- View
- Search
- Filter
- Sort
- Export/download where allowed

The viewer must NOT be able to:

- Edit
- Add/New
- Delete
- Soft delete
- Hard delete
- Import data if Import is considered a modifying operation
- Modify reports/data

## Important

This must be enforced server-side, not merely by hiding buttons.

A viewer must not be able to bypass the restriction by manually entering an edit/create/delete URL.

Use the existing role middleware/authorization architecture.

---

# 13. Viewer UI

For viewer users:

Hide or disable modifying controls such as:

```text
+ New
Edit
Delete
Import
Hard Delete
```

Keep allowed controls such as:

```text
Search
Filter
Sort
Export
View/Detail
```

Do not hide Export if exporting is explicitly allowed.

---

# 14. Viewer Server-Side Authorization

Audit every affected controller/action.

The backend must reject unauthorized requests for:

```text
POST create
PUT/PATCH update
DELETE soft delete
POST import
POST hard delete
```

Return the application's normal authorization response/error.

Do not rely solely on Blade conditionals.

---

# PART 5 — EDUCATIONAL LEVEL AND STATUS PKWTT DISPLAY BUG

# 15. Fix Educational Level Data Master

The Educational Level Data Master currently has values in MySQL, but the table displays no values.

Fix this.

Audit:

- Model
- Controller
- Query
- Column names
- Database table name
- Blade variable names
- Data mapping
- Status filtering
- Soft-delete scopes

The page must display the existing MySQL values correctly.

Do not replace the existing records with fake seed data merely to make them appear.

---

# 16. Fix Status PKWTT Data Master

The Status PKWTT Data Master has the same issue:

- Values exist in MySQL.
- The web table does not display them.

Fix the underlying query/mapping/view issue.

Verify that existing records such as the user's previously entered values remain intact.

Do not delete/recreate the records unnecessarily.

---

# PART 6 — EMPLOYEE PROFILE PAGE

# 17. Add Employee Profile Page Under Lean Operations

Under the sidebar section:

```text
Lean Operations
```

add a page called:

```text
Employees Profile
```

Use the existing sidebar/navigation conventions.

---

# 18. Employee Profile List Table

The Employees Profile page must contain a table with exactly these primary columns:

| No | Name | Role | Line | Department | Factory | Status | Action |
|---|---|---|---|---|---|---|---|

The values should come from the Employee/Operator data already stored in the application.

Do not create a second employee database just for the profile page.

---

# 19. Employee Profile Functions

The Employee Profile page should behave like the other Data Master pages with the existing appropriate functions:

- Import
- Export
- New
- Delete/soft delete
- Search
- Filter By
- Show Inactive
- Sorting
- Row count

However, all modifying functions must respect the user's role.

Therefore:

### Developer/Admin

Can perform the modifying operations allowed by the existing role policy.

### Viewer/Normal User

Can only:

- View
- Search
- Filter
- Sort
- Export
- Open employee details

Viewer must not:

- New
- Edit
- Import
- Soft delete
- Hard delete

---

# 20. Employee Profile Detail Button

The Action column must contain a detail/view function.

Example:

```text
Detail
```

When pressed, open the selected Employee details page.

Use a unique Employee ID in the route.

Do not use the employee's name as the only identifier.

---

# 21. Employee Details Page

The detail page must show Employee information.

At minimum:

```text
Name
Role
Line
Department
Factory
```

These values must be connected to the same Employee record shown in the previous table.

Example flow:

```text
Employees Profile
       ↓
Click Detail on Fauzan
       ↓
Employee ID = 123
       ↓
Employee Details
       ↓
Name / Role / Line / Department / Factory
come from Employee ID 123
```

Do not use hard-coded profile information.

---

# 22. Employee Profile Data Relationship

If Employees Profile and Employees Data Master are intended to represent the same employee entity, reuse the same underlying table/model.

Do not create:

```text
employee_profiles
```

unless the existing architecture genuinely requires a separate profile table.

Prefer one authoritative Employee record plus related master data.

---

# PART 7 — DEPARTMENTS DATA MASTER

# 23. Remove Factory Column From Departments

On the Departments Data Master page, remove:

```text
Factory
```

from the visible table.

Also adjust related:

- New/Edit
- Import
- Export
- Filters
- Validation
- Search where applicable

so Factory is no longer required by the Departments Data Master UI.

---

# 24. Department Database Safety

Before removing the Department Factory field/relationship from the UI:

Audit whether it is used elsewhere.

If other modules depend on the database relationship:

- Do not blindly drop the column.
- Remove it from the requested Department Data Master interface.
- Preserve existing historical data unless explicitly instructed otherwise.

If the database field truly must be removed, use an incremental migration and verify dependencies first.

---

# PART 8 — FIX ALL DATA MASTER DELETE, IMPORT, AND NEW FUNCTIONS

# 25. Audit Every Data Master

The user reports that the following functions currently do not work correctly across Data Masters:

- Delete
- Import
- Add New

Audit every Data Master individually.

At minimum check:

1. Processes
2. Employees/Operators
3. Articles
4. GSD Elements
5. Factories
6. Departments
7. Skill Gradings
8. Divisions
9. Sections
10. Machine Types
11. Components/Panels
12. Machine Numbers
13. Shifts
14. Failure Modes
15. Mechanics
16. Spare Parts
17. Production Lines
18. Genders
19. Production Roles
20. Educational Level
21. Status PKWTT
22. Destination
23. Any additional Data Master currently present

---

# 26. Fix Add/New Function

For every Data Master:

Verify:

1. New button opens the correct modal/page.
2. Required fields validate correctly.
3. Text values are converted to uppercase where applicable.
4. Dropdowns load existing Data Master values.
5. Record is actually saved to MySQL.
6. Success notification appears.
7. New record appears in the table.
8. Row count updates.
9. Search finds it.
10. Filters find it.
11. Export includes it.

Do not merely fix the button visually.

Trace the complete flow:

```text
Blade form
→ route
→ controller
→ validation
→ model
→ database
→ redirect/response
→ table
```

---

# 27. Fix Import Function

For every Data Master import:

Verify:

1. Upload control works.
2. File validation works.
3. Correct columns are recognized.
4. Existing Data Master conventions are respected.
5. Text values are uppercased where appropriate.
6. Existing records are not accidentally overwritten unless the import logic explicitly supports updates.
7. Duplicate handling works.
8. Validation errors are reported clearly.
9. Successful rows are saved.
10. Row count updates.
11. Search/filter finds imported records.
12. Export includes imported records.

Test with a real small Excel import file.

---

# 28. Fix Soft Delete Function

The normal Delete function on every Data Master must be a **soft delete**.

When confirmed:

```text
status = inactive / Not Active
```

according to the existing status convention.

Do NOT permanently remove the row through the normal Data Master Delete button.

The record must remain in MySQL.

It should become visible when:

```text
Show Inactive
```

is enabled.

---

# PART 9 — REVERSE THE EARLIER HARD DELETE DESIGN

# 29. Normal Data Master Delete = Soft Delete Only

The previous request to make Hard Delete available directly on Data Master pages must be reversed.

From now on:

```text
Data Master Delete
        ↓
SOFT DELETE ONLY
```

No Data Master page should permanently delete records through its ordinary Delete action.

---

# PART 10 — DEVELOPER-ONLY HARD DELETE PAGE

# 30. Create Hard Delete Page

Under the sidebar section:

```text
Management
```

create a page called:

```text
Hard Delete
```

This page must be accessible ONLY to the `developer` role.

Admins and viewers must not be allowed to access it.

Server-side authorization is mandatory.

---

# 31. Hard Delete Page Purpose

The Hard Delete page is the centralized permanent-deletion area.

It should permanently delete inactive records from Data Master tables.

The normal Data Master pages must NOT permanently delete records.

---

# 32. Hard Delete UI

The page should clearly communicate that this is a destructive operation.

Example:

```text
HARD DELETE

This action permanently removes inactive records and cannot be undone.

[ Hard Delete Inactive Rows ]
```

Use a strong confirmation dialog before execution.

The developer must explicitly confirm the action.

---

# 33. Hard Delete Scope

The Hard Delete operation must target inactive/not-active rows from Data Master tables.

At minimum audit these Data Masters:

- Processes
- Employees/Operators
- Articles
- GSD Elements
- Factories
- Departments
- Skill Gradings
- Divisions
- Sections
- Machine Types
- Components/Panels
- Machine Numbers
- Shifts
- Failure Modes
- Mechanics
- Spare Parts
- Production Lines
- Genders
- Production Roles
- Educational Level
- Status PKWTT
- Destination
- Any additional Data Master tables

Do not hard-delete active records.

---

# 34. Hard Delete Confirmation

Before permanent deletion:

1. Display the number of inactive records that will be affected.
2. Explain that the action is irreversible.
3. Require explicit confirmation.
4. Re-query the database server-side.
5. Verify each targeted record is still inactive.
6. Delete only eligible inactive records.
7. Handle foreign-key dependencies safely.
8. Do not disable foreign-key checks merely to force deletion.
9. Show a success/error summary.

If records cannot be safely deleted because another table references them:

- Do not corrupt referential integrity.
- Do not disable FK checks.
- Report which records could not be deleted and why.

---

# 35. Hard Delete Authorization

Use developer-only authorization at both:

### UI level

Only developer sees:

```text
Management
  └── Hard Delete
```

### Server level

A non-developer must receive an authorization failure if they manually access the route.

Do not rely only on hiding the sidebar item.

---

# PART 11 — ROLE BEHAVIOR SUMMARY

## Developer

Can:

- View
- Search
- Filter
- Sort
- Export
- New
- Edit
- Import
- Soft Delete
- Access Hard Delete
- Permanently delete inactive Data Master rows through Hard Delete

## Admin

Can use normal application management functions according to the existing admin policy, but:

- Cannot access Developer-only Hard Delete.
- Cannot permanently hard-delete through the Data Master pages.

## Viewer / Normal User

Can only:

- View
- Search
- Filter
- Sort
- Export
- View Employee Details

Cannot:

- New
- Edit
- Import
- Soft Delete
- Hard Delete
- Permanently delete

---

# PART 12 — DATA PRESERVATION

# 36. Preserve Existing MySQL Values

Unless explicitly requested otherwise, preserve existing MySQL values.

The only changes that should intentionally modify existing records are those required by this task, such as:

- Uppercasing applicable Data Master text values
- Schema changes explicitly required
- Soft-delete state when the user intentionally deletes a record

Do not use destructive database reset commands.

---

# PART 13 — SEEDING

# 37. Seed Existing/Required Data Carefully

Audit current seeders before modifying them.

If seeders are used to preserve required master data:

- Make them idempotent where appropriate.
- Use stable unique keys.
- Do not create duplicate rows when `php artisan db:seed` is run repeatedly.
- Do not replace user-entered values unnecessarily.

The user's latest request does not ask for destructive replacement of existing master data.

Therefore, do not delete existing master rows simply to rebuild seeders.

---

# PART 14 — TESTING

# 38. Test Uppercase

Test on every applicable Data Master:

### New

Enter:

```text
lean & ie
```

Verify:

```text
LEAN & IE
```

### Edit

Verify edited text is uppercased.

### Import

Import lowercase text and verify stored/displayed values are uppercase.

### Existing data

Verify applicable existing Data Master values were normalized to uppercase.

---

# 39. Employees Tests

Verify:

- [ ] Employees page works.
- [ ] Start Date is no longer a visible table column.
- [ ] Start Date range filter works with calendar controls.
- [ ] Date of Birth range filter works with calendar controls.
- [ ] Factory column exists.
- [ ] Department column exists.
- [ ] Division column exists.
- [ ] Section column exists.
- [ ] Line column exists.
- [ ] New/Edit works.
- [ ] Import works.
- [ ] Export works.
- [ ] Search works.
- [ ] Filters work.
- [ ] Existing Employee records remain intact.

---

# 40. Login Tests

Test at:

- Desktop width
- Tablet width
- Mobile width

Verify:

- [ ] Dark gradient appears correctly.
- [ ] Form is centered and readable.
- [ ] Username works.
- [ ] Password works.
- [ ] Validation messages work.
- [ ] Login succeeds with valid credentials.
- [ ] Invalid login remains properly rejected.
- [ ] No horizontal overflow occurs.

---

# 41. Viewer Permission Tests

Login as viewer/normal user.

Verify viewer can:

- [ ] View Data Masters.
- [ ] Search.
- [ ] Filter.
- [ ] Sort.
- [ ] Export.
- [ ] View Employee Details.

Verify viewer cannot:

- [ ] Add New.
- [ ] Edit.
- [ ] Import.
- [ ] Soft Delete.
- [ ] Hard Delete.
- [ ] Bypass restrictions through direct URLs.

---

# 42. Educational Level / Status PKWTT Tests

Verify both pages now display the records that already exist in MySQL.

Do not accept a solution that merely inserts duplicate replacement rows.

Verify the actual existing records are correctly queried and rendered.

---

# 43. Employee Profile Tests

Verify:

- [ ] Sidebar item exists under Lean Operations.
- [ ] Page opens.
- [ ] Table contains No, Name, Role, Line, Department, Factory, Status, Action.
- [ ] Search works.
- [ ] Filter works.
- [ ] Show Inactive works.
- [ ] New works for authorized roles.
- [ ] Import works for authorized roles.
- [ ] Export works.
- [ ] Soft Delete works for authorized roles.
- [ ] Detail opens correct Employee.
- [ ] Detail Name is correct.
- [ ] Detail Role is correct.
- [ ] Detail Line is correct.
- [ ] Detail Department is correct.
- [ ] Detail Factory is correct.

---

# 44. Hard Delete Tests

Test as Developer:

1. Soft-delete an active Data Master row.
2. Verify it becomes inactive.
3. Open Hard Delete.
4. Verify the inactive row is eligible.
5. Confirm hard deletion.
6. Verify the row is permanently removed.

Test as Admin:

- Hard Delete page must be inaccessible.

Test as Viewer:

- Hard Delete page must be inaccessible.

Also verify active records cannot be hard-deleted.

---

# 45. Data Master New/Import/Delete Regression Tests

For every Data Master:

### New

- Open form.
- Enter valid data.
- Save.
- Verify MySQL.
- Verify table.

### Import

- Import a small valid file.
- Verify records are inserted correctly.
- Verify uppercase behavior.
- Verify errors are shown for invalid data.

### Delete

- Select an active row.
- Use normal Delete.
- Confirm.
- Verify it becomes inactive.
- Verify it still exists in MySQL.
- Verify Show Inactive displays it.

### Hard Delete

- Access only from Developer → Management → Hard Delete.
- Verify inactive row can be permanently removed.

---

# PART 15 — CHANGELOG

# 46. Update `changelogs.md`

Every meaningful change must be recorded in:

```text
changelogs.md
```

Document:

- Date
- Feature
- UI changes
- Database changes
- Migration names
- Seeder changes
- Permission changes
- Authentication changes
- Data normalization changes
- Import/export changes
- Filter changes
- Delete behavior changes
- Testing performed
- Known limitations

Example:

```markdown
## 2026-10-01

### Data Masters
- Added uppercase normalization for applicable Data Master inputs and existing values.
- Fixed New, Import, and Soft Delete functionality.
- Added/reworked role restrictions.

### Employees
- Added Factory, Department, Division, Section, and Line.
- Removed Start Date from the visible table.
- Added date-range calendar filters for Start Date and Date of Birth.

### Authentication
- Redesigned Login page with responsive dark-gradient UI.
- Restricted Viewer to view/search/filter/sort/export operations.

### Employee Profile
- Added Employees Profile under Lean Operations.
- Added Employee Detail page connected to the Employee record.

### Hard Delete
- Reversed direct Data Master Hard Delete behavior.
- Normal Data Master Delete is now soft delete only.
- Added developer-only Management → Hard Delete page.

### Bug Fixes
- Fixed Educational Level table not displaying existing MySQL values.
- Fixed Status PKWTT table not displaying existing MySQL values.

### Departments
- Removed Factory from the Data Master UI.

### Testing
- ...
```

Use the actual changes made; do not blindly copy this example.

---

# PART 16 — FINAL VERIFICATION CHECKLIST

Before declaring the implementation complete:

- [ ] All applicable Data Master text inputs uppercase values.
- [ ] Existing applicable Data Master text values normalized to uppercase.
- [ ] Employees Start Date range filter uses calendar/date controls.
- [ ] Employees Date of Birth range filter uses calendar/date controls.
- [ ] Employees has Factory.
- [ ] Employees has Department.
- [ ] Employees has Division.
- [ ] Employees has Section.
- [ ] Employees has Line.
- [ ] Employees Start Date is removed from the visible table.
- [ ] Employees New/Edit updated.
- [ ] Employees Import updated.
- [ ] Employees Export updated.
- [ ] Login page is modern, dark-gradient, and responsive.
- [ ] Viewer can view/search/filter/sort/export.
- [ ] Viewer cannot New/Edit/Import/Soft Delete/Hard Delete.
- [ ] Server-side authorization blocks viewer modification attempts.
- [ ] Educational Level displays existing MySQL values.
- [ ] Status PKWTT displays existing MySQL values.
- [ ] Employees Profile exists under Lean Operations.
- [ ] Employee Profile table has required columns.
- [ ] Employee Detail page loads the correct Employee record.
- [ ] Department Factory column removed.
- [ ] New works on every Data Master.
- [ ] Import works on every Data Master.
- [ ] Normal Delete works as soft delete on every Data Master.
- [ ] No normal Data Master Delete permanently removes rows.
- [ ] Management → Hard Delete exists for Developer only.
- [ ] Hard Delete permanently removes inactive records only.
- [ ] Admin cannot access Hard Delete.
- [ ] Viewer cannot access Hard Delete.
- [ ] Existing MySQL values are preserved unless explicitly changed by this task.
- [ ] No `migrate:fresh` was used.
- [ ] `changelogs.md` updated.
- [ ] All requested functionality tested.

---

# FINAL INSTRUCTION TO THE AI AGENT

Implement this task in the existing LEAN ENTERPRISE codebase.

**Audit the current implementation first. Do not guess the schema.**

**Preserve existing MySQL data unless the user explicitly asks for deletion.**

**Use incremental migrations. Do not use `php artisan migrate:fresh`.**

**Reuse existing Data Master tables, relationships, components, authentication, authorization, search/filter system, import/export system, and status conventions wherever possible.**

The most important delete rule is:

```text
Normal Data Master Delete
        ↓
SOFT DELETE / INACTIVE

Developer → Management → Hard Delete
        ↓
PERMANENTLY DELETE INACTIVE RECORDS
```

The viewer/normal-user rule is:

```text
VIEW
SEARCH
FILTER
SORT
EXPORT

ONLY
```

No viewer modification must be possible through either the UI or direct backend requests.

After implementation:

1. Test every requested feature.
2. Verify existing MySQL data is preserved.
3. Verify existing Education Level and Status PKWTT values display correctly.
4. Verify all Data Master New/Import/Delete functions.
5. Verify role authorization server-side.
6. Verify Hard Delete is developer-only.
7. Verify normal Delete is soft delete only.
8. Update `changelogs.md`.
9. Run the appropriate Laravel build/cache/migration commands without destructive database resets.
