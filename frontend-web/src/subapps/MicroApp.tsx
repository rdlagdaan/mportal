// src/subapps/MicroApp.tsx
import * as React from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import DashboardLayout from '@/DashboardLayout'
import CoursesPage from '@/pages/CoursesPage'
import CourseDetails from '@/pages/CourseDetails'
import EnrolledPage from '@/pages/EnrolledPage'
import CoursePortalPage from '@/pages/CoursePortalPage'
import FinishedPage from '@/pages/FinishedPage'
import ProfilePage from '@/pages/ProfilePage'
import CertificatePage from '@/pages/CertificatePage'
import BadgePage from '@/pages/BadgePage'
import CheckoutPage from '@/pages/CheckoutPage'
import CourseReportPage from '@/pages/CourseReportPage'

export default function MicroApp() {
  return (
    <Routes>
      <Route element={<DashboardLayout />}>
        {/* /app -> courses */}
        <Route index element={<Navigate to="/courses" replace />} />

        <Route path="courses" element={<CoursesPage />} />
        <Route path="courses/:courseId" element={<CourseDetails />} />
        <Route path="courses/:courseId/checkout" element={<CheckoutPage />} />
        <Route path="courses/:courseId/report" element={<CourseReportPage />} />
        <Route path="courses/:courseId/portal" element={<CoursePortalPage />} />
        <Route path="enrolled" element={<EnrolledPage />} />
        <Route path="finished" element={<FinishedPage />} />
        <Route path="finished/:courseId/certificate" element={<CertificatePage />} />
        <Route path="finished/:courseId/badge" element={<BadgePage />} />
        <Route path="profile" element={<ProfilePage />} />
      </Route>

      {/* keep if you like, but not required now */}
      <Route path="dashboard" element={<Navigate to="/courses" replace />} />
      {/* ❌ remove the `path="*"` redirect here */}
    </Routes>
  )
}
