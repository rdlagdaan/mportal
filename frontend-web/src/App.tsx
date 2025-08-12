// src/App.tsx
import * as React from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import DashboardLayout from './DashboardLayout'
import TabbedLogin from './pages/TabbedLogin'      // <- make sure this path matches your file
import CoursesPage from './pages/CoursesPage'
import CourseDetails from './pages/CourseDetails' 
import EnrolledPage from './pages/EnrolledPage'
import FinishedPage from './pages/FinishedPage'
import ProfilePage from './pages/ProfilePage'

import CertificatePage from './pages/CertificatePage'   // NEW
import BadgePage from './pages/BadgePage'               // NEW
import CourseReportPage from './pages/CourseReportPage' // NEW

export default function App() {
  return (
<BrowserRouter basename="/app">
  <Routes>
    <Route path="login" element={<TabbedLogin />} />
    <Route element={<DashboardLayout />}>
      <Route path="courses"  element={<CoursesPage />} />
       <Route path="courses/:courseId" element={<CourseDetails />} /> 
       <Route path="courses/:courseId/report" element={<CourseReportPage />} />
      <Route path="enrolled" element={<EnrolledPage />} />
      <Route path="finished" element={<FinishedPage />} />
       <Route path="finished/:courseId/certificate" element={<CertificatePage />} /> {/* NEW */}
        <Route path="finished/:courseId/badge" element={<BadgePage />} />              {/* NEW */}
      <Route path="profile"  element={<ProfilePage />} />
    </Route>
    <Route path="dashboard" element={<Navigate to="courses" replace />} />
    <Route path="*" element={<Navigate to="/login" replace />} /> {/* ✅ absolute */}
  </Routes>
</BrowserRouter>

  )
}
