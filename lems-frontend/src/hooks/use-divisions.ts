"use client";

import { useQuery } from "@tanstack/react-query";
import api from "@/lib/axios";
import { Division } from "@/types";

export function useAllDivisions() {
  return useQuery({
    queryKey: ["divisions", "all"],
    queryFn: async () => {
      const res = await api.get<Division[]>("/divisions/all");
      return res.data;
    },
  });
}
