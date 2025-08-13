import * as React from 'react'
import { useParams, Link, useOutletContext } from 'react-router-dom'
import { courseDetails } from '../data/staticCatalog'

type LayoutCtx = { active:'courses'|'enrolled'|'finished'|'profile'; setActive:(k:LayoutCtx['active'])=>void }

// Files live in backend/public (web root). Use absolute paths.
const PROGRAM_BADGE: Record<string, string> = {
  'Business Management': '/BUSINESSBADGE.png',
  'Community Pharmacy': '/PHARMACYBADGE.png',
  'Hospitality Management': '/TOURISMBADGE.png',
  'Healthcare Hospitality & Tourism Concierge': '/TOURISMBADGE.png', // reuse tourism badge
}

export default function BadgePage() {
  const params = useParams()
  const ctx = useOutletContext<LayoutCtx | undefined>()
  React.useEffect(() => { ctx?.setActive('finished') }, [ctx])

  const raw = (params.courseId ?? params.id ?? '').toString()
  const id = Number(raw.replace(/[^\d]/g, ''))
  const c = Number.isFinite(id) ? courseDetails[id] : undefined

  if (!c) {
    return (
      <div className="rounded-2xl border border-green-100 bg-white/80 p-4">
        <div className="text-green-900">Badge not found.</div>
        <Link to="/finished" className="mt-3 inline-flex rounded-xl bg-yellow-400 px-3 py-1.5 text-green-950">Back</Link>
      </div>
    )
  }

  const src = PROGRAM_BADGE[c.program] ?? PROGRAM_BADGE['Business Management']

  const download = () => {
    const a = document.createElement('a')
    a.href = src
    a.download = `${c.title}-badge.png`
    document.body.appendChild(a)
    a.click()
    a.remove()
  }

  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      <header className="mb-3">
        <h2 className="text-lg font-semibold text-green-900">Badge — {c.title}</h2>
        <p className="text-sm text-green-700/80">Preview & download</p>
      </header>

      <div className="overflow-auto rounded-xl border border-green-100 bg-white p-3">
        <img
          src={src}
          alt={`Badge — ${c.title}`}
          className="mx-auto block h-auto max-h-[80vh] w-full max-w-[560px] object-contain"
          loading="eager"
        />
      </div>

      <div className="mt-4 flex gap-2">
        <button onClick={download} className="rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700">
          Download PNG
        </button>
        <a href={src} target="_blank" rel="noopener noreferrer"
           className="rounded-xl bg-white px-4 py-2 text-green-900 ring-1 ring-green-200 hover:bg-green-50">
          Open in new tab
        </a>
        <Link to="/finished" className="rounded-xl bg-yellow-400 px-4 py-2 text-green-950 hover:bg-yellow-500">
          Back to Finished
        </Link>
      </div>
    </section>
  )
}
