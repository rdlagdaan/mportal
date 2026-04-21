import * as React from 'react';
import { useEffect, useRef, useState } from 'react';
import napi from '@/utils/axiosnapi';
import {
  PlusCircleIcon,
  PencilSquareIcon,
  TrashIcon,
  ArrowsUpDownIcon,
  PrinterIcon
} from '@heroicons/react/24/outline';
import AssetDetailModal from '@/lrwsis/components/fixedassets/AssetDetailModal';

type AssetRow = {
  id: number;
  asset_no: string;
  description: string;
  group_asset_name?: string | null;
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
  const [order, setOrder] = useState<
    'asset_no'|'description'|'group_asset'|'life_months'|'quantity'|'gross_amount'|'purchase_date'|'reference'|'supplier_name'|'created_at'
  >('purchase_date');
  const [dir, setDir] = useState<'asc'|'desc'>('desc');

  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState<number|null>(null);

  // Print modal
  const [printOpen, setPrintOpen] = useState(false);
  const [printRow, setPrintRow] = useState<AssetRow | null>(null);

  // debounce search
  const t = useRef<ReturnType<typeof setTimeout> | null>(null);
  useEffect(() => {
    if (t.current) clearTimeout(t.current);
    t.current = setTimeout(() => setDebouncedQ(q.trim()), 300);
    return () => { if (t.current) clearTimeout(t.current); };
  }, [q]);

  // init: page size + first load
  useEffect(() => { (async () => {
    try {
      const s = await napi.get('/settings/paginaterecs');
      setPer(Number(s.data?.value) || 10);
    } catch {}
    await load(1);
  })(); }, []);

  // reload when search/sort changes
  useEffect(() => { load(1); /* eslint-disable-next-line */ }, [debouncedQ, order, dir, per]);

  async function load(p = page) {
    setIsLoading(true);
    try {
      const res = await napi.get<Paged<AssetRow>>('/assets', {
        params: { q: debouncedQ, per, page: p, order, dir }
      });
      setRows(res.data.data);
      setPage(res.data.current_page);
      setPages(res.data.last_page);
    } finally { setIsLoading(false); }
  }

  function toggleSort(col: typeof order) {
    if (order === col) setDir(d => d === 'asc' ? 'desc' : 'asc');
    else { setOrder(col); setDir('asc'); }
  }

  function openAdd() { setEditingId(null); setModalOpen(true); }
  function openEdit(id: number) { setEditingId(id); setModalOpen(true); }

  async function handleDelete(id: number) {
    if (!confirm('Archive this asset?')) return;
    await napi.delete(`/assets/${id}`);
    const nextP = rows.length === 1 && page > 1 ? page - 1 : page;
    await load(nextP);
  }

  function openPrint(row: AssetRow) {
    setPrintRow(row);
    setPrintOpen(true);
  }

  function handlePrintNow() {
    if (!printRow) return;
    const size = 512; // crisper for stickers
    const qrSrc = `/app/assets/${printRow.id}/qr.svg?size=${size}`;
    const title = `${printRow.asset_no}`;
    const desc = printRow.description || '';

    const html = `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>${title}</title>
<style>
  *{box-sizing:border-box;}
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",Arial; margin:24px;}
  .card{width: 92mm; border:1px solid #ddd; padding:16px; border-radius:10px;}
  .qr{display:block; margin:8px auto; width:60mm; height:60mm;}
  .label{margin-top:8px; text-align:center;}
  .asset{font-weight:700; font-size:18px;}
  .desc{font-size:14px; margin-top:4px;}
  @page { size: A4; margin: 12mm; }
</style>
</head>
<body>
  <div class="card">
    <img class="qr" src="${qrSrc}" alt="QR: ${title}">
    <div class="label asset">${title}</div>
    <div class="label desc">${desc.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
  </div>
  <script>window.onload = function(){ window.focus(); window.print(); };</script>
</body>
</html>`;
    const w = window.open('', 'qrprint', 'width=720,height=900');
    if (w) {
      w.document.open();
      w.document.write(html);
      w.document.close();
    }
  }

  return (
    <div className="p-6 space-y-4">
      {/* Toolbar */}
      <div className="flex flex-wrap items-center gap-2">
        <button
          onClick={openAdd}
          className="inline-flex items-center gap-2 bg-green-700 text-white px-4 py-2 rounded hover:bg-green-600"
        >
          <PlusCircleIcon className="h-5 w-5" /> Add Asset Detail
        </button>
        <input
          value={q}
          onChange={e => setQ(e.target.value)}
          placeholder="Search asset no / description / reference / supplier / notes…"
          className="w-96 border rounded px-3 py-2"
        />
        <button onClick={() => { setQ(''); setDebouncedQ(''); }} className="px-3 py-2 border rounded">
          Clear
        </button>

        <div className="ml-auto flex items-center gap-2">
          <label className="text-sm text-gray-600">Per page</label>
          <select value={per} onChange={e => setPer(Number(e.target.value))} className="border rounded px-2 py-1">
            {[10, 20, 50, 100].map(n => <option key={n} value={n}>{n}</option>)}
          </select>
        </div>
      </div>


{/* WIDTH-LIMITED CARD so the page doesn’t feel too wide (tune 1280px below) */}
<div className="mx-auto w-full max-w-[900px]">

  <div className="border rounded shadow bg-white overflow-hidden w-full">
    {/* one scroll container for BOTH axes; tune the height to your layout */}
    <div className="overflow-auto max-h-[calc(100vh-240px)]">
      <table
        className="w-full min-w-[1900px] text-sm table-fixed"
        style={{ tableLayout: 'fixed' }}
      >
        <colgroup>
          <col style={{ width: '3rem' }} />      {/* delete */}
          <col style={{ width: '14rem' }} />     {/* Asset Number */}
          <col style={{ width: '26rem' }} />     {/* Asset Desc (reserved & widest) */}
          <col style={{ width: '9rem' }} />      {/* Group Asset Name */}
          <col style={{ width: '5rem' }} />      {/* Life */}
          <col style={{ width: '6rem' }} />      {/* Quantity */}
          <col style={{ width: '7rem' }} />      {/* Gross Amt */}
          <col style={{ width: '11rem' }} />     {/* Purchased Date */}
          <col style={{ width: '9rem' }} />      {/* Reference */}
          <col style={{ width: '9rem' }} />      {/* Supplier */}
          <col style={{ width: '14rem' }} />     {/* Notes */}
          <col style={{ width: '6rem' }} />      {/* Actions */}
        </colgroup>

        <thead className="bg-gray-100 text-gray-700 sticky top-0 z-10">
          <tr>
            <th className="p-2"></th>
            <th className="p-2 cursor-pointer whitespace-nowrap" onClick={() => toggleSort('asset_no')}>
              Asset Number <ArrowsUpDownIcon className="inline h-4 w-4" />
            </th>
            <th className="p-2 cursor-pointer" onClick={() => toggleSort('description')}>
              Asset Desc <ArrowsUpDownIcon className="inline h-4 w-4" />
            </th>
            <th className="p-2 cursor-pointer whitespace-nowrap" onClick={() => toggleSort('group_asset')}>
              Group Asset Name <ArrowsUpDownIcon className="inline h-4 w-4" />
            </th>
            <th className="p-2 cursor-pointer text-right" onClick={() => toggleSort('life_months')}>Life</th>
            <th className="p-2 cursor-pointer text-right" onClick={() => toggleSort('quantity')}>Quantity</th>
            <th className="p-2 cursor-pointer text-right whitespace-nowrap" onClick={() => toggleSort('gross_amount')}>
              Gross Amt <ArrowsUpDownIcon className="inline h-4 w-4" />
            </th>
            <th className="p-2 cursor-pointer whitespace-nowrap" onClick={() => toggleSort('purchase_date')}>
              Purchased Date <ArrowsUpDownIcon className="inline h-4 w-4" />
            </th>
            <th className="p-2 cursor-pointer">Reference</th>
            <th className="p-2 cursor-pointer">Supplier</th>
            <th className="p-2">Notes</th>
            <th className="p-2">Actions</th>
          </tr>
        </thead>

        <tbody>
          {rows.map((r, i) => (
            <tr key={r.id} className={i % 2 ? 'bg-gray-50' : ''}>
              <td className="p-2 text-center align-top">
                <button onClick={() => handleDelete(r.id)} title="Archive" className="text-red-600 hover:text-red-800">
                  <TrashIcon className="h-5 w-5" />
                </button>
              </td>

              <td
                className="p-2 text-green-700 font-medium whitespace-nowrap align-top cursor-pointer"
                onClick={() => openEdit(r.id)}
                title="Edit"
              >
                {r.asset_no}
              </td>

              {/* Ensure the cells can’t be narrower than the reserved col width */}
              <td className="p-2 whitespace-normal break-words align-top leading-snug min-w-[26rem]">
                {r.description}
              </td>

              <td className="p-2 align-top">{r.group_asset_name || r.type_code || ''}</td>
              <td className="p-2 align-top text-right">{r.life_months ?? ''}</td>
              <td className="p-2 align-top text-right">{r.quantity ?? ''}</td>
              <td className="p-2 align-top text-right">{new Intl.NumberFormat().format(r.gross_amount || 0)}</td>
              <td className="p-2 align-top whitespace-nowrap">{r.purchase_date || ''}</td>
              <td className="p-2 align-top">{r.reference || ''}</td>
              <td className="p-2 align-top">{r.supplier_name || ''}</td>
              <td className="p-2 align-top">
                <div className="truncate" title={r.notes || ''}>{r.notes || ''}</div>
              </td>
              <td className="p-2 align-top">
                <div className="flex items-center gap-3">
                  <button onClick={() => openEdit(r.id)} className="text-blue-600 hover:text-blue-800" title="Edit">
                    <PencilSquareIcon className="h-5 w-5" />
                  </button>
                  <button onClick={() => openPrint(r)} className="text-gray-700 hover:text-gray-900" title="QR / Print">
                    <PrinterIcon className="h-5 w-5" />
                  </button>
                </div>
              </td>
            </tr>
          ))}

          {!isLoading && rows.length === 0 && (
            <tr><td colSpan={12} className="p-6 text-center text-gray-500">No records found</td></tr>
          )}
        </tbody>
      </table>
    </div>
  </div>
</div>




      {/* Pagination */}
      <div className="flex items-center justify-between text-sm">
        <div>Page {page} of {pages}</div>
        <div className="flex gap-2">
          <button onClick={() => load(1)} disabled={page === 1} className="px-3 py-1 border rounded disabled:opacity-50">First</button>
          <button onClick={() => load(page - 1)} disabled={page === 1} className="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
          <button onClick={() => load(page + 1)} disabled={page === pages} className="px-3 py-1 border rounded disabled:opacity-50">Next</button>
          <button onClick={() => load(pages)} disabled={page === pages} className="px-3 py-1 border rounded disabled:opacity-50">Last</button>
        </div>
      </div>

      {/* Add/Edit Modal */}
      {modalOpen && (
        <AssetDetailModal
          id={editingId}
          onClose={async (changed) => { setModalOpen(false); setEditingId(null); if (changed) await load(page); }}
        />
      )}

      {/* QR / Print Modal */}
      {printOpen && printRow && (
        <div className="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-xl shadow-xl w-full max-w-xl">
            <div className="p-4 border-b flex items-center justify-between">
              <h3 className="font-semibold text-lg">Print Asset QR</h3>
              <button onClick={() => setPrintOpen(false)} className="px-2 py-1 text-gray-600 hover:text-black">✕</button>
            </div>
            <div className="p-6 space-y-4">
              <div className="text-center">
                <img
                  src={`/app/assets/${printRow.id}/qr.svg?size=256`}
                  alt={`QR ${printRow.asset_no}`}
                  className="mx-auto"
                />
              </div>
              <div className="text-center">
                <div className="text-base font-semibold">{printRow.asset_no}</div>
                <div className="text-sm text-gray-700">{printRow.description}</div>
              </div>
            </div>
            <div className="p-4 border-t flex items-center justify-end gap-2">
              <button onClick={() => setPrintOpen(false)} className="px-4 py-2 rounded border">Close</button>
              <button onClick={handlePrintNow} className="inline-flex items-center gap-2 bg-green-700 text-white px-4 py-2 rounded hover:bg-green-600">
                <PrinterIcon className="h-5 w-5" />
                Print
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
