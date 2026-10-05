"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useFactories, useCreateFactory, useUpdateFactory, useDeleteFactory } from "@/hooks/use-factories";
import { DataTable, Column } from "@/components/data-table";
import { PageHeader } from "@/components/page-header";
import { DeleteDialog } from "@/components/delete-dialog";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Factory } from "@/types";
import { Loader2 } from "lucide-react";

const factorySchema = z.object({
  factory_name: z.string().min(1, "Name is required"),
  description: z.string().optional(),
});

type FactoryForm = z.infer<typeof factorySchema>;

export default function FactoriesPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState("id");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [selected, setSelected] = useState<Factory | null>(null);

  const { data, isLoading } = useFactories({ page, per_page: 15, search, sort_by: sortBy, sort_order: sortOrder });
  const createMutation = useCreateFactory();
  const updateMutation = useUpdateFactory();
  const deleteMutation = useDeleteFactory();

  const form = useForm<FactoryForm>({
    resolver: zodResolver(factorySchema),
  });

  const openCreate = () => {
    setSelected(null);
    form.reset({ factory_name: "", description: "" });
    setDialogOpen(true);
  };

  const openEdit = (item: Factory) => {
    setSelected(item);
    form.reset({
      factory_name: item.factory_name,
      description: item.description || "",
    });
    setDialogOpen(true);
  };

  const openDelete = (item: Factory) => {
    setSelected(item);
    setDeleteOpen(true);
  };

  const onSubmit = (formData: FactoryForm) => {
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
    if (sortBy === key) {
      setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    } else {
      setSortBy(key);
      setSortOrder("asc");
    }
  };

  const columns: Column<Factory>[] = [
    { key: "id", header: "ID", sortable: true },
    { key: "factory_name", header: "Name", sortable: true },
    { key: "description", header: "Description" },
  ];

  const isSaving = createMutation.isPending || updateMutation.isPending;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Factories"
        description="Manage factory locations"
        action={{ label: "Add Factory", onClick: openCreate }}
      />

      <DataTable
        columns={columns}
        data={data?.data || []}
        isLoading={isLoading}
        searchPlaceholder="Search factories..."
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

      {/* Create/Edit Dialog */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{selected ? "Edit Factory" : "Add Factory"}</DialogTitle>
          </DialogHeader>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label>Factory Name</Label>
              <Input {...form.register("factory_name")} placeholder="e.g. Main Factory" />
              {form.formState.errors.factory_name && (
                <p className="text-sm text-destructive">{form.formState.errors.factory_name.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label>Description</Label>
              <Textarea {...form.register("description")} placeholder="Description" />
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

      {/* Delete Dialog */}
      <DeleteDialog
        open={deleteOpen}
        onOpenChange={setDeleteOpen}
        onConfirm={onDelete}
        title="Delete Factory"
        description={`Are you sure you want to delete "${selected?.factory_name}"? This action cannot be undone.`}
        isLoading={deleteMutation.isPending}
      />
    </div>
  );
}
