import * as React from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import DashboardLayout from './DashboardLayout'
import CoursesPage from './pages/CoursesPage'
import EnrolledPage from './pages/EnrolledPage'
import FinishedPage from './pages/FinishedPage'
import ProfilePage from './pages/ProfilePage'

export default function App() {
  return (
    <BrowserRouter basename="/app">
      <Routes>
        <Route element={<DashboardLayout />}>
          <Route index element={<Navigate to="courses" replace />} />
          <Route path="courses" element={<CoursesPage />} />
          <Route path="enrolled" element={<EnrolledPage />} />
          <Route path="finished" element={<FinishedPage />} />
          <Route path="profile" element={<ProfilePage />} />
          <Route path="*" element={<div className="p-6 text-green-900">Not found</div>} />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}
