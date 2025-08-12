import * as React from 'react'
import { useEffect } from 'react'
import { useOutletContext } from 'react-router-dom'

type LayoutCtx = { active: 'courses' | 'enrolled' | 'finished' | 'profile'; setActive: (k: LayoutCtx['active']) => void }

export default function ProfilePage() {
  const { setActive } = useOutletContext<LayoutCtx>()
  useEffect(() => setActive('profile'), [setActive])

  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      <header>
        <h2 className="text-lg font-semibold text-green-900">Student Profile</h2>
        <p className="text-sm text-green-700/80">Manage your basic info (static)</p>
      </header>

      <div className="mt-4 grid grid-cols-1 gap-6 md:grid-cols-3">
        <div className="flex items-center gap-4 rounded-2xl border border-green-100 bg-white p-4 shadow-sm">
          <img
            src="https://images.unsplash.com/photo-1502685104226-ee32379fefbe?q=80&w=400&auto=format&fit=crop"
            className="h-16 w-16 rounded-full object-cover"
            alt=""
          />
          <div>
            <div className="font-semibold text-green-900">Randy Lagdaan</div>
            <div className="text-sm text-green-700/80">randy@example.com</div>
          </div>
        </div>

        <div className="rounded-2xl border border-green-100 bg-white p-4 shadow-sm md:col-span-2">
          <div className="grid gap-3 sm:grid-cols-2">
            <Field label="First name" value="Randy" />
            <Field label="Last name" value="Lagdaan" />
            <Field label="Mobile" value="(+63) 900 000 0000" />
            <Field label="Organization" value="Trinity University of Asia" />
          </div>
          <div className="mt-4 flex justify-end">
            <button className="rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700">
              Save (static)
            </button>
          </div>
        </div>
      </div>
    </section>
  )
}

function Field({ label, value }: { label: string; value: string }) {
  return (
    <label className="block">
      <div className="text-xs font-medium text-green-800">{label}</div>
      <input
        defaultValue={value}
        className="mt-1 w-full rounded-xl border border-green-200 bg-white px-3 py-2 text-green-900 placeholder:text-green-700/60 focus:outline-none focus:ring-2 focus:ring-yellow-300"
      />
    </label>
  )
}
