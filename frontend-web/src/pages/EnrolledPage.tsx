import * as React from 'react'
import { useEffect } from 'react'
import { Link, useOutletContext } from 'react-router-dom'

type LayoutCtx = { active: 'courses' | 'enrolled' | 'finished' | 'profile'; setActive: (k: LayoutCtx['active']) => void }

export default function EnrolledPage() {
  // keep sidebar highlight in sync
  const { setActive } = useOutletContext<LayoutCtx>()
  useEffect(() => setActive('enrolled'), [setActive])

  // match existing courseDetails ids so /courses/:id works
  const enrolled = [
    {
      id: 21, // React Basics
      title: 'React Basics',
      img: 'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=600&auto=format&fit=crop',
      schedule: 'Sat 9:00–12:00 (Aug 24 – Sep 21)',
      status: 'Enrolled',
    },
    {
      id: 12, // SQL for Analysts
      title: 'SQL for Analysts',
      img: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=600&auto=format&fit=crop',
      schedule: 'Wed 18:00–21:00 (Aug 20 – Sep 17)',
      status: 'Enrolled',
    },
  ]

  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      <header>
        <h2 className="text-lg font-semibold text-green-900">Enrolled Courses</h2>
        <p className="text-sm text-green-700/80">Your active enrollments (static)</p>
      </header>

      <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        {enrolled.map((c) => (
          <div key={c.id} className="rounded-2xl border border-green-100 bg-white p-4 shadow-sm">
            <div className="rounded-xl border border-green-100 p-2">
              <img src={c.img} alt="" className="h-32 w-full rounded-lg object-cover" />
              <div className="mt-2 flex items-start justify-between">
                <div className="text-sm font-medium text-green-900">{c.title}</div>
                <span className="ml-3 rounded-full bg-yellow-200 px-2.5 py-0.5 text-xs font-semibold text-green-900">
                  {c.status}
                </span>
              </div>
              <div className="mt-1 text-xs text-green-700/80">{c.schedule}</div>
              <Link
                to={`/courses/${c.id}`}
                className="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700"
              >
                Open course
              </Link>
            </div>
          </div>
        ))}
      </div>
    </section>
  )
}
