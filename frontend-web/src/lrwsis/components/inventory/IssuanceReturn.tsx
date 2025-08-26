import * as React from 'react'
import { useEffect, useState } from 'react'
import napi from '@/utils/axiosnapi'

type Row = {
  custody_id: number
  asset_id: number
  asset_number: string
  seq_id: number
  description: string
  group_name: string
  purchased_date: string
  custodian: string | null
  issued_by: string | null
  status: 'IN_STOCK' | 'ASSIGNED'
  serial_no: string | null
  department: string | null
  location: string | null
}

export default function IssuanceReturn(){
  // filters band (green)
  const [f, setF] = useState({
    asset_number: '', description: '', group_name: '',
    department: '', location: '', custodian: ''
  })
  const [per,setPer]=useState(20)
  const [page,setPage]=useState(1)

  const [rows,setRows]=useState<Row[]>([])
  const [total,setTotal]=useState(0)

  async function load(){
    const res = await napi.get('/inv/issuance-return', { params: { ...f, per, page } })
    setRows(res.data.data ?? res.data)
    setTotal(res.data.total ?? res.data.length ?? 0)
  }
  useEffect(()=>{ load() }, [JSON.stringify(f), per, page]) // eslint-disable-line

  // modal state for Assign / Return / Assign SN
  const [modal, setModal] = useState<null | { kind:'assign'|'return'|'sn', assetId:number, assetNumber:string, description:string }>(null)
  const [byBatch, setByBatch] = useState(true)
  const [seqId, setSeqId] = useState<number|''>('')
  const [dept, setDept] = useState(''); const [loc,setLoc]=useState(''); const [cust,setCust]=useState(''); const [issuedBy,setIssuedBy]=useState('')
  const [startSN, setStartSN] = useState('')

  async function openAssign(r:Row){
    setByBatch(true); setSeqId(''); setDept(''); setLoc(''); setCust(''); setIssuedBy('')
    setModal({kind:'assign', assetId:r.asset_id, assetNumber:r.asset_number, description:r.description})
  }
  async function openReturn(r:Row){
    setByBatch(true); setSeqId(''); setModal({kind:'return', assetId:r.asset_id, assetNumber:r.asset_number, description:r.description})
  }
  async function openSN(r:Row){
    setStartSN(''); setModal({kind:'sn', assetId:r.asset_id, assetNumber:r.asset_number, description:r.description})
  }

  async function doSubmit(){
    if (!modal) return
    if (modal.kind==='assign'){
      const payload:any = { by_batch: byBatch, department: dept, location: loc, custodian: cust, issued_by: issuedBy }
      if (!byBatch) payload.seq_id = seqId
      await napi.post(`/inv/issuance/${modal.assetId}`, payload)
    } else if (modal.kind==='return'){
      const payload:any = { by_batch: byBatch }
      if (!byBatch) payload.seq_id = seqId
      await napi.post(`/inv/return/${modal.assetId}`, payload)
    } else {
      await napi.post(`/inv/serials/${modal.assetId}`, { start_serial: startSN })
    }
    setModal(null)
    load()
  }

  return (
    <div className="p-4 space-y-4">
      <div className="bg-yellow-300 rounded border p-2 font-bold">ASSET ISSUANCE</div>

      {/* Green filter band */}
      <div className="rounded border bg-green-800 p-3 text-white">
        <div className="grid grid-cols-6 gap-2 items-end">
          <Labeled label="Asset Number"><Input value={f.asset_number} onChange={v=>setF(x=>({...x,asset_number:v}))}/></Labeled>
          <Labeled label="Asset Desc"><Input value={f.description} onChange={v=>setF(x=>({...x,description:v}))}/></Labeled>
          <Labeled label="Group Asset Name"><Input value={f.group_name} onChange={v=>setF(x=>({...x,group_name:v}))}/></Labeled>
          <Labeled label="Department"><Input value={f.department} onChange={v=>setF(x=>({...x,department:v}))}/></Labeled>
          <Labeled label="Location"><Input value={f.location} onChange={v=>setF(x=>({...x,location:v}))}/></Labeled>
          <Labeled label="Custodian"><Input value={f.custodian} onChange={v=>setF(x=>({...x,custodian:v}))}/></Labeled>
        </div>
        <div className="mt-2 flex items-center justify-end gap-2">
          <button className="px-3 py-2 rounded bg-white text-green-800" onClick={()=>{setPage(1); load()}}>Search</button>
          <select className="rounded text-black px-2 py-1" value={per} onChange={e=>setPer(+e.target.value)}>
            <option>20</option><option>50</option><option>100</option>
          </select>
        </div>
      </div>

      {/* Grid */}
      <div className="overflow-auto border rounded">
        <table className="min-w-full text-sm">
          <thead className="bg-green-800 text-white">
            <tr>
              <Th>ASSGN</Th>
              <Th>RETURN</Th>
              <Th>ASSGN–SN</Th>
              <Th>Asset Number</Th>
              <Th>Seq ID</Th>
              <Th>Description</Th>
              <Th>Group Name</Th>
              <Th>Group Of</Th>
              <Th>Date Purchased</Th>
              <Th>Custodian</Th>
              <Th>Issued By</Th>
            </tr>
          </thead>
          <tbody>
            {rows.map(r=>(
              <tr key={r.custody_id} className="odd:bg-white even:bg-green-50">
                <Td>
                  <button className={`px-2 py-1 rounded ${r.status==='IN_STOCK'?'bg-yellow-300':'bg-gray-300 cursor-not-allowed'}`}
                          disabled={r.status!=='IN_STOCK'} onClick={()=>openAssign(r)} title="Assign">📦</button>
                </Td>
                <Td>
                  <button className={`px-2 py-1 rounded ${r.status==='ASSIGNED'?'bg-yellow-300':'bg-gray-300 cursor-not-allowed'}`}
                          disabled={r.status!=='ASSIGNED'} onClick={()=>openReturn(r)} title="Return">↩️</button>
                </Td>
                <Td>
                  <button className="px-2 py-1 rounded bg-yellow-300" onClick={()=>openSN(r)} title="Assign Serials">🧾</button>
                </Td>
                {/*<Td className="bg-yellow-100">{r.asset_number}</Td>
                <Td>{r.seq_id}</Td>
                <Td>{r.description}</Td>
                <Td>{r.group_name}</Td>
                <Td className="text-center">1</Td>
                <Td className="bg-yellow-100">{r.purchased_date || ''}</Td>
                <Td>{r.custodian || ''}</Td>
                <Td>{r.issued_by || ''}</Td>*/}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Pager */}
      <div className="flex gap-2 items-center">
        <button className="px-2 py-1 border rounded" disabled={page<=1} onClick={()=>setPage(p=>p-1)}>Prev</button>
        <span>Page {page}</span>
        <button className="px-2 py-1 border rounded" disabled={rows.length<per} onClick={()=>setPage(p=>p+1)}>Next</button>
        <span className="text-gray-500">Total ~{total}</span>
      </div>

      {/* Modal */}
      {modal && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
          <div className="bg-yellow-50 rounded-xl border shadow-xl p-4 w-[720px]">
            <div className="flex justify-between items-center">
              <div className="font-bold">
                {modal.kind==='assign' && 'Assign Values'}
                {modal.kind==='return' && 'Return'}
                {modal.kind==='sn'     && 'Assign Serial Numbers'}
              </div>
              <button className="px-2 py-1 bg-white rounded" onClick={()=>setModal(null)}>Close this Form</button>
            </div>

            <div className="mt-2 text-green-800">
              Asset Number: <b>{modal.assetNumber}</b> &nbsp;&nbsp; Asset Description: <b>{modal.description}</b>
            </div>

            {modal.kind!=='sn' ? (
              <>
                <div className="mt-3">
                  <label className="inline-flex items-center gap-2">
                    <input type="checkbox" checked={byBatch} onChange={e=>setByBatch(e.target.checked)}/>
                    <span>Check if By Batch, unCheck if By Individual</span>
                  </label>
                </div>
                {!byBatch && (
                  <div className="mt-2">
                    <Labeled label="Seq ID"><Input value={seqId as any} onChange={(v)=>setSeqId(v?+v as any:'')} /></Labeled>
                  </div>
                )}
              </>
            ) : null}

            {modal.kind==='assign' && (
              <div className="grid grid-cols-2 gap-3 mt-3">
                <Labeled label="Department"><Input value={dept} onChange={setDept}/></Labeled>
                <Labeled label="Location"><Input value={loc} onChange={setLoc}/></Labeled>
                <Labeled label="Custodian"><Input value={cust} onChange={setCust}/></Labeled>
                <Labeled label="Issued By"><Input value={issuedBy} onChange={setIssuedBy}/></Labeled>
              </div>
            )}

            {modal.kind==='sn' && (
              <div className="grid grid-cols-2 gap-3 mt-3">
                <Labeled label="Starting Serial Number"><Input value={startSN} onChange={setStartSN} placeholder="e.g., ABC0000"/></Labeled>
              </div>
            )}

            <div className="mt-4">
              <button className="px-4 py-2 rounded bg-green-700 text-white" onClick={doSubmit}>
                {modal.kind==='assign' ? 'Assign Values' : modal.kind==='return' ? 'Return' : 'Assign Serials'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

function Labeled({label, children}:{label:string; children:React.ReactNode}){
  return (
    <label className="text-sm w-full">
      <div className="mb-1 font-semibold text-white">{label}:</div>
      {children}
    </label>
  )
}
function Input({value,onChange,placeholder}:{value:any; onChange:(v:string)=>void; placeholder?:string}){
  return <input className="w-full rounded px-2 py-1 bg-yellow-100 text-black" value={value ?? ''} placeholder={placeholder} onChange={e=>onChange(e.target.value)} />
}
function Th({children}:{children:React.ReactNode}){ return <th className="p-2 text-left">{children}</th> }
function Td({children}:{children:React.ReactNode}){ return <td className="p-2">{children}</td> }
