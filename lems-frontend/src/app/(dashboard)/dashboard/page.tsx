"use client";

import { useAuthStore } from "@/store/auth-store";
import { useDashboardStats } from "@/hooks/use-dashboard";
import { StatCard } from "@/components/stat-card";
import {
  Building2,
  Building,
  GitBranch,
  Package,
  Users,
  List,
  FileText,
  Gauge,
} from "lucide-react";
import { Skeleton } from "@/components/ui/skeleton";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { ArrowRight, ClipboardCheck } from "lucide-react";

export default function DashboardPage() {
  const { user } = useAuthStore();
  const { data: stats, isLoading } = useDashboardStats();

  return (
    <div className="space-y-6">
      {/* Welcome */}
      <div>
        <h1 className="text-2xl font-bold tracking-tight">
          Welcome back, {user?.name || "User"} 👋
        </h1>
        <p className="text-muted-foreground">
          Here&apos;s an overview of your Lean & IE Management System.
        </p>
      </div>

      {/* Stats Grid */}
      {isLoading ? (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          {Array.from({ length: 8 }).map((_, i) => (
            <Skeleton key={i} className="h-[120px] rounded-lg" />
          ))}
        </div>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <StatCard
            title="Total Factories"
            value={stats?.factories ?? 0}
            icon={Building2}
            description="Registered factories"
          />
          <StatCard
            title="Departments"
            value={stats?.departments ?? 0}
            icon={Building}
            description="Across all factories"
          />
          <StatCard
            title="Production Lines"
            value={stats?.production_lines ?? 0}
            icon={GitBranch}
            description="Active lines"
          />
          <StatCard
            title="Articles"
            value={stats?.articles ?? 0}
            icon={Package}
            description="In the system"
          />
          <StatCard
            title="Operators"
            value={stats?.operators ?? 0}
            icon={Users}
            description="Registered operators"
          />
          <StatCard
            title="Processes"
            value={stats?.processes ?? 0}
            icon={List}
            description="Process library"
          />
          <StatCard
            title="PTMS Reports"
            value={stats?.ptms_reports ?? 0}
            icon={FileText}
            description="Time study reports"
          />
          <StatCard
            title="Average SMV"
            value={stats?.average_smv?.toFixed(2) ?? "0.00"}
            icon={Gauge}
            description="Standard Minute Value"
          />
        </div>
      )}

      <div className="relative overflow-hidden rounded-xl bg-slate-950 p-6 text-white">
        <div className="relative z-10 max-w-2xl">
          <div className="flex items-center gap-2 text-sm font-medium text-teal-300">
            <ClipboardCheck className="h-4 w-4" />
            Work plan workspace
          </div>
          <h2 className="mt-2 text-xl font-semibold">Run the improvement loop</h2>
          <p className="mt-2 text-sm leading-6 text-slate-300">
            Capture cycle time, diagnose breakdowns, balance stations, manage Kaizen, and keep TPM, skills, materials, and VSM in one working view.
          </p>
          <Button asChild className="mt-4 bg-teal-500 text-slate-950 hover:bg-teal-400">
            <Link href="/operations">
              Open Operations Hub <ArrowRight className="ml-2 h-4 w-4" />
            </Link>
          </Button>
        </div>
        <div className="absolute -right-16 -top-24 h-72 w-72 rounded-full bg-teal-500/20 blur-3xl" />
      </div>
    </div>
  );
}
