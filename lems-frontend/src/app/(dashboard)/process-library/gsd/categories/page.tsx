"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useGsdCategories, useCreateGsdCategory, useUpdateGsdCategory, useDeleteGsdCategory } from "@/hooks/use-gsd-categories";
import { useAuthStore } from "@/store/auth-store";
import { DataTable, Column } from "@/components/data-table";
import { PageHeader } from "@/components/page-header";
import { DeleteDialog } from "@/components/delete-dialog";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { GsdCategory } from "@/types";
import { Loader2 } from "lucide-react";

const schema = z.object({
  category_name: z.string().min(1, "Name is required"),
  description: z.string().optional(),
  status: z.string().min(1, "Status is required"),
});

type FormType = z.infer<typeof schema>;

export default function GsdCategoriesPage() {
  const { token } = useAuthStore();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState("id");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [selected, setSelected] = useState<GsdCategory | null>(null);

  const { data, isLoading } = useGsdCategories({ page, per_page: 15, search, sort_by: sortBy, sort_order: sortOrder });
  const createMutation = useCreateGsdCategory();
  const updateMutation = useUpdateGsdCategory();
  const deleteMutation = useDeleteGsdCategory();

  const form = useForm<FormType>({ resolver: zodResolver(schema) });

  const openCreate = () => { setSelected(null); form.reset({ category_name: "", description: "", status: "active" }); setDialogOpen(true); };
  const openEdit = (item: GsdCategory) => { setSelected(item); form.reset({ category_name: item.category_name, description: item.description || "", status: item.status }); setDialogOpen(true); };
  const openDelete = (item: GsdCategory) => { setSelected(item); setDeleteOpen(true); };

  const onSubmit = (formData: FormType) => {
    if (selected) updateMutation.mutate({ id: selected.id, data: formData }, { onSuccess: () => setDialogOpen(false) });
    else createMutation.mutate(formData, { onSuccess: () => setDialogOpen(false) });
  };

  const handleSort = (key: string) => {
    if (sortBy === key) setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    else { setSortBy(key); setSortOrder("asc"); }
  };

  const columns: Column<GsdCategory>[] = [
    { key: "category_name", header: "Name", sortable: true },
    { key: "status", header: "Status", sortable: true },
    { key: "elements", header: "Elements", render: (item) => item.elements?.length || 0 },
    { key: "description", header: "Description" },
  ];

  const isSaving = createMutation.isPending || updateMutation.isPending;

  return (
    <div className="space-y-6">
      <PageHeader title="GSD Categories" description="Manage GSD categories" action={{ label: "Add Category", onClick: openCreate }} />

      <DataTable columns={columns} data={data?.data || []} isLoading={isLoading} searchPlaceholder="Search categories..." searchValue={search} onSearchChange={(v) => { setSearch(v); setPage(1); }} autocompleteEndpoint="/api/master/gsd-categories/search" authToken={token ?? undefined} onEdit={openEdit} onDelete={openDelete} currentPage={data?.current_page || 1} totalPages={data?.last_page || 1} onPageChange={setPage} totalItems={data?.total || 0} sortBy={sortBy} sortOrder={sortOrder} onSort={handleSort} />

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>{selected ? "Edit GSD Category" : "Add GSD Category"}</DialogTitle></DialogHeader>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label>Category Name</Label>
              <Input {...form.register("category_name")} placeholder="e.g. Basic Motions" />
              {form.formState.errors.category_name && <p className="text-sm text-destructive">{form.formState.errors.category_name.message}</p>}
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

      <DeleteDialog open={deleteOpen} onOpenChange={setDeleteOpen} onConfirm={() => { if (selected) deleteMutation.mutate(selected.id, { onSuccess: () => setDeleteOpen(false) }); }} title="Delete GSD Category" description={`Are you sure you want to delete "${selected?.category_name}"?`} isLoading={deleteMutation.isPending} />
    </div>
  );
}
