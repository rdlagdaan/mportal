import * as React from 'react';
import { useEffect, useRef, useState } from 'react';
import napi from '@/utils/axiosnapi';
import {
  PlusCircleIcon,
  PencilSquareIcon,
  TrashIcon,
  ArrowsUpDownIcon
} from '@heroicons/react/24/outline';
import AssetDetailModal from '@/lrwsis/components/fixedassets/AssetDetailModal';

type AssetRow = {
  id: number;
  asset_no: string;
  description: string;
  group_asset_name?: string | null;   // aggregated Class/Type name (backend can provide)
  type_code?: string | null;
  life_months: number;
  quantity: number;
  gross_amount: number;
  purchase_date?: string | null;
  reference?: string | null;
  supplier_name?: string | null;
  notes?: string | null;
  status: string;
  created_at?: string;
  updated_at?: string;
};

type Paged<T> = { data: T[]; current_page: number; last_page: number; total: number };

export default function AssetDetailList() {
  const [rows, setRows] = useState<AssetRow[]>([]);
  const [isLoading, setIsLoading] = useState(false);

  const [q, setQ] = useState('');
  const [debouncedQ, setDebouncedQ] = useState('');
  const [per, setPer] = useState(10);
  const [page, setPage] = useState(1);
  const [pages, setPages] = useState(1);
  const [order, setOrder] = useState<'asset_no'|'description'|'group_asset'|'life_months'|'quantity'|'gross_amount'|'purchase_date'|'reference'|'supplier_name'|'created_at'>('purchase_date');
  const [dir, setDir] = useState<'asc'|'desc'>('desc');

  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState<number|null>(null);

  // debounce search
  const t = useRef<ReturnType<typeof setTimeout>|null>(null);
  useEffect(()=>{ if (t.current) clearTimeout(t.current); t.current = setTimeout(()=> setDebouncedQ(q.trim()), 300); return ()=>{ if (t.current) clearTimeout(t.current);} }, [q]);

  // init: page size + first load
  useEffect(()=>{ (async ()=> {
    try {
      const s = await napi.get('/app/api/settings/paginaterecs');
      setPer(Number(s.data?.value)||10);
    } catch {}
    await load(1);
  })(); }, []);

  // reload when search/sort changes
  useEffect(()=>{ load(1); /* eslint-disable-next-line */ }, [debouncedQ, order, dir, per]);

  async function load(p = page) {
    setIsLoading(true);
    try {
      const res = await napi.get<Paged<AssetRow>>('/app/api/assets', {
        params: { q: debouncedQ, per, page: p, order, dir }
      });
      setRows(res.data.data);
      setPage(res.data.current_page);
      setPages(res.data.last_page);
    } finally { setIsLoading(false); }
  }

  function toggleSort(col: typeof order) {
    if (order === col) setDir(d=> d==='asc'?'desc':'asc');
    else { setOrder(col); setDir('asc'); }
  }

  function openAdd() { setEditingId(null); setModalOpen(true); }
  function openEdit(id: number) { setEditingId(id); setModalOpen(true); }

  async function handleDelete(id: number) {
    if (!confirm('Archive this asset?')) return;
    await napi.delete(`/app/api/assets/${id}`);
    const nextP = rows.length===1 && page>1 ? page-1 : page;
    await load(nextP);
  }

  return (
    <div className="p-6 space-y-4">
      {/* Toolbar */}
      <div className="flex flex-wrap items-center gap-2">
        <button onClick={openAdd} className="inline-flex items-center gap-2 bg-green-700 text-white px-4 py-2 rounded hover:bg-green-600">
          <PlusCircleIcon className="h-5 w-5" /> Add Asset Detail
        </button>
        <input
          value={q}
          onChange={e=>setQ(e.target.value)}
          placeholder="Search asset no / description / reference / supplier / notes…"
          className="w-96 border rounded px-3 py-2"
        />
        <button onClick={()=>{ setQ(''); setDebouncedQ(''); }} className="px-3 py-2 border rounded">Clear</button>

        <div className="ml-auto flex items-center gap-2">
          <label className="text-sm text-gray-600">Per page</label>
          <select value={per} onChange={e=> setPer(Number(e.target.value))} className="border rounded px-2 py-1">
            {[10,20,50,100].map(n=> <option key={n} value={n}>{n}</option>)}
          </select>
        </div>
      </div>

      {/* Table */}
      <div className="border rounded shadow bg-white overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="bg-gray-100 text-gray-700">
            <tr>
              <th className="p-2 w-10"></th>
              <th className="p-2 cursor-pointer whitespace-nowrap" onClick={()=>toggleSort('asset_no')}>
                Asset Number <ArrowsUpDownIcon className="inline h-4 w-4"/>
              </th>
              <th className="p-2 cursor-pointer whitespace-nowrap" onClick={()=>toggleSort('description')}>
                Asset Desc <ArrowsUpDownIcon className="inline h-4 w-4"/>
              </th>
              <th className="p-2 cursor-pointer whitespace-nowrap" onClick={()=>toggleSort('group_asset')}>
                Group Asset Name <ArrowsUpDownIcon className="inline h-4 w-4"/>
              </th>
              <th className="p-2 cursor-pointer" onClick={()=>toggleSort('life_months')}>Life</th>
              <th className="p-2 cursor-pointer" onClick={()=>toggleSort('quantity')}>Quantity</th>
              <th className="p-2 cursor-pointer whitespace-nowrap" onClick={()=>toggleSort('gross_amount')}>
                Gross Amt <ArrowsUpDownIcon className="inline h-4 w-4"/>
              </th>
              <th className="p-2 cursor-pointer whitespace-nowrap" onClick={()=>toggleSort('purchase_date')}>
                Purchased Date <ArrowsUpDownIcon className="inline h-4 w-4"/>
              </th>
              <th className="p-2 cursor-pointer" onClick={()=>toggleSort('reference')}>Reference</th>
              <th className="p-2 cursor-pointer" onClick={()=>toggleSort('supplier_name')}>Supplier</th>
              <th className="p-2">Notes</th>
              <th className="p-2 w-20">Actions</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r,i)=> (
              <tr key={r.id} className={i%2? 'bg-gray-50' : ''}>
                <td className="p-2 text-center">
                  <button onClick={()=>handleDelete(r.id)} title="Archive" className="text-red-600 hover:text-red-800">
                    <TrashIcon className="h-5 w-5"/>
                  </button>
                </td>
                <td className="p-2 text-green-700 font-medium cursor-pointer" onClick={()=>openEdit(r.id)}>{r.asset_no}</td>
                <td className="p-2">{r.description}</td>
                <td className="p-2">{r.group_asset_name || r.type_code || ''}</td>
                <td className="p-2">{r.life_months}</td>
                <td className="p-2">{r.quantity}</td>
                <td className="p-2">{new Intl.NumberFormat().format(r.gross_amount||0)}</td>
                <td className="p-2 whitespace-nowrap">{r.purchase_date||''}</td>
                <td className="p-2">{r.reference||''}</td>
                <td className="p-2">{r.supplier_name||''}</td>
                <td className="p-2 max-w-[16rem]">
                  <div className="truncate" title={r.notes||''}>{r.notes||''}</div>
                </td>
                <td className="p-2">
                  <button onClick={()=>openEdit(r.id)} className="text-blue-600 hover:text-blue-800" title="Edit">
                    <PencilSquareIcon className="h-5 w-5"/>
                  </button>
                </td>
              </tr>
            ))}
            {!isLoading && rows.length===0 && (
              <tr><td colSpan={12} className="p-6 text-center text-gray-500">No records found</td></tr>
            )}
          </tbody>
        </table>
        {isLoading && <div className="p-4 text-center text-gray-600">Loading…</div>}
      </div>

      {/* Pagination */}
      <div className="flex items-center justify-between text-sm">
        <div>Page {page} of {pages}</div>
        <div className="flex gap-2">
          <button onClick={()=>load(1)} disabled={page===1} className="px-3 py-1 border rounded disabled:opacity-50">First</button>
          <button onClick={()=>load(page-1)} disabled={page===1} className="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
          <button onClick={()=>load(page+1)} disabled={page===pages} className="px-3 py-1 border rounded disabled:opacity-50">Next</button>
          <button onClick={()=>load(pages)} disabled={page===pages} className="px-3 py-1 border rounded disabled:opacity-50">Last</button>
        </div>
      </div>

      {/* Modal */}
      {modalOpen && (
        <AssetDetailModal
          id={editingId}
          onClose={async (changed)=>{ setModalOpen(false); setEditingId(null); if (changed) await load(page); }}
        />
      )}
    </div>
  );
}
