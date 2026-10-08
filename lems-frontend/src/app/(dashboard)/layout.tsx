"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuthStore } from "@/store/auth-store";
import { DashboardLayout } from "@/components/layout/dashboard-layout";

export default function DashboardGroupLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const { isAuthenticated } = useAuthStore();
  // zustand persist rehydrates from localStorage after the first client render;
  // without waiting for it, isAuthenticated is still false and authenticated
  // users get bounced to /login on every hard reload. NOTE: useAuthStore.persist
  // only exists in the browser (on the server the storage is unavailable and
  // zustand skips attaching it), so never touch it outside useEffect.
  const [hydrated, setHydrated] = useState(false);

  useEffect(() => {
    const persist = (useAuthStore as unknown as {
      persist?: { hasHydrated(): boolean; onFinishHydration(cb: () => void): () => void };
    }).persist;
    if (!persist || persist.hasHydrated()) {
      setHydrated(true);
      return;
    }
    return persist.onFinishHydration(() => setHydrated(true));
  }, []);

  useEffect(() => {
    if (hydrated && !isAuthenticated) {
      router.replace("/login");
    }
  }, [hydrated, isAuthenticated, router]);

  if (!hydrated || !isAuthenticated) {
    return (
      <div className="flex h-screen items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary" />
      </div>
    );
  }

  return <DashboardLayout>{children}</DashboardLayout>;
}
