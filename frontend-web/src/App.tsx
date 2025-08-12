import * as React from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import DashboardLayout from './DashboardLayout'
import CoursesPage from './pages/CoursesPage'
import CourseDetails from './pages/CourseDetails'

// Simple placeholders to satisfy routes (static)
function EnrolledPage(){
  return <div className="rounded-2xl border border-green-100 bg-white/80 p-4">Enrolled (static placeholder)</div>
}
function FinishedPage(){
  return <div className="rounded-2xl border border-green-100 bg-white/80 p-4">Finished (static placeholder)</div>
}
function ProfilePage(){
  return <div className="rounded-2xl border border-green-100 bg-white/80 p-4">Profile (static placeholder)</div>
}

export default function App(){
  return (
    <BrowserRouter basename="/app">
      <Routes>
        <Route element={<DashboardLayout/>}>
          <Route index element={<Navigate to="courses" replace/>} />
          <Route path="courses" element={<CoursesPage/>} />
          <Route path="courses/:courseId" element={<CourseDetails/>} />
          <Route path="enrolled" element={<EnrolledPage/>} />
          <Route path="finished" element={<FinishedPage/>} />
          <Route path="profile" element={<ProfilePage/>} />
          <Route path="*" element={<div className="p-4 text-green-900">Not found (static)</div>} />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}