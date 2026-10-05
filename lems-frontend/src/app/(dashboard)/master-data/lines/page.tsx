"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useProductionLines, useCreateProductionLine, useUpdateProductionLine, useDeleteProductionLine } from "@/hooks/use-production-lines";
import { useAllDepartments } from "@/hooks/use-departments";
import { DataTable, Column } from "@/components/data-table";
import { PageHeader } from "@/components/page-header";
import { DeleteDialog } from "@/components/delete-dialog";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ProductionLine } from "@/types";
import { Loader2 } from "lucide-react";

const schema = z.object({
  department_id: z.number().min(1, "Department is required"),
  line_name: z.string().min(1, "Name is required"),
  description: z.string().optional(),
});

type FormType = z.infer<typeof schema>;

export default function ProductionLinesPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState("id");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [selected, setSelected] = useState<ProductionLine | null>(null);

  const { data, isLoading } = useProductionLines({ page, per_page: 15, search, sort_by: sortBy, sort_order: sortOrder });
  const { data: departments } = useAllDepartments();
  const createMutation = useCreateProductionLine();
  const updateMutation = useUpdateProductionLine();
  const deleteMutation = useDeleteProductionLine();

  const form = useForm<FormType>({ resolver: zodResolver(schema) });

  const openCreate = () => {
    setSelected(null);
    form.reset({ department_id: 0, line_name: "", description: "" });
    setDialogOpen(true);
  };

  const openEdit = (item: ProductionLine) => {
    setSelected(item);
    form.reset({ department_id: item.department_id, line_name: item.line_name, description: item.description || "" });
    setDialogOpen(true);
  };

  const openDelete = (item: ProductionLine) => { setSelected(item); setDeleteOpen(true); };

  const onSubmit = (formData: FormType) => {
    if (selected) updateMutation.mutate({ id: selected.id, data: formData }, { onSuccess: () => setDialogOpen(false) });
    else createMutation.mutate(formData, { onSuccess: () => setDialogOpen(false) });
  };

  const handleSort = (key: string) => {
    if (sortBy === key) setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    else { setSortBy(key); setSortOrder("asc"); }
  };

  const columns: Column<ProductionLine>[] = [
    { key: "id", header: "ID", sortable: true },
    { key: "line_name", header: "Name", sortable: true },
    { key: "department", header: "Department", render: (item) => item.department?.department_name || "-" },
    { key: "description", header: "Description" },
  ];

  const isSaving = createMutation.isPending || updateMutation.isPending;

  return (
    <div className="space-y-6">
      <PageHeader title="Production Lines" description="Manage production lines" action={{ label: "Add Line", onClick: openCreate }} />

      <DataTable columns={columns} data={data?.data || []} isLoading={isLoading} searchPlaceholder="Search lines..." searchValue={search} onSearchChange={(v) => { setSearch(v); setPage(1); }} onEdit={openEdit} onDelete={openDelete} currentPage={data?.current_page || 1} totalPages={data?.last_page || 1} onPageChange={setPage} totalItems={data?.total || 0} sortBy={sortBy} sortOrder={sortOrder} onSort={handleSort} />

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>{selected ? "Edit Line" : "Add Line"}</DialogTitle></DialogHeader>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label>Department</Label>
              <Select value={form.watch("department_id")?.toString()} onValueChange={(v) => form.setValue("department_id", parseInt(v))}>
                <SelectTrigger><SelectValue placeholder="Select department" /></SelectTrigger>
                <SelectContent>
                  {departments?.map((d) => <SelectItem key={d.id} value={d.id.toString()}>{d.department_name}</SelectItem>)}
                </SelectContent>
              </Select>
              {form.formState.errors.department_id && <p className="text-sm text-destructive">{form.formState.errors.department_id.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Line Name</Label>
              <Input {...form.register("line_name")} placeholder="e.g. Line A" />
              {form.formState.errors.line_name && <p className="text-sm text-destructive">{form.formState.errors.line_name.message}</p>}
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

      <DeleteDialog open={deleteOpen} onOpenChange={setDeleteOpen} onConfirm={() => { if (selected) deleteMutation.mutate(selected.id, { onSuccess: () => setDeleteOpen(false) }); }} title="Delete Line" description={`Are you sure you want to delete "${selected?.line_name}"?`} isLoading={deleteMutation.isPending} />
    </div>
  );
}
