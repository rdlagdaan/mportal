// src/pages/CertificatePage.tsx
import * as React from 'react'
import { useParams, Link, useOutletContext } from 'react-router-dom'
import { courseDetails } from '../data/staticCatalog'

type LayoutCtx = { active:'courses'|'enrolled'|'finished'|'profile'; setActive:(k:LayoutCtx['active'])=>void }

export default function CertificatePage() {
  const { courseId } = useParams()
  const ctx = useOutletContext<LayoutCtx | undefined>()
  React.useEffect(() => { ctx?.setActive('finished') }, [ctx])

  const id = Number(courseId)
  const c = courseDetails[id]
  const canvasRef = React.useRef<HTMLCanvasElement>(null)

  React.useEffect(() => {
    if (!c || !canvasRef.current) return
    const canvas = canvasRef.current
    canvas.width = 1200
    canvas.height = 800
    const g = canvas.getContext('2d')!
    // bg
    g.fillStyle = '#fff'
    g.fillRect(0, 0, canvas.width, canvas.height)
    // border (TUA gradient)
    const grad = g.createLinearGradient(0,0,canvas.width,0)
    grad.addColorStop(0,'#16a34a')   // green-600
    grad.addColorStop(.5,'#84cc16')  // lime-500
    grad.addColorStop(1,'#facc15')   // yellow-400
    g.lineWidth = 14
    g.strokeStyle = grad
    g.strokeRect(18,18,canvas.width-36,canvas.height-36)

    // heading
    g.fillStyle = '#065f46' // emerald-800-ish
    g.font = 'bold 46px system-ui, -apple-system, Segoe UI, Roboto'
    g.fillText('Certificate of Completion', 350, 140)

    // student (static prototype)
    g.font = '28px system-ui'
    g.fillStyle = '#14532d'
    g.fillText('This certifies that', 480, 220)

    g.font = 'bold 54px system-ui'
    g.fillStyle = '#166534'
    g.fillText('Randy Lagdaan', 430, 290)

    // course title
    g.font = '28px system-ui'
    g.fillStyle = '#065f46'
    g.fillText('has successfully completed the course', 380, 350)

    g.font = 'bold 36px system-ui'
    g.fillStyle = '#065f46'
    g.fillText(c.title, 380, 395)

    // details row
    g.font = '22px system-ui'
    g.fillStyle = '#064e3b'
    g.fillText(`Program: ${c.program}`, 380, 450)
    g.fillText(`Duration: ${c.duration}`, 380, 485)
    g.fillText(`Approved on: ${new Date().toLocaleDateString()}`, 380, 520)

    // footer
    g.font = 'bold 20px system-ui'
    g.fillStyle = '#065f46'
    g.fillText('Trinity University of Asia — Microcredentials', 380, 580)
  }, [c])

  if (!c) {
    return (
      <div className="rounded-2xl border border-green-100 bg-white/80 p-4">
        <div className="text-green-900">Certificate not found.</div>
        <Link to="/finished" className="mt-3 inline-flex rounded-xl bg-yellow-400 px-3 py-1.5 text-green-950">Back</Link>
      </div>
    )
  }

  const download = () => {
    const url = canvasRef.current!.toDataURL('image/png')
    const a = document.createElement('a')
    a.href = url
    a.download = `${c.title}-certificate.png`
    a.click()
  }

  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      <header className="mb-3">
        <h2 className="text-lg font-semibold text-green-900">Certificate — {c.title}</h2>
        <p className="text-sm text-green-700/80">Preview & download</p>
      </header>

      <div className="overflow-auto rounded-xl border border-green-100 bg-white p-3">
        <canvas ref={canvasRef} className="mx-auto block" />
      </div>

      <div className="mt-4 flex gap-2">
        <button onClick={download} className="rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700">
          Download PNG
        </button>
        <Link to="/finished" className="rounded-xl bg-yellow-400 px-4 py-2 text-green-950 hover:bg-yellow-500">
          Back to Finished
        </Link>
      </div>
    </section>
  )
}
