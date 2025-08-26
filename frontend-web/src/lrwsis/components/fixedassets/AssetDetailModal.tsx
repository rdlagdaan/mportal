import * as React from 'react';
import { useEffect, useMemo, useState } from 'react';
import napi from '@/utils/axiosnapi';
import Cookies from 'js-cookie';
import { XMarkIcon } from '@heroicons/react/24/outline';
import SearchableCombo from '@/lrwsis/components/common/SearchableCombo';
import ComboBox, { ComboOption } from '@/lrwsis/components/common/ComboBox';

type AssetForm = {
  id?: number;
  asset_no?: string;

  description: string;
  asset_type: 'depreciable' | 'non_depreciable';
  class_code?: string | null;
  class_name?: string | null;
  category_code?: string | null;
  category_name?: string | null;
  type_code?: string | null;
  type_name?: string | null;
  quantity: number;

  loan_agreement?: string | null;
  include_in_audits: boolean;
  last_audited?: string | null;

  is_serialized: boolean;
  date_received?: string | null;
  notes?: string | null;
  manufacturer?: string | null;
  brand?: string | null;
  model?: string | null;

  life_months: number;
  reference?: string | null;
  supplier_id?: number | null;
  supplier_name?: string | null;
  purchase_date?: string | null;
  in_service_date?: string | null;
  warranty_expires?: string | null;
  vat_inclusive: boolean;
  vat_rate?: number | null;
  gross_amount: number;
  total_net_cost?: number | null;
  unit_cost?: number | null;

  status: string;
  picture_path?: string | null;

  depr_method?: 'straight_line' | 'declining_balance' | 'units_of_production';
  residual_rate?: number | null;
};

type SchedRow = { period:number; period_start:string; depreciation:number; };

