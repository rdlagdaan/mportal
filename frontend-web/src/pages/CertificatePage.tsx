// src/pages/CertificatePage.tsx
import * as React from 'react'
import { useParams, Link, useOutletContext } from 'react-router-dom'
import { courseDetails } from '../data/staticCatalog'

type LayoutCtx = { active:'courses'|'enrolled'|'finished'|'profile'; setActive:(k:LayoutCtx['active'])=>void }

const PROGRAM_ASSETS: Record<string, { cert:string; badge:string }> = {
  'Business Management': {
    cert: '/CertificateBusiness.jpg',
    badge: '/BUSINESSBADGE.png',
  },
  'Community Pharmacy': {
    cert: '/CertificatePharmacyBlank.jpg',
    badge: '/PHARMACYBADGE.png',
  },
  'Hospitality Management': {
    cert: '/CertificateTourismBlank.jpg',
    badge: '/TOURISMBADGE.png',
  },
  // If you also show the new concierge track, map it to the Tourism template:
  'Healthcare Hospitality & Tourism Concierge': {
    cert: '/CertificateTourismBlank.jpg',
    badge: '/TOURISMBADGE.png',
  },
}

export default function CertificatePage() {
  const { courseId } = useParams()
  const ctx = useOutletContext<LayoutCtx | undefined>()
  React.useEffect(() => { ctx?.setActive('finished') }, [ctx])

  const id = Number(courseId)
  const c = courseDetails[id]
  const canvasRef = React.useRef<HTMLCanvasElement>(null)

  React.useEffect(() => {
    if (!c || !canvasRef.current) return

    const assets = PROGRAM_ASSETS[c.program] ?? PROGRAM_ASSETS['Business Management']
    const canvas = canvasRef.current
    const g = canvas.getContext('2d')!

    // Target output size matches provided backgrounds (landscape A4-ish)
    canvas.width = 1400
    canvas.height = 1080

    const bg = new Image()
    const badge = new Image()
    let loaded = 0
    const done = () => {
      if (++loaded < 2) return

      // draw certificate background
      g.drawImage(bg, 0, 0, canvas.width, canvas.height)

      // draw badge (bottom-left)
      const badgeW = 220
      const badgeH = 320
      g.drawImage(badge, 90, canvas.height - badgeH - 120, badgeW, badgeH)

      // text styling
      g.fillStyle = '#0b3b2e'
      g.textAlign = 'left'

      // student (static prototype)
      g.font = '700 48px system-ui, -apple-system, Segoe UI, Roboto'
      g.fillText('Randy Lagdaan', 420, 560)

      // course title
      g.font = '700 42px system-ui, -apple-system, Segoe UI, Roboto'
      wrapText(g, c.title, 420, 620, 860, 48)

      // program + duration
      g.font = '500 26px system-ui, -apple-system, Segoe UI, Roboto'
      g.fillText(`Program: ${c.program}`, 420, 700)
      g.fillText(`Duration: ${c.duration}`, 420, 740)

      // approved date (today for prototype)
      const approved = new Date().toLocaleDateString(undefined, { year:'numeric', month:'long', day:'numeric' })
      g.fillText(`Approved on: ${approved}`, 420, 780)
    }

    bg.onload = done
    badge.onload = done
    bg.src = assets.cert
    badge.src = assets.badge

    function wrapText(ctx:CanvasRenderingContext2D, text:string, x:number, y:number, maxWidth:number, lineHeight:number) {
      const words = text.split(' ')
      let line = ''
      for (let n = 0; n < words.length; n++) {
        const testLine = line + words[n] + ' '
        const metrics = ctx.measureText(testLine)
        if (metrics.width > maxWidth && n > 0) {
          ctx.fillText(line.trim(), x, y)
          line = words[n] + ' '
          y += lineHeight
        } else {
          line = testLine
        }
      }
      ctx.fillText(line.trim(), x, y)
    }
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
