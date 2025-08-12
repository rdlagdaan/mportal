// src/App.tsx
import * as React from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import DashboardLayout from './DashboardLayout'
import TabbedLogin from './pages/TabbedLogin'      // <- make sure this path matches your file
import CoursesPage from './pages/CoursesPage'
import EnrolledPage from './pages/EnrolledPage'
import FinishedPage from './pages/FinishedPage'
import ProfilePage from './pages/ProfilePage'

export default function App() {
  return (
<BrowserRouter basename="/app">
  <Routes>
    <Route path="login" element={<TabbedLogin />} />
    <Route element={<DashboardLayout />}>
      <Route path="courses"  element={<CoursesPage />} />
      <Route path="enrolled" element={<EnrolledPage />} />
      <Route path="finished" element={<FinishedPage />} />
      <Route path="profile"  element={<ProfilePage />} />
    </Route>
    <Route path="dashboard" element={<Navigate to="enrolled" replace />} />
    <Route path="*" element={<Navigate to="/login" replace />} /> {/* ✅ absolute */}
  </Routes>
</BrowserRouter>

  )
}
