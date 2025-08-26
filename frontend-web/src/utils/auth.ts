// src/utils/auth.ts
import { clearCookieEverywhere, getCookie } from './cookies'
import { postMicro } from '@/utils/axios-micro';
const MICRO_SESSION_COOKIE = 'micro_session'

async function ensureCsrf() {
  if (!getCookie('XSRF-TOKEN')) {
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
  }
}

export async function logoutAndClean() {
  try {
    await ensureCsrf()
    await postMicro('/microcredentials/logout', {   // ✅ micro-specific logout
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') ?? '',
      },
    })
  } catch (err) {
    console.warn('logout error', err)
  } finally {
    // best-effort client-side clears (HttpOnly will be cleared by server)
    clearCookieEverywhere('XSRF-TOKEN')
    clearCookieEverywhere(MICRO_SESSION_COOKIE)
    clearCookieEverywhere('laravel_session')

    // ✅ add a one-shot flag so login page won't auto-redirect
    window.location.replace('/app/login?loggedout=1')
  }
}
