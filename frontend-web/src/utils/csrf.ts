// src/utils/csrf.ts
import axios from 'axios';

type Bucket = 'lrwsis' | 'micro' | 'open';

/** New, explicit */
export async function getCsrfTokenFor(bucket: Bucket) {
  const map = {
    lrwsis: '/app/lrwsis/csrf-cookie',
    micro:  '/app/sanctum/csrf-cookie',
    open:   '/open/api/sanctum/csrf-cookie', // adjust if different
  } as const;
  await axios.get(map[bucket], { withCredentials: true });
}

/** Legacy alias (kept for now) */
export async function getCsrfToken() {
  // Heuristic: if you’re under /app (LRWSIS SPA), use lrwsis
  if (location.pathname.startsWith('/app')) {
    return getCsrfTokenFor('lrwsis');
  }
  // Otherwise keep previous behavior or call a safe default
  return getCsrfTokenFor('micro'); // adjust to your common case
}
