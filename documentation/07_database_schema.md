# Database Schema — MySQL

## LEAN ENTERPRISE (LIMS)

> **Version:** 1.0  
> **Date:** 2026-09-04  
> **Database:** MySQL 8.x  
> **Engine:** InnoDB  
> **Charset:** utf8mb4  

---

## Table of Contents

1. [Schema Overview](#1-schema-overview)
2. [Module 1 — Authentication & Authorization](#2-module-1--authentication--authorization)
3. [Module 2 — Organization & Master Data](#3-module-2--organization--master-data)
4. [Module 3 — Process Library (GSD / MTM)](#4-module-3--process-library-gsd--mtm)
5. [Module 4 — Production Engineering (PTMS)](#5-module-4--production-engineering-ptms)
6. [Module 5 — Operational Breakdown](#6-module-5--operational-breakdown)
7. [Module 6 — Line Balancing](#7-module-6--line-balancing)
8. [Module 7 — Cycle Time](#8-module-7--cycle-time)
9. [Module 8 — Kaizen](#9-module-8--kaizen)
10. [Module 9 — OSCP & Skill Matrix](#10-module-9--oscp--skill-matrix)
11. [Module 10 — TPM](#11-module-10--tpm)
12. [Module 11 — Material Database](#12-module-11--material-database)
13. [Module 12 — VSM](#13-module-12--vsm)
14. [Module 13 — System & Audit](#14-module-13--system--audit)
15. [Relationships Summary](#15-relationships-summary)
16. [Seed Data](#16-seed-data)

---

## 1. Schema Overview

| Module | Tables | Status |
|--------|--------|--------|
| Authentication & Authorization | `roles`, `users`, `password_reset_tokens`, `personal_access_tokens` | ✅ Implemented |
| Organization & Master Data | `factories`, `departments`, `production_lines`, `articles`, `operators` | ✅ Implemented |
| Process Library | `gsd_categories`, `gsd_elements`, `mtm_elements`, `sewing_factors`, `sewing_stop_factors`, `processes`, `process_versions` | ✅ Implemented |
| Production Engineering (PTMS) | `ptms_reports` | ✅ Implemented |
| Operational Breakdown | `ob_reports`, `ob_items` | 🔲 Planned |
| Line Balancing | `line_balancing_sessions`, `line_balancing_stations` | 🔲 Planned |
| Cycle Time | `cycle_time_records` | 🔲 Planned |
| Kaizen | `kaizens`, `kaizen_actions` | 🔲 Planned |
| OSCP & Skill Matrix | `oscp_records`, `skill_matrix_ratings` | 🔲 Planned |
| TPM | `equipment`, `maintenance_schedules`, `maintenance_logs` | 🔲 Planned |
| Material Database | `materials`, `article_materials` | 🔲 Planned |
| VSM | `vsm_maps`, `vsm_stages` | 🔲 Planned |
| System | `audit_logs`, `settings` | 🔲 Planned |

**Legend:**
- ✅ Implemented — Migration exists in codebase
- 🔲 Planned — Defined in this document, migration to be created

---

## 2. Module 1 — Authentication & Authorization

### `roles`

Stores every user role in the system.

```sql
CREATE TABLE roles (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name       VARCHAR(50) NOT NULL UNIQUE,
    description     VARCHAR(255) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AI | Unique identifier |
| role_name | VARCHAR(50) | NOT NULL, UNIQUE | Role name (developer, admin, viewer) |
| description | VARCHAR(255) | NULLABLE | Role description |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |

**Indexes:** `UNIQUE(role_name)`

---

### `users`

Stores system login accounts.

```sql
CREATE TABLE users (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id             BIGINT UNSIGNED NOT NULL,
    employee_number     VARCHAR(20) NULL UNIQUE,
    name                VARCHAR(255) NOT NULL,
    username            VARCHAR(255) NOT NULL UNIQUE,
    description         TEXT NULL,
    email_verified_at   TIMESTAMP NULL,
    password            VARCHAR(255) NOT NULL,
    remember_token      VARCHAR(100) NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
```

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AI | Unique identifier |
| role_id | BIGINT UNSIGNED | FK → roles.id, NOT NULL | User's role |
| employee_number | VARCHAR(20) | NULLABLE, UNIQUE | Employee ID number |
| name | VARCHAR(255) | NOT NULL | Full name |
| username | VARCHAR(255) | NOT NULL, UNIQUE | Login username |
| description | TEXT | NULLABLE | User description/notes |
| email_verified_at | TIMESTAMP | NULLABLE | Email verification (legacy, unused) |
| password | VARCHAR(255) | NOT NULL | Bcrypt hashed password |
| remember_token | VARCHAR(100) | NULLABLE | Remember me token |
| created_at | TIMESTAMP | NULLABLE | Record creation time |
| updated_at | TIMESTAMP | NULLABLE | Last update time |

**Indexes:** `UNIQUE(username)`, `UNIQUE(employee_number)`, `INDEX(role_id)`

---

### `password_reset_tokens`

Laravel default — stores password reset tokens.

```sql
CREATE TABLE password_reset_tokens (
    email       VARCHAR(255) PRIMARY KEY,
    token       VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP NULL
);
```

---

### `personal_access_tokens`

Laravel Sanctum — stores API tokens.

```sql
CREATE TABLE personal_access_tokens (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type  VARCHAR(255) NOT NULL,
    tokenable_id    BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(255) NOT NULL,
    token           VARCHAR(64) NOT NULL UNIQUE,
    abilities       TEXT NULL,
    last_used_at    TIMESTAMP NULL,
    expires_at      TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    INDEX personal_access_tokens_tokenable_type_tokenable_id_index (tokenable_type, tokenable_id)
);
```

---

## 3. Module 2 — Organization & Master Data

### `factories`

Top-level organizational unit.

```sql
CREATE TABLE factories (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    factory_name    VARCHAR(100) NOT NULL,
    description     VARCHAR(255) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

---

### `departments`

Belongs to a factory.

```sql
CREATE TABLE departments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    factory_id      BIGINT UNSIGNED NOT NULL,
    department_name VARCHAR(100) NOT NULL,
    description     VARCHAR(255) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_departments_factory
        FOREIGN KEY (factory_id) REFERENCES factories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
```

**Indexes:** `INDEX(factory_id)`

> ⚠️ **Known Bug:** The migration column is named `desription` (typo). Needs a rename migration to `description`.

---

### `production_lines`

Belongs to a department.

```sql
CREATE TABLE production_lines (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id   BIGINT UNSIGNED NOT NULL,
    line_name       VARCHAR(100) NOT NULL,
    description     VARCHAR(255) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_production_lines_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
```

**Indexes:** `INDEX(department_id)`

---

### `articles`

Products/articles being manufactured.

```sql
CREATE TABLE articles (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_name    VARCHAR(150) NOT NULL,
    label_number    VARCHAR(100) NOT NULL UNIQUE,
    destination     VARCHAR(100) NOT NULL,
    description     VARCHAR(255) NULL,
    status          ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

**Indexes:** `UNIQUE(label_number)`

---

### `operators`

Production operators / workers.

```sql
CREATE TABLE operators (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_number VARCHAR(20) NOT NULL UNIQUE,
    operator_name   VARCHAR(100) NOT NULL,
    status          ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

**Indexes:** `UNIQUE(employee_number)`

> **Future Enhancement:** Add `line_id` FK to link operator to assigned production line. Add `department_id` for department-level filtering.

---

## 4. Module 3 — Process Library (GSD / MTM)

### `gsd_categories`

General Sewing Data categories (e.g., Obtain & Match, Aligning, Forming Shapes).

```sql
CREATE TABLE gsd_categories (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name   VARCHAR(150) NOT NULL UNIQUE,
    description     VARCHAR(255) NULL,
    status          ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

---

### `gsd_elements`

Individual GSD elements within each category.

```sql
CREATE TABLE gsd_elements (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gsd_category_id BIGINT UNSIGNED NOT NULL,
    element_name    VARCHAR(200) NOT NULL,
    description     VARCHAR(255) NULL,
    code            VARCHAR(50) NOT NULL,
    tmu             DECIMAL(10,2) NOT NULL,
    seconds         DECIMAL(10,2) NOT NULL,
    motion_sequence VARCHAR(100) NULL,
    status          ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_gsd_elements_category
        FOREIGN KEY (gsd_category_id) REFERENCES gsd_categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
```

**Indexes:** `INDEX(gsd_category_id)`, `INDEX(code)`

---

### `mtm_elements`

Methods-Time Measurement body motion elements.

```sql
CREATE TABLE mtm_elements (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    element_name    VARCHAR(200) NOT NULL,
    description     VARCHAR(255) NULL,
    code            VARCHAR(50) NOT NULL,
    tmu             DECIMAL(10,2) NOT NULL,
    seconds         DECIMAL(10,2) NOT NULL,
    status          ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

---

### `sewing_factors`

Sewing difficulty multipliers.

```sql
CREATE TABLE sewing_factors (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    factor_name     VARCHAR(100) NOT NULL,
    description     VARCHAR(255) NULL,
    factor_value    DECIMAL(10,2) NOT NULL,
    code            VARCHAR(50) NOT NULL,
    status          ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

---

### `sewing_stop_factors`

Sewing stop/tolerance factors with TMU values.

```sql
CREATE TABLE sewing_stop_factors (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    factor_name     VARCHAR(150) NOT NULL,
    description     VARCHAR(255) NULL,
    tolerance       VARCHAR(100) NULL,
    factor_value    DECIMAL(10,2) NOT NULL,
    code            VARCHAR(50) NOT NULL,
    status          ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

---

### `processes`

Master process records.

```sql
CREATE TABLE processes (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    process_name    VARCHAR(200) NOT NULL UNIQUE,
    description     VARCHAR(255) NULL,
    status          ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

**Indexes:** `UNIQUE(process_name)`

---

### `process_versions`

Versioned snapshots of a process (draft → active → archived).

```sql
CREATE TABLE process_versions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    process_id      BIGINT UNSIGNED NOT NULL,
    version_number  INT UNSIGNED NOT NULL,
    notes           VARCHAR(255) NULL,
    status          ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'draft',
    created_by      BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_process_versions_process
        FOREIGN KEY (process_id) REFERENCES processes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_process_versions_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    UNIQUE KEY process_versions_process_version_unique (process_id, version_number)
);
```

**Indexes:** `UNIQUE(process_id, version_number)`, `INDEX(created_by)`

---

## 5. Module 4 — Production Engineering (PTMS)

### `ptms_reports`

Production Time Method Sheet — the core calculation report.

```sql
CREATE TABLE ptms_reports (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_number           VARCHAR(50) NOT NULL UNIQUE,
    article_id              BIGINT UNSIGNED NOT NULL,
    process_version_id      BIGINT UNSIGNED NOT NULL,
    operator_id             BIGINT UNSIGNED NOT NULL,
    factory_id              BIGINT UNSIGNED NOT NULL,
    department_id           BIGINT UNSIGNED NOT NULL,
    line_id                 BIGINT UNSIGNED NOT NULL,
    created_by              BIGINT UNSIGNED NOT NULL,

    -- Machine parameters
    machine_name            VARCHAR(150) NULL,
    feed_type               VARCHAR(100) NULL,
    rpm                     DECIMAL(10,2) NULL,
    stitch_per_cm           DECIMAL(10,2) NULL,
    seam_width              DECIMAL(10,2) NULL,

    -- Allowance percentages
    machine_delay_percent   DECIMAL(10,2) NOT NULL DEFAULT 0,
    contingency_percent     DECIMAL(10,2) NOT NULL DEFAULT 0,
    ra_percent              DECIMAL(10,2) NOT NULL DEFAULT 0,

    -- TMU calculations
    machining_tmu           DECIMAL(10,2) NOT NULL DEFAULT 0,
    handling_tmu            DECIMAL(10,2) NOT NULL DEFAULT 0,
    bundle_tmu              DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_tmu               DECIMAL(10,2) NOT NULL DEFAULT 0,
    bms                     DECIMAL(10,2) NOT NULL DEFAULT 0,
    smv                     DECIMAL(10,2) NOT NULL DEFAULT 0,

    status                  ENUM('draft', 'final', 'archived') NOT NULL DEFAULT 'draft',
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,

    CONSTRAINT fk_ptms_article
        FOREIGN KEY (article_id) REFERENCES articles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ptms_process_version
        FOREIGN KEY (process_version_id) REFERENCES process_versions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ptms_operator
        FOREIGN KEY (operator_id) REFERENCES operators(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ptms_factory
        FOREIGN KEY (factory_id) REFERENCES factories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ptms_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ptms_line
        FOREIGN KEY (line_id) REFERENCES production_lines(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ptms_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
```

**Indexes:** `UNIQUE(report_number)`, `INDEX(article_id)`, `INDEX(process_version_id)`, `INDEX(operator_id)`, `INDEX(factory_id)`, `INDEX(line_id)`

> **Future Enhancement:** Add `ptms_report_elements` junction table to store individual GSD/MTM elements used in each PTMS report calculation.

---

### `ptms_report_elements` *(Planned)*

Junction table linking PTMS reports to the GSD/MTM elements used in the calculation.

```sql
CREATE TABLE ptms_report_elements (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ptms_report_id      BIGINT UNSIGNED NOT NULL,
    elementable_type    VARCHAR(255) NOT NULL,  -- 'gsd_element' or 'mtm_element'
    elementable_id      BIGINT UNSIGNED NOT NULL,
    sequence_order      INT UNSIGNED NOT NULL DEFAULT 0,
    quantity            INT UNSIGNED NOT NULL DEFAULT 1,
    tmu_value           DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT fk_ptms_elements_report
        FOREIGN KEY (ptms_report_id) REFERENCES ptms_reports(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX ptms_report_elements_elementable_index (elementable_type, elementable_id)
);
```

---

## 6. Module 5 — Operational Breakdown *(Planned)*

### `ob_reports`

Operational Breakdown report header.

```sql
CREATE TABLE ob_reports (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_number       VARCHAR(50) NOT NULL UNIQUE,
    article_id          BIGINT UNSIGNED NOT NULL,
    process_version_id  BIGINT UNSIGNED NOT NULL,
    operator_id         BIGINT UNSIGNED NOT NULL,
    line_id             BIGINT UNSIGNED NOT NULL,
    total_observed_time DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_tmu           DECIMAL(10,2) NOT NULL DEFAULT 0,
    notes               TEXT NULL,
    status              ENUM('draft', 'final', 'archived') NOT NULL DEFAULT 'draft',
    created_by          BIGINT UNSIGNED NOT NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT fk_ob_article
        FOREIGN KEY (article_id) REFERENCES articles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ob_process_version
        FOREIGN KEY (process_version_id) REFERENCES process_versions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ob_operator
        FOREIGN KEY (operator_id) REFERENCES operators(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ob_line
        FOREIGN KEY (line_id) REFERENCES production_lines(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ob_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
```

---

### `ob_items`

Individual breakdown items within an OB report.

```sql
CREATE TABLE ob_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ob_report_id    BIGINT UNSIGNED NOT NULL,
    gsd_category_id BIGINT UNSIGNED NULL,
    gsd_element_id  BIGINT UNSIGNED NULL,
    description     VARCHAR(255) NOT NULL,
    sequence_order  INT UNSIGNED NOT NULL DEFAULT 0,
    observed_time   DECIMAL(10,2) NOT NULL DEFAULT 0,
    tmu_value       DECIMAL(10,2) NOT NULL DEFAULT 0,
    percentage      DECIMAL(5,2) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_ob_items_report
        FOREIGN KEY (ob_report_id) REFERENCES ob_reports(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ob_items_gsd_category
        FOREIGN KEY (gsd_category_id) REFERENCES gsd_categories(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ob_items_gsd_element
        FOREIGN KEY (gsd_element_id) REFERENCES gsd_elements(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);
```

---

## 7. Module 6 — Line Balancing *(Planned)*

### `line_balancing_sessions`

Line balancing analysis session.

```sql
CREATE TABLE line_balancing_sessions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_name    VARCHAR(200) NOT NULL,
    line_id         BIGINT UNSIGNED NOT NULL,
    article_id      BIGINT UNSIGNED NOT NULL,
    takt_time       DECIMAL(10,2) NOT NULL,
    total_stations  INT UNSIGNED NOT NULL DEFAULT 0,
    efficiency      DECIMAL(5,2) NULL,
    balance_loss    DECIMAL(5,2) NULL,
    notes           TEXT NULL,
    status          ENUM('draft', 'final', 'archived') NOT NULL DEFAULT 'draft',
    created_by      BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_lb_line
        FOREIGN KEY (line_id) REFERENCES production_lines(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_lb_article
        FOREIGN KEY (article_id) REFERENCES articles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_lb_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
```

---

### `line_balancing_stations`

Individual stations within a line balancing session.

```sql
CREATE TABLE line_balancing_stations (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    line_balancing_session_id BIGINT UNSIGNED NOT NULL,
    station_number          INT UNSIGNED NOT NULL,
    operator_id             BIGINT UNSIGNED NULL,
    process_version_id      BIGINT UNSIGNED NULL,
    cycle_time              DECIMAL(10,2) NOT NULL DEFAULT 0,
    tmu_value               DECIMAL(10,2) NOT NULL DEFAULT 0,
    idle_time               DECIMAL(10,2) NOT NULL DEFAULT 0,
    utilization             DECIMAL(5,2) NOT NULL DEFAULT 0,
    notes                   VARCHAR(255) NULL,
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,

    CONSTRAINT fk_lbs_session
        FOREIGN KEY (line_balancing_session_id) REFERENCES line_balancing_sessions(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_lbs_operator
        FOREIGN KEY (operator_id) REFERENCES operators(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_lbs_process_version
        FOREIGN KEY (process_version_id) REFERENCES process_versions(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);
```

---

## 8. Module 7 — Cycle Time *(Planned)*

### `cycle_time_records`

Cycle time observations per operation.

```sql
CREATE TABLE cycle_time_records (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id          BIGINT UNSIGNED NOT NULL,
    process_version_id  BIGINT UNSIGNED NOT NULL,
    operator_id         BIGINT UNSIGNED NOT NULL,
    line_id             BIGINT UNSIGNED NOT NULL,
    operation_name      VARCHAR(200) NOT NULL,
    observation_date    DATE NOT NULL,
    sample_size         INT UNSIGNED NOT NULL DEFAULT 1,
    cycle_time_avg      DECIMAL(10,2) NOT NULL DEFAULT 0,
    cycle_time_min      DECIMAL(10,2) NOT NULL DEFAULT 0,
    cycle_time_max      DECIMAL(10,2) NOT NULL DEFAULT 0,
    cycle_time_std_dev  DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit                VARCHAR(20) NOT NULL DEFAULT 'seconds',
    notes               TEXT NULL,
    created_by          BIGINT UNSIGNED NOT NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT fk_ct_article
        FOREIGN KEY (article_id) REFERENCES articles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ct_process_version
        FOREIGN KEY (process_version_id) REFERENCES process_versions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ct_operator
        FOREIGN KEY (operator_id) REFERENCES operators(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ct_line
        FOREIGN KEY (line_id) REFERENCES production_lines(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ct_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
```

---

### `cycle_time_observations` *(Optional)*

Raw individual observations for detailed analysis.

```sql
CREATE TABLE cycle_time_observations (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cycle_time_record_id BIGINT UNSIGNED NOT NULL,
    observation_number  INT UNSIGNED NOT NULL,
    observed_value      DECIMAL(10,2) NOT NULL,
    created_at          TIMESTAMP NULL,

    CONSTRAINT fk_cto_record
        FOREIGN KEY (cycle_time_record_id) REFERENCES cycle_time_records(id)
        ON UPDATE CASCADE ON DELETE CASCADE
);
```

---

## 9. Module 8 — Kaizen *(Planned)*

### `kaizens`

Kaizen / improvement proposals.

```sql
CREATE TABLE kaizens (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kaizen_number       VARCHAR(50) NOT NULL UNIQUE,
    title               VARCHAR(255) NOT NULL,
    description         TEXT NULL,
    factory_id          BIGINT UNSIGNED NOT NULL,
    department_id       BIGINT UNSIGNED NOT NULL,
    line_id             BIGINT UNSIGNED NULL,
    article_id          BIGINT UNSIGNED NULL,
    process_version_id  BIGINT UNSIGNED NULL,
    category            VARCHAR(100) NULL,
    priority            ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status              ENUM('proposed', 'in_progress', 'completed', 'verified', 'rejected') NOT NULL DEFAULT 'proposed',
    proposed_by         BIGINT UNSIGNED NOT NULL,
    assigned_to         BIGINT UNSIGNED NULL,
    target_date         DATE NULL,
    completed_date      DATE NULL,
    before_description  TEXT NULL,
    after_description   TEXT NULL,
    before_metric       DECIMAL(10,2) NULL,
    after_metric        DECIMAL(10,2) NULL,
    savings_description VARCHAR(255) NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT fk_kaizen_factory
        FOREIGN KEY (factory_id) REFERENCES factories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_kaizen_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_kaizen_line
        FOREIGN KEY (line_id) REFERENCES production_lines(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_kaizen_article
        FOREIGN KEY (article_id) REFERENCES articles(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_kaizen_process_version
        FOREIGN KEY (process_version_id) REFERENCES process_versions(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_kaizen_proposed_by
        FOREIGN KEY (proposed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_kaizen_assigned_to
        FOREIGN KEY (assigned_to) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);
```

---

### `kaizen_actions`

Action items within a kaizen.

```sql
CREATE TABLE kaizen_actions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kaizen_id       BIGINT UNSIGNED NOT NULL,
    action_description TEXT NOT NULL,
    responsible     VARCHAR(100) NULL,
    due_date        DATE NULL,
    status          ENUM('pending', 'in_progress', 'done') NOT NULL DEFAULT 'pending',
    completed_at    TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_kaizen_actions_kaizen
        FOREIGN KEY (kaizen_id) REFERENCES kaizens(id)
        ON UPDATE CASCADE ON DELETE CASCADE
);
```

---

## 10. Module 9 — OSCP & Skill Matrix *(Planned)*

### `oscp_records`

Operator Skill Certification Plan records.

```sql
CREATE TABLE oscp_records (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    operator_id     BIGINT UNSIGNED NOT NULL,
    skill_name      VARCHAR(200) NOT NULL,
    certification_level ENUM('trainee', 'beginner', 'intermediate', 'advanced', 'expert') NOT NULL DEFAULT 'trainee',
    certified_date  DATE NULL,
    expiry_date     DATE NULL,
    assessor_name   VARCHAR(100) NULL,
    status          ENUM('pending', 'certified', 'expired', 'revoked') NOT NULL DEFAULT 'pending',
    notes           TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_oscp_operator
        FOREIGN KEY (operator_id) REFERENCES operators(id)
        ON UPDATE CASCADE ON DELETE CASCADE
);
```

---

### `skill_matrix_ratings`

Skill ratings per operator per process/operation.

```sql
CREATE TABLE skill_matrix_ratings (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    operator_id         BIGINT UNSIGNED NOT NULL,
    process_version_id  BIGINT UNSIGNED NOT NULL,
    line_id             BIGINT UNSIGNED NULL,
    skill_level         TINYINT UNSIGNED NOT NULL DEFAULT 0,  -- 1-5 scale
    rating_date         DATE NOT NULL,
    rated_by            BIGINT UNSIGNED NOT NULL,
    notes               TEXT NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT fk_sm_operator
        FOREIGN KEY (operator_id) REFERENCES operators(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_sm_process_version
        FOREIGN KEY (process_version_id) REFERENCES process_versions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sm_line
        FOREIGN KEY (line_id) REFERENCES production_lines(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_sm_rated_by
        FOREIGN KEY (rated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    UNIQUE KEY skill_matrix_unique (operator_id, process_version_id)
);
```

---

## 11. Module 10 — TPM *(Planned)*

### `equipment`

Machine / equipment registry.

```sql
CREATE TABLE equipment (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_code  VARCHAR(50) NOT NULL UNIQUE,
    equipment_name  VARCHAR(200) NOT NULL,
    equipment_type  VARCHAR(100) NULL,
    brand           VARCHAR(100) NULL,
    model           VARCHAR(100) NULL,
    serial_number   VARCHAR(100) NULL,
    factory_id      BIGINT UNSIGNED NOT NULL,
    department_id   BIGINT UNSIGNED NULL,
    line_id         BIGINT UNSIGNED NULL,
    purchase_date   DATE NULL,
    status          ENUM('active', 'maintenance', 'inactive', 'retired') NOT NULL DEFAULT 'active',
    notes           TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_equipment_factory
        FOREIGN KEY (factory_id) REFERENCES factories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_equipment_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_equipment_line
        FOREIGN KEY (line_id) REFERENCES production_lines(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);
```

---

### `maintenance_schedules`

Preventive maintenance schedules.

```sql
CREATE TABLE maintenance_schedules (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_id    BIGINT UNSIGNED NOT NULL,
    schedule_name   VARCHAR(200) NOT NULL,
    frequency       ENUM('daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
    last_done       DATE NULL,
    next_due        DATE NOT NULL,
    description     TEXT NULL,
    status          ENUM('active', 'paused', 'completed') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_ms_equipment
        FOREIGN KEY (equipment_id) REFERENCES equipment(id)
        ON UPDATE CASCADE ON DELETE CASCADE
);
```

---

### `maintenance_logs`

Actual maintenance activity records.

```sql
CREATE TABLE maintenance_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_id    BIGINT UNSIGNED NOT NULL,
    schedule_id     BIGINT UNSIGNED NULL,
    maintenance_type ENUM('preventive', 'corrective', 'breakdown', 'improvement') NOT NULL,
    description     TEXT NOT NULL,
    performed_by    VARCHAR(100) NULL,
    performed_at    DATETIME NOT NULL,
    downtime_hours  DECIMAL(8,2) NULL DEFAULT 0,
    cost            DECIMAL(12,2) NULL DEFAULT 0,
    parts_used      TEXT NULL,
    status          ENUM('completed', 'pending_parts', 'in_progress') NOT NULL DEFAULT 'completed',
    notes           TEXT NULL,
    created_by      BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_ml_equipment
        FOREIGN KEY (equipment_id) REFERENCES equipment(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ml_schedule
        FOREIGN KEY (schedule_id) REFERENCES maintenance_schedules(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ml_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
```

---

## 12. Module 11 — Material Database *(Planned)*

### `materials`

Raw material / component master data.

```sql
CREATE TABLE materials (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_code   VARCHAR(50) NOT NULL UNIQUE,
    material_name   VARCHAR(200) NOT NULL,
    material_type   VARCHAR(100) NULL,
    unit_of_measure VARCHAR(50) NULL,
    specification   TEXT NULL,
    status          ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

---

### `article_materials`

Junction table — which materials are used in which articles.

```sql
CREATE TABLE article_materials (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id      BIGINT UNSIGNED NOT NULL,
    material_id     BIGINT UNSIGNED NOT NULL,
    quantity        DECIMAL(10,2) NULL,
    unit            VARCHAR(50) NULL,
    notes           VARCHAR(255) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_am_article
        FOREIGN KEY (article_id) REFERENCES articles(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_am_material
        FOREIGN KEY (material_id) REFERENCES materials(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    UNIQUE KEY article_materials_unique (article_id, material_id)
);
```

---

## 13. Module 12 — VSM *(Planned)*

### `vsm_maps`

Value Stream Map definitions.

```sql
CREATE TABLE vsm_maps (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    map_name        VARCHAR(200) NOT NULL,
    description     TEXT NULL,
    factory_id      BIGINT UNSIGNED NOT NULL,
    article_id      BIGINT UNSIGNED NULL,
    current_state   BOOLEAN NOT NULL DEFAULT TRUE,
    status          ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'draft',
    created_by      BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_vsm_factory
        FOREIGN KEY (factory_id) REFERENCES factories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_vsm_article
        FOREIGN KEY (article_id) REFERENCES articles(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_vsm_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);
```

---

### `vsm_stages`

Individual stages/steps in a value stream.

```sql
CREATE TABLE vsm_stages (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vsm_map_id      BIGINT UNSIGNED NOT NULL,
    stage_name      VARCHAR(200) NOT NULL,
    stage_type      ENUM('process', 'inventory', 'transport', 'inspection', 'delay') NOT NULL DEFAULT 'process',
    sequence_order  INT UNSIGNED NOT NULL DEFAULT 0,
    cycle_time      DECIMAL(10,2) NULL,
    lead_time       DECIMAL(10,2) NULL,
    uptime_percent  DECIMAL(5,2) NULL,
    batch_size      INT UNSIGNED NULL,
    operators_count INT UNSIGNED NULL,
    wip_quantity    INT UNSIGNED NULL,
    process_time    DECIMAL(10,2) NULL,
    changeover_time DECIMAL(10,2) NULL,
    notes           TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    CONSTRAINT fk_vsm_stages_map
        FOREIGN KEY (vsm_map_id) REFERENCES vsm_maps(id)
        ON UPDATE CASCADE ON DELETE CASCADE
);
```

---

## 14. Module 13 — System & Audit *(Planned)*

### `audit_logs`

Track all data changes for accountability.

```sql
CREATE TABLE audit_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NULL,
    action          ENUM('create', 'update', 'delete', 'login', 'logout', 'export') NOT NULL,
    auditable_type  VARCHAR(255) NOT NULL,
    auditable_id    BIGINT UNSIGNED NOT NULL,
    old_values      JSON NULL,
    new_values      JSON NULL,
    ip_address      VARCHAR(45) NULL,
    user_agent      VARCHAR(255) NULL,
    created_at      TIMESTAMP NULL,

    CONSTRAINT fk_audit_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    INDEX audit_logs_auditable_index (auditable_type, auditable_id),
    INDEX audit_logs_user_id_index (user_id),
    INDEX audit_logs_created_at_index (created_at)
);
```

---

### `settings`

System-wide configuration key-value store.

```sql
CREATE TABLE settings (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key         VARCHAR(100) NOT NULL UNIQUE,
    value       TEXT NULL,
    group_name  VARCHAR(50) NULL DEFAULT 'general',
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

---

## 15. Relationships Summary

### Entity Relationship Diagram (Text)

```
roles ──────────┬──── 1:N ──── users
                │                 │
                │                 ├── 1:N ── process_versions (created_by)
                │                 ├── 1:N ── ptms_reports (created_by)
                │                 └── 1:N ── audit_logs
                │
factories ──────┬──── 1:N ──── departments
                │                  │
                │                  └──── 1:N ──── production_lines
                │                                    │
                ├── 1:N ── ptms_reports              ├── 1:N ── ptms_reports
                ├── 1:N ── equipment                 ├── 1:N ── operators (future)
                └── 1:N ── vsm_maps                 ├── 1:N ── line_balancing_*
                                                    └── 1:N ── cycle_time_records

articles ───────┬──── 1:N ──── ptms_reports
                ├── 1:N ──── ob_reports
                ├── N:M ──── materials (via article_materials)
                ├── 1:N ──── line_balancing_sessions
                ├── 1:N ──── cycle_time_records
                └── 1:N ──── vsm_maps

processes ──────┬──── 1:N ──── process_versions
                            │
                            ├── 1:N ──── ptms_reports
                            ├── 1:N ──── ob_reports
                            ├── 1:N ──── line_balancing_stations
                            └── 1:N ──── skill_matrix_ratings

gsd_categories ─┬──── 1:N ──── gsd_elements
                └──── 1:N ──── ob_items

operators ──────┬──── 1:N ──── ptms_reports
                ├── 1:N ──── ob_reports
                ├── 1:N ──── cycle_time_records
                ├── 1:N ──── oscp_records
                └── 1:N ──── skill_matrix_ratings

equipment ──────┬──── 1:N ──── maintenance_schedules
                └──── 1:N ──── maintenance_logs
```

---

## 16. Seed Data

### Roles

| id | role_name | description |
|----|-----------|-------------|
| 1 | developer | Full system access, including technical/system functions |
| 2 | admin | Full application/business feature access |
| 3 | viewer | View and download/export only |

### GSD Categories (7)

Obtain and Match, Aligning and Adjusting, Forming Shapes, Trimming and Tool Use, Asiding, Handling Machine, Get and Put Data

### GSD Elements (30+)

Elements within each category with TMU values, codes, and motion sequences. See `GsdElementSeeder`.

### MTM Elements (15+)

Body motion elements (Foot, Pace, Bend, Sit, etc.) with TMU values. See `MtmElementSeeder`.

### Sewing Factors (4)

| Code | Factor | Value |
|------|--------|-------|
| N | Nil | 1.00 |
| L | Low | 1.10 |
| M | Medium | 1.20 |
| H | High | 1.40 |

### Sewing Stop Factors (3)

| Code | Factor | Value |
|------|--------|-------|
| A | Stop Long a Seam or Run Off | 0.00 |
| B | Stop for Non-Visible Backtack | 9.00 |
| C | Stop to Change Direction / Visible Backtack | 21.00 |

---

*End of Database Schema*
