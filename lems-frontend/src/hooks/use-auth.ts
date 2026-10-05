"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/axios";
import { useAuthStore } from "@/store/auth-store";
import { useRouter } from "next/navigation";
import { LoginRequest, LoginResponse, User } from "@/types";
import { toast } from "@/components/ui/use-toast";

export function useLogin() {
  const { login } = useAuthStore();
  const router = useRouter();

  return useMutation({
    mutationFn: async (data: LoginRequest) => {
      const res = await api.post<LoginResponse>("/login", data);
      return res.data;
    },
    onSuccess: (data) => {
      login(data.user, data.token);
      toast({ title: "Success", description: "Logged in successfully" });
      router.push("/dashboard");
    },
    onError: (error: any) => {
      toast({
        title: "Error",
        description: error.response?.data?.message || "Login failed",
        variant: "destructive",
      });
    },
  });
}

export function useLogout() {
  const { logout } = useAuthStore();
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async () => {
      await api.post("/logout");
    },
    onSettled: () => {
      logout();
      queryClient.clear();
      router.push("/login");
    },
  });
}

export function useMe() {
  const { updateUser } = useAuthStore();

  return useQuery({
    queryKey: ["me"],
    queryFn: async () => {
      const res = await api.get<User>("/me");
      updateUser(res.data);
      return res.data;
    },
    retry: false,
  });
}
