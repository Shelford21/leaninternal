"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useOperators, useCreateOperator, useUpdateOperator, useDeleteOperator } from "@/hooks/use-operators";
import { DataTable, Column } from "@/components/data-table";
import { PageHeader } from "@/components/page-header";
import { DeleteDialog } from "@/components/delete-dialog";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Operator } from "@/types";
import { Loader2 } from "lucide-react";

const schema = z.object({
  employee_number: z.string().min(1, "Employee number is required"),
  operator_name: z.string().min(1, "Name is required"),
  status: z.string().min(1, "Status is required"),
});

type FormType = z.infer<typeof schema>;

export default function OperatorsPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState("id");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [selected, setSelected] = useState<Operator | null>(null);

  const { data, isLoading } = useOperators({ page, per_page: 15, search, sort_by: sortBy, sort_order: sortOrder });
  const createMutation = useCreateOperator();
  const updateMutation = useUpdateOperator();
  const deleteMutation = useDeleteOperator();

  const form = useForm<FormType>({ resolver: zodResolver(schema) });

  const openCreate = () => { setSelected(null); form.reset({ employee_number: "", operator_name: "", status: "active" }); setDialogOpen(true); };
  const openEdit = (item: Operator) => { setSelected(item); form.reset({ employee_number: item.employee_number, operator_name: item.operator_name, status: item.status }); setDialogOpen(true); };
  const openDelete = (item: Operator) => { setSelected(item); setDeleteOpen(true); };

  const onSubmit = (formData: FormType) => {
    if (selected) updateMutation.mutate({ id: selected.id, data: formData }, { onSuccess: () => setDialogOpen(false) });
    else createMutation.mutate(formData, { onSuccess: () => setDialogOpen(false) });
  };

  const handleSort = (key: string) => {
    if (sortBy === key) setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    else { setSortBy(key); setSortOrder("asc"); }
  };

  const columns: Column<Operator>[] = [
    { key: "employee_number", header: "Employee No.", sortable: true },
    { key: "operator_name", header: "Name", sortable: true },
    { key: "status", header: "Status", render: (item) => <Badge variant={item.status === "active" ? "default" : "secondary"}>{item.status}</Badge> },
  ];

  const isSaving = createMutation.isPending || updateMutation.isPending;

  return (
    <div className="space-y-6">
      <PageHeader title="Operators" description="Manage operators" action={{ label: "Add Operator", onClick: openCreate }} />

      <DataTable columns={columns} data={data?.data || []} isLoading={isLoading} searchPlaceholder="Search operators..." searchValue={search} onSearchChange={(v) => { setSearch(v); setPage(1); }} onEdit={openEdit} onDelete={openDelete} currentPage={data?.current_page || 1} totalPages={data?.last_page || 1} onPageChange={setPage} totalItems={data?.total || 0} sortBy={sortBy} sortOrder={sortOrder} onSort={handleSort} />

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>{selected ? "Edit Operator" : "Add Operator"}</DialogTitle></DialogHeader>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label>Employee Number</Label>
              <Input {...form.register("employee_number")} placeholder="e.g. EMP-001" />
              {form.formState.errors.employee_number && <p className="text-sm text-destructive">{form.formState.errors.employee_number.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Operator Name</Label>
              <Input {...form.register("operator_name")} placeholder="e.g. John Doe" />
              {form.formState.errors.operator_name && <p className="text-sm text-destructive">{form.formState.errors.operator_name.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Status</Label>
              <Select value={form.watch("status")} onValueChange={(v) => form.setValue("status", v)}>
                <SelectTrigger><SelectValue placeholder="Select status" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={isSaving}>{isSaving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}{selected ? "Update" : "Create"}</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <DeleteDialog open={deleteOpen} onOpenChange={setDeleteOpen} onConfirm={() => { if (selected) deleteMutation.mutate(selected.id, { onSuccess: () => setDeleteOpen(false) }); }} title="Delete Operator" description={`Are you sure you want to delete "${selected?.operator_name}"?`} isLoading={deleteMutation.isPending} />
    </div>
  );
}
