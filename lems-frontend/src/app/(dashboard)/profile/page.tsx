"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useAuthStore } from "@/store/auth-store";
import { useChangeMyPassword } from "@/hooks/use-users";
import { PageHeader } from "@/components/page-header";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Loader2, User, Hash, Shield, KeyRound } from "lucide-react";

const passwordSchema = z.object({
  current_password: z.string().min(1, "Current password is required"),
  password: z.string().min(6, "Password must be at least 6 characters"),
  password_confirmation: z.string().min(6, "Confirm your password"),
}).refine((data) => data.password === data.password_confirmation, {
  message: "Passwords don't match",
  path: ["password_confirmation"],
});

type PasswordFormType = z.infer<typeof passwordSchema>;

export default function ProfilePage() {
  const { user } = useAuthStore();
  const changePasswordMutation = useChangeMyPassword();

  const form = useForm<PasswordFormType>({ resolver: zodResolver(passwordSchema) });

  const onSubmit = (formData: PasswordFormType) => {
    if (!user) return;
    changePasswordMutation.mutate(
      formData,
      {
        onSuccess: () => {
          form.reset();
        },
      }
    );
  };

  if (!user) return null;

  return (
    <div className="space-y-6">
      <PageHeader title="Profile" description="View your profile and change password" />

      <div className="grid gap-6 md:grid-cols-2">
        {/* Profile Info */}
        <Card>
          <CardHeader>
            <CardTitle>Profile Information</CardTitle>
            <CardDescription>Your account details</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center gap-3">
              <div className="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                <User className="h-8 w-8 text-primary" />
              </div>
              <div>
                <p className="text-xl font-semibold">{user.name}</p>
                <p className="text-muted-foreground">@{user.username}</p>
              </div>
            </div>
            <Separator />
            <div className="space-y-3">
              <div className="flex items-center gap-3">
                <Hash className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm text-muted-foreground">Employee Number</p>
                  <p className="font-medium">{user.employee_number}</p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <Shield className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm text-muted-foreground">Role</p>
                  <Badge variant="outline">{user.role?.role_name || "N/A"}</Badge>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Change Password */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <KeyRound className="h-5 w-5" /> Change Password
            </CardTitle>
            <CardDescription>Update your account password</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
              <div className="space-y-2">
                <Label>Current Password</Label>
                <Input type="password" {...form.register("current_password")} placeholder="Current password" />
                {form.formState.errors.current_password && <p className="text-sm text-destructive">{form.formState.errors.current_password.message}</p>}
              </div>
              <div className="space-y-2">
                <Label>New Password</Label>
                <Input type="password" {...form.register("password")} placeholder="New password" />
                {form.formState.errors.password && <p className="text-sm text-destructive">{form.formState.errors.password.message}</p>}
              </div>
              <div className="space-y-2">
                <Label>Confirm New Password</Label>
                <Input type="password" {...form.register("password_confirmation")} placeholder="Confirm new password" />
                {form.formState.errors.password_confirmation && <p className="text-sm text-destructive">{form.formState.errors.password_confirmation.message}</p>}
              </div>
              <Button type="submit" disabled={changePasswordMutation.isPending}>
                {changePasswordMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                Change Password
              </Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
