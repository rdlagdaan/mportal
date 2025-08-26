import * as React from 'react'
import { useEffect, useState } from 'react'
import napi from '@/utils/axiosnapi'

type Hdr = { id:number; gatepass_no:string; purpose?:string; requested_by?:string; status:'OPEN'|'SUBMITTED'|'CLOSED'; created_at:string }
type Item = { id:number; asset_id:number|null; custody_id:number|null; description:string|null; qty:number; remarks:string|null; asset_number?:string; seq_id?:number|null }

export default function Gatepass(){
  const [q,setQ]=useState(''); const [per,setPer]=useState(20); const [page,setPage]=useState(1)
  const [rows,setRows]=useState<Hdr[]>([]); const [total,setTotal]=useState(0)
  const [sel,setSel]=useState<Hdr|null>(null); const [items,setItems]=useState<Item[]>([])
  const [form,setForm]=useState<Partial<Hdr>>({})

  async function load(){
    const r=await napi.get('/inv/gatepass',{params:{q,per,page}})
    setRows(r.data.data ?? r.data); setTotal(r.data.total ?? r.data.length ?? 0)
  }
  useEffect(()=>{ load() },[q,per,page])

  async function createHdr(){
    if(!form.gatepass_no) return alert('Gatepass # required')
    const r=await napi.post('/inv/gatepass', form)
    await load(); openHdr(r.data.id)
  }
  async function openHdr(id:number){
    const r=await napi.get(`/inv/gatepass/${id}`)
    setSel(r.data.header); setItems(r.data.items)
  }

  async function addItem(){
    if(!sel) return
    const r=await napi.post(`/inv/gatepass/${sel.id}/items`, { description: 'Misc item', qty:1 })
    openHdr(sel.id)
  }
  async function removeItem(id:number){ if(!sel) return; await napi.delete(`/inv/gatepass/items/${id}`); openHdr(sel.id) }

  async function changeStatus(next:'SUBMITTED'|'CLOSED'){
    if(!sel) return
    await napi.post(`/inv/gatepass/${sel.id}/${next.toLowerCase()}`)
    openHdr(sel.id); load()
  }

  return (
    <div className="p-4 space-y-4">
      <div className="bg-yellow-300 rounded border p-2 font-bold">Gatepass</div>

      <div className="flex gap-2">
        <input className="border rounded px-2 py-1 bg-yellow-100" placeholder="Search no/purpose/requester" value={q} onChange={e=>setQ(e.target.value)} />
        <select className="border rounded px-2" value={per} onChange={e=>setPer(+e.target.value)}><option>20</option><option>50</option></select>
        <button className="px-3 py-1 rounded bg-yellow-300" onClick={()=>setForm({gatepass_no:'',purpose:'',requested_by:''})}>New Gatepass</button>
      </div>

      {form.gatepass_no!==undefined && (
        <div className="bg-yellow-50 border rounded p-3">
          <div className="grid grid-cols-3 gap-2">
            <input className="border px-2 py-1 rounded" placeholder="Gatepass #" value={form.gatepass_no||''} onChange={e=>setForm(f=>({...f,gatepass_no:e.target.value}))}/>
            <input className="border px-2 py-1 rounded" placeholder="Purpose" value={form.purpose||''} onChange={e=>setForm(f=>({...f,purpose:e.target.value}))}/>
            <input className="border px-2 py-1 rounded" placeholder="Requested By" value={form.requested_by||''} onChange={e=>setForm(f=>({...f,requested_by:e.target.value}))}/>
          </div>
          <div className="mt-2 flex gap-2">
            <button className="px-4 py-1 rounded bg-green-700 text-white" onClick={createHdr}>Save</button>
            <button className="px-3 py-1 rounded bg-white border" onClick={()=>setForm({})}>Close</button>
          </div>
        </div>
      )}

      <div className="grid grid-cols-2 gap-4">
        {/* left list */}
        <div className="border rounded overflow-auto">
          <table className="min-w-full text-sm">
            <thead className="bg-green-800 text-white"><tr><th className="p-2">Gatepass #</th><th className="p-2">Status</th><th className="p-2">Purpose</th></tr></thead>
            <tbody>
              {rows.map(r=>(
                <tr key={r.id} className={`cursor-pointer ${sel?.id===r.id?'bg-yellow-100':'odd:bg-white even:bg-green-50'}`} onClick={()=>openHdr(r.id)}>
                  <td className="p-2">{r.gatepass_no}</td><td className="p-2">{r.status}</td><td className="p-2">{r.purpose}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* right panel */}
        <div className="border rounded p-3">
          {!sel ? <div className="text-gray-500">Select a gatepass.</div> : (
            <>
              <div className="font-semibold">#{sel.gatepass_no} — {sel.status}</div>
              <div className="mt-2 flex gap-2">
                <button className="px-3 py-1 rounded bg-yellow-300" onClick={addItem} disabled={sel.status!=='OPEN'}>Add Item</button>
                <button className="px-3 py-1 rounded bg-white border" onClick={()=>changeStatus('SUBMITTED')} disabled={sel.status!=='OPEN'}>Submit</button>
                <button className="px-3 py-1 rounded bg-green-700 text-white" onClick={()=>changeStatus('CLOSED')} disabled={sel.status!=='SUBMITTED'}>Close</button>
              </div>
              <div className="mt-3 overflow-auto border rounded">
                <table className="min-w-full text-sm">
                  <thead className="bg-green-800 text-white"><tr><th className="p-2">Actions</th><th className="p-2">Asset #</th><th className="p-2">Seq</th><th className="p-2">Description</th><th className="p-2">Qty</th></tr></thead>
                  <tbody>
                    {items.map(i=>(
                      <tr key={i.id} className="odd:bg-white even:bg-green-50">
                        <td className="p-2"><button className="text-red-700" onClick={()=>removeItem(i.id)} disabled={sel.status!=='OPEN'}>Delete</button></td>
                        <td className="p-2">{i.asset_number||''}</td>
                        <td className="p-2">{i.seq_id||''}</td>
                        <td className="p-2">{i.description||''}</td>
                        <td className="p-2">{i.qty}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  )
}
