"use client";

import { useState, useRef, useCallback } from "react";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Search,
  ChevronLeft,
  ChevronRight,
  ArrowUpDown,
  ArrowUp,
  ArrowDown,
  MoreHorizontal,
  Pencil,
  Trash2,
  Eye,
} from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

export interface Column<T> {
  key: string;
  header: string;
  sortable?: boolean;
  render?: (item: T) => React.ReactNode;
}

interface DataTableProps<T> {
  columns: Column<T>[];
  data: T[];
  isLoading?: boolean;
  searchPlaceholder?: string;
  searchValue?: string;
  onSearchChange?: (value: string) => void;
  onEdit?: (item: T) => void;
  onDelete?: (item: T) => void;
  onView?: (item: T) => void;
  customActions?: (item: T) => React.ReactNode;
  currentPage?: number;
  totalPages?: number;
  onPageChange?: (page: number) => void;
  totalItems?: number;
  perPage?: number;
  sortBy?: string;
  sortOrder?: "asc" | "desc";
  onSort?: (key: string) => void;
  emptyMessage?: string;
  actions?: boolean;
  /** Per-row gate for the Edit action. Return false to hide. */
  canEdit?: (item: T) => boolean;
  /** Per-row gate for the Delete action. Return false to hide. */
  canDelete?: (item: T) => boolean;
  /** Per-row gate for custom actions. Return false to hide. */
  canAct?: (item: T) => boolean;
  /** API endpoint for autocomplete search. Called with ?q= parameter. */
  autocompleteEndpoint?: string;
  /** Authorization header value for autocomplete requests. */
  authToken?: string;
  /** Called when an autocomplete suggestion is selected. */
  onAutocompleteSelect?: (item: { id: number; label: string; description?: string }) => void;
}

