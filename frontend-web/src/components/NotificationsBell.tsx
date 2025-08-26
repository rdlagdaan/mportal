import React, { useEffect, useRef, useState } from 'react'
import napi from '@/utils/axiosnapi'

export default function NotificationsBell({ employeeId }: { employeeId: number }) {
  const [items, setItems] = useState<any[]>([])
  const [open, setOpen] = useState(false)
  const popRef = useRef<HTMLDivElement | null>(null)

  const load = async () => {
    const r = await napi.get('/notifications')
    setItems(r.data || [])
  }

  useEffect(() => {
    load()
    const anyWindow = window as any
    if (anyWindow.Echo && employeeId) {
      const Echo = anyWindow.Echo
      Echo.private(`employee.${employeeId}`).listen('.leave.status', load).listen('.leave.balance', load)
      Echo.private(`approver.${employeeId}`).listen('.leave.submitted', load)
    }
  }, [employeeId])

  // Optional: close on outside click / Esc
  useEffect(() => {
    if (!open) return
    const onClick = (e: MouseEvent) => {
      if (popRef.current && !popRef.current.contains(e.target as Node)) setOpen(false)
    }
    const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') setOpen(false) }
    window.addEventListener('click', onClick)
    window.addEventListener('keydown', onKey)
    return () => { window.removeEventListener('click', onClick); window.removeEventListener('keydown', onKey) }
  }, [open])

  return (
    <div className="relative" ref={popRef}>
<button
  type="button"
  aria-label="Notifications"
  onClick={(e) => { e.stopPropagation(); setOpen(o => !o) }}
  // hard reset: no background in any state
  className="relative inline-flex items-center justify-center p-2 rounded-full
             bg-transparent hover:bg-transparent active:bg-transparent focus:bg-transparent
             ring-0 focus:ring-2 focus:ring-white/60
             text-white/90 hover:text-white transition"
  style={{ background: 'transparent' }}  // beats any non-Tailwind global rule
>
  <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
    <path d="M12 2a6 6 0 0 0-6 6v3.09c0 .53-.21 1.04-.59 1.41L4 14h16l-1.41-1.5a2 2 0 0 1-.59-1.41V8a6 6 0 0 0-6-6Zm0 20a3 3 0 0 0 3-3H9a3 3 0 0 0 3 3Z"/>
  </svg>

  {items.length > 0 && (
    <span className="absolute -top-1 -right-1 text-[10px] leading-none bg-red-600 text-white rounded-full px-1">
      {items.length}
    </span>
  )}
</button>


      {open && (
        <div className="absolute right-0 mt-2 w-96 bg-white text-gray-800 shadow-xl rounded-lg p-2 max-h-96 overflow-auto z-50">
          {items.length === 0 && <div className="p-3 text-sm opacity-60">No new notifications</div>}
          {items.map((n:any)=>(
            <div key={n.id} className="p-2 border-b text-sm">
              <div className="font-medium mb-1">{n.data.kind}</div>
              <div className="opacity-70 text-xs break-words">{JSON.stringify(n.data)}</div>
              <button className="text-xs underline mt-1"
                      onClick={async()=>{ await napi.post(`/notifications/${n.id}/read`); load() }}>
                Mark as read
              </button>
            </div>
          ))}
          {items.length > 0 && (
            <button className="mt-2 text-xs underline"
                    onClick={async()=>{ await napi.post('/notifications/read-all'); load() }}>
              Mark all as read
            </button>
          )}
        </div>
      )}
    </div>
  )
}
