import axios from "axios";

const api = axios.create({
  baseURL: "/app/api/lrwsis",
  withCredentials: true,
  xsrfCookieName: "XSRF-TOKEN",
  xsrfHeaderName: "X-XSRF-TOKEN",
});

let csrfReady: Promise<void> | null = null;

export async function ensureCsrf() {
  if (!csrfReady) {
    csrfReady = axios.get("/app/lrwsis/csrf-cookie", { withCredentials: true }).then(() => {});
  }
  return csrfReady;
}

export async function loginLrwsis(email: string, password: string, remember = true) {
  await ensureCsrf(); // sets the /app cookie
  return api.post("/login", { email, password, remember });
}

export async function meLrwsis() {
  return api.get("/me");
}

export async function logoutLrwsis() {
  await ensureCsrf();
  return api.post("/logout");
}

export default api;
