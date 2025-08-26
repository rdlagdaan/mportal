import * as React from 'react'
import { useEffect, useState } from 'react'
import napi from '@/utils/axiosnapi'

type Row = {
  id: number
  asset_number: string
  description: string
  group_name: string
  gross_amount: number
  depr_amount: number
  remaining_amount: number
  spent_months: number
  remaining_months: number
}

export default function AssetDepreciation(){
  // filters
  const [f, setF] = useState({
    asset_number: '',
    description: '',
    group_name: '',
    depr_amt: '',
    remaining_amt: '',
    spent_months: '',
    remain_months: '',
  })
  const [per, setPer] = useState(20)
  const [page, setPage] = useState(1)

  // data
  const [rows, setRows] = useState<Row[]>([])
  const [total, setTotal] = useState(0)
  const [processing, setProcessing] = useState(false)

  async function load(){
    const res = await napi.get('/assets/depreciation', { params: { ...f, per, page } })
    const data = res.data
    setRows(data.data ?? data)
    setTotal(data.total ?? data.length ?? 0)
  }
  useEffect(()=>{ load() },[JSON.stringify(f), per, page]) // eslint-disable-line

  // Process Depreciation (batch)
  const [procStart, setProcStart] = useState('')
  const [procEnd, setProcEnd] = useState('')

  async function runProcess(){
    if (!procStart || !procEnd) return alert('Start and End are required (YYYY-MM-DD).')
    setProcessing(true)
    try {
      await napi.post('/assets/depreciation/process', { start: procStart, end: procEnd })
      await load()
    } finally {
      setProcessing(false)
    }
  }

  return (
    <div className="p-4 space-y-4">
      {/* Toolbar */}
      <div className="flex flex-wrap items-end gap-3 bg-yellow-100 p-3 rounded border">
        <button
          className="px-3 py-2 rounded bg-yellow-400 border shadow-sm"
          onClick={load}
          title="Refresh Depreciation Records"
        >
          🔄 Refresh Depreciation Records
        </button>

        <div className="grid grid-cols-6 gap-2 items-end w-full">
          <LabeledInput label="Asset Number" value={f.asset_number} onChange={v=>setF(x=>({...x,asset_number:v}))}/>
          <LabeledInput label="Asset Desc" value={f.description} onChange={v=>setF(x=>({...x,description:v}))}/>
          <LabeledInput label="Group Asset Name" value={f.group_name} onChange={v=>setF(x=>({...x,group_name:v}))}/>
          <LabeledInput label="Depreciated Amt ≥" value={f.depr_amt} onChange={v=>setF(x=>({...x,depr_amt:v}))}/>
          <LabeledInput label="Remaining Amt ≥" value={f.remaining_amt} onChange={v=>setF(x=>({...x,remaining_amt:v}))}/>
          <LabeledInput label="Spent Months ≥" value={f.spent_months} onChange={v=>setF(x=>({...x,spent_months:v}))}/>
        </div>
        <div className="grid grid-cols-6 gap-2 items-end w-full">
          <LabeledInput label="Remaining Months ≥" value={f.remain_months} onChange={v=>setF(x=>({...x,remain_months:v}))}/>
          <div className="col-span-2" />
          <div className="col-span-2" />
          <div className="col-span-1 flex items-center justify-end gap-2">
            <select className="border rounded px-2 py-1" value={per} onChange={e=>setPer(+e.target.value)}>
              <option>20</option><option>50</option><option>100</option>
            </select>
          </div>
        </div>
      </div>

      {/* Process panel (like your 2nd screen) */}
      <div className="bg-yellow-50 p-3 rounded border">
        <div className="flex items-end gap-3">
          <LabeledInput label="Start Date" placeholder="YYYY-MM-01" value={procStart} onChange={setProcStart}/>
          <LabeledInput label="End Date" placeholder="YYYY-MM-01" value={procEnd} onChange={setProcEnd}/>
          <button
            onClick={runProcess}
            disabled={processing}
            className="px-4 py-2 rounded bg-green-700 text-white disabled:opacity-50"
            title="Process Asset Depreciation"
          >
            💾 Process Asset Depreciation
          </button>
        </div>
      </div>

      {/* Grid */}
      <div className="overflow-auto border rounded">
        <table className="min-w-full text-sm">
          <thead className="bg-green-800 text-white">
            <tr>
              <Th>Asset Number</Th>
              <Th>Description</Th>
              <Th>Group Name</Th>
              <Th className="text-right">Gross Amt</Th>
              <Th className="text-right">Deprctd Amt</Th>
              <Th className="text-right">Remaining Amt</Th>
              <Th className="text-right">Spent Months</Th>
              <Th className="text-right">Remaining Months</Th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.id} className="odd:bg-white even:bg-green-50">
                <Td>{r.asset_number}</Td>
                <Td>{r.description}</Td>
                <Td>{r.group_name}</Td>
                <Td className="text-right">{fmt(r.gross_amount)}</Td>
                <Td className="text-right">{fmt(r.depr_amount)}</Td>
                <Td className="text-right">{fmt(r.remaining_amount)}</Td>
                <Td className="text-right">{r.spent_months}</Td>
                <Td className="text-right">{r.remaining_months}</Td>
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
    </div>
  )
}

function LabeledInput({label, value, onChange, placeholder}:{label:string; value:string; onChange:(v:string)=>void; placeholder?:string}){
  return (
    <label className="text-sm w-full">
      <div className="text-green-800 font-semibold mb-1">{label}:</div>
      <input className="w-full border px-2 py-1 rounded bg-yellow-100 focus:bg-white" value={value} onChange={e=>onChange(e.target.value)} placeholder={placeholder}/>
    </label>
  )
}

function Th({children, className}:{children:React.ReactNode; className?:string}){
  return <th className={`p-2 text-left ${className||''}`}>{children}</th>
}
function Td({children, className}:{children:React.ReactNode; className?:string}){
  return <td className={`p-2 ${className||''}`}>{children}</td>
}
function fmt(n:number){ return (n??0).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}) }
