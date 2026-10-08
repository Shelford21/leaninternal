# LEAN ENTERPRISE — Laravel → Next.js Full-Stack Audit & Migration Prompt

## Mission

You are an AI coding agent working on the existing **LEAN ENTERPRISE** application.

The current application was built with Laravel/PHP/Blade/MySQL. It is being migrated to:

- Next.js
- TypeScript
- React
- Next.js as the full-stack framework
- MySQL
- a TypeScript-compatible ORM/database layer
- server-side business logic inside Next.js

Your task is **not** to blindly rewrite PHP into TypeScript.

Your task is to:

1. Completely audit the existing Laravel application.
2. Discover its actual routes, pages, CRUD, database schema, relationships, business rules, calculations, validation, authentication, authorization, imports, exports, file handling, logs, and UI behavior.
3. Produce a comprehensive Markdown audit.
4. Produce a detailed Laravel → Next.js migration specification that another AI coding agent can follow.
5. Preserve the existing MySQL database and existing application behavior.

**Audit first. Document second. Implement third.**

---

# 1. Source of truth

Use the following priority:

1. Actual Laravel source code.
2. Actual MySQL schema/data structure.
3. Existing tests.
4. Existing runtime/UI behavior where inspectable.
5. Existing project documentation.
6. Previous requirements/specifications.

Do not silently replace actual behavior with assumptions from previous conversations.

If the source does not define something, write:

> Not found in source.

If two sources conflict, document the conflict instead of silently choosing one.

---

# 2. Required deliverables

Create a comprehensive Markdown document containing:

1. Executive Summary
2. Existing Laravel Stack
3. Project Structure
4. Route Inventory
5. Page Inventory
6. Authentication
7. Authorization
8. Database Schema
9. Models and Relationships
10. Data Masters
11. CRUD Matrix
12. Business Rules
13. Calculations
14. Search / Filter / Sort
15. Import System
16. Export System
17. File / Image Storage
18. Logs / Auditing
19. Background Jobs / Scheduler
20. Frontend JavaScript Behavior
21. UI Components
22. Tests
23. Existing Problems / Risks
24. Laravel → Next.js Mapping
25. Next.js Target Architecture
26. Database / ORM Strategy
27. Authentication Migration
28. Authorization Migration
29. API / Server Action Mapping
30. UI Component Mapping
31. Import / Export Migration
32. File Storage Migration
33. Calculation Migration
34. Environment Variable Mapping
35. Docker / Coolify Deployment
36. Migration Phases
37. Testing Plan
38. Acceptance Criteria
39. Items Requiring Human Confirmation
40. Known Limitations

---

# 3. Audit the complete Laravel project

Inspect, where present:

```text
app/
bootstrap/
config/
database/
public/
resources/
routes/
storage/
tests/
composer.json
.env.example
package.json
vite.config.*
```

Also inspect project-specific directories.

Inventory:

- controllers
- models
- services
- repositories
- form requests
- middleware
- policies
- gates
- events
- listeners
- jobs
- commands
- notifications
- mail
- resources
- Blade layouts
- Blade components
- Blade views
- JavaScript
- CSS
- routes
- migrations
- seeders
- factories
- tests
- helpers
- observers
- scheduled tasks.

Do not assume standard Laravel behavior if this project is customized.

---

# 4. Audit the actual MySQL database

Document every table and:

- columns
- data types
- nullable/non-nullable
- defaults
- primary keys
- foreign keys
- unique constraints
- indexes
- composite indexes
- soft-delete columns
- timestamps
- relationships
- pivot tables
- status fields
- enum-like fields
- generated/derived values.

Distinguish:

### Source fields
Values users actually enter or maintain.

### Reference fields
Values originating from another master/entity.

### Derived fields
Values calculated from other values.

Do not turn a derived value into an editable database field unless the Laravel application actually stores it.

---

# 5. Database preservation — critical

The existing MySQL database is valuable.

The default migration strategy must be:

