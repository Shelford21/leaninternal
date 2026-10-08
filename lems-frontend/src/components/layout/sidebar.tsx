"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import { useSidebarStore } from "@/store/sidebar-store";
import { useAuthStore } from "@/store/auth-store";
import {
  LayoutDashboard,
  Activity,
  Gauge,
  Sparkles,
  Users,
  Wrench,
  ArrowRight,
  User,
  FileText,
  KeyRound,
  Trash2,
  Zap,
  Settings,
  Menu,
  ChevronDown,
  LogOut,
  ChevronLeft,
  Factory,
  X,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";

interface NavItem {
  label: string;
  href: string;
  icon?: React.ElementType;
  /** show only for these role names (undefined = everyone) */
  roles?: string[];
  /** collapsible sub-links (e.g. System > Logs) */
  children?: { label: string; href: string }[];
}

interface NavGroup {
  title: string;
  items: NavItem[];
  /** show only for these role names (undefined = everyone) */
  roles?: string[];
  /** collapsible group with plain text links (Data Masters) */
  dropdown?: boolean;
}

/**
 * Sidebar — replica of resources/views/layouts/app.blade.php (§4 of the
 * Data Master audit). Section order and item labels are verbatim Laravel.
 */
const navigation: NavGroup[] = [
  {
    title: "Main",
    items: [{ label: "Dashboard", href: "/dashboard", icon: LayoutDashboard }],
  },
  {
    title: "Data Masters",
    dropdown: true,
    items: [
      { label: "Processes", href: "/master-data/processes" },
      { label: "Employees", href: "/master-data/operators" },
      { label: "Articles", href: "/master-data/articles" },
      { label: "GSD Elements", href: "/master-data/gsd-elements" },
      { label: "Factories", href: "/master-data/factories" },
      { label: "Departments", href: "/master-data/departments" },
      { label: "Destinations", href: "/master-data/destinations" },
      { label: "Production Lines", href: "/master-data/production-lines" },
      { label: "Skill Gradings", href: "/master-data/skill-gradings" },
      { label: "Divisions", href: "/master-data/divisions" },
      { label: "Sections", href: "/master-data/sections" },
      { label: "Machine Types", href: "/master-data/machine-types" },
      { label: "Components/Panels", href: "/master-data/components-panels" },
      { label: "Machine Numbers", href: "/master-data/machine-numbers" },
      { label: "Shifts", href: "/master-data/shifts" },
      { label: "Failure Modes", href: "/master-data/failure-modes" },
      { label: "Mechanics", href: "/master-data/mechanics" },
      { label: "Spare Parts", href: "/master-data/spare-parts" },
      { label: "Genders", href: "/master-data/genders" },
      { label: "Production Roles", href: "/master-data/production-roles" },
      { label: "Educational Level", href: "/master-data/educational-levels" },
      { label: "Status PKWTT", href: "/master-data/status-pkwtt" },
    ],
  },
  {
    title: "Lean Operations",
    /* out-of-scope features kept reachable (audit §14): targets point at the
       existing hub pages; "PTMS Reports" keeps the existing Reports page
       reachable (deviation from §4 item list). */
    items: [
      { label: "Operational Breakdown", href: "/operations", icon: Activity },
      { label: "Line Balancing", href: "/operations/line-balancing", icon: Gauge },
      { label: "Kaizen", href: "/operations", icon: Sparkles },
      { label: "Skills & OSCP", href: "/operations", icon: Users },
      { label: "TPM", href: "/operations", icon: Wrench },
      { label: "VSM", href: "/operations", icon: ArrowRight },
      { label: "Employees Profile", href: "/employees-profile", icon: User },
      { label: "PTMS Reports", href: "/ptms", icon: FileText },
    ],
  },
  {
    title: "Management",
    roles: ["developer", "admin"],
    items: [
      { label: "Credentials", href: "/credentials", icon: KeyRound },
      { label: "Clear Cache", href: "/system/clear-cache", icon: Trash2, roles: ["developer"] },
      { label: "Speed Test", href: "/system/speed-test", icon: Zap, roles: ["developer"] },
      { label: "Hard Delete", href: "/hard-delete", icon: Trash2, roles: ["developer"] },
    ],
  },
  {
    title: "System",
    roles: ["developer"],
    items: [
      {
        label: "Logs",
        href: "/login-logs",
        icon: FileText,
        children: [
          { label: "Login Logs", href: "/login-logs" },
          { label: "Activity Logs", href: "/activity-logs" },
        ],
      },
      { label: "Settings", href: "/profile", icon: Settings },
    ],
  },
];

export function Sidebar() {
  const pathname = usePathname();
  const { isCollapsed, isMobileOpen, toggle, setMobileOpen } = useSidebarStore();
  const { user, logout } = useAuthStore();
  const roleName = user?.role?.role_name;

  /* collapsible groups (Data Masters / Logs) — Laravel opens the active one
     on page load (`$isDataMasterActive`) */
  const [openGroups, setOpenGroups] = useState<Record<string, boolean>>({});
  useEffect(() => {
    setOpenGroups((prev) => ({
      ...prev,
      "Data Masters": pathname.startsWith("/master-data"),
      Logs: pathname.startsWith("/login-logs") || pathname.startsWith("/activity-logs"),
    }));
  }, [pathname]);

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
          {navigation
            .filter((group) => !group.roles || (roleName && group.roles.includes(roleName)))
            .map((group) => {
              const items = group.items.filter(
                (item) => !item.roles || (roleName && item.roles.includes(roleName))
              );
              const groupOpen = !!openGroups[group.title] || isCollapsed;

              /* Find the most specific matching href in this group
                 so that e.g. /operations/line-balancing wins over /operations */
              const bestMatch = items
                .filter((it) => !it.children && (pathname === it.href || pathname.startsWith(it.href + "/")))
                .sort((a, b) => b.href.length - a.href.length)[0]?.href ?? null;

              return (
                <div key={group.title}>
                  {!isCollapsed && (
                    <p className="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-sidebar-text/60">
                      {group.title}
                    </p>
                  )}

                  {/* Collapsible group (Data Masters) — single toggle button
                      with all items as flat links inside (matches Laravel blade) */}
                  {group.dropdown && (
                    <div>
                      {!isCollapsed && (
                        <button
                          type="button"
                          title={group.title}
                          onClick={() => setOpenGroups((prev) => ({ ...prev, [group.title]: !prev[group.title] }))}
                          className={cn(
                            "flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors",
                            groupOpen
                              ? "bg-sidebar-active text-sidebar-text-active"
                              : "text-sidebar-text hover:bg-sidebar-hover hover:text-sidebar-text-active"
                          )}
                        >
                          <Menu className="h-5 w-5 shrink-0" />
                          <span className="flex-1 text-left">{group.title}</span>
                          <ChevronDown
                            className={cn("h-4 w-4 transition-transform", groupOpen && "rotate-180")}
                          />
                        </button>
                      )}
                      {(groupOpen || isCollapsed) && (
                        <div className={cn("space-y-0.5", !isCollapsed && "mt-1 ml-4 border-l border-sidebar-hover pl-3")}>
                          {items.map((child) => {
                            const childActive =
                              pathname === child.href || pathname.startsWith(child.href + "/");
                            return (
                              <Link
                                key={child.href}
                                href={child.href}
                                title={child.label}
                                onClick={() => setMobileOpen(false)}
                                className={cn(
                                  "flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors",
                                  childActive
                                    ? "bg-sidebar-active text-sidebar-text-active"
                                    : "text-sidebar-text hover:bg-sidebar-hover hover:text-sidebar-text-active",
                                  isCollapsed && "justify-center py-2.5 text-sm"
                                )}
                              >
                                {isCollapsed ? child.label.charAt(0) : child.label}
                              </Link>
                            );
                          })}
                        </div>
                      )}
                    </div>
                  )}

                  {/* Non-dropdown groups */}
                  {!group.dropdown && (
                    <div className="space-y-1">
                      {items.map((item) => {
                        const isActive =
                          !!bestMatch && item.href === bestMatch;

                        /* Item with sub-links (System > Logs) */
                        if (item.children) {
                          const childOpen = !!openGroups[item.label];
                          return (
                            <div key={item.label}>
                              <button
                                type="button"
                                title={item.label}
                                onClick={() =>
                                  setOpenGroups((prev) => ({ ...prev, [item.label]: !childOpen }))
                                }
                                className={cn(
                                  "flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors",
                                  childOpen
                                    ? "bg-sidebar-active text-sidebar-text-active"
                                    : "text-sidebar-text hover:bg-sidebar-hover hover:text-sidebar-text-active",
                                  isCollapsed && "justify-center px-2"
                                )}
                              >
                                {item.icon && <item.icon className="h-5 w-5 shrink-0" />}
                                {!isCollapsed && (
                                  <>
                                    <span className="flex-1 text-left">{item.label}</span>
                                    <ChevronDown
                                      className={cn("h-4 w-4 transition-transform", childOpen && "rotate-180")}
                                    />
                                  </>
                                )}
                              </button>
                              {childOpen && !isCollapsed && (
                                <div className="mt-1 ml-4 space-y-0.5 border-l border-sidebar-hover pl-3">
                                  {item.children.map((child) => {
                                    const childActive =
                                      pathname === child.href || pathname.startsWith(child.href + "/");
                                    return (
                                      <Link
                                        key={child.href}
                                        href={child.href}
                                        title={child.label}
                                        onClick={() => setMobileOpen(false)}
                                        className={cn(
                                          "flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors",
                                          childActive
                                            ? "bg-sidebar-active text-sidebar-text-active"
                                            : "text-sidebar-text hover:bg-sidebar-hover hover:text-sidebar-text-active"
                                        )}
                                      >
                                        {child.label}
                                      </Link>
                                    );
                                  })}
                                </div>
                              )}
                            </div>
                          );
                        }

                        /* Plain link */
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
                            {item.icon && <item.icon className="h-5 w-5 shrink-0" />}
                            {!isCollapsed && <span>{item.label}</span>}
                          </Link>
                        );
                      })}
                    </div>
                  )}
                </div>
              );
            })}
        </nav>

        {/* User section */}
        <div className="border-t border-sidebar-hover p-4">
          {user && (
            <div className={cn("flex items-center gap-3", isCollapsed && "justify-center")}>
              <Link
                href="/profile"
                onClick={() => setMobileOpen(false)}
                title="Profile"
                className={cn("flex items-center gap-3 min-w-0", isCollapsed && "justify-center")}
              >
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
              </Link>
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
