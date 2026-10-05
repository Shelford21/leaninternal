"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { GsdCategory, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useGsdCategories(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["gsd-categories", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<GsdCategory>>("/gsd-categories", { params });
      return res.data;
    },
  });
}

export function useGsdCategory(id: number) {
  return useQuery({
    queryKey: ["gsd-categories", id],
    queryFn: async () => {
      const res = await api.get<GsdCategory>(`/gsd-categories/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useAllGsdCategories() {
  return useQuery({
    queryKey: ["gsd-categories", "all"],
    queryFn: async () => {
      const res = await api.get<GsdCategory[]>("/gsd-categories/all");
      return res.data;
    },
  });
}

export function useCreateGsdCategory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<GsdCategory>) => {
      const res = await api.post<GsdCategory>("/gsd-categories", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["gsd-categories"] });
      toast({ title: "Success", description: "GSD Category created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create GSD category",
        variant: "destructive",
      });
    },
  });
}

export function useUpdateGsdCategory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<GsdCategory> }) => {
      const res = await api.put<GsdCategory>(`/gsd-categories/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["gsd-categories"] });
      toast({ title: "Success", description: "GSD Category updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update GSD category",
        variant: "destructive",
      });
    },
  });
}

export function useDeleteGsdCategory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/gsd-categories/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["gsd-categories"] });
      toast({ title: "Success", description: "GSD Category deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete GSD category",
        variant: "destructive",
      });
    },
  });
}
