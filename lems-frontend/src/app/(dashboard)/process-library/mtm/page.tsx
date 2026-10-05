"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMtmElements, useCreateMtmElement, useUpdateMtmElement, useDeleteMtmElement } from "@/hooks/use-mtm-elements";
import { DataTable, Column } from "@/components/data-table";
import { PageHeader } from "@/components/page-header";
import { DeleteDialog } from "@/components/delete-dialog";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { MtmElement } from "@/types";
import { Loader2 } from "lucide-react";

const schema = z.object({
  element_name: z.string().min(1, "Name is required"),
  description: z.string().optional(),
  code: z.string().min(1, "Code is required"),
  tmu: z.number().min(0, "TMU must be positive"),
  seconds: z.number().min(0, "Seconds must be positive"),
  status: z.string().min(1, "Status is required"),
});

type FormType = z.infer<typeof schema>;

export default function MtmElementsPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState("id");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [selected, setSelected] = useState<MtmElement | null>(null);

  const { data, isLoading } = useMtmElements({ page, per_page: 15, search, sort_by: sortBy, sort_order: sortOrder });
  const createMutation = useCreateMtmElement();
  const updateMutation = useUpdateMtmElement();
  const deleteMutation = useDeleteMtmElement();

  const form = useForm<FormType>({ resolver: zodResolver(schema) });

  const openCreate = () => { setSelected(null); form.reset({ element_name: "", description: "", code: "", tmu: 0, seconds: 0, status: "active" }); setDialogOpen(true); };
  const openEdit = (item: MtmElement) => { setSelected(item); form.reset({ element_name: item.element_name, description: item.description || "", code: item.code, tmu: item.tmu, seconds: item.seconds, status: item.status }); setDialogOpen(true); };
  const openDelete = (item: MtmElement) => { setSelected(item); setDeleteOpen(true); };

  const onSubmit = (formData: FormType) => {
    if (selected) updateMutation.mutate({ id: selected.id, data: formData }, { onSuccess: () => setDialogOpen(false) });
    else createMutation.mutate(formData, { onSuccess: () => setDialogOpen(false) });
  };

  const handleSort = (key: string) => {
    if (sortBy === key) setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    else { setSortBy(key); setSortOrder("asc"); }
  };

  const columns: Column<MtmElement>[] = [
    { key: "code", header: "Code", sortable: true },
    { key: "element_name", header: "Name", sortable: true },
    { key: "tmu", header: "TMU", sortable: true },
    { key: "seconds", header: "Seconds", sortable: true },
    { key: "status", header: "Status", sortable: true },
    { key: "description", header: "Description" },
  ];

  const isSaving = createMutation.isPending || updateMutation.isPending;

  return (
    <div className="space-y-6">
      <PageHeader title="MTM Elements" description="Manage MTM elements" action={{ label: "Add MTM Element", onClick: openCreate }} />

      <DataTable columns={columns} data={data?.data || []} isLoading={isLoading} searchPlaceholder="Search MTM elements..." searchValue={search} onSearchChange={(v) => { setSearch(v); setPage(1); }} onEdit={openEdit} onDelete={openDelete} currentPage={data?.current_page || 1} totalPages={data?.last_page || 1} onPageChange={setPage} totalItems={data?.total || 0} sortBy={sortBy} sortOrder={sortOrder} onSort={handleSort} />

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>{selected ? "Edit MTM Element" : "Add MTM Element"}</DialogTitle></DialogHeader>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label>Code</Label>
              <Input {...form.register("code")} placeholder="e.g. MTM-001" />
              {form.formState.errors.code && <p className="text-sm text-destructive">{form.formState.errors.code.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Element Name</Label>
              <Input {...form.register("element_name")} placeholder="e.g. Reach" />
              {form.formState.errors.element_name && <p className="text-sm text-destructive">{form.formState.errors.element_name.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>TMU</Label>
              <Input type="number" step="0.01" {...form.register("tmu", { valueAsNumber: true })} placeholder="e.g. 1.50" />
              {form.formState.errors.tmu && <p className="text-sm text-destructive">{form.formState.errors.tmu.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Seconds</Label>
              <Input type="number" step="0.01" {...form.register("seconds", { valueAsNumber: true })} placeholder="e.g. 0.09" />
              {form.formState.errors.seconds && <p className="text-sm text-destructive">{form.formState.errors.seconds.message}</p>}
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

      <DeleteDialog open={deleteOpen} onOpenChange={setDeleteOpen} onConfirm={() => { if (selected) deleteMutation.mutate(selected.id, { onSuccess: () => setDeleteOpen(false) }); }} title="Delete MTM Element" description={`Are you sure you want to delete "${selected?.element_name}"?`} isLoading={deleteMutation.isPending} />
    </div>
  );
}
