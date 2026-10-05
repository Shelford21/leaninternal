# 🔍 Kinglean (LIMS) — Full Codebase Audit Report

> **Date:** 2026-09-03  
> **Project:** Lean & IE Management System (LIMS)  
> **Framework:** Laravel 10.x | PHP ^8.1  
> **Auditor:** GitHub Copilot  

---

## 📊 Project Overview

| Item | Detail |
|------|--------|
| **Framework** | Laravel 10.x |
| **PHP** | ^8.1 |
| **Auth** | Laravel Breeze (modified for username-based login) |
| **API Auth** | Laravel Sanctum |
| **Frontend** | Tailwind CSS 3.x + Alpine.js 3.x + Vite 4.x |
| **Database** | MySQL (`lean_ie`) |
| **Roles** | developer, admin, viewer (custom `RoleMiddleware`) |
| **Tables** | ~18 tables (users, roles, factories, departments, production_lines, articles, operators, gsd_categories, gsd_elements, mtm_elements, sewing_factors, sewing_stop_factors, processes, process_versions, ptms_reports, etc.) |

---

## 🔴 CRITICAL — Security Issues

### 1. `.env` — `APP_DEBUG=true` + Exposed APP_KEY

- `APP_DEBUG=true` exposes stack traces, environment variables, and database credentials to users on error.
- The `APP_KEY` is committed and visible — if this repo is ever public, anyone can decrypt sessions/cookies.
- **Fix:** Set `APP_DEBUG=false` in production, rotate the key with `php artisan key:generate`.

### 2. `.env` — Empty Database Password

- `DB_PASSWORD=` is blank — the MySQL root user has no password.
- **Fix:** Set a strong password for the MySQL user.

### 3. `database/seeders/UserSeeder.php` — Plaintext Passwords in Source

- Seeder contains visible plaintext passwords for all seeded users.
- If this code is committed to version control, passwords are exposed in git history forever.
- **Fix:** Use `Hash::make()` in seeders, or better yet, use `.env` variables for seed passwords.

### 4. `config/cors.php` — Allows ALL Origins (`'*'`)

- Any website on the internet can make cross-origin requests to your API.
- **Fix:** Restrict to your actual domain(s):
  ```php
  'allowed_origins' => ['https://yourdomain.com'],
  ```

### 5. `config/sanctum.php` — Tokens Never Expire (`'expiration' => null`)

- Personal access tokens live forever once issued.
- **Fix:** Set a reasonable expiration, e.g., `'expiration' => 1440` (24 hours).

### 6. `RegisteredUserController.php` — Validates Non-existent `email` Field

- Registration validates `email` but the `User` model and `users` table have **no `email` column**.
- This will cause a database error on registration.
- **Fix:** Change to validate `username` instead, or add `email` to the users table if needed.

### 7. Profile Update Forms Reference `email` — Field Doesn't Exist

- `resources/views/profile/partials/update-profile-information-form.blade.php` has an email input.
- `ProfileUpdateRequest` likely validates email.
- The `users` table has no `email` column — this will crash.
- **Fix:** Replace email field with `username`/`employee_number`, or add `email` column to DB.

### 8. Password Reset Flow Uses Email — But Users Have No Email

- `forgot-password.blade.php`, `reset-passw ord.blade.php` all require email.
- Without an `email` column, password reset is completely broken.
- **Fix:** Either add email to users table, or implement an alternative reset mechanism.

---

## 🟠 HIGH — Bugs & Broken Functionality

### 9. `app/Models/Role.php` — Wrong `$fillable` Array (Copy-Paste Bug)

```php
// CURRENT (WRONG):
protected $fillable = ['name', 'username', 'employee_number', 'password', 'role_id'];

// SHOULD BE:
protected $fillable = ['role_name', 'description'];
```

- This is clearly copied from `User.php` and never updated.
- Mass assignment protection is broken for the Role model.

### 10. `database/migrations/..._create_departments_table.php` — Column Typo

- Column is named `desription` instead of `description`.
- Any code referencing `description` on departments will fail.
- **Fix:** Create a new migration to rename the column:
  ```php
  $table->renameColumn('desription', 'description');
  ```

### 11. `database/factories/UserFactory.php` — References Non-existent `email` Field

- The factory defines `email` and `email_verified_at` but the users table has neither.
- `User::factory()->create()` will fail.
- **Fix:** Update factory to use `username` and `employee_number` instead.

### 12. `tests/Feature/ProfileTest.php` — Tests Reference `email`

- Tests assert `$user->email` which doesn't exist.
- All profile tests will fail.
- **Fix:** Update tests to match actual schema.

### 13. `RoleMiddleware.php` — Unprofessional Error Message

```php
abort(403, 'aowkwk ngakak');  // Indonesian slang: "LOL laughing"
```

- **Fix:** Replace with professional message:
  ```php
  abort(403, 'You do not have permission to access this resource.');
  ```

### 14. `resources/views/layouts/navigation.blade.php` — Orphaned File

- This was the old Breeze top navigation bar.
- After the sidebar redesign in `app.blade.php`, it's no longer `@include()`d anywhere.
- **Fix:** Delete the file to avoid confusion.

---

## 🟡 MEDIUM — Code Quality Issues

### 15. No Custom Tests