```text
Existing MySQL
      ↓
Next.js + TypeScript
      ↓
Same existing data
```

Do NOT:

```bash
php artisan migrate:fresh
php artisan db:wipe
```

Do NOT delete and recreate the database.

Do NOT create an empty replacement database just because the ORM changes.

Do NOT destroy existing tables to make them match a newly generated ORM schema.

The migration must establish compatibility with the existing schema first.

Document the safe baseline/migration strategy for the chosen ORM.

---

# 6. Audit all routes

Inspect:

- routes/web.php
- routes/api.php
- other route files.

For every route document:

| Laravel Route | Method | Middleware | Controller/Action | Purpose | Auth | Role | Next.js Target |
|---|---|---|---|---|---|---|---|

Include:

- GET
- POST
- PUT
- PATCH
- DELETE
- resource routes
- named routes
- parameters
- route model binding
- middleware
- authorization
- redirects
- validation
- response type.

Do not omit routes that look unimportant.

---

# 7. Audit every page

For every page document:

- URL
- title
- sidebar location
- access rules
- table columns
- forms
- modals
- actions
- search
- filters
- sorting
- pagination
- import
- export
- delete behavior
- status behavior
- calculations
- dependent dropdowns
- images/files
- loading state
- empty state
- error state
- confirmations.

Do not assume pages from old requirements actually exist. Verify them.

---

# 8. Audit CRUD for every entity

For every entity document exactly:

## Create
- fields
- required/optional
- defaults
- transformations
- uppercase/lowercase behavior
- validation
- generated values
- relationships
- file uploads
- authorization.

## Read
- list
- detail
- relationships
- calculated values
- search
- filters
- sorting
- pagination.

## Update
- editable fields
- immutable fields
- validation
- side effects
- related records affected.

## Delete
Determine whether the Laravel implementation uses:

- soft delete
- hard delete
- status change
- archive
- cascade
- restricted deletion
- custom behavior.

Preserve the actual behavior.

---

# 9. Audit all Data Masters

Find every actual Data Master.

For each document:

- table
- model
- route
- controller
- view
- fields
- types
- unique fields
- status
- descriptions
- search
- filters
- sorting
- import
- export
- create
- edit
- delete
- soft delete
- hard delete
- inactive behavior
- relationships
- where the master is used.

Trace each Data Master into dependent modules such as Employees, Articles, Processes, GSD, Line Balancing, Operational Breakdown, TPM, etc.

Do not create duplicate masters in Next.js.

---

# 10. Audit authentication

Inspect the actual implementation and document:

- login route
- login fields
- username/email behavior
- password hashing
- session mechanism
- logout
- remember-me
- failed login handling
- rate limiting
- password reset
- registration if present
- account status
- activation/deactivation.

Do not weaken password security.

Do not store plaintext passwords.

---

# 11. Audit authorization

Inspect:

- roles
- middleware
- policies
- gates
- controller checks
- Blade authorization checks.

Build a permission matrix:

| Feature | Developer | Admin | Viewer | Other |
|---|---|---|---|---|

Document both UI and backend authorization.

Hiding a button is not authorization.

---

# 12. Audit validation

Inspect:

- Form Requests
- controller validation
- custom validators
- database constraints
- frontend validation.

Document:

- required fields
- nullable fields
- min/max
- formats
- uniqueness
- conditional validation
- cross-field rules
- custom messages.

The Next.js version must preserve the actual rules.

---

# 13. Audit business logic and calculations

Search the whole application, not only controllers.

Inspect:

- models
- accessors/mutators
- services
- repositories
- helpers
- observers
- jobs
- commands
- SQL
- Blade expressions
- frontend JavaScript.

For every important calculation document:

```text
Name
Source file/method
Inputs
Exact formula
Output
Formatting
Where displayed
Where stored
```

Do not replace an existing formula with a "more correct" formula without explicit approval.

If something looks wrong, document:

> Potential existing business-rule issue

and preserve it in the migration specification unless the user approves changing it.

