"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useSewingStopFactors, useCreateSewingStopFactor, useUpdateSewingStopFactor, useDeleteSewingStopFactor } from "@/hooks/use-sewing-stop-factors";
import { useAuthStore } from "@/store/auth-store";
import { DataTable, Column } from "@/components/data-table";
import { PageHeader } from "@/components/page-header";
import { DeleteDialog } from "@/components/delete-dialog";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { SewingStopFactor } from "@/types";
import { Loader2 } from "lucide-react";

const schema = z.object({
  code: z.string().min(1, "Code is required"),
  factor_name: z.string().min(1, "Name is required"),
  factor_value: z.number().min(0, "Value must be positive"),
  tolerance: z.number().optional(),
  description: z.string().optional(),
  status: z.string().default("active"),
});

type FormType = z.infer<typeof schema>;

export default function StopFactorsPage() {
  const { token } = useAuthStore();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState("id");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [selected, setSelected] = useState<SewingStopFactor | null>(null);

  const { data, isLoading } = useSewingStopFactors({ page, per_page: 15, search, sort_by: sortBy, sort_order: sortOrder });
  const createMutation = useCreateSewingStopFactor();
  const updateMutation = useUpdateSewingStopFactor();
  const deleteMutation = useDeleteSewingStopFactor();

  const form = useForm<FormType>({ resolver: zodResolver(schema) });

  const openCreate = () => { setSelected(null); form.reset({ code: "", factor_name: "", factor_value: 0, tolerance: 0, description: "", status: "active" }); setDialogOpen(true); };
  const openEdit = (item: SewingStopFactor) => { setSelected(item); form.reset({ code: item.code, factor_name: item.factor_name, factor_value: item.factor_value, tolerance: item.tolerance || 0, description: item.description || "", status: item.status }); setDialogOpen(true); };
  const openDelete = (item: SewingStopFactor) => { setSelected(item); setDeleteOpen(true); };

  const onSubmit = (formData: FormType) => {
    if (selected) updateMutation.mutate({ id: selected.id, data: formData }, { onSuccess: () => setDialogOpen(false) });
    else createMutation.mutate(formData, { onSuccess: () => setDialogOpen(false) });
  };

  const handleSort = (key: string) => {
    if (sortBy === key) setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    else { setSortBy(key); setSortOrder("asc"); }
  };

  const columns: Column<SewingStopFactor>[] = [
    { key: "code", header: "Code", sortable: true },
    { key: "factor_name", header: "Name", sortable: true },
    { key: "factor_value", header: "Value", sortable: true },
    { key: "tolerance", header: "Tolerance", sortable: true },
    { key: "description", header: "Description" },
  ];

  const isSaving = createMutation.isPending || updateMutation.isPending;

  return (
    <div className="space-y-6">
      <PageHeader title="Stop Factors" description="Manage sewing stop factors" action={{ label: "Add Stop Factor", onClick: openCreate }} />

      <DataTable columns={columns} data={data?.data || []} isLoading={isLoading} searchPlaceholder="Search stop factors..." searchValue={search} onSearchChange={(v) => { setSearch(v); setPage(1); }} autocompleteEndpoint="/api/master/stop-factors/search" authToken={token ?? undefined} onEdit={openEdit} onDelete={openDelete} currentPage={data?.current_page || 1} totalPages={data?.last_page || 1} onPageChange={setPage} totalItems={data?.total || 0} sortBy={sortBy} sortOrder={sortOrder} onSort={handleSort} />

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>{selected ? "Edit Stop Factor" : "Add Stop Factor"}</DialogTitle></DialogHeader>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label>Code</Label>
              <Input {...form.register("code")} placeholder="e.g. STF-001" />
              {form.formState.errors.code && <p className="text-sm text-destructive">{form.formState.errors.code.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Factor Name</Label>
              <Input {...form.register("factor_name")} placeholder="e.g. Bobbin Change" />
              {form.formState.errors.factor_name && <p className="text-sm text-destructive">{form.formState.errors.factor_name.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Factor Value</Label>
              <Input type="number" step="0.01" {...form.register("factor_value", { valueAsNumber: true })} placeholder="e.g. 0.50" />
              {form.formState.errors.factor_value && <p className="text-sm text-destructive">{form.formState.errors.factor_value.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Tolerance</Label>
              <Input type="number" step="0.01" {...form.register("tolerance", { valueAsNumber: true })} placeholder="e.g. 0.10" />
            </div>
            <div className="space-y-2">
              <Label>Description</Label>
              <Textarea {...form.register("description")} placeholder="Description" />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={isSaving}>{isSaving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}{selected ? "Update" : "Create"}</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <DeleteDialog open={deleteOpen} onOpenChange={setDeleteOpen} onConfirm={() => { if (selected) deleteMutation.mutate(selected.id, { onSuccess: () => setDeleteOpen(false) }); }} title="Delete Stop Factor" description={`Are you sure you want to delete "${selected?.factor_name}"?`} isLoading={deleteMutation.isPending} />
    </div>
  );
}
