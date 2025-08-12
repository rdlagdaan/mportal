import * as React from 'react'
import { useEffect } from 'react'
import { useOutletContext } from 'react-router-dom'

type LayoutCtx = { active: 'courses' | 'enrolled' | 'finished' | 'profile'; setActive: (k: LayoutCtx['active']) => void }

export default function EnrolledPage() {
  const { setActive } = useOutletContext<LayoutCtx>()
  useEffect(() => setActive('enrolled'), [setActive])

  const items = [
    {
      id: 101,
      title: 'React Basics',
      status: 'Enrolled',
      img: 'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=600&auto=format&fit=crop',
      schedule: 'Sat 9:00–12:00 (Aug 24 – Sep 21)',
    },
    {
      id: 102,
      title: 'SQL for Analysts',
      status: 'Enrolled',
      img: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=600&auto=format&fit=crop',
      schedule: 'Wed 18:00–20:00 (Aug 20 – Sep 17)',
    },
  ]

  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      <header>
        <h2 className="text-lg font-semibold text-green-900">Enrolled Courses</h2>
        <p className="text-sm text-green-700/80">Your active enrollments (static)</p>
      </header>

      <ul className="mt-4 divide-y divide-green-100">
        {items.map((it) => (
          <li key={it.id} className="flex items-center gap-4 py-3">
            <img src={it.img} alt="" className="h-16 w-16 rounded-xl object-cover" />
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
    </section>
  )
}
