// src/root-router.tsx
import '@/utils/echo' 

import * as React from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import TabbedLogin from '@/pages/TabbedLogin'
import MicroApp from '@/subapps/MicroApp'
import LrwsisApp from '@/subapps/LrwsisApp'
import ProtectedRoute from '@/pages/components/ProtectedRoute'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query' 

const qc = new QueryClient() 

export default function RootRouter() {
  return (
    <QueryClientProvider client={qc}> 
      <BrowserRouter basename="/app">
        <Routes>
          {/* Public */}
          <Route path="login" element={<TabbedLogin />} />

          {/* LRWSIS lives under /app/lrwsis/* (server guards + layout’s /me check) */}
          <Route path="lrwsis/*" element={<LrwsisApp />} />

          {/* Everything else → your Micro app (keep your client-side guard) */}
          <Route
            path="*"
            element={
              <ProtectedRoute>
                <MicroApp />
              </ProtectedRoute>
            }
          />

          {/* (Not reached because of path="*") */}
          <Route path="/" element={<Navigate to="/app" replace />} />
        </Routes>
      </BrowserRouter>
    </QueryClientProvider>  
  )
}
