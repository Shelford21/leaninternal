# Product Requirement Document (PRD)

## LEAN ENTERPRISE — Web-Based Application

> **Version:** 1.0  
> **Date:** 2026-09-04  
> **Author:** Fauzan Fadhillah Arisandi  
> **Status:** In Development  

---

## Table of Contents

1. [Overview](#1-overview)
2. [Problem Statement](#2-problem-statement)
3. [Goals & Objectives](#3-goals--objectives)
4. [Target Users](#4-target-users)
5. [Functional Requirements](#5-functional-requirements)
6. [Non-Functional Requirements](#6-non-functional-requirements)
7. [Module Specifications](#7-module-specifications)
8. [User Roles & Permissions](#8-user-roles--permissions)
9. [UI/UX Requirements](#9-uiux-requirements)
10. [Success Criteria](#10-success-criteria)
11. [Constraints & Assumptions](#11-constraints--assumptions)
12. [Out of Scope](#12-out-of-scope)

---

## 1. Overview

**LEAN ENTERPRISE** is a web-based integrated platform for managing Industrial Engineering (IE) and Lean Manufacturing data, methods, and improvement activities within a manufacturing environment.

The system consolidates multiple IE/Lean tools — GSD, Process Database, Cycle Time, Operational Breakdown, Line Balancing, Kaizen, OSCP, TPM, Skill Matrix, VSM — into a single centralized application, eliminating fragmented spreadsheets and siloed databases.

### Technology Stack

| Layer | Technology |
|-------|-----------|
| Frontend | Next.js (React) |
| Styling | Tailwind CSS |
| Server State | React Query (TanStack Query) |
| Client State | Zustand |
| Backend API | Laravel 10 (PHP 8.1+) |
| Database | MySQL |
| Authentication | Laravel Sanctum (token-based) |

---

## 2. Problem Statement

In the current workflow, Industrial Engineering and Lean Manufacturing data is managed across multiple disconnected files, spreadsheets, and standalone tools. This creates:

- **Data fragmentation** — Process data, GSD standards, cycle times, and operator skills are stored in separate files with no linkages.
- **Duplication** — The same data (e.g., operator info, article details) is repeated across multiple spreadsheets.
- **Inconsistency** — Updates in one file are not reflected in others, leading to version conflicts.
- **Slow retrieval** — Finding specific data requires manually searching through multiple files.
- **No audit trail** — Changes are not tracked; there is no history of who modified what and when.
- **Limited analysis** — Cross-module analysis (e.g., linking cycle time to line balancing) is difficult without integrated data.
- **Manual reporting** — Reports are compiled manually from various sources.

---

## 3. Goals & Objectives

### 3.1 General Goal

Build a web-based integrated platform to support structured, centralized, and accessible management of Lean Manufacturing and Industrial Engineering data and activities.

### 3.2 Specific Objectives

| # | Objective |
|---|-----------|
| 1 | Integrate data related to production processes, operators, materials, products, and work methods into a single system. |
| 2 | Create a centralized database as the single source of truth for all Lean/IE activities. |
| 3 | Simplify data input, search, processing, and update workflows. |
| 4 | Reduce dependency on manual data management and scattered files. |
| 5 | Support data-driven analysis and decision making. |
| 6 | Standardize work methods and process documentation. |
| 7 | Integrate multiple Lean/IE tools into one platform. |
| 8 | Build a system that can be developed incrementally based on business needs. |

---

## 4. Target Users

| User Role | Description | Primary Actions |
|-----------|-------------|-----------------|
| **IE Engineer** | Core user — creates and manages process data, GSD, cycle time, line balancing, PTMS | Full CRUD on all IE modules |
| **Supervisor / Line Leader** | Monitors production data, reviews line balancing, tracks improvement | View dashboards, approve kaizen, view reports |
| **Admin** | Manages user accounts, master data, factory/department/line structure | User management, master data CRUD |
| **Viewer / Operator** | Read-only access to relevant data (skill matrix, process standards) | View only |
| **Developer** | System administration, technical configuration | Full system access |

---

## 5. Functional Requirements

### 5.1 Authentication & User Management

| ID | Requirement | Priority |
|----|-------------|----------|
| AUTH-01 | Users log in with username + password (no email required) | P0 |
| AUTH-02 | Role-based access control (developer, admin, IE engineer, supervisor, viewer) | P0 |
| AUTH-03 | Credential management — admin can create, edit, delete user accounts | P0 |
| AUTH-04 | Password hashing (bcrypt) | P0 |
| AUTH-05 | Rate limiting on login attempts (5 attempts per minute) | P0 |
| AUTH-06 | Session management with token expiration | P1 |
| AUTH-07 | Profile management (update name, password) | P1 |

### 5.2 Master Data

| ID | Requirement | Priority |
|----|-------------|----------|
| MD-01 | Factory management (CRUD) — name, description | P0 |
| MD-02 | Department management (CRUD) — linked to factory | P0 |
| MD-03 | Production Line management (CRUD) — linked to department | P0 |
| MD-04 | Article/Product management (CRUD) — name, label number, destination | P0 |
| MD-05 | Operator management (CRUD) — employee number, name, status | P0 |
| MD-06 | Process master management (CRUD) — name, description, versioning | P0 |
| MD-07 | Machine/Equipment management (CRUD) | P1 |
| MD-08 | Material management (CRUD) | P2 |

### 5.3 GSD & Process Database Module

| ID | Requirement | Priority |
|----|-------------|----------|
| GSD-01 | GSD Category management — categories of sewing/motion activities | P0 |
| GSD-02 | GSD Element management — elements within each category (code, TMU, seconds, motion sequence) | P0 |
| GSD-03 | MTM Element management — body motion elements (code, TMU, seconds) | P0 |
| GSD-04 | Sewing Factor management — difficulty factors (Nil, Low, Medium, High) with multiplier values | P0 |
| GSD-05 | Sewing Stop Factor management — stop/tolerance factors with TMU values | P0 |
| GSD-06 | Process Version management — draft → active → archived lifecycle | P0 |
| GSD-07 | Search and filter elements by category, code, or name | P1 |
| GSD-08 | Export GSD data to Excel/PDF | P2 |

### 5.4 PTMS (Production Time Method Sheet) Module

| ID | Requirement | Priority |
|----|-------------|----------|
| PTMS-01 | Create PTMS report linked to article, process version, operator, factory, department, line | P0 |
| PTMS-02 | Record machine parameters (machine name, feed type, RPM, stitch/cm, seam width) | P0 |
| PTMS-03 | Calculate handling TMU from GSD elements | P0 |
| PTMS-04 | Calculate machining TMU from machine parameters | P0 |
| PTMS-05 | Apply sewing factors and stop factors to calculations | P0 |
| PTMS-06 | Auto-calculate total TMU, BMS (Basic Minute Standard), and SMV (Standard Minute Value) | P0 |
| PTMS-07 | Apply contingency and RA (Rating Allowance) percentages | P0 |
| PTMS-08 | Report lifecycle: draft → final → archived | P0 |
| PTMS-09 | Print/export PTMS report | P1 |

### 5.5 Operational Breakdown Module

| ID | Requirement | Priority |
|----|-------------|----------|
| OB-01 | Record operational breakdown per process/operation | P1 |
| OB-02 | Categorize breakdown activities | P1 |
| OB-03 | Time recording per breakdown element | P1 |
| OB-04 | Analysis view — breakdown distribution chart | P2 |

### 5.6 Line Balancing Module

| ID | Requirement | Priority |
|----|-------------|----------|
| LB-01 | Define line configuration (operators, processes, stations) | P1 |
| LB-02 | Input cycle time per operation/station | P1 |
| LB-03 | Calculate line balancing efficiency | P1 |
| LB-04 | Visualize cycle time vs. takt time chart | P1 |
| LB-05 | Identify bottlenecks automatically | P2 |
| LB-06 | Suggest rebalancing recommendations | P3 |

### 5.7 Cycle Time Module

| ID | Requirement | Priority |
|----|-------------|----------|
| CT-01 | Record cycle time observations per operation | P1 |
| CT-02 | Calculate average, min, max, standard deviation | P1 |
| CT-03 | Link cycle time to process/article/operator | P1 |
| CT-04 | Historical cycle time tracking and trend analysis | P2 |

### 5.8 Kaizen Module

| ID | Requirement | Priority |
|----|-------------|----------|
| KZ-01 | Record kaizen/improvement proposals | P2 |
| KZ-02 | Track kaizen status (proposed → in progress → completed → verified) | P2 |
| KZ-03 | Link kaizen to specific process/line/article | P2 |
| KZ-04 | Before/after comparison | P2 |
| KZ-05 | Kaizen dashboard — count, status distribution, savings | P3 |

### 5.9 OSCP Module

| ID | Requirement | Priority |
|----|-------------|----------|
| OSCP-01 | Record OSCP (Operator Skill Certification Plan) data | P2 |
| OSCP-02 | Track certification status per operator per skill | P2 |
| OSCP-03 | Link OSCP to skill matrix | P2 |

### 5.10 TPM (Total Productive Maintenance) Module

| ID | Requirement | Priority |
|----|-------------|----------|
| TPM-01 | Equipment/machine database | P2 |
| TPM-02 | Maintenance schedule management | P2 |
| TPM-03 | Maintenance activity recording | P2 |
| TPM-04 | Equipment downtime tracking | P3 |
| TPM-05 | OEE (Overall Equipment Effectiveness) calculation | P3 |

### 5.11 Skill Matrix Module

| ID | Requirement | Priority |
|----|-------------|----------|
| SM-01 | Define skill categories and levels | P2 |
| SM-02 | Record operator skill ratings per operation | P2 |
| SM-03 | Visual skill matrix grid (operator × skill) | P2 |
| SM-04 | Filter by line, department, factory | P2 |
| SM-05 | Skill gap identification | P3 |

### 5.12 Material Database Module

| ID | Requirement | Priority |
|----|-------------|----------|
| MAT-01 | Material master data (name, type, specification) | P2 |
| MAT-02 | Link materials to articles/products | P2 |
| MAT-03 | Material usage tracking per process | P3 |

### 5.13 VSM (Value Stream Mapping) Module

| ID | Requirement | Priority |
|----|-------------|----------|
| VSM-01 | Define value stream (process flow from raw to finished) | P3 |
| VSM-02 | Record lead time, cycle time, inventory at each stage | P3 |
| VSM-03 | Visual VSM diagram | P3 |
| VSM-04 | Current state vs. future state comparison | P3 |

### 5.14 Reporting & Analytics

| ID | Requirement | Priority |
|----|-------------|----------|
| RPT-01 | Dashboard with key metrics per module | P1 |
| RPT-02 | Export data to Excel/PDF | P1 |
| RPT-03 | Cross-module filtering (by factory, department, line, article, date range) | P1 |
| RPT-04 | Audit log — who changed what, when | P2 |

---

## 6. Non-Functional Requirements

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-01 | **Performance** — Page load time | < 2 seconds |
| NFR-02 | **Performance** — API response time | < 500ms for CRUD operations |
| NFR-03 | **Scalability** — Concurrent users | Support 50+ concurrent users |
| NFR-04 | **Availability** — Uptime | 99% during business hours |
| NFR-05 | **Security** — Authentication | Token-based (Sanctum), bcrypt hashing |
| NFR-06 | **Security** — Authorization | Role-based access control per module |
| NFR-07 | **Security** — Rate limiting | Login throttling (5 attempts/min) |
| NFR-08 | **Usability** — Responsive design | Works on desktop, tablet, mobile |
| NFR-09 | **Usability** — Browser support | Chrome, Edge, Firefox (latest 2 versions) |
| NFR-10 | **Maintainability** — Code quality | PSR-12 (PHP), ESLint (JS/TS) |
| NFR-11 | **Data integrity** — Validation | Server-side validation on all inputs |
| NFR-12 | **Backup** — Database | Daily automated backups |

---

## 7. Module Specifications

### 7.1 Module Dependency Map

```
┌─────────────────────────────────────────────────────────────┐
│                     MASTER DATA LAYER                        │
│  Factories → Departments → Lines → Operators → Articles      │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                  PROCESS LIBRARY LAYER                        │
│  Processes → Process Versions → GSD/MTM Elements             │
│  Sewing Factors · Sewing Stop Factors                        │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                 PRODUCTION ENGINEERING LAYER                  │
│  PTMS Reports → Operational Breakdown → Cycle Time           │
│  Line Balancing                                               │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                  IMPROVEMENT LAYER                            │
│  Kaizen → OSCP → Skill Matrix                                │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                 SUPPORTING LAYER                              │
│  TPM → Material Database → VSM                               │
└─────────────────────────────────────────────────────────────┘
```

### 7.2 Cross-Module Data Sharing

| Shared Data | Used By Modules |
|-------------|-----------------|
| Factory / Department / Line | All modules |
| Article / Product | PTMS, Line Balancing, Kaizen, VSM |
| Operator | PTMS, Skill Matrix, OSCP, Cycle Time |
| Process / Process Version | PTMS, Operational Breakdown, Line Balancing, VSM |
| GSD Elements | PTMS, Operational Breakdown |
| MTM Elements | PTMS, Operational Breakdown |
| Cycle Time | Line Balancing, VSM |

---

## 8. User Roles & Permissions

| Module | Developer | Admin | IE Engineer | Supervisor | Viewer |
|--------|:---------:|:-----:|:-----------:|:----------:|:------:|
| User Management | CRUD | CRUD | — | — | — |
| Master Data | CRUD | CRUD | CRUD | View | View |
| GSD & Process DB | CRUD | View | CRUD | View | View |
| PTMS | CRUD | View | CRUD | View/Approve | View |
| Operational Breakdown | CRUD | View | CRUD | View | View |
| Line Balancing | CRUD | View | CRUD | View | View |
| Cycle Time | CRUD | View | CRUD | View | View |
| Kaizen | CRUD | View | CRUD | View/Approve | View |
| OSCP | CRUD | View | CRUD | View | View |
| TPM | CRUD | CRUD | View | View | View |
| Skill Matrix | CRUD | View | CRUD | View | View |
| Material DB | CRUD | CRUD | View | View | View |
| VSM | CRUD | View | CRUD | View | View |
| Reports / Export | All | All | All | All | View/Export |
| System Config | Full | — | — | — | — |

---

## 9. UI/UX Requirements

| ID | Requirement |
|----|-------------|
| UX-01 | Collapsible sidebar navigation with module grouping |
| UX-02 | Consistent page layout: header → filters → data table → pagination |
| UX-03 | Modal-based forms for create/edit operations (no full page redirects) |
| UX-04 | Toast notifications for success/error feedback |
| UX-05 | Responsive design — usable on desktop (1280px+), tablet (768px+), mobile (375px+) |
| UX-06 | Loading states and skeleton screens during data fetching |
| UX-07 | Confirmation dialogs for destructive actions (delete) |
| UX-08 | Search and filter capabilities on all data tables |
| UX-09 | Dark mode support (optional, P2) |
| UX-10 | Keyboard shortcuts for common actions (P2) |

---

## 10. Success Criteria

The project is considered successful when:

| # | Criteria |
|---|----------|
| 1 | The system is accessible via web browser. |
| 2 | All P0 modules function according to requirements. |
| 3 | Data across modules is integrated via shared master data. |
| 4 | Users can input and retrieve data in a structured manner. |
| 5 | The system produces useful information for Lean/IE activities. |
| 6 | User trial is conducted and produces actionable feedback. |
| 7 | The system architecture supports incremental module development. |
| 8 | The UI renders correctly on desktop, tablet, and mobile. |

---

## 11. Constraints & Assumptions

### Constraints

- Development is incremental — not all modules will be built at once.
- The system must work within the company's existing infrastructure (XAMPP/local server initially, cloud later).
- Budget and timeline constraints prioritize P0 and P1 modules first.

### Assumptions

- Users have basic computer literacy and web browser access.
- Network connectivity is available at the factory site.
- Master data (factories, departments, lines) is relatively stable and changes infrequently.
- IE engineers will be the primary data entry users.
- The system will initially serve one factory, with multi-factory support as a future enhancement.

---

## 12. Out of Scope

The following are explicitly out of scope for the initial development:

- ERP/SAP integration
- Real-time IoT data collection from machines
- Payroll/HR system integration
- Accounting/finance modules
- Multi-language support (initially Indonesian + English only)
- Native mobile applications (responsive web only)
- Offline mode

---

*End of Product Requirement Document*
