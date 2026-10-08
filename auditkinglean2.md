# LEAN ENTERPRISE (LEMS) — Laravel → Next.js Full-Stack Audit & Migration Blueprint

| Document control | Value |
|---|---|
| Document | `auditkinglean2.md` |
| Project | kinglean2 — LEAN ENTERPRISE Management System (LEMS), garment/textile manufacturing |
| Workspace | `d:\xampp\htdocs\kinglean2` (XAMPP / Windows) |
| Governing instruction | `LEAN_ENTERPRISE_LARAVEL_TO_NEXTJS_FULLSTACK_AUDIT_MIGRATION_PROMPT.md` (48-section instruction set) |
| Revision | v2.0 — full audit + migration specification (supersedes v1 "14-section audit") |
| Status | Complete — implements the 40 required deliverables (see §2 of the prompt) and the 7 required audit tables (see §46 of the prompt) |
| Directive | **Audit first. Document second. Implement third.** |

## How to read this document

- **Source-of-truth priority** (per prompt §1): ① actual Laravel source code, ② actual MySQL schema/data, ③ existing tests, ④ runtime/UI behavior, ⑤ project documentation, ⑥ previous requirements. Lower-priority sources never override higher ones.
- Where a source does not define something, this document writes: **"Not found in source."**
- Where two sources disagree, this document writes a **CONFLICT** block (prompt §42) instead of silently choosing one.
- Business terminology, formulas, and existing oddities (typos, hardcoded values) are preserved **exactly as implemented**. Items that look wrong are flagged as *"Potential existing business-rule issue"* and kept in the migration spec pending human approval (prompt §13).
- **No destructive database operation is part of the default migration.** `migrate:fresh` / `db:wipe` are forbidden (prompt §5).

---

# 1. Executive Summary

**What this system is.** LEMS is a Lean Enterprise Management System for a garment/textile manufacturer (the Line Balancing workbook template names "PT. QUTY KARUNIA"). It manages organizational master data (Factories, Divisions, Departments, Sections, Production Lines), people (Operators/Employees with photos, education, PKWTT status, computed age/tenure), products (Articles with label codes), industrial-engineering standards (Processes with versioned GSD/MTM element breakdowns, Sewing Factors, Sewing Stop Factors), PTMS reports (machine specs + TMU/BMS/SMV values), Line Balancing reports (cycle-time studies, stopwatch capture, Yamazumi chart, Excel export), user credentials (3 roles), and system logs (login + activity). ~22 "Data Masters" feed the operational modules.

**What exists today.**

| Layer | Implementation |
|---|---|
| Backend | Laravel 10 (PHP ^8.1), MySQL, Sanctum API tokens, PhpSpreadsheet for Excel |
| Frontend A (primary, legacy) | Blade + Alpine.js + Tailwind (Vite build) — ~55 Blade views, component layout `app.blade.php` (~830 lines) |
| Frontend B (secondary) | `lems-frontend/` — Next.js 14.2.15 SPA (React 18, TS 5.6, react-query, zustand, Radix/shadcn-style UI) consuming the Laravel **API** via axios + Bearer tokens from localStorage |
| Database | MySQL — **38 tables / 58 migrations** — existing data is a project asset and must be preserved |
| Code scale | 34 models, 21 API controllers/requests/resources, 4 web controllers, ~270 web route URIs (≈90% route closures), 86 API endpoints, 26 seeders, 8 feature tests |

**Top findings (full list in §23).**