---

# 14. Dedicated Line Balancing audit

Perform a separate detailed audit of Line Balancing.

Inspect:

- report list
- report creation
- report edit
- report view
- report deletion
- report status
- factory
- article
- line
- target output
- process rows
- machine
- employee/operator
- operator quantity
- cycle-time observations
- stopwatch
- averages
- allowance
- output/hour
- request operator
- potential output
- total manpower
- summary KPIs
- Yamazumi chart
- Excel import/export
- calculations
- permissions
- history/logs.

If the old Excel template is implemented in Laravel, inspect the actual Laravel implementation rather than assuming the workbook is the only source of truth.

---

# 15. Audit imports

For every Excel/CSV import document:

- page
- endpoint/action
- file types
- expected columns
- ignored columns
- validation
- duplicate behavior
- insert/update behavior
- transaction behavior
- row-level errors
- rollback behavior
- success reporting.

---

# 16. Audit exports

For every export document:

- format
- columns
- ordering
- formatting
- formulas
- dates
- alignment
- borders
- images
- charts
- filename
- filters
- whether inactive/soft-deleted rows are excluded.

The Next.js version should preserve the same functional output.

---

# 17. Audit file/image handling

Inspect:

- employee photos
- article photos
- attachments
- report files
- storage disks
- public/private storage
- filenames
- validation
- resizing
- deletion.

Document:

```text
Existing storage
→ Existing references
→ Next.js access strategy
```

Do not break existing file references.

---

# 18. Audit search/filter/sort/pagination

For each relevant page document:

### Search
- columns searched
- partial/exact behavior
- case sensitivity
- relationship search.

### Filters
- fields
- values
- date ranges
- numeric ranges
- status/inactive.

### Sorting
- sortable fields
- ascending/descending
- default sort.

### Pagination
- page size
- total count
- default page.

---

# 19. Audit soft-delete/inactive behavior

Document:

- tables using soft delete
- tables using status
- whether delete changes status
- whether inactive records remain queryable
- Show Inactive behavior
- hard-delete behavior
- who can hard-delete.

Do not replace soft delete with permanent deletion.

---

# 20. Audit logs

Inspect:

- login logs
- logout logs
- activity logs
- audit tables
- event listeners
- CRUD logging
- import/export logging
- report actions.

Document fields such as:

- user
- action
- entity
- record ID
- timestamp
- IP
- user agent
- before/after values.

Preserve historical logs where practical.

---

# 21. Audit frontend behavior

Inspect:

- resources/js
- Alpine/Livewire if present
- AJAX/fetch/Axios
- dynamic forms
- dependent dropdowns
- stopwatch
- charts
- modals
- client-side validation
- Excel behavior.

Do not assume business logic exists only in PHP.

---

# 22. Audit jobs/scheduler

Inspect:

- scheduler
- queues
- jobs
- commands
- cron dependencies.

Document what runs, when, inputs, outputs, dependencies, and failure behavior.

Map these to an appropriate Next.js/server mechanism.

---

# 23. Audit tests

Inspect:

- feature tests
- unit tests
- browser tests
- manual test documentation.

Convert important existing tests into Next.js acceptance tests.

---

# 24. Target architecture

The target must be **Next.js as full-stack**.

Do not create Laravel as a backend dependency.

Target:

```text
Browser
   ↓
Next.js
   ├── React UI
   ├── Server Components
   ├── Client Components
   ├── Server Actions
   ├── Route Handlers
   ├── Authentication
   ├── Authorization
   ├── Validation
   ├── Business Services
   └── Database Access
          ↓
       Existing MySQL
```

Use TypeScript for application logic.

Prefer `.ts` and `.tsx`.

Avoid `any` unless justified.

---

# 25. Next.js App Router

Use the Next.js App Router unless the audit finds a concrete reason not to.

Map Laravel pages into appropriate `app/` routes.

Example only:

