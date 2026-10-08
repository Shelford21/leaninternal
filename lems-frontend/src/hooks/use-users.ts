"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { User, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useUsers(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["users", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<User>>("/users", { params });
      return res.data;
    },
  });
}

export function useUser(id: number) {
  return useQuery({
    queryKey: ["users", id],
    queryFn: async () => {
      const res = await api.get<User>(`/users/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useCreateUser() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<User> & { password?: string; password_confirmation?: string }) => {
      const res = await api.post<User>("/users", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
      toast({ title: "Success", description: "User created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create user",
        variant: "destructive",
      });
    },
  });
}

export function useUpdateUser() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<User> & { password?: string; password_confirmation?: string } }) => {
      const res = await api.put<User>(`/users/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
      toast({ title: "Success", description: "User updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update user",
        variant: "destructive",
      });
    },
  });
}

export function useDeleteUser() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/users/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
      toast({ title: "Success", description: "User deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete user",
        variant: "destructive",
      });
    },
  });
}

export function useChangePassword() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: { current_password?: string; password: string; password_confirmation: string } }) => {
      // Laravel has no /users/{id}/change-password endpoint; admins change a
      // user's password through the regular user update (password is hashed
      // server-side). Preserved contract.
      const res = await api.put(`/users/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
      toast({ title: "Success", description: "Password changed successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to change password",
        variant: "destructive",
      });
    },
  });
}

/** Self-service password change: AuthController::changePassword (PUT /me/password). */
export function useChangeMyPassword() {
  return useMutation({
    mutationFn: async (data: { current_password: string; password: string; password_confirmation: string }) => {
      const res = await api.put("/me/password", data);
      return res.data;
    },
    onSuccess: () => {
      toast({ title: "Success", description: "Password changed successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to change password",
        variant: "destructive",
      });
    },
  });
}
