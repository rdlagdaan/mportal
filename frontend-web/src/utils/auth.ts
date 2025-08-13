import { getCookie, clearCookieEverywhere } from './cookies'

// Match your .env: SESSION_COOKIE=mportal_session
const SESSION_COOKIE_NAME = 'mportal_session'

async function ensureCsrf() {
  if (!getCookie('XSRF-TOKEN')) {
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
  }
}

// src/utils/auth.ts
export async function logoutAndClean() {
  try {
    await fetch('/logout', {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': (document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
      },
    });
  } catch {} finally {
    // ...clear cookies if you added that earlier...
    window.location.href = '/app/login'; // 👈 important
  }
}

