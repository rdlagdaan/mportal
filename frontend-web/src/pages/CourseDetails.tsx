import * as React from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import { courseDetails } from '../data/staticCatalog'

export default function CourseDetails(){
  const { courseId } = useParams()
  const navigate = useNavigate()
  const id = Number(courseId)
  const c = courseDetails[id]

  if (!c) {
    return (
      <div className="rounded-2xl border border-green-100 bg-white/80 p-4">
        <div className="text-green-900">Course not found.</div>
        <Link to="/courses" className="mt-3 inline-flex rounded-xl bg-yellow-400 px-3 py-1.5 text-green-950">Back to Courses</Link>
      </div>
    )
  }

  function handleEnroll(){
    // Prototype: jump to Enrolled. Later this will open a payment screen.
    navigate('/enrolled')
  }

  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      <div className="flex flex-col gap-4 md:flex-row">
        <img src={c.img} alt="" className="h-48 w-full rounded-xl object-cover md:h-64 md:w-80" />
        <div className="flex-1">
          <div className="text-sm font-semibold text-green-700/80">{c.program}</div>
          <h2 className="text-2xl font-semibold text-green-900">{c.title}</h2>
          <p className="mt-2 text-green-800/90">{c.description}</p>

          <div className="mt-4 grid gap-3 sm:grid-cols-2">
            <Info label="Duration" value={c.duration} />
            <Info label="Schedule" value={c.schedule} />
            <Info label="Price" value={`${c.price.currency} ${c.price.amount.toLocaleString()}.00`} />
            <div className="flex items-center gap-3 rounded-xl border border-green-100 bg-white p-3">
              {c.teacher.avatar && (
                <img src={c.teacher.avatar} alt="" className="h-10 w-10 rounded-full object-cover" />
              )}
              <div>
                <div className="text-sm font-semibold text-green-900">{c.teacher.name}</div>
                {c.teacher.title && <div className="text-xs text-green-700/80">{c.teacher.title}</div>}
              </div>
            </div>
          </div>

          <button
            onClick={handleEnroll}
            className="mt-5 rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700"
          >
            Enroll
          </button>
          <Link to="/courses" className="ml-3 inline-flex rounded-xl bg-yellow-400 px-4 py-2 text-green-950 hover:bg-yellow-500">Back</Link>
        </div>
      </div>

      <div className="mt-6">
        <h3 className="text-lg font-semibold text-green-900">Sample Outline</h3>
        <ul className="mt-2 list-disc space-y-1 pl-5 text-green-800/90">
          {c.sampleOutline.map((s, i)=>(<li key={i}>{s}</li>))}
        </ul>
      </div>
    </section>
  )
}

function Info({label, value}:{label:string; value:string}){
  return (
    <div className="rounded-xl border border-green-100 bg-white p-3">
      <div className="text-xs font-medium text-green-700/80">{label}</div>
      <div className="text-sm font-semibold text-green-900">{value}</div>
    </div>
  )
}
