# System Architecture

## LEAN ENTERPRISE (LIMS)

> **Version:** 1.0  
> **Date:** 2026-09-04  
> **Status:** Planned (migration from Blade to SPA)  

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Technology Stack](#2-technology-stack)
3. [High-Level Architecture Diagram](#3-high-level-architecture-diagram)
4. [Backend Architecture (Laravel 10)](#4-backend-architecture-laravel-10)
5. [Frontend Architecture (Next.js)](#5-frontend-architecture-nextjs)
6. [State Management](#6-state-management)
7. [Authentication Flow](#7-authentication-flow)
8. [API Design Principles](#8-api-design-principles)
9. [Database Architecture](#9-database-architecture)
10. [Deployment Architecture](#10-deployment-architecture)
11. [Development Workflow](#11-development-workflow)
12. [Security Architecture](#12-security-architecture)
13. [Performance Considerations](#13-performance-considerations)
14. [Migration Path (Blade → SPA)](#14-migration-path-blade--spa)

---

## 1. Architecture Overview

LEAN ENTERPRISE uses a **decoupled (headless) architecture** where the frontend and backend are separate applications that communicate via a RESTful API.

```
┌─────────────────────┐         ┌─────────────────────┐
│   FRONTEND (SPA)    │         │    BACKEND (API)     │
│                     │         │                     │
│   Next.js 14+       │◄──REST──►   Laravel 10        │
│   React 18+         │   API   │   PHP 8.1+         │
│   Tailwind CSS      │         │   MySQL 8           │
│   React Query       │         │   Sanctum Auth      │
│   Zustand           │         │                     │
│                     │         │                     │
│   Port: 3000        │         │   Port: 8000        │
└─────────────────────┘         └─────────────────────┘
```

**Why Decoupled?**

- **Independent deployment** — Frontend and backend can be deployed, scaled, and updated independently.
- **Technology flexibility** — Frontend can evolve to React Native (mobile) without changing the backend.
- **Better DX** — Next.js provides hot reload, SSR/SSG, and a modern React ecosystem.
- **API-first** — The same API serves the web app, and can later serve mobile apps or third-party integrations.

---

## 2. Technology Stack

### Backend

| Component | Technology | Version | Purpose |
|-----------|-----------|---------|---------|
| Language | PHP | 8.1+ | Server-side language |
| Framework | Laravel | 10.x | API framework, ORM, auth, queue |
| Database | MySQL | 8.x | Primary data store |
| Auth | Laravel Sanctum | 3.x | Token-based API authentication |
| ORM | Eloquent | (Laravel) | Database abstraction |
| Validation | Laravel Form Requests | (Laravel) | Input validation |
| Queue | Laravel Queue | (Laravel) | Background jobs (future) |
| Testing | PHPUnit | 10.x | Backend unit & feature tests |

### Frontend

| Component | Technology | Version | Purpose |
|-----------|-----------|---------|---------|
| Framework | Next.js | 14+ (App Router) | React meta-framework with SSR/SSG |
| UI Library | React | 18+ | Component-based UI |
| Styling | Tailwind CSS | 3.x | Utility-first CSS |
| Components | shadcn/ui / Radix UI | Latest | Accessible UI primitives |
| Server State | React Query (TanStack Query) | 5.x | API data fetching, caching, sync |
| Client State | Zustand | Latest | Global client-side state |
| HTTP Client | Axios | 1.x | API communication |
| Forms | React Hook Form | 7.x | Form state management |
| Validation | Zod | 3.x | Client-side schema validation |
| Tables | TanStack Table | 8.x | Data table rendering & logic |
| Charts | Recharts | 2.x | Data visualization |
| Icons | Lucide React | Latest | Icon library |

### DevOps & Tooling

| Component | Technology | Purpose |
|-----------|-----------|---------|
| Package Manager (BE) | Composer | PHP dependency management |
| Package Manager (FE) | pnpm | Node.js dependency management |
| Build Tool | Next.js built-in (Turbopack) | Frontend bundling |
| Linting (BE) | Laravel Pint | PHP code style |
| Linting (FE) | ESLint + Prettier | JS/TS code quality |
| Type Checking | TypeScript | Frontend type safety |
| Version Control | Git | Source control |
| CI/CD | GitHub Actions (future) | Automated testing & deployment |

---

## 3. High-Level Architecture Diagram

```
                          ┌──────────────┐
                          │   Browser    │
                          │  (User)      │
                          └──────┬───────┘
                                 │
                                 ▼
                    ┌────────────────────────┐
                    │    Next.js Frontend    │
                    │    (SSR + CSR)         │
                    │                        │
                    │  ┌──────────────────┐  │
                    │  │   React Query    │  │  ← Server state (API cache)
                    │  │   Zustand Store  │  │  ← Client state (UI state)
                    │  │   React Hook Form│  │  ← Form state
                    │  └──────────────────┘  │
                    │                        │
                    │  Tailwind CSS + shadcn  │
                    └───────────┬────────────┘
                                │
                          HTTP/REST (JSON)
                                │
                                ▼
                    ┌────────────────────────┐
                    │   Laravel 10 Backend   │
                    │   (API Only)           │
                    │                        │
                    │  ┌──────────────────┐  │
                    │  │  Routes (api.php) │  │
                    │  │  Controllers      │  │
                    │  │  Form Requests    │  │
                    │  │  Resources (JSON) │  │
                    │  │  Services         │  │
                    │  │  Eloquent Models  │  │
                    │  └──────────────────┘  │
                    │                        │
                    │  Sanctum Auth Middleware│
                    └───────────┬────────────┘
                                │
                                ▼
                    ┌────────────────────────┐
                    │      MySQL 8           │
                    │      (lean_ie DB)      │
                    │                        │
                    │  18+ tables across     │
                    │  12 functional modules │
                    └────────────────────────┘
```

---

## 4. Backend Architecture (Laravel 10)

### 4.1 Project Structure

```
app/
├── Console/
│   └── Kernel.php
├── Exceptions/
│   └── Handler.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── FactoryController.php
│   │       ├── DepartmentController.php
│   │       ├── ProductionLineController.php
│   │       ├── ArticleController.php
│   │       ├── OperatorController.php
│   │       ├── ProcessController.php
│   │       ├── ProcessVersionController.php
│   │       ├── GsdCategoryController.php
│   │       ├── GsdElementController.php
│   │       ├── MtmElementController.php
│   │       ├── SewingFactorController.php
│   │       ├── SewingStopFactorController.php
│   │       ├── PtmsReportController.php
│   │       ├── UserController.php
│   │       └── DashboardController.php
│   ├── Middleware/
│   │   ├── Authenticate.php
│   │   ├── RoleMiddleware.php
│   │   └── EnsureJsonAcceptHeader.php
│   ├── Requests/
│   │   └── Api/
│   │       ├── LoginRequest.php
│   │       ├── StoreUserRequest.php
│   │       ├── UpdateUserRequest.php
│   │       ├── StorePtmsReportRequest.php
│   │       └── ... (one per controller action)
│   └── Resources/
│       └── Api/
│           ├── UserResource.php
│           ├── FactoryResource.php
│           ├── DepartmentResource.php
│           ├── ProductionLineResource.php
│           ├── ArticleResource.php
│           ├── OperatorResource.php
│           ├── ProcessResource.php
│           ├── ProcessVersionResource.php
│           ├── GsdCategoryResource.php
│           ├── GsdElementResource.php
│           ├── MtmElementResource.php
│           ├── SewingFactorResource.php
│           ├── SewingStopFactorResource.php
│           └── PtmsReportResource.php
├── Models/
│   ├── Role.php
│   ├── User.php
│   ├── Factory.php
│   ├── Department.php
│   ├── ProductionLine.php
│   ├── Article.php
│   ├── Operator.php
│   ├── Process.php
│   ├── ProcessVersion.php
│   ├── GsdCategory.php
│   ├── GsdElement.php
│   ├── MtmElement.php
│   ├── SewingFactor.php
│   ├── SewingStopFactor.php
│   └── PtmsReport.php
├── Policies/                    ← (future)
│   └── ...
├── Services/                    ← (future, business logic layer)
│   ├── PtmsCalculationService.php
│   └── LineBalancingService.php
└── Providers/
    ├── AppServiceProvider.php
    ├── AuthServiceProvider.php
    ├── RouteServiceProvider.php
    └── ...
```

### 4.2 Architecture Layers

```
┌─────────────────────────────────────────────┐
│              Routes (api.php)                │  ← HTTP entry point
├─────────────────────────────────────────────┤
│          Middleware (auth, role)              │  ← Auth & authorization
├─────────────────────────────────────────────┤
│           Controllers (Api/)                 │  ← Request handling
├─────────────────────────────────────────────┤
│     Form Requests (validation)               │  ← Input validation
├─────────────────────────────────────────────┤
│     Services (business logic)                │  ← Complex calculations
├─────────────────────────────────────────────┤
│     Eloquent Models (data access)            │  ← ORM / database
├─────────────────────────────────────────────┤
│     JSON Resources (response shaping)        │  ← API response format
├─────────────────────────────────────────────┤
│     MySQL Database                           │  ← Persistence
└─────────────────────────────────────────────┘
```

### 4.3 Design Patterns

| Pattern | Usage |
|---------|-------|
| **Repository Pattern** | Optional — use Eloquent directly for simple CRUD, Services for complex logic |
| **Service Layer** | Complex calculations (PTMS, line balancing) encapsulated in Service classes |
| **Form Request** | Every controller action uses a dedicated FormRequest for validation |
| **API Resource** | Every model has a JsonResource to control response shape |
| **Policy** | Authorization logic per model (future enhancement) |
| **Observer** | Audit logging on model changes (future enhancement) |

---

## 5. Frontend Architecture (Next.js)

### 5.1 Project Structure

```
src/
├── app/                          ← Next.js App Router
│   ├── layout.tsx                ← Root layout (providers, fonts)
│   ├── page.tsx                  ← Landing / redirect to dashboard
│   ├── (auth)/                   ← Auth route group
│   │   ├── login/
│   │   │   └── page.tsx
│   │   └── layout.tsx            ← Auth layout (no sidebar)
│   ├── (dashboard)/              ← Protected route group
│   │   ├── layout.tsx            ← Dashboard layout (sidebar + header)
│   │   ├── dashboard/
│   │   │   └── page.tsx
│   │   ├── master-data/
│   │   │   ├── factories/
│   │   │   │   └── page.tsx
│   │   │   ├── departments/
│   │   │   │   └── page.tsx
│   │   │   ├── lines/
│   │   │   │   └── page.tsx
│   │   │   ├── articles/
│   │   │   │   └── page.tsx
│   │   │   ├── operators/
│   │   │   │   └── page.tsx
│   │   │   └── layout.tsx
│   │   ├── process-library/
│   │   │   ├── processes/
│   │   │   ├── gsd/
│   │   │   ├── mtm/
│   │   │   ├── sewing-factors/
│   │   │   └── page.tsx
│   │   ├── ptms/
│   │   │   ├── page.tsx          ← PTMS list
│   │   │   └── [id]/
│   │   │       └── page.tsx      ← PTMS detail
│   │   ├── credentials/          ← User management
│   │   │   └── page.tsx
│   │   └── profile/
│   │       └── page.tsx
│   └── not-found.tsx
├── components/
│   ├── ui/                       ← shadcn/ui components
│   │   ├── button.tsx
│   │   ├── input.tsx
│   │   ├── dialog.tsx
│   │   ├── table.tsx
│   │   ├── select.tsx
│   │   ├── badge.tsx
│   │   ├── toast.tsx
│   │   ├── dropdown-menu.tsx
│   │   ├── sheet.tsx
│   │   └── skeleton.tsx
│   ├── layout/
│   │   ├── Sidebar.tsx
│   │   ├── Header.tsx
│   │   ├── PageContainer.tsx
│   │   └── MobileNav.tsx
│   ├── data-table/
│   │   ├── DataTable.tsx
│   │   ├── DataTablePagination.tsx
│   │   ├── DataTableSearch.tsx
│   │   └── DataTableColumnHeader.tsx
│   ├── forms/
│   │   ├── LoginForm.tsx
│   │   ├── UserForm.tsx
│   │   ├── PtmsReportForm.tsx
│   │   └── ...
│   └── shared/
│       ├── ConfirmDialog.tsx
│       ├── LoadingSpinner.tsx
│       ├── EmptyState.tsx
│       └── StatusBadge.tsx
├── lib/
│   ├── api/
│   │   ├── client.ts             ← Axios instance with interceptors
│   │   ├── auth.ts               ← Login, logout, me
│   │   ├── factories.ts          ← Factory CRUD
│   │   ├── departments.ts        ← Department CRUD
│   │   ├── operators.ts          ← Operator CRUD
│   │   ├── articles.ts           ← Article CRUD
│   │   ├── processes.ts          ← Process CRUD
│   │   ├── gsd.ts                ← GSD CRUD
│   │   ├── mtm.ts                ← MTM CRUD
│   │   ├── ptms.ts               ← PTMS CRUD
│   │   └── ...
│   ├── hooks/
│   │   ├── useAuth.ts            ← Auth hook (wraps React Query)
│   │   ├── useFactories.ts       ← Factory hooks (useQuery + useMutation)
│   │   ├── useDepartments.ts
│   │   ├── useOperators.ts
│   │   ├── useArticles.ts
│   │   ├── useProcesses.ts
│   │   ├── useGsd.ts
│   │   ├── useMtm.ts
│   │   ├── usePtms.ts
│   │   └── ...
│   ├── stores/
│   │   ├── useAuthStore.ts       ← Zustand: auth state (user, token)
│   │   ├── useSidebarStore.ts    ← Zustand: sidebar collapsed/expanded
│   │   ├── useFilterStore.ts     ← Zustand: global filter state
│   │   └── useToastStore.ts      ← Zustand: toast notifications
│   ├── schemas/
│   │   ├── login.ts              ← Zod schema for login
│   │   ├── user.ts               ← Zod schema for user CRUD
│   │   ├── ptms.ts               ← Zod schema for PTMS
│   │   └── ...
│   ├── utils/
│   │   ├── cn.ts                 ← clsx + tailwind-merge utility
│   │   ├── format.ts             ← Number/date formatting
│   │   └── constants.ts          ← App-wide constants
│   └── types/
│       ├── index.ts              ← Shared TypeScript types
│       ├── user.ts
│       ├── factory.ts
│       ├── ptms.ts
│       └── ...
├── providers/
│   ├── QueryProvider.tsx         ← React Query Provider
│   ├── AuthProvider.tsx          ← Auth context / hydration
│   └── ToastProvider.tsx         ← Toast notification provider
└── middleware.ts                 ← Next.js middleware (auth redirect)
```

### 5.2 Page Architecture Pattern

Every data page follows this structure:

```
┌─────────────────────────────────────────────────────┐
│  Page Header (title + description + action buttons)  │
├─────────────────────────────────────────────────────┤
│  Filters (search, dropdowns, date range)             │
├─────────────────────────────────────────────────────┤
│  Data Table (sortable, filterable, paginated)        │
│  ┌─────┬──────────┬──────────┬─────────┬──────────┐ │
│  │ #   │ Name     │ Status   │ Actions │ ...      │ │
│  ├─────┼──────────┼──────────┼─────────┼──────────┤ │
│  │ 1   │ ...      │ Active   │ Edit ▼  │          │ │
│  └─────┴──────────┴──────────┴─────────┴──────────┘ │
├─────────────────────────────────────────────────────┤
│  Pagination (showing X of Y, page controls)          │
└─────────────────────────────────────────────────────┘

+ Create/Edit → Dialog/Sheet with form
+ Delete → Confirmation dialog
```

### 5.3 Component Architecture

```
Page Component
├── uses React Query hook (useFactories, etc.)
├── renders DataTable component
│   ├── columns defined with TanStack Table
│   ├── sorting, filtering, pagination handled by TanStack Table
│   └── row actions (edit, delete) trigger mutations
├── renders Create/Edit Dialog
│   ├── form managed by React Hook Form
│   ├── validation by Zod schema
│   └── submit triggers useMutation hook
└── renders Delete ConfirmDialog
    └── confirm triggers delete mutation
```

---

## 6. State Management

### 6.1 State Classification

| State Type | Tool | Examples |
|-----------|------|----------|
| **Server State** | React Query | API data (factories, operators, PTMS reports, etc.) |
| **Client State (global)** | Zustand | Auth state, sidebar state, global filters |
| **Client State (local)** | React `useState` | Form state, modal open/close, UI toggles |
| **Form State** | React Hook Form | All form inputs, validation state, dirty/touched |
| **URL State** | Next.js `searchParams` | Table page number, sort order, active filters |

### 6.2 React Query Usage

```typescript
// Example: useFactories.ts
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import * as api from '@/lib/api/factories';

export function useFactories(params?: ListParams) {
  return useQuery({
    queryKey: ['factories', params],
    queryFn: () => api.list(params),
  });
}

export function useFactory(id: number) {
  return useQuery({
    queryKey: ['factories', id],
    queryFn: () => api.show(id),
    enabled: !!id,
  });
}

export function useCreateFactory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: api.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['factories'] });
    },
  });
}

export function useUpdateFactory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: api.update,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['factories'] });
    },
  });
}

export function useDeleteFactory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: api.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['factories'] });
    },
  });
}
```

**React Query provides:**
- Automatic caching with stale-while-revalidate
- Background refetching
- Optimistic updates
- Loading/error states
- Pagination support
- Query invalidation on mutations

### 6.3 Zustand Usage

```typescript
// stores/useAuthStore.ts
import { create } from 'zustand';
import { persist } from 'zustand/middleware';

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  login: (user: User, token: string) => void;
  logout: () => void;
  setUser: (user: User) => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      login: (user, token) => set({ user, token, isAuthenticated: true }),
      logout: () => set({ user: null, token: null, isAuthenticated: false }),
      setUser: (user) => set({ user }),
    }),
    { name: 'auth-storage' }
  )
);
```

```typescript
// stores/useSidebarStore.ts
import { create } from 'zustand';

interface SidebarState {
  collapsed: boolean;
  toggle: () => void;
}

export const useSidebarStore = create<SidebarState>((set) => ({
  collapsed: false,
  toggle: () => set((state) => ({ collapsed: !state.collapsed })),
}));
```

**Zustand manages:**
- Auth state (user object, token, isAuthenticated) — persisted to localStorage
- Sidebar collapsed/expanded state — persisted to localStorage
- Global filter state (factory, department, line selections)
- Toast notification queue

---

## 7. Authentication Flow

```
┌──────────┐     POST /api/login      ┌──────────┐
│  Client  │ ────────────────────────► │  Server  │
│          │   { username, password }  │          │
│          │                           │          │
│          │ ◄──────────────────────── │          │
│          │   { token, user }         │          │
└────┬─────┘                          └──────────┘
     │
     │  1. Store token in Zustand (persisted to localStorage)
     │  2. Set Authorization: Bearer <token> header
     │  3. Fetch user data via /api/me
     │
     ▼
┌──────────────────────────────────────────────┐
│  Subsequent API calls                         │
│                                               │
│  GET /api/factories                           │
│  Headers:                                     │
│    Authorization: Bearer <token>              │
│    Accept: application/json                   │
│    Content-Type: application/json             │
└──────────────────────────────────────────────┘
```

### Token Management

- **Storage:** Zustand store with `persist` middleware → localStorage
- **Header:** Axios interceptor attaches `Authorization: Bearer <token>` to every request
- **Expiry:** Backend returns 401 → Axios interceptor clears auth state → Redirect to login
- **Refresh:** No token refresh needed with Sanctum personal access tokens (set long expiry or re-login)

### Axios Interceptor Setup

```typescript
// lib/api/client.ts
import axios from 'axios';
import { useAuthStore } from '@/stores/useAuthStore';

const client = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

// Request interceptor — attach token
client.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor — handle 401
client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().logout();
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default client;
```

---

## 8. API Design Principles

See [10_api.md](./10_api.md) for full API specification.

### Summary

| Principle | Implementation |
|-----------|---------------|
| **RESTful** | Standard HTTP methods (GET, POST, PUT, DELETE) |
| **JSON:API-like** | Consistent response envelope `{ data, meta, links }` |
| **Versioned** | All routes under `/api/v1/` |
| **Authenticated** | Sanctum bearer tokens |
| **Paginated** | Cursor or page-based pagination |
| **Filterable** | Query parameters for filtering |
| **Sortable** | `?sort=field&order=asc` |
| **Validated** | Form Request validation on every endpoint |
| **Rate Limited** | 60 requests/minute per user |

---

## 9. Database Architecture

See [07_database_schema.md](./07_database_schema.md) for full schema.

### Key Principles

- **InnoDB engine** — Foreign key support, transactions
- **utf8mb4 charset** — Full Unicode support
- **Soft deletes** — On critical tables (users, articles, processes) — future enhancement
- **Timestamps** — `created_at`, `updated_at` on all tables
- **Foreign keys** — Enforced at database level with RESTRICT/CASCADE rules
- **Indexes** — On all foreign keys, unique constraints, and frequently queried columns

---

## 10. Deployment Architecture

### Development

```
Developer Machine
├── XAMPP (MySQL + Apache)
├── php artisan serve (Laravel backend, port 8000)
└── pnpm dev (Next.js frontend, port 3000)
```

### Production (Target)

```
┌─────────────────────────────────────────────┐
│                Load Balancer                 │
│              (Nginx / Caddy)                 │
└────────┬──────────────────────┬─────────────┘
         │                      │
         ▼                      ▼
┌─────────────────┐    ┌─────────────────┐
│  Next.js App    │    │  Laravel API    │
│  (Node.js)      │    │  (PHP-FPM)      │
│  Port: 3000     │    │  Port: 9000     │
└─────────────────┘    └────────┬────────┘
                                │
                                ▼
                       ┌─────────────────┐
                       │  MySQL 8        │
                       │  Port: 3306     │
                       └─────────────────┘
```

### Environment Variables

**Backend (.env):**
```env
APP_NAME="LEAN ENTERPRISE"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lean_ie
DB_USERNAME=app_user
DB_PASSWORD=strong_password

SANCTUM_STATEFUL_DOMAINS=yourdomain.com,localhost:3000
SESSION_DOMAIN=yourdomain.com

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

**Frontend (.env.local):**
```env
NEXT_PUBLIC_API_URL=https://api.yourdomain.com/api
```

---

## 11. Development Workflow

```
┌────────────────────────────────────────────────────────────┐
│                    Development Cycle                        │
│                                                            │
│   1. Design                                                │
│      └─ Define DB schema, API contract, UI wireframe       │
│                                                            │
│   2. Backend First                                         │
│      ├─ Create migration + model                           │
│      ├─ Create Form Request + Controller                   │
│      ├─ Create API Resource                                │
│      ├─ Define routes in api.php                           │
│      └─ Test with Postman / Insomnia                       │
│                                                            │
│   3. Frontend Second                                       │
│      ├─ Define TypeScript types from API response          │
│      ├─ Create API client functions                        │
│      ├─ Create React Query hooks                           │
│      ├─ Build page components                              │
│      └─ Build forms with React Hook Form + Zod             │
│                                                            │
│   4. Integration Testing                                   │
│      └─ Test full flow: UI → API → DB → API → UI          │
│                                                            │
│   5. Commit & Deploy                                       │
│      └─ Git commit → push → deploy                         │
└────────────────────────────────────────────────────────────┘
```

---

## 12. Security Architecture

| Layer | Measure |
|-------|---------|
| **Transport** | HTTPS in production |
| **Authentication** | Sanctum bearer tokens with expiry |
| **Authorization** | RoleMiddleware checks role_name on every route |
| **Input Validation** | Form Requests validate all input server-side |
| **SQL Injection** | Eloquent ORM parameterized queries |
| **XSS** | React auto-escapes output; Laravel API returns JSON only |
| **CSRF** | Not needed for API-only with bearer tokens |
| **Rate Limiting** | 60 req/min per user, 5 login attempts/min |
| **Password** | Bcrypt hashing (12 rounds in production) |
| **CORS** | Restricted to frontend domain only |
| **Secrets** | APP_KEY, DB_PASSWORD in .env (never committed) |
| **Audit** | Audit log table tracks all data changes (future) |

---

## 13. Performance Considerations

| Area | Strategy |
|------|----------|
| **API Response** | Eager loading (`with()`) to avoid N+1 queries |
| **API Pagination** | Limit default 15 items per page, max 100 |
| **Frontend Caching** | React Query caches with stale-while-revalidate |
| **Frontend Bundle** | Next.js code splitting per route |
| **Images** | Next.js Image component with optimization |
| **Database** | Indexes on all FK and frequently queried columns |
| **CSS** | Tailwind purges unused styles in production |
| **Fonts** | Self-hosted Inter font with `next/font` |

---

## 14. Migration Path (Blade → SPA)

The current codebase uses Laravel Blade templates. The migration to Next.js SPA will be incremental:

### Phase 1 — API Foundation *(Current → Next)*
1. Create API routes in `routes/api.php`
2. Create API controllers (mirror existing `CredentialController`, `ProfileController`)
3. Create Form Requests and API Resources
4. Test API with Postman
5. Keep Blade views working alongside API

### Phase 2 — Frontend Bootstrap
1. Initialize Next.js project with TypeScript
2. Set up Tailwind CSS, shadcn/ui, React Query, Zustand
3. Build auth pages (login)
4. Build main layout (sidebar, header)
5. Connect to Laravel API

### Phase 3 — Page Migration
1. Migrate Dashboard page
2. Migrate Credentials/User management page
3. Migrate each master data page (factories, departments, lines, articles, operators)
4. Migrate Process Library pages (processes, GSD, MTM, factors)
5. Migrate PTMS pages

### Phase 4 — Cleanup
1. Remove Blade views
2. Remove `web.php` routes (except API redirect)
3. Laravel becomes pure API backend

```
CURRENT STATE           TRANSITION              TARGET STATE
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│  Laravel     │       │  Laravel     │       │  Next.js     │
│  Blade Views │  ───► │  Blade + API │  ───► │  SPA Only    │
│  + Web Routes│       │  + Next.js   │       │  + Laravel   │
│              │       │  (both)      │       │  API Only    │
└──────────────┘       └──────────────┘       └──────────────┘
```

---

*End of System Architecture*