export function DataTable<T extends { id: number }>({
  columns,
  data,
  isLoading,
  searchPlaceholder = "Search...",
  searchValue = "",
  onSearchChange,
  onEdit,
  onDelete,
  onView,
  customActions,
  currentPage = 1,
  totalPages = 1,
  onPageChange,
  totalItems = 0,
  perPage = 15,
  sortBy,
  sortOrder,
  onSort,
  emptyMessage = "No data found.",
  actions = true,
  canEdit,
  canDelete,
  canAct,
  autocompleteEndpoint,
  authToken,
  onAutocompleteSelect,
}: DataTableProps<T>) {
  // Autocomplete state
  const [acItems, setAcItems] = useState<{ id: number; label: string; description?: string }[]>([]);
  const [acOpen, setAcOpen] = useState(false);
  const acTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const handleSearchWithAc = useCallback(
    (v: string) => {
      onSearchChange?.(v);
      if (!autocompleteEndpoint) return;
      if (acTimer.current) clearTimeout(acTimer.current);
      const q = v.trim();
      if (q.length < 1) { setAcItems([]); setAcOpen(false); return; }
      acTimer.current = setTimeout(async () => {
        try {
          const res = await fetch(`${autocompleteEndpoint}?q=${encodeURIComponent(q)}`, {
            headers: { "Content-Type": "application/json", ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}) },
          });
          if (!res.ok) return;
          const body = await res.json().catch(() => null);
          const results: { id: number; label: string; description?: string }[] = Array.isArray(body?.data) ? body.data : Array.isArray(body) ? body : [];
          setAcItems(results);
          setAcOpen(results.length > 0);
        } catch { /* ignore */ }
      }, 300);
    },
    [onSearchChange, autocompleteEndpoint, authToken],
  );
  if (isLoading) {
    return (
      <div className="space-y-3">
        <div className="flex items-center gap-2">
          <Skeleton className="h-10 w-64" />
        </div>
        <div className="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                {columns.map((col) => (
                  <TableHead key={col.key}>
                    <Skeleton className="h-4 w-20" />
                  </TableHead>
                ))}
                {actions && <TableHead><Skeleton className="h-4 w-10" /></TableHead>}
              </TableRow>
            </TableHeader>
            <TableBody>
              {Array.from({ length: 5 }).map((_, i) => (
                <TableRow key={i}>
                  {columns.map((col) => (
                    <TableCell key={col.key}>
                      <Skeleton className="h-4 w-24" />
                    </TableCell>
                  ))}
                  {actions && (
                    <TableCell>
                      <Skeleton className="h-4 w-10" />
                    </TableCell>
                  )}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Search */}
      {onSearchChange && (
        <div className="flex items-center gap-2">
          <div className="relative flex-1 max-w-sm">
            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder={searchPlaceholder}
              value={searchValue}
              onChange={(e) => handleSearchWithAc(e.target.value)}
              onBlur={() => setTimeout(() => setAcOpen(false), 150)}
              onFocus={() => acItems.length > 0 && setAcOpen(true)}
              autoComplete="off"
              className="pl-8"
            />
            {acOpen && acItems.length > 0 && (
              <div className="absolute left-0 top-full mt-1 z-50 w-full max-h-60 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 shadow-lg">
                {acItems.map((item) => (
                  <div
                    key={item.id}
                    className="cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-slate-600"
                    onMouseDown={() => {
                      if (onAutocompleteSelect) {
                        onAutocompleteSelect(item);
                      } else {
                        onSearchChange(item.label);
                      }
                      setAcOpen(false);
                    }}
                  >
                    <div className="font-medium text-slate-800 dark:text-slate-200">{item.label}</div>
                    {item.description && <div className="text-xs text-slate-500 dark:text-slate-400 truncate">{item.description}</div>}
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Table */}
      <div className="rounded-md border overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              {columns.map((col) => (
                <TableHead key={col.key}>
                  {col.sortable && onSort ? (
                    <Button
                      variant="ghost"
                      size="sm"
                      className="-ml-3 h-8 data-[state=open]:bg-accent"
                      onClick={() => onSort(col.key)}
                    >
                      <span>{col.header}</span>
                      {sortBy === col.key ? (
                        sortOrder === "asc" ? (
                          <ArrowUp className="ml-2 h-4 w-4" />
                        ) : (
                          <ArrowDown className="ml-2 h-4 w-4" />
                        )
                      ) : (
                        <ArrowUpDown className="ml-2 h-4 w-4" />
                      )}
                    </Button>
                  ) : (
                    col.header
                  )}
                </TableHead>
              ))}
              {actions && (onEdit || onDelete || onView || customActions) && (
                <TableHead className="w-[100px]">Actions</TableHead>
              )}
            </TableRow>
          </TableHeader>
          <TableBody>
            {data.length === 0 ? (
              <TableRow>
                <TableCell
                  colSpan={columns.length + (actions ? 1 : 0)}
                  className="h-24 text-center text-muted-foreground"
                >
                  {emptyMessage}
                </TableCell>
              </TableRow>
            ) : (
              data.map((item) => (
                <TableRow key={item.id}>
                  {columns.map((col) => (
                    <TableCell key={col.key}>
                      {col.render
                        ? col.render(item)
                        : String((item as Record<string, unknown>)[col.key] ?? "")}
                    </TableCell>
                  ))}
                  {actions && (onEdit || onDelete || onView || customActions) && (
                    <TableCell>
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" className="h-8 w-8 p-0">
                            <span className="sr-only">Open menu</span>
                            <MoreHorizontal className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          {onView && (
                            <DropdownMenuItem onClick={() => onView(item)}>
                              <Eye className="mr-2 h-4 w-4" />
                              View
                            </DropdownMenuItem>
                          )}
                          {onEdit && (!canEdit || canEdit(item)) && (
                            <DropdownMenuItem onClick={() => onEdit(item)}>
                              <Pencil className="mr-2 h-4 w-4" />
                              Edit
                            </DropdownMenuItem>
                          )}
                          {customActions && (!canAct || canAct(item)) && customActions(item)}
                          {onDelete && (!canDelete || canDelete(item)) && (
                            <DropdownMenuItem
                              onClick={() => onDelete(item)}
                              className="text-destructive"
                            >
                              <Trash2 className="mr-2 h-4 w-4" />
                              Delete
                            </DropdownMenuItem>
                          )}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  )}
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      {/* Pagination */}
      {totalPages > 1 && onPageChange && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-muted-foreground">
            Showing {(currentPage - 1) * perPage + 1} to{" "}
            {Math.min(currentPage * perPage, totalItems)} of {totalItems} results
          </p>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => onPageChange(currentPage - 1)}
              disabled={currentPage <= 1}
            >
              <ChevronLeft className="h-4 w-4" />
              Previous
            </Button>
            <span className="text-sm">
              Page {currentPage} of {totalPages}
            </span>
            <Button
              variant="outline"
              size="sm"
              onClick={() => onPageChange(currentPage + 1)}
              disabled={currentPage >= totalPages}
            >
              Next
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
