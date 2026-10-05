"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { ProductionLine, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useProductionLines(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["production-lines", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<ProductionLine>>("/production-lines", { params });
      return res.data;
    },
  });
}

export function useProductionLine(id: number) {
  return useQuery({
    queryKey: ["production-lines", id],
    queryFn: async () => {
      const res = await api.get<ProductionLine>(`/production-lines/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useCreateProductionLine() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<ProductionLine>) => {
      const res = await api.post<ProductionLine>("/production-lines", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["production-lines"] });
      toast({ title: "Success", description: "Production line created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create production line",
        variant: "destructive",
      });
    },
  });
}

export function useUpdateProductionLine() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<ProductionLine> }) => {
      const res = await api.put<ProductionLine>(`/production-lines/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["production-lines"] });
      toast({ title: "Success", description: "Production line updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update production line",
        variant: "destructive",
      });
    },
  });
}

export function useDeleteProductionLine() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/production-lines/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["production-lines"] });
      toast({ title: "Success", description: "Production line deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete production line",
        variant: "destructive",
      });
    },
  });
}
