"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { Factory, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useFactories(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["factories", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<Factory>>("/factories", { params });
      return res.data;
    },
  });
}

export function useFactory(id: number) {
  return useQuery({
    queryKey: ["factories", id],
    queryFn: async () => {
      const res = await api.get<Factory>(`/factories/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useAllFactories() {
  return useQuery({
    queryKey: ["factories", "all"],
    queryFn: async () => {
      const res = await api.get<Factory[]>("/factories/all");
      return res.data;
    },
  });
}

export function useCreateFactory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<Factory>) => {
      const res = await api.post<Factory>("/factories", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["factories"] });
      toast({ title: "Success", description: "Factory created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create factory",
        variant: "destructive",
      });
    },
  });
}

export function useUpdateFactory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<Factory> }) => {
      const res = await api.put<Factory>(`/factories/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["factories"] });
      toast({ title: "Success", description: "Factory updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update factory",
        variant: "destructive",
      });
    },
  });
}

export function useDeleteFactory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/factories/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["factories"] });
      toast({ title: "Success", description: "Factory deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete factory",
        variant: "destructive",
      });
    },
  });
}
