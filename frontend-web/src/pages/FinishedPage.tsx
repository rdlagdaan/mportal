import * as React from 'react'
import { useEffect } from 'react'
import { Link, useOutletContext } from 'react-router-dom'

type LayoutCtx = { active: 'courses' | 'enrolled' | 'finished' | 'profile'; setActive: (k: LayoutCtx['active']) => void }

export default function FinishedPage() {
  const { setActive } = useOutletContext<LayoutCtx>()
  useEffect(() => setActive('finished'), [setActive])

  const finished = [
    {
      id: 11, // Intro to Data Viz
      title: 'Intro to Data Viz',
      img: 'https://images.unsplash.com/photo-1551281044-8e8b89f0ee3b?q=80&w=600&auto=format&fit=crop',
      approvedOn: 'Aug 3, 2025',
    },
  ]

  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      <header>
        <h2 className="text-lg font-semibold text-green-900">Finished Courses</h2>
        <p className="text-sm text-green-700/80">Certificates & badges (static)</p>
      </header>

      <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {finished.map((c) => (
          <div key={c.id} className="rounded-2xl border border-green-100 bg-white p-4 shadow-sm">
            <img src={c.img} alt="" className="h-36 w-full rounded-xl object-cover" />
            <div className="mt-3 font-medium text-green-900">{c.title}</div>
            <div className="text-sm text-green-700/80">Approved on {c.approvedOn}</div>
            <div className="mt-3 grid grid-cols-2 gap-2">
              <button className="rounded-xl bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700">
                Download Certificate
              </button>
              <button className="rounded-xl bg-yellow-400 px-3 py-1.5 text-sm font-medium text-green-950 hover:bg-yellow-500">
                View Badge
              </button>
            </div>
            <Link
              to={`/courses/${c.id}`}
              className="mt-2 inline-flex w-full items-center justify-center rounded-xl bg-white px-3 py-1.5 text-sm font-medium text-green-900 ring-1 ring-green-200 hover:bg-green-50"
            >
              View course
            </Link>
          </div>
        ))}
      </div>
    </section>
  )
}
