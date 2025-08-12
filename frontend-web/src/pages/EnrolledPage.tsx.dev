import * as React from 'react'
import { useEffect } from 'react'
import { Link, useOutletContext } from 'react-router-dom'
import { enrolledPrograms } from '../data/staticEnrolledCatalog'

type LayoutCtx = {
  active: 'courses' | 'enrolled' | 'finished' | 'profile'
  setActive: (k: LayoutCtx['active']) => void
}

export default function EnrolledPage() {
  // keep sidebar highlight synced with the page
  const { setActive } = useOutletContext<LayoutCtx>()
  useEffect(() => setActive('enrolled'), [setActive])

  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      <header>
        <h2 className="text-lg font-semibold text-green-900">Enrolled Courses</h2>
        <p className="text-sm text-green-700/80">Your active enrollments (static)</p>
      </header>

      <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        {enrolledPrograms.map((p) => (
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
                  <div className="mt-2 flex items-start justify-between">
                    <div className="text-sm font-medium text-green-900">{c.title}</div>
                    <span className="ml-2 rounded-full bg-yellow-200 px-2 py-0.5 text-xs font-semibold text-green-900">
                      {c.status}
                    </span>
                  </div>
                  <div className="mt-1 text-xs text-green-700/80">{c.schedule}</div>
                  <Link
                    to={`/courses/${c.id}`}
                    className="mt-2 inline-flex w-full items-center justify-center rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700"
                  >
                    Open course
                  </Link>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </section>
  )
}
