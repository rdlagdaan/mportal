// src/lrwsis/LrwsisLayout.tsx
import * as React from 'react'
import { Outlet, useNavigate } from 'react-router-dom'
import napi from '@/utils/axiosnapi'

export default function LrwsisLayout() {
  const navigate = useNavigate()

  React.useEffect(() => {
    // On mount, verify server session; bounce to login if not signed in
    napi.get('/lrwsis/me').catch(() => navigate('/login', { replace: true }))
  }, [navigate])

  return (
    <div className="min-h-screen">
      <header className="px-4 py-3 bg-gradient-to-r from-yellow-400 to-green-600 text-white font-semibold">
        LRWSIS
      </header>
      <main className="p-4">
        <Outlet />
      </main>
    </div>
  )
}
