"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { MtmElement, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useMtmElements(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["mtm-elements", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<MtmElement>>("/mtm-elements", { params });
      return res.data;
    },
  });
}

export function useMtmElement(id: number) {
  return useQuery({
    queryKey: ["mtm-elements", id],
    queryFn: async () => {
      const res = await api.get<MtmElement>(`/mtm-elements/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useCreateMtmElement() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<MtmElement>) => {
      const res = await api.post<MtmElement>("/mtm-elements", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["mtm-elements"] });
      toast({ title: "Success", description: "MTM Element created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create MTM element",
        variant: "destructive",
      });
    },
  });
}

export function useUpdateMtmElement() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<MtmElement> }) => {
      const res = await api.put<MtmElement>(`/mtm-elements/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["mtm-elements"] });
      toast({ title: "Success", description: "MTM Element updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update MTM element",
        variant: "destructive",
      });
    },
  });
}

export function useDeleteMtmElement() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/mtm-elements/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["mtm-elements"] });
      toast({ title: "Success", description: "MTM Element deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete MTM element",
        variant: "destructive",
      });
    },
  });
}
