// src/api/axios.ts
import axios, { AxiosError, AxiosHeaders } from 'axios';
import Cookies from 'js-cookie';
import type { InternalAxiosRequestConfig } from 'axios';

// ---------- Base URLs ----------
const baseURL =
  import.meta.env.VITE_API_URL?.trim() ||
  (typeof window !== 'undefined' ? `${window.location.origin}/api` : '/api');

// Sanctum's CSRF endpoint is NOT under /api.
// Derive the same-origin, non-/api base:
const nonApiBase = baseURL.replace(/\/api\/?$/, '') || '';

// Full CSRF URL (e.g. http://localhost:8787/sanctum/csrf-cookie)
const csrfURL = `${nonApiBase}/sanctum/csrf-cookie`;

// ---------- Axios instance ----------
const napi = axios.create({
  baseURL,              // all your API calls go to /api/...
  withCredentials: true
  // If you want, you could also set:
  // xsrfCookieName: 'XSRF-TOKEN',
  // xsrfHeaderName: 'X-XSRF-TOKEN',
});
(napi.defaults.headers.common as any)['X-Requested-With'] = 'XMLHttpRequest';
// ---------- CSRF fetch (singleton) ----------
let csrfPromise: Promise<void> | null = null;
const fetchCsrfCookie = () => {
  if (!csrfPromise) {
    csrfPromise = axios.get(csrfURL, { withCredentials: true })
      .then(() => { /* cookie set */ })
      .finally(() => { csrfPromise = null; });
  }
  return csrfPromise;
};


export async function ensureCsrf(): Promise<void> {
  if (!Cookies.get('XSRF-TOKEN')) {
    await axios.get(csrfURL, { withCredentials: true });
  }
}



const isUnsafe = (method?: string) =>
  ['post', 'put', 'patch', 'delete'].includes((method || '').toLowerCase());

// ---------- Request interceptor ----------
napi.interceptors.request.use(async (config: InternalAxiosRequestConfig & { _retried?: boolean }) => {
  // Ensure headers type
  if (!config.headers || !(config.headers instanceof AxiosHeaders)) {
    config.headers = new AxiosHeaders(config.headers as any);
  }

  // If we're about to do a state‑changing request and we don't have a CSRF cookie, fetch it first.
  if (isUnsafe(config.method) && !Cookies.get('XSRF-TOKEN')) {
    await fetchCsrfCookie();
  }

  // Add CSRF header from cookie (good even if we just fetched it)
  const xsrf = Cookies.get('XSRF-TOKEN');
  if (xsrf) config.headers.set('X-XSRF-TOKEN', xsrf);

  // Optional bearer (if you use token auth anywhere)
  const bearer = localStorage.getItem('token');
  if (bearer) config.headers.set('Authorization', `Bearer ${bearer}`);

  if (!config.headers.get('X-Requested-With')) {
    config.headers.set('X-Requested-With', 'XMLHttpRequest');
  }


  return config;
});

// ---------- Response interceptor (retry on 419 once) ----------
napi.interceptors.response.use(
  (res) => res,
  async (error: AxiosError & { config?: any }) => {
    const status = error.response?.status;
    const msg = (error.response?.data as any)?.message || '';

    const cfg = error.config as (InternalAxiosRequestConfig & { _retried?: boolean }) | undefined;

    const looksLikeCsrfIssue =
      status === 419 || (status === 401 && /csrf/i.test(msg));

    if (cfg && !cfg._retried && looksLikeCsrfIssue) {
      cfg._retried = true;
      await fetchCsrfCookie(); // refresh cookie
      // Reapply header after refresh
      const xsrf = Cookies.get('XSRF-TOKEN');
      if (xsrf) {
        if (!cfg.headers || !(cfg.headers instanceof AxiosHeaders)) {
          cfg.headers = new AxiosHeaders(cfg.headers as any);
        }
        cfg.headers.set('X-XSRF-TOKEN', xsrf);
      }
      return napi(cfg); // retry once
    }

    throw error;
  }
);



export async function postWithCsrf<T = any>(url: string, data?: any) {
  await ensureCsrf();
  return napi.post<T>(url, data);
}

export async function logout() {
  return await postWithCsrf("/logout", {});
}

export default napi;
