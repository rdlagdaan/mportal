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
        {/* Public login */}
        <Route path="login" element={<TabbedLogin />} />

        {/* Default: land on login */}
        <Route index element={<Navigate to="login" replace />} />

        {/* Dashboard shell + pages */}
        <Route element={<DashboardLayout />}>
          <Route path="courses"  element={<CoursesPage />} />
          <Route path="enrolled" element={<EnrolledPage />} />
          <Route path="finished" element={<FinishedPage />} />
          <Route path="profile"  element={<ProfilePage />} />
        </Route>

        {/* Optional alias: /app/dashboard -> /app/courses */}
        <Route path="dashboard" element={<Navigate to="courses" replace />} />

        {/* Fallback */}
        <Route path="*" element={<Navigate to="login" replace />} />
      </Routes>
    </BrowserRouter>
  )
}
