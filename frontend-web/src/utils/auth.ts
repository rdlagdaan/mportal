import { getCookie, clearCookieEverywhere } from './cookies'

// Match your .env: SESSION_COOKIE=mportal_session
const SESSION_COOKIE_NAME = 'mportal_session'

async function ensureCsrf() {
  if (!getCookie('XSRF-TOKEN')) {
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
  }
}

export async function logoutAndClean() {
  try {
    await ensureCsrf()

    // Call your existing Laravel 12 route: POST /logout (in web.php)
    await fetch('/logout', {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') ?? '',
        'Content-Type': 'application/json',
      },
    })
  } catch {
    // ignore — we’ll still clear locally
  } finally {
    // Best-effort client cleanup (server should also be expiring cookies)
    clearCookieEverywhere('XSRF-TOKEN')
    clearCookieEverywhere(SESSION_COOKIE_NAME)
    clearCookieEverywhere('laravel_session') // in case the name was used previously

    // Optional: clear app caches
    sessionStorage.clear()
    // localStorage.removeItem('...') // if you store auth flags

    // Hard redirect to login to ensure a clean state
    window.location.href = '/login'
  }
}
