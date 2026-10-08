import axios from "axios";
import { useAuthStore } from "@/store/auth-store";

const api = axios.create({
  // Always use /api — requests go through Next.js proxy (fallback rewrites
  // in next.config.js) which forwards to LEAN_API_URL server-side. This
  // avoids CORS because the browser never contacts the Laravel backend directly.
  baseURL: "/api",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

api.interceptors.request.use((config) => {
  if (typeof window !== "undefined") {
    // Token lives in the zustand auth store (persisted as "auth-storage").
    const token = useAuthStore.getState().token;
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }
  // Laravel reads sort/order; the SPA sends sort_by/sort_order (normalize).
  if (config.params) {
    if ("sort_by" in config.params) {
      config.params.sort = config.params.sort ?? config.params.sort_by;
      delete config.params.sort_by;
    }
    if ("sort_order" in config.params) {
      config.params.order = config.params.order ?? config.params.sort_order;
      delete config.params.sort_order;
    }
  }
  return config;
});

api.interceptors.response.use(
  (response) => {
    const body = response.data;
    if (body && typeof body === "object") {
      // Laravel paginator envelope -> flat shape the SPA hooks expect.
      if (body.meta && body.links && Array.isArray(body.data)) {
        response.data = {
          data: body.data,
          current_page: body.meta.current_page,
          last_page: body.meta.last_page,
          per_page: body.meta.per_page,
          total: body.meta.total,
          from: body.meta.from,
          to: body.meta.to,
        };
      } else if ("message" in body && "data" in body) {
        // Laravel { data, message } envelope -> plain payload.
        response.data = body.data;
      }
    }
    return response;
  },
  (error) => {
    if (error.response?.status === 401 && typeof window !== "undefined") {
      // Never treat a failed login POST as a session failure: wrong
      // credentials must show the error toast, not wipe state or reload.
      const isLoginRequest = String(error.config?.url || "").includes("/login");
      if (!isLoginRequest) {
        useAuthStore.getState().logout();
        if (window.location.pathname !== "/login") {
          window.location.href = "/login";
        }
      }
    }
    return Promise.reject(error);
  }
);

export default api;
