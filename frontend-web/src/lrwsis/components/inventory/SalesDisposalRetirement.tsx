import * as React from 'react'
import { useEffect, useState } from 'react'
import napi from '@/utils/axiosnapi'

type Row = { id:number; type:'SALE'|'DISPOSAL'|'RETIREMENT'; doc_no?:string; date_txn:string; remarks?:string; asset_number:string; description:string; status:string }

export default function SalesDisposalRetirement(){
  const [rows,setRows]=useState<Row[]>([]); const [per,setPer]=useState(20); const [page,setPage]=useState(1)
  const [form,setForm]=useState<any>({ type:'DISPOSAL', date_txn:new Date().toISOString().slice(0,10) })

  async function load(){ const r=await napi.get('/inv/disposal-retirement',{params:{per,page}}); setRows(r.data.data ?? r.data) }
  useEffect(()=>{ load() },[per,page])

  async function save(){
    if(!form.asset_id || !form.type || !form.date_txn) return alert('Asset, Type, Date required.')
    const r=await napi.post('/inv/disposal-retirement', form)
    setForm({ type:'DISPOSAL', date_txn:new Date().toISOString().slice(0,10) })
    load()
  }
  async function finalize(id:number){ await napi.post(`/inv/disposal-retirement/${id}/finalize`); load() }

  return (
    <div className="p-4 space-y-4">
      <div className="bg-yellow-300 rounded border p-2 font-bold">Sales / Disposal / Retirement</div>

      {/* quick create */}
      <div className="bg-yellow-50 border rounded p-3">
        <div className="grid grid-cols-5 gap-2">
          <input className="border px-2 py-1 rounded" placeholder="Asset ID" value={form.asset_id||''} onChange={e=>setForm((f:any)=>({...f,asset_id:+e.target.value}))}/>
          <select className="border px-2 py-1 rounded" value={form.type} onChange={e=>setForm((f:any)=>({...f,type:e.target.value}))}>
            <option value="SALE">SALE</option><option value="DISPOSAL">DISPOSAL</option><option value="RETIREMENT">RETIREMENT</option>
          </select>
          <input className="border px-2 py-1 rounded" placeholder="Doc No" value={form.doc_no||''} onChange={e=>setForm((f:any)=>({...f,doc_no:e.target.value}))}/>
          <input className="border px-2 py-1 rounded" placeholder="Date (YYYY-MM-DD)" value={form.date_txn} onChange={e=>setForm((f:any)=>({...f,date_txn:e.target.value}))}/>
          <input className="border px-2 py-1 rounded col-span-5" placeholder="Remarks" value={form.remarks||''} onChange={e=>setForm((f:any)=>({...f,remarks:e.target.value}))}/>
        </div>
        <div className="mt-2"><button className="px-4 py-1 rounded bg-green-700 text-white" onClick={save}>Save</button></div>
      </div>

      {/* grid */}
      <div className="border rounded overflow-auto">
        <table className="min-w-full text-sm">
          <thead className="bg-green-800 text-white"><tr><th className="p-2">Finalize</th><th className="p-2">Type</th><th className="p-2">Doc No</th><th className="p-2">Date</th><th className="p-2">Asset #</th><th className="p-2">Desc</th><th className="p-2">Asset Status</th></tr></thead>
          <tbody>
            {rows.map(r=>(
              <tr key={r.id} className="odd:bg-white even:bg-green-50">
                <td className="p-2"><button className="px-3 py-1 rounded bg-yellow-300" onClick={()=>finalize(r.id)}>Finalize</button></td>
                <td className="p-2">{r.type}</td>
                <td className="p-2">{r.doc_no||''}</td>
                <td className="p-2">{r.date_txn}</td>
                <td className="p-2">{r.asset_number}</td>
                <td className="p-2">{r.description}</td>
                <td className="p-2">{r.status}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
