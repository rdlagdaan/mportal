import * as React from 'react'
import { useEffect, useState } from 'react'
import napi from '@/utils/axiosnapi'

type Row = {
  id: number
  gl_account: string
  department: string
  trans_flag: 'Y'|'N'
}

export default function AssetDepartmentGL(){
  // filters
  const [gl, setGl] = useState('')
  const [name, setName] = useState('')
  const [per, setPer] = useState(20)
  const [page, setPage] = useState(1)

  // data
  const [rows, setRows] = useState<Row[]>([])
  const [total, setTotal] = useState(0)

  // form (create/update)
  const [form, setForm] = useState<Partial<Row>>({})
  const editing = (form as any).id ? true : false

  async function load(){
    const res = await napi.get('/assets/department-gl', { params: { gl, name, per, page } })
    setRows(res.data.data ?? res.data)
    setTotal(res.data.total ?? res.data.length ?? 0)
  }
  useEffect(()=>{ load() }, [gl, name, per, page])

  async function save(){
    if (!form.gl_account || !form.department) {
      alert('Department GL Code and Department Name are required.')
      return
    }
    const payload = {
      gl_account: form.gl_account,
      department: form.department,
      trans_flag: (form.trans_flag ?? 'Y')
    }
    if (editing) {
      await napi.patch(`/assets/department-gl/${(form as any).id}`, payload)
    } else {
      const res = await napi.post('/assets/department-gl', payload)
      ;(payload as any).id = res.data.id
    }
    setForm({})
    await load()
  }

  async function del(id:number){
    if (!confirm('Delete this Department GL row?')) return
    await napi.delete(`/assets/department-gl/${id}`)
    await load()
  }

  return (
    <div className="p-4 space-y-4">
      {/* Header toolbar (New) */}
      <div className="bg-yellow-300 border rounded p-2 inline-flex items-center gap-2">
        <button
          className="px-3 py-2 rounded bg-yellow-400 border shadow-sm"
          onClick={()=>setForm({ gl_account:'', department:'', trans_flag:'Y' })}
        >
          🧭 New Asset Department GL
        </button>
      </div>

      {/* Green search band */}
      <div className="rounded border bg-green-800 p-3 text-white">
        <div className="grid grid-cols-6 gap-3 items-end">
          <label className="col-span-2 text-sm">
            <div className="mb-1 font-semibold">Department GL:</div>
            <input className="w-full rounded px-2 py-1 text-black bg-yellow-100" value={gl} onChange={e=>setGl(e.target.value)} />
          </label>
          <label className="col-span-3 text-sm">
            <div className="mb-1 font-semibold">Department Name:</div>
            <input className="w-full rounded px-2 py-1 text-black bg-yellow-100" value={name} onChange={e=>setName(e.target.value)} />
          </label>
          <div className="col-span-1 flex gap-2 items-end justify-end">
            <button className="px-3 py-2 rounded bg-white text-green-800" onClick={()=>{setPage(1); load()}}>Search</button>
            <select className="rounded text-black px-2 py-1" value={per} onChange={e=>setPer(+e.target.value)}>
              <option>20</option><option>50</option><option>100</option>
            </select>
          </div>
        </div>
      </div>

      {/* Create / Update form (yellow bar) */}
      {(form.gl_account !== undefined) && (
        <div className="rounded border bg-yellow-50 p-4">
          <div className="mb-3 font-bold">{editing ? 'Update' : 'Save'}</div>
          <div className="grid grid-cols-4 gap-3">
            <label className="text-sm col-span-1">
              <div className="text-green-800 font-semibold mb-1">Department GL Code:</div>
              <input className="w-full border rounded px-2 py-1 bg-yellow-100"
                     value={form.gl_account ?? ''} onChange={e=>setForm(f=>({...f, gl_account:e.target.value}))} />
            </label>
            <label className="text-sm col-span-2">
              <div className="text-green-800 font-semibold mb-1">Department Name:</div>
              <input className="w-full border rounded px-2 py-1 bg-yellow-100"
                     value={form.department ?? ''} onChange={e=>setForm(f=>({...f, department:e.target.value}))} />
            </label>
            <label className="text-sm col-span-1">
              <div className="text-green-800 font-semibold mb-1">Transaction Flag:</div>
              <select className="w-full border rounded px-2 py-1 bg-yellow-100"
                      value={(form.trans_flag ?? 'Y') as 'Y'|'N'}
                      onChange={e=>setForm(f=>({...f, trans_flag: e.target.value as 'Y'|'N'}))}>
                <option value="Y">Y</option>
                <option value="N">N</option>
              </select>
            </label>
          </div>

          <div className="mt-3 flex gap-2">
            <button className="px-4 py-2 rounded bg-green-700 text-white" onClick={save}>
              {editing ? 'Update' : 'Save'}
            </button>
            <button className="px-3 py-2 rounded bg-white border" onClick={()=>setForm({})}>
              Close this Form
            </button>
          </div>
        </div>
      )}

      {/* Grid */}
      <div className="overflow-auto border rounded">
        <table className="min-w-full text-sm">
          <thead className="bg-green-800 text-white">
            <tr>
              <th className="p-2 text-left">Actions</th>
              <th className="p-2 text-left">Department GL Code</th>
              <th className="p-2 text-left">Department Name</th>
              <th className="p-2 text-left">Transaction Flag</th>
            </tr>
          </thead>
          <tbody>
            {rows.map(r=>(
              <tr key={r.id} className="odd:bg-white even:bg-green-50">
                <td className="p-2">
                  <button className="text-blue-700 mr-3" onClick={()=>setForm(r)}>Edit</button>
                  <button className="text-red-700" onClick={()=>del(r.id)}>✖</button>
                </td>
                <td className="p-2">{r.gl_account}</td>
                <td className="p-2">{r.department}</td>
                <td className="p-2">{r.trans_flag}</td>
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
