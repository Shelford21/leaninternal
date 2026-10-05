"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { SewingStopFactor, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useSewingStopFactors(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["sewing-stop-factors", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<SewingStopFactor>>("/sewing-stop-factors", { params });
      return res.data;
    },
  });
}

export function useSewingStopFactor(id: number) {
  return useQuery({
    queryKey: ["sewing-stop-factors", id],
    queryFn: async () => {
      const res = await api.get<SewingStopFactor>(`/sewing-stop-factors/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useCreateSewingStopFactor() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<SewingStopFactor>) => {
      const res = await api.post<SewingStopFactor>("/sewing-stop-factors", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["sewing-stop-factors"] });
      toast({ title: "Success", description: "Stop factor created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create stop factor",
        variant: "destructive",
      });
    },
  });
}

export function useUpdateSewingStopFactor() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<SewingStopFactor> }) => {
      const res = await api.put<SewingStopFactor>(`/sewing-stop-factors/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["sewing-stop-factors"] });
      toast({ title: "Success", description: "Stop factor updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update stop factor",
        variant: "destructive",
      });
    },
  });
}

export function useDeleteSewingStopFactor() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/sewing-stop-factors/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["sewing-stop-factors"] });
      toast({ title: "Success", description: "Stop factor deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete stop factor",
        variant: "destructive",
      });
    },
  });
}
