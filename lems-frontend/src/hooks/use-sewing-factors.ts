"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { SewingFactor, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useSewingFactors(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["sewing-factors", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<SewingFactor>>("/sewing-factors", { params });
      return res.data;
    },
  });
}

export function useSewingFactor(id: number) {
  return useQuery({
    queryKey: ["sewing-factors", id],
    queryFn: async () => {
      const res = await api.get<SewingFactor>(`/sewing-factors/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useCreateSewingFactor() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<SewingFactor>) => {
      const res = await api.post<SewingFactor>("/sewing-factors", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["sewing-factors"] });
      toast({ title: "Success", description: "Sewing factor created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create sewing factor",
        variant: "destructive",
      });
    },
  });
}

export function useUpdateSewingFactor() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<SewingFactor> }) => {
      const res = await api.put<SewingFactor>(`/sewing-factors/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["sewing-factors"] });
      toast({ title: "Success", description: "Sewing factor updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update sewing factor",
        variant: "destructive",
      });
    },
  });
}

export function useDeleteSewingFactor() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/sewing-factors/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["sewing-factors"] });
      toast({ title: "Success", description: "Sewing factor deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete sewing factor",
        variant: "destructive",
      });
    },
  });
}
