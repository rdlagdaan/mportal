// src/pages/BadgePage.tsx
import * as React from 'react'
import { useParams, Link, useOutletContext } from 'react-router-dom'
import { courseDetails } from '../data/staticCatalog'

type LayoutCtx = { active:'courses'|'enrolled'|'finished'|'profile'; setActive:(k:LayoutCtx['active'])=>void }

export default function BadgePage() {
  const { courseId } = useParams()
  const ctx = useOutletContext<LayoutCtx | undefined>()
  React.useEffect(() => { ctx?.setActive('finished') }, [ctx])

  const id = Number(courseId)
  const c = courseDetails[id]
  const canvasRef = React.useRef<HTMLCanvasElement>(null)

  React.useEffect(() => {
    if (!c || !canvasRef.current) return
    const canvas = canvasRef.current
    canvas.width = 700
    canvas.height = 700
    const g = canvas.getContext('2d')!

    // bg
    g.fillStyle = '#ffffff'
    g.fillRect(0,0,canvas.width,canvas.height)

    // outer ring gradient
    const grad = g.createLinearGradient(0,0,700,700)
    grad.addColorStop(0,'#16a34a')
    grad.addColorStop(.5,'#84cc16')
    grad.addColorStop(1,'#facc15')

    g.beginPath()
    g.arc(350,350,300,0,Math.PI*2)
    g.fillStyle = grad
    g.fill()

    // inner circle
    g.beginPath()
    g.arc(350,350,250,0,Math.PI*2)
    g.fillStyle = '#ffffff'
    g.fill()

    // cap icon (simple)
    g.fillStyle = '#065f46'
    g.beginPath()
    g.moveTo(210,330); g.lineTo(350,285); g.lineTo(490,330); g.lineTo(350,375); g.closePath(); g.fill()
    g.fillRect(300,375,100,18)

    // text
    g.fillStyle = '#065f46'
    g.font = 'bold 28px system-ui'
    g.textAlign = 'center'
    g.fillText('TUA Microcredential', 350, 430)
    g.font = 'bold 34px system-ui'
    g.fillText(c ? c.title : 'Course', 350, 470)

  }, [c])

  if (!c) {
    return (
      <div className="rounded-2xl border border-green-100 bg-white/80 p-4">
        <div className="text-green-900">Badge not found.</div>
        <Link to="/finished" className="mt-3 inline-flex rounded-xl bg-yellow-400 px-3 py-1.5 text-green-950">Back</Link>
      </div>
    )
  }

  const download = () => {
    const url = canvasRef.current!.toDataURL('image/png')
    const a = document.createElement('a')
    a.href = url
    a.download = `${c.title}-badge.png`
    a.click()
  }

  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      <header className="mb-3">
        <h2 className="text-lg font-semibold text-green-900">Badge — {c.title}</h2>
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
