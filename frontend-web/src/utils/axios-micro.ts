// src/utils/axios-micro.ts
import axios from 'axios';

export const axiosMicro = axios.create({
  baseURL: '/app/api',
  withCredentials: true,
  headers: { 'X-Requested-With': 'XMLHttpRequest' },
});

export async function ensureCsrf() {
  // OK to use the default endpoint; cookie path `/` works for /app too
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

export async function getMicro(path: string) {
  return axiosMicro.get(path);
}

export async function postMicro(path: string, data?: any) {
  await ensureCsrf();
  return axiosMicro.post(path, data);
}


export default axiosMicro;