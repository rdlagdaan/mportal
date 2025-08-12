import * as React from 'react'
import { Link } from 'react-router-dom'
import { programs } from '../data/staticCatalog'

export default function CoursesPage(){
  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      <header>
        <h2 className="text-lg font-semibold text-green-900">Courses</h2>
        <p className="text-sm text-green-700/80">Browse programs and sample courses (static)</p>
      </header>
      <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
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
                  <Link
                    to={`/courses/${c.id}`}
                    className="mt-2 inline-flex w-full items-center justify-center rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700"
                  >
                    Register
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

