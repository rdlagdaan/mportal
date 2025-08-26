// src/lrwsis/pages/LrwsisDashboard.tsx
import * as React from 'react'
import { useEffect, useMemo, useState, lazy, Suspense } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import napi from '@/utils/axiosnapi'
import { ChevronDownIcon, ChevronRightIcon, ClipboardDocumentIcon } from '@heroicons/react/24/outline'

import NotificationsBell from '@/components/NotificationsBell'

type SubModule = { sub_module_id: number; sub_module_name: string; component_path: string | null }
type Module = { module_id: number; module_name: string; sub_modules: SubModule[] }
type System = { system_id: number; system_name: string; modules: Module[] }

// Auto-register all LRWSIS components under this folder (recursive)
const componentRegistry = import.meta.glob('@/lrwsis/components/**/*.tsx', { eager: false })

function resolveComponent(path: string | null) {
  if (!path) return null
  // match .../settings/Role.tsx when component_path === 'settings/Role'
  const suffixes = [
    `/components/${path}.tsx`,
    `/components/${path}/index.tsx`,
  ]
  const key = Object.keys(componentRegistry).find(k => suffixes.some(s => k.endsWith(s)))
  return key ? (lazy(componentRegistry[key] as any) as React.LazyExoticComponent<React.ComponentType<any>>) : null
}

