import React, { useState } from 'react'
import napi from '@/utils/axiosnapi'

export default function LeaveApplicationPanel() {
  const [form, setForm] = useState<any>({
    employee_id: (window as any).currentEmployeeId,
    school_year_id: (window as any).currentSchoolYearId,
    leave_type_id: '',
    start_date: '',
    end_date: '',
    part_day: '',
    reason_text: ''
  })
  const [draft, setDraft] = useState<any>(null)

  const saveDraft = async (e: React.FormEvent) => {
    e.preventDefault()
    const r = await napi.post('/hrsi/leave/requests', form)
    setDraft(r.data)
  }

  const submit = async () => {
    if (!draft?.id) return
    await napi.post(`/hrsi/leave/requests/${draft.id}/submit`)
    alert('Submitted for approval.')
  }

  return (
    <div className="space-y-4">
      <div className="text-lg font-semibold">Leave Application</div>

      <form onSubmit={saveDraft} className="grid gap-3 max-w-xl">
        <label className="text-sm">
          <span className="block mb-1">Leave Type ID</span>
          <input className="border p-2 w-full" value={form.leave_type_id}
                 onChange={e=>setForm({...form, leave_type_id: e.target.value})}/>
        </label>

        <div className="grid grid-cols-2 gap-3">
          <label className="text-sm">
            <span className="block mb-1">Start Date</span>
            <input type="date" className="border p-2 w-full" value={form.start_date}
                   onChange={e=>setForm({...form, start_date: e.target.value})}/>
          </label>
          <label className="text-sm">
            <span className="block mb-1">End Date</span>
            <input type="date" className="border p-2 w-full" value={form.end_date}
                   onChange={e=>setForm({...form, end_date: e.target.value})}/>
          </label>
        </div>

        <label className="text-sm">
          <span className="block mb-1">Part Day</span>
          <select className="border p-2 w-full" value={form.part_day}
                  onChange={e=>setForm({...form, part_day: e.target.value})}>
            <option value="">Full Day</option>
            <option value="AM">AM</option>
            <option value="PM">PM</option>
          </select>
        </label>

        <label className="text-sm">
          <span className="block mb-1">Reason</span>
          <textarea className="border p-2 w-full" rows={3}
                    onChange={e=>setForm({...form, reason_text: e.target.value})}/>
        </label>

        <div className="flex gap-2">
          <button className="px-3 py-2 border rounded" type="submit">Save Draft</button>
          <button className="px-3 py-2 border rounded" type="button" onClick={submit} disabled={!draft?.id}>
            Submit for Approval
          </button>
        </div>
      </form>

      {draft && <pre className="text-xs bg-gray-50 p-2 rounded max-w-xl overflow-auto">{JSON.stringify(draft,null,2)}</pre>}
    </div>
  )
}
