// Read a cookie by name
export function getCookie(name: string): string | null {
  const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&') + '=([^;]*)'))
  return m ? decodeURIComponent(m[1]) : null
}

// Delete a cookie across '/' and '/app' and with/without domain
export function clearCookieEverywhere(
  name: string,
  {
    domains = [window.location.hostname, '.' + window.location.hostname.replace(/^\./, '')],
    paths = ['/', '/app'],
  }: { domains?: string[]; paths?: string[] } = {}
) {
  const expires = 'expires=Thu, 01 Jan 1970 00:00:00 GMT'
  const secure = 'Secure'
  const samesite = 'SameSite=Lax'

  for (const d of Array.from(new Set(domains))) {
    for (const p of paths) {
      document.cookie = `${name}=; ${expires}; path=${p}; domain=${d}; ${secure}; ${samesite}`
    }
  }
  for (const p of paths) {
    document.cookie = `${name}=; ${expires}; path=${p}; ${secure}; ${samesite}`
  }
}
