

// frontend-web/src/utils/axiosnapi.ts
// src/axiosnapi.ts
import axios, { AxiosError, AxiosHeaders } from "axios";
import Cookies from "js-cookie";
import type { InternalAxiosRequestConfig } from "axios";

/** Resolve a safe base URL for API calls (prevents mixed content). */
function resolveBaseURL(): string {
  if (typeof window !== "undefined") {
    // ✅ Both Micro & LRWSIS live under /app; so hit /app/api for everything
    return new URL("/app/api", window.location.origin).toString();
  }
  const envUrl = (import.meta.env.VITE_API_URL || "").trim();
  return envUrl || "/app/api";
}

const baseURL = resolveBaseURL();

/** Sanctum CSRF cookie endpoint (site root, not under /api). */
const CSRF_URL =
  typeof window !== "undefined"
    ? new URL("/sanctum/csrf-cookie", window.location.origin).toString()
    : "/sanctum/csrf-cookie";


// ---------- Axios instance ----------
const napi = axios.create({
  baseURL,               // e.g. "https://host/app/../api" (normalized)
  withCredentials: true, // send/receive Sanctum cookies
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
});
(napi.defaults.headers.common as any)["X-Requested-With"] = "XMLHttpRequest";



// ---------- CSRF helpers ----------
let csrfFetching: Promise<void> | null = null;

async function fetchCsrfCookie(): Promise<void> {
  if (!csrfFetching) {
    csrfFetching = axios
      .get(CSRF_URL, {
        withCredentials: true,
        headers: { "X-Requested-With": "XMLHttpRequest" },
      })
      .then(() => void 0)
      .finally(() => {
        csrfFetching = null;
      });
  }
  return csrfFetching;
}

export async function ensureCsrf(): Promise<void> {
  if (!Cookies.get("XSRF-TOKEN")) {
    await fetchCsrfCookie();
  }
}

const isUnsafe = (m?: string) =>
  ["post", "put", "patch", "delete"].includes((m || "").toLowerCase());

// ---------- Request interceptor ----------
napi.interceptors.request.use(
  async (config: InternalAxiosRequestConfig & { _retried?: boolean }) => {
    if (!config.headers || !(config.headers instanceof AxiosHeaders)) {
      config.headers = new AxiosHeaders(config.headers as any);
    }

    // Ensure CSRF cookie before unsafe methods
    if (isUnsafe(config.method) && !Cookies.get("XSRF-TOKEN")) {
      await fetchCsrfCookie();
    }

    // Attach CSRF header
    const xsrf = Cookies.get("XSRF-TOKEN");
    if (xsrf) config.headers.set("X-XSRF-TOKEN", xsrf);

    // Optional bearer (kept for future token APIs)
    const bearer = localStorage.getItem("token");
    if (bearer) config.headers.set("Authorization", `Bearer ${bearer}`);

    if (!config.headers.get("X-Requested-With")) {
      config.headers.set("X-Requested-With", "XMLHttpRequest");
    }

    return config;
  }
);

// ---------- Response interceptor (retry once on likely CSRF miss) ----------
napi.interceptors.response.use(
  (res) => res,
  async (error: AxiosError & { config?: any }) => {
    const status = error.response?.status;
    const msg = (error.response?.data as any)?.message || "";
    const cfg = error.config as
      | (InternalAxiosRequestConfig & { _retried?: boolean })
      | undefined;

    const looksLikeCsrf =
      status === 419 || (status === 401 && /csrf/i.test(msg || ""));

    if (cfg && !cfg._retried && looksLikeCsrf) {
      cfg._retried = true;
      await fetchCsrfCookie();

      const xsrf = Cookies.get("XSRF-TOKEN");
      if (xsrf) {
        if (!cfg.headers || !(cfg.headers instanceof AxiosHeaders)) {
          cfg.headers = new AxiosHeaders(cfg.headers as any);
        }
        cfg.headers.set("X-XSRF-TOKEN", xsrf);
      }
      return napi(cfg);
    }

    throw error;
  }
);

// ---------- Convenience helpers ----------
export async function postWithCsrf<T = any>(url: string, data?: any) {
  await ensureCsrf();
  return napi.post<T>(url, data);
}

export async function getWithCreds<T = any>(url: string, params?: any) {
  return napi.get<T>(url, { params });
}

export async function logout() {
  await ensureCsrf();
  return napi.post("/logout", {});
}

export default napi;