```text
app/
├── layout.tsx
├── login/page.tsx
├── dashboard/page.tsx
├── data-masters/...
├── employees/...
├── articles/...
├── processes/...
├── line-balancing/...
└── ...
```

Do not force this exact structure if the audit indicates a better organization.

---

# 26. Laravel → Next.js mapping

Create a concrete mapping for every actual Laravel component.

General mapping:

| Laravel | Next.js / TypeScript |
|---|---|
| Blade page | React page |
| Blade layout | Next.js layout |
| Blade component | React component |
| Controller | Server Action / Route Handler / service |
| Form Request | TypeScript validation schema |
| Eloquent model | ORM schema/model |
| Eloquent relation | ORM relation |
| Middleware | Next.js middleware/server authorization |
| Policy | authorization service |
| Gate | permission function |
| Session auth | Next.js auth/session system |
| Route | App Router route |
| API endpoint | Route Handler |
| Service | TypeScript service |
| Helper | TypeScript utility |
| Event/Listener | server-side application mechanism |
| Job | background-job equivalent |
| Command | TypeScript/server script |
| Migration | ORM migration |
| Seeder | TypeScript seed mechanism |
| Factory | test factory |
| Storage disk | storage/object-storage layer |
| Scheduler | server/deployment scheduler |
| PHPUnit | Vitest/Jest/Playwright equivalent |

For every actual Laravel implementation, provide the concrete target.

---

# 27. ORM/database layer

Choose a TypeScript MySQL ORM/query layer based on the audited schema.

Consider:

- Prisma
- Drizzle
- another established TypeScript solution.

Evaluate:

- existing-schema compatibility
- relations
- transactions
- raw SQL
- migrations
- type safety
- production deployment
- seed support.

Recommend one and explain why.

Do not force a new schema over the existing database.

---

# 28. Existing database migration strategy

The migration specification must explain:

1. Existing schema.
2. ORM representation.
3. Baseline/introspection strategy.
4. How existing tables are preserved.
5. How future migrations work.
6. How seed scripts work.
7. How production migrations are deployed.

The first goal is **safe compatibility with the existing MySQL database**.

---

# 29. Seeder replacement

Laravel's:

```bash
php artisan db:seed
```

will no longer be used.

Replace it with the chosen TypeScript ORM's seed mechanism.

For example, if Prisma is selected:

```bash
npx prisma db seed
```

But do not assume Prisma before evaluating the project.

Seeds must be:

- idempotent
- non-destructive
- safe to rerun
- explicit about reference/master data
- unable to overwrite legitimate user data accidentally.

---

# 30. Authentication migration

Rebuild authentication while preserving:

- login identifier
- password hashing
- session behavior
- logout
- account status
- roles
- permissions.

Do not expose password hashes to clients.

---

# 31. Authorization migration

Authorization must be enforced server-side.

Do not rely on frontend visibility.

For every Laravel authorization rule, provide the Next.js equivalent.

---

# 32. Server Actions / Route Handlers

For every Laravel controller action decide whether the target should be:

- Server Action
- Route Handler
- server service
- direct server-side database operation.

Do not expose privileged database logic to client components.

Avoid unnecessary APIs when Server Actions are appropriate.

---

# 33. React component architecture

Identify reusable UI patterns from the actual Laravel application.

Possible examples:

```text
DataTable
SearchBar
FilterBar
SortHeader
Pagination
Modal
ConfirmDialog
Toast
StatusBadge
FileUpload
DateRangeFilter
NumericRangeFilter
Dropdown
Sidebar
Header
KpiCard
Chart
Stopwatch
ExcelImport
ExcelExport
```

Only abstract components that are actually reused.

---

# 34. Preserve terminology

Preserve the application's real business terminology, including concepts such as:

- GSD
- SMV
- PPH
- Takt Time
- Cycle Time
- Line Balancing
- Operational Breakdown
- Skill Grading
- Production Role
- Status PKWTT
- Educational Level
- Factory
- Division
- Department
- Section
- Line
- Article
- Process
- Employee
- Machine Type