| # | Severity | Finding |
|---|---|---|
| 1 | CRITICAL | Business logic lives in **route closures** inside a ~2,436-line `routes/web.php` (helpers `logActivity()`/`applyExcelFormatting()` defined in the route file). No Service/Repository layer exists. |
| 2 | CRITICAL | **Hardcoded credentials** in `UserSeeder` (developer/#devadmin + 7 named accounts with trivial passwords) — security incident if shipped. |
| 3 | CRITICAL | **API `destroy()` performs hard deletes** and API controllers (except Users) have **no role checks** — `role:developer,admin` is enforced on web only. |
| 4 | CRITICAL | Effectively **no test coverage** for business calculations, imports/exports, authorization (8 feature tests total). |
| 5 | HIGH | `PtmsReport` `report_number` (`PTMS-YYYY-%04d`) is generated from a **row count → race condition / duplicate-key failures** under concurrency. |
| 6 | HIGH | Dual frontend (Blade + Next.js SPA) doubles maintenance; the SPA bypasses Blade authorization (server-side API lacks the same role rules). |
| 7 | HIGH | Export module hardcodes `×1.15` allowance and `8 h`/`28800 s` in formulas although `allowance_percent` / `working_hours_per_day` are configurable — UI and Excel disagree when settings ≠ defaults. |
| 8 | HIGH | No login rate limiting; `CredentialController` allows 4-char passwords; token in localStorage (XSS); `RoleMiddleware` error text is unprofessional ("aowkwk ngakak"). |
| 9 | MEDIUM | Schema typos frozen into the DB: `departments.desription`, `articles.label_number_quty`; hardcoded suffix `'17596'` in `Article` model; free-text `operators.gender`/`role`/`articles.destination` despite lookup tables existing. |
| 10 | MEDIUM | Fresh `php artisan migrate` **fails** (migration-order bug: FKs to `status_pkwtt`/`educational_levels` before those tables are created; two migrations call `DB::table()` without importing `DB`). Confirms the mandate to **never re-run migrations destructively**. |

**Migration verdict.** The system is feasible to migrate to **Next.js full-stack + TypeScript on the existing MySQL database** with no data loss. Recommended ORM: **Prisma** (introspect/baseline the existing schema — §26). The existing `lems-frontend/` Next.js app provides a UI head start but its data layer (axios → Laravel API) must be replaced by server-side ORM access (§24–29). Recommended strategy: **strangler-style staged migration (Phases 0–15, §36)** with Laravel kept runnable until regression parity is proven (prompt §45). No `migrate:fresh`, no schema re-creation, no silent behavior changes.

---

# 2. Existing Laravel Stack

| Concern | Actual implementation | Evidence |
|---|---|---|
| Language / runtime | PHP ^8.1 (XAMPP on Windows) | `composer.json` |
| Framework | Laravel 10 (`laravel/framework: ^10.0`) | `composer.json` |
| API auth | Laravel Sanctum ^3.2 (personal access tokens) | `composer.json`, `config/sanctum.php`, `routes/api.php` |
| Web auth scaffolding | Laravel Breeze (dev dependency) — Blade stack | `routes/auth.php`, `app/Http/Controllers/Auth/*` |
| Excel | `phpoffice/phpspreadsheet` 1.29 (imports, exports, LB workbook with charts) | `composer.json`, import/export closures, `LineBalancingController::export` |
| Frontend (Blade) | Blade components + Alpine.js ^3.4.2 + Tailwind CSS ^3.1.0 + Vite ^4.0.0 | `package.json`, `vite.config.js`, `resources/views/layouts/app.blade.php` |
| Frontend (SPA) | `lems-frontend/`: Next.js 14.2.15, React ^18.3.1, TypeScript ^5.6.3, @tanstack/react-query ^5.59, zustand ^5, react-hook-form + zod, Radix UI, recharts, axios | `lems-frontend/package.json` |
| Database | MySQL (default connection), InnoDB FK constraints incl. RESTRICT/CASCADE/SET NULL | `config/database.php`, 58 migrations |
| Cache / session | `file` driver (`.env.example`); session-based web auth | `config/session.php`, `.env.example` |
| Queue | `sync` — **no queued jobs exist** | `config/queue.php`, empty `app/Jobs` (absent) |
| Scheduler | `Console/Kernel::schedule()` is empty (commented `inspire` only) — **no cron tasks** | `app/Console/Kernel.php` |
| Mail | mailpit SMTP configured in `.env.example`; **no mail code found in source** ("Not found in source.") | `.env.example` |
| Localization | `SetLocale` middleware (en/id), session key `locale`, `POST /language/{locale}` | `app/Http/Middleware/SetLocale.php` |
| Logging | Laravel log + custom `activity_logs` / `login_logs` tables via global `logActivity()` helper | `routes/web.php` L1–85 |
| Tests | PHPUnit (`phpunit.xml`) — 8 feature tests (Breeze auth + `LeanEnterpriseMasterDataTest`), 1 unit test | `tests/` |
| Web server (dev) | `php artisan serve` via `kinglean php artisan serve host.bat` | workspace root |
| Environment | `.env.example` = stock Laravel 10 (MySQL 127.0.0.1:3306, db `laravel`, root/no password) | `.env.example` |

**Architecture summary.** Classic "Laravel monolith + Blade SPA-style pages". There is **no** `app/Services`, `app/Repositories`, `app/Jobs`, `app/Events`, `app/Listeners`, `app/Observers`, `app/Policies`, `app/Console/Commands`, or `app/Notifications` directory ("Not found in source."). Business logic is concentrated in: route closures (`routes/web.php`), 4 web controllers, model accessors/events, and inline Blade/Alpine JavaScript.

---

# 3. Project Structure

```text
kinglean2/
├── app/
│   ├── Console/Kernel.php              # empty schedule(); no Commands dir
│   ├── Exceptions/Handler.php
│   ├── Http/
│   │   ├── Kernel.php                  # NOTE: api group stateful (Sanctum) middleware commented out
│   │   ├── Controllers/
│   │   │   ├── Controller.php
│   │   │   ├── Auth/*                  # Breeze: login/register/password reset (9 controllers)
│   │   │   ├── CredentialController.php    # user/credential CRUD (web)
│   │   │   ├── LineBalancingController.php # only full feature controller (web)
│   │   │   ├── ProfileController.php       # Breeze profile
│   │   │   └── Api/                    # 21 files: BaseController, AuthController,
│   │   │                               #   DashboardController, UserController,
│   │   │                               #   14 entity CRUD controllers (apiResource),
│   │   │                               #   Requests/ (Store/UpdatePtmsReportRequest),
│   │   │                               #   Resources/ (PtmsReportResource …)
│   │   ├── Middleware/                 # 12: Authenticate, EncryptCookies,
│   │   │                               #   LogUserLogin (DEAD CODE — no-op),
│   │   │                               #   RoleMiddleware, SetLocale, …
│   │   └── Requests/                   # web Form Requests (Breeze profile only)
│   ├── Models/                         # 34 models (User, Operator, Article, PtmsReport,
│   │                                   #   LineBalancingReport(Row), 22 master models …)
│   ├── Traits/                         # shared traits (e.g. status helpers)
│   └── View/Components/AppLayout.php   # component-based layout ({{ $slot }})
├── bootstrap/app.php
├── config/                             # stock Laravel 10 config (app, auth, database, sanctum …)
├── database/
│   ├── factories/
│   ├── migrations/                     # 58 files → 38 tables (schema in §8)
│   └── seeders/                        # 26 seeders (UserSeeder ⚠ hardcoded credentials,
│                                       #   LineBalancingSeeder idempotent demo report)
├── documentation/                      # 01–10 docs, Work Plan, api/, database/, diagrams/, erd/, ui/
├── generate_lb_template.php            # standalone CLI: generates LB_Livlig_Template.xlsx (reference workbook)
├── lems-frontend/                      # Next.js 14 SPA (second frontend — see §24)
│   └── src/
│       ├── app/(auth)/login, (dashboard)/{dashboard, credentials, master-data/*,
│       │       operations, process-library/*, profile, ptms/[id]}
│       ├── components/{layout, providers, ui (~17 shadcn-style), data-table, …}
│       ├── hooks/                      # 16 react-query hooks (use-articles, use-operators …)
│       ├── lib/axios.ts                # Bearer token interceptor → Laravel API
│       ├── store/{auth-store, sidebar-store}.ts   # zustand (+persist)
│       └── types/index.ts
├── public/                             # storage symlink target for photos (public disk)
├── resources/
│   ├── css/app.css                     # Tailwind directives
│   ├── js/{app.js, bootstrap.js}       # Alpine boot
│   ├── lang/                           # en/id translations
│   └── views/                          # ~55 Blade files:
│       ├── layouts/{app, guest, navigation(unused)}
│       ├── components/                 # 13 stock Breeze components
│       ├── master-data/                # articles, departments, destinations, factories,
│       │                               #   gsd-elements, mechanics, operators, processes,
│       │                               #   production-lines, simple-master (shared ×13), partials/
│       ├── operations/{module, line-balancing/{index, edit}}
│       ├── system/{activity-logs, clear-cache, login-logs, speed-test}
│       ├── credentials/, hard-delete/, operators/show, profile/, auth/
│       └── {home, dashboard, developer, admin, viewer, welcome}
├── routes/{web.php (~2,436 lines), api.php (~90 lines), auth.php, console.php, channels.php}
├── storage/app/public/                 # operator + article photos
├── tests/{Feature/{Auth/*, ProfileTest, LeanEnterpriseMasterDataTest, ExampleTest}, Unit/}
├── composer.json / package.json / vite.config.js / phpunit.xml
└── kinglean php artisan serve host.bat # dev server launcher
```

**Custom global helpers (defined in `routes/web.php`, not `app/helpers.php`):**
- `logActivity(string $activity, string $module = null)` — writes `activity_logs` (username snapshot, user_id FK, module).
- `applyExcelFormatting($spreadsheet)` — shared PhpSpreadsheet header styling for exports.

**Explicitly absent ("Not found in source."):** Services, Repositories, Jobs, Events, Listeners, Observers, Policies, Gates (beyond `RoleMiddleware` + inline checks), Notifications, Mailables, custom Commands, scheduled tasks, queues, Horizon, broadcasting (Echo code commented out).

# 4. Route Inventory

**Route files:** `routes/web.php` (~2,436 lines), `routes/api.php` (~90 lines), `routes/auth.php` (Breeze), `routes/console.php` (empty of tasks). Total ≈ **270 web URIs + 86 API endpoints**. Every route is listed below (prompt §6: "Do not omit routes that look unimportant").

**Conventions used in the matrices:**
- Middleware shorthands: `auth` = web session auth (`Authenticate`), `role:X` = `RoleMiddleware` (`role:developer,admin` etc.), `auth:sanctum` = token auth, `guest`, `signed`, `throttled`, `verified` (Breeze).
- **Pattern rows** (13 generic masters × 9 routes, and API `apiResource` × 5 routes) fully enumerate every concrete URI; all entities following the pattern are listed with the pattern. This is complete documentation of every route, not an omission.
- Next.js targets follow the App Router structure defined in §25; mutations = Server Actions (`actions/*.ts`), file streams/search JSON = Route Handlers (`app/api/**/route.ts`).

## 4.1 Route Matrix — Public & language (web)

| Route | Method | Middleware | Controller | Behavior | Next.js Target |
|---|---|---|---|---|---|
| `/` | GET | — | closure | Redirect → `home` if authed, else `login` | `middleware.ts` redirect |
| `/language/{locale}` (`language.switch`) | POST | — | closure | Session `locale` = en/id (whitelist), back | Server Action `setLocale` + cookie |

## 4.2 Route Matrix — App shell & home (web)

| Route | Method | Middleware | Controller | Behavior | Next.js Target |
|---|---|---|---|---|---|
| `/home` (`home`) | GET | `auth` | closure | 6 COUNT stats (articles, operators, processes, process_versions, gsd_categories, gsd_elements) → `home` view | `app/(dashboard)/dashboard/page.tsx` (RSC + service) |
| `/dashboard` (`dashboard`) | GET | `auth` | closure | Redirect → `home` | redirect in `app/(dashboard)/layout.tsx` |
| `/developer` | GET | `auth`,`role:developer` | closure | Role landing page | `app/(dashboard)/roles/developer/page.tsx` |
| `/admin` | GET | `auth`,`role:developer,admin` | closure | Role landing page | `app/(dashboard)/roles/admin/page.tsx` |
| `/viewer` | GET | `auth`,`role:developer,admin,viewer` | closure | Role landing page | `app/(dashboard)/roles/viewer/page.tsx` |

## 4.3 Route Matrix — Credentials (user management)

| Route | Method | Middleware | Controller | Behavior | Next.js Target |
|---|---|---|---|---|---|
| `/credentials` | GET | `auth`,`role:developer,admin` | `CredentialController@index` | User list | `app/(dashboard)/credentials/page.tsx` |
| `/credentials` | POST | `auth`,`role:developer,admin` | `CredentialController@store` | Create user (password min **4**) | Server Action `createUser` |
| `/credentials/{user}/edit` | GET | `auth`,`role:developer,admin` | `CredentialController@edit` | Edit form (route-model binding) | `app/(dashboard)/credentials/[id]/page.tsx` |
| `/credentials/{user}` | PUT | `auth`,`role:developer,admin` | `CredentialController@update` | Update user/role/password | Server Action `updateUser` |
| `/credentials/{user}` | DELETE | `auth`,`role:developer,admin` | `CredentialController@destroy` | Delete user (self-delete prevention — see §12) | Server Action `deleteUser` |

## 4.4 Route Matrix — Hard Delete center (developer only)

| Route | Method | Middleware | Controller | Behavior | Next.js Target |
|---|---|---|---|---|---|
| `/hard-delete` | GET | `auth`,`role:developer` | closure | Page listing 22 masters with inactive counts | `app/(dashboard)/hard-delete/page.tsx` |
| `/hard-delete` | DELETE | `auth`,`role:developer` | closure | Body `master_key` → map of 22 models; deletes **only `status='inactive'`** rows; FK-protected rows raise QueryException 23000 → error message; logs `Hard Delete` | Server Action `hardDeleteMaster` (22-key map, `requireRole('developer')`) |

Master map (22): operators, processes, destinations, articles, gsd_elements, factories, departments, divisions, sections, production_lines, mechanics, failure_modes, spare_parts, machine_types, machine_numbers, skill_gradings, components_panels, shifts, genders, production_roles, educational_levels, status_pkwtt.

## 4.5 Route Matrix — System (developer only)

| Route | Method | Middleware | Controller | Behavior | Next.js Target |
|---|---|---|---|---|---|
| `/system/logs/login-logs` | GET | `auth`,`role:developer` | closure | Paginate 50 `login_logs` desc | `app/(dashboard)/system/login-logs/page.tsx` |
| `/system/logs/login-logs/clear` | DELETE | `auth`,`role:developer` | closure | Truncate-style `LoginLog::query()->delete()`, logs activity | Server Action `clearLoginLogs` |
| `/system/logs/activity-logs` | GET | `auth`,`role:developer` | closure | Paginate 50 `activity_logs` desc | `app/(dashboard)/system/activity-logs/page.tsx` |
| `/system/logs/activity-logs/clear` | DELETE | `auth`,`role:developer` | closure | Delete all activity logs, logs activity | Server Action `clearActivityLogs` |
| `/system/clear-cache` | GET | `auth`,`role:developer` | closure | Shows computed `storage/framework/cache/data` size | `app/(dashboard)/system/clear-cache/page.tsx` |
| `/system/clear-cache` | POST | `auth`,`role:developer` | closure | Artisan `cache:clear`/`config:clear`/`route:clear`/`view:clear` | Server Action (Next.js cache invalidation — §31/§40) |
| `/system/speed-test` | GET | `auth`,`role:developer` | closure | Renders speed test page | `app/(dashboard)/system/speed-test/page.tsx` |
| `/system/speed-test` | POST | `auth`,`role:developer` | closure | Times PDO connect + `processes` COUNT; returns db/query ms, DB name, driver, Laravel/PHP versions | Server Action (probe DB latency + app version) |

## 4.6 Route Matrix — Master data (group `role:developer,admin,viewer`, prefix `master-data`)

### Processes (custom closure — includes ProcessVersion + GSD pivot behavior)

| Route | Method | Middleware | Controller | Behavior | Next.js Target |
|---|---|---|---|---|---|
| `/master-data/processes` | GET | `auth`,`role:D,A,V` | closure | List + search (`process_name`,`description`), filter column/value, `show_inactive`, sort whitelist, eager versions | `app/(dashboard)/data-masters/processes/page.tsx` |
| `/master-data/processes` | POST | `auth`,`role:D,A,V` (viewer blocked 403 inline) | closure | Create process + version 1 + GSD pivot sync (per tests) | Server Action `createProcess` |
| `/master-data/processes/{process}` | PUT | same | closure | Update process + version/pivot side effects | Server Action `updateProcess` |
| `/master-data/processes/{process}/deactivate` | PATCH | viewer blocked | closure | Soft delete (`status='inactive'`) | Server Action `deactivateProcess` |
| `/master-data/processes/bulk-deactivate` | PATCH | viewer blocked | closure | Bulk `whereIn(id)->update(status=inactive)` | Server Action `bulkDeactivate` |
| `/master-data/processes/hard-delete` | DELETE | viewer blocked | closure | Delete only inactive; **skips rows with PtmsReport history** (per tests) | Server Action `hardDelete` |
| `/master-data/processes/import` | POST | viewer blocked | closure | XLSX/CSV upsert (§15) | Route Handler `api/imports/processes` |
| `/master-data/processes/export` | GET | `auth`,`role:D,A,V` | closure | XLSX download (§16) | Route Handler `api/exports/processes` |
| `/master-data/processes/search` | GET | `auth`,`role:D,A,V` | closure | JSON autocomplete `{id,label,description}`, active, LIKE, limit 10 | Route Handler `api/search/processes` |

### Operators / Employees (custom closure — photo upload)

| Route | Method | Middleware | Controller | Behavior | Next.js Target |
|---|---|---|---|---|---|
| `/master-data/operators` | GET | `auth`,`role:D,A,V` | closure | List + search (name/NIK/employee_number), filters incl. FK names, `show_inactive`, sort whitelist | `app/(dashboard)/master-data/operators/page.tsx` |
| `/master-data/operators` | POST | viewer blocked | closure | Create with **photo upload** (image validation, `public` disk), all org FKs, dates, gender/role free text, status_pkwtt, educational_level | Server Action `createOperator` |
| `/master-data/operators/{operator}` | PUT | viewer blocked | closure | Update; keeps old photo when no new upload (tests) | Server Action `updateOperator` |
| `/master-data/operators/{operator}/deactivate` | PATCH | viewer blocked | closure | Soft delete | Server Action |
| `/master-data/operators/bulk-deactivate` | PATCH | viewer blocked | closure | Bulk soft delete | Server Action |
| `/master-data/operators/hard-delete` | DELETE | viewer blocked | closure | Only inactive; **blocked when PTMS history exists** (tests) | Server Action |
| `/master-data/operators/import` | POST | viewer blocked | closure | XLSX/CSV upsert (§15) | Route Handler `api/imports/operators` |
| `/master-data/operators/export` | GET | `auth`,`role:D,A,V` | closure | XLSX (§16) | Route Handler `api/exports/operators` |
| `/master-data/operators/search` | GET | `auth`,`role:D,A,V` | closure | JSON autocomplete (name/NIK) — used by LB editor | Route Handler `api/search/operators` |

### Destinations, Articles, GSD Elements, Factories, Departments, Mechanics, Production Lines (custom closures)

Each custom entity has the same 8–9 route shape (`GET` list · `POST` store · `PUT {item}` update · `PATCH {item}/deactivate` · `PATCH bulk-deactivate` · `DELETE hard-delete` · `POST import` · `GET export` · `GET search` where present). Entity-specific behavior:

| Entity (prefix `/master-data/…`) | Routes present | Behavior notes (details §10/§11) | Next.js target dir |
|---|---|---|---|
| `destinations` | 9 (incl. search) | Unique-free name; hard-delete skips referenced-by-none (no FK — string `articles.destination`); soft delete | `app/(dashboard)/data-masters/destinations/` |
| `articles` | 9 (incl. search) | **Photo upload**; `label_number` UQ; `label_number_quty` auto `label_number.'17596'`; hard-delete skips rows with PTMS history + deletes photo file; import generates `LBL-`+`Str::random(10)` when missing, upsert on `article_name` | `app/(dashboard)/articles/` |
| `gsd-elements` | 9 (incl. search) | FK `gsd_category_id` (exists); `tmu`/`seconds` numeric ≥ 0; import **firstOrCreate** category; hard-delete has **no dependency check** | `app/(dashboard)/process-library/gsd/elements/` |
| `factories` | 9 (incl. search) | Hard-delete skips if `departments()` exist | `app/(dashboard)/data-masters/factories/` |
| `departments` | 9 (incl. search) | Search over `department_name`, **`desription`** (typo column), `factory.factory_name` (`orWhereHas`); hard-delete skips if `operators()` OR `ptmsReports()` | `app/(dashboard)/data-masters/departments/` |
| `mechanics` | 9 (incl. search) | `nik_karyawan` nullable + `unique:mechanics,nik_karyawan`; hard-delete skips if `ptmsReports()` | `app/(dashboard)/data-masters/mechanics/` |
| `production-lines` | 9 (incl. search) | Single deactivate **blocked if PTMS history**; bulk-deactivate does NOT check (inconsistency — §23); export includes **inactive** rows (inconsistency); FK `division_id` (NOT NULL) | `app/(dashboard)/master-data/lines/` |

### Generic "simple master" pattern (13 entities × 9 routes = 117 routes)

Defined once in a `$simpleMasters` loop (`routes/web.php` L~1780–1955). For **each** slug below the concrete routes exist (with `master-data.` names):

| # | Route (concrete URI) | Method | Behavior |
|---|---|---|---|
| 1 | `/master-data/{slug}` | GET | List; search name+description; filter name; `show_inactive`; sort whitelist `[name, description, status]`; shared view `master-data.simple-master` |
| 2 | `/master-data/{slug}` | POST | Create: name required string max 200, description nullable max 255, `status='active'`; viewer blocked |
| 3 | `/master-data/{slug}/{item}` | PUT | `findOrFail` (raw id); same validation; viewer blocked |
| 4 | `/master-data/{slug}/{item}/deactivate` | PATCH | Soft delete (`status='inactive'`); viewer blocked |
| 5 | `/master-data/{slug}/bulk-deactivate` | PATCH | Bulk soft delete; viewer blocked |
| 6 | `/master-data/{slug}/hard-delete` | DELETE | Delete only inactive rows — **no dependency checks** (generic); viewer blocked |
| 7 | `/master-data/{slug}/import` | POST | XLSX/CSV upsert keyed on name column (§15); viewer blocked |
| 8 | `/master-data/{slug}/export` | GET | XLSX 3 cols, active only, **no `logActivity`** (inconsistency); `master-data.{slug}.export` |
| 9 | `/master-data/{slug}/search` | GET | JSON autocomplete, active, LIKE name/description, limit 10 |

| Slug | Model | Name field | Import header keys |
|---|---|---|---|
| `skill-gradings` | SkillGrading | `skill_grade` | `skill grade`, `descriptions` |
| `divisions` | Division | `division` | `division`, `descriptions` |
| `sections` | Section | `section` | `section`, `descriptions` |
| `machine-types` | MachineType | `machine_type` | `machine type`, `descriptions` |
| `components-panels` | ComponentsPanel | `component_panel` | `component/panel`, `descriptions` |
| `machine-numbers` | MachineNumber | `machine_number` | `machine number`, `descriptions` |
| `shifts` | Shift | `shift` | `shift`, `descriptions` |
| `failure-modes` | FailureMode | `failure_mode` | `failure mode` / `kerusakan`, `descriptions` |
| `spare-parts` | SparePart | `spare_part` | `spare part`, `descriptions` |
| `genders` | Gender | `gender` | `gender`, `descriptions` |
| `production-roles` | ProductionRole | `production_role` | `production role`, `descriptions` |
| `educational-levels` | EducationalLevel | `level` | `level`, `descriptions` |
| `status-pkwtt` | StatusPkwtt | `pkwtt` | `pkwtt`, `descriptions` |

Next.js target: `app/(dashboard)/data-masters/{slug}/page.tsx` + one shared generic service/action factory (`lib/crud/simple-master.ts`) mirroring the Laravel loop — **no duplicate masters** (prompt §9).

## 4.7 Route Matrix — Employee detail & operations

| Route | Method | Middleware | Controller | Behavior | Next.js Target |
|---|---|---|---|---|---|
| `/operators/{operator}` | GET | `auth` | closure | Operator profile: eager-loads `ptmsReports.{article, processVersion.process, processVersion.gsdElements.gsdCategory, factory, department, productionLine}`; computed age/working-age/years-of-service (§13) | `app/(dashboard)/employees/[id]/page.tsx` |
| `/operators/{operator}/update-details` | PUT | `auth` (viewer → 403 inline "Viewer role is read-only.") | closure | Update `start_date`, `date_of_birth` only; logs | Server Action `updateOperatorDetails` |
| `/operations/employee-profile` | GET | `auth` | closure | Redirect to first active operator by name, else back with error | redirect server-side |
| `/operations/` (`operations.index`) | GET | `auth`,`role:D,A,V` | closure | Operations hub view (`module='overview'`) | `app/(dashboard)/operations/page.tsx` |
| `/operations/cycle-time` | GET | `auth`,`role:D,A,V` | closure | Placeholder module view | `app/(dashboard)/operations/cycle-time/page.tsx` |
| `/operations/breakdown` | GET | same | closure | Placeholder ("Operational Breakdown") | `…/operations/breakdown/page.tsx` |
| `/operations/kaizen` | GET | same | closure | Placeholder | `…/operations/kaizen/page.tsx` |
| `/operations/skills` | GET | same | closure | Placeholder ("Skill Grading" module) | `…/operations/skills/page.tsx` |
| `/operations/tpm` | GET | same | closure | Placeholder (TPM) | `…/operations/tpm/page.tsx` |
| `/operations/materials` | GET | same | closure | Placeholder | `…/operations/materials/page.tsx` |
| `/operations/vsm` | GET | same | closure | Placeholder (Value Stream Mapping) | `…/operations/vsm/page.tsx` |

Note: `operations.breakdown` and `operations.line.balancing.index` are sidebar links; module views are mostly shells ("Not found in source." for deeper logic — §5).

## 4.8 Route Matrix — Line Balancing (`/operations/line-balancing`, `LineBalancingController`)

| Route | Method | Middleware | Controller | Behavior | Next.js Target |
|---|---|---|---|---|---|
| `/operations/line-balancing` | GET | `auth`,`role:D,A,V` | `index` | Paginated 15/page; search + filters (factory, article, report_name, created_by, status) via `whereHas`; joined sorts; `show_inactive` | `app/(dashboard)/line-balancing/page.tsx` |
| `/operations/line-balancing` | POST | same (⚠ viewer can write — §23) | `store` | Validate factory/article/line exists, report_name ≤150, hours 0–24, allowance 0–100, update_date; `target_output_per_hour=0` initially → redirect to editor | Server Action `createLbReport` |
| `/operations/line-balancing/{id}` | GET | same | `edit` | Report + rows (machineType, employee) + active MachineTypes/Operators pickers; editor UI (§20) | `app/(dashboard)/line-balancing/[id]/page.tsx` |
| `/operations/line-balancing/{id}` | PUT | same | `update` | Header: `target_output_per_hour` (req int ≥0), `output_actual`, hours, allowance, update_date (null → keep existing) | Server Action `updateLbHeader` |
| `/operations/line-balancing/{id}/rows` | POST | same | `saveRows` | **Transactional full replace** of rows (delete + reinsert); per-row validation (§12); `name` overwritten from `employee_id`; also writes hidden `target_output_per_hour`/`output_actual` (⚠ stale-field risk §23) | Server Action `saveLbRows` |
| `/operations/line-balancing/{id}/deactivate` | PATCH | same | `deactivate` | Soft delete single (⚠ unused by views) | Server Action |
| `/operations/line-balancing/bulk-deactivate` | PATCH | same | `bulkDeactivate` | `ids[]` exists → bulk `status='inactive'` | Server Action `bulkDeactivateLb` |
| `/operations/line-balancing/{id}/export` | GET | same | `export` | PhpSpreadsheet XLSX reproduction of "LB Livlig" workbook incl. formulas + Yamazumi chart (§16) | Route Handler `api/exports/line-balancing/[id]` |
| `/operations/line-balancing/lines-by-factory/{factoryId}` | GET | same | `getLinesByFactory` | JSON active production lines (⚠ **ignores `$factoryId`** — ProductionLine is linked to Division; dead endpoint) | Route Handler `api/production-lines` (rework) |

## 4.9 Route Matrix — Profile (Breeze)

| Route | Method | Middleware | Controller | Behavior | Next.js Target |
|---|---|---|---|---|---|
| `/profile` | GET | `auth` | `ProfileController@edit` | Profile page | `app/(dashboard)/profile/page.tsx` |
| `/profile` | PATCH | `auth` | `ProfileController@update` | Update name/email (Breeze rules) | Server Action `updateProfile` |
| `/profile` | DELETE | `auth` | `ProfileController@destroy` | Delete account (Breeze password confirm) | Server Action `deleteAccount` |

## 4.10 Route Matrix — Web auth (Breeze, `routes/auth.php`)

| Route | Method | Middleware | Controller | Behavior | Next.js Target |
|---|---|---|---|---|---|
| `/register` | GET/POST | `guest` | `RegisteredUserController` | Registration (present; see §6 note) | Auth.js register flow (decision §39) |
| `/login` | GET/POST | `guest` | `AuthenticatedSessionController` | Session login (username **or** email per Breeze + customizations — §6) | `app/(auth)/login/page.tsx` + Auth.js |
| `/forgot-password` | GET/POST | `guest` | `PasswordResetLinkController` | Request reset link | Auth.js/forgot page |
| `/reset-password/{token}` | GET/POST | `guest` | `NewPasswordController` | Reset form | Auth.js reset page |
| `/verify-email` | GET | `auth`,`throttled` | `EmailVerificationPromptController` | Prompt | verify page |
| `/verify-email/{id}/{hash}` | GET | `auth`,`signed`,`throttled` | `VerificationController` | Verify | route handler |
| `/email/verification-notification` | POST | `auth`,`throttled` | `VerificationEmailController` | Resend | server action |
| `/confirm-password` | GET/POST | `auth`,`throttled` | `ConfirmablePasswordController` | Confirm (Breeze) | confirm page |
| `/password.update` | PUT/POST | `auth` | `PasswordController` | Update password | Server Action `updatePassword` |
| `/logout` | POST | `auth` | `AuthenticatedSessionController@destroy` | Session destroy + login_log 'Logout' | Auth.js signOut |

## 4.11 Route Matrix — API (`routes/api.php`, prefix `/api`)

| Route | Method | Middleware | Controller | Behavior | Next.js Target |
|---|---|---|---|---|---|
| `/api/login` | POST | — (public) | `AuthController@login` | Validate `username`+`password` (`Hash::check`), `createToken`, returns token + user | **Retire** — SPA moves to cookie session (§27); keep only if dual-run needed |
| `/api/logout` | POST | `auth:sanctum` | `AuthController@logout` | Revoke current token | signOut |
| `/api/me` | GET | `auth:sanctum` | `AuthController@me` | Current user | session profile |
| `/api/me/password` | PUT | `auth:sanctum` | `AuthController@changePassword` | Change password | Server Action `updatePassword` |
| `/api/dashboard/stats` | GET | `auth:sanctum` | `DashboardController@stats` | Aggregates: counts + `average_smv = PtmsReport::avg('smv')` etc. (16+ queries) | Dashboard service (cached) |
| `/api/ptms-reports/{ptms_report}/status` | PUT | `auth:sanctum` | `PtmsReportController@updateStatus` | Status → `draft/final/archived` | Server Action `updatePtmsStatus` |

**apiResource expansion (5 routes each: `GET` index · `POST` store · `GET` {id} show · `PUT/PATCH` {id} update · `DELETE` {id} destroy).** ⚠ `destroy()` = **hard delete** for all of these; ⚠ **no role checks** except `users`; `sort`/`with` params accept arbitrary values (§23). Next.js target: RSC list pages + Server Actions + `app/api/**` only where the SPA-equivalent needs JSON.

| apiResource (prefix `/api`) | Middleware | Notes | Next.js Target |
|---|---|---|---|
| `factories` | `auth:sanctum` | uniform CRUD, search/sort/with | `data-masters/factories` + `actions/factories.ts` |
| `departments` | `auth:sanctum` | uniform | `data-masters/departments` |
| `production-lines` | `auth:sanctum` | uniform | `master-data/lines` |
| `articles` | `auth:sanctum` | uniform | `articles` |
| `operators` | `auth:sanctum` | uniform | `employees` |
| `gsd-categories` | `auth:sanctum` | uniform | `process-library/gsd/categories` |
| `gsd-elements` | `auth:sanctum` | uniform | `process-library/gsd/elements` |
| `mtm-elements` | `auth:sanctum` | uniform | `process-library/mtm` |
| `sewing-factors` | `auth:sanctum` | uniform | `process-library/sewing-factors` |
| `sewing-stop-factors` | `auth:sanctum` | uniform | `process-library/stop-factors` |
| `processes` | `auth:sanctum` | uniform | `process-library/processes` |
| `process-versions` | `auth:sanctum` | uniform | `process-library/processes/[id]` |
| `ptms-reports` | `auth:sanctum` | create stores TMU/BMS/SMV values as given (§13 conflict) | `ptms` |
| `users` | `auth:sanctum`,`role:developer,admin` | the **only** API resource with role gating; self-delete prevention | `credentials` |

**Route-model binding:** web routes use implicit binding (`Process $process`, `Article $article`, …) except generic masters/mechanics (raw `{item}` + `findOrFail`). API uses `{ptms_report}` and default resource params.

# 5. Page Inventory

All Blade pages verified against `resources/views/` (prompt §7: pages from old requirements that do not exist are **not** listed). Layout: every authenticated page uses `layouts/app.blade.php` (component layout `{{ $slot }}`, collapsible dark sidebar, role-gated nav, EN/ID switcher, user dropdown); auth pages use `layouts/guest.blade.php`. `layouts/navigation.blade.php` is unused stock Breeze.

| URL | View | Sidebar location | Access | Core UI (tables/forms/modals) | Search · Filter · Sort · Paginate | Import/Export · Delete · Status | Notes |
|---|---|---|---|---|---|---|---|
| `/login` | `auth/login` | — (guest) | guest | Login form (username **or** email + password + remember) | — | — | throttled (5 attempts) |
| `/register`, `/forgot-password`, `/reset-password/{token}`, `/verify-email`, `/confirm-password` | `auth/*` | — | guest/auth | Stock Breeze forms | — | — | registration present |
| `/home` | `home` | Main | auth | 6 stat cards (articles, operators, processes, process versions, GSD categories, GSD elements) | — | — | counts server-side |
| `/developer`, `/admin`, `/viewer` | `developer`/`admin`/`viewer` | — (not in sidebar) | role-gated | Role landing/info pages | — | — | mostly static |
| `/credentials` (+`/{user}/edit`) | `credentials/index` | Management (dev,admin) | `role:developer,admin` | Users table (name, username, role, description), create/edit **modal**, delete button | — · — · — | Delete = hard (`delete()`), self-delete blocked | password min 4 |
| `/hard-delete` | `hard-delete/index` | Management | `role:developer` | 22 master cards with inactive counts + delete forms | — | Hard-delete inactive only, FK errors surfaced | confirm via form POST |
| `/system/logs/login-logs` | `system/login-logs` | System (dev) | `role:developer` | login_logs table (user, username, activity, time) | — | Clear-all (DELETE) | paginate 50 |
| `/system/logs/activity-logs` | `system/activity-logs` | System (dev) | `role:developer` | activity_logs table (user, activity, module, time) | — | Clear-all | paginate 50 |
| `/system/clear-cache` | `system/clear-cache` | Management (dev) | `role:developer` | Cache size display + clear button | — | POST clears app caches | — |
| `/system/speed-test` | `system/speed-test` | Management (dev) | `role:developer` | Run test button → results (db/query ms, versions) | — | — | — |
| `/master-data/processes` | `master-data/processes` | Data Masters | D,A,V | Process table (name, description, versions, status), create/edit modal incl. **version + GSD element multi-select** | search ✓ · column filter ✓ · sortable ✓ · `show_inactive` | Import/Export buttons, deactivate, bulk-deactivate, hard-delete (dev) | `updateProcessFilterValues` JSON embedded (tests) |
| `/master-data/operators` | `master-data/operators` | Data Masters | D,A,V | Operator table (name, NIK, gender, role, org columns, dates, status), create/edit modal with **photo upload** + dependent dropdowns (factory→dept→line) | search ✓ · filters incl. FK names + date range (`dateFrom/dateTo`) · sortable ✓ | Import/Export; deactivate; bulk; hard-delete (dev-only, history-guarded) | lookup data: genders, productionRoles, statusPkwtt, educationalLevels |
| `/operators/{id}` | `operators/show` | (linked) | auth | Employee profile: photo, personal/org data, computed age fields (§13), PTMS history table w/ GSD details | — | update-details (dates) | eager loads PTMS graph |
| `/master-data/articles` | `master-data/articles` | Data Masters | D,A,V | Articles table (name, label number, quty code, destination, photo thumb, status), modal + photo | search ✓ · filter ✓ · sortable ✓ | Import (label auto-gen) / Export; deactivate; bulk; hard-delete (history-guarded) | quty uniqueness enforced |
| `/master-data/gsd-elements` | `master-data/gsd-elements` | Data Masters | D,A,V | GSD elements table (name, code, TMU, seconds, motion, category, status), modal | search ✓ · filter (name/code/motion) ✓ · sort whitelist | Import/Export; deactivate; bulk; hard-delete (no dep check) | category select (active) |
| `/master-data/factories` | `master-data/factories` | Data Masters | D,A,V | Factories table + modal | ✓ ✓ ✓ | Import/Export; deactivate; bulk; hard-delete (dept-guarded) | — |
| `/master-data/departments` | `master-data/departments` | Data Masters | D,A,V | Departments table (name, factory, `desription`, status), modal with factory select | ✓ (incl. factory name) ✓ ✓ | Import/Export; deactivate; bulk; hard-delete (guarded) | typo column displayed |
| `/master-data/destinations` | `master-data/destinations` | Data Masters | D,A,V | Destinations table + modal | ✓ ✓ ✓ | Import/Export; deactivate; bulk; hard-delete | — |
| `/master-data/production-lines` | `master-data/production-lines` | Data Masters | D,A,V | Lines table (name, division, description, status), modal | ✓ ✓ ✓ | Import/Export (export includes inactive!); deactivate (PTMS-guarded single only); bulk; hard-delete | division select |
| `/master-data/mechanics` | `master-data/mechanics` | Data Masters | D,A,V | Mechanics table (NIK, name, description, status), modal | ✓ ✓ ✓ | Import/Export; deactivate; bulk; hard-delete (PTMS-guarded) | NIK unique |
| `/master-data/{13 simple masters}` | `master-data/simple-master` (shared) | Data Masters | D,A,V | Generic table (name, description, status) + modal | ✓ ✓ ✓ | Import/Export; deactivate; bulk; hard-delete | one shared Blade for 13 entities (§4.6) |
| `/operations/` | `operations/module` | Lean Operations | D,A,V | Module hub | — | — | `module='overview'` |
| `/operations/{cycle-time,breakdown,kaizen,skills,tpm,materials,vsm}` | `operations/module` | Lean Operations | D,A,V | Placeholder module views | — | — | no deeper logic found ("Not found in source.") |
| `/operations/line-balancing` | `operations/line-balancing/index` | Lean Operations | D,A,V | Reports table (name, factory, article, line, target, status), **create modal**, bulk-delete mode (checkbox column + confirm()) | search (Enter) · filter column/value selects · joined sorts · `show_inactive` · paginate 15 | Export per row; deactivate; bulk-deactivate | Chart.js loaded in edit; see §14 dedicated audit below |
| `/operations/line-balancing/{id}` | `operations/line-balancing/edit` | (linked) | D,A,V | **Editor**: header settings form (target PPH, output actual, hours, allowance, date), dynamic rows (machine select, process, employee autocomplete, operator count, CT1–CT5), **Stopwatch** (Start/Stop/Lap/Reset, 5 laps), live recalc table + 11 KPI cards + **grouped bar chart** (Avg CT vs Avg CT+Allow, red over takt, dashed takt line) | — | Save rows (transactional replace); Export xlsx | inline JS ~all behavior (§20) |
| `/profile` (+partials) | `profile/edit` | Settings | auth | Breeze profile: update name/email, update password, delete account | — | Delete account | — |
| `/welcome` | `welcome` | — | public | Landing | — | — | stock |

**lems-frontend SPA pages (parallel frontend, verified):** `(auth)/login`; `(dashboard)/{dashboard, credentials, master-data/{articles,departments,factories,lines,operators}, operations, profile, ptms, ptms/[id]}`; `process-library/{gsd, gsd/elements, mtm, processes, sewing-factors, stop-factors}`. **SPA gaps vs Blade:** destinations, mechanics, 13 simple masters, line-balancing editor, system logs, clear-cache, speed-test, hard-delete, operator profile — must be built in Next.js (§36).

---

# 6. Authentication

| Aspect | Actual behavior (source: `app/Http/Requests/Auth/LoginRequest.php`, `AuthenticatedSessionController`, `Api/AuthController`) |
|---|---|
| Web login route | `GET/POST /login` (Breeze, guest middleware) |
| Login identifier | **Username OR email** — rules: `username: nullable, required_without:email` · `email: nullable, email, required_without:username` + `password: required` |
| Case handling | Identifier is **lowercased** before `Auth::attempt` (`strtolower`) — works with MySQL `*_ci` collation; document as preserved behavior |
| Password hashing | `Hash::make()` = **bcrypt** (Laravel default); verified via `Hash::check` / `Auth::attempt` |
| Session | Laravel session cookie (encrypted, `file` driver); `remember` token supported (`remember_token` column) |
| Logout | `POST /logout` → session invalidate + regenerate; writes `login_logs` row (`activity='Logout'`) |
| Login logging | `AuthenticatedSessionController` writes `LoginLog::create` on login and logout (user_id, username, activity enum `Login`/`Logout`). ⚠ `LogUserLogin` middleware exists but is **dead code** (never performs logging) |
| Failed login | Generic `auth.failed` error; per-field message naming the used identifier; no account lockout |
| Rate limiting | **Web:** custom `RateLimiter` check — max **5 attempts** per throttle key (`throttleKey()`), then `auth.throttle` (Breeze-style). **API: Not found in source.** — `Api/AuthController@login` has **no throttling** (brute-force risk, §23) |
| Password reset | Breeze `forgot-password` + `reset-password/{token}` (mailpit in dev config) |
| Registration | `POST /register` present (Breeze `RegisteredUserController`) — role assignment on register is default-role behavior ("Not found in source." for explicit role assignment beyond seed defaults) |
| Email verification | `MustVerifyEmail` implemented on `User` but **not enforced** (routes exist, no `verified` middleware on app routes) |
| API auth | `POST /api/login` (public): validates `username`+`password`, `Hash::check`, `createToken()` (Sanctum bearer). Token consumed via `auth:sanctum`; SPA stores it in **localStorage** (XSS risk) |
| Account status | No `active` flag on `users` — account deactivation is achieved by **deleting** the user (credentials page) or not. ⚠ No login check against `status`; user status concept is "Not found in source." |
| Self-protection | `CredentialController@destroy` refuses self-delete ("You cannot delete your own account.") |
| Password policy | Web credential CRUD: `min:4` (⚠ weak); Breeze profile change: stock rules (min 8 in Breeze default — `PasswordController`); API `changePassword`: rules per `AuthController` |
| Seed credentials | ⚠ **CRITICAL:** `UserSeeder` hardcodes 8 accounts (`developer/#devadmin`, `yuliyana/yuliyana1234`, `kanitha/kanigtha1234`…, `normal/1234`) — must be replaced by secure seeding (§29) and rotated in any shared environment |

---

# 7. Authorization

**Mechanisms actually in use:**
1. `RoleMiddleware` (`role:developer,admin`) — route-level role gate reading `auth()->user()->role->role_name` (message contains unprofessional text — §23).
2. **Inline closure checks** — but **only inside the operators routes** (`store/update/deactivate/bulk-deactivate/import` block `viewer` with 403; `hard-delete` requires `developer`) and `operators/update-details`.
3. Blade-side visibility (`@if(auth()->user()->role->role_name ...)`) — hides sidebar links/buttons (**not** authorization).
4. **No** Policies, Gates, or `authorize()` calls anywhere ("Not found in source.").

**⚠ Backend authorization gaps (verified against `routes/web.php`):**
- All master-data write routes **except operators** (processes, articles, destinations, gsd-elements, factories, departments, mechanics, production-lines, 13 generic masters — create/update/deactivate/bulk/hard-delete/**import**) contain **no role check** below the group middleware `role:developer,admin,viewer`. A **viewer account can mutate data by direct request** even though the UI hides buttons (prompt §11: "Hiding a button is not authorization").
- **Line Balancing** has no per-action checks — viewers can create/edit/save rows/deactivate/export.
- **API**: only `users` resource has `role:developer,admin`; all other API resources are writable **and hard-deletable** by any authenticated token; no viewer restriction at all.
- Per-entity **operator** hard-delete requires `developer`; per-entity hard-delete for other entities has no explicit role check (the centralized `/hard-delete` center is `role:developer`).

## Permission Matrix (Feature × Role) — as implemented today

| Feature | Developer | Admin | Viewer | Notes / evidence |
|---|---|---|---|---|
| View all pages (masters, operations, profile) | ✓ | ✓ | ✓ | group middleware `role:developer,admin,viewer` |
| Master data — create/edit/deactivate/bulk/import (non-operator) | ✓ | ✓ | **⚠ backend ✓ (UI hidden)** | no inline guard (verified) |
| Master data — operator create/edit/deactivate/bulk/import | ✓ | ✓ | ✗ (403) | inline guard L633–775 |
| Master data — export (all) | ✓ | ✓ | ✓ | read-only route |
| Per-entity hard-delete (operators) | ✓ | ✗ (403) | ✗ (403) | "Only developers can perform hard deletes." L743 |
| Per-entity hard-delete (other entities) | ✓ | **⚠ ✓** | **⚠ ✓ (backend)** | no role check beyond group |
| Hard-Delete center (`/hard-delete`) | ✓ | ✗ | ✗ | `role:developer` |
| Credentials (user CRUD) | ✓ | ✓ | ✗ | `role:developer,admin` |
| System logs / clear-cache / speed-test | ✓ | ✗ | ✗ | `role:developer` |
| Operator profile dates (`update-details`) | ✓ | ✓ | ✗ (403) | inline guard L2381 |
| Line Balancing (all actions incl. export) | ✓ | ✓ | **⚠ ✓ (write!)** | no per-action checks |
| Operations placeholder modules | ✓ | ✓ | ✓ | read-only views |
| Profile (self) | ✓ | ✓ | ✓ | Breeze |
| API — all resources except users (CRUD + **hard delete**) | ✓ | ✓ | **⚠ ✓** | `auth:sanctum` only |
| API — users resource | ✓ | ✓ | ✗ | `role:developer,admin` |
| Role pages `/developer`,`/admin`,`/viewer` | dev only | `/admin` | `/viewer` | route middleware |

**Migration rule (§28):** the Next.js implementation must close the ⚠ gaps **or** faithfully reproduce them — default recommendation: enforce the *intended* policy (viewer = read-only everywhere; hard delete = developer-only) and list this as a **Human Confirmation item (§39)** because it changes observable behavior for viewers.

# 8. Database Schema

**Verified from all 58 migrations** (`database/migrations/`) → **38 tables**. MySQL/InnoDB. Notation: NN = NOT NULL, UQ = unique, FK = foreign key. Soft delete is implemented as `status ENUM('active','inactive')` — **no `deleted_at` columns anywhere** ("Not found in source.").

## 8.1 Core / auth tables

**`users`** (create + 3 alters): `id` BIGINT AI PK · `role_id` BIGINT NN **FK→roles.id (UPD CASCADE, DEL RESTRICT)** · `employee_number` VARCHAR(20) UQ NULL · `name` VARCHAR(255) NN · `username` VARCHAR(255) UQ (**made NULL-able via raw `ALTER TABLE … MODIFY`** — MySQL-specific) · `description` TEXT NULL · `email` VARCHAR(255) UQ NULL · `email_verified_at` TIMESTAMP NULL · `password` VARCHAR(255) NN · `remember_token` · timestamps.

**`roles`**: `id` PK · `role_name` VARCHAR(50) UQ NN · `description` VARCHAR(255) · timestamps. (3 rows expected: developer, admin, viewer.)

**`password_reset_tokens`**: `email` PK · `token` · `created_at`. **`failed_jobs`**: standard (uuid UQ). **`personal_access_tokens`** (Sanctum): morphs `tokenable`, `name`, `token` VARCHAR(64) UQ, `abilities` TEXT, `last_used_at`, `expires_at`, timestamps (composite morphs index).

## 8.2 Organization tables

| Table | Columns (beyond id/timestamps) | Constraints |
|---|---|---|
| `factories` | `factory_name` VARCHAR(100) NN · `description` VARCHAR(255) · `status` ENUM(active,inactive) NN default active | — |
| `departments` | `factory_id` FK→factories (CASCADE upd / RESTRICT del) NN · `department_name` VARCHAR(100) NN · **`desription`** VARCHAR(255) ⚠typo column · `status` ENUM | FK |
| `divisions` | `division` VARCHAR(100) NN · `description` · `status` ENUM | — |
| `sections` | `section` VARCHAR(100) NN · `description` · `status` ENUM | — |
| `production_lines` | `division_id` FK→divisions NN (CASCADE/RESTRICT) · `line_name` VARCHAR(100) NN · `description` · `status` ENUM | ⚠ `department_id` **dropped** by `2026_09_18_000001` after data migration dept→division map |
| `destinations` | `destination` VARCHAR(100) NN · `description` · `status` ENUM | (no FK consumers — articles.destination is free text) |

## 8.3 Product / people tables

**`articles`**: `article_name` VARCHAR(150) NN · `label_number` VARCHAR(100) NN **UQ** · **`label_number_quty`** VARCHAR(100) UQ NULL (⚠ generated = `label_number` . `'17596'`, model event) · `destination` VARCHAR(100) NN (free string ⚠) · `description` · `photo_path` VARCHAR(255) NULL · `status` ENUM · timestamps.

**`operators`** (create + 5 alters): `employee_number` VARCHAR(20) NN **UQ** · `nik_karyawan` VARCHAR(50) UQ NULL · `operator_name` VARCHAR(100) NN · `gender` VARCHAR(30) NULL (free string ⚠ despite `genders` table) · `role` VARCHAR(100) NULL (free string ⚠ despite `production_roles`) · `photo_path` NULL · `factory_id`/`department_id`/`line_id`/`division_id`/`section_id` FKs **ON DELETE SET NULL** (all NULL-able) · `start_date` DATE NULL · `date_of_birth` DATE NULL · `status` ENUM NN · `status_pkwtt_id` FK→status_pkwtt SET NULL · `educational_level_id` FK→educational_levels SET NULL · timestamps.

**`login_logs`**: `user_id` FK→users **DEL CASCADE** · `username` VARCHAR(100) NN · `activity` ENUM('Login','Logout') NN · timestamps.
**`activity_logs`**: `user_id` FK→users **DEL SET NULL** NULL · `username` VARCHAR(100) NN · `activity` VARCHAR(500) NN · `module` VARCHAR(100) NULL · timestamps.

## 8.4 GSD / MTM / sewing standards

| Table | Key columns | Constraints |
|---|---|---|
| `gsd_categories` | `category_name` VARCHAR(150) NN · `description` · `status` ENUM | category_name UQ |
| `gsd_elements` | `gsd_category_id` FK (CASCADE/RESTRICT) NN · `element_name` VARCHAR(200) NN · `description` · `code` VARCHAR(50) NN · `tmu` DECIMAL(10,2) NN · `seconds` DECIMAL(10,2) NN · `motion_sequence` VARCHAR(100) · `status` ENUM | FK |
| `mtm_elements` | `element_name` VARCHAR(200) · `description` · `code` VARCHAR(50) · `tmu` DECIMAL(10,2) · `seconds` DECIMAL(10,2) · `status` ENUM | (API-only master) |
| `sewing_factors` | `factor_name` VARCHAR(100) (nil/low/medium/high per migration comment) · `description` · `factor_value` DECIMAL(10,2) NN · `code` VARCHAR(50) · `status` ENUM | (API-only master) |
| `sewing_stop_factors` | `factor_name` VARCHAR(150) · `description` · `tolerance` VARCHAR(100) · `factor_value` DECIMAL(10,2) · `code` · `status` ENUM | (API-only master) |

## 8.5 Process / PTMS tables

**`processes`**: `process_name` VARCHAR(200) NN **UQ** · `description` · `status` ENUM.

**`process_versions`** (create + GSD alter): `process_id` FK→processes (CASCADE/RESTRICT) NN · `version_number` UNSIGNED INT NN · **UQ (process_id, version_number)** · `notes` VARCHAR(255) · `status` ENUM(**draft, active, archived**) default draft · `created_by` FK→users NN (RESTRICT del) · `gsd_category_id` FK→gsd_categories **SET NULL** · `gsd_element_id` FK→gsd_elements **SET NULL** (legacy single link) · timestamps.

**`process_version_gsd_elements`** (pivot): `id` PK · `process_version_id` FK→process_versions **CASCADE del** NN · `gsd_element_id` FK→gsd_elements (RESTRICT del) NN · **UQ (process_version_id, gsd_element_id)** · timestamps. Migration backfilled from legacy `gsd_element_id`.

**`ptms_reports`** — central fact table: `report_number` VARCHAR(50) NN **UQ** (⚠ generated `PTMS-YYYY-%04d` from yearly count — race) · 7 FKs NN with **RESTRICT delete / CASCADE update**: `article_id`, `process_version_id`, `operator_id`, `factory_id`, `department_id`, `line_id`, `created_by` · machine specs: `machine_name` VARCHAR(150) (free text ⚠), `feed_type` VARCHAR(100), `rpm` DECIMAL(10,2), `stitch_per_cm`, `seam_width` · percentages: `machine_delay_percent`, `contingency_percent`, `ra_percent` DECIMAL(10,2) NN default 0 · IE values: `machining_tmu`, `handling_tmu`, `bundle_tmu`, `total_tmu`, `bms`, `smv` DECIMAL(10,2) NN default 0 (⚠ **stored as provided** — see CONFLICT C-2 §23) · `status` ENUM(**draft, final, archived**) default draft · timestamps.

## 8.6 Master lookup tables (identical pattern)

Pattern: `id` PK AI · `<value>` VARCHAR NN · `description` VARCHAR(255) NULL · `status` ENUM(active,inactive) default active · timestamps.

| Table | Value column | Extra |
|---|---|---|
| `skill_gradings` | `skill_grade` VARCHAR(100) | — |
| `machine_types` | `machine_type` VARCHAR(100) | — |
| `machine_numbers` | `machine_number` VARCHAR(50) | — |
| `components_panels` | `component_panel` VARCHAR(200) | — |
| `shifts` | `shift` VARCHAR(100) | — |
| `failure_modes` | `failure_mode` VARCHAR(200) | — |
| `mechanics` | `mechanic` VARCHAR(100) | + `nik_karyawan` VARCHAR(50) UQ NULL |
| `spare_parts` | `spare_part` VARCHAR(200) | — |
| `genders` | `gender` VARCHAR(50) **UQ** | — |
| `production_roles` | `production_role` VARCHAR(100) **UQ** | — |
| `educational_levels` | `level` VARCHAR(100) **UQ** | — |
| `status_pkwtt` | `pkwtt` VARCHAR(100) **UQ** | — |

## 8.7 Line Balancing tables

**`line_balancing_reports`**: `factory_id`/`article_id`/`line_id`/`created_by` FKs NN (CASCADE upd / RESTRICT del) · `report_name` VARCHAR(150) NN · `target_output_per_hour` INT NN default 0 · `output_actual` INT NULL · `working_hours_per_day` DECIMAL(5,2) NN default **8** · `allowance_percent` DECIMAL(5,2) NN default **15** · `update_date` DATE NULL · `status` ENUM · timestamps.

**`line_balancing_report_rows`**: `line_balancing_report_id` FK **CASCADE del** · `row_number` INT NN default 1 · `machine_type_id` FK→machine_types SET NULL · `employee_id` FK→operators SET NULL · `process` VARCHAR(150) · `name` VARCHAR(150) · `joint_process` VARCHAR(150) · `operator` INT NN default 1 (**manpower count**, not FK) · `ct_1`…`ct_5` DECIMAL(10,2) NULL (⚠ legacy single `cycle_time` migrated into `ct_1` then dropped) · timestamps.

## 8.8 Foreign-key map (complete)

| Child.column | Parent | ON UPDATE | ON DELETE |
|---|---|---|---|
| users.role_id | roles | CASCADE | RESTRICT |
| departments.factory_id | factories | CASCADE | RESTRICT |
| production_lines.division_id | divisions | CASCADE | RESTRICT |
| gsd_elements.gsd_category_id | gsd_categories | CASCADE | RESTRICT |
| process_versions.process_id | processes | CASCADE | RESTRICT |
| process_versions.created_by | users | CASCADE | RESTRICT |
| process_versions.gsd_category_id / gsd_element_id | gsd_categories / gsd_elements | CASCADE | SET NULL |
| process_version_gsd_elements.process_version_id | process_versions | CASCADE | **CASCADE** |
| process_version_gsd_elements.gsd_element_id | gsd_elements | CASCADE | RESTRICT |
| ptms_reports.{article,process_version,operator,factory,department,line,created_by} | respective | CASCADE | **RESTRICT** |
| operators.{factory,department,line,division,section}_id | respective | default | SET NULL |
| operators.status_pkwtt_id / educational_level_id | status_pkwtt / educational_levels | default | SET NULL |
| login_logs.user_id | users | default | **CASCADE** |
| activity_logs.user_id | users | default | SET NULL |
| line_balancing_reports.{factory,article,line,created_by} | respective | CASCADE | RESTRICT |
| line_balancing_report_rows.line_balancing_report_id | line_balancing_reports | CASCADE | **CASCADE** |
| line_balancing_report_rows.machine_type_id / employee_id | machine_types / operators | default | SET NULL |

## 8.9 Unique constraints (all)

`users.username` · `users.employee_number` · `users.email` · `failed_jobs.uuid` · `personal_access_tokens.token` · `roles.role_name` · `articles.label_number` · `articles.label_number_quty` · `operators.employee_number` · `operators.nik_karyawan` · `gsd_categories.category_name` · `processes.process_name` · `process_versions(process_id, version_number)` · `process_version_gsd_elements(process_version_id, gsd_element_id)` · `ptms_reports.report_number` · `genders.gender` · `production_roles.production_role` · `educational_levels.level` · `status_pkwtt.pkwtt` · `mechanics.nik_karyawan`. Explicit non-PK index: only `personal_access_tokens` morphs composite.

## 8.10 Enum reference

| Enum | Values | Default | Tables |
|---|---|---|---|
| status (generic) | active, inactive | active | factories, departments, production_lines, articles, operators, gsd/mtm/sewing tables, processes, all lookups, line_balancing_reports |
| process_versions.status | draft, active, archived | draft | process_versions |
| ptms_reports.status | draft, final, archived | draft | ptms_reports |
| login_logs.activity | Login, Logout | — | login_logs |

## 8.11 Source / Reference / Derived field classification (prompt §4)

**Key entities** (full masters follow the same "source" pattern: name + description + status are user-entered).

| Entity | Source fields (user-entered) | Reference fields (from other masters) | Derived / generated (never user-editable) |
|---|---|---|---|
| User | name, username, email, employee_number, password, description | role_id → roles | — |
| Operator | employee_number, nik_karyawan, operator_name, gender*, role*, start_date, date_of_birth, photo | factory_id, department_id, division_id, section_id, line_id, status_pkwtt_id, educational_level_id | `working_age`, `age`, `age_year`, `years_of_service` (computed accessors — NOT stored) |
| Article | article_name, label_number, destination*, description, photo | — | **`label_number_quty` = label_number + '17596'** (stored, auto on save) |
| Process / ProcessVersion | process_name, notes, version_number (sequential) | process_id, created_by, gsd_category_id, gsd_element_id + pivot GSD elements | pivot backfill (legacy) |
| GsdElement | element_name, code, tmu, seconds, motion_sequence, description | gsd_category_id | — |
| PtmsReport | machine_name*, feed_type, rpm, stitch_per_cm, seam_width, machine_delay_percent, contingency_percent, ra_percent, machining_tmu, handling_tmu, bundle_tmu, total_tmu, bms, smv (⚠ see CONFLICT C-2), status | article_id, process_version_id, operator_id, factory_id, department_id, line_id, created_by | **`report_number` = PTMS-YYYY-%04d** (stored, auto on create) |
| LineBalancingReport | report_name, target_output_per_hour, output_actual, working_hours_per_day, allowance_percent, update_date | factory_id, article_id, line_id, created_by | `allowance_multiplier`, `working_seconds_per_day` (accessors, not stored) |
| LineBalancingReportRow | row_number, process, name, joint_process, operator (count), ct_1..ct_5 | machine_type_id, employee_id | `avg_cycle_time`, `avg_cycle_time_allowance`, `avg_ct_per_process`, `output_per_hour`, `output_process_per_hour`, `request_operator`, `potential_output_per_process` (accessors, not stored) |

\* = free-text field that conceptually references a master (genders, production_roles, destinations, machine_name) — **no FK** (duplicate-concept risk §23 R-6).

⚠ **Do not promote derived accessors to stored columns** in the Next.js schema (prompt §4).

## 8.12 Database Matrix (prompt §46)

| Table | Purpose | PK | Relations | Soft Delete | Important Constraints | Next.js Model (Prisma) |
|---|---|---|---|---|---|---|
| users | Accounts | id | →roles (RESTRICT) | none (hard delete) | username/employee_number/email UQ; username nullable | `User` |
| roles | Role lookup | id | ←users | none | role_name UQ | `Role` |
| password_reset_tokens | Reset tokens | email | — | none | — | `PasswordResetToken` |
| failed_jobs | Failed jobs | id | — | none | uuid UQ | (unused — omit) |
| personal_access_tokens | API tokens | id | morph→users | none | token UQ | `PersonalAccessToken` (during dual-run only) |
| factories | Org master | id | ←departments, operators, lb_reports, ptms | status | — | `Factory` |
| departments | Org master | id | →factories; ←operators, ptms | status | **column `desription`** (keep name) | `Department` |
| divisions | Org master | id | ←production_lines, operators | status | — | `Division` |
| sections | Org master | id | ←operators | status | — | `Section` |
| production_lines | Org master | id | →divisions; ←operators, ptms, lb_reports | status | dept→division historic migration | `ProductionLine` |
| destinations | Org master (orphaned concept) | id | none (string copy in articles) | status | — | `Destination` |
| articles | Products | id | ←ptms, lb_reports | status | label_number UQ, label_number_quty UQ | `Article` |
| operators | Employees | id | →6 lookups/orgs; ←ptms, lb_rows | status | employee_number UQ, nik UQ | `Operator` |
| gsd_categories | GSD master | id | ←gsd_elements, process_versions | status | category_name UQ | `GsdCategory` |
| gsd_elements | GSD motions | id | →gsd_categories; ←pivot, process_versions | status | — | `GsdElement` |
| mtm_elements | MTM motions | id | — | status | — | `MtmElement` |
| sewing_factors | Sewing factors | id | — | status | — | `SewingFactor` |
| sewing_stop_factors | Stop factors | id | — | status | — | `SewingStopFactor` |
| processes | Process master | id | ←process_versions, ptms (via versions) | status | process_name UQ | `Process` |
| process_versions | Versioned process specs | id | →processes, users, gsd_*; ←pivot, ptms | status enum(draft/active/archived) | UQ(process_id, version_number) | `ProcessVersion` |
| process_version_gsd_elements | Pivot (version↔GSD) | id | →process_versions (CASCADE), gsd_elements | none | UQ pair | implicit m2m |
| ptms_reports | IE reports (TMU/SMV) | id | 7 FKs RESTRICT | status enum(draft/final/archived) | report_number UQ | `PtmsReport` |
| login_logs | Login audit | id | →users CASCADE | none | activity enum | `LoginLog` |
| activity_logs | Activity audit | id | →users SET NULL | none | — | `ActivityLog` |
| skill_gradings … status_pkwtt (12 lookups) | Master lookups | id | ←operators (2 of them), lb_rows (machine_types) | status | UQ on genders/production_roles/educational_levels/status_pkwtt value | one model each |
| mechanics | Maintenance master | id | — | status | nik UQ | `Mechanic` |
| line_balancing_reports | LB studies | id | →factories, articles, production_lines, users | status | defaults 8h/15% | `LineBalancingReport` |
| line_balancing_report_rows | LB process rows | id | →lb_reports CASCADE, machine_types, operators | none | ct_1..ct_5 decimal | `LineBalancingReportRow` |

# 9. Models and Relationships

34 Eloquent models in `app/Models/`. All use `HasFactory`. Soft delete is **status-based** everywhere (`SoftDeletes` trait: "Not found in source.").

| Model | Table | Relationships (Eloquent) | Accessors / mutators / events |
|---|---|---|---|
| `User` | users | belongsTo `role`; (creator of processVersions, ptmsReports, lbReports) | `MustVerifyEmail` (unenforced) |
| `Role` | roles | hasMany users | — |
| `Factory` | factories | hasMany departments, operators, ptmsReports, lbReports | — |
| `Department` | departments | belongsTo factory; hasMany operators, ptmsReports | ⚠ field `desription` |
| `Division` | divisions | hasMany productionLines, operators | — |
| `Section` | sections | hasMany operators | — |
| `ProductionLine` | production_lines | belongsTo `division()`; hasMany operators, ptmsReports, lbReports | deprecated `department()` alias → `division()` |
| `Destination` | destinations | — (orphaned; articles store destination as text) | — |
| `Article` | articles | hasMany ptmsReports, lbReports | **`booted` saving event**: `label_number_quty = label_number . '17596'` (hardcoded suffix) |
| `Operator` | operators | belongsTo factory, department, division, section, productionLine, statusPkwtt, educationalLevel; hasMany ptmsReports; hasOne `latestPtmsReport` (latestOfMany) | accessors `working_age`, `age`, `age_year`, `years_of_service` (§13) |
| `GsdCategory` | gsd_categories | hasMany gsdElements; referenced by processVersions | — |
| `GsdElement` | gsd_elements | belongsTo gsdCategory; belongsToMany processVersions (pivot) | — |
| `MtmElement` | mtm_elements | — | — |
| `SewingFactor` / `SewingStopFactor` | sewing_* | — | — |
| `Process` | processes | hasMany versions | — |
| `ProcessVersion` | process_versions | belongsTo process, creator (User), gsdCategory, gsdElement; **belongsToMany gsdElements** via `process_version_gsd_elements`; hasMany ptmsReports | version_number UQ per process |
| `PtmsReport` | ptms_reports | belongsTo article, processVersion, operator, factory, department, productionLine, creator | **`boot` creating**: `report_number = PTMS-YYYY-%04d` (yearly count+1); decimal:2 casts on 12 numeric columns |
| `LoginLog` / `ActivityLog` | login_logs / activity_logs | belongsTo user | — |
| `LineBalancingReport` | line_balancing_reports | belongsTo factory, article, productionLine, creator; hasMany rows | accessors `allowance_multiplier` (1 + allowance/100), `working_seconds_per_day` (hours×3600) |
| `LineBalancingReportRow` | line_balancing_report_rows | belongsTo report, machineType, employee (Operator) | accessors `cycle_time_observations`, `avg_cycle_time`, `avg_cycle_time_allowance`, `avg_ct_per_process`, `output_per_hour`, `output_process_per_hour`, `request_operator`, `potential_output_per_process` (§13) |
| 12 lookup models (`SkillGrading`, `MachineType`, `MachineNumber`, `ComponentsPanel`, `Shift`, `FailureMode`, `Mechanic`, `SparePart`, `Gender`, `ProductionRole`, `EducationalLevel`, `StatusPkwtt`) | respective | mostly none (MachineType → lbRows; StatusPkwtt/EducationalLevel → operators) | — |

**Model graph (core):**

```mermaid
erDiagram
    roles ||--o{ users : role_id
    users ||--o{ ptms_reports : created_by
    users ||--o{ process_versions : created_by
    users ||--o{ line_balancing_reports : created_by
    factories ||--o{ departments : factory_id
    factories ||--o{ operators : factory_id
    divisions ||--o{ production_lines : division_id
    departments ||--o{ operators : department_id
    operators ||--o{ ptms_reports : operator_id
    articles ||--o{ ptms_reports : article_id
    processes ||--o{ process_versions : process_id
    process_versions ||--o{ ptms_reports : process_version_id
    process_versions ||--o{ process_version_gsd_elements : ""
    gsd_elements ||--o{ process_version_gsd_elements : ""
    gsd_categories ||--o{ gsd_elements : gsd_category_id
    factories ||--o{ line_balancing_reports : factory_id
    articles ||--o{ line_balancing_reports : article_id
    production_lines ||--o{ line_balancing_reports : line_id
    line_balancing_reports ||--o{ line_balancing_report_rows : cascade
    machine_types ||--o{ line_balancing_report_rows : machine_type_id
    operators ||--o{ line_balancing_report_rows : employee_id
```

---

# 10. Data Masters

22 Data Masters are managed in the web UI (sidebar "Data Masters"); 5 additional masters (gsd_categories, mtm_elements, sewing_factors, sewing_stop_factors + users/roles) are managed via API/SPA (process-library pages). All masters share: `status` active/inactive toggle (soft delete), `show_inactive` list mode, search, column filter, sort whitelist, Excel import (upsert), Excel export (active only, except production-lines), JSON autocomplete (where present).

| Master | Table / Model | UI route (view) | Unique field(s) | Used by (traced — prompt §9) | Hard-delete guard |
|---|---|---|---|---|---|
| Processes | processes / Process | `master-data/processes` | `process_name` | ProcessVersions → PtmsReports, LB rows (free-text `process`), process library | skip if versions have PTMS history |
| Operators (Employees) | operators / Operator | `master-data/operators` (`operators/show` profile) | `employee_number`, `nik_karyawan` | PtmsReports, LB rows (employee_id), login/activity attribution | dev-only; skip if PTMS history |
| Articles | articles / Article | `master-data/articles` | `label_number`, `label_number_quty` | PtmsReports, LB reports | skip if PTMS history; deletes photo file |
| GSD Elements | gsd_elements / GsdElement | `master-data/gsd-elements` | — (code not unique) | ProcessVersion pivot, PTMS breakdowns | **none** (⚠) |
| GSD Categories | gsd_categories / GsdCategory | SPA `process-library/gsd` | `category_name` | GsdElements, ProcessVersions | API hard delete (⚠) |
| MTM Elements | mtm_elements / MtmElement | SPA `process-library/mtm` | — | process library | API hard delete (⚠) |
| Sewing Factors | sewing_factors | SPA `process-library/sewing-factors` | — | IE references | API hard delete (⚠) |
| Sewing Stop Factors | sewing_stop_factors | SPA `process-library/stop-factors` | — | IE references | API hard delete (⚠) |
| Factories | factories / Factory | `master-data/factories` | — | Departments, Operators, PtmsReports, LB reports | skip if departments exist |
| Departments | departments / Department | `master-data/departments` | — | Operators, PtmsReports | skip if operators OR ptmsReports |
| Divisions | divisions / Division | `master-data/divisions` (simple-master) | — | ProductionLines, Operators | none (generic) |
| Sections | sections / Section | `master-data/sections` | — | Operators | none (generic) |
| Production Lines | production_lines / ProductionLine | `master-data/production-lines` | — | Operators, PtmsReports, LB reports | skip if ptmsReports; single deactivate PTMS-guarded |
| Destinations | destinations / Destination | `master-data/destinations` | — | ⚠ conceptually Articles (free-text `destination`) | none |
| Skill Gradings | skill_gradings | simple-master | — | operations/skills (placeholder) | none (generic) |
| Machine Types | machine_types | simple-master | — | **LB rows** (machine_type_id) | none (generic) |
| Machine Numbers | machine_numbers | simple-master | — | operations/breakdown (placeholder) | none (generic) |
| Components Panels | components_panels | simple-master | — | operations/breakdown (placeholder) | none (generic) |
| Shifts | shifts | simple-master | — | operations (placeholder) | none (generic) |
| Failure Modes | failure_modes | simple-master | — | operations/breakdown (placeholder) | none (generic) |
| Spare Parts | spare_parts | simple-master | — | operations/breakdown (placeholder) | none (generic) |
| Mechanics | mechanics / Mechanic | `master-data/mechanics` | `nik_karyawan` | maintenance (placeholder) | skip if ptmsReports (⚠ relation does not exist on Mechanic model — verify at runtime: see R-11 §23) |
| Genders | genders | simple-master | `gender` | ⚠ conceptually Operators (`gender` free text) | none (generic) |
| Production Roles | production_roles | simple-master | `production_role` | ⚠ conceptually Operators (`role` free text) | none (generic) |
| Educational Levels | educational_levels | simple-master | `level` | Operators (FK `educational_level_id`) | none (generic) |
| Status PKWTT | status_pkwtt | simple-master | `pkwtt` | Operators (FK `status_pkwtt_id`) | none (generic) |
| Users / Roles | users / roles | `credentials` | `username` | everything (authorship) | self-delete blocked |

**Next.js rule:** exactly one master per concept — e.g. Destinations must either gain a real FK relationship with Articles or remain documented as free-text; do not create a second "Destination" concept (prompt §9).

# 11. CRUD Matrix

Legend: ✅ full · ⚠ behavior with caveats · ➖ not present ("Not found in source."). **Delete mode** = what the Laravel implementation actually does (prompt §8).

| Entity | Create | Read | Update | Delete | Soft Delete | Import | Export |
|---|---|---|---|---|---|---|---|
| Users / Credentials | ✅ web modal (name, username, password **min:4**, role_id, description) | ✅ list | ✅ (password optional) | ⚠ **hard delete**, self-delete blocked | ➖ | ➖ | ➖ |
| Roles | ➖ (seeded) | ✅ dropdown | ➖ | ➖ | ➖ | ➖ | ➖ |
| Operators / Employees | ✅ + photo upload | ✅ list + profile detail (PTMS history) | ✅; dates via separate `update-details` | ⚠ hard delete **dev-only**, history-guarded | ✅ status toggle + bulk | ✅ XLSX/CSV upsert (role uppercased) | ✅ XLSX (14 cols) |
| Articles | ✅ + photo; `label_number_quty` auto | ✅ list | ✅ (photo kept if not re-uploaded) | ⚠ hard delete history-guarded + deletes photo | ✅ + bulk | ✅ (label auto `LBL-`+random10, upsert on name) | ✅ (2 cols: No, Article Name) |
| Processes | ✅ + version 1 + GSD pivot | ✅ list (versions eager) | ✅ + pivot sync | ⚠ hard delete history-guarded (via versions) | ✅ (deactivate blocked if PTMS history) + bulk | ✅ | ✅ |
| Process Versions | ✅ (within process flow + API) | ✅ | ✅ (API) | ⚠ API hard delete | status draft/active/archived | ➖ | ➖ |
| GSD Elements | ✅ (FK category, tmu/seconds ≥0) | ✅ | ✅ | ⚠ hard delete, **no dependency check** | ✅ + bulk | ✅ (firstOrCreate category) | ✅ (8 cols) |
| GSD Categories / MTM / Sewing Factors / Stop Factors | ✅ API | ✅ API + SPA | ✅ API | ⚠ API **hard delete** | status field exists | ➖ | ➖ |
| Factories | ✅ | ✅ | ✅ | ⚠ hard delete skips if departments | ✅ + bulk | ✅ | ✅ |
| Departments | ✅ (factory FK; `desription`) | ✅ (factory eager) | ✅ | ⚠ hard delete skips if operators/PTMS | ✅ + bulk | ✅ (resolves factory by name) | ✅ (3 cols) |
| Divisions / Sections / 10 other simple masters | ✅ generic (name, description) | ✅ shared list | ✅ | ⚠ hard delete inactive only, no dep check | ✅ + bulk | ✅ upsert on name | ✅ (3 cols, active only) |
| Destinations | ✅ | ✅ | ✅ | ⚠ hard delete inactive only | ✅ + bulk | ✅ | ✅ |
| Mechanics | ✅ (nik nullable unique) | ✅ | ✅ (unique ignores self) | ⚠ hard delete, claimed PTMS guard | ✅ + bulk | ✅ (upsert on mechanic name) | ✅ (4 cols) |
| Production Lines | ✅ (division FK) | ✅ | ✅ | ⚠ hard delete skips if PTMS | ✅ (single deactivate PTMS-guarded; **bulk unguarded**) + bulk | ✅ | ⚠ **all rows incl. inactive** |
| PTMS Reports | ✅ API (report_number auto) | ✅ API + SPA + operator profile | ✅ API (status via `updateStatus`) | ⚠ API **hard delete** | status draft/final/archived | ➖ | ➖ |
| Line Balancing Reports | ✅ (store → editor) | ✅ list + editor | ✅ header + **rows transactional replace** | ⚠ soft only (`status='inactive'`), no hard delete | ✅ + bulk | ➖ | ✅ XLSX w/ formulas + chart |
| Login / Activity Logs | ✅ (auto writes) | ✅ paginate 50 | ➖ | ⚠ clear-all = hard delete of entire table | ➖ | ➖ | ➖ |

## 11.1 Create rules (per prompt §8)

| Entity | Required | Optional | Defaults | Transformations | Validation highlights | Generated | Uploads |
|---|---|---|---|---|---|---|---|
| User | name, username, password, role_id | description | — | `Hash::make` | password **min:4**; role exists | — | — |
| Operator | operator_name, employee_number | nik, gender*, role*, photo, all FKs, start_date, date_of_birth, status_pkwtt_id, educational_level_id | status=active | **`role` → strtoupper** (create/update/import) | image validation on `photo` (tests: non-image rejected); nik/employee_number unique | — | photo → `public` disk `photo_path` |
| Article | article_name, label_number, destination | description, photo | status=active | **`label_number_quty` = label_number+'17596'** (model event) | label_number unique (quty collisions error — tests) | label_number on import: `LBL-`+`Str::random(10)` | photo → `photo_path` |
| Process | process_name | description | status=active | — | process_name unique | version 1 + pivot | — |
| GsdElement | element_name, code, tmu, seconds, gsd_category_id | description, motion_sequence | status=active | numeric fallback 0 on import | tmu/seconds numeric min:0; category exists | — | — |
| Simple master (×13) | name field (string max 200) | description (max 255) | status=active | — | — | — | — |
| Mechanic | mechanic | nik_karyawan (nullable **unique**), description | status=active | — | unique ignoring self on update | — | — |
| LbReport | factory_id, article_id, line_id, report_name | hours (0–24), allowance (0–100), update_date | target=0, hours=8, allowance=15 | — | exists FKs; name ≤150 | — | — |
| LbRow | row_number (≥1), operator (≥1) | machine_type_id, employee_id, process, name, joint_process (≤150), ct_1..ct_5 (≥0) | operator=1 | **`name` overwritten with employee's `operator_name`** when employee_id set | rows array min 1; full-replace transaction | — | — |
| PtmsReport (API) | article_id, process_version_id, operator_id, factory_id, department_id, line_id | machine specs + 12 numerics, status | numeric 0s; status draft | — | `StorePtmsReportRequest` numeric nullable | **report_number** PTMS-YYYY-%04d | — |

## 11.2 Update / immutable fields

- `report_number`, `label_number_quty` are generated once (label regenerates on every save — verify: model `saving` event ⇒ re-derives on update; tests confirm auto-gen on update).
- `created_by`, `created_at` immutable everywhere; `updated_at` auto.
- Operators: normal update excludes dates → `update-details` handles `start_date`/`date_of_birth` (viewer blocked).
- LbRows: update = **delete-all + reinsert** (IDs not preserved) inside `DB::transaction`.
- Import upserts **stamp `created_at` on update rows** (⚠ `updateOrInsert` behavior).

## 11.3 Delete behavior summary (prompt §19)

- **Soft delete (status flip)** is the primary delete for all 22 masters + LB reports: `status='inactive'`; rows remain queryable via "Show Inactive"; bulk variants exist.
- **Hard delete** exists per-entity (inactive-only prefilter) + centralized developer page; several have referential guards (§12); generic ones have **none** (FK RESTRICT raises QueryException 23000 → caught centrally on the hard-delete page only).
- **API `destroy()`** = unconditional hard delete (⚠ no guards, no roles).
- Log "clear" endpoints hard-delete entire log tables.
- No `SoftDeletes`/`deleted_at` anywhere.

---

# 12. Business Rules

(Validation rules of prompt §12 included inline. Sources: route closures, controllers, tests.)

| # | Rule | Source | Notes |
|---|---|---|---|
| BR-1 | Process **deactivate** blocked when any of its versions has PTMS history ("used by historical reports") | `web.php` L437 | bulk-deactivate does **not** re-check (⚠ inconsistency) |
| BR-2 | Process **hard delete** skips processes with PTMS history | `web.php` L457+ | per-row skip + count message |
| BR-3 | Article **hard delete** blocked for PTMS history; photo file deleted with record | `web.php` L1193+ | tests assert record remains |
| BR-4 | Operator **hard delete** requires `developer`, skips rows with PTMS history | `web.php` L743 | "Only developers can perform hard deletes." |
| BR-5 | Factory hard delete skips when departments exist; Department hard delete skips when operators OR ptmsReports exist | `web.php` L1543+, L1688+ | |
| BR-6 | Production line single **deactivate** blocked if PTMS history; **bulk-deactivate does not** | `web.php` L2179 vs L2189 | ⚠ CONFLICT C-4 (§23) |
| BR-7 | Credentials: self-delete prevented ("You cannot delete your own account.") | `CredentialController` | password min:4 on create & optional update |
| BR-8 | Viewer is read-only (403 "Unauthorized. Viewer role is read-only.") | operators routes only | ⚠ not enforced elsewhere (§7) |
| BR-9 | Article `label_number_quty` = `label_number` + **'17596'** auto-derived on save | `Article` model | uniqueness collision → validation error (tests) |
| BR-10 | PTMS `report_number` = `PTMS-{YYYY}-{04d seq}` auto on create | `PtmsReport` model | yearly count+1 (⚠ race) |
| BR-11 | `operators.role` uppercased on create/update/import | `web.php` L662/702/883 | `gender` is not uppercased |
| BR-12 | LB row `name` always mirrors assigned employee's `operator_name` on save | `LineBalancingController@saveRows` | |
| BR-13 | LB rows save = full replace in transaction; header target/output written from hidden fields on each save | `saveRows` | ⚠ stale-hidden-field overwrite (§23) |
| BR-14 | Imports upsert by name key; missing label → `LBL-`+random(10); import file types xlsx/xls/csv; headers matched lowercase with `_`→space normalization | import closures | no row-level error report (count-only message) |
| BR-15 | Exports include **active** rows only (production-lines export excepted) | export closures | |
| BR-16 | Bulk deactivate of empty selection → error "No records selected." | closures | |
| BR-17 | Hard-delete page catches FK violation 23000 → "…still referenced by other data." | `web.php` L214 | only centralized route |
| BR-18 | Locale en/id via session; `SetLocale` middleware | middleware | strings in `resources/lang/{en,id}` |
| BR-19 | Login identifiers lowercased before attempt | `LoginRequest` | both username/email paths |
| BR-20 | Login throttle 5 attempts (web only) | `LoginRequest` | API unthrottled ⚠ |

---

# 13. Calculations

All formulas preserved **exactly as implemented** (prompt §35). "Potential existing business-rule issue" markers flag values that look wrong but are preserved.

| Name | Source file/method | Inputs | Exact formula | Output | Formatting | Displayed | Stored? |
|---|---|---|---|---|---|---|---|
| Working Age | `Operator::getWorkingAgeAttribute` | date_of_birth, start_date | `Carbon(dob)->diff(start)`; null if start < dob or either null | y/m/d | `"{y} Yr {m} Mth {d} Day"` | operator profile | no |
| Age | `Operator::getAgeAttribute` | date_of_birth | `Carbon(dob)->diff(now())` | y/m/d | `"{y} Yr {m} Mth {d} Day"` | operator profile | no |
| Age (year) | `Operator::getAgeYearAttribute` | date_of_birth | `Carbon(dob)->diff(now())->y` | int | — | operator profile | no |
| Years of Service (to retirement) | `Operator::getYearsOfServiceAttribute` | date_of_birth | `retirement = dob + 59 years + 20 days`; `diff = today->diff(retirement)`; if today ≥ retirement → `"0 Yr 0 Mth 0 Day"` | y/m/d | `"{y} Yr {m} Mth {d} Day"` | operator profile | no |
| Label number quty | `Article` boot `saving` | label_number | `label_number_quty = label_number . '17596'` | string | as-is | articles list | **yes** |
| Report number | `PtmsReport` boot `creating` | year, `whereYear(created_at,$year)->count()` | `sprintf('PTMS-%s-%04d', $year, count+1)` | string | zero-padded 4 | PTMS lists | **yes** |
| Allowance multiplier | `LineBalancingReport::getAllowanceMultiplierAttribute` | allowance_percent | `1 + allowance_percent/100` | float | — | used by rows | no |
| Working seconds/day | `LineBalancingReport::getWorkingSecondsPerDayAttribute` | working_hours_per_day | `hours × 3600` | float | — | UI summary | no |
| CT observations | `LineBalancingReportRow::getCycleTimeObservationsAttribute` | ct_1..ct_5 | `array_filter([ct1..ct5], v != null && v > 0)` | array | — | internal | no |
| Average Cycle Time | `getAvgCycleTimeAttribute` | observations | `sum(obs)/count(obs)` (0 if none) | float | — | row + chart | no |
| Average CT + Allowance | `getAvgCycleTimeAllowanceAttribute` | avg_ct, report.allowance_multiplier (fallback **1.15**) | `round(avg_ct × multiplier, 2)` | float 2dp | 2dp | row + chart + Σ | no |
| Average CT / Process | `getAvgCtPerProcessAttribute` | avg_ct | `round(avg_ct, 2)` | float 2dp | 2dp | row | no |
| Output / Hour | `getOutputPerHourAttribute` | avg_ct_allowance | `round(3600 / avg_ct_allowance, 2)` (0 if ≤0) | float 2dp | 2dp | row + Σ | no |
| Output Process / Hour | `getOutputProcessPerProcessAttribute` | output_per_hour | = output_per_hour | float | 2dp | row | no |
| Takt Time | implicit in `getRequestOperatorAttribute` + editor JS | target_output_per_hour | `3600 / target_output_per_hour` (guarded ≤0) | float | 2dp | editor header | no |
| Request Operator | `getRequestOperatorAttribute` | avg_ct_per_process, takt | `round(avg_ct_per_process / takt, 2)` (0 if no target) | float 2dp | 2dp | row + Σ | no |
| Potential Output / Process | `getPotentialOutputPerProcessAttribute` | output_process_per_hour, working_hours_per_day (fallback 8) | `round(output_per_hour × hours, 0)` | int | 0dp | row + Σ | no |
| Dashboard averages | `Api/DashboardController@stats` | smv, … | SQL `avg('smv')` etc. | decimal | 2dp in SPA | SPA dashboard | no |

**Summary KPIs (editor UI + Excel export — workbook rows 35–43).** Identical intent in both; ⚠ differences flagged:

| KPI | Formula (as implemented) | Notes |
|---|---|---|
| Total Cycle Time | Σ(rows `avg_ct_allowance`) | UI & export |
| Total Manpower | Σ(rows `operator`) | UI & export |
| Average Standard Time | Total Cycle Time ÷ Total Manpower | guard ÷0? UI mirrors workbook |
| Total Working Time | **28,800 s** (8 h) | ⚠ hardcoded in export/KPI block even when `working_hours_per_day ≠ 8` — *Potential existing business-rule issue* |
| Target per PCS | `ROUND(28800 / TotalCT, 0)` | ⚠ 28800 hardcoded (export) |
| Target Line / Day | Manpower × Target per PCS | |
| Target Line / Hour | Target Line/Day ÷ **8** | ⚠ hardcoded 8 (export) |
| Maximum Based on Cycle Time | `3600 / MAX(avg_ct_allowance)` | workbook label typo "MAXIMUM BASE ON CYLE TIME" preserved in template |
| Output Actual | stored `output_actual` | header field |
| PPH (Productivity per operator) | `target_output_per_hour ÷ Σ operator` | header `L4 = Q3/H6` in workbook |
| Q4 (workbook) | `target × working_hours` | export uses Q3×hours |
| Takt (workbook Q6) | `3600 / target_output_per_hour` | |

⚠ **Export vs UI inconsistency:** the PhpSpreadsheet export hardcodes `×1.15` and `×8`/`28800` inside formulas and the "Allowance 15 %" header text, while UI/PHP accessors honor `allowance_percent`/`working_hours_per_day`. See CONFLICT C-3 (§23).

**TMU / BMS / SMV (PTMS).** Laravel stores `machining_tmu`, `handling_tmu`, `bundle_tmu`, `total_tmu`, `bms`, `smv` as **user-supplied numerics** (`nullable|numeric` in `Store/UpdatePtmsReportRequest`; no server-side computation found). See **CONFLICT C-2** (§23) vs PRD "auto-calculate". Any Next.js auto-calculation is a **behavior change requiring human approval** (§39).

## Calculation Matrix (prompt §46)

| Calculation | Source | Formula | Inputs | Output | Next.js Function |
|---|---|---|---|---|---|
| Working Age | Operator accessor | dob→start diff | DOB, start date | "Y Yr M Mth D Day" | `lib/calc/operator.ts#workingAge` |
| Age | Operator accessor | dob→now diff | DOB | same | `#age` |
| Age (year) | Operator accessor | dob diff years | DOB | int | `#ageYears` |
| Years of Service | Operator accessor | (dob+59y+20d) − today | DOB | same / "0 Yr 0 Mth 0 Day" | `#yearsOfService` |
| label_number_quty | Article model event | `label_number . '17596'` | label_number | string | `lib/calc/article.ts#labelNumberQuty` (keep suffix configurable-but-default-identical) |
| report_number | PtmsReport model event | `PTMS-YYYY-%04d` (year count+1) | year, count | string | `lib/calc/ptms.ts#nextReportNumber` (**atomic** generation — see §33) |
| allowance_multiplier | LB report accessor | `1 + allowance/100` | allowance_percent | decimal | `lib/calc/line-balancing.ts#allowanceMultiplier` |
| working_seconds_per_day | LB report accessor | `hours × 3600` | hours | int | `#workingSecondsPerDay` |
| CT observations | LB row accessor | filter ct_1..ct_5 >0 | ct_1..ct_5 | array | `#ctObservations` |
| avg_cycle_time | LB row accessor | mean(obs) | ct set | decimal | `#avgCycleTime` |
| avg_ct_allowance | LB row accessor | `round(avg × multiplier, 2)` | avg, allowance | 2dp | `#avgCycleTimeAllowance` |
| avg_ct_per_process | LB row accessor | `round(avg, 2)` | avg | 2dp | `#avgCtPerProcess` |
| output_per_hour | LB row accessor | `round(3600 / avg_ct_allowance, 2)` | avg_ct_allowance | 2dp | `#outputPerHour` |
| output_process_per_hour | LB row accessor | = output_per_hour | — | 2dp | `#outputProcessPerHour` |
| takt_time | LB row/editor JS | `3600 / target_output_per_hour` | target | 2dp | `#taktTime` |
| request_operator | LB row accessor | `round(avg_ct_per_process / takt, 2)` | avg, takt | 2dp | `#requestOperator` |
| potential_output | LB row accessor | `round(output_process_per_hour × hours, 0)` | output, hours | int | `#potentialOutputPerProcess` |
| Σ KPIs (CT, manpower, std time, target/pcs, target/day, target/hr, max CT, PPH) | editor JS + export | see table above | rows + header | mixed | `#summaryKpis` |
| average_smv (dashboard) | Api DashboardController | `AVG(smv)` | ptms_reports | 2dp | `lib/calc/dashboard.ts` |

**Migration rule (§33):** implement **one** shared `lib/calc/*` module used by React UI, server actions, and the Excel exporter — no formula may exist in more than one place (prompt §35).

---

# 13A. Dedicated Line Balancing Audit (prompt §14)

Separate detailed audit of the Line Balancing module. **Source of truth = the Laravel implementation** (`LineBalancingController`, `LineBalancingReport`/`LineBalancingReportRow` models, `operations/line-balancing/*.blade.php`, `generate_lb_template.php`); the old Excel workbook/template is secondary evidence only.

## 13A.1 Inspection checklist (prompt §14 items → actual behavior)

| # | Item | Actual behavior (verified) | Evidence |
|---|---|---|---|
| 1 | Report list | `/operations/line-balancing` — table: report_name, factory, article, line, target_output_per_hour, status, created/updated; search (Enter), filter column/value, Show Inactive, sortable joined headers, pagination **15/page**, bulk-delete mode (checkbox slide-in + `confirm()` + PATCH bulk-deactivate), status pills, Create modal (per-factory N+1 line queries; `lb-search-dropdown` dead markup) | `line-balancing/index.blade.php`, route L2415 |
| 2 | Report creation | `POST /operations/line-balancing` (store): validates factory/article/line `exists`, report_name `max:150`, working_hours_per_day `0–24`, allowance_percent `0–100`, update_date; defaults target=0, output_actual=null, hours=**8**, allowance=**15**; redirect → editor | `LineBalancingController@store` |
| 3 | Report edit | `GET /operations/line-balancing/{id}/edit` — header settings form + dynamic rows + live recalc + chart | `edit.blade.php` |
| 4 | Report view | **Not found in source.** (no read-only view; edit doubles as view) | — |
| 5 | Report deletion | **Soft only** — `deactivate` (single) + `bulkDeactivate` (PATCH, `status='inactive'`). No hard-delete route; ⚠ single deactivate has no PTMS-style guard (unlike production-lines) | routes L2415–2422 |
| 6 | Report status | `line_balancing_reports.status` ENUM(active,inactive), toggled by deactivate/bulk; list pills + `show_inactive` | migration `2026_10_08_000001` |
| 7 | Factory | `factory_id` FK RESTRICT — create-modal select + filters + workbook header; ⚠ `getLinesByFactory` AJAX exists but **never called** and **ignores `$factoryId`** (dead) | route L2426 |
| 8 | Article | `article_id` FK RESTRICT — select + filters + workbook header | — |
| 9 | Line | `line_id` FK → production_lines RESTRICT — select + filters + workbook header | — |
| 10 | Target output | `target_output_per_hour` INT default 0; drives takt (`3600/target`), request-operator, workbook Q4/Q6, header PPH (`target ÷ Σ operator`); ⚠ written back from **hidden inputs** on every saveRows (stale overwrite R-4) | `saveRows` |
| 11 | Process rows | `line_balancing_report_rows`: `row_number` (renumbered on client), `process`/`name`/`joint_process` VARCHAR(150); save = **delete-all + reinsert** in `DB::transaction` | `saveRows` |
| 12 | Machine | `machine_type_id` FK→machine_types **SET NULL** — select of active machine_types | row + workbook col D |
| 13 | Employee / operator | `employee_id` FK→operators **SET NULL**; autocomplete: 250 ms debounce, `GET /master-data/operators/search?q=` (active, limit 10), fills hidden employee_id; ⚠ row `name` **overwritten with `operator_name`** server-side on save (BR-12) | `saveRows` |
| 14 | Operator quantity | `operator` INT default **1** = manpower count (**not** an FK); required, `min:1`; feeds Σ Total Manpower + PPH | validation |
| 15 | Cycle-time observations | `ct_1`…`ct_5` DECIMAL(10,2) NULL (`min:0`); observation = value `!== null && > 0`; legacy single `cycle_time` migrated → `ct_1` then dropped; stopwatch writes up to 5 laps | model accessor |
| 16 | Stopwatch | Start/Stop/Lap/Reset; 10 ms tick; display `mm:ss.cc`; **max 5 laps → auto-stop**; laps write CT1–CT5 of selected row then `recalculate()` | `edit.blade.php` inline JS |
| 17 | Averages | `avg_cycle_time` = sum(observations)/count, else 0 (exact formula §13) | `LineBalancingReportRow` |
| 18 | Allowance | `allowance_percent` → multiplier `1 + %/100`; `avg_cycle_time_allowance = round(avg × multiplier, 2)` (fallback multiplier **1.15** if report missing); ⚠ export hardcodes ×1.15 regardless of setting (CONFLICT C-3) | model + export |
| 19 | Output / hour | `round(3600 / avg_ct_allowance, 2)`, 0 if ≤0 | model |
| 20 | Request operator | `round(avg_ct_per_process / takt, 2)` where takt = `3600 / target_output_per_hour`; 0 if no target | model |
| 21 | Potential output | `round(output_process_per_hour × working_hours_per_day, 0)` (fallback hours 8) | model |
| 22 | Total manpower | Σ(row `operator`) — UI + workbook | recalculate/export |
| 23 | Summary KPIs | 11 cards + takt/PPH header: Total CT (Σ avg+allow), Total Manpower, Average Standard Time (TotalCT÷Manpower), Total Working Time (**28,800 s** ⚠ hardcoded), Target/PCS `ROUND(28800/TotalCT,0)`, Target Line/Day (×Manpower), Target Line/Hour (÷**8** ⚠), Maximum Based on Cycle Time (`3600/MAX(avg+allow)`), Output Actual (stored), PPH (target÷Σmanpower) — exact table §13 | `recalculate()` + workbook rows 35–43 |
| 24 | Yamazumi chart | **Editor (client):** grouped bar per row — "Avg CT" (red if over takt, green if at/under) + "Avg CT+Allowance" (blue) + dashed red takt annotation (Chart.js 4.4.0 + chartjs-plugin-annotation 3.0.1 CDN). **Export (workbook):** clustered column chart "Yamazumi Chart Before", categories `B11:B…`, values column `P`, anchored rows `totalRow+14 → +22` | `edit.blade.php`, `LineBalancingController@export` |
| 25 | Excel import | **Not found in source.** (no LB import route) | — |
| 26 | Excel export | PhpSpreadsheet workbook spec — full detail §16 (sheet "LB Livlig", A–V, hidden F/S, 43 merges, per-row formulas M/N/O/P/R/T, helper row 10, summary rows 35–43, total row O32=MAX Q32/T32=MIN, `setIncludeCharts(true)`, filename `LineBalancing_<name>_<timestamp>.xlsx`, `deleteFileAfterSend`). `generate_lb_template.php` = standalone CLI reproducing the template (21 sample rows, `=6.65+21.13`-style sums, typo "MAXIMUM BASE ON CYLE TIME") — Laravel export implementation is authoritative | `web.php` export closure |
| 27 | Calculations | All formulas verbatim in §13 + Calculation Matrix (`LineBalancingReport`/`LineBalancingReportRow` accessors + client `recalculate()` mirrors them) — single-source rule §33 | §13 |
| 28 | Permissions | ⚠ **No per-action role checks** — developer/admin/**viewer can all create, edit, save rows, deactivate, bulk-deactivate, export** (group middleware `role:developer,admin,viewer` only); UI hides buttons (not authorization). Security gap: CONFLICT C-1 / R-3 | routes L2402–2422 |
| 29 | History / logs | ⚠ **No `logActivity` calls anywhere in the LB module** — no audit trail of creates/edits/saves/deletes/exports (gap R-18); no other history mechanism ("Not found in source.") | grep-verified |

## 13A.2 Editor client behavior (cross-ref §20)

Dynamic rows (`addRow`/`removeRow`/`renumberRows` rewriting `rows[i][…]` names) · employee autocomplete · stopwatch (item 16) · live `recalculate()` using server-injected `allowanceMultiplier`, `workingHoursPerDay`, `WORKING_SECONDS` · chart refresh (item 24) · save POST with hidden header fields (R-4).

## 13A.3 Line Balancing risks (cross-ref §23)

R-4 (stale hidden-field header overwrite) · R-17 (export hardcodes 1.15/8/28800 vs configurable settings) · R-18 (zero logging) · C-1 (viewer writes) · dead `getLinesByFactory` (R-16) · no hard delete (data growth) · workbook row-10 helper formulas reference only row 11 (template artifact, preserved).

**Next.js target:** `lib/services/line-balancing.ts` (store/saveRows/deactivate/bulk/export) + `lib/calc/line-balancing.ts` + `lib/export/line-balancing-workbook.ts` + `LbEditor`/`Stopwatch`/`LineBalancingChart` client components — parity verified per §37 Phase 10.

---

# 14. Search / Filter / Sort / Pagination

**Web master lists (processes, operators, articles, gsd-elements, factories, departments, destinations, mechanics, production-lines, 13 simple masters):**

| Aspect | Behavior |
|---|---|
| Search | `?search=` — `LIKE %term%` (case per MySQL collation), `orWhere` across: name + description + entity extras (process: name/description; operators: name, nik, employee_number, gender, role…; articles: name/label/destination; gsd: name/description/code/motion_sequence; departments: name/**`desription`**/factory name via `orWhereHas`) |
| Filters | `?filter_column=&filter_value=` — exact match on whitelisted columns (each list embeds `filterValues` distinct plucks for dropdowns); operators also support `dateFrom`/`dateTo` range on date columns |
| Status | `?show_inactive=1` toggles `status='active'` default filter |
| Sorting | `?sort=&direction=` — **whitelisted** column arrays per entity (e.g. operators: operator_name, nik_karyawan, gender, role, start_date, date_of_birth, status + FK display names via join/subquery); default: name asc |
| Pagination | ⚠ **None** on master lists — full result sets via `->get()` (memory risk on large data; preserved behavior to be re-evaluated — §39). Exceptions: Line Balancing **15/page**; system logs **50/page**; API `BaseController::paginated()` |
| Autocomplete | `/search` endpoints: `?q=` LIKE, active only, **limit 10**, `{id,label,description}`, empty q → `[]` |

**Line Balancing list:** search report_name + related factory/article/line/user names; filters factory/article/report_name/created_by/status via `whereHas`; joined sortable columns (report_name, created_at, updated_at, status, factory_name, article_name, creator name); `show_inactive`; paginate 15.

**API index endpoints:** `search`, `sort`, `with` params. ⚠ `sort` and `with` are **not validated** (arbitrary column/relation names reach the query builder → N+1 / information exposure risk, R-7 §23). Pagination via `BaseController::paginated()` — ⚠ returns `Resource::collection()` **without** a consistent `data` wrapper (API inconsistency, R-8).

**SPA (lems-frontend):** client tables via `DataTable` component (sorting hooks per column config), react-query pagination state mirroring API params.

# 15. Import System

**Shared pipeline (all `POST master-data/*/import`):** upload → `mimes:xlsx,xls,csv` validation → `Storage::disk('local')->put('temp', …)` → `PhpSpreadsheet IOFactory::load` → **first row = header, lowercased** with `_`→space normalization (generic masters `str_replace('_',' ','{nameField}')`) → column positions via `array_search($name, $header)` → row loops with `updateOrInsert`/upsert keyed on the natural name → `@unlink` temp file → `logActivity` (except generic-13 imports) → redirect with inserted/updated count. ⚠ `array_search` returning `false` (missing header) silently falls back to **column 0** (bug, R-9 §23). ⚠ `updateOrInsert` stamps `created_at` on updates. No dry-run, no row-level error file.

| Entity | Source Format | Columns (header-matched, lowercase) | Validation | Duplicate Behavior | Next.js Target |
|---|---|---|---|---|---|
| Articles | xlsx/xls/csv | `article name`, `label number`, `destination`, `description` | DB constraints (label UQ) | upsert on `article_name`; missing label → `LBL-`+`Str::random(10)`; new rows get `label_number_quty = label+'17596'` | Route Handler `POST /api/imports/articles` (or Server Action + streaming) using `lib/import/articles.ts` |
| Operators | xlsx/xls/csv | `employee name`, `nik karyawan`, `employee number`, `gender`, `role`, `factory`, `department`, `division`, `section`, `line`, `status pkwtt`, `educational level`, `start date`, `date of birth` | FK lookups resolved by name; `role` **uppercased** | upsert on `employee_number`; org lookups resolved by name (missing → error message per file) | `lib/import/operators.ts` + Route Handler |
| Processes | xlsx/xls/csv | `process name`, `descriptions` | UQ process_name | upsert on `process_name` | `lib/import/processes.ts` |
| GSD Elements | xlsx/xls/csv | `element name`, `code`, `tmu`, `seconds`, `motion sequence`, `descriptions`, `category` | numeric fallback **0** when non-numeric | `updateOrInsert` keyed `element_name`; category `firstOrCreate` | `lib/import/gsd-elements.ts` |
| Factories | xlsx/xls/csv | `factory name`, `descriptions` | — | upsert on name | generic importer |
| Departments | xlsx/xls/csv | `department name`, `descriptions`, `factory` | factory resolved **by name** | upsert on name | generic + FK resolver |
| 13 simple masters | xlsx/xls/csv | `str_replace('_',' ','{nameField}')`, `descriptions` | — | `updateOrInsert` keyed name (fields: nameField + description + created_at/updated_at) | one generic `lib/import/simple-master.ts` (slug-configurable) |
| Mechanics | xlsx/xls/csv | `nik karyawan`, `mechanic`, `descriptions` | NIK UQ nullable | upsert keyed `mechanic` name | generic importer |
| Production Lines | xlsx/xls/csv | `line name`, `descriptions` | division FK ⚠ **not** imported (column absent) | upsert on name | generic importer |

## Import Matrix (prompt §46)

| Entity | Source Format | Columns | Validation | Duplicate Behavior | Next.js Target |
|---|---|---|---|---|---|
| Articles | xlsx/xls/csv | article name, label number, destination, description | UQ labels; no type checks | upsert on article_name; auto label + quty suffix | Route Handler + `lib/import/articles` |
| Operators | xlsx/xls/csv | 14 columns (see above) | FK-by-name resolution; date parsing | upsert on employee_number; role uppercased | Route Handler + `lib/import/operators` |
| Processes | xlsx/xls/csv | process name, descriptions | UQ | upsert on process_name | shared generic importer |
| GSD Elements | xlsx/xls/csv | element name, code, tmu, seconds, motion sequence, descriptions, category | numeric→0 fallback | updateOrInsert on element_name | shared generic importer |
| Factories | xlsx/xls/csv | factory name, descriptions | — | upsert on name | shared generic importer |
| Departments | xlsx/xls/csv | department name, descriptions, factory | factory by name | upsert on name | shared generic importer |
| 13 simple masters | xlsx/xls/csv | name (`{nameField}` with `_`→space), descriptions | — | updateOrInsert on name | one configurable importer |
| Mechanics | xlsx/xls/csv | nik karyawan, mechanic, descriptions | NIK UQ | upsert on mechanic | shared generic importer |
| Production Lines | xlsx/xls/csv | line name, descriptions | (division missing ⚠) | upsert on name | shared generic importer |
| Line Balancing | — | — | — | — | **Not found in source.** (no import) |

**Migration rule (§21):** preserve header names, upsert keys, transformations (uppercasing, auto-labels, 0-fallbacks), and count-based feedback. The exact xlsx/xls/csv parse behavior must be reproduced with a maintained Excel lib (§31). Fixing the `array_search` silent-fallback bug changes behavior → list in §39.

---

# 16. Export System

**Shared pipeline:** PhpSpreadsheet `Xlsx` writer + global helper `applyExcelFormatting()` (bold header row, freeze panes, auto column widths, header fill — defined in `routes/web.php`) → download → `deleteFileAfterSend` (ephemeral temp file). Filters: **active rows only** — **except production-lines which exports all statuses** (⚠). Only mechanics + articles + generic exports call `logActivity` (generic-13 exports do **not**, ⚠ coverage gap).

| Entity | Format | Columns (exact headers) | Formatting | Next.js Target |
|---|---|---|---|---|
| Articles | xlsx | No, Article Name | `applyExcelFormatting` | Route Handler stream + shared writer |
| GSD Elements | xlsx | No, Element Name, Code, TMU, Seconds, Motion Sequence, Description, Category | same | same |
| Factories | xlsx | No, Factory Name, Descriptions | same | same |
| Departments | xlsx | No, Department Name, Descriptions (**reads `desription`**) | same | same |
| 13 simple masters | xlsx | No, {Name}, Descriptions | same (no logActivity) | generic exporter |
| Mechanics | xlsx | No, NIK KARYAWAN, Mechanic, Descriptions | same | same |
| Production Lines | xlsx | No, Line Name, Descriptions | same; **includes inactive** | same (preserve!) |
| Operators | xlsx | No, Employee Name, NIK Karyawan, Gender, Role, Factory, Department, Division, Section, Line, Status PKWTT, Educational Level, Start Date, Date of Birth | same | same |
| **Line Balancing workbook** | xlsx (`LineBalancing_<name>_<timestamp>.xlsx`, `setIncludeCharts(true)`, `deleteFileAfterSend`) | Sheet **"LB Livlig"** cols A–V (No, Process, Operator, Machine Type, Name, Joint Process, … CT1–CT5, Avg CT (M), N=M×1.15 (hidden F/S cols), O=M, P=3600/N, Q=operator, R=O/$Q$6, S, T=Q×8, U, V) | 43 merged header cells; helper row 10 (G10=SUM(G11), N10=MAX(N11), Q10/T10=MIN); per-row formulas **M=AVERAGE(H:L)**, N=M×1.15, O=M, P=3600/N, R=O/$Q$6, T=Q×8; summary rows 35–43 (§13); total row O32=MAX, Q32/T32=MIN; **Yamazumi clustered column chart** "Yamazumi Chart Before" (categories B11:B, values P) positioned rows totalRow+14→+22 | dedicated `lib/export/line-balancing-workbook.ts` |

## Export Matrix (prompt §46)

| Entity | Format | Columns | Formatting | Next.js Target |
|---|---|---|---|---|
| Articles | xlsx | 2 | standard | shared `lib/export/excel.ts` |
| GSD Elements | xlsx | 8 | standard | shared |
| Factories / Departments / 13 simple / Mechanics | xlsx | 3–4 | standard | shared generic |
| Production Lines | xlsx | 3 | standard; all statuses | shared generic (preserve quirk) |
| Operators | xlsx | 14 | standard | shared |
| Line Balancing | xlsx + chart | A–V + formulas + summary | merges, hidden cols, formulas, chart (above) | `lib/export/line-balancing-workbook.ts` |
| PTMS / logs | — | — | — | **Not found in source.** (no export) |

**Migration rule (§21):** filenames, sheet name, hidden columns, merged cells, formula strings (including `×1.15`/`28800` hardcodes and the "MAXIMUM BASE ON CYLE TIME" typo in `generate_lb_template.php` output), and the Yamazumi chart must be **byte-for-byte equivalent in behavior** (formulas evaluate the same in Excel). Keep `generate_lb_template.php` output reproduced by an equivalent Node generator if still needed.

---

# 17. File / Image Storage

| Aspect | Actual behavior |
|---|---|
| Storage disk | `public` (`storage/app/public`, symlink `public/storage`, `FILESYSTEM_DISK=public` in `.env.example`) |
| Uploaded files | Operator photos (`photo` request field → `photo_path` column), Article photos (`photo` → `photo_path`) |
| Path format | e.g. `photo_path = 'photo/<hash>.<ext>'` (verify exact `store()` call at implementation: `store('photo','public')` style) |
| Validation | `image` rule (tests assert non-image rejected); unique labels fail before storage |
| Display | Blade `<img src="{{ asset('storage/'.$op->photo_path) }}">`; SPA via `${API_URL}/storage/...` or public URL |
| Deletion | Article hard delete unlinks photo file; operator photo replace path ("Not found in source." for old-file cleanup — verify) |
| Temp imports | `storage/app/temp/…`, `@unlink` after parse |
| Export temp | system temp dir + `deleteFileAfterSend` |
| Next.js target | §32 — keep files in place; serve via Laravel `public/storage` during dual-run, then copy/mount same directory for Next.js (`/public` static mount or Route Handler stream); **DB keeps the same `photo_path` string** (old path → new path → DB reference mapping required if directory changes) |

---

# 18. Logs / Auditing

| Aspect | `login_logs` | `activity_logs` |
|---|---|---|
| Write point | `AuthenticatedSessionController` (login + logout) | global `logActivity($activity, $module)` helper defined in `routes/web.php` |
| Fields | user_id (FK CASCADE), username, activity ENUM('Login','Logout'), created_at | user_id (FK SET NULL), username, activity VARCHAR(500), module VARCHAR(100), created_at |
| Read UI | `/system/logs/login-logs` (paginate 50 desc) | `/system/logs/activity-logs` (paginate 50 desc) |
| Clearing | `DELETE clear` = `LoginLog::query()->delete()` + activity log entry | same pattern |
| Coverage gaps (⚠) | logout also logged | all CRUD/import/export/deactivate call `logActivity` **except**: generic-13 exports, **entire Line Balancing module** (no `logActivity` anywhere), LB saves, hard-delete-center per-entity? (central page logs) — verify each call at implementation |
| Other logs | Laravel `storage/logs/laravel.log`; `LogUserLogin` middleware **dead code** | — |
| Next.js target | §27/§33 — `LoginLog`/`ActivityLog` writes from auth callbacks + server actions; same fields; keep clear endpoints | — |

---

# 19. Jobs / Queues / Scheduler / Events

| Item | Finding |
|---|---|
| Jobs | **Not found in source.** (`app/Jobs` absent) |
| Queue | `QUEUE_CONNECTION=sync` — effectively no async processing |
| Scheduler | `app/Console/Kernel::schedule()` contains only the commented `inspire` example — **no scheduled tasks** |
| Events / Listeners / Observers / Notifications | **Not found in source.** (only model `boot`/`booted` events: Article label, PtmsReport report_number) |
| External services (mail/API calls) | Breeze password-reset mail (mailpit dev config); no outbound integrations found |
| Next.js target | No background workers needed. Number generation must become **transactional/atomic** in-process (§33). If later required: Coolify-side cron or queue worker — "Not found in source." → not in scope |

# 20. Frontend JS Behavior

**Global (layouts/app.blade.php, Alpine.js):** collapsible sidebar (persisted?), dark mode toggle (localStorage), user dropdown, EN/ID language switcher (POST `/language/{locale}`), flash message auto-dismiss, modal open/close helpers, mobile sidebar overlay. Minor markup bugs preserved (duplicated `<span`, stray `<div` ~L770 — cosmetic).

**Master list pages:** show-inactive checkbox (URL param), filter selects per column, sortable headers (URL params), search form (Enter submit), create/edit **modals** (Alpine show/hide), delete/deactivate confirm dialogs (`confirm()`), bulk mode (checkbox column slide-in + `confirm()` + PATCH bulk-deactivate), operator photo preview on select, **dependent dropdowns** (factory → department; division → line; `partials/operator-selects.blade.php`), date-range pickers (operators).

**Employee profile (`operators/show`):** computed fields rendered server-side (§13), PTMS history table with GSD element details (eager-loaded).

**Line Balancing editor (`line-balancing/edit.blade.php`, Chart.js 4.4.0 + chartjs-plugin-annotation 3.0.1 via CDN):**

| Feature | Behavior (preserve exactly) |
|---|---|
| Stopwatch | Start/Stop/Lap/Reset buttons; 10 ms tick; display `mm:ss.cc`; max **5 laps → auto-stop**; Lap writes into CT1–CT5 of the currently selected row then triggers recalculate |
| Dynamic rows | `addRow()` / `removeRow(i)` / `renumberRows()` — rewrites all `rows[i][…]` input names; row_number recomputed sequentially |
| Employee autocomplete | 250 ms debounce; `GET /master-data/operators/search?q=`; fills hidden `employee_id` + visible name (server will overwrite `name` on save — BR-12) |
| Live recalculation | `recalculate()` runs all §13 row formulas client-side + Σ KPIs + takt/PPH header using server-injected `allowanceMultiplier`, `workingHoursPerDay`, `WORKING_SECONDS` |
| Chart | Grouped bar: per row "Avg CT" (red when over takt, green when at/under) + "Avg CT+Allowance" (blue) + dashed red takt annotation line; updates on recalculate |
| Save | POST saveRows with all rows + hidden header fields (⚠ stale-overwrite risk R-4) |

**Line Balancing index:** search (Enter), filter column/value selects, Show Inactive, bulk-delete mode (checkbox slide-in, `confirm()`, PATCH bulk-deactivate), sortable headers, status pills, pagination, Create modal (per-factory N+1 line queries; `lb-search-dropdown` dead markup; `getLinesByFactory` AJAX never called).

**SPA (lems-frontend):** React Query fetch/cache + optimistic patterns; zustand auth store with `persist` (localStorage token — XSS surface); `DataTable`, `SearchBar`, `FilterBar`, `Modal` primitives; recharts for PTMS/SMV charts; axios interceptor (Bearer token; 401 → `/login`).

**Next.js target:** every behavior above must be reproduced in React/TS with **no inline business formulas** (call `lib/calc/*`) and no duplicated logic (prompt §35).

# 21. UI Components

Only abstractions that are actually reused across pages (prompt §25). Legacy equivalents listed for parity.

| Component | Used by | Legacy equivalent | Notes |
|---|---|---|---|
| `AppShell` (Sidebar + Header + locale/dark-mode/user menu) | all authed pages | `layouts/app.blade.php` | role-based nav gating (visibility only) |
| `DataTable` | all master lists, LB index, logs, credentials | per-view table markup | sortable headers, bulk checkbox mode, status pills |
| `SearchBar` | all lists + autocomplete | forms + `/search` JSON | debounce variant for autocomplete |
| `FilterBar` (column + value selects, show-inactive, date range) | master lists, LB index | `filter_column/filter_value` forms | distinct-value dropdowns |
| `Pagination` | LB index (15), logs (50), API-driven lists | `links()` | — |
| `Modal` + `ConfirmDialog` | create/edit forms, deletes | Alpine modals + `confirm()` | delete confirmations preserved |
| `StatusBadge` | all lists | Blade pills | active/inactive + draft/final/archived |
| `FileUpload` (image preview) | operators, articles | `<input type=file>` + preview | `image` validation |
| `KpiCard` | home (6 stats), LB editor (11 KPIs), SPA dashboard | card markup | — |
| `Chart` (bar w/ annotation, Yamazumi) | LB editor, SPA dashboards | Chart.js / workbook chart | takt line annotation |
| `Stopwatch` | LB editor | inline JS | 5-lap semantics |
| `ExcelImport` / `ExcelExport` buttons + feedback | all masters, LB export | forms + redirects | count messages preserved |
| `AutocompleteInput` | LB editor employee field | inline fetch | 250 ms debounce, limit 10 |

"Do not invent abstractions for one-off pages" — role landing pages, speed-test, clear-cache remain simple pages.

# 22. Tests

**Existing (PHPUnit):**

| File | Coverage |
|---|---|
| `tests/Feature/LeanEnterpriseMasterDataTest.php` (RefreshDatabase, 7 tests) | core pages render (`/home`, master-data processes/operators/articles); operator profile with PTMS history + GSD graph; process & article create/update with pivot sync + `label_number_quty` auto-gen; operator CRUD + photo upload (`Storage::fake('public')`) + delete; quty uniqueness + history-blocked deletes + photo validation; search + column filters with `updateProcessFilterValues` JSON |
| `tests/Feature/ProfileTest.php` | Breeze profile |
| `tests/Feature/Auth/*` | Breeze auth (login/registration/password reset) |
| `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` | smoke |

⚠ Coverage is near-zero for: Line Balancing (all calculations!), imports/exports, hard-delete guards, API endpoints, authorization matrix.

**Converted acceptance tests (§37):** each existing test becomes a parity test `Laravel result == Next.js result` (same fixture → same DB state → same rendered values), plus new tests for: LB formulas (every row in Calculation Matrix), imports (upsert keys, auto-labels), exports (formula strings), role matrix (§7 incl. closing gaps decision), login throttle, PTMS numbering, operators date fields.

# 23. Problems and Risks

Risk levels: 🔴 critical · 🟠 high · 🟡 medium · 🔵 low.

| # | Risk | Level | Location | Migration impact |
|---|---|---|---|---|
| R-1 | Business logic lives in ~2,400 lines of route closures in `web.php` (no service layer) | 🔴 | `routes/web.php` | Extract to `lib/` services during Phase 2–6; behavior frozen by tests first |
| R-2 | **Hardcoded credentials** in `UserSeeder` (developer/#devadmin + 7 named accounts) | 🔴 | `database/seeders/UserSeeder.php` | rotate; env-driven seeding; never copy into Next.js seeds |
| R-3 | **Insecure authorization**: viewer can mutate most masters via direct request (UI-hidden only); API hard-deletes unrestricted; no Policies/Gates | 🔴 | §7 | enforce intended policy server-side in Next.js (Human Confirmation §39) |
| R-4 | LB `saveRows` rewrites header fields from hidden inputs rendered at page load (stale overwrite) | 🟠 | `LineBalancingController@saveRows` | preserve behavior (documented) or confirm fix |
| R-5 | `PtmsReport.report_number` generation races (yearly `count()+1`) | 🟠 | `PtmsReport` model | atomic generator in Next.js (§33) |
| R-6 | Duplicate concepts: `genders`/`production_roles`/`destinations`/`machine_name` tables vs free-text columns; `label_number_quty` suffix '17596' | 🟠 | schema | keep schema as-is (MySQL preserved); do not silently normalize |
| R-7 | API `sort`/`with` accept arbitrary values (N+1 / injection surface) | 🟠 | `BaseController`, api resources | whitelist in Next.js API/Server Actions |
| R-8 | API pagination wrapper inconsistent (`data` wrapper missing) | 🟡 | `BaseController::paginated` | SPA callers must keep working during dual-run |
| R-9 | Import header lookup `array_search(...) ?: 0` silently mis-maps missing columns | 🟡 | import closures | preserve + document; fix = behavior change (§39) |
| R-10 | Fresh `php artisan migrate` **fails** (FK order: `2026_09_29` refs tables created `2026_10_09`); `DB::table()` without `use DB` in 2 migrations | 🟠 | `database/migrations` | baseline via `prisma migrate diff` against live DB; never re-run these migrations |
| R-11 | `Mechanic` hard-delete guard references ptmsReports but no FK/relation exists — guard may always pass | 🟡 | `web.php` mechanics hard-delete | verify at runtime; document actual behavior |
| R-12 | Near-zero test coverage outside master-data happy path | 🟠 | `tests/` | Phase 14 regression suite is the safety net |
| R-13 | Password `min:4`, no API login throttle, tokens in localStorage (XSS), unprofessional `RoleMiddleware` error text | 🟠 | CredentialController / Api\AuthController / SPA / RoleMiddleware | tighten in Next.js (behavior change → §39) |
| R-14 | Schema typos frozen in DB (`desription`, `label_number_quty`) | 🟡 | migrations | keep column names in Prisma schema (mapped) |
| R-15 | No pagination on web master lists (`->get()` full sets) | 🟡 | closures | server-side pagination in Next.js lists is recommended but changes UX (§39) |
| R-16 | `LogUserLogin` middleware dead; `mustVerifyEmail` unenforced; `getLinesByFactory` ignores `$factoryId`; single-vs-bulk deactivate guard mismatch | 🔵 | various | document only |
| R-17 | Export hardcoded `1.15` / `8` / `28800` diverge from configured LB values | 🟠 | LB export / `generate_lb_template.php` | preserve formulas exactly (§16) |
| R-18 | LB module has **zero** `logActivity` coverage; generic-13 exports unlogged | 🟡 | web.php | decide whether to add logging (behavior change §39) |
| R-19 | Dual frontend drift (Blade vs `lems-frontend`) — SPA lacks 9+ modules and bypasses Blade role gating | 🟠 | `lems-frontend/` | §25 strangler target: one app |
| R-20 | Seeder seeds demo rows (Factory 1/2, 30 lines, LB sample) inside **migrations** (2026_09_09_*) | 🟡 | migrations | baseline protects prod data; never re-run |
| R-21 | Chart.js 4.4 + annotation plugin via CDN (offline/build fragility) | 🔵 | edit.blade.php | bundle via npm in Next.js |
| R-22 | XAMPP/Windows dev paths; MySQL-specific raw ALTER (nullable username) | 🔵 | `.env`, migrations | Prisma/MySQL target keeps compatibility |

## CONFLICT blocks (prompt §42 format)

**CONFLICT C-1 — viewer write access**
- Source A: UI/Blade + sidebar intent — viewer buttons hidden ("read-only role"); error text "Unauthorized. Viewer role is read-only."
- Source B: `routes/web.php` — inline viewer guard exists **only** in operator routes; all other master write routes + LB module + API are writable by any authenticated role/token.
- Current runtime behavior: viewer **can** create/update/deactivate/import/hard-delete most masters via direct HTTP request; LB saves work for viewers; API mutations unrestricted except users.
- Migration recommendation: enforce read-only for viewer everywhere (server-side `requireRole`).
- **Human confirmation required: YES** (changes observable behavior).

**CONFLICT C-2 — PTMS auto-calculation**
- Source A: `documentation/06_product_requirement_document.md` PTMS-06 — "Auto-calculate total TMU, BMS, and SMV".
- Source B: `PtmsReport` model + `Store/UpdatePtmsReportRequest` — 12 numerics stored **as provided**; no formula in source.
- Current runtime behavior: values are user-entered; dashboard computes `AVG(smv)` only.
- Migration recommendation: store-as-provided (parity); optionally compute later behind a flag after humans supply the official formulas (TMU sum? BMS = total_tmu × allowance? SMV = BMS × factory efficiency? — **not in source**).
- **Human confirmation required: YES.**

**CONFLICT C-3 — LB allowance/working time**
- Source A: UI + PHP accessors honor `allowance_percent` / `working_hours_per_day` (defaults 15% / 8h).
- Source B: Excel export + `generate_lb_template.php` hardcode ×1.15, ×8, 28800, header "Allowance 15 %".
- Current runtime behavior: non-default reports show correct UI numbers but export formulas assume 15%/8h (P/N columns ×1.15, R38/R41 use 28800/8).
- Migration recommendation: **preserve export formulas byte-for-byte** (parity) and recompute UI from the same `lib/calc` module; fix only after confirmation.
- **Human confirmation required: YES** (to change export behavior).

**CONFLICT C-4 — deactivate guard consistency (production lines, processes)**
- Source A: single deactivate blocked when PTMS history exists.
- Source B: bulk-deactivate skips the check (processes: history check only on single too).
- Current runtime behavior: bulk path can deactivate rows the single path refuses.
- Migration recommendation: preserve both paths as-is for parity; flag for cleanup.
- **Human confirmation required: YES** (to unify).

**CONFLICT C-5 — schema documentation vs database**
- Source A: `documentation/07_database_schema.md` (clean names).
- Source B: actual migrations (`desription` typo; `label_number_quty`).
- Current runtime behavior: code reads/writes the typo columns (`$d->desription`).
- Migration recommendation: Prisma schema maps exact existing column names (no rename).
- **Human confirmation required: only to rename** (default: keep).

**CONFLICT C-6 — password/email policy**
- Source A: Breeze defaults (min 8, verified email).
- Source B: `CredentialController` `min:4`; `MustVerifyEmail` not enforced.
- Current runtime behavior: min-4 passwords accepted; email verification unused.
- Migration recommendation: reproduce min-4 for parity; tighten later with confirmation.
- **Human confirmation required: YES** (to tighten).

## Risk detection checklist (prompt §43) — mapped

| Category | Findings |
|---|---|
| Duplicate concepts | R-6 (genders/roles/destinations free text vs tables), dual frontend R-19, `latestPtmsReport` vs `ptmsReports` |
| Unused/dead code | `LogUserLogin` middleware, `getLinesByFactory` (ignores arg), `lb-search-dropdown` markup, `layouts/navigation.blade.php`, commented `inspire`, 7 placeholder operation modules |
| Hidden logic | model `boot` events (label/report numbers), `array_search` fallback R-9, LB stale-hidden-field R-4, `strtolower` on login |
| Hardcoded values/roles | '17596', 1.15/8/28800, `WORKING_SECONDS`, seed credentials, role strings 'developer'/'admin'/'viewer' in closures/Blade |
| Insecure authorization | R-3, R-13 |
| Unsafe uploads | image-only rule but stored on public disk; no size cap found ("Not found in source." for `max:` rule — verify) |
| Secrets | `UserSeeder` credentials; `.env` APP_KEY |
| Migration/seed risks | R-10, R-20 |

# 24. Laravel → Next.js Mapping

## 24.1 Generic mapping table

| Laravel | Next.js / TypeScript target |
|---|---|
| Route (`routes/web.php` closure) | **Server Action** (mutations) or **Route Handler** `app/api/**/route.ts` (uploads, downloads, JSON search) |
| Route (`routes/api.php` resource) | Route Handler + service in `lib/services/` (keeps SPA contract during dual-run) |
| Controller (thin, 4 web) | Server Actions + `lib/services/*.ts` |
| Blade view + Alpine | React Server/Client Component page in `app/` |
| Eloquent model | Prisma model (existing MySQL introspected — §26) |
| Model accessor | pure function in `lib/calc/*` (§33) |
| Model `boot` event | service-layer code (called explicitly in create/update action) |
| Migration | `prisma migrate diff` baseline + future migration files (never re-run Laravel migrations) |
| Seeder | idempotent `prisma/seed.ts` (demo data optional; **no hardcoded passwords**) |
| Factory (faker) | test fixtures / factories in test dir |
| FormRequest validation | zod schema in `lib/validation/*` (shared client/server) |
| Middleware (auth, role) | Next.js middleware + `requireAuth`/`requireRole` helpers (§28) |
| Sanctum token | HTTP-only session cookie (§27) |
| Session (`file`) | signed HTTP-only cookie session (JWT/session table optional) |
| `logActivity()` helper | `lib/services/activity-log.ts` called from actions |
| `Hash::make` (bcrypt) | `bcryptjs`/`bcrypt` verify-compat + same hash format (§27) |
| PhpSpreadsheet export | `exceljs` (formulas, merges, hidden cols, charts) §31 |
| PhpSpreadsheet import | `exceljs`/`papaparse` (xlsx + csv) §31 |
| `Storage::disk('public')` | static mount / route handler over same directory (§32) |
| `resources/lang/{en,id}` | `lib/i18n` dictionaries + locale cookie (§30) |
| Pagination `->paginate()` | server-side pagination params + `Pagination` component |
| `redirect()->with('success'/'error')` | `redirect()` + `searchParams` flash or toast state |
| `abort(403/404)` | `notFound()` / `forbidden()` |
| `QueryException 23000` catch | try/catch on Prisma `P2002/P2003` equivalents |
| `artisan cache:clear` etc. | admin Route Handler (dev-only) |
| PHPUnit tests | Vitest + Playwright (§37) |

## 24.2 Concrete component targets (prompt §37)

| Current component | Concrete next step |
|---|---|
| `routes/web.php` (2,436 lines, closures) | split into `lib/services/{processes,operators,articles,gsd,factories,departments,lines,mechanics,simple-master,line-balancing,credentials,logs,system}.ts` + Server Actions per mutation |
| `routes/api.php` (13 resources + auth + dashboard) | `app/api/v1/**/route.ts` thin handlers delegating to the **same** services (dual-run parity) |
| `CredentialController` | Server Actions `createUser/updateUser/deleteUser` + zod (`min:4` preserved) |
| `LineBalancingController` (8 methods) | `lib/services/line-balancing.ts` + Server Actions + `lib/export/line-balancing-workbook.ts` |
| `Api/DashboardController` | `app/api/v1/dashboard/stats/route.ts` → `lib/services/dashboard.ts` |
| `Api/*Controller` (BaseController helpers) | `lib/services/crud-factory.ts` (whitelisted sort/with) |
| `app/Models/*` (34) | `prisma/schema.prisma` models + `lib/calc/*` for accessors |
| `applyExcelFormatting()` / `logActivity()` helpers | `lib/export/excel.ts` / `lib/services/activity-log.ts` |
| `RoleMiddleware`, `SetLocale`, `Authenticate` | `middleware.ts` + `lib/auth/*` |
| `UserSeeder` | `prisma/seed.ts` with **env-driven** admin bootstrap |
| `generate_lb_template.php` | `scripts/generate-lb-template.ts` (byte-equivalent output) |
| `lems-frontend/` | becomes the new `app/` UI kit (§25) |
| Blade `layouts/app.blade.php` | `AppShell` component (sidebar parity incl. role-gating display) |
| LB editor inline JS + Chart.js | `Stopwatch`, `LbEditor` client components + recharts/chart.js npm bundle |

# 25. Next.js Target Architecture

**Decision: evolve `lems-frontend/`** into the single full-stack app (App Router + Server Actions), rather than a fresh app — it already contains the UI kit, hooks, and page skeletons matching the LEAN ENTERPRISE look. Its **data layer (axios + localStorage token) is replaced**, not reused. Fresh-start remains an option — **Human confirmation (§39)**.

```text
lems-frontend/  (evolved)
├─ app/
│  ├─ (auth)/login, register, forgot-password, reset-password, confirm-password
│  ├─ (app)/
│  │  ├─ home                      # 6 KPI cards (server-computed)
│  │  ├─ master-data/
│  │  │  ├─ processes              # list + version/GSD modal
│  │  │  ├─ operators  + [id]      # list + profile (PTMS history, computed fields)
│  │  │  ├─ articles, gsd-elements, factories, departments,
│  │  │  ├─ destinations, production-lines, mechanics
│  │  │  └─ [simple-master]        # 13 shared pages via dynamic route + config
│  │  ├─ operations/
│  │  │  ├─ page.tsx               # hub + 7 placeholder modules (parity)
│  │  │  └─ line-balancing/  [id]  # list + editor (Stopwatch, KPIs, chart)
│  │  ├─ credentials               # user management (dev+admin)
│  │  ├─ hard-delete               # developer center (22 masters)
│  │  ├─ system/{login-logs, activity-logs, clear-cache, speed-test}
│  │  ├─ profile
│  │  └─ developer, admin, viewer  # role pages (parity)
│  └─ api/v1/**/route.ts           # JSON/search/uploads/downloads + dual-run API
├─ lib/
│  ├─ auth/ (session, requireAuth, requireRole, bcrypt verify)
│  ├─ db/ (prisma client singleton)
│  ├─ calc/ (operator, article, ptms, line-balancing, dashboard — §33)
│  ├─ validation/ (zod schemas mirroring FormRequests)
│  ├─ services/ (one per domain, single source of business rules)
│  ├─ export/ (excel.ts generic + line-balancing-workbook.ts)
│  ├─ import/ (generic + per-entity resolvers)
│  └─ i18n/ (en, id dictionaries; locale cookie)
├─ components/ (ui kit from lems-frontend + §21 components)
├─ prisma/ (schema.prisma introspected, migrations/, seed.ts)
└─ tests/ (vitest unit + playwright e2e — §37)
```

**Strangler flow (per prompt "keep Laravel available until regression passes"):** Laravel stays at :8000 serving the existing SPA/Blade; Next.js is built and validated feature-by-feature (Phases 2–14); cutover in Phase 15 (Coolify). The Next.js `api/v1` handlers may proxy to Laravel during dual-run if needed for parity checks.

# 26. ORM Strategy — Prisma vs Drizzle (prompt §38)

| Criterion | Prisma | Drizzle | Winner |
|---|---|---|---|
| Existing-schema compatibility | `prisma db pull` introspects live MySQL incl. FKs, enums, defaults → exact match | `drizzle-kit introspect` less mature (MySQL enum/FK fidelity varies) | **Prisma** |
| Relations / nested writes | `include`/`select` graphs match eager-loads in `operators/show`, process versions + pivot | manual joins/query composition | **Prisma** |
| Transactions | interactive transactions (`saveRows` full-replace) | transactions supported | tie |
| Raw SQL | `$queryRaw` for speed-test/count parity | template SQL | tie |
| Migrations | `migrate diff` → **baseline without destroying data**; team review workflow | generate SQL diffs | **Prisma** |
| Type safety | generated client types | TS-first, excellent | Drizzle (slight) |
| Seeds | `prisma/seed.ts` | scripts | tie |
| Deployment (Coolify/Docker) | binary engine + `prisma generate` at build | pure TS, lighter image | Drizzle (slight) |
| Team familiarity / docs | largest ecosystem | growing | **Prisma** |

**Recommendation: Prisma.** Baseline procedure (§36 Phase 3): `prisma db pull` against the **existing MySQL** → `prisma migrate diff --from-empty --to-schema-datamodel` to produce baseline migration marked applied (`migrate resolve --applied`) → **no data touched**. Column typos (`desription`, `label_number_quty`) are mapped as-is (`@map`) — no renames (CONFLICT C-5).

# 27. Authentication Migration

| Laravel behavior | Next.js replacement | Parity notes |
|---|---|---|
| `POST /login` (username **or** email, lowercased) | `app/(auth)/login` → Server Action `login()` | zod schema mirrors `LoginRequest`; keep `strtolower` matching (or rely on ci collation — document) |
| `Auth::attempt` + bcrypt `$2y$` hashes | verify with `bcryptjs.compare` (**`$2y$` compat must be verified** — bcryptjs historically expects `$2a$`; normalize prefix on read if needed) | **No password re-hash on migrate** — existing MySQL `users.password` stays authoritative |
| Session cookie (encrypted, file) | signed **HTTP-only** cookie session (JWT or iron-session) | SameSite=Lax; secure in prod |
| Remember me (`remember_token`) | long-lived cookie variant | parity optional |
| Login throttle (5 attempts web) | `RateLimiter` equivalent in-memory/DB (5 attempts) | preserve; API throttle is a gap (§39) |
| `LoginLog::create` on login/logout | same writes from auth callbacks | identical fields |
| Sanctum bearer tokens (SPA localStorage) | **HTTP-only cookies**; keep `/api/v1/login` issuing tokens **only** for legacy SPA during dual-run | localStorage token removed at cutover (XSS fix R-13) |
| Password reset (Breeze mail) | token table (`password_reset_tokens`) + mail provider | preserve table |
| Registration page | preserve (parity) or disable per §39 | seed roles unchanged |
| `MustVerifyEmail` unenforced | do not enforce (parity) | note in limitations |
| `UserSeeder` credentials | **env-driven bootstrap** (`ADMIN_USERNAME/ADMIN_PASSWORD`) + rotate all known passwords | never ship fixed passwords |
| `Hash::make` on credential create/update | `bcrypt.hash` (cost 10 to match) | password `min:4` rule preserved (zod `min(4)`) |
| API `PUT /me/password` | Route Handler `app/api/v1/me/password` | same rules |

# 28. Authorization Migration

- `middleware.ts`: gate `/home`, `/master-data/**`, `/operations/**`, `/profile` behind session; role gates via `requireRole('developer')` etc. in **server-side** code paths (Server Actions and Route Handlers), mirroring §4/§7 matrices.
- Role values stay the strings `developer`/`admin`/`viewer` (from `roles.role_name`) — no renaming.
- **Decision (Human Confirmation §39):** default = enforce intended policy — viewer read-only everywhere, hard delete developer-only, API mutations role-checked. If strict parity is demanded instead, the gaps in §7 are reproduced verbatim (documented in UI as "legacy behavior").
- Blade visibility rules (`@if` sidebar/buttons) become conditional rendering — but are **never** the only guard (prompt §11).
- Single helper `requireRole(...)`/`requireAuth()` used by every action — no ad-hoc checks.

# 29. API and Server Action Mapping (prompt §41)

| Current action | Route | Next.js target |
|---|---|---|
| Login/logout/me/change-password | `api.php` | Route Handlers `app/api/v1/{login,logout,me,me/password}` (dual-run) + Server Actions for the app itself |
| Dashboard stats (16 counts + AVG smv) | `GET /api/dashboard/stats` | Route Handler → `lib/services/dashboard.ts` |
| PTMS status update | `PUT /ptms-reports/{id}/status` | Server Action `updatePtmsStatus` + Route Handler twin |
| 13 apiResources (index/store/show/update/destroy) | `api.php` | Route Handlers `app/api/v1/**` delegating to shared services; `destroy` keeps hard-delete semantics **but with guards** (§28 decision) |
| Master list pages render (GET + query params) | web closures | **Server Components** reading via services (RSC + searchParams) |
| Create/update/deactivate/bulk/hard-delete/import | web closures | **Server Actions** with zod validation |
| Export endpoints | web closures | **Route Handlers** streaming xlsx (exceljs) |
| `/search` JSON autocomplete | web closures | Route Handlers `.../search?q=` (limit 10, active only) |
| LB saveRows | web closure | Server Action (transaction) |
| LB export | web closure | Route Handler (workbook) |
| clear-cache / speed-test | web closures | dev-only Route Handlers (Artisan equivalents) |
| Log clear endpoints | web closures | Server Actions (developer) |
| Language switch | `POST /language/{locale}` | Server Action + locale cookie |
| Photo upload | multipart in closures | Server Action `formData` (or Route Handler) writing same `photo_path` scheme |

**File organization (prompt §41):** mutations → Server Actions; files/exports/imports/JSON/autocomplete → Route Handlers; **all business rules → `lib/services`** shared by both; pages → Server Components; interactive editors (LB, modals, stopwatch) → `"use client"` components.

# 30. UI Component Mapping

| Blade / SPA piece | Next.js component | Notes |
|---|---|---|
| `layouts/app.blade.php` sidebar + header | `AppShell` | collapsible, dark mode, locale switcher EN/ID (server cookie), role-gated menu labels |
| master tables | `DataTable` (+ bulk mode) | sortable headers preserve whitelists |
| search/filter forms | `SearchBar`, `FilterBar` | URL searchParams driven (shareable URLs preserved) |
| create/edit Alpine modals | `Modal` + react-hook-form + zod | same field order/labels; inline validation errors |
| `confirm()` dialogs | `ConfirmDialog` | same wording preserved |
| status pills | `StatusBadge` | active/inactive, draft/final/archived |
| home stat cards / LB KPI cards | `KpiCard` | values from `lib/calc` |
| LB editor table | `LbEditor` (client) | dynamic rows, renumbering, employee `AutocompleteInput` (250 ms) |
| inline stopwatch | `Stopwatch` (client) | 10 ms tick, `mm:ss.cc`, 5-lap auto-stop → CT1–CT5 |
| Chart.js Yamazumi/grouped bars | chart.js npm or recharts | takt annotation line preserved |
| file inputs + previews | `FileUpload` | `image` validation client+server |
| flash messages | `Flash`/toast from action results | success/error strings preserved verbatim (incl. ID translations) |
| `resources/lang/{en,id}` strings | `lib/i18n` dictionaries | same keys; EN/ID parity required |
| pagination links | `Pagination` | 15 (LB) / 50 (logs) page sizes preserved |

# 31. Import / Export Migration (prompt §22)

- Library: **exceljs** (formulas `=AVERAGE(H11:L11)`, merges, column hidden flags `F`/`S`, chart embedding via exceljs chart support or xlsx-populate fallback; CSV via papaparse).
- Import: same header normalization (lowercase, `_`→space), same upsert keys and transformations (§15), same count-only feedback (plus optional row-error file **only after confirmation**).
- Export: same headers/order, `applyExcelFormatting` equivalents (bold header, freeze pane, column widths), same filename patterns (`LineBalancing_<name>_<timestamp>.xlsx`), `deleteFileAfterSend` semantics (stream + no persistence).
- LB workbook: all formula strings preserved **literally** (including `×1.15`, `28800`, "Allowance 15 %", helper row 10 refs, O32=MAX/Q32=MIN, 43 merges, hidden cols, sheet name "LB Livlig", Yamazumi chart data/position) — validated by opening both files in Excel and diffing evaluated values (§37).
- `generate_lb_template.php` reproduced by `scripts/generate-lb-template.ts` with identical sample rows/formulas/typo label.

# 32. File Storage Migration (prompt §24)

| Step | Mapping |
|---|---|
| Old path | `storage/app/public/photo/…` (Laravel `public` disk) |
| New path | **same directory**, served by Next.js (route handler `GET /api/v1/files/**` or public mount / Coolify volume) |
| DB reference | `photo_path` strings unchanged → zero data migration |
| Access mechanism | Blade `asset('storage/…')` → app URL `/api/v1/files/{photo_path}` (or `/media/{photo_path}`) |
| Upload flow | Server Action validates (`image`, size cap — preserve current rules; size cap absent in source), writes same relative path scheme, updates `photo_path` |
| Delete flow | article hard delete unlinks file (parity); operator photo replace behavior verified against source |
| Production | Coolify persistent volume for the media dir; documented in Dockerfile/compose (§35) |

# 33. Calculation Migration (prompt §35)

Central module `lib/calc/` — the **only** place formulas exist; consumed by Server Components, Server Actions, LB editor client (via server-injected constants), and exporters.

| Laravel source | Exact formula (preserved) | Next.js function |
|---|---|---|
| `Operator::getWorkingAgeAttribute` | dob→start diff formatted `"{y} Yr {m} Mth {d} Day"`; null guard | `workingAge(dob, start)` |
| `Operator::getAgeAttribute` | dob→now diff, same format | `age(dob, now?)` |
| `Operator::getAgeYearAttribute` | diff years int | `ageYears(dob)` |
| `Operator::getYearsOfServiceAttribute` | `(dob+59y+20d) − today`, `"0 Yr 0 Mth 0 Day"` past retirement | `yearsOfService(dob, today?)` |
| `Article` boot saving | `label_number_quty = label_number + '17596'` | `labelNumberQuty(label)` (suffix constant `'17596'`) |
| `PtmsReport` boot creating | `PTMS-YYYY-%04d` = year-count+1 | `nextReportNumber(tx, now)` — **atomic** (SELECT … FOR UPDATE / unique-retry) to fix race R-5 while keeping format identical |
| `LineBalancingReport` accessors | `1 + allowance/100`; `hours × 3600` | `allowanceMultiplier`, `workingSecondsPerDay` |
| `LineBalancingReportRow` accessors | filter ct>0 · mean · `round(mean×mult,2)` · `round(mean,2)` · `round(3600/avgAllw,2)` (0 guard) · `round(avg/takt,2)` · `round(output×hours,0)` | `ctObservations`, `avgCycleTime`, `avgCycleTimeAllowance`, `avgCtPerProcess`, `outputPerHour`, `outputProcessPerHour`, `taktTime`, `requestOperator`, `potentialOutputPerProcess` |
| LB summary (UI + workbook) | Σ CT · Σ manpower · std time · 28800 · `ROUND(28800/ΣCT,0)` · ×manpower · ÷8 · `3600/MAX(CT+Allow)` · PPH = target/Σmanpower | `summaryKpis(rows, header)` (+ `exportKpis()` preserving hardcoded 28800/8) |
| `Api/DashboardController` | SQL `AVG(smv)` etc. | `dashboardStats(tx)` |
| TMU/BMS/SMV | **stored as provided** (CONFLICT C-2) | `ptms.ts` passthrough + TODO hooks for confirmed formulas |

Every function gets unit tests (§37) with fixtures copied from current runtime outputs (e.g. LB seeder row values → expected KPIs) to guarantee `Laravel result == Next.js result`.

# 34. Environment Variable Mapping

| Laravel (`.env`) | Next.js / Prisma | Notes |
|---|---|---|
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `DATABASE_URL="mysql://user:pass@internal-host:3306/kinglean2"` | single URL for Prisma; never `NEXT_PUBLIC_` |
| `DB_CONNECTION=mysql` | implied by `DATABASE_URL` | MySQL 8 preserved |
| `APP_KEY` | `AUTH_SECRET` (session/JWT signing) | generate new; rotate |
| `APP_URL`, `ASSET_URL` | `NEXT_PUBLIC_APP_URL` | public-safe |
| `APP_ENV`, `APP_DEBUG` | `NODE_ENV`, `NEXT_PUBLIC_APP_ENV` | |
| `FILESYSTEM_DISK=public`, `FILESYSTEM_*` | `MEDIA_DIR` (volume path) | §32 |
| `SESSION_DRIVER=file`, `SESSION_LIFETIME` | cookie session config (`SESSION_TTL`) | |
| `CACHE_DRIVER=file` | in-memory / Redis optional | |
| `QUEUE_CONNECTION=sync` | n/a (no workers — §19) | |
| `MAIL_*` | `EMAIL_SERVER`/`EMAIL_FROM` (password reset) | |
| `SANCTUM_STATEFUL_DOMAINS`, `FRONTEND_URL` (if set) | `CORS_ORIGINS` / cookie `SameSite` config | dual-run only |
| `NEXT_PUBLIC_API_URL` (SPA) | replaced by same-origin `/api/v1` | remove external base URL at cutover |
| — | `ADMIN_BOOTSTRAP_USER`, `ADMIN_BOOTSTRAP_PASSWORD` | env-driven seeding (replaces `UserSeeder` hardcodes) |

Rules: **never commit secrets**; **never expose server secrets via `NEXT_PUBLIC_*`** (prompt §38); `.env.example` lists keys only.

# 35. Local Development Workflow

Replaces `php artisan serve` (`kinglean php artisan serve host.bat` retired):

```bash
npm install
npx prisma generate        # after schema changes
npm run dev                # http://localhost:3000
npm run build && npm run start   # production-like local test
```

Tests: `npm run test` (vitest), `npm run test:e2e` (playwright). MySQL: existing local XAMPP MySQL kept (same data) — point `DATABASE_URL` at it read/write, never `migrate reset`. Exact scripts to be written into the final `package.json` (Phase 2).

# 36. Docker / Coolify Deployment

| Item | Value |
|---|---|
| Node.js | 20 LTS (Alpine base) |
| Package manager | npm (lockfile committed) |
| Build command | `npm ci && npx prisma generate && npm run build` |
| Start command | `npm start` (standalone output) |
| Internal port | 3000 |
| Healthcheck | `GET /api/health` (new Route Handler: DB `SELECT 1` + app ok) — `/` used as fallback |
| Dockerfile | multi-stage (deps → build → runner), non-root user, `output: 'standalone'` |
| Compose | app service + **no MySQL service if MySQL is a separate Coolify resource** |
| Network | shared private Docker network with the MySQL resource |
| DB hostname | Coolify internal hostname (e.g. `mysql` / resource container name); **do not expose MySQL publicly** |
| Media | persistent volume mounted at `MEDIA_DIR` (§32) |
| Env vars | §34 via Coolify env (no secrets in image/CI logs) |
| Migrations | `prisma migrate deploy` at boot (baseline already applied) — **never `migrate reset`/`db push --force-reset`** |
| Logging | stdout/stderr (Coolify collection) |
| Laravel during dual-run | keep Laravel container running until Phase 14 regression passes (§37 Git safety) |

# 37. Migration Phases 0–15 (prompt §44)

| Phase | Scope | Gate (exit criteria) |
|---|---|---|
| **0 — Backup** | mysqldump of existing DB, `storage/app` file backup, **restore verification** (restore into scratch DB and compare row counts) | restore proven; backups stored off-box |
| **1 — Audit** | this document (`auditkinglean2.md`) — source + DB audit | acceptance checklist §39 ticked |
| **2 — Next.js foundation** | evolve `lems-frontend/`: App Router, TS strict, Tailwind, Prisma client, env config, `lib/` skeleton | `npm run build` green; health endpoint up |
| **3 — Existing MySQL connection** | `prisma db pull` → verify 38 tables/relations vs §8; baseline migration `--applied`; **no destructive changes** | introspected schema diff-clean vs §8.8–8.9 |
| **4 — Authentication** | login (username/email), bcrypt `$2y$` verify, session cookie, login throttle, login/logout logs, roles seed, `requireRole` | login/logout/role tests pass; old hashes work |
| **5 — Shared UI** | `AppShell`, sidebar parity, `DataTable`, `SearchBar`, `FilterBar`, `Modal`, `ConfirmDialog`, `StatusBadge`, `Pagination`, `KpiCard`, i18n EN/ID, dark mode | visual parity checklist vs Blade screenshots |
| **6 — Data Masters** | migrate **one at a time** (factories → departments → divisions/sections → production-lines → destinations → 10 simple masters → mechanics → gsd-categories/mtm/sewing), each incl. CRUD, search/filter/sort, import, export, soft/hard delete, logging | per-master parity tests (§38) |
| **7 — Employees** | operators CRUD, photo upload, dependent dropdowns, computed fields (`lib/calc/operator`), profile page + PTMS history, import/export, guards (viewer/developer) | LeanEnterpriseMasterDataTest parity |
| **8 — Articles** | articles CRUD, photo, `label_number_quty` auto-gen, import/export, history guards | test parity incl. quty uniqueness |
| **9 — Processes / GSD** | processes + versions + GSD pivot sync, gsd-elements, import/export, PTMS create/update/status + `report_number` atomic gen, SPA PTMS screens | pivot-sync test parity; numbering format identical |
| **10 — Line Balancing** | list + editor (stopwatch, dynamic rows, autocomplete, live recalc, chart), saveRows transaction, KPIs, export workbook (formulas + chart byte-parity), deactivate/bulk | **all Calculation Matrix rows `Laravel result == Next.js result`**; Excel diff-equal |
| **11 — Remaining operational modules** | 7 placeholder modules parity, employee-profile redirect, `getLinesByFactory` behavior | pages render |
| **12 — Reports / exports** | all exports incl. operators 14-col, generic, production-lines all-status quirk | export diff tests |
| **13 — Logs** | activity/login logs + clear endpoints + `logActivity` parity (incl. documented gaps R-18 decision) | log entries match Laravel format |
| **14 — Full regression** | entire §38 matrix + side-by-side Laravel vs Next.js runs on same DB copy | 34-item checklist green; zero data drift |
| **15 — Coolify production** | Docker/Compose (§36), env, media volume, healthcheck, DNS cutover, keep Laravel container for rollback window | prod smoke tests; rollback plan tested |

"Do not attempt a giant one-shot rewrite" — each phase commits independently. **Git safety (prompt §45):** keep the Laravel app fully available until Phase 14 passes; branch-per-phase; no destructive DB commands in any script; rollback = point traffic back to Laravel.

# 38. Testing & Regression Plan

Matrix `Laravel behavior → Next.js implementation → test → expected result` (prompt §41). Coverage per area:

| Area | Test type | Key assertions |
|---|---|---|
| Login/logout | e2e + unit | username & email paths, lowercase matching, throttle 5, `login_logs` rows |
| Roles/permissions | e2e matrix | every §7 row (incl. chosen C-1 resolution): viewer read-only, dev-only hard delete |
| CRUD (all entities) | e2e | create defaults, required/optional rules, transformations (`strtoupper(role)`, label suffix), immutables |
| Validation | unit (zod parity) | min/max/unique/exists rules (§11.1) with same error keys |
| Search/filters/sort | e2e | same result sets as Laravel for fixture data; whitelists respected |
| Pagination | e2e | 15 (LB) / 50 (logs) preserved |
| Import | integration | upsert keys, auto-label `LBL-`+random, 0-fallbacks, counts, header normalization |
| Export | integration + Excel diff | headers, formulas (`M=AVERAGE(H:L)`, `N=M*1.15`, …), merges, hidden cols, chart present, filenames |
| Soft delete / status | e2e | inactive filtering, show-inactive, bulk behavior incl. guard mismatches (C-4 parity) |
| Hard delete | e2e | guard matrix (skip messages), FK-violation message "still referenced by other data.", dev-only |
| Relationships | integration | PTMS history graph eager-loading on profile; pivot sync |
| Calculations | unit (all §33 functions) + property cases (0/1/5 CTs, retirement past/now) | `Laravel result == Next.js result` fixtures from current runtime |
| Images | e2e | upload, preview, validation reject, delete-on-article-delete |
| Reports (PTMS, LB) | e2e | numbering `PTMS-YYYY-%04d`, status transitions, KPI values |
| Logs | integration | `logActivity` call sites preserved (incl. documented gaps) |
| i18n | e2e | EN/ID switch parity |

Critical paths require literal `Laravel result == Next.js result` on the same DB fixtures (run Laravel suite + Next.js suite against copies of the Phase-0 backup). Converted tests from §22 run first in Phase 14.

# 39. Acceptance Criteria (prompt §47 — final checklist)

- [x] Every Laravel route was audited. → §4 (133 web defs + 8 + 14 apiResource)
- [x] Every page was audited. → §5 (Blade + SPA)
- [x] Every CRUD operation was audited. → §11
- [x] Every database table was audited. → §8 (38 tables)
- [x] Relationships were documented. → §8.8, §9 (ER diagram)
- [x] Authentication was documented. → §6
- [x] Authorization was documented. → §7 (+ Permission Matrix)
- [x] Validation was documented. → §11.1, §12
- [x] Important calculations were documented. → §13 (+ Calculation Matrix)
- [x] Search/filter/sort/pagination were documented. → §14
- [x] Imports were documented. → §15 (+ Import Matrix)
- [x] Exports were documented. → §16 (+ Export Matrix)
- [x] File/image handling was documented. → §17
- [x] Soft-delete behavior was documented. → §11.3, §12
- [x] Hard-delete behavior was documented. → §11.3, §12 (guard table)
- [x] Logs/auditing were documented. → §18
- [x] Frontend JavaScript behavior was documented. → §20
- [x] Tests were reviewed. → §22
- [x] Existing MySQL preservation strategy was documented. → §26, §37 Phase 0/3 (backup, introspect, baseline, no destruction)
- [x] Laravel → Next.js mappings exist. → §24
- [x] Next.js App Router architecture is defined. → §25
- [x] TypeScript architecture is defined. → §25 (`lib/` layering), §29
- [x] ORM strategy is defined. → §26 (Prisma, with Drizzle comparison)
- [x] Authentication migration is defined. → §27
- [x] Authorization migration is defined. → §28
- [x] CRUD migration is defined. → §29 (Server Actions / Route Handlers table)
- [x] Import/export migration is defined. → §31
- [x] File-storage migration is defined. → §32
- [x] Calculation migration is defined. → §33
- [x] Environment variables are mapped. → §34
- [x] Docker/Coolify deployment is defined. → §36
- [x] Testing/regression strategy is defined. → §38
- [x] Human-confirmation items are clearly listed. → §40
- [x] No destructive database operation is part of the default migration. → enforced: backup + introspect + baseline only; `migrate:fresh`/`db:wipe`/`migrate reset` forbidden in all phases

All 34 items satisfied by this document. Terminology preserved per prompt §34 (GSD, SMV, PPH, Takt Time, Cycle Time, Line Balancing, Operational Breakdown, Skill Grading, Production Role, Status PKWTT, Educational Level, Factory, Division, Department, Section, Line, Article, Process, Employee, Machine Type, TMU, BMS, Yamazumi, Kaizen, TPM, VSM).

# 40. Items Requiring Human Confirmation

| # | Item | Why it needs a human | Default (parity-safe) |
|---|---|---|---|
| H-1 | **Viewer write gap** (CONFLICT C-1) — close backend gaps (viewer read-only, dev-only hard delete, role-checked API) or reproduce legacy behavior? | security fix changes observable behavior | enforce intended policy |
| H-2 | **PTMS auto-calculation** (CONFLICT C-2) — supply official TMU/BMS/SMV formulas, or keep store-as-provided? | formulas are not in the source | store as provided |
| H-3 | **LB export hardcodes** (CONFLICT C-3) — fix export to use configured allowance/hours, or preserve ×1.15/28800? | changes Excel output | preserve exactly |
| H-4 | **Deactivate guard mismatch** (CONFLICT C-4) — unify single vs bulk checks? | behavior change | preserve |
| H-5 | **Typo columns** (`desription`, `label_number_quty`) — keep or rename? | rename = data migration | keep (map as-is) |
| H-6 | **Hardcoded '17596'** label suffix — confirm business meaning; keep constant or make configurable? | unknown business rule | keep literal |
| H-7 | **Credentials** — rotate all `UserSeeder` passwords; confirm secure bootstrap process; disable/keep `register` page? | security + process | env bootstrap; keep register for parity |
| H-8 | **Password policy** — keep `min:4`/unthrottled API, or tighten (min 8, throttle, no localStorage tokens)? | security vs parity | tighten after sign-off |
| H-9 | **lems-frontend evolve vs fresh app** | architecture choice | evolve `lems-frontend/` |
| H-10 | **bcrypt `$2y$` hash compatibility** — confirm all existing logins work after switch to bcryptjs (`$2a$` normalization) | must verify against real hash sample | verify in Phase 4 gate |
| H-11 | **Pagination on master lists** — add server pagination (current lists unbounded) or preserve? | UX change | paginate (confirm) |
| H-12 | **Logging gaps** (R-18) — add `logActivity` to LB/generic exports or preserve gaps? | audit coverage | preserve, then add |
| H-13 | **Mechanic hard-delete guard** (R-11) — verify intended PTMS dependency at runtime | source ambiguity | document actual behavior |

# 41. Known Limitations

1. **TMU/BMS/SMV computation** is undefined in source (CONFLICT C-2) — the migration stores client values only.
2. Some runtime behaviors (exact `store('photo','public')` path strings, upload size caps, operator photo-replace cleanup, `Mechanic` PTMS guard effectiveness) are flagged "**verify at runtime**" items — confirm with the live app during Phase 1/6.
3. Fresh Laravel `migrate` cannot reproduce the schema (FK order bug R-10) — the authoritative schema is the **live MySQL database**, which this audit introspects indirectly through migrations; Phase 3 `prisma db pull` is the final arbiter (run diff and reconcile with §8 before Phase 4).
4. Exact xlsx byte-identity for exports is not achievable across libraries; equivalence is defined as **equal evaluated values, formulas, merges, hidden columns, and chart data** verified in Excel.
5. Placeholder operation modules (cycle-time, breakdown, kaizen, skills, tpm, materials, vsm) have **no implementable logic** in source — Next.js reproduces the placeholder pages only.
6. `average_smv` and other dashboard aggregates beyond `AVG(smv)`/counts follow the same "stored as provided" rule; rounding parity verified via fixtures.
7. Localization: EN/ID strings must be copied from `resources/lang` as-is; untranslated keys stay as-is (no rewording).
8. Performance characteristics (unbounded lists, N+1 eager loads, dashboard 16 counts) are preserved where behavior-visible; optimization is out of scope until after Phase 14.
9. This audit reflects the repository at the time of writing; any code changes after Phase 1 require re-running the affected audit sections.

---

**Document status:** complete per prompt §47 (34/34). Audit first. Document second. Implement third — implementation must proceed through Phases 0–15 with no destructive database operations and exact behavioral preservation, treating `auditkinglean2.md` as the migration blueprint.
