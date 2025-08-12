import * as React from 'react'
import { useState } from 'react'
import {
  AcademicCapIcon,
  BookmarkSquareIcon,
  CheckBadgeIcon,
  UserCircleIcon,
  Bars3Icon,
  XMarkIcon,
  MagnifyingGlassIcon,
} from '@heroicons/react/24/outline'

/**
 * PROTOTYPE DASHBOARD (STATIC)
 * - Green → Yellow → White theme (matches TabbedLogin)
 * - Sidebar modules: Courses, Enrolled Courses, Finished Courses, Student Profile
 * - Static content on the right panel; no API calls yet
 * - Keyboard/screen-reader friendly; responsive sidebar
 */

const navItems = [
  { key: 'courses', label: 'Courses', icon: AcademicCapIcon },
  { key: 'enrolled', label: 'Enrolled Courses', icon: BookmarkSquareIcon },
  { key: 'finished', label: 'Finished Courses', icon: CheckBadgeIcon },
  { key: 'profile', label: 'Student Profile', icon: UserCircleIcon },
] as const

export default function Dashboard() {
  const [active, setActive] = useState<(typeof navItems)[number]['key']>('courses')
  const [sidebarOpen, setSidebarOpen] = useState(false)

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
            <h1 className="text-lg font-semibold text-green-900">Microcredentials Dashboard</h1>
          </div>
          <div className="ml-auto hidden items-center gap-2 rounded-2xl border border-green-100 bg-white px-3 py-1.5 text-sm text-green-900 shadow-sm sm:flex">
            <MagnifyingGlassIcon className="h-5 w-5 text-green-700/70" />
            <input
              placeholder="Quick search (static)"
              className="w-48 bg-transparent placeholder:text-green-700/60 focus:outline-none"
            />
          </div>
        </div>
      </header>

      {/* Layout */}
      <div className="mx-auto grid max-w-7xl grid-cols-1 lg:grid-cols-[260px_1fr]">
        {/* Sidebar – desktop */}
        <aside className="sticky top-[57px] hidden h-[calc(100vh-57px)] lg:block">
          <Sidebar active={active} onSelect={setActive} />
        </aside>

        {/* Sidebar – mobile drawer */}
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
              <Sidebar
                active={active}
                onSelect={(k) => {
                  setActive(k)
                  setSidebarOpen(false)
                }}
              />
            </div>
          </div>
        )}

        {/* Main content */}
        <main className="px-4 py-6 sm:px-6 lg:px-8">
          {active === 'courses' && <CoursesPanel />}
          {active === 'enrolled' && <EnrolledPanel />}
          {active === 'finished' && <FinishedPanel />}
          {active === 'profile' && <ProfilePanel />}
        </main>
      </div>
    </div>
  )
}

function Sidebar({
  active,
  onSelect,
}: {
  active: (typeof navItems)[number]['key']
  onSelect: (k: (typeof navItems)[number]['key']) => void
}) {
  return (
    <nav
      aria-label="Sidebar"
      className="flex h-full w-[260px] flex-col gap-3 border-r border-green-100 bg-white/80 p-4 backdrop-blur"
    >
      <div className="text-xs font-semibold uppercase tracking-wide text-green-700/70">Modules</div>
      <ul className="mt-1 space-y-1">
        {navItems.map((item) => (
          <li key={item.key}>
            <button
              onClick={() => onSelect(item.key)}
              className={[
                'group flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left',
                active === item.key
                  ? 'bg-yellow-200/70 text-green-900 ring-2 ring-yellow-300'
                  : 'text-green-900 hover:bg-yellow-100',
              ].join(' ')}
            >
              <item.icon
                className={[
                  'h-5 w-5',
                  active === item.key ? 'text-green-800' : 'text-green-700/80 group-hover:text-green-800',
                ].join(' ')}
              />
              <span className="text-sm font-medium">{item.label}</span>
            </button>
          </li>
        ))}
      </ul>

      <div className="mt-auto grid gap-2 text-xs text-green-700/70">
        <div className="rounded-xl border border-green-100 bg-white p-3">
          <div className="font-semibold text-green-900">Tip</div>
          <p>Prototype only. Content is static for now.</p>
        </div>
      </div>
    </nav>
  )
}

// --- PANELS (static placeholders) ---

function PanelFrame({ title, subtitle, children }: { title: string; subtitle?: string; children: React.ReactNode }) {
  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      <header className="flex items-baseline justify-between gap-2">
        <div>
          <h2 className="text-lg font-semibold text-green-900">{title}</h2>
          {subtitle && <p className="text-sm text-green-700/80">{subtitle}</p>}
        </div>
      </header>
      <div className="mt-4">{children}</div>
    </section>
  )
}

