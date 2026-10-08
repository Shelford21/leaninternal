"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useUsers, useCreateUser, useUpdateUser, useDeleteUser, useChangePassword } from "@/hooks/use-users";
import { useAuthStore } from "@/store/auth-store";
import { DataTable, Column } from "@/components/data-table";
import { PageHeader } from "@/components/page-header";
import { DeleteDialog } from "@/components/delete-dialog";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { User } from "@/types";
import { Loader2, KeyRound } from "lucide-react";
import { DropdownMenuItem } from "@/components/ui/dropdown-menu";

const userSchema = z.object({
  name: z.string().min(1, "Name is required"),
  username: z.string().min(3, "Username must be at least 3 characters"),
  // Required by Laravel's StoreUserRequest (unique|max:20).
  employee_number: z.string().min(1, "Employee number is required"),
  password: z.string().min(6, "Password must be at least 6 characters").optional(),
  role_id: z.number().min(1, "Role is required"),
});

const passwordSchema = z.object({
  password: z.string().min(6, "Password must be at least 6 characters"),
  password_confirmation: z.string().min(6, "Confirm your password"),
}).refine((data) => data.password === data.password_confirmation, {
  message: "Passwords don't match",
  path: ["password_confirmation"],
});

type UserFormType = z.infer<typeof userSchema>;
type PasswordFormType = z.infer<typeof passwordSchema>;

// Real rows from the existing `roles` table (developer/admin/viewer).
const ROLES = [
  { id: 27, name: "developer" },
  { id: 28, name: "admin" },
  { id: 29, name: "viewer" },
];

