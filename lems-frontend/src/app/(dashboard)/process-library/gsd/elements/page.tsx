"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useGsdElements, useCreateGsdElement, useUpdateGsdElement, useDeleteGsdElement } from "@/hooks/use-gsd-elements";
import { useAllGsdCategories } from "@/hooks/use-gsd-categories";
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
import { GsdElement } from "@/types";
import { Loader2 } from "lucide-react";

const schema = z.object({
  gsd_category_id: z.number().min(1, "Category is required"),
  element_name: z.string().min(1, "Name is required"),
  description: z.string().optional(),
  code: z.string().min(1, "Code is required"),
  tmu: z.number().min(0, "TMU must be positive"),
  seconds: z.number().min(0, "Seconds must be positive"),
  motion_sequence: z.string().optional(),
  status: z.string().min(1, "Status is required"),
});

type FormType = z.infer<typeof schema>;

export default function GsdElementsPage() {
  const { token } = useAuthStore();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState("id");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [selected, setSelected] = useState<GsdElement | null>(null);

  const { data, isLoading } = useGsdElements({ page, per_page: 15, search, sort_by: sortBy, sort_order: sortOrder });
  const { data: categories } = useAllGsdCategories();
  const createMutation = useCreateGsdElement();
  const updateMutation = useUpdateGsdElement();
  const deleteMutation = useDeleteGsdElement();

  const form = useForm<FormType>({ resolver: zodResolver(schema) });

  const openCreate = () => { setSelected(null); form.reset({ gsd_category_id: 0, element_name: "", description: "", code: "", tmu: 0, seconds: 0, motion_sequence: "", status: "active" }); setDialogOpen(true); };
  const openEdit = (item: GsdElement) => { setSelected(item); form.reset({ gsd_category_id: item.gsd_category_id, element_name: item.element_name, description: item.description || "", code: item.code, tmu: item.tmu, seconds: item.seconds, motion_sequence: item.motion_sequence || "", status: item.status }); setDialogOpen(true); };
  const openDelete = (item: GsdElement) => { setSelected(item); setDeleteOpen(true); };

  const onSubmit = (formData: FormType) => {
    if (selected) updateMutation.mutate({ id: selected.id, data: formData }, { onSuccess: () => setDialogOpen(false) });
    else createMutation.mutate(formData, { onSuccess: () => setDialogOpen(false) });
  };

  const handleSort = (key: string) => {
    if (sortBy === key) setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    else { setSortBy(key); setSortOrder("asc"); }
  };

  const columns: Column<GsdElement>[] = [
    { key: "code", header: "Code", sortable: true },
    { key: "element_name", header: "Name", sortable: true },
    { key: "gsd_category", header: "Category", render: (item) => item.gsd_category?.category_name || "-" },
    { key: "tmu", header: "TMU", sortable: true },
    { key: "seconds", header: "Seconds", sortable: true },
    { key: "status", header: "Status", sortable: true },
    { key: "description", header: "Description" },
  ];

  const isSaving = createMutation.isPending || updateMutation.isPending;

  return (
    <div className="space-y-6">
      <PageHeader title="GSD Elements" description="Manage GSD elements" action={{ label: "Add Element", onClick: openCreate }} />

      <DataTable columns={columns} data={data?.data || []} isLoading={isLoading} searchPlaceholder="Search elements..." searchValue={search} onSearchChange={(v) => { setSearch(v); setPage(1); }} autocompleteEndpoint="/api/master/gsd-elements/search" authToken={token ?? undefined} onEdit={openEdit} onDelete={openDelete} currentPage={data?.current_page || 1} totalPages={data?.last_page || 1} onPageChange={setPage} totalItems={data?.total || 0} sortBy={sortBy} sortOrder={sortOrder} onSort={handleSort} />

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>{selected ? "Edit GSD Element" : "Add GSD Element"}</DialogTitle></DialogHeader>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label>Category</Label>
              <Select value={form.watch("gsd_category_id")?.toString()} onValueChange={(v) => form.setValue("gsd_category_id", parseInt(v))}>
                <SelectTrigger><SelectValue placeholder="Select category" /></SelectTrigger>
                <SelectContent>
                  {categories?.map((c) => <SelectItem key={c.id} value={c.id.toString()}>{c.category_name}</SelectItem>)}
                </SelectContent>
              </Select>
              {form.formState.errors.gsd_category_id && <p className="text-sm text-destructive">{form.formState.errors.gsd_category_id.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Code</Label>
              <Input {...form.register("code")} placeholder="e.g. GSD-ELM-001" />
              {form.formState.errors.code && <p className="text-sm text-destructive">{form.formState.errors.code.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Element Name</Label>
              <Input {...form.register("element_name")} placeholder="e.g. Pick Up" />
              {form.formState.errors.element_name && <p className="text-sm text-destructive">{form.formState.errors.element_name.message}</p>}
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>TMU</Label>
                <Input type="number" step="0.01" {...form.register("tmu", { valueAsNumber: true })} placeholder="e.g. 2.50" />
                {form.formState.errors.tmu && <p className="text-sm text-destructive">{form.formState.errors.tmu.message}</p>}
              </div>
              <div className="space-y-2">
                <Label>Seconds</Label>
                <Input type="number" step="0.01" {...form.register("seconds", { valueAsNumber: true })} placeholder="e.g. 0.15" />
                {form.formState.errors.seconds && <p className="text-sm text-destructive">{form.formState.errors.seconds.message}</p>}
              </div>
            </div>
            <div className="space-y-2">
              <Label>Motion Sequence</Label>
              <Input {...form.register("motion_sequence")} placeholder="e.g. Reach-Grasp-Move" />
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

      <DeleteDialog open={deleteOpen} onOpenChange={setDeleteOpen} onConfirm={() => { if (selected) deleteMutation.mutate(selected.id, { onSuccess: () => setDeleteOpen(false) }); }} title="Delete GSD Element" description={`Are you sure you want to delete "${selected?.element_name}"?`} isLoading={deleteMutation.isPending} />
    </div>
  );
}
