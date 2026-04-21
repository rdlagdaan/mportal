// src/components/ReverbProbe.tsx
import { useEffect, useMemo } from 'react'
import echo from '@/utils/echo'

function probeEnabled(): boolean {
  try {
    const qs = new URLSearchParams(window.location.search)
    if (qs.has('probeWs')) {
      // persist if specified like ?probeWs=1
      if (qs.get('probeWs') === '1') localStorage.setItem('probeWs', '1')
      if (qs.get('probeWs') === '0') localStorage.removeItem('probeWs')
    }
    return localStorage.getItem('probeWs') === '1'
  } catch { return false }
}

export default function ReverbProbe() {
  const enabled = useMemo(() => probeEnabled(), [])
  useEffect(() => {
    if (!enabled) return
    const ch = echo.channel('notifications')
    const handler = (e: any) => {
      console.log('[ReverbProbe] .test.notification', e)
      if (e?.message) alert(e.message)
    }
    ch.listen('.test.notification', handler)
    return () => { try { ch.stopListening('.test.notification') } catch {} }
  }, [enabled])
  return null
}
