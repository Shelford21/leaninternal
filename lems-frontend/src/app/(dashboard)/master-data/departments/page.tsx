"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useDepartments, useCreateDepartment, useUpdateDepartment, useDeleteDepartment } from "@/hooks/use-departments";
import { useAllFactories } from "@/hooks/use-factories";
import { DataTable, Column } from "@/components/data-table";
import { PageHeader } from "@/components/page-header";
import { DeleteDialog } from "@/components/delete-dialog";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Department } from "@/types";
import { Loader2 } from "lucide-react";

const schema = z.object({
  factory_id: z.number().min(1, "Factory is required"),
  department_name: z.string().min(1, "Name is required"),
  desription: z.string().optional(),
});

type FormType = z.infer<typeof schema>;

export default function DepartmentsPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState("id");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [selected, setSelected] = useState<Department | null>(null);

  const { data, isLoading } = useDepartments({ page, per_page: 15, search, sort_by: sortBy, sort_order: sortOrder });
  const { data: factories } = useAllFactories();
  const createMutation = useCreateDepartment();
  const updateMutation = useUpdateDepartment();
  const deleteMutation = useDeleteDepartment();

  const form = useForm<FormType>({ resolver: zodResolver(schema) });

  const openCreate = () => {
    setSelected(null);
    form.reset({ factory_id: 0, department_name: "", desription: "" });
    setDialogOpen(true);
  };

  const openEdit = (item: Department) => {
    setSelected(item);
    form.reset({
      factory_id: item.factory_id,
      department_name: item.department_name,
      desription: item.desription || "",
    });
    setDialogOpen(true);
  };

  const openDelete = (item: Department) => {
    setSelected(item);
    setDeleteOpen(true);
  };

  const onSubmit = (formData: FormType) => {
    if (selected) {
      updateMutation.mutate({ id: selected.id, data: formData }, { onSuccess: () => setDialogOpen(false) });
    } else {
      createMutation.mutate(formData, { onSuccess: () => setDialogOpen(false) });
    }
  };

  const onDelete = () => {
    if (selected) {
      deleteMutation.mutate(selected.id, { onSuccess: () => setDeleteOpen(false) });
    }
  };

  const handleSort = (key: string) => {
    if (sortBy === key) setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    else { setSortBy(key); setSortOrder("asc"); }
  };

  const columns: Column<Department>[] = [
    { key: "id", header: "ID", sortable: true },
    { key: "department_name", header: "Name", sortable: true },
    { key: "factory", header: "Factory", render: (item) => item.factory?.factory_name || "-" },
    { key: "desription", header: "Description" },
  ];

  const isSaving = createMutation.isPending || updateMutation.isPending;

  return (
    <div className="space-y-6">
      <PageHeader title="Departments" description="Manage departments" action={{ label: "Add Department", onClick: openCreate }} />

      <DataTable
        columns={columns}
        data={data?.data || []}
        isLoading={isLoading}
        searchPlaceholder="Search departments..."
        searchValue={search}
        onSearchChange={(v) => { setSearch(v); setPage(1); }}
        onEdit={openEdit}
        onDelete={openDelete}
        currentPage={data?.current_page || 1}
        totalPages={data?.last_page || 1}
        onPageChange={setPage}
        totalItems={data?.total || 0}
        sortBy={sortBy}
        sortOrder={sortOrder}
        onSort={handleSort}
      />

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{selected ? "Edit Department" : "Add Department"}</DialogTitle>
          </DialogHeader>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label>Factory</Label>
              <Select
                value={form.watch("factory_id")?.toString()}
                onValueChange={(v) => form.setValue("factory_id", parseInt(v))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select factory" />
                </SelectTrigger>
                <SelectContent>
                  {factories?.map((f) => (
                    <SelectItem key={f.id} value={f.id.toString()}>{f.factory_name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.formState.errors.factory_id && (
                <p className="text-sm text-destructive">{form.formState.errors.factory_id.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label>Department Name</Label>
              <Input {...form.register("department_name")} placeholder="e.g. Sewing" />
              {form.formState.errors.department_name && (
                <p className="text-sm text-destructive">{form.formState.errors.department_name.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label>Description</Label>
              <Textarea {...form.register("desription")} placeholder="Description" />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={isSaving}>
                {isSaving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {selected ? "Update" : "Create"}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <DeleteDialog
        open={deleteOpen}
        onOpenChange={setDeleteOpen}
        onConfirm={onDelete}
        title="Delete Department"
        description={`Are you sure you want to delete "${selected?.department_name}"?`}
        isLoading={deleteMutation.isPending}
      />
    </div>
  );
}