function CoursesPanel() {
  // static programs + courses preview
  const programs = [
    {
      id: 1,
      name: 'Data Analytics',
      img: 'https://images.unsplash.com/photo-1551281044-8e8b89f0ee3b?q=80&w=800&auto=format&fit=crop',
      blurb: 'Hands-on analytics using Python and SQL.',
      courses: [
        { id: 11, title: 'Intro to Data Viz', img: 'https://images.unsplash.com/photo-1551281044-8e8b89f0ee3b?q=80&w=600&auto=format&fit=crop' },
        { id: 12, title: 'SQL for Analysts', img: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=600&auto=format&fit=crop' },
      ],
    },
    {
      id: 2,
      name: 'Web Development',
      img: 'https://images.unsplash.com/photo-1529400971008-f566de0e6dfc?q=80&w=800&auto=format&fit=crop',
      blurb: 'Frontend to backend foundations.',
      courses: [
        { id: 21, title: 'React Basics', img: 'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=600&auto=format&fit=crop' },
        { id: 22, title: 'Laravel Essentials', img: 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?q=80&w=600&auto=format&fit=crop' },
      ],
    },
  ]

  return (
    <PanelFrame title="Courses" subtitle="Browse programs and their sample courses (static demo)">
      <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        {programs.map((p) => (
          <div key={p.id} className="rounded-2xl border border-green-100 bg-white p-4 shadow-sm">
            <div className="flex items-center gap-3">
              <img src={p.img} alt="" className="h-16 w-16 rounded-xl object-cover" />
              <div>
                <div className="font-semibold text-green-900">{p.name}</div>
                <div className="text-sm text-green-700/80">{p.blurb}</div>
              </div>
            </div>
            <div className="mt-3 grid grid-cols-2 gap-3">
              {p.courses.map((c) => (
                <div key={c.id} className="rounded-xl border border-green-100 p-2">
                  <img src={c.img} alt="" className="h-28 w-full rounded-lg object-cover" />
                  <div className="mt-2 text-sm font-medium text-green-900">{c.title}</div>
                  <button className="mt-2 w-full rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700">
                    Register
                  </button>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </PanelFrame>
  )
}

function EnrolledPanel() {
  const items = [
    {
      id: 101,
      title: 'React Basics',
      status: 'Enrolled',
      img: 'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=600&auto=format&fit=crop',
      schedule: 'Sat 9:00–12:00 (Aug 24 – Sep 21)'
    },
    {
      id: 102,
      title: 'SQL for Analysts',
      status: 'Enrolled',
      img: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=600&auto=format&fit=crop',
      schedule: 'Wed 18:00–20:00 (Aug 20 – Sep 17)'
    },
  ]

  return (
    <PanelFrame title="Enrolled Courses" subtitle="Your active enrollments (static demo)">
      <ul className="divide-y divide-green-100">
        {items.map((it) => (
          <li key={it.id} className="flex items-center gap-4 py-3">
            <img src={it.img} className="h-16 w-16 rounded-xl object-cover" alt="" />
            <div className="flex-1">
              <div className="font-medium text-green-900">{it.title}</div>
              <div className="text-sm text-green-700/80">{it.schedule}</div>
            </div>
            <span className="rounded-full bg-yellow-200 px-2.5 py-1 text-xs font-semibold text-green-900">{it.status}</span>
            <button className="rounded-xl bg-white px-3 py-1.5 text-sm text-green-900 ring-1 ring-green-200 hover:bg-green-50">
              Go to course
            </button>
          </li>
        ))}
      </ul>
    </PanelFrame>
  )
}

function FinishedPanel() {
  const items = [
    {
      id: 201,
      title: 'Intro to Data Viz',
      img: 'https://images.unsplash.com/photo-1551281044-8e8b89f0ee3b?q=80&w=600&auto=format&fit=crop',
      date: 'Approved on Aug 3, 2025',
    },
  ]

  return (
    <PanelFrame title="Finished Courses" subtitle="Download certificates and badges (static demo)">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {items.map((it) => (
          <div key={it.id} className="rounded-2xl border border-green-100 bg-white p-3 shadow-sm">
            <img src={it.img} className="h-36 w-full rounded-xl object-cover" alt="" />
            <div className="mt-3 font-medium text-green-900">{it.title}</div>
            <div className="text-sm text-green-700/80">{it.date}</div>
            <div className="mt-3 flex gap-2">
              <button className="flex-1 rounded-xl bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700">
                Download Certificate
              </button>
              <button className="rounded-xl bg-yellow-400 px-3 py-1.5 text-sm font-medium text-green-950 hover:bg-yellow-500">
                View Badge
              </button>
            </div>
          </div>
        ))}
      </div>
    </PanelFrame>
  )
}

function ProfilePanel() {
  return (
    <PanelFrame title="Student Profile" subtitle="Manage your basic information (static demo)">
      <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div className="flex items-center gap-4 rounded-2xl border border-green-100 bg-white p-4 shadow-sm">
          <img
            src="https://images.unsplash.com/photo-1502685104226-ee32379fefbe?q=80&w=400&auto=format&fit=crop"
            className="h-16 w-16 rounded-full object-cover"
            alt=""
          />
          <div>
            <div className="font-semibold text-green-900">Randy Lagdaan</div>
            <div className="text-sm text-green-700/80">randy@example.com</div>
          </div>
        </div>
        <div className="rounded-2xl border border-green-100 bg-white p-4 shadow-sm md:col-span-2">
          <div className="grid gap-3 sm:grid-cols-2">
            <Field label="First name" value="Randy" />
            <Field label="Last name" value="Lagdaan" />
            <Field label="Mobile" value="(+63) 900 000 0000" />
            <Field label="Organization" value="Trinity University of Asia" />
          </div>
          <div className="mt-4 flex justify-end">
            <button className="rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700">Save (static)</button>
          </div>
        </div>
      </div>
    </PanelFrame>
  )
}

function Field({ label, value }: { label: string; value: string }) {
  return (
    <label className="block">
      <div className="text-xs font-medium text-green-800">{label}</div>
      <input
        defaultValue={value}
        className="mt-1 w-full rounded-xl border border-green-200 bg-white px-3 py-2 text-green-900 placeholder:text-green-700/60 focus:ring-2 focus:ring-yellow-300 focus:outline-none"
      />
    </label>
  )
}
