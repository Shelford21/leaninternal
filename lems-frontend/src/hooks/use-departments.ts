"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { Department, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useDepartments(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["departments", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<Department>>("/departments", { params });
      return res.data;
    },
  });
}

export function useDepartment(id: number) {
  return useQuery({
    queryKey: ["departments", id],
    queryFn: async () => {
      const res = await api.get<Department>(`/departments/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useAllDepartments() {
  return useQuery({
    queryKey: ["departments", "all"],
    queryFn: async () => {
      const res = await api.get<Department[]>("/departments/all");
      return res.data;
    },
  });
}

export function useCreateDepartment() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<Department>) => {
      const res = await api.post<Department>("/departments", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["departments"] });
      toast({ title: "Success", description: "Department created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create department",
        variant: "destructive",
      });
    },
  });
}

export function useUpdateDepartment() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<Department> }) => {
      const res = await api.put<Department>(`/departments/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["departments"] });
      toast({ title: "Success", description: "Department updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update department",
        variant: "destructive",
      });
    },
  });
}

export function useDeleteDepartment() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/departments/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["departments"] });
      toast({ title: "Success", description: "Department deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete department",
        variant: "destructive",
      });
    },
  });
}