Only include terminology confirmed by the audit.

---

# 35. Preserve calculations exactly

For every calculation produce:

```text
Laravel source
→ exact formula
→ inputs
→ output
→ formatting
→ Next.js service/function
```

Centralize important calculations instead of duplicating formulas across Blade, React, APIs, and exports.

---

# 36. Preserve imports/exports

Every Laravel import/export needs a Next.js target.

Preserve:

- columns
- validation
- transformation
- duplicate behavior
- errors
- formatting
- filenames
- filters.

---

# 37. Preserve files/images

Do not delete or move existing files without a migration plan.

Document:

```text
Old path
→ New path
→ database reference
→ access mechanism
```

---

# 38. Environment variable mapping

Produce a complete mapping.

Example:

| Laravel | Next.js |
|---|---|
| DB_HOST | DATABASE_URL / ORM config |
| DB_PORT | DATABASE_URL |
| DB_DATABASE | DATABASE_URL |
| DB_USERNAME | DATABASE_URL |
| DB_PASSWORD | DATABASE_URL |
| APP_KEY | Next.js auth/encryption equivalent |
| FILESYSTEM_* | storage configuration |
| MAIL_* | email configuration |

Never commit secrets.

Never expose server secrets through `NEXT_PUBLIC_*`.

---

# 39. Local development

Replace:

```bash
php artisan serve
```

with the new Next.js workflow.

Normally:

```bash
npm run dev
```

Production-like local test:

```bash
npm run build
npm run start
```

Document exact commands based on the actual final package configuration.

---

# 40. Coolify / Docker deployment

The target deployment may use Coolify and Docker Compose.

Document:

- Node.js version
- package manager
- build command
- start command
- internal application port
- environment variables
- database URL
- healthcheck
- Dockerfile/Compose requirements
- Docker networks
- database connectivity.

If MySQL is already a separate Coolify resource, do not duplicate MySQL inside the Next.js Compose stack.

The Next.js application should use the appropriate private/internal MySQL hostname and shared Docker network.

Do not expose MySQL publicly unless explicitly required.

---

# 41. Testing strategy

Create a migration test matrix:

```text
Laravel behavior
→ Next.js implementation
→ test
→ expected result
```

Cover:

- login/logout
- roles
- permissions
- CRUD
- validation
- search
- filters
- sorting
- pagination
- import
- export
- soft delete
- hard delete
- status
- relationships
- calculations
- images
- reports
- logs.

For critical functionality verify:

```text
Laravel result == Next.js result
```

for equivalent inputs.

---

# 42. Conflict handling

If requirements and implementation differ, document:

```text
CONFLICT

Source A:
...

Source B:
...

Current runtime behavior:
...

Migration recommendation:
...

Human confirmation required:
Yes/No
```

Do not silently resolve conflicts.

---

# 43. Risk detection

Actively identify:

- duplicate tables
- duplicate concepts
- unused tables
- unused columns
- orphaned routes
- orphaned views
- hidden business logic
- hardcoded dropdown values
- hardcoded role checks
- duplicated formulas
- insecure authorization
- unsafe uploads
- secrets
- migration risks
- seed risks
- database compatibility risks.

Do not silently fix them during the audit. Document them first.

---

# 44. Migration phases

Produce a staged implementation plan:

## Phase 0 — Backup
- database backup
- file backup
- restore verification

## Phase 1 — Audit
- source audit
- database audit

## Phase 2 — Next.js foundation
- Next.js
- TypeScript
- App Router
- styling
- ORM
- environment

## Phase 3 — Existing MySQL connection
- introspect/verify schema
- verify relations
- no destructive changes

## Phase 4 — Authentication
- login
- sessions
- roles
- permissions

## Phase 5 — Shared UI
- layout
- sidebar
- tables
- forms
- modals
- notifications

## Phase 6 — Data Masters

Migrate and test one at a time.

## Phase 7 — Employees

## Phase 8 — Articles