export default function AssetDetailModal({
  id,
  onClose,
}: {
  id: number | null;
  onClose: (changed: boolean) => void;
}) {
  const [tab, setTab] =
    useState<'General' | 'Finance' | 'Notes' | 'History' | 'Picture' | 'Depreciation'>('General');
  const [busy, setBusy] = useState(false);
  const [saving, setSaving] = useState(false);

  const [classLabel, setClassLabel] = useState('');
  const [categoryLabel, setCategoryLabel] = useState('');
  const [typeLabel, setTypeLabel] = useState('');
  const [supplierLabel, setSupplierLabel] = useState('');

  const [sched, setSched] = useState<SchedRow[]>([]);
  const [schedBusy, setSchedBusy] = useState(false);

  const [deprLabel, setDeprLabel] = useState('');

  const descRef = React.useRef<HTMLInputElement>(null);




  const isEdit = !!id;

  const [form, setForm] = useState<AssetForm>({
    description: '',
    asset_type: 'depreciable',
    quantity: 1,
    loan_agreement: 'Default',
    include_in_audits: false,
    last_audited: '',
    is_serialized: false,
    date_received: '',
    notes: '',
    manufacturer: '',
    brand: '',
    model: '',
    life_months: 60,
    reference: '',
    supplier_id: null,
    supplier_name: '',
    purchase_date: '',
    in_service_date: '',
    warranty_expires: '',
    vat_inclusive: false,
    vat_rate: 0,
    gross_amount: 0,
    total_net_cost: 0,
    unit_cost: 0,
    status: 'ACTIVE',
    depr_method: 'straight_line',
    residual_rate: 0,
  });

const [deprOptions, setDeprOptions] = useState<ComboOption[]>([]);

// Load once; also set the label if the form already has a value
useEffect(() => {
  (async () => {
    try {
      const r = await napi.get('/lookups/depreciation-types');
      const rows = Array.isArray(r.data) ? r.data : [];
      const opts: ComboOption[] = rows.map((x:any) => ({
        id: x.value ?? x.id,      // API returns { id, value, label }
        label: x.label,
      }));
      setDeprOptions(opts);

      // if editing and we have a stored value, show its label
      if (!deprLabel && form.asset_type) {
        const found = opts.find(o => String(o.id) === String(form.asset_type));
        if (found) setDeprLabel(found.label);
      }
    } catch { /* ignore */ }
  })();
  // eslint-disable-next-line react-hooks/exhaustive-deps
}, []);






  // key: close with Esc
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(false); };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [onClose]);

  // Load existing
  useEffect(() => {
    (async () => {
      if (!id) return;
      setBusy(true);
      try {
        const r = await napi.get(`/app/api/assets/${id}`);
        const a = r.data || {};
        setForm((prev) => ({
          ...prev,
          ...a,
          asset_type: (a.asset_type || 'depreciable') as AssetForm['asset_type'],
          total_net_cost: a.total_net_cost ?? a.gross_amount ?? 0,
          unit_cost:
            a.unit_cost ??
            (a.quantity ? Number(((a.total_net_cost ?? a.gross_amount ?? 0) / a.quantity).toFixed(2)) : 0),
        }));
        setDeprLabel(a.asset_type === 'non_depreciable' ? 'Non-Depreciable' : 'Depreciable');
        setClassLabel(a.class_name || a.class_code || '');
        setCategoryLabel(a.category_name || a.category_code || '');
        setTypeLabel(a.type_name || a.type_code || '');
        setSupplierLabel(a.supplier_name || '');
      } finally { setBusy(false); }
    })();
  }, [id]);

  // Derived totals
  const derived = useMemo(() => {
    const qty = Number(form.quantity || 0);
    const gross = Number(form.gross_amount || 0);
    const vatRate = Number(form.vat_rate || 0) / 100;

    let net = Number(form.total_net_cost ?? 0);
    if (form.vat_inclusive && vatRate > 0) net = +(gross / (1 + vatRate)).toFixed(2);
    else if (!form.vat_inclusive) net = Number(form.total_net_cost ?? gross);

    const unit = qty > 0 ? +(net / qty).toFixed(2) : 0;
    return { net, unit };
  }, [form.quantity, form.gross_amount, form.vat_inclusive, form.vat_rate, form.total_net_cost]);

  useEffect(() => {
    setForm((f) => ({ ...f, total_net_cost: derived.net, unit_cost: derived.unit }));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [derived.net, derived.unit]);

  async function save(closeAfter = true) {
    setSaving(true);
    try {
      const payload = { ...form } as any;
      ['date_received', 'purchase_date', 'in_service_date', 'warranty_expires', 'last_audited'].forEach((k) => {
        if (payload[k] === '') payload[k] = null;
      });

      if (isEdit) {
        await napi.patch(`/app/api/assets/${id}`, payload, {
          headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' }, withCredentials: true,
        });
      } else {
        await napi.post(`/app/api/assets`, payload, {
          headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' }, withCredentials: true,
        });
      }
      if (closeAfter) onClose(true);
    } catch (e:any) {
      alert(e?.response?.data?.message || 'Save failed');
    } finally { setSaving(false); }
  }

  // per-tab save
  function TabSaveBar() {
    return (
      <div className="pt-4 flex justify-end">
        <button
          onClick={()=>save(false)}
          disabled={saving}
          className="px-4 py-2 bg-green-700 text-white rounded hover:bg-green-600 disabled:opacity-50"
        >
          Save This Tab
        </button>
      </div>
    );
  }

  // Depreciation preview
  async function loadSchedule() {
    if (!isEdit) return;
    setSchedBusy(true);
    try {
      const r = await napi.post(`/app/api/assets/${id}/depreciation/preview`, {});
      setSched((r.data?.rows || []) as SchedRow[]);
    } catch { setSched([]); }
    finally { setSchedBusy(false); }
  }
  useEffect(()=>{ if (tab==='Depreciation' && isEdit) loadSchedule(); }, [tab, isEdit]);

  // UI helpers
  function Label({ children }: { children: React.ReactNode }) {
    return <label className="block text-xs font-semibold text-green-800 tracking-wide uppercase mb-1">{children}</label>;
  }
  function InputBase({ children, col='col-span-4'}:{ children:React.ReactNode; col?:string }) {
    return <div className={`${col} min-w-0`}>{children}</div>;
  }
  const inputClass = 'w-full border-2 border-green-300 rounded-lg px-3 py-2 text-[15px] focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-green-500 bg-white';
  const dateClass = inputClass;
  const selectClass = inputClass;
  const checkClass = 'h-5 w-5 accent-green-600';

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      {/* overlay (behind) */}
      <div className="fixed inset-0 bg-black/40 z-40" onClick={()=>onClose(false)} />
      {/* sheet */}
      <div className="relative z-50 mx-auto my-6 w-full max-w-7xl">
        <div className="bg-white rounded-2xl shadow-2xl">
          {/* sticky header */}
          <div className="sticky top-0 z-10 flex items-center justify-between p-4 border-b bg-green-50">
            <div className="flex items-center gap-2">
              <button disabled={saving} onClick={()=>save(true)} className="px-4 py-2 bg-green-700 text-white rounded-lg hover:bg-green-600 disabled:opacity-50">Save and Close</button>
              <button disabled={saving} onClick={()=>save(false)} className="px-4 py-2 border-2 border-green-300 rounded-lg hover:bg-green-50 disabled:opacity-50">Save</button>
            </div>
            <div className="text-center">
              <div className="text-xs text-gray-500">{isEdit ? 'Asset Number' : 'New Asset'}</div>
              <div className="font-semibold text-lg text-green-800">{isEdit ? (form.asset_no || '') : 'tmp(temporary)'}</div>
            </div>
            <button onClick={()=>onClose(false)} className="p-1 rounded hover:bg-green-100"><XMarkIcon className="h-7 w-7 text-gray-600"/></button>
          </div>

          {/* scrollable body */}
          <div className="max-h-[80vh] overflow-y-auto">
            {/* MAIN */}
            <div className="p-5">
              <div className="grid grid-cols-12 gap-6">
                {/* left */}
                <div className="col-span-12 lg:col-span-8">
                  <div className="grid grid-cols-12 gap-4">
<InputBase col="col-span-12">
  <Label>Description</Label>
  <input
    ref={descRef}
    className={inputClass}
    value={form.description}
    onChange={(e) => {
      const v = e.target.value;
      setForm(f => ({ ...f, description: v }));
      // keep the caret in this input even if siblings re-render
      requestAnimationFrame(() => descRef.current?.focus());
    }}
    required
  />
</InputBase>


                  <InputBase col="col-span-4">
                  <ComboBox
                    label="Asset Depreciation Type"
                    placeholder="Pick depreciation type…"
                    options={deprOptions}
                    valueLabel={deprLabel}
                    onSelect={(opt) => {
                      setDeprLabel(opt.label);
                      setForm(f => ({ ...f, asset_type: opt.id as 'depreciable' | 'non_depreciable' }));
                    }}
                    required
                  />
                  </InputBase>


                <InputBase col="col-span-8">
                  <Label>Asset Group (Class)</Label>
<SearchableCombo
  placeholder="Search class…"
  fetchUrl="assets/lookups/classes"
  selectedLabel={classLabel}
  onChange={(v:any) => {
    setForm(f => ({
      ...f,
      class_code: v?.value || null,
      class_name: v?.label || null,
      category_code: null,
      category_name: null,
      type_code: null,
      type_name: null,
    }));
    setClassLabel(v?.label || '');
    setCategoryLabel('');
    setTypeLabel('');
  }}
/>
                </InputBase>


                <InputBase col="col-span-6">
                  <Label>Asset Sub Group (Category)</Label>
<SearchableCombo
  key={`cat-${form.class_code || 'none'}`}
  placeholder={form.class_code ? 'Search category…' : 'Pick a class first…'}
  fetchUrl={`assets/lookups/categories?class_code=${encodeURIComponent(form.class_code || '')}`}
  selectedLabel={categoryLabel}
  onChange={(v:any) => {
    setForm(f => ({
      ...f,
      category_code: v?.value || null,
      category_name: v?.label || null,
      type_code: null,
      type_name: null,
    }));
    setCategoryLabel(v?.label || '');
    setTypeLabel('');
  }}
/>
                </InputBase>


                <InputBase col="col-span-6">
                  <Label>Asset Comp Group (Type)</Label>
<SearchableCombo
  key={`type-${form.category_code || 'none'}`}
  placeholder={form.category_code ? 'Search type…' : 'Pick a category first…'}
  fetchUrl={`assets/types-by-cat?cat_code=${encodeURIComponent(form.category_code || '')}`}
  selectedLabel={typeLabel}
  onChange={(v:any) => {
    setForm(f => ({ ...f, type_code: v?.value || null, type_name: v?.label || null }));
    setTypeLabel(v?.label || '');
  }}
/>

                </InputBase>


                    <InputBase col="col-span-3">
                      <Label>Quantity</Label>
                      <input type="number" min={1} step={1} className={inputClass} value={form.quantity}
                        onChange={e=>setForm(f=>({...f, quantity:Number(e.target.value||1)}))} />
                    </InputBase>
                  </div>
                </div>

                {/* right */}
                <div className="col-span-12 lg:col-span-4">
                  <div className="grid grid-cols-12 gap-4">
                    <InputBase col="col-span-12">
                      <Label>Asset Number</Label>
                      <input className={inputClass} value={form.asset_no || 'tmp(temporary)'} disabled />
                    </InputBase>

                    {isEdit && (
                      <InputBase col="col-span-12">
                        <Label>QR Code</Label>
                        <div className="border rounded-lg p-3 flex items-center justify-center bg-white">
                          <img src={`/app/api/assets/${id}/qr.svg`} alt="Asset QR" className="max-h-40" onError={(e)=>((e.currentTarget.style.display='none'))}/>
                        </div>
                      </InputBase>
                    )}

                    <InputBase col="col-span-12">
                      <div className="rounded-xl border-2 border-green-200 p-4">
                        <div className="text-sm font-semibold text-green-800 mb-3">Options</div>
                        <div className="grid grid-cols-12 gap-3">
                          <div className="col-span-12">
                            <Label>Loan Agreement</Label>
                            <select className={selectClass} value={form.loan_agreement || 'Default'} onChange={e=>setForm(f=>({...f, loan_agreement:e.target.value}))}>
                              <option>Default</option><option>Lease</option><option>Loan</option>
                            </select>
                          </div>
                          <div className="col-span-6 flex items-center gap-3 mt-2">
                            <input type="checkbox" className={checkClass} checked={!!form.include_in_audits}
                              onChange={e=>setForm(f=>({...f, include_in_audits:e.target.checked}))}/>
                            <span className="text-sm font-medium text-gray-700">Include in Audits</span>
                          </div>
                          <div className="col-span-6">
                            <Label>Last Audited</Label>
                            <input type="date" className={dateClass} value={form.last_audited||''}
                              onChange={e=>setForm(f=>({...f, last_audited:e.target.value||null}))}/>
                          </div>
                        </div>
                      </div>
                    </InputBase>
                  </div>
                </div>
              </div>
            </div>

            {/* Tabs (sticky) */}
            <div className="px-5 sticky top-[64px] z-10 bg-white border-b">
              <div className="flex flex-wrap gap-2">
                {(['General','Finance','Notes','History','Picture','Depreciation'] as const).map(t=> (
                  <button key={t} onClick={()=>setTab(t)}
                    className={`px-4 py-2 border-b-2 -mb-px text-sm font-semibold ${
                      tab===t ? 'border-green-700 text-green-700' : 'border-transparent text-gray-600 hover:text-gray-800'
                    }`}>{t}</button>
                ))}
              </div>
            </div>

            {/* Body */}
            <div className="p-5">
              {busy && <div className="p-6 text-center">Loading…</div>}
              {!busy && (
                <>
                  {tab==='General' && (
                    <>
                      <div className="grid grid-cols-12 gap-4">
                        <InputBase col="col-span-3">
                          <Label>Serialized</Label>
                          <div className="flex items-center gap-3">
                            <input type="checkbox" className={checkClass} checked={!!form.is_serialized}
                              onChange={e=>setForm(f=>({...f, is_serialized:e.target.checked}))}/>
                            <span className="text-sm text-gray-700">Yes</span>
                          </div>
                        </InputBase>

                        <InputBase col="col-span-4">
                          <Label>Date Received</Label>
                          <input type="date" className={dateClass} value={form.date_received||''}
                            onChange={e=>setForm(f=>({...f, date_received:e.target.value||null}))}/>
                        </InputBase>

                        <InputBase col="col-span-12">
                          <Label>Notes</Label>
                          <textarea className={`${inputClass} min-h-36`} value={form.notes||''}
                            onChange={e=>setForm(f=>({...f, notes:e.target.value}))}/>
                        </InputBase>

                        <InputBase col="col-span-4">
                          <Label>Manufacturer</Label>
                          <input className={inputClass} value={form.manufacturer||''}
                            onChange={e=>setForm(f=>({...f, manufacturer:e.target.value}))}/>
                        </InputBase>
                        <InputBase col="col-span-4">
                          <Label>Brand</Label>
                          <input className={inputClass} value={form.brand||''}
                            onChange={e=>setForm(f=>({...f, brand:e.target.value}))}/>
                        </InputBase>
                        <InputBase col="col-span-4">
                          <Label>Model</Label>
                          <input className={inputClass} value={form.model||''}
                            onChange={e=>setForm(f=>({...f, model:e.target.value}))}/>
                        </InputBase>
                      </div>
                      <TabSaveBar/>
                    </>
                  )}

                  {tab==='Finance' && (
                    <>
                      <div className="grid grid-cols-12 gap-4">
                        <InputBase col="col-span-2">
                          <Label>Life (months)</Label>
                          <input type="number" min={0} className={inputClass} value={form.life_months}
                            onChange={e=>setForm(f=>({...f, life_months:Number(e.target.value||0)}))}/>
                        </InputBase>

                        <InputBase col="col-span-5">
                          <Label>Reference (CV/PO/Invoice)</Label>
                          <input className={inputClass} value={form.reference||''}
                            onChange={e=>setForm(f=>({...f, reference:e.target.value}))}/>
                        </InputBase>

<InputBase col="col-span-5">
  <Label>Supplier</Label>
  <SearchableCombo
    placeholder="Search supplier…"
    fetchUrl="/app/api/lookups/vendors"
    selectedLabel={supplierLabel}
    onChange={(v) => {
      const idNum = v?.id === undefined || v?.id === null ? null : Number(v.id);
      setForm(f => ({
        ...f,
        supplier_id: idNum,                       // ← now number | null
        supplier_name: v?.label ?? null,
      }));
      setSupplierLabel(v?.label || '');
    }}
  />
</InputBase>

                        <InputBase col="col-span-3">
                          <Label>Purchased</Label>
                          <input type="date" className={dateClass} value={form.purchase_date||''}
                            onChange={e=>setForm(f=>({...f, purchase_date:e.target.value||null}))}/>
                        </InputBase>

                        <InputBase col="col-span-3">
                          <Label>In Service</Label>
                          <input type="date" className={dateClass} value={form.in_service_date||''}
                            onChange={e=>setForm(f=>({...f, in_service_date:e.target.value||null}))}/>
                        </InputBase>

                        <InputBase col="col-span-3">
                          <Label>Warranty Expires</Label>
                          <input type="date" className={dateClass} value={form.warranty_expires||''}
                            onChange={e=>setForm(f=>({...f, warranty_expires:e.target.value||null}))}/>
                        </InputBase>

                        <InputBase col="col-span-2">
                          <Label>VAT Inclusive</Label>
                          <div className="flex items-center gap-3">
                            <input type="checkbox" className={checkClass} checked={!!form.vat_inclusive}
                              onChange={e=>setForm(f=>({...f, vat_inclusive:e.target.checked}))}/>
                            <span className="text-sm text-gray-700">Yes</span>
                          </div>
                        </InputBase>

                        <InputBase col="col-span-2">
                          <Label>VAT %</Label>
                          <input type="number" min={0} max={100} step={0.01} className={inputClass} value={form.vat_rate??0}
                            onChange={e=>setForm(f=>({...f, vat_rate:Number(e.target.value||0)}))}/>
                        </InputBase>

                        <InputBase col="col-span-3">
                          <Label>Total Gross Amount</Label>
                          <input type="number" min={0} step={0.01} className={inputClass} value={form.gross_amount}
                            onChange={e=>setForm(f=>({...f, gross_amount:Number(e.target.value||0)}))}/>
                        </InputBase>

                        <InputBase col="col-span-4">
                          <Label>Total Net Cost</Label>
                          <input type="number" min={0} step={0.01} className={inputClass} value={form.total_net_cost??0}
                            onChange={e=>setForm(f=>({...f, total_net_cost:Number(e.target.value||0)}))}/>
                        </InputBase>

                        <InputBase col="col-span-3">
                          <Label>Unit Cost</Label>
                          <input type="number" className={inputClass} value={form.unit_cost??0} readOnly />
                        </InputBase>
                      </div>
                      <TabSaveBar/>
                    </>
                  )}

                  {tab==='Notes' && (
                    <>
                      <Label>Notes</Label>
                      <textarea className={`${inputClass} min-h-60`} value={form.notes||''}
                        onChange={e=>setForm(f=>({...f, notes:e.target.value}))}/>
                      <TabSaveBar/>
                    </>
                  )}

                  {tab==='History' && (
                    <>
                      <HistoryPanel table="asset_details" id={id||0}/>
                    </>
                  )}

                  {tab==='Picture' && (
                    <>
                      {isEdit
                        ? <PicturePanel id={id!} currentUrl={form.picture_path||undefined} onUploaded={(url)=>setForm(f=>({...f, picture_path:url}))}/>
                        : <div className="text-sm text-gray-600">Save the asset first to upload a picture.</div>
                      }
                      {isEdit && <TabSaveBar/>}
                    </>
                  )}

                  {tab==='Depreciation' && (
                    <div className="space-y-3">
                      {!isEdit && <div className="text-sm text-gray-600">Save the asset first to compute depreciation.</div>}
                      {isEdit && (
                        <div className="border rounded overflow-hidden">
                          <table className="w-full text-sm">
                            <thead className="bg-green-50">
                              <tr>
                                <th className="p-2 text-left">Period</th>
                                <th className="p-2 text-left">Month Sequence</th>
                                <th className="p-2 text-left">Actual Year</th>
                                <th className="p-2 text-left">Actual Month</th>
                                <th className="p-2 text-right">Depreciation Amount</th>
                              </tr>
                            </thead>
                            <tbody>
                              {schedBusy && <tr><td colSpan={5} className="p-4 text-center">Loading…</td></tr>}
                              {!schedBusy && sched.length===0 && <tr><td colSpan={5} className="p-4 text-center text-gray-500">No schedule rows</td></tr>}
                              {!schedBusy && sched.map((r,i)=>{
                                const d = new Date(r.period_start);
                                const yr = d.getUTCFullYear();
                                const mo = d.toLocaleString('en',{ month:'short' });
                                const seq = i+1;
                                return (
                                  <tr key={`${r.period}-${i}`} className={i%2?'bg-gray-50':''}>
                                    <td className="p-2">{r.period}</td>
                                    <td className="p-2">{seq}</td>
                                    <td className="p-2">{yr}</td>
                                    <td className="p-2">{mo}</td>
                                    <td className="p-2 text-right">
                                      {new Intl.NumberFormat(undefined,{ minimumFractionDigits:2, maximumFractionDigits:2 }).format(r.depreciation)}
                                    </td>
                                  </tr>
                                );
                              })}
                            </tbody>
                          </table>
                        </div>
                      )}
                    </div>
                  )}
                </>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

/** HISTORY PANEL */
function HistoryPanel({ table, id }:{ table:string; id:number }) {
  const [rows, setRows] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(()=>{ if (!id) return; (async()=>{
    setLoading(true);
    try { const r = await napi.get('/app/api/audit-logs', { params:{ table, id } }); setRows(r.data||[]); }
    finally { setLoading(false); }
  })(); }, [table, id]);

  if (loading) return <div>Loading…</div>;
  if (!rows.length) return <div className="text-sm text-gray-600">No history yet.</div>;

  return (
    <div className="border rounded overflow-hidden">
      <table className="w-full text-sm">
        <thead className="bg-green-50">
          <tr>
            <th className="p-2 text-left">Time Stamp</th>
            <th className="p-2 text-left">Description</th>
            <th className="p-2 text-left">Comment</th>
            <th className="p-2 text-left">User Name</th>
            <th className="p-2 text-left">Computer Name</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((r:any,i:number)=> (
            <tr key={i} className={i%2?'bg-gray-50':''}>
              <td className="p-2">{r.changed_at}</td>
              <td className="p-2">{r.action}</td>
              <td className="p-2">{r.summary||''}</td>
              <td className="p-2">{r.changed_by||''}</td>
              <td className="p-2">{r.workstation_id||''}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

/** PICTURE PANEL */
function PicturePanel({
  id, currentUrl, onUploaded
}:{ id:number; currentUrl?:string; onUploaded:(url:string)=>void }) {
  const [file, setFile] = useState<File|null>(null);
  const [busy, setBusy] = useState(false);

  async function upload() {
    if (!file) return;
    setBusy(true);
    try {
      const fd = new FormData(); fd.append('file', file);
      const r = await napi.post(`/app/api/assets/${id}/picture`, fd, { headers: { 'Content-Type':'multipart/form-data' }});
      onUploaded(r.data.picture_path);
    } finally { setBusy(false); setFile(null); }
  }

  return (
    <div className="space-y-3">
      {currentUrl && <img src={currentUrl} alt="Asset" className="max-h-64 rounded border shadow" />}
      <input type="file" accept="image/*" onChange={e=> setFile(e.target.files?.[0]||null)} />
      <button disabled={!file||busy} onClick={upload} className="px-4 py-2 bg-green-700 text-white rounded disabled:opacity-50">
        Upload
      </button>
    </div>
  );
}