export default function CredentialsPage() {
  const { user: currentUser, token } = useAuthStore();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState("id");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [passwordOpen, setPasswordOpen] = useState(false);
  const [selected, setSelected] = useState<User | null>(null);

  const { data, isLoading } = useUsers({ page, per_page: 15, search, sort_by: sortBy, sort_order: sortOrder });
  const createMutation = useCreateUser();
  const updateMutation = useUpdateUser();
  const deleteMutation = useDeleteUser();
  const changePasswordMutation = useChangePassword();

  const form = useForm<UserFormType>({ resolver: zodResolver(userSchema) });
  const passwordForm = useForm<PasswordFormType>({ resolver: zodResolver(passwordSchema) });

  const openCreate = () => {
    setSelected(null);
    form.reset({ name: "", username: "", employee_number: "", password: "", role_id: 29 });
    setDialogOpen(true);
  };

  const openEdit = (item: User) => {
    setSelected(item);
    form.reset({ name: item.name, username: item.username, employee_number: item.employee_number || "", password: "", role_id: item.role_id || 29 });
    setDialogOpen(true);
  };

  const openDelete = (item: User) => { setSelected(item); setDeleteOpen(true); };

  const openChangePassword = (item: User) => {
    setSelected(item);
    passwordForm.reset({ password: "", password_confirmation: "" });
    setPasswordOpen(true);
  };

  const onSubmit = (formData: UserFormType) => {
    // Laravel's `confirmed` rule requires password_confirmation next to password.
    const payload = selected
      ? {
          ...formData,
          password: formData.password || undefined,
          password_confirmation: formData.password || undefined,
        }
      : {
          ...formData,
          password: formData.password || "",
          password_confirmation: formData.password || "",
        };
    if (selected) {
      updateMutation.mutate({ id: selected.id, data: payload }, { onSuccess: () => setDialogOpen(false) });
    } else {
      createMutation.mutate(payload, { onSuccess: () => setDialogOpen(false) });
    }
  };

  const onPasswordSubmit = (formData: PasswordFormType) => {
    if (selected) {
      changePasswordMutation.mutate({ id: selected.id, data: formData }, { onSuccess: () => setPasswordOpen(false) });
    }
  };

  const handleSort = (key: string) => {
    if (sortBy === key) setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    else { setSortBy(key); setSortOrder("asc"); }
  };

  const columns: Column<User>[] = [
    { key: "name", header: "Name", sortable: true },
    { key: "username", header: "Username", sortable: true },
    { key: "role", header: "Role", render: (item) => <Badge variant="outline">{item.role?.role_name || "N/A"}</Badge> },
  ];

  const isSaving = createMutation.isPending || updateMutation.isPending;

  if (!["developer", "admin"].includes(currentUser?.role?.role_name ?? "")) {
    return (
      <div className="space-y-6">
        <PageHeader title="Access Denied" description="You do not have permission to manage users." />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader title="User Management" description="Manage user credentials and roles" action={{ label: "Add User", onClick: openCreate }} />

      <DataTable
        columns={columns}
        data={data?.data || []}
        isLoading={isLoading}
        searchPlaceholder="Search users..."
        searchValue={search}
        onSearchChange={(v) => { setSearch(v); setPage(1); }}
        autocompleteEndpoint="/api/master/users/search"
        authToken={token ?? undefined}
        onEdit={openEdit}
        onDelete={openDelete}
        customActions={(item) => (
          <DropdownMenuItem onClick={() => openChangePassword(item)}>
            <KeyRound className="mr-2 h-4 w-4" />
            Change Password
          </DropdownMenuItem>
        )}
        /* Admin cannot edit/delete/change-password developer accounts */
        canEdit={(item) => {
          const roleName = item.role?.role_name ?? "";
          if (currentUser?.role?.role_name === "admin" && roleName === "developer") return false;
          return true;
        }}
        canDelete={(item) => {
          const roleName = item.role?.role_name ?? "";
          if (currentUser?.role?.role_name === "admin" && roleName === "developer") return false;
          return true;
        }}
        canAct={(item) => {
          const roleName = item.role?.role_name ?? "";
          if (currentUser?.role?.role_name === "admin" && roleName === "developer") return false;
          return true;
        }}
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
          <DialogHeader><DialogTitle>{selected ? "Edit User" : "Add User"}</DialogTitle></DialogHeader>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label>Name</Label>
              <Input {...form.register("name")} placeholder="Full name" />
              {form.formState.errors.name && <p className="text-sm text-destructive">{form.formState.errors.name.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Username</Label>
              <Input {...form.register("username")} placeholder="Username" />
              {form.formState.errors.username && <p className="text-sm text-destructive">{form.formState.errors.username.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Employee Number</Label>
              <Input {...form.register("employee_number")} placeholder="Employee number" />
              {form.formState.errors.employee_number && <p className="text-sm text-destructive">{form.formState.errors.employee_number.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Password {selected && "(leave blank to keep current)"}</Label>
              <Input type="password" {...form.register("password")} placeholder="Password" />
              {form.formState.errors.password && <p className="text-sm text-destructive">{form.formState.errors.password.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Role</Label>
              <Select value={form.watch("role_id")?.toString()} onValueChange={(v) => form.setValue("role_id", parseInt(v))}>
                <SelectTrigger><SelectValue placeholder="Select role" /></SelectTrigger>
                <SelectContent>
                  {ROLES.map((r) => <SelectItem key={r.id} value={r.id.toString()}>{r.name}</SelectItem>)}
                </SelectContent>
              </Select>
              {form.formState.errors.role_id && <p className="text-sm text-destructive">{form.formState.errors.role_id.message}</p>}
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={isSaving}>{isSaving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}{selected ? "Update" : "Create"}</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Change Password Dialog */}
      <Dialog open={passwordOpen} onOpenChange={setPasswordOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>Change Password for {selected?.name}</DialogTitle></DialogHeader>
          <form onSubmit={passwordForm.handleSubmit(onPasswordSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label>New Password</Label>
              <Input type="password" {...passwordForm.register("password")} placeholder="New password" />
              {passwordForm.formState.errors.password && <p className="text-sm text-destructive">{passwordForm.formState.errors.password.message}</p>}
            </div>
            <div className="space-y-2">
              <Label>Confirm Password</Label>
              <Input type="password" {...passwordForm.register("password_confirmation")} placeholder="Confirm password" />
              {passwordForm.formState.errors.password_confirmation && <p className="text-sm text-destructive">{passwordForm.formState.errors.password_confirmation.message}</p>}
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setPasswordOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={changePasswordMutation.isPending}>
                {changePasswordMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                Change Password
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Delete Dialog */}
      <DeleteDialog
        open={deleteOpen}
        onOpenChange={setDeleteOpen}
        onConfirm={() => { if (selected) deleteMutation.mutate(selected.id, { onSuccess: () => setDeleteOpen(false) }); }}
        title="Delete User"
        description={`Are you sure you want to delete user "${selected?.name}"?`}
        isLoading={deleteMutation.isPending}
      />
    </div>
  );
}
