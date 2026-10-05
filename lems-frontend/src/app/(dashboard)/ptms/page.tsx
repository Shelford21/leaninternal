"use client";

import { useState } from "react";
import { usePtmsReports, useDeletePtmsReport } from "@/hooks/use-ptms-reports";
import { DataTable, Column } from "@/components/data-table";
import { PageHeader } from "@/components/page-header";
import { DeleteDialog } from "@/components/delete-dialog";
import { Badge } from "@/components/ui/badge";
import { PtmsReport } from "@/types";
import { useRouter } from "next/navigation";
import { Eye, Trash2, Plus } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function PtmsReportsPage() {
  const router = useRouter();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState("id");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [selected, setSelected] = useState<PtmsReport | null>(null);

  const { data, isLoading } = usePtmsReports({ page, per_page: 15, search, sort_by: sortBy, sort_order: sortOrder });
  const deleteMutation = useDeletePtmsReport();

  const handleSort = (key: string) => {
    if (sortBy === key) setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    else { setSortBy(key); setSortOrder("asc"); }
  };

  const columns: Column<PtmsReport>[] = [
    { key: "id", header: "ID", sortable: true },
    { key: "factory", header: "Factory", render: (item) => item.factory?.factory_name || "-" },
    { key: "department", header: "Department", render: (item) => item.department?.department_name || "-" },
    { key: "production_line", header: "Line", render: (item) => item.production_line?.line_name || "-" },
    { key: "article", header: "Article", render: (item) => item.article?.article_name || "-" },
    { key: "smv", header: "SMV", sortable: true, render: (item) => item.smv?.toFixed(2) || "-" },
    { key: "status", header: "Status", sortable: true, render: (item) => (
      <Badge variant={item.status === "completed" ? "default" : item.status === "in_progress" ? "secondary" : "outline"}>
        {item.status}
      </Badge>
    )},
    { key: "created_at", header: "Created", sortable: true, render: (item) => item.created_at ? new Date(item.created_at).toLocaleDateString() : "-" },
  ];

  const openDelete = (item: PtmsReport) => { setSelected(item); setDeleteOpen(true); };

  return (
    <div className="space-y-6">
      <PageHeader
        title="PTMS Reports"
        description="Production Time Measurement Study reports"
        action={{ label: "New Report", onClick: () => router.push("/ptms/new") }}
      />

      <DataTable
        columns={columns}
        data={data?.data || []}
        isLoading={isLoading}
        searchPlaceholder="Search reports..."
        searchValue={search}
        onSearchChange={(v) => { setSearch(v); setPage(1); }}
        onView={(item) => router.push(`/ptms/${item.id}`)}
        onDelete={openDelete}
        currentPage={data?.current_page || 1}
        totalPages={data?.last_page || 1}
        onPageChange={setPage}
        totalItems={data?.total || 0}
        sortBy={sortBy}
        sortOrder={sortOrder}
        onSort={handleSort}
      />

      <DeleteDialog
        open={deleteOpen}
        onOpenChange={setDeleteOpen}
        onConfirm={() => { if (selected) deleteMutation.mutate(selected.id, { onSuccess: () => setDeleteOpen(false) }); }}
        title="Delete PTMS Report"
        description={`Are you sure you want to delete this PTMS report? This action cannot be undone.`}
        isLoading={deleteMutation.isPending}
      />
    </div>
  );
}
