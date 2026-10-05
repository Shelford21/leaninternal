"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { ProcessVersion, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useProcessVersions(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["process-versions", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<ProcessVersion>>("/process-versions", { params });
      return res.data;
    },
  });
}

export function useProcessVersion(id: number) {
  return useQuery({
    queryKey: ["process-versions", id],
    queryFn: async () => {
      const res = await api.get<ProcessVersion>(`/process-versions/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useCreateProcessVersion() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<ProcessVersion>) => {
      const res = await api.post<ProcessVersion>("/process-versions", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["process-versions"] });
      queryClient.invalidateQueries({ queryKey: ["processes"] });
      toast({ title: "Success", description: "Process version created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create process version",
        variant: "destructive",
      });
    },
  });
}

export function useUpdateProcessVersion() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<ProcessVersion> }) => {
      const res = await api.put<ProcessVersion>(`/process-versions/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["process-versions"] });
      queryClient.invalidateQueries({ queryKey: ["processes"] });
      toast({ title: "Success", description: "Process version updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update process version",
        variant: "destructive",
      });
    },
  });
}

export function useDeleteProcessVersion() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/process-versions/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["process-versions"] });
      queryClient.invalidateQueries({ queryKey: ["processes"] });
      toast({ title: "Success", description: "Process version deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete process version",
        variant: "destructive",
      });
    },
  });
}
