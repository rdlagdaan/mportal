// src/pages/CourseReportPage.tsx
import * as React from 'react'
import { useParams, Link, useOutletContext } from 'react-router-dom'
import { courseDetails } from '../data/staticCatalog'

type LayoutCtx = { active:'courses'|'enrolled'|'finished'|'profile'; setActive:(k:LayoutCtx['active'])=>void }

export default function CourseReportPage() {
  const { courseId } = useParams()
  const ctx = useOutletContext<LayoutCtx | undefined>()
  React.useEffect(() => { ctx?.setActive('finished') }, [ctx]) // keep highlight under Finished

  const id = Number(courseId)
  const c = courseDetails[id]
  if (!c) {
    return (
      <div className="rounded-2xl border border-green-100 bg-white/80 p-4">
        <div className="text-green-900">Course not found.</div>
        <Link to="/finished" className="mt-3 inline-flex rounded-xl bg-yellow-400 px-3 py-1.5 text-green-950">Back</Link>
      </div>
    )
  }

  // Static demo data
  const student = { name: 'Randy Lagdaan', grade: '91%', status: 'Passed', teacherComment: 'Consistent, engaged, and delivered high-quality work on the final project.' }
  const milestones = [
    { name: 'Quiz 1', score: 18, outOf: 20, status: 'Done' },
    { name: 'Assignment: Mini Project', score: 38, outOf: 40, status: 'Done' },
    { name: 'Quiz 2', score: 16, outOf: 20, status: 'Done' },
    { name: 'Final Project', score: 19, outOf: 20, status: 'Done' },
  ]

  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      <header>
        <h2 className="text-lg font-semibold text-green-900">Performance — {c.title}</h2>
        <p className="text-sm text-green-700/80">Student summary & teacher evaluation (static)</p>
      </header>

      <div className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div className="rounded-2xl border border-green-100 bg-white p-4 lg:col-span-2">
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <Info label="Student" value={student.name} />
            <Info label="Grade" value={student.grade} />
            <Info label="Status" value={student.status} />
            <Info label="Teacher" value={c.teacher.name} />
          </div>

          <div className="mt-4">
            <h3 className="text-sm font-semibold text-green-900">Milestones</h3>
            <ul className="mt-2 divide-y divide-green-100">
              {milestones.map((m, i) => (
                <li key={i} className="flex items-center justify-between py-2">
                  <div className="text-green-900">{m.name}</div>
                  <div className="text-sm text-green-800">{m.score} / {m.outOf}</div>
                  <span className="rounded-full bg-yellow-200 px-2.5 py-0.5 text-xs font-semibold text-green-900">{m.status}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>

        <div className="rounded-2xl border border-green-100 bg-white p-4">
          <h3 className="text-sm font-semibold text-green-900">Teacher’s evaluation</h3>
          <p className="mt-2 text-sm text-green-800/90">{student.teacherComment}</p>

          <div className="mt-4 flex gap-2">
            <Link to={`/finished/${id}/certificate`} className="rounded-xl bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700">
              View Certificate
            </Link>
            <Link to={`/finished/${id}/badge`} className="rounded-xl bg-yellow-400 px-3 py-1.5 text-sm font-medium text-green-950 hover:bg-yellow-500">
              View Badge
            </Link>
          </div>
        </div>
      </div>

      <div className="mt-4">
        <Link to="/finished" className="rounded-xl bg-white px-4 py-2 text-green-900 ring-1 ring-green-200 hover:bg-green-50">Back to Finished</Link>
      </div>
    </section>
  )
}

function Info({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-xl border border-green-100 bg-white p-3">
      <div className="text-xs font-medium text-green-700/80">{label}</div>
      <div className="text-sm font-semibold text-green-900">{value}</div>
    </div>
  )
}
