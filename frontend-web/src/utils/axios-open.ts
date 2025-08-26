import axios from "axios";

const axiosOpen = axios.create({
  baseURL: "/open/api",
  withCredentials: true,
  headers: { "X-Requested-With": "XMLHttpRequest" },
});

export async function ensureOpenCsrf() {
  // Same-site cookie; OK to fetch from root
  await axios.get("/sanctum/csrf-cookie", { withCredentials: true });
}

export async function postOpen(path: string, data?: any) {
  await ensureOpenCsrf();
  return axiosOpen.post(path, data);
}

export default axiosOpen;
