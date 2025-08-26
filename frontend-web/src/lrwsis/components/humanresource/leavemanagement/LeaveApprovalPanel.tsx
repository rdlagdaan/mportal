import React, { useEffect, useState } from 'react'
import napi from '@/utils/axiosnapi'

export default function LeaveApprovalPanel() {
  const [rows, setRows] = useState<any[]>([])
  const [remarks, setRemarks] = useState('')

  const load = async () => {
    const r = await napi.get('/hrsi/leave/approvals/inbox')
    setRows(r.data?.data || [])
  }

  useEffect(() => { load() }, [])

  const approve = async (stepId:number) => {
    await napi.post(`/hrsi/leave/approvals/steps/${stepId}/approve`, { remarks })
    setRemarks(''); load()
  }
  const reject = async (stepId:number) => {
    await napi.post(`/hrsi/leave/approvals/steps/${stepId}/reject`, { remarks })
    setRemarks(''); load()
  }

  return (
    <div className="space-y-4">
      <div className="text-lg font-semibold">Leave Approval</div>

      <div className="space-y-3">
        {rows.map((s:any)=>(
          <div key={s.id} className="border p-3 rounded">
            <div className="font-medium mb-1">Step #{s.id} — order {s.step_order}</div>
            <pre className="text-xs bg-gray-50 p-2 rounded">{JSON.stringify(s.request,null,2)}</pre>
            <div className="flex gap-2 mt-2">
              <input className="border p-1 text-sm flex-1" placeholder="Remarks" value={remarks}
                     onChange={e=>setRemarks(e.target.value)} />
              <button className="border px-2 py-1 rounded" onClick={()=>approve(s.id)}>Approve</button>
              <button className="border px-2 py-1 rounded" onClick={()=>reject(s.id)}>Reject</button>
            </div>
          </div>
        ))}
        {rows.length === 0 && <div className="text-sm opacity-70">No pending approvals.</div>}
      </div>
    </div>
  )
}
