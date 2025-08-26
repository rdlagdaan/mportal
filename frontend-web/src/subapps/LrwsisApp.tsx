// src/subapps/LrwsisApp.tsx
import * as React from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import LrwsisLayout from '@/lrwsis/LrwsisLayout'
import LrwsisDashboard from '@/lrwsis/pages/Dashboard'

export default function LrwsisApp() {
  return (
    <Routes>
      <Route element={<LrwsisLayout />}>
        <Route index element={<Navigate to="dashboard" replace />} />
        <Route path="dashboard" element={<LrwsisDashboard />} />
        {/* add more LRWSIS pages here */}
      </Route>
      <Route path="*" element={<Navigate to="/login" replace />} />
    </Routes>
  )
}
