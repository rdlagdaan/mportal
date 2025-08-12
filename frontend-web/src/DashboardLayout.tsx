import * as React from 'react'
import { useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import {
  AcademicCapIcon,
  BookmarkSquareIcon,
  CheckBadgeIcon,
  UserCircleIcon,
  Bars3Icon,
  XMarkIcon,
  MagnifyingGlassIcon,
} from '@heroicons/react/24/outline'
import { logout } from '@/utils/axiosnapi' // <-- your real API logout

/**
 * DashboardLayout.tsx (STATIC)
 * - Responsive header + sidebar
 * - Uses React Router NavLink to render nested routes into <Outlet />
 * - Theme: green → yellow → white
 */

const NAV = [
  { key: 'courses', label: 'Courses', to: '/courses', icon: AcademicCapIcon },
  { key: 'enrolled', label: 'Enrolled Courses', to: '/enrolled', icon: BookmarkSquareIcon },
  { key: 'finished', label: 'Finished Courses', to: '/finished', icon: CheckBadgeIcon },
  { key: 'profile', label: 'Student Profile', to: '/profile', icon: UserCircleIcon },
] as const

export default function DashboardLayout() {
  const [sidebarOpen, setSidebarOpen] = useState(false)
   const navigate = useNavigate()
     
  async function handleLogout() {
    try {
      await logout()
      // With basename="/app", this becomes /app/login
      navigate('/logout')
    } catch {
      alert('Logout failed')
    }
  }


  return (
    <div className="min-h-screen bg-gradient-to-br from-green-50 via-yellow-50 to-white">
      {/* Header */}
      <header className="sticky top-0 z-40 border-b border-green-100/60 bg-white/70 backdrop-blur">
        <div className="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
          <button
            type="button"
            aria-label="Open menu"
            onClick={() => setSidebarOpen(true)}
            className="inline-flex items-center justify-center rounded-xl p-2 text-green-800 hover:bg-yellow-100 lg:hidden"
          >
            <Bars3Icon className="h-6 w-6" />
          </button>
          <div className="flex items-center gap-3">
            <div className="h-8 w-8 rounded-xl bg-gradient-to-br from-green-500 via-lime-400 to-yellow-300" />
            <h1 className="text-lg font-semibold text-green-900">Microcredentials 111Dashboard</h1>
          </div>
          <div className="ml-auto hidden items-center gap-2 rounded-2xl border border-green-100 bg-white px-3 py-1.5 text-sm text-green-900 shadow-sm sm:flex">
            <MagnifyingGlassIcon className="h-5 w-5 text-green-700/70" />
            <input
              placeholder="Quick search (static)"
              className="w-48 bg-transparent placeholder:text-green-700/60 focus:outline-none"
            />
          </div>

          <button
            onClick={handleLogout}
            className="flex items-center gap-2 rounded-xl bg-white px-3 py-1.5 text-sm font-medium text-green-900 ring-1 ring-green-200 hover:bg-green-50"
            title="Log out"
          ></button>          
        </div>
      </header>

      {/* Grid */}
      <div className="mx-auto grid max-w-7xl grid-cols-1 lg:grid-cols-[260px_1fr]">
        {/* Desktop Sidebar */}
        <aside className="sticky top-[57px] hidden h-[calc(100vh-57px)] lg:block">
          <Sidebar />
        </aside>

        {/* Mobile Drawer */}
        {sidebarOpen && (
          <div className="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
            <div className="absolute inset-0 bg-black/30" onClick={() => setSidebarOpen(false)} />
            <div className="absolute left-0 top-0 h-full w-72 bg-white shadow-xl">
              <div className="flex items-center justify-between border-b px-4 py-3">
                <div className="text-sm font-semibold text-green-900">Menu</div>
                <button
                  aria-label="Close menu"
                  className="rounded-xl p-2 text-green-800 hover:bg-yellow-100"
                  onClick={() => setSidebarOpen(false)}
                >
                  <XMarkIcon className="h-6 w-6" />
                </button>
              </div>
              <Sidebar onNavigate={() => setSidebarOpen(false)} />
            </div>
          </div>
        )}

        {/* Main */}
        <main className="px-4 py-6 sm:px-6 lg:px-8">
          <Outlet />
        </main>
      </div>
    </div>
  )
}

function Sidebar({ onNavigate }: { onNavigate?: () => void } = {}) {
  return (
    <nav aria-label="Sidebar" className="flex h-full w-[260px] flex-col gap-3 border-r border-green-100 bg-white/80 p-4 backdrop-blur">
      <div className="text-xs font-semibold uppercase tracking-wide text-green-700/70">Modules</div>
      <ul className="mt-1 space-y-1">
        {NAV.map((n) => (
          <li key={n.key}>
            <NavLink
              to={n.to}
              onClick={onNavigate}
              className={({ isActive }) =>
                [
                  'group flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left',
                  isActive ? 'bg-yellow-200/70 text-green-900 ring-2 ring-yellow-300' : 'text-green-900 hover:bg-yellow-100',
                ].join(' ')
              }
            >
              <n.icon className="h-5 w-5 text-green-700/80 group-hover:text-green-800" />
              <span className="text-sm font-medium">{n.label}</span>
            </NavLink>
          </li>
        ))}
      </ul>
      <div className="mt-auto rounded-xl border border-green-100 bg-white p-3 text-xs text-green-700/70">
        Prototype only. Content is static.
      </div>
    </nav>
  )
}
