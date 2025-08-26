import * as React from 'react';
import { useEffect, useState } from 'react';
import napi from '@/utils/axiosnapi';

type Row = {
  id: number;
  service_date: string;
  description?: string|null;
  parts_cost?: number|null;
  labor_cost?: number|null;
  next_due_date?: string|null;
};

type Paged<T> = { data: T[]; current_page: number; last_page: number; total: number };

export default function ServiceLogTable({ assetId }:{ assetId:number }) {
  const [rows, setRows] = useState<Row[]>([]);
  const [per, setPer] = useState(10);
  const [page, setPage] = useState(1);
  const [pages, setPages] = useState(1);
  const [busy, setBusy] = useState(false);

  async function load(p=page) {
    setBusy(true);
    try {
      const r = await napi.get<Paged<Row>>(`/app/api/assets/${assetId}/service-logs`, { params: { per, page:p }});
      setRows(r.data.data); setPage(r.data.current_page); setPages(r.data.last_page);
    } finally { setBusy(false); }
  }
  useEffect(()=>{ load(1); /* eslint-disable-next-line */ }, [assetId]);

  async function add() {
    const today = new Date().toISOString().slice(0,10);
    await napi.post(`/app/api/assets/${assetId}/service-logs`, { service_date: today, description:'', parts_cost:0, labor_cost:0 });
    await load(1);
  }
  async function upd(r:Row, patch: Partial<Row>) {
    await napi.patch(`/app/api/service-logs/${r.id}`, patch);
    await load(page);
  }
  async function del(r:Row) {
    if (!confirm('Delete this service log?')) return;
    await napi.delete(`/app/api/service-logs/${r.id}`);
    const nextP = rows.length===1 && page>1 ? page-1 : page; await load(nextP);
  }

  return (
    <div className="space-y-3">
      <div className="flex items-center gap-2">
        <button onClick={add} className="px-3 py-2 bg-green-700 text-white rounded">New Service Entry</button>
        <div className="ml-auto text-sm text-gray-600">Per page
          <select value={per} onChange={e=>{ setPer(Number(e.target.value)); load(1); }} className="ml-2 border rounded px-2 py-1">
            {[10,20,50].map(n=> <option key={n} value={n}>{n}</option>)}
          </select>
        </div>
      </div>

      <div className="border rounded overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-100"><tr><th className="p-2">Date</th><th className="p-2">Description</th><th className="p-2 text-right">Parts</th><th className="p-2 text-right">Labor</th><th className="p-2">Next Due</th><th className="p-2 w-24">Actions</th></tr></thead>
          <tbody>
          {rows.map((r,i)=> (
            <tr key={r.id} className={i%2? 'bg-gray-50':''}>
              <td className="p-2"><input type="date" className="border rounded px-2 py-1" value={r.service_date} onChange={e=>upd(r,{ service_date:e.target.value })} /></td>
              <td className="p-2"><input className="w-full border rounded px-2 py-1" value={r.description||''} onChange={e=>upd(r,{ description:e.target.value })} /></td>
              <td className="p-2 text-right"><input type="number" min={0} step={0.01} className="w-28 border rounded px-2 py-1 text-right" value={r.parts_cost||0} onChange={e=>upd(r,{ parts_cost:Number(e.target.value||0) })} /></td>
              <td className="p-2 text-right"><input type="number" min={0} step={0.01} className="w-28 border rounded px-2 py-1 text-right" value={r.labor_cost||0} onChange={e=>upd(r,{ labor_cost:Number(e.target.value||0) })} /></td>
              <td className="p-2"><input type="date" className="border rounded px-2 py-1" value={r.next_due_date||''} onChange={e=>upd(r,{ next_due_date:e.target.value })} /></td>
              <td className="p-2"><button onClick={()=>del(r)} className="px-2 py-1 border rounded text-red-600">Delete</button></td>
            </tr>
          ))}
          {!busy && rows.length===0 && <tr><td colSpan={6} className="p-4 text-center text-gray-500">No service logs yet</td></tr>}
          </tbody>
        </table>
        {busy && <div className="p-3 text-center">Loading…</div>}
      </div>

      <div className="flex items-center justify-between text-sm">
        <div>Page {page} of {pages}</div>
        <div className="flex gap-2">
          <button onClick={()=>load(1)} disabled={page===1} className="px-3 py-1 border rounded disabled:opacity-50">First</button>
          <button onClick={()=>load(page-1)} disabled={page===1} className="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
          <button onClick={()=>load(page+1)} disabled={page===pages} className="px-3 py-1 border rounded disabled:opacity-50">Next</button>
          <button onClick={()=>load(pages)} disabled={page===pages} className="px-3 py-1 border rounded disabled:opacity-50">Last</button>
        </div>
      </div>
    </div>
  );
}
