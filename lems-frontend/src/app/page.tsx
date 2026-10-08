"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuthStore } from "@/store/auth-store";

export default function HomePage() {
  const router = useRouter();
  const { isAuthenticated } = useAuthStore();
  // Wait for zustand persist rehydration before choosing a target, otherwise
  // hard reloads bounce authenticated users to /login. useAuthStore.persist is
  // browser-only (zustand skips it when localStorage is unavailable on the
  // server), so it must only be touched inside useEffect.
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
    if (!hydrated) return;
    if (isAuthenticated) {
      router.replace("/dashboard");
    } else {
      router.replace("/login");
    }
  }, [hydrated, isAuthenticated, router]);

  return (
    <div className="flex h-screen items-center justify-center">
      <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary" />
    </div>
  );
}
