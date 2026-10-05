"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { Article, PaginatedResponse, QueryParams } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useArticles(params: QueryParams = {}) {
  return useQuery({
    queryKey: ["articles", params],
    queryFn: async () => {
      const res = await api.get<PaginatedResponse<Article>>("/articles", { params });
      return res.data;
    },
  });
}

export function useArticle(id: number) {
  return useQuery({
    queryKey: ["articles", id],
    queryFn: async () => {
      const res = await api.get<Article>(`/articles/${id}`);
      return res.data;
    },
    enabled: !!id,
  });
}

export function useAllArticles() {
  return useQuery({
    queryKey: ["articles", "all"],
    queryFn: async () => {
      const res = await api.get<Article[]>("/articles/all");
      return res.data;
    },
  });
}

export function useCreateArticle() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: Partial<Article>) => {
      const res = await api.post<Article>("/articles", data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["articles"] });
      toast({ title: "Success", description: "Article created successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to create article",
        variant: "destructive",
      });
    },
  });
}

export function useUpdateArticle() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<Article> }) => {
      const res = await api.put<Article>(`/articles/${id}`, data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["articles"] });
      toast({ title: "Success", description: "Article updated successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to update article",
        variant: "destructive",
      });
    },
  });
}

export function useDeleteArticle() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/articles/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["articles"] });
      toast({ title: "Success", description: "Article deleted successfully" });
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Failed to delete article",
        variant: "destructive",
      });
    },
  });
}
