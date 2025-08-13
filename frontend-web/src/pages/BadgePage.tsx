// src/pages/BadgePage.tsx
import * as React from 'react'
import { useParams, Link, useOutletContext } from 'react-router-dom'
import { courseDetails } from '../data/staticCatalog'

type LayoutCtx = { active:'courses'|'enrolled'|'finished'|'profile'; setActive:(k:LayoutCtx['active'])=>void }

const PROGRAM_BADGE: Record<string, string> = {
  'Business Management': '/BUSINESSBADGE.png',
  'Community Pharmacy': '/PHARMACYBADGE.png',
  'Hospitality Management': '/TOURISMBADGE.png',
  'Healthcare Hospitality & Tourism Concierge': '/TOURISMBADGE.png',
}

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
    const g = canvas.getContext('2d')!

    // square canvas for badge PNGs
    canvas.width = 740
    canvas.height = 740

    const badge = new Image()
    badge.onload = () => {
      // white bg for transparent PNG edges
      g.fillStyle = '#ffffff'
      g.fillRect(0,0,canvas.width,canvas.height)

      // center badge and keep aspect
      const pad = 40
      const w = canvas.width - pad*2
      const h = canvas.height - pad*2 - 110 // leave room for title
      g.imageSmoothingQuality = 'high'
      g.drawImage(badge, pad, pad, w, h)

      // course title under badge (two lines max)
      g.fillStyle = '#065f46'
      g.textAlign = 'center'
      g.font = '700 28px system-ui, -apple-system, Segoe UI, Roboto'
      wrapCenter(g, c.title, canvas.width/2, canvas.height - 70, canvas.width - 80, 32)
    }
    badge.src = PROGRAM_BADGE[c.program] ?? PROGRAM_BADGE['Business Management']

    function wrapCenter(ctx:CanvasRenderingContext2D, text:string, x:number, y:number, maxWidth:number, lineHeight:number) {
      const words = text.split(' ')
      const lines:string[] = []
      let line = ''
      for (let n = 0; n < words.length; n++) {
        const testLine = line + words[n] + ' '
        if (ctx.measureText(testLine).width > maxWidth && n > 0) {
          lines.push(line.trim())
          line = words[n] + ' '
        } else {
          line = testLine
        }
      }
      lines.push(line.trim())
      const startY = y - ((lines.length - 1) * lineHeight) / 2
      lines.slice(0,2).forEach((ln, i) => ctx.fillText(ln, x, startY + i*lineHeight))
    }
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
