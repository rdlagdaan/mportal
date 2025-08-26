// src/pages/components/ProtectedRoute.tsx
import { useEffect, useState } from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { getWithCreds } from '@/utils/axiosnapi'
import { getMicro } from '@/utils/axios-micro';
import type { JSX } from 'react';
export default function ProtectedRoute({ children }: { children: JSX.Element }) {
  const [ok, setOk] = useState<boolean | null>(null)
  const loc = useLocation()

  useEffect(() => {
    let cancelled = false
    ;(async () => {
      try {
        const r = await getMicro('/microcredentials/me') // -> /api/microcredentials/me
        if (!cancelled) setOk(!!r.data?.user)
      } catch {
        if (!cancelled) setOk(false)
      }
    })()
    return () => { cancelled = true }
  }, [])

  if (ok === null) return null
  if (!ok) return <Navigate to="/app/login" replace state={{ from: loc.pathname }} />
  return children
}
