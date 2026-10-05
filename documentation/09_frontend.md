# Frontend Implementation Guide

## LEAN ENTERPRISE (LIMS)

> **Version:** 1.0  
> **Date:** 2026-09-04  
> **Framework:** Next.js 14+ (App Router)  
> **Styling:** Tailwind CSS + shadcn/ui  
> **Language:** TypeScript  

---

## Table of Contents

1. [Project Setup](#1-project-setup)
2. [Project Structure](#2-project-structure)
3. [Routing Architecture](#3-routing-architecture)
4. [Layout System](#4-layout-system)
5. [Component Library](#5-component-library)
6. [Page Implementations](#6-page-implementations)
7. [Data Fetching Patterns](#7-data-fetching-patterns)
8. [Form Patterns](#8-form-patterns)
9. [Data Table Implementation](#9-data-table-implementation)
10. [Authentication UI](#10-authentication-ui)
11. [Responsive Design](#11-responsive-design)
12. [Loading & Error States](#12-loading--error-states)
13. [Toast Notifications](#13-toast-notifications)
14. [Accessibility](#14-accessibility)
15. [Performance Optimization](#15-performance-optimization)

---

## 1. Project Setup

### 1.1 Initialize Project

```bash
npx create-next-app@latest lems-frontend --typescript --tailwind --eslint --app --src-dir --import-alias "@/*"
cd lems-frontend
```

### 1.2 Install Dependencies

```bash
# Core dependencies
pnpm add @tanstack/react-query @tanstack/react-query-devtools zustand axios react-hook-form @hookform/resolvers zod

# UI library (shadcn/ui)
pnpm dlx shadcn-ui@latest init

# shadcn/ui components
pnpm dlx shadcn-ui@latest add button input label card dialog dropdown-menu select table badge toast separator sheet skeleton avatar tabs textarea command popover calendar

# Additional UI
pnpm add lucide-react recharts @tanstack/react-table date-fns clsx tailwind-merge
```

### 1.3 Environment Variables

```env
# .env.local
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_APP_NAME=LEAN ENTERPRISE
```

### 1.4 Tailwind Configuration

```typescript
// tailwind.config.ts
import type { Config } from 'tailwindcss';

const config: Config = {
  darkMode: ['class'],
  content: [
    './src/pages/**/*.{js,ts,jsx,tsx,mdx}',
    './src/components/**/*.{js,ts,jsx,tsx,mdx}',
    './src/app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
          950: '#172554',
        },
        sidebar: {
          bg: '#0f172a',       // slate-900
          hover: '#1e293b',    // slate-800
          active: '#2563eb',   // primary-600
          text: '#94a3b8',     // slate-400
          'text-active': '#ffffff',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [require('tailwindcss-animate')],
};

export default config;
```

---

## 2. Project Structure

```
src/
├── app/
│   ├── layout.tsx                    ← Root layout (fonts, providers)
│   ├── page.tsx                      ← Redirect to /dashboard
│   ├── globals.css                   ← Tailwind base + custom CSS
│   ├── not-found.tsx                 ← 404 page
│   │
│   ├── (auth)/                       ← Auth route group (no sidebar)
│   │   ├── layout.tsx                ← Centered auth layout
│   │   └── login/
│   │       └── page.tsx              ← Login page
│   │
│   ├── (dashboard)/                  ← Protected route group (sidebar + header)
│   │   ├── layout.tsx                ← Dashboard layout
│   │   ├── loading.tsx               ← Dashboard loading skeleton
│   │   │
│   │   ├── dashboard/
│   │   │   └── page.tsx              ← Dashboard home
│   │   │
│   │   ├── master-data/
│   │   │   ├── layout.tsx            ← Master data section layout
│   │   │   ├── factories/
│   │   │   │   ├── page.tsx          ← Factory list
│   │   │   │   └── loading.tsx
│   │   │   ├── departments/
│   │   │   │   ├── page.tsx
│   │   │   │   └── loading.tsx
│   │   │   ├── lines/
│   │   │   │   ├── page.tsx
│   │   │   │   └── loading.tsx
│   │   │   ├── articles/
│   │   │   │   ├── page.tsx
│   │   │   │   └── loading.tsx
│   │   │   └── operators/
│   │   │       ├── page.tsx
│   │   │       └── loading.tsx
│   │   │
│   │   ├── process-library/
│   │   │   ├── layout.tsx
│   │   │   ├── processes/
│   │   │   │   └── page.tsx
│   │   │   ├── gsd/
│   │   │   │   ├── categories/
│   │   │   │   │   └── page.tsx
│   │   │   │   └── elements/
│   │   │   │       └── page.tsx
│   │   │   ├── mtm/
│   │   │   │   └── page.tsx
│   │   │   ├── sewing-factors/
│   │   │   │   └── page.tsx
│   │   │   └── stop-factors/
│   │   │       └── page.tsx
│   │   │
│   │   ├── ptms/
│   │   │   ├── page.tsx              ← PTMS report list
│   │   │   ├── loading.tsx
│   │   │   └── [id]/
│   │   │       └── page.tsx          ← PTMS report detail
│   │   │
│   │   ├── credentials/
│   │   │   └── page.tsx              ← User management
│   │   │
│   │   └── profile/
│   │       └── page.tsx              ← User profile
│   │
│   └── api/                          ← Next.js API routes (rarely used)
│       └── health/
│           └── route.ts
│
├── components/
│   ├── ui/                           ← shadcn/ui primitives (auto-generated)
│   │   ├── button.tsx
│   │   ├── input.tsx
│   │   ├── label.tsx
│   │   ├── card.tsx
│   │   ├── dialog.tsx
│   │   ├── dropdown-menu.tsx
│   │   ├── select.tsx
│   │   ├── table.tsx
│   │   ├── badge.tsx
│   │   ├── separator.tsx
│   │   ├── sheet.tsx
│   │   ├── skeleton.tsx
│   │   ├── avatar.tsx
│   │   ├── tabs.tsx
│   │   ├── toast.tsx
│   │   ├── toaster.tsx
│   │   ├── textarea.tsx
│   │   └── use-toast.ts
│   │
│   ├── layout/
│   │   ├── AppSidebar.tsx            ← Main sidebar navigation
│   │   ├── SidebarGroup.tsx          ← Collapsible sidebar group
│   │   ├── SidebarItem.tsx           ← Individual nav item
│   │   ├── Header.tsx                ← Top header bar
│   │   ├── UserMenu.tsx              ← User dropdown (profile, logout)
│   │   ├── PageHeader.tsx            ← Page title + breadcrumb + actions
│   │   ├── PageContainer.tsx         ← Content wrapper
│   │   ├── MobileNav.tsx             ← Mobile sidebar (Sheet)
│   │   └── Breadcrumbs.tsx           ← Breadcrumb navigation
│   │
│   ├── data-table/
│   │   ├── DataTable.tsx             ← Reusable data table
│   │   ├── DataTableColumnHeader.tsx ← Sortable column header
│   │   ├── DataTablePagination.tsx   ← Pagination controls
│   │   ├── DataTableSearch.tsx       ← Search input
│   │   ├── DataTableFilter.tsx       ← Column filters
│   │   ├── DataTableToolbar.tsx      ← Toolbar (search + filters + actions)
│   │   ├── DataTableRowActions.tsx    ← Row action dropdown
│   │   └── DataTableEmpty.tsx        ← Empty state
│   │
│   ├── forms/
│   │   ├── FormField.tsx             ← Generic form field wrapper
│   │   ├── FormSelect.tsx            ← Select field with API data
│   │   ├── FormDatePicker.tsx        ← Date picker field
│   │   ├── FormCombobox.tsx          ← Searchable combobox
│   │   └── FormSubmitButton.tsx      ← Submit button with loading state
│   │
│   ├── shared/
│   │   ├── ConfirmDialog.tsx         ← Confirmation dialog
│   │   ├── LoadingSpinner.tsx        ← Loading indicator
│   │   ├── EmptyState.tsx            ← Empty state illustration
│   │   ├── ErrorState.tsx            ← Error state with retry
│   │   ├── StatusBadge.tsx           ← Colored status badge
│   │   ├── SearchInput.tsx           ← Debounced search input
│   │   └── PageSkeleton.tsx          ← Page loading skeleton
│   │
│   └── dashboard/
│       ├── StatsCards.tsx            ← Dashboard stat cards
│       ├── RecentActivity.tsx        ← Recent activity feed
│       └── QuickActions.tsx          ← Quick action buttons
│
├── lib/
│   ├── api/
│   │   ├── client.ts                 ← Axios instance + interceptors
│   │   ├── types.ts                  ← API response types
│   │   ├── auth.ts                   ← Auth API (login, logout, me)
│   │   ├── factories.ts              ← Factory CRUD API
│   │   ├── departments.ts            ← Department CRUD API
│   │   ├── production-lines.ts       ← Production line CRUD API
│   │   ├── articles.ts               ← Article CRUD API
│   │   ├── operators.ts              ← Operator CRUD API
│   │   ├── processes.ts              ← Process CRUD API
│   │   ├── process-versions.ts       ← Process version CRUD API
│   │   ├── gsd-categories.ts         ← GSD category CRUD API
│   │   ├── gsd-elements.ts           ← GSD element CRUD API
│   │   ├── mtm-elements.ts           ← MTM element CRUD API
│   │   ├── sewing-factors.ts         ← Sewing factor CRUD API
│   │   ├── sewing-stop-factors.ts    ← Sewing stop factor CRUD API
│   │   ├── ptms-reports.ts           ← PTMS report CRUD API
│   │   └── users.ts                  ← User CRUD API
│   │
│   ├── hooks/
│   │   ├── useAuth.ts                ← Auth hook (login, logout, current user)
│   │   ├── useFactories.ts           ← Factory CRUD hooks
│   │   ├── useDepartments.ts         ← Department CRUD hooks
│   │   ├── useProductionLines.ts     ← Production line CRUD hooks
│   │   ├── useArticles.ts            ← Article CRUD hooks
│   │   ├── useOperators.ts           ← Operator CRUD hooks
│   │   ├── useProcesses.ts           ← Process CRUD hooks
│   │   ├── useProcessVersions.ts     ← Process version CRUD hooks
│   │   ├── useGsdCategories.ts       ← GSD category CRUD hooks
│   │   ├── useGsdElements.ts         ← GSD element CRUD hooks
│   │   ├── useMtmElements.ts         ← MTM element CRUD hooks
│   │   ├── useSewingFactors.ts       ← Sewing factor CRUD hooks
│   │   ├── useSewingStopFactors.ts   ← Sewing stop factor CRUD hooks
│   │   ├── usePtmsReports.ts         ← PTMS report CRUD hooks
│   │   ├── useUsers.ts               ← User CRUD hooks
│   │   ├── useDashboard.ts           ← Dashboard stats hook
│   │   └── useDebounce.ts            ← Debounce hook for search
│   │
│   ├── stores/
│   │   ├── useAuthStore.ts           ← Zustand: auth (user, token)
│   │   ├── useSidebarStore.ts        ← Zustand: sidebar state
│   │   └── useFilterStore.ts         ← Zustand: global filters
│   │
│   ├── schemas/
│   │   ├── login.ts                  ← Login form Zod schema
│   │   ├── user.ts                   ← User form Zod schema
│   │   ├── factory.ts                ← Factory form Zod schema
│   │   ├── department.ts             ← Department form Zod schema
│   │   ├── production-line.ts        ← Production line form Zod schema
│   │   ├── article.ts                ← Article form Zod schema
│   │   ├── operator.ts               ← Operator form Zod schema
│   │   ├── process.ts                ← Process form Zod schema
│   │   ├── gsd.ts                    ← GSD form Zod schema
│   │   ├── mtm.ts                    ← MTM form Zod schema
│   │   ├── sewing-factor.ts          ← Sewing factor form Zod schema
│   │   └── ptms.ts                   ← PTMS form Zod schema
│   │
│   ├── types/
│   │   ├── index.ts                  ← Shared types
│   │   ├── user.ts
│   │   ├── factory.ts
│   │   ├── department.ts
│   │   ├── production-line.ts
│   │   ├── article.ts
│   │   ├── operator.ts
│   │   ├── process.ts
│   │   ├── gsd.ts
│   │   ├── mtm.ts
│   │   ├── sewing-factor.ts
│   │   └── ptms.ts
│   │
│   ├── utils/
│   │   ├── cn.ts                     ← clsx + tailwind-merge
│   │   ├── format.ts                 ← Number/date formatting
│   │   ├── constants.ts              ← App constants
│   │   └── export.ts                 ← CSV/Excel export utility
│   │
│   └── config/
│       ├── site.ts                   ← Site config (name, description)
│       └── nav.ts                    ← Navigation config (sidebar items)
│
├── providers/
│   ├── QueryProvider.tsx             ← React Query Provider + DevTools
│   ├── AuthProvider.tsx              ← Auth hydration on mount
│   └── ToastProvider.tsx             ← Toast provider
│
└── middleware.ts                     ← Next.js middleware (auth guard)
```

---

## 3. Routing Architecture

### 3.1 Route Groups

| Group | Layout | Auth | Purpose |
|-------|--------|------|---------|
| `(auth)` | Centered, no sidebar | No | Login page |
| `(dashboard)` | Sidebar + Header | Yes | All protected pages |

### 3.2 Route Table

| URL Path | Page | Description |
|----------|------|-------------|
| `/` | Redirect | Redirects to `/dashboard` |
| `/login` | Login | Username/password login |
| `/dashboard` | Dashboard | Stats, charts, quick actions |
| `/master-data/factories` | Factories | Factory CRUD |
| `/master-data/departments` | Departments | Department CRUD |
| `/master-data/lines` | Lines | Production line CRUD |
| `/master-data/articles` | Articles | Article CRUD |
| `/master-data/operators` | Operators | Operator CRUD |
| `/process-library/processes` | Processes | Process + versions |
| `/process-library/gsd/categories` | GSD Categories | GSD category CRUD |
| `/process-library/gsd/elements` | GSD Elements | GSD element CRUD |
| `/process-library/mtm` | MTM | MTM element CRUD |
| `/process-library/sewing-factors` | Sewing Factors | Sewing factor CRUD |
| `/process-library/stop-factors` | Stop Factors | Sewing stop factor CRUD |
| `/ptms` | PTMS Reports | Report list |
| `/ptms/[id]` | PTMS Detail | Report detail/edit |
| `/credentials` | Users | User management |
| `/profile` | Profile | Current user profile |

### 3.3 Middleware (Auth Guard)

```typescript
// src/middleware.ts
import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

const publicPaths = ['/login', '/api/health'];

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;
  
  // Allow public paths
  if (publicPaths.some(path => pathname.startsWith(path))) {
    return NextResponse.next();
  }
  
  // Check for auth token cookie or redirect to login
  // Note: Zustand stores in localStorage, so middleware checks a cookie
  // that is set/cleared by the auth provider
  const isAuthenticated = request.cookies.get('isAuthenticated')?.value;
  
  if (!isAuthenticated) {
    const loginUrl = new URL('/login', request.url);
    loginUrl.searchParams.set('from', pathname);
    return NextResponse.redirect(loginUrl);
  }
  
  return NextResponse.next();
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
};
```

---

## 4. Layout System

### 4.1 Root Layout

```tsx
// src/app/layout.tsx
import { Inter } from 'next/font/google';
import { QueryProvider } from '@/providers/QueryProvider';
import { Toaster } from '@/components/ui/toaster';
import './globals.css';

const inter = Inter({ subsets: ['latin'] });

export const metadata = {
  title: 'LEAN ENTERPRISE',
  description: 'Industrial Engineering Management System',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" suppressHydrationWarning>
      <body className={inter.className}>
        <QueryProvider>
          {children}
          <Toaster />
        </QueryProvider>
      </body>
    </html>
  );
}
```

### 4.2 Auth Layout (Centered)

```tsx
// src/app/(auth)/layout.tsx
export default function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100">
      <div className="w-full max-w-md">
        {children}
      </div>
    </div>
  );
}
```

### 4.3 Dashboard Layout (Sidebar + Header)

```tsx
// src/app/(dashboard)/layout.tsx
import { AppSidebar } from '@/components/layout/AppSidebar';
import { Header } from '@/components/layout/Header';

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="flex h-screen overflow-hidden">
      {/* Sidebar — hidden on mobile, shown on desktop */}
      <AppSidebar />
      
      {/* Main content area */}
      <div className="flex-1 flex flex-col overflow-hidden">
        <Header />
        <main className="flex-1 overflow-y-auto bg-slate-50 p-6">
          {children}
        </main>
      </div>
    </div>
  );
}
```

### 4.4 Sidebar Component

```tsx
// components/layout/AppSidebar.tsx
'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useSidebarStore } from '@/stores/useSidebarStore';
import { cn } from '@/lib/utils/cn';
import {
  LayoutDashboard,
  Database,
  Cog,
  ClipboardList,
  Users,
  ChevronDown,
  Factory,
  Building2,
  GitBranch,
  Package,
  UserCog,
  Settings2,
  Layers,
  Timer,
  Scissors,
  ShieldAlert,
} from 'lucide-react';

const navigation = [
  {
    label: 'Dashboard',
    href: '/dashboard',
    icon: LayoutDashboard,
  },
  {
    label: 'Master Data',
    icon: Database,
    children: [
      { label: 'Factories', href: '/master-data/factories', icon: Factory },
      { label: 'Departments', href: '/master-data/departments', icon: Building2 },
      { label: 'Production Lines', href: '/master-data/lines', icon: GitBranch },
      { label: 'Articles', href: '/master-data/articles', icon: Package },
      { label: 'Operators', href: '/master-data/operators', icon: UserCog },
    ],
  },
  {
    label: 'Process Library',
    icon: Cog,
    children: [
      { label: 'Processes', href: '/process-library/processes', icon: Settings2 },
      { label: 'GSD Categories', href: '/process-library/gsd/categories', icon: Layers },
      { label: 'GSD Elements', href: '/process-library/gsd/elements', icon: Layers },
      { label: 'MTM Elements', href: '/process-library/mtm', icon: Timer },
      { label: 'Sewing Factors', href: '/process-library/sewing-factors', icon: Scissors },
      { label: 'Stop Factors', href: '/process-library/stop-factors', icon: ShieldAlert },
    ],
  },
  {
    label: 'PTMS',
    href: '/ptms',
    icon: ClipboardList,
  },
  {
    label: 'Credentials',
    href: '/credentials',
    icon: Users,
  },
];

export function AppSidebar() {
  const pathname = usePathname();
  const { collapsed } = useSidebarStore();

  return (
    <aside
      className={cn(
        'flex flex-col bg-sidebar-bg text-sidebar-text transition-all duration-300 border-r border-slate-700/50',
        collapsed ? 'w-16' : 'w-64'
      )}
    >
      {/* Logo */}
      <div className="flex items-center h-16 px-4 border-b border-slate-700/50">
        <span className={cn(
          'font-bold text-white text-lg transition-all',
          collapsed && 'hidden'
        )}>
          LEMS
        </span>
        {collapsed && (
          <span className="font-bold text-white text-lg mx-auto">L</span>
        )}
      </div>

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto py-4 px-2 space-y-1">
        {navigation.map((item) =>
          item.children ? (
            <SidebarGroup
              key={item.label}
              item={item}
              pathname={pathname}
              collapsed={collapsed}
            />
          ) : (
            <SidebarItem
              key={item.href}
              item={item}
              pathname={pathname}
              collapsed={collapsed}
            />
          )
        )}
      </nav>
    </aside>
  );
}
```

---

## 5. Component Library

### 5.1 shadcn/ui Components Used

| Component | Usage |
|-----------|-------|
| `Button` | All actions (create, edit, delete, submit) |
| `Input` | Text inputs |
| `Label` | Form labels |
| `Card` | Stat cards, content containers |
| `Dialog` | Create/Edit forms, confirmations |
| `Sheet` | Mobile sidebar, detail panels |
| `DropdownMenu` | Row actions, user menu |
| `Select` | Dropdown selects |
| `Combobox` | Searchable selects (operators, articles) |
| `Table` | Data tables |
| `Badge` | Status indicators, counts |
| `Tabs` | Tab navigation within pages |
| `Separator` | Visual dividers |
| `Skeleton` | Loading states |
| `Avatar` | User avatar |
| `Toast` | Success/error notifications |
| `Textarea` | Multi-line inputs |

### 5.2 Custom Components

#### PageHeader

```tsx
// components/layout/PageHeader.tsx
interface PageHeaderProps {
  title: string;
  description?: string;
  actions?: React.ReactNode;
}

export function PageHeader({ title, description, actions }: PageHeaderProps) {
  return (
    <div className="flex items-center justify-between mb-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">{title}</h1>
        {description && (
          <p className="text-slate-500 mt-1">{description}</p>
        )}
      </div>
      {actions && <div className="flex items-center gap-2">{actions}</div>}
    </div>
  );
}
```

#### ConfirmDialog

```tsx
// components/shared/ConfirmDialog.tsx
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';

interface ConfirmDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description: string;
  onConfirm: () => void;
  loading?: boolean;
  variant?: 'default' | 'destructive';
}

export function ConfirmDialog({
  open, onOpenChange, title, description, onConfirm, loading, variant = 'destructive',
}: ConfirmDialogProps) {
  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{title}</AlertDialogTitle>
          <AlertDialogDescription>{description}</AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel disabled={loading}>Cancel</AlertDialogCancel>
          <AlertDialogAction
            onClick={onConfirm}
            disabled={loading}
            className={variant === 'destructive' ? 'bg-red-600 hover:bg-red-700' : ''}
          >
            {loading ? 'Processing...' : 'Confirm'}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
```

#### EmptyState

```tsx
// components/shared/EmptyState.tsx
import { InboxIcon } from 'lucide-react';

interface EmptyStateProps {
  icon?: React.ComponentType<{ className?: string }>;
  title: string;
  description: string;
  action?: React.ReactNode;
}

export function EmptyState({
  icon: Icon = InboxIcon,
  title,
  description,
  action,
}: EmptyStateProps) {
  return (
    <div className="flex flex-col items-center justify-center py-12 text-center">
      <Icon className="h-12 w-12 text-slate-300 mb-4" />
      <h3 className="text-lg font-semibold text-slate-700">{title}</h3>
      <p className="text-sm text-slate-500 mt-1 mb-4">{description}</p>
      {action}
    </div>
  );
}
```

---

## 6. Page Implementations

### 6.1 Login Page

```tsx
// src/app/(auth)/login/page.tsx
'use client';

import { useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { loginSchema, type LoginFormData } from '@/lib/schemas/login';
import { useAuth } from '@/lib/hooks/useAuth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export default function LoginPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { login, isLoggingIn, loginError } = useAuth();
  const [showPassword, setShowPassword] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
  });

  const onSubmit = (data: LoginFormData) => {
    login(data, {
      onSuccess: () => {
        const from = searchParams.get('from') || '/dashboard';
        router.push(from);
      },
    });
  };

  return (
    <Card className="shadow-xl border-0">
      <CardHeader className="text-center pb-2">
        <CardTitle className="text-2xl font-bold">LEAN ENTERPRISE</CardTitle>
        <CardDescription>Industrial Engineering Management System</CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {loginError && (
            <div className="bg-red-50 text-red-600 text-sm p-3 rounded-md">
              Invalid username or password
            </div>
          )}
          
          <div className="space-y-2">
            <Label htmlFor="username">Username</Label>
            <Input
              id="username"
              placeholder="Enter your username"
              {...register('username')}
              className={errors.username ? 'border-red-500' : ''}
            />
            {errors.username && (
              <p className="text-red-500 text-xs">{errors.username.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="password">Password</Label>
            <Input
              id="password"
              type={showPassword ? 'text' : 'password'}
              placeholder="Enter your password"
              {...register('password')}
              className={errors.password ? 'border-red-500' : ''}
            />
            {errors.password && (
              <p className="text-red-500 text-xs">{errors.password.message}</p>
            )}
          </div>

          <Button
            type="submit"
            className="w-full"
            disabled={isLoggingIn}
          >
            {isLoggingIn ? 'Signing in...' : 'Sign In'}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
```

### 6.2 Dashboard Page

```tsx
// src/app/(dashboard)/dashboard/page.tsx
'use client';

import { useDashboard } from '@/lib/hooks/useDashboard';
import { StatsCards } from '@/components/dashboard/StatsCards';
import { RecentActivity } from '@/components/dashboard/RecentActivity';
import { QuickActions } from '@/components/dashboard/QuickActions';
import { PageHeader } from '@/components/layout/PageHeader';
import { PageContainer } from '@/components/layout/PageContainer';

export default function DashboardPage() {
  const { data: stats, isLoading } = useDashboard();

  return (
    <PageContainer>
      <PageHeader
        title="Dashboard"
        description="Overview of your industrial engineering operations"
      />
      
      <div className="grid gap-6">
        {/* Stats Cards */}
        <StatsCards stats={stats} isLoading={isLoading} />
        
        <div className="grid lg:grid-cols-3 gap-6">
          {/* Recent Activity — 2 cols */}
          <div className="lg:col-span-2">
            <RecentActivity />
          </div>
          
          {/* Quick Actions — 1 col */}
          <div>
            <QuickActions />
          </div>
        </div>
      </div>
    </PageContainer>
  );
}
```

### 6.3 Master Data Page Template (e.g., Factories)

```tsx
// src/app/(dashboard)/master-data/factories/page.tsx
'use client';

import { useState } from 'react';
import { Plus } from 'lucide-react';
import { useFactories, useDeleteFactory } from '@/lib/hooks/useFactories';
import { PageHeader } from '@/components/layout/PageHeader';
import { PageContainer } from '@/components/layout/PageContainer';
import { DataTable } from '@/components/data-table/DataTable';
import { columns } from './columns';
import { FactoryFormDialog } from '@/components/forms/FactoryFormDialog';
import { ConfirmDialog } from '@/components/shared/ConfirmDialog';
import { Button } from '@/components/ui/button';

export default function FactoriesPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [createOpen, setCreateOpen] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);
  const [deleteId, setDeleteId] = useState<number | null>(null);

  const { data, isLoading } = useFactories({ page, search });
  const { mutate: deleteFactory, isPending: isDeleting } = useDeleteFactory();

  return (
    <PageContainer>
      <PageHeader
        title="Factories"
        description="Manage factory locations"
        actions={
          <Button onClick={() => setCreateOpen(true)}>
            <Plus className="h-4 w-4 mr-2" />
            Add Factory
          </Button>
        }
      />

      <DataTable
        columns={columns({
          onEdit: (id) => setEditId(id),
          onDelete: (id) => setDeleteId(id),
        })}
        data={data?.data ?? []}
        isLoading={isLoading}
        search={search}
        onSearchChange={setSearch}
        pagination={{
          page,
          totalPages: data?.meta?.last_page ?? 1,
          onPageChange: setPage,
        }}
      />

      {/* Create/Edit Dialog */}
      <FactoryFormDialog
        open={createOpen || editId !== null}
        onOpenChange={(open) => {
          if (!open) {
            setCreateOpen(false);
            setEditId(null);
          }
        }}
        factoryId={editId}
      />

      {/* Delete Confirmation */}
      <ConfirmDialog
        open={deleteId !== null}
        onOpenChange={(open) => !open && setDeleteId(null)}
        title="Delete Factory"
        description="Are you sure you want to delete this factory? This action cannot be undone."
        onConfirm={() => {
          if (deleteId) {
            deleteFactory(deleteId, {
              onSuccess: () => setDeleteId(null),
            });
          }
        }}
        loading={isDeleting}
      />
    </PageContainer>
  );
}
```

---

## 7. Data Fetching Patterns

### 7.1 API Client

```typescript
// lib/api/client.ts
import axios from 'axios';
import { useAuthStore } from '@/stores/useAuthStore';

const client = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

client.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

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

### 7.2 API Module Pattern

```typescript
// lib/api/factories.ts
import client from './client';
import type { Factory, ListParams, PaginatedResponse } from '@/lib/types';

export async function list(params?: ListParams): Promise<PaginatedResponse<Factory>> {
  const { data } = await client.get('/factories', { params });
  return data;
}

export async function show(id: number): Promise<Factory> {
  const { data } = await client.get(`/factories/${id}`);
  return data.data;
}

export async function create(input: Partial<Factory>): Promise<Factory> {
  const { data } = await client.post('/factories', input);
  return data.data;
}

export async function update({ id, ...input }: Partial<Factory> & { id: number }): Promise<Factory> {
  const { data } = await client.put(`/factories/${id}`, input);
  return data.data;
}

export async function remove(id: number): Promise<void> {
  await client.delete(`/factories/${id}`);
}
```

### 7.3 React Query Hook Pattern

```typescript
// lib/hooks/useFactories.ts
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import * as api from '@/lib/api/factories';
import type { ListParams } from '@/lib/types';
import { useToast } from '@/components/ui/use-toast';

export function useFactories(params?: ListParams) {
  return useQuery({
    queryKey: ['factories', params],
    queryFn: () => api.list(params),
  });
}

export function useFactory(id: number | null) {
  return useQuery({
    queryKey: ['factories', id],
    queryFn: () => api.show(id!),
    enabled: !!id,
  });
}

export function useCreateFactory() {
  const queryClient = useQueryClient();
  const { toast } = useToast();

  return useMutation({
    mutationFn: api.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['factories'] });
      toast({ title: 'Success', description: 'Factory created successfully' });
    },
    onError: (error: any) => {
      toast({
        title: 'Error',
        description: error.response?.data?.message || 'Failed to create factory',
        variant: 'destructive',
      });
    },
  });
}

export function useUpdateFactory() {
  const queryClient = useQueryClient();
  const { toast } = useToast();

  return useMutation({
    mutationFn: api.update,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['factories'] });
      toast({ title: 'Success', description: 'Factory updated successfully' });
    },
    onError: (error: any) => {
      toast({
        title: 'Error',
        description: error.response?.data?.message || 'Failed to update factory',
        variant: 'destructive',
      });
    },
  });
}

export function useDeleteFactory() {
  const queryClient = useQueryClient();
  const { toast } = useToast();

  return useMutation({
    mutationFn: api.remove,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['factories'] });
      toast({ title: 'Success', description: 'Factory deleted successfully' });
    },
    onError: (error: any) => {
      toast({
        title: 'Error',
        description: error.response?.data?.message || 'Failed to delete factory',
        variant: 'destructive',
      });
    },
  });
}
```

---

## 8. Form Patterns

### 8.1 Zod Schema

```typescript
// lib/schemas/factory.ts
import { z } from 'zod';

export const factorySchema = z.object({
  factory_name: z
    .string()
    .min(1, 'Factory name is required')
    .max(255, 'Factory name is too long'),
  factory_code: z
    .string()
    .min(1, 'Factory code is required')
    .max(50, 'Factory code is too long'),
  location: z
    .string()
    .max(500, 'Location is too long')
    .optional(),
});

export type FactoryFormData = z.infer<typeof factorySchema>;
```

### 8.2 Form Dialog Component

```tsx
// components/forms/FactoryFormDialog.tsx
'use client';

import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { factorySchema, type FactoryFormData } from '@/lib/schemas/factory';
import { useFactory, useCreateFactory, useUpdateFactory } from '@/lib/hooks/useFactories';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

interface FactoryFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  factoryId?: number | null;
}

export function FactoryFormDialog({ open, onOpenChange, factoryId }: FactoryFormDialogProps) {
  const isEdit = !!factoryId;
  const { data: factory, isLoading: isLoadingFactory } = useFactory(factoryId ?? null);
  const { mutate: create, isPending: isCreating } = useCreateFactory();
  const { mutate: update, isPending: isUpdating } = useUpdateFactory();

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<FactoryFormData>({
    resolver: zodResolver(factorySchema),
  });

  // Reset form when dialog opens/closes or data loads
  useEffect(() => {
    if (open) {
      if (isEdit && factory) {
        reset({
          factory_name: factory.factory_name,
          factory_code: factory.factory_code,
          location: factory.location ?? '',
        });
      } else {
        reset({ factory_name: '', factory_code: '', location: '' });
      }
    }
  }, [open, isEdit, factory, reset]);

  const onSubmit = (data: FactoryFormData) => {
    if (isEdit && factoryId) {
      update({ id: factoryId, ...data }, { onSuccess: () => onOpenChange(false) });
    } else {
      create(data, { onSuccess: () => onOpenChange(false) });
    }
  };

  const isPending = isCreating || isUpdating;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{isEdit ? 'Edit Factory' : 'Create Factory'}</DialogTitle>
          <DialogDescription>
            {isEdit ? 'Update factory details' : 'Add a new factory to the system'}
          </DialogDescription>
        </DialogHeader>

        {isEdit && isLoadingFactory ? (
          <div className="space-y-4">
            <div className="h-10 bg-slate-100 rounded animate-pulse" />
            <div className="h-10 bg-slate-100 rounded animate-pulse" />
            <div className="h-10 bg-slate-100 rounded animate-pulse" />
          </div>
        ) : (
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="factory_name">Factory Name</Label>
              <Input
                id="factory_name"
                placeholder="e.g., Main Factory"
                {...register('factory_name')}
                className={errors.factory_name ? 'border-red-500' : ''}
              />
              {errors.factory_name && (
                <p className="text-red-500 text-xs">{errors.factory_name.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="factory_code">Factory Code</Label>
              <Input
                id="factory_code"
                placeholder="e.g., F001"
                {...register('factory_code')}
                className={errors.factory_code ? 'border-red-500' : ''}
              />
              {errors.factory_code && (
                <p className="text-red-500 text-xs">{errors.factory_code.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="location">Location (optional)</Label>
              <Input
                id="location"
                placeholder="e.g., Building A, Floor 2"
                {...register('location')}
              />
            </div>

            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => onOpenChange(false)}
                disabled={isPending}
              >
                Cancel
              </Button>
              <Button type="submit" disabled={isPending}>
                {isPending ? 'Saving...' : isEdit ? 'Update' : 'Create'}
              </Button>
            </DialogFooter>
          </form>
        )}
      </DialogContent>
    </Dialog>
  );
}
```

---

## 9. Data Table Implementation

### 9.1 Column Definition

```tsx
// app/(dashboard)/master-data/factories/columns.tsx
'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal, Pencil, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import type { Factory } from '@/lib/types';

interface ColumnActions {
  onEdit: (id: number) => void;
  onDelete: (id: number) => void;
}

export const columns = ({ onEdit, onDelete }: ColumnActions): ColumnDef<Factory>[] => [
  {
    accessorKey: 'factory_code',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Code" />
    ),
  },
  {
    accessorKey: 'factory_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Factory Name" />
    ),
  },
  {
    accessorKey: 'location',
    header: 'Location',
    cell: ({ row }) => row.getValue('location') || '—',
  },
  {
    accessorKey: 'departments_count',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Departments" />
    ),
    cell: ({ row }) => (
      <span className="text-slate-500">{row.getValue('departments_count') ?? 0}</span>
    ),
  },
  {
    id: 'actions',
    cell: ({ row }) => (
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" className="h-8 w-8 p-0">
            <MoreHorizontal className="h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem onClick={() => onEdit(row.original.factory_id)}>
            <Pencil className="mr-2 h-4 w-4" />
            Edit
          </DropdownMenuItem>
          <DropdownMenuItem
            onClick={() => onDelete(row.original.factory_id)}
            className="text-red-600"
          >
            <Trash2 className="mr-2 h-4 w-4" />
            Delete
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    ),
  },
];
```

### 9.2 Reusable DataTable Component

The `DataTable` component wraps TanStack Table with:
- Column sorting (click header to sort)
- Search (debounced text input)
- Pagination (page controls)
- Loading skeleton
- Empty state

---

## 10. Authentication UI

### 10.1 Login Flow

```
User visits any page
       │
       ▼
Middleware checks cookie
       │
       ├─ No cookie → Redirect to /login
       │                     │
       │                     ▼
       │              Login form (username + password)
       │                     │
       │                     ▼
       │              POST /api/login
       │                     │
       │              ┌──────┴──────┐
       │              │  Success    │  Error
       │              │  Store token│  Show error
       │              │  Set cookie │  Stay on login
       │              │  Redirect   │
       │              └──────┬──────┘
       │                     │
       ├─ Has cookie ────────┘
       │
       ▼
  Render protected page
```

### 10.2 User Menu (Header)

```tsx
// components/layout/UserMenu.tsx
'use client';

import { useAuthStore } from '@/stores/useAuthStore';
import { useAuth } from '@/lib/hooks/useAuth';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { User, LogOut, Settings } from 'lucide-react';
import Link from 'next/link';

export function UserMenu() {
  const user = useAuthStore((state) => state.user);
  const { logout, isLoggingOut } = useAuth();

  const initials = user?.name
    ?.split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase() ?? 'U';

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <button className="flex items-center gap-2 hover:bg-slate-100 rounded-md p-1.5 transition-colors">
          <Avatar className="h-8 w-8">
            <AvatarFallback className="bg-primary-100 text-primary-700 text-sm font-medium">
              {initials}
            </AvatarFallback>
          </Avatar>
          <div className="text-left hidden md:block">
            <p className="text-sm font-medium text-slate-700">{user?.name}</p>
            <p className="text-xs text-slate-500">{user?.role?.role_name}</p>
          </div>
        </button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-48">
        <DropdownMenuItem asChild>
          <Link href="/profile">
            <User className="mr-2 h-4 w-4" />
            Profile
          </Link>
        </DropdownMenuItem>
        <DropdownMenuItem asChild>
          <Link href="/profile">
            <Settings className="mr-2 h-4 w-4" />
            Settings
          </Link>
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem
          onClick={() => logout()}
          disabled={isLoggingOut}
          className="text-red-600"
        >
          <LogOut className="mr-2 h-4 w-4" />
          {isLoggingOut ? 'Signing out...' : 'Sign Out'}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
```

---

## 11. Responsive Design

### 11.1 Breakpoint Strategy

| Breakpoint | Width | Sidebar | Layout |
|-----------|-------|---------|--------|
| Mobile | < 768px | Hidden (Sheet overlay) | Full width |
| Tablet | 768px – 1024px | Collapsed (icons only) | Full width |
| Desktop | > 1024px | Expanded (full) | Sidebar + content |

### 11.2 Mobile Navigation

```tsx
// components/layout/MobileNav.tsx
'use client';

import { Menu } from 'lucide-react';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { AppSidebar } from './AppSidebar';
import { useState } from 'react';

export function MobileNav() {
  const [open, setOpen] = useState(false);

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>
        <Button variant="ghost" size="icon" className="md:hidden">
          <Menu className="h-5 w-5" />
        </Button>
      </SheetTrigger>
      <SheetContent side="left" className="p-0 w-64">
        <AppSidebar />
      </SheetContent>
    </Sheet>
  );
}
```

### 11.3 Responsive Data Table

- On mobile: Stack columns, hide non-essential columns
- Use `hidden md:table-cell` for columns that should hide on mobile
- Card-based layout on mobile as alternative to table

---

## 12. Loading & Error States

### 12.1 Loading Skeletons

Every page has a `loading.tsx` that shows skeleton UI:

```tsx
// app/(dashboard)/master-data/factories/loading.tsx
import { Skeleton } from '@/components/ui/skeleton';

export default function Loading() {
  return (
    <div className="space-y-6">
      {/* Header skeleton */}
      <div className="flex items-center justify-between">
        <div>
          <Skeleton className="h-8 w-48" />
          <Skeleton className="h-4 w-64 mt-2" />
        </div>
        <Skeleton className="h-10 w-32" />
      </div>
      
      {/* Table skeleton */}
      <div className="border rounded-lg">
        <div className="h-12 border-b bg-slate-50 flex items-center px-4">
          <Skeleton className="h-4 w-32" />
        </div>
        {Array.from({ length: 5 }).map((_, i) => (
          <div key={i} className="h-16 border-b flex items-center px-4 gap-4">
            <Skeleton className="h-4 w-16" />
            <Skeleton className="h-4 w-48" />
            <Skeleton className="h-4 w-32" />
            <Skeleton className="h-4 w-24" />
          </div>
        ))}
      </div>
    </div>
  );
}
```

### 12.2 Error Boundary

```tsx
// components/shared/ErrorState.tsx
import { AlertCircle, RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface ErrorStateProps {
  title?: string;
  message?: string;
  onRetry?: () => void;
}

export function ErrorState({
  title = 'Something went wrong',
  message = 'An error occurred while loading this page.',
  onRetry,
}: ErrorStateProps) {
  return (
    <div className="flex flex-col items-center justify-center py-12 text-center">
      <AlertCircle className="h-12 w-12 text-red-400 mb-4" />
      <h3 className="text-lg font-semibold text-slate-700">{title}</h3>
      <p className="text-sm text-slate-500 mt-1 mb-4">{message}</p>
      {onRetry && (
        <Button onClick={onRetry} variant="outline">
          <RefreshCw className="h-4 w-4 mr-2" />
          Try Again
        </Button>
      )}
    </div>
  );
}
```

---

## 13. Toast Notifications

Success/error feedback via shadcn/ui toast:

```typescript
// Usage in components
import { useToast } from '@/components/ui/use-toast';

const { toast } = useToast();

// Success
toast({ title: 'Success', description: 'Factory created successfully' });

// Error
toast({
  title: 'Error',
  description: 'Failed to create factory',
  variant: 'destructive',
});
```

---

## 14. Accessibility

| Requirement | Implementation |
|------------|---------------|
| **Keyboard navigation** | All interactive elements focusable, tab order logical |
| **Screen readers** | ARIA labels on buttons, landmarks, form fields |
| **Color contrast** | Minimum 4.5:1 ratio (Tailwind defaults meet this) |
| **Focus indicators** | Visible focus rings on all interactive elements |
| **Form labels** | Every input has a `<Label>` associated via `htmlFor` |
| **Error messages** | Linked to inputs via `aria-describedby` |
| **Loading states** | `aria-busy` and `role="status"` on loading elements |
| **Dialogs** | Focus trap, Escape to close, ARIA attributes |

---

## 15. Performance Optimization

| Strategy | Implementation |
|----------|---------------|
| **Code splitting** | Next.js automatic per-route code splitting |
| **Image optimization** | `next/image` with lazy loading |
| **Font optimization** | `next/font` with Inter, preloaded |
| **React Query caching** | `staleTime: 5 * 60 * 1000` (5 min default) |
| **Debounced search** | 300ms debounce on search inputs |
| **Memoization** | `React.memo` on expensive table rows |
| **Virtual scrolling** | TanStack Table virtual rows for 1000+ items (future) |
| **Bundle analysis** | `@next/bundle-analyzer` in development |
| **Prefetching** | React Query prefetch on hover for detail pages |

---

*End of Frontend Implementation Guide*