export default function LrwsisDashboard() {
  const navigate = useNavigate()
  const [searchParams, setSearchParams] = useSearchParams()

  const [sidebarOpen, setSidebarOpen] = useState(true)
  const [systems, setSystems] = useState<System[]>([])
  const [openSystems, setOpenSystems] = useState<number[]>([])
  const [openModules, setOpenModules] = useState<number[]>([])
  const [selectedSub, setSelectedSub] = useState<SubModule | null>(null)
  const [searchQuery, setSearchQuery] = useState('')
  const [username, setUsername] = useState<string>('')

  const [me, setMe] = React.useState<{ name?: string; employee_id?: number } | null>(null)
  
   React.useEffect(() => {
    // You already call /lrwsis/me in the layout; this is just to fetch name/employeeId for the header
    napi.get('/lrwsis/me')
      .then(r => setMe({
        name: r.data?.user?.name,
        employee_id: r.data?.user?.employee_id ?? (window as any).currentEmployeeId
      }))
      .catch(()=>{})
  }, []) 
  
  
  // ---- Auth guard + user ----
  useEffect(() => {
    let mounted = true
    ;(async () => {
      try {
        const me = await napi.get('/lrwsis/me')
        if (mounted) {
          const name = me?.data?.user?.name || me?.data?.user?.email || 'User'
          setUsername(name)
        }
      } catch {
        navigate('/login', { replace: true })
      }
    })()
    return () => { mounted = false }
  }, [navigate])





  // ---- Fetch menu (3-level) ----
  useEffect(() => {
    let mounted = true
    ;(async () => {
      try {
        const res = await napi.get<System[]>('/user/modules') // -> /app/api/user/modules
        if (!mounted) return
        setSystems(res.data || [])

        // Deep-link restore (?sub=settings/Role)
        const deepPath = searchParams.get('sub')
        if (deepPath) {
          // expand parents if we find a match
          res.data?.forEach(sys => {
            sys.modules.forEach(mod => {
              mod.sub_modules.forEach(sm => {
                if (sm.component_path === deepPath) {
                  setOpenSystems(os => os.includes(sys.system_id) ? os : [...os, sys.system_id])
                  setOpenModules(om => om.includes(mod.module_id) ? om : [...om, mod.module_id])
                  setSelectedSub(sm)
                }
              })
            })
          })
        }
      } catch (err) {
        console.error('Failed to fetch LRWSIS modules', err)
      }
    })()
    return () => { mounted = false }
  }, []) // eslint-disable-line

  // ---- Logout ----
  const handleLogout = async () => {
    try { await napi.post('/lrwsis/logout') } catch {}
    localStorage.removeItem('user')
    navigate('/login', { replace: true })
  }

  const toggleSystem = (id: number) =>
    setOpenSystems(prev => prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id])

  const toggleModule = (id: number) =>
    setOpenModules(prev => prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id])

  // ---- Search (module/sub-module text match) ----
  const filteredSystems = useMemo<System[]>(() => {
    const q = searchQuery.trim().toLowerCase()
    if (!q) return systems

    return systems.map(sys => {
      const filteredModules = sys.modules.map(m => {
        const mHit = m.module_name.toLowerCase().includes(q)
        const subs = m.sub_modules.filter(s => s.sub_module_name.toLowerCase().includes(q))
        return (mHit || subs.length) ? { ...m, sub_modules: mHit ? m.sub_modules : subs } : null
      }).filter(Boolean) as Module[]

      return { ...sys, modules: filteredModules }
    }).filter(s => s.modules.length)
  }, [systems, searchQuery])

  // ---- Dynamic component from selected sub-module ----
  const SelectedComponent = useMemo(() => resolveComponent(selectedSub?.component_path ?? null), [selectedSub?.component_path])

  // ---- Handlers ----
  const onClickSub = (sysId: number, modId: number, sm: SubModule) => {
    setSelectedSub(sm)
    // expand parents
    setOpenSystems(prev => prev.includes(sysId) ? prev : [...prev, sysId])
    setOpenModules(prev => prev.includes(modId) ? prev : [...prev, modId])
    // sync URL (?sub=...)
    if (sm.component_path) setSearchParams(prev => { prev.set('sub', sm.component_path!); return prev }, { replace: true })
  }

  return (
    <div className="flex w-full h-screen bg-white text-gray-900">
      {/* Sidebar */}
      <aside className={`transition-all duration-300 border-r shadow-md h-full flex flex-col ${sidebarOpen ? 'w-72' : 'w-0'}`}>
        <div className="p-4 border-b bg-gradient-to-r from-green-700 to-yellow-400 text-white flex items-center justify-between">
          <div className="flex items-center gap-3">
            <img src="/android-icon-144x144.png" alt="TUA" className="h-8 w-8 rounded" />
            <span className="font-extrabold tracking-wide">LRWSIS</span>
          </div>
          <button
            type="button"
            onClick={() => setSidebarOpen(false)}
            title="Collapse"
            aria-label="Collapse sidebar"
            className="group inline-flex h-12 w-12 items-center justify-center rounded-full
                       bg-green-700 text-white
                       ring-2 ring-white/90 ring-offset-2 ring-offset-yellow-400
                       shadow-[0_2px_8px_rgba(0,0,0,0.25)]
                       transition-colors hover:bg-white hover:text-green-700 focus:outline-none focus:ring-4 focus:ring-white/90">
            <span aria-hidden className="flex flex-col items-center justify-center gap-[4px]">
              <span className="block h-[3px] w-7 rounded bg-current"></span>
              <span className="block h-[3px] w-7 rounded bg-current"></span>
              <span className="block h-[3px] w-7 rounded bg-current"></span>
            </span>
          </button>
        </div>

        {/* Search */}
        <div className="px-3 py-2 bg-green-50">
          <input
            type="text"
            placeholder="Search modules or submodules…"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full border border-green-200 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
          />
        </div>

        {/* Navigation */}
        <nav className="p-3 overflow-y-auto bg-white">
          {filteredSystems.map(system => (
            <div key={system.system_id} className="mb-2">
              {/* System */}
              <button
                onClick={() => toggleSystem(system.system_id)}
                className="w-full flex items-center gap-2 text-left font-semibold px-2 py-2 rounded-md text-green-900 hover:bg-green-50">
                {openSystems.includes(system.system_id) ? <ChevronDownIcon className="h-4 w-4" /> : <ChevronRightIcon className="h-4 w-4" />}
                {system.system_name}
              </button>

              {/* Modules */}
              {openSystems.includes(system.system_id) && (
                <div className="ml-4 mt-1">
                  {system.modules.map(module => (
                    <div key={module.module_id} className="mb-1">
                      <button
                        onClick={() => toggleModule(module.module_id)}
                        className="w-full flex items-center gap-2 text-left text-sm px-2 py-1 rounded-md text-emerald-900 hover:bg-emerald-50">
                        {openModules.includes(module.module_id) ? <ChevronDownIcon className="h-3 w-3" /> : <ChevronRightIcon className="h-3 w-3" />}
                        {module.module_name}
                      </button>

                      {/* Submodules */}
                      {openModules.includes(module.module_id) && (
                        <ul className="ml-6 mt-1 space-y-1">
                          {module.sub_modules.map(sub => {
                            const isActive = selectedSub?.sub_module_id === sub.sub_module_id
                            return (
                              <li key={sub.sub_module_id}>
                                <button
                                  onClick={() => onClickSub(system.system_id, module.module_id, sub)}
                                  className={`w-full text-left px-3 py-1 rounded-md text-sm flex items-center gap-2
                                    ${isActive ? 'bg-yellow-100 text-green-900' : 'text-gray-700 hover:bg-yellow-50 hover:text-green-900'}`}>
                                  <ClipboardDocumentIcon className="h-4 w-4" />
                                  {sub.sub_module_name}
                                </button>
                              </li>
                            )
                          })}
                        </ul>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>
          ))}
        </nav>
      </aside>

      {/* Main */}
      <main className="flex-1 flex flex-col">
        {/* Header */}
        <header className="w-full bg-gradient-to-r from-green-700 to-green-600 text-white px-6 py-3 flex items-center justify-between shadow">
          <div className="flex items-center gap-3">
            {!sidebarOpen && (
              <button
                type="button"
                onClick={() => setSidebarOpen(true)}
                title="Expand"
                aria-label="Expand sidebar"
                className="inline-flex h-10 w-10 items-center justify-center rounded-lg
                          bg-white text-green-700 ring-2 ring-green-700/80
                          shadow-[0_2px_6px_rgba(0,0,0,0.2)]
                          transition-colors hover:bg-green-700 hover:text-white
                          focus:outline-none focus:ring-4 focus:ring-green-700/70">
                <span aria-hidden className="flex flex-col items-center justify-center gap-[3px]">
                  <span className="block h-[3px] w-6 rounded bg-current"></span>
                  <span className="block h-[3px] w-6 rounded bg-current"></span>
                  <span className="block h-[3px] w-6 rounded bg-current"></span>
                </span>
              </button>
            )}
            <h1 className="text-lg font-bold">
              {selectedSub?.sub_module_name ?? 'Select a sub-module'}
            </h1>
          </div>

<div className="relative flex items-center gap-3 select-none">
  <span className="text-sm">Welcome, {username}</span>

  {/* 🔔 Notification bell stays independent */}
  <NotificationsBell employeeId={Number(me?.employee_id ?? 0)} />

  {/* 👤 Avatar + dropdown wrapper */}
  <div className="relative group">
    <img
      src="/app/tua-logo.png"
      alt="User"
      className="w-8 h-8 rounded-full border-2 border-white object-cover cursor-pointer"
    />
    <div className="absolute right-0 mt-2 w-36 bg-white text-green-800 rounded shadow-md opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
      <button
        onClick={handleLogout}
        className="w-full text-left px-4 py-2 text-sm hover:bg-green-50"
      >
        Logout
      </button>
    </div>
  </div>
</div>

        </header>

        {/* Body */}
        <section className="flex-1 p-6 bg-white">
          <div className="rounded-xl border border-green-100 shadow-sm min-h-[420px]">
            <Suspense fallback={<div className="p-6 text-gray-500">Loading module…</div>}>
              {SelectedComponent ? (
                <SelectedComponent />
              ) : (
                <div className="p-6 text-gray-700">Select a sub-module from the left to begin.</div>
              )}
            </Suspense>
          </div>
        </section>

        <footer className="h-1 bg-gradient-to-r from-green-700 via-yellow-400 to-green-700" />
      </main>
    </div>
  )
}
