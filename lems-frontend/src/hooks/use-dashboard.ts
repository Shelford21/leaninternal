"use client";

import { useQuery } from "@tanstack/react-query";
import api from "@/lib/axios";
import { DashboardStats } from "@/types";

export function useDashboardStats() {
  return useQuery({
    queryKey: ["dashboard", "stats"],
    queryFn: async () => {
      const res = await api.get<DashboardStats>("/dashboard/stats");
      return res.data;
    },
  });
}
