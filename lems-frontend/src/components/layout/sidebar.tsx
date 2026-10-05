"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import { useSidebarStore } from "@/store/sidebar-store";
import { useAuthStore } from "@/store/auth-store";
import {
  LayoutDashboard,
  Building2,
  Building,
  GitBranch,
  Package,
  Users,
  List,
  FolderTree,
  Layers,
  Cpu,
  Settings,
  OctagonX,
  FileText,
  UserCog,
  User,
  LogOut,
  ChevronLeft,
  Factory,
  X,
  Workflow,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";

interface NavItem {
  label: string;
  href: string;
  icon: React.ElementType;
}

interface NavGroup {
  title: string;
  items: NavItem[];
}

const navigation: NavGroup[] = [
  {
    title: "OVERVIEW",
    items: [
      { label: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
    ],
  },
  {
    title: "MASTER DATA",
    items: [
      { label: "Factories", href: "/master-data/factories", icon: Building2 },
      { label: "Departments", href: "/master-data/departments", icon: Building },
      { label: "Lines", href: "/master-data/lines", icon: GitBranch },
      { label: "Articles", href: "/master-data/articles", icon: Package },
      { label: "Operators", href: "/master-data/operators", icon: Users },
    ],
  },
  {
    title: "PROCESS LIBRARY",
    items: [
      { label: "Processes", href: "/process-library/processes", icon: List },
      { label: "GSD Categories", href: "/process-library/gsd/categories", icon: FolderTree },
      { label: "GSD Elements", href: "/process-library/gsd/elements", icon: Layers },
      { label: "MTM Elements", href: "/process-library/mtm", icon: Cpu },
      { label: "Sewing Factors", href: "/process-library/sewing-factors", icon: Settings },
      { label: "Stop Factors", href: "/process-library/stop-factors", icon: OctagonX },
    ],
  },
  {
    title: "PRODUCTION",
    items: [
      { label: "PTMS Reports", href: "/ptms", icon: FileText },
      { label: "Operations Hub", href: "/operations", icon: Workflow },
    ],
  },
  {
    title: "ADMINISTRATION",
    items: [
      { label: "Credentials", href: "/credentials", icon: UserCog },
      { label: "Profile", href: "/profile", icon: User },
    ],
  },
];

export function Sidebar() {
  const pathname = usePathname();
  const { isCollapsed, isMobileOpen, toggle, setMobileOpen } = useSidebarStore();
  const { user, logout } = useAuthStore();

  return (
    <>
      {/* Mobile overlay */}
      {isMobileOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/50 lg:hidden"
          onClick={() => setMobileOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside
        className={cn(
          "fixed left-0 top-0 z-50 h-full bg-sidebar transition-all duration-300 flex flex-col",
          isCollapsed ? "w-[72px]" : "w-64",
          isMobileOpen
            ? "translate-x-0"
            : "-translate-x-full lg:translate-x-0"
        )}
      >
        {/* Header */}
        <div className="flex h-16 items-center justify-between px-4">
          {!isCollapsed && (
            <div className="flex items-center gap-2">
              <Factory className="h-6 w-6 text-blue-500" />
              <span className="text-lg font-bold text-white tracking-wider">
                LEAN ENTERPRISE
              </span>
            </div>
          )}
          {isCollapsed && (
            <Factory className="h-6 w-6 text-blue-500 mx-auto" />
          )}
          <Button
            variant="ghost"
            size="icon"
            className="text-sidebar-text hover:text-white hover:bg-sidebar-hover lg:flex hidden"
            onClick={toggle}
          >
            <ChevronLeft
              className={cn(
                "h-4 w-4 transition-transform",
                isCollapsed && "rotate-180"
              )}
            />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            className="text-sidebar-text hover:text-white hover:bg-sidebar-hover lg:hidden"
            onClick={() => setMobileOpen(false)}
          >
            <X className="h-4 w-4" />
          </Button>
        </div>

        <Separator className="bg-sidebar-hover" />

        {/* Navigation */}
        <nav className="flex-1 overflow-y-auto py-4 px-3 space-y-6">
          {navigation.map((group) => (
            <div key={group.title}>
              {!isCollapsed && (
                <p className="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-sidebar-text/60">
                  {group.title}
                </p>
              )}
              <div className="space-y-1">
                {group.items.map((item) => {
                  const isActive =
                    pathname === item.href ||
                    pathname.startsWith(item.href + "/");
                  return (
                    <Link
                      key={item.href}
                      href={item.href}
                      onClick={() => setMobileOpen(false)}
                      className={cn(
                        "flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors",
                        isActive
                          ? "bg-sidebar-active text-sidebar-text-active"
                          : "text-sidebar-text hover:bg-sidebar-hover hover:text-sidebar-text-active",
                        isCollapsed && "justify-center px-2"
                      )}
                      title={isCollapsed ? item.label : undefined}
                    >
                      <item.icon className="h-5 w-5 shrink-0" />
                      {!isCollapsed && <span>{item.label}</span>}
                    </Link>
                  );
                })}
              </div>
            </div>
          ))}
        </nav>

        {/* User section */}
        <div className="border-t border-sidebar-hover p-4">
          {user && (
            <div className={cn("flex items-center gap-3", isCollapsed && "justify-center")}>
              <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white text-sm font-medium">
                {user.name?.charAt(0)?.toUpperCase() || "U"}
              </div>
              {!isCollapsed && (
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-white truncate">
                    {user.name}
                  </p>
                  <Badge
                    variant="secondary"
                    className="mt-0.5 text-[10px] bg-blue-600/20 text-blue-400 border-blue-600/30"
                  >
                    {user.role?.role_name || "User"}
                  </Badge>
                </div>
              )}
              {!isCollapsed && (
                <Button
                  variant="ghost"
                  size="icon"
                  className="text-sidebar-text hover:text-white hover:bg-sidebar-hover"
                  onClick={logout}
                  title="Logout"
                >
                  <LogOut className="h-4 w-4" />
                </Button>
              )}
            </div>
          )}
        </div>
      </aside>
    </>
  );
}
