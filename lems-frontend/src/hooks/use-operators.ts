"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { Operator, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useOperators(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["operators", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<Operator>>("/operators", { params });
      return res.data;
    },
  });
}

export function useOperator(id: number) {
  return useQuery({
    queryKey: ["operators", id],
    queryFn: async () => {
      const res = await api.get<Operator>(`/operators/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useAllOperators() {
  return useQuery({
    queryKey: ["operators", "all"],
    queryFn: async () => {
      const res = await api.get<Operator[]>("/operators/all");
      return res.data;
    },
  });
}

export function useCreateOperator() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<Operator>) => {
      const res = await api.post<Operator>("/operators", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["operators"] });
      toast({ title: "Success", description: "Operator created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create operator",
        variant: "destructive",
      });
    },
  });
}

export function useUpdateOperator() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<Operator> }) => {
      const res = await api.put<Operator>(`/operators/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["operators"] });
      toast({ title: "Success", description: "Operator updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update operator",
        variant: "destructive",
      });
    },
  });
}

export function useDeleteOperator() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/operators/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["operators"] });
      toast({ title: "Success", description: "Operator deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete operator",
        variant: "destructive",
      });
    },
  });
}
