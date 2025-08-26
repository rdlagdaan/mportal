// src/hr/LeaveRequests.tsx
import * as React from 'react'
import { useEffect, useMemo, useState } from 'react'
import napi from '@/utils/axiosnapi'

type LeaveRow = {
  id: number
  employee_id: number
  employee_name: string
  leave_type_id: number
  leave_type_name: string
  start_date: string // YYYY-MM-DD
  end_date: string   // YYYY-MM-DD
  part_day?: 'FULL' | 'AM' | 'PM'
  status: 'DRAFT' | 'SUBMITTED' | 'APPROVED' | 'REJECTED' | 'CANCELLED'
}

type LeaveOne = {
  id: number
  employee_id: number
  employee_name: string
  leave_type_id: number
  leave_type_name: string
  start_date: string
  end_date: string
  part_day?: 'FULL' | 'AM' | 'PM'
  reason_text?: string
  requires_med_cert?: boolean
  prior_notice_days?: number
  submitted_at?: string | null
  final_decision_at?: string | null
  created_at?: string
  updated_at?: string
  status: LeaveRow['status']
}

type LeaveType = { id: number; name: string }

export default function LeaveRequestsPanel() {
  const [q, setQ] = useState('')
  const [per, setPer] = useState(10)
  const [page, setPage] = useState(1)
  const [rows, setRows] = useState<LeaveRow[]>([])
  const [selected, setSelected] = useState<LeaveOne | null>(null)

  const [openForm, setOpenForm] = useState(false)
  const [openDetails, setOpenDetails] = useState<LeaveOne | null>(null)

  async function loadList() {
    const r = await napi.get('/leave-requests', { params: { q, per, page } })
    setRows(r.data.data ?? r.data) // supports pagination or plain list
  }
  useEffect(() => { loadList() }, [q, per, page])

  async function openDetailsById(id: number) {
    const r = await napi.get(`/leave-requests/${id}`)
    setSelected(r.data)
    setOpenDetails(r.data)
  }

  return (
    <div className="p-4 space-y-4">
      {/* Search + New */}
      <div className="flex gap-2 items-center">
        <input
          className="border px-2 py-1 rounded"
          placeholder="Search (employee, leave type, status)"
          value={q}
          onChange={e => setQ(e.target.value)}
        />
        <select
          className="border px-2 py-1 rounded"
          value={per}
          onChange={e => setPer(+e.target.value)}
        >
          <option>10</option><option>20</option><option>50</option>
        </select>
        <button
          className="px-3 py-1 rounded bg-green-700 text-white"
          onClick={() => setOpenForm(true)}
        >
          File Leave
        </button>
        <div className="ml-auto flex items-center gap-2 text-sm text-gray-500">
          <span>Page</span>
          <input
            type="number"
            min={1}
            className="w-16 border rounded px-2 py-1"
            value={page}
            onChange={e => setPage(Math.max(1, +e.target.value || 1))}
          />
        </div>
      </div>

      {/* List */}
      <div className="border rounded overflow-auto">
        <table className="min-w-full text-sm">
          <thead className="bg-green-800 text-white">
            <tr>
              <th className="p-2 text-left">Employee</th>
              <th className="p-2 text-left">Leave Type</th>
              <th className="p-2 text-left">Start</th>
              <th className="p-2 text-left">End</th>
              <th className="p-2 text-left">Part</th>
              <th className="p-2 text-left">Status</th>
              <th className="p-2 text-left">Details</th>
            </tr>
          </thead>
          <tbody>
            {rows.map(r => (
              <tr key={r.id} className="odd:bg-white even:bg-green-50">
                <td className="p-2">{r.employee_name}</td>
                <td className="p-2">{r.leave_type_name}</td>
                <td className="p-2">{r.start_date}</td>
                <td className="p-2">{r.end_date}</td>
                <td className="p-2">
                  {r.part_day === 'AM' ? 'AM Half' : r.part_day === 'PM' ? 'PM Half' : 'Full Day'}
                </td>
                <td className="p-2">
                  <StatusBadge status={r.status} />
                </td>
                <td className="p-2">
                  <button
                    className="text-green-700 underline"
                    onClick={() => openDetailsById(r.id)}
                  >
                    View
                  </button>
                </td>
              </tr>
            ))}
            {rows.length === 0 && (
              <tr><td colSpan={7} className="p-4 text-center text-gray-500">No leave requests found.</td></tr>
            )}
          </tbody>
        </table>
      </div>

      {/* Modals */}
      {openForm && (
        <LeaveFormModal
          onClose={() => setOpenForm(false)}
          onSaved={async (id) => {
            setOpenForm(false)
            await loadList()
            if (id) openDetailsById(id)
          }}
        />
      )}

      {openDetails && (
        <LeaveDetailsModal
          data={openDetails}
          onClose={() => setOpenDetails(null)}
        />
      )}
    </div>
  )
}

/* ---------- Components ---------- */

function StatusBadge({ status }: { status: LeaveRow['status'] }) {
  const map: Record<LeaveRow['status'], string> = {
    DRAFT: 'bg-gray-200 text-gray-800',
    SUBMITTED: 'bg-yellow-100 text-yellow-800',
    APPROVED: 'bg-green-100 text-green-800',
    REJECTED: 'bg-red-100 text-red-700',
    CANCELLED: 'bg-gray-100 text-gray-700',
  }
  return <span className={`px-2 py-0.5 rounded text-xs font-medium ${map[status]}`}>{status}</span>
}

