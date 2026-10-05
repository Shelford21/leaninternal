"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { PtmsReport, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function usePtmsReports(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["ptms-reports", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<PtmsReport>>("/ptms-reports", { params });
      return res.data;
    },
  });
}

export function usePtmsReport(id: number) {
  return useQuery({
    queryKey: ["ptms-reports", id],
    queryFn: async () => {
      const res = await api.get<PtmsReport>(`/ptms-reports/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useCreatePtmsReport() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<PtmsReport>) => {
      const res = await api.post<PtmsReport>("/ptms-reports", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["ptms-reports"] });
      toast({ title: "Success", description: "PTMS Report created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create PTMS report",
        variant: "destructive",
      });
    },
  });
}

export function useUpdatePtmsReport() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<PtmsReport> }) => {
      const res = await api.put<PtmsReport>(`/ptms-reports/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["ptms-reports"] });
      toast({ title: "Success", description: "PTMS Report updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update PTMS report",
        variant: "destructive",
      });
    },
  });
}

export function useDeletePtmsReport() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/ptms-reports/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["ptms-reports"] });
      toast({ title: "Success", description: "PTMS Report deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete PTMS report",
        variant: "destructive",
      });
    },
  });
}