## Phase 9 — Processes / GSD

## Phase 10 — Line Balancing

## Phase 11 — Remaining operational modules

## Phase 12 — Reports / exports

## Phase 13 — Logs

## Phase 14 — Full regression testing

## Phase 15 — Coolify production deployment

Do not attempt a giant one-shot rewrite.

---

# 45. Git safety

Before major changes inspect:

```bash
git status
git branch
git log --oneline -10
```

Use migration branches where appropriate.

Keep the Laravel version available until the Next.js version has passed regression testing.

---

# 46. Required audit tables

The final Markdown must include at minimum:

## Route Matrix

| Route | Method | Middleware | Controller | Behavior | Next.js Target |
|---|---|---|---|---|---|

## CRUD Matrix

| Entity | Create | Read | Update | Delete | Soft Delete | Import | Export |
|---|---|---|---|---|---|---|---|

## Permission Matrix

| Feature | Developer | Admin | Viewer |
|---|---|---|---|

## Database Matrix

| Table | Purpose | PK | Relations | Soft Delete | Important Constraints | Next.js Model |
|---|---|---|---|---|---|---|

## Calculation Matrix

| Calculation | Source | Formula | Inputs | Output | Next.js Function |
|---|---|---|---|---|---|

## Import Matrix

| Entity | Source Format | Columns | Validation | Duplicate Behavior | Next.js Target |
|---|---|---|---|---|---|

## Export Matrix

| Entity | Format | Columns | Formatting | Next.js Target |
|---|---|---|---|---|

---

# 47. Final acceptance criteria

The audit/migration document is complete only when:

- [ ] Every Laravel route was audited.
- [ ] Every page was audited.
- [ ] Every CRUD operation was audited.
- [ ] Every database table was audited.
- [ ] Relationships were documented.
- [ ] Authentication was documented.
- [ ] Authorization was documented.
- [ ] Validation was documented.
- [ ] Important calculations were documented.
- [ ] Search/filter/sort/pagination were documented.
- [ ] Imports were documented.
- [ ] Exports were documented.
- [ ] File/image handling was documented.
- [ ] Soft-delete behavior was documented.
- [ ] Hard-delete behavior was documented.
- [ ] Logs/auditing were documented.
- [ ] Frontend JavaScript behavior was documented.
- [ ] Tests were reviewed.
- [ ] Existing MySQL preservation strategy was documented.
- [ ] Laravel → Next.js mappings exist.
- [ ] Next.js App Router architecture is defined.
- [ ] TypeScript architecture is defined.
- [ ] ORM strategy is defined.
- [ ] Authentication migration is defined.
- [ ] Authorization migration is defined.
- [ ] CRUD migration is defined.
- [ ] Import/export migration is defined.
- [ ] File-storage migration is defined.
- [ ] Calculation migration is defined.
- [ ] Environment variables are mapped.
- [ ] Docker/Coolify deployment is defined.
- [ ] Testing/regression strategy is defined.
- [ ] Human-confirmation items are clearly listed.
- [ ] No destructive database operation is part of the default migration.

---

# 48. Final instruction to the AI coding agent

You are not translating PHP syntax.

You are reverse-engineering the existing LEAN ENTERPRISE application's behavior and producing a reliable migration blueprint.

Preserve:

- business logic
- CRUD
- database relationships
- validation
- calculations
- permissions
- search
- filters
- sorting
- pagination
- imports
- exports
- file handling
- image handling
- soft delete
- hard delete
- status handling
- logs
- reports
- UI behavior
- existing data.

Target:

```text
Laravel + PHP + Blade
        ↓
COMPLETE AUDIT
        ↓
Behavioral Migration Specification
        ↓
Next.js + TypeScript + React
        ↓
Existing MySQL
```

Do not invent missing behavior.

Do not silently resolve contradictions.

Do not reset the database.

Do not destroy existing data.

Keep the Laravel application available until the Next.js implementation passes regression testing.

**Audit first. Document second. Implement third.**