- Only default Breeze tests exist (`ExampleTest`, `ProfileTest`, `Auth/*`).
- `ProfileTest` will fail due to email references.
- Zero tests for: `CredentialController`, `RoleMiddleware`, factories/departments CRUD, role-based access.
- **Recommendation:** Write feature tests for all CRUD operations and role-based access.

### 16. No Form Request Validation for Most Controllers

- `CredentialController` uses inline `$request->validate()` instead of dedicated FormRequest classes.
- **Recommendation:** Create `StoreCredentialRequest` and `UpdateCredentialRequest` for cleaner code.

### 17. No API Resources/Transformers

- API routes return raw models/collections.
- **Recommendation:** Use `JsonResource` classes to control API output shape.

### 18. No Policies Defined

- `AuthServiceProvider` has no policies registered.
- Authorization is only done via `RoleMiddleware` at the route level.
- **Recommendation:** Define policies for `User`, `Factory`, `Department` models for finer-grained control.

### 19. Seeder Typos

- `GsdCategorySeeder.php`: "Triming" should be "Trimming".
- Mixed Indonesian/English descriptions in seeders (inconsistent).

### 20. `config/session.php` — Verify Encryption Setting

- Session encryption should be enabled for sensitive data.
- **Check:** Ensure `'encrypt' => true` if storing sensitive session data.

---

## 🔵 LOW — Architecture & Best Practices

### 21. Dashboard Pages Are Placeholders

- `dashboard.blade.php`, `admin.blade.php`, `developer.blade.php`, `viewer.blade.php` all just show "Hi [Role]".
- No actual data, charts, or KPIs.

### 22. Broadcasting Configured But Unused

- `BroadcastServiceProvider` is registered, Pusher config exists.
- No events or channels are actually used.
- **Recommendation:** Either implement real-time features or remove broadcasting config.

### 23. Queue Is Sync Only

- `QUEUE_CONNECTION=sync` — all jobs run synchronously.
- Fine for development, but will block web requests in production.
- **Recommendation:** Switch to `database` or `redis` queue driver for production.

### 24. No Error/Exception Pages Customized

- Default Laravel error pages (404, 403, 500).
- **Recommendation:** Create custom error pages matching the new sidebar design.

### 25. `config/app.php` — Dynamic URL Detection

```php
'url' => env('APP_URL', isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : 'http://localhost'),
```

- This is non-standard and could cause issues with CLI commands, queued jobs, or email links.
- **Fix:** Use the standard:
  ```php
  'url' => env('APP_URL', 'http://localhost'),
  ```

### 26. No API Versioning

- All API routes are in a single `routes/api.php` with no version prefix.
- **Recommendation:** Use `/api/v1/` prefix for future compatibility.

---

## 📋 Summary by Severity

| Severity | Count | Key Items |
|----------|-------|-----------|
| 🔴 Critical | 8 | Debug mode, no DB password, plaintext passwords in seeder, CORS wildcard, broken registration, broken profile, broken password reset, non-expiring tokens |
| 🟠 High | 6 | Role model wrong fillable, department typo, factory broken, tests broken, unprofessional message, orphaned file |
| 🟡 Medium | 6 | No tests, no form requests, no API resources, no policies, seeder typos, session encryption |
| 🔵 Low | 6 | Placeholder dashboards, unused broadcasting, sync queue, no error pages, dynamic URL, no API versioning |

---

## 🎯 Recommended Fix Priority

| # | Task | Severity | Effort |
|---|------|----------|--------|
| 1 | Fix `Role` model `$fillable` | 🟠 High | 1 min |
| 2 | Fix registration/profile to use `username` instead of `email` | 🔴 Critical | 15 min |
| 3 | Fix `UserFactory` | 🟠 High | 5 min |
| 4 | Fix department migration typo (`desription` → `description`) | 🟠 High | 5 min |
| 5 | Set `APP_DEBUG=false` for production | 🔴 Critical | 1 min |
| 6 | Restrict CORS to actual domains | 🔴 Critical | 1 min |
| 7 | Set Sanctum token expiration | 🔴 Critical | 1 min |
| 8 | Remove plaintext passwords from seeders | 🔴 Critical | 5 min |
| 9 | Clean up `RoleMiddleware` message | 🟠 High | 1 min |
| 10 | Delete orphaned `navigation.blade.php` | 🟠 High | 1 min |
| 11 | Fix `ProfileTest` to match schema | 🟠 High | 10 min |
| 12 | Write feature tests for CRUD + roles | 🟡 Medium | 60 min |
| 13 | Create FormRequest classes | 🟡 Medium | 30 min |
| 14 | Define model policies | 🟡 Medium | 30 min |
| 15 | Build actual dashboard content | 🔵 Low | 120 min |
| 16 | Custom error pages | 🔵 Low | 30 min |

---

## ✅ What's Working Well

- **Sidebar redesign** — Clean, modern dark gradient sidebar with Alpine.js interactivity
- **Role-based routing** — Proper middleware separation for developer/admin/viewer
- **Credential CRUD** — Full user management with add/edit/delete modals
- **Database schema** — Well-structured manufacturing domain tables (GSD, MTM, sewing factors)
- **Seeder data** — Comprehensive seed data for GSD categories/elements, MTM elements, sewing factors
- **Tailwind + Alpine.js stack** — Modern, lightweight frontend without heavy JS frameworks

---

*End of Audit Report*
