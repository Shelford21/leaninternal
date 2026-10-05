"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { Process, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useProcesses(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["processes", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<Process>>("/processes", { params });
      return res.data;
    },
  });
}

export function useProcess(id: number) {
  return useQuery({
    queryKey: ["processes", id],
    queryFn: async () => {
      const res = await api.get<Process>(`/processes/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useAllProcesses() {
  return useQuery({
    queryKey: ["processes", "all"],
    queryFn: async () => {
      const res = await api.get<Process[]>("/processes/all");
      return res.data;
    },
  });
}

export function useCreateProcess() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<Process>) => {
      const res = await api.post<Process>("/processes", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["processes"] });
      toast({ title: "Success", description: "Process created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create process",
        variant: "destructive",
      });
    },
  });
}

export function useUpdateProcess() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<Process> }) => {
      const res = await api.put<Process>(`/processes/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["processes"] });
      toast({ title: "Success", description: "Process updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update process",
        variant: "destructive",
      });
    },
  });
}

export function useDeleteProcess() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/processes/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["processes"] });
      toast({ title: "Success", description: "Process deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete process",
        variant: "destructive",
      });
    },
  });
}
