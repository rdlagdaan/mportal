import { clearCookieEverywhere, getCookie } from './cookies' // ← adjust if needed

const SESSION_COOKIE_NAME = 'mportal_session'

async function ensureCsrf() {
  if (!getCookie('XSRF-TOKEN')) {
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
  }
}

export async function logoutAndClean() {
  try {
    await ensureCsrf()
    const res = await fetch('/api/logout', {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') ?? '',
      },
    })
    console.log('logout status', res.status)
  } catch (err) {
    console.warn('logout error', err)
  } finally {
    clearCookieEverywhere('XSRF-TOKEN')
    clearCookieEverywhere(SESSION_COOKIE_NAME)
    clearCookieEverywhere('laravel_session')
    window.location.href = '/app/login'  // important: SPA lives under /app
  }
}
