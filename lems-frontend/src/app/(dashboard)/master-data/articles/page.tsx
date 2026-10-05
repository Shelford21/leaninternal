"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useArticles, useCreateArticle, useUpdateArticle, useDeleteArticle } from "@/hooks/use-articles";
import { DataTable, Column } from "@/components/data-table";
import { PageHeader } from "@/components/page-header";
import { DeleteDialog } from "@/components/delete-dialog";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Article } from "@/types";
import { Loader2 } from "lucide-react";

const schema = z.object({
  article_name: z.string().min(1, "Name is required"),
  label_number: z.string().min(1, "Label number is required"),
  destination: z.string().min(1, "Destination is required"),
  description: z.string().optional(),
  status: z.string().min(1, "Status is required"),
});

type FormType = z.infer<typeof schema>;

export default function ArticlesPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState("id");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [selected, setSelected] = useState<Article | null>(null);

  const { data, isLoading } = useArticles({ page, per_page: 15, search, sort_by: sortBy, sort_order: sortOrder });
  const createMutation = useCreateArticle();
  const updateMutation = useUpdateArticle();
  const deleteMutation = useDeleteArticle();

  const form = useForm<FormType>({ resolver: zodResolver(schema) });

  const openCreate = () => { setSelected(null); form.reset({ article_name: "", label_number: "", destination: "", description: "", status: "active" }); setDialogOpen(true); };
  const openEdit = (item: Article) => { setSelected(item); form.reset({ article_name: item.article_name, label_number: item.label_number, destination: item.destination, description: item.description || "", status: item.status }); setDialogOpen(true); };
  const openDelete = (item: Article) => { setSelected(item); setDeleteOpen(true); };

  const onSubmit = (formData: FormType) => {
    if (selected) updateMutation.mutate({ id: selected.id, data: formData }, { onSuccess: () => setDialogOpen(false) });
    else createMutation.mutate(formData, { onSuccess: () => setDialogOpen(false) });
  };

  const handleSort = (key: string) => {
    if (sortBy === key) setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    else { setSortBy(key); setSortOrder("asc"); }
  };

  const columns: Column<Article>[] = [
    { key: "article_name", header: "Name", sortable: true },
    { key: "label_number", header: "Label No.", sortable: true },
    { key: "destination", header: "Destination" },
    { key: "status", header: "Status", render: (item) => <Badge variant={item.status === "active" ? "default" : "secondary"}>{item.status}</Badge> },
    { key: "description", header: "Description" },
  ];

  const isSaving = createMutation.isPending || updateMutation.isPending;

  return (
    <div className="space-y-6">
      <PageHeader title="Articles" description="Manage articles/products" action={{ label: "Add Article", onClick: openCreate }} />

      <DataTable columns={columns} data={data?.data || []} isLoading={isLoading} searchPlaceholder="Search articles..." searchValue={search} onSearchChange={(v) => { setSearch(v); setPage(1); }} onEdit={openEdit} onDelete={openDelete} currentPage={data?.current_page || 1} totalPages={data?.last_page || 1} onPageChange={setPage} totalItems={data?.total || 0} sortBy={sortBy} sortOrder={sortOrder} onSort={handleSort} />

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>{selected ? "Edit Article" : "Add Article"}</DialogTitle></DialogHeader>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label>Article Name</Label>
              <Input {...form.register("article_name")} placeholder="e.g. Polo Shirt" />
              {form.formState.errors.article_name && <p className="text-sm text-destructive">{form.formState.errors.article_name.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Label Number</Label>
              <Input {...form.register("label_number")} placeholder="e.g. LBL-001" />
              {form.formState.errors.label_number && <p className="text-sm text-destructive">{form.formState.errors.label_number.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Destination</Label>
              <Input {...form.register("destination")} placeholder="e.g. Factory A" />
              {form.formState.errors.destination && <p className="text-sm text-destructive">{form.formState.errors.destination.message}</p>}
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

      <DeleteDialog open={deleteOpen} onOpenChange={setDeleteOpen} onConfirm={() => { if (selected) deleteMutation.mutate(selected.id, { onSuccess: () => setDeleteOpen(false) }); }} title="Delete Article" description={`Are you sure you want to delete "${selected?.article_name}"?`} isLoading={deleteMutation.isPending} />
    </div>
  );
}
