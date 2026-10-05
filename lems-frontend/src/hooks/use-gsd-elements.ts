"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { GsdElement, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useGsdElements(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["gsd-elements", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<GsdElement>>("/gsd-elements", { params });
      return res.data;
    },
  });
}

export function useGsdElement(id: number) {
  return useQuery({
    queryKey: ["gsd-elements", id],
    queryFn: async () => {
      const res = await api.get<GsdElement>(`/gsd-elements/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useCreateGsdElement() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<GsdElement>) => {
      const res = await api.post<GsdElement>("/gsd-elements", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["gsd-elements"] });
      toast({ title: "Success", description: "GSD Element created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create GSD element",
        variant: "destructive",
      });
    },
  });
}

export function useUpdateGsdElement() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<GsdElement> }) => {
      const res = await api.put<GsdElement>(`/gsd-elements/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["gsd-elements"] });
      toast({ title: "Success", description: "GSD Element updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update GSD element",
        variant: "destructive",
      });
    },
  });
}

export function useDeleteGsdElement() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/gsd-elements/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["gsd-elements"] });
      toast({ title: "Success", description: "GSD Element deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete GSD element",
        variant: "destructive",
      });
    },
  });
}