function LeaveFormModal({
  onClose,
  onSaved,
}: {
  onClose: () => void
  onSaved: (id?: number) => void
}) {
  const [types, setTypes] = useState<LeaveType[]>([])
  const [form, setForm] = useState<any>({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    part_day: 'FULL',
    reason_text: '',
  })
  const valid = useMemo(() => {
    if (!form.leave_type_id) return false
    if (!form.start_date || !form.end_date) return false
    return true
  }, [form])

  useEffect(() => {
    (async () => {
      const r = await napi.get('/leave-types')
      setTypes(r.data.data ?? r.data)
    })()
  }, [])

  async function create(submit: boolean) {
    const payload = {
      leave_type_id: +form.leave_type_id,
      start_date: form.start_date, // YYYY-MM-DD
      end_date: form.end_date,
      part_day: form.part_day,     // 'FULL' | 'AM' | 'PM'
      reason_text: form.reason_text?.trim() || null,
      submit,                      // backend: if true → set status SUBMITTED + submitted_at
    }
    const r = await napi.post('/leave-requests', payload)
    onSaved(r.data?.id)
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
      <div className="w-[700px] bg-white rounded-lg shadow-xl overflow-hidden">
        <div className="bg-green-800 text-white px-4 py-2 text-lg">Leave Application</div>
        <div className="p-4 space-y-3">
          <div className="grid grid-cols-2 gap-3">
            <div className="col-span-2">
              <label className="block text-sm mb-1">Leave Type</label>
              <select
                className="w-full border rounded px-2 py-1"
                value={form.leave_type_id}
                onChange={e => setForm((f: any) => ({ ...f, leave_type_id: e.target.value }))}
              >
                <option value="">-- Select --</option>
                {types.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
              </select>
            </div>

            <div>
              <label className="block text-sm mb-1">Start Date</label>
              <input
                type="date"
                className="w-full border rounded px-2 py-1"
                value={form.start_date}
                onChange={e => setForm((f: any) => ({ ...f, start_date: e.target.value }))}
              />
            </div>
            <div>
              <label className="block text-sm mb-1">End Date</label>
              <input
                type="date"
                className="w-full border rounded px-2 py-1"
                value={form.end_date}
                onChange={e => setForm((f: any) => ({ ...f, end_date: e.target.value }))}
              />
            </div>

            <div className="col-span-2">
              <label className="block text-sm mb-1">Part Day</label>
              <select
                className="w-full border rounded px-2 py-1"
                value={form.part_day}
                onChange={e => setForm((f: any) => ({ ...f, part_day: e.target.value }))}
              >
                <option value="FULL">Full Day</option>
                <option value="AM">Half Day (AM)</option>
                <option value="PM">Half Day (PM)</option>
              </select>
            </div>

            <div className="col-span-2">
              <label className="block text-sm mb-1">Reason</label>
              <textarea
                className="w-full border rounded px-2 py-2"
                rows={4}
                placeholder="Reason for leave"
                value={form.reason_text}
                onChange={e => setForm((f: any) => ({ ...f, reason_text: e.target.value }))}
              />
            </div>
          </div>
        </div>

        <div className="p-4 flex gap-2 bg-green-50">
          <button
            className="px-3 py-1 rounded bg-gray-200"
            onClick={onClose}
          >
            Cancel
          </button>
          <button
            disabled={!valid}
            className={`px-3 py-1 rounded ${valid ? 'bg-green-700 text-white' : 'bg-green-200 text-white/60'}`}
            onClick={() => create(false)}
          >
            Save Draft
          </button>
          <button
            disabled={!valid}
            className={`px-3 py-1 rounded ${valid ? 'bg-yellow-600 text-white' : 'bg-yellow-200 text-white/60'}`}
            onClick={() => create(true)}
          >
            Submit for Approval
          </button>
        </div>
      </div>
    </div>
  )
}

function LeaveDetailsModal({ data, onClose }: { data: LeaveOne, onClose: () => void }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
      <div className="w-[720px] bg-white rounded-lg shadow-xl overflow-hidden">
        <div className="flex items-center justify-between bg-green-800 text-white px-4 py-2">
          <div className="text-lg">Leave Details</div>
          <StatusBadge status={data.status} />
        </div>

        <div className="p-4 grid grid-cols-2 gap-3">
          <Field label="Employee" value={data.employee_name} />
          <Field label="Leave Type" value={data.leave_type_name} />
          <Field label="Start Date" value={data.start_date} />
          <Field label="End Date" value={data.end_date} />
          <Field label="Part Day" value={data.part_day === 'AM' ? 'Half (AM)' : data.part_day === 'PM' ? 'Half (PM)' : 'Full Day'} />
          <Field label="Requires Med Cert" value={data.requires_med_cert ? 'Yes' : 'No'} />
          <Field label="Prior Notice (days)" value={data.prior_notice_days ?? '-'} />
          <Field label="Submitted At" value={data.submitted_at ?? '-'} />
          <Field label="Final Decision At" value={data.final_decision_at ?? '-'} />
          <div className="col-span-2">
            <div className="text-sm text-gray-600 mb-1">Reason</div>
            <div className="border rounded p-2 min-h-[80px] bg-green-50">
              {data.reason_text || <span className="opacity-50">—</span>}
            </div>
          </div>
        </div>

        <div className="p-4 bg-green-50">
          <button className="px-3 py-1 rounded bg-gray-200" onClick={onClose}>Close</button>
        </div>
      </div>
    </div>
  )
}

function Field({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div>
      <div className="text-sm text-gray-600 mb-1">{label}</div>
      <div className="border rounded px-2 py-1 bg-white">{value}</div>
    </div>
  )
}
