import { useEffect, useRef, useState, useMemo  } from 'react';
import napi from '@/utils/axiosnapi';
import { getCsrfToken, getCsrfTokenFor  } from '@/utils/csrf';
import Cookies from 'js-cookie';
import { PencilSquareIcon, TrashIcon, PlusCircleIcon, XMarkIcon } from '@heroicons/react/24/outline';



type DeprMethod = 'straight_line' | 'declining_balance' | 'units_of_production' | 'none';

const DEPR_OPTIONS = [
  { value: 'straight_line', label: 'Straight Line' },
  { value: 'declining_balance', label: 'Declining Balance' },
  { value: 'units_of_production', label: 'Units of Production' },
  { value: 'none', label: 'None' },
] as const;

type Paged<T> = { data: T[]; current_page: number; last_page: number };

type ClassRow = {
  id: number; class_code: string; class_name: string;
  default_life_months: number; default_depr_method: DeprMethod; residual_rate: number;
  is_active: boolean; sort_order: number;
};

type CategoryRow = {
  id: number;
  class_code: string;        // ⬅️ use class_code instead of class_id
  cat_code: string;
  cat_name: string;
  is_active: boolean;
  sort_order: number;
};

type TypeRow = {
  id: number;
  cat_code: string;               // ⬅️ use cat_code instead of category_id
  type_code: string;
  type_name: string;
  life_months_override?: number | null;
  depr_method_override?: DeprMethod | null;
  residual_rate_override?: number | null;
  is_active: boolean;
  sort_order: number;
};






// ===== Reusable searchable combobox (top-level, outside Taxonomy) =====
type ComboOption = { id: number | string; label: string };

function ComboBox({
  label,
  placeholder = "Search…",
  options,
  valueLabel,
  onInputChange,
  onSelect,
  required,
  readOnly = false,
}: {
  label: string;
  placeholder?: string;
  options: ComboOption[];
  valueLabel: string;
  onInputChange: (v: string) => void;
  onSelect: (opt: ComboOption) => void;
  required?: boolean;
  readOnly?: boolean;
}) {
  const [open, setOpen] = useState(false);
  const [activeIdx, setActiveIdx] = useState(0);
  const inputRef = useRef<HTMLInputElement>(null);
  const boxRef = useRef<HTMLDivElement>(null);

  // 🔒 De-dupe options we were given (protects UI even if data accidentally repeats)
  const opts = useMemo(() => {
    const seen = new Set<string>();
    const out: ComboOption[] = [];
    for (const o of options || []) {
      const k = `${String(o.id)}::${o.label}`;
      if (!seen.has(k)) { seen.add(k); out.push(o); }
    }
    return out;
  }, [options]);

  // Filter against the clean list
  const filtered = opts.filter(o =>
    o.label.toLowerCase().includes((valueLabel || "").toLowerCase())
  );

  // keep focus; don't lose cursor on state changes
  useEffect(() => {
    inputRef.current?.focus();
  }, [open, valueLabel]);

  // close only on outside click
  useEffect(() => {
    const onDocClick = (e: MouseEvent) => {
      if (!boxRef.current?.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener("mousedown", onDocClick);
    return () => document.removeEventListener("mousedown", onDocClick);
  }, []);

  useEffect(() => {
    const closeAll = () => setOpen(false);
    window.addEventListener('faims-close-combos', closeAll as unknown as EventListener);
    return () => window.removeEventListener('faims-close-combos', closeAll as unknown as EventListener);
  }, []);

  return (
    <div className="relative" ref={boxRef}>
      <label className="block text-sm mb-1">{label}</label>
      <div className="relative">
        <input
          ref={inputRef}
          className={`faims-field pr-9 ${readOnly ? 'bg-gray-100 cursor-not-allowed' : ''}`}
          placeholder={placeholder}
          value={valueLabel}
          readOnly={readOnly}
          onFocus={() => setOpen(true)}
          onChange={(e) => {
            onInputChange(e.target.value);
            setOpen(true);
            setActiveIdx(0);
          }}
          onKeyDown={(e) => {
            if (readOnly) return;
            if (!open) setOpen(true);
            if (e.key === "ArrowDown") {
              e.preventDefault();
              setActiveIdx(i => Math.min(i + 1, Math.max(filtered.length - 1, 0)));
            } else if (e.key === "ArrowUp") {
              e.preventDefault();
              setActiveIdx(i => Math.max(i - 1, 0));
            } else if (e.key === "Enter") {
              e.preventDefault();
              const pick = filtered[activeIdx];
              if (pick) {
                onSelect(pick);
                setOpen(false);
                requestAnimationFrame(() => inputRef.current?.focus());
              }
            } else if (e.key === "Escape") {
              setOpen(false);
              requestAnimationFrame(() => inputRef.current?.focus());
            }
          }}
          required={required}
        />
        <button
          type="button"
          className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
          onMouseDown={(e) => {
            if (readOnly) return;
            e.preventDefault();  // prevent blur
            setOpen(o => !o);
            requestAnimationFrame(() => inputRef.current?.focus());
          }}
          aria-label="Toggle options"
        >
          ▼
        </button>
      </div>

      {open && (
        <ul className="absolute z-50 mt-1 max-h-56 w-full overflow-auto rounded-md border bg-white shadow-lg">
          {filtered.length ? (
            filtered.map((o, idx) => (
              <li
                key={`${o.id}-${o.label}`}
                className={`px-3 py-2 cursor-pointer ${idx === activeIdx ? "bg-amber-100 font-semibold" : "hover:bg-amber-50"}`}
                onMouseDown={(e) => {
                  e.preventDefault();
                  onSelect(o);
                  setOpen(false);
                  requestAnimationFrame(() => inputRef.current?.focus());
                }}
              >
                {o.label}
              </li>
            ))
          ) : (
            <li className="px-3 py-2 text-sm text-gray-500">No matches</li>
          )}
        </ul>
      )}

      <p className="mt-1 text-xs text-gray-500">Type to search; pick an item to bind.</p>
    </div>
  );
}




import { Dialog, Transition } from '@headlessui/react'
import { Fragment } from 'react'

function ConfirmDialog({
  open, title, message, confirmText = 'Delete', cancelText = 'Cancel',
  onClose, onConfirm,
}: {
  open: boolean; title: string; message: string;
  confirmText?: string; cancelText?: string;
  onClose: () => void; onConfirm: () => void;
}) {
  return (
    <Transition appear show={open} as={Fragment}>
      <Dialog as="div" className="relative z-[100]" onClose={onClose}>
        <Transition.Child as={Fragment}
          enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100"
          leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
          <div className="fixed inset-0 bg-black/40" />
        </Transition.Child>

        <div className="fixed inset-0 overflow-y-auto">
          <div className="flex min-h-full items-center justify-center p-4">
            <Transition.Child as={Fragment}
              enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100"
              leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
              <Dialog.Panel className="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl">
                <Dialog.Title className="text-lg font-semibold text-gray-900">{title}</Dialog.Title>
                <div className="mt-2 text-sm text-gray-700">{message}</div>

                <div className="mt-6 flex justify-end gap-2">
                  <button
                    type="button"
                    className="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300"
                    onClick={onClose}
                  >{cancelText}</button>
                  <button
                    type="button"
                    className="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-500"
                    onClick={onConfirm}
                  >{confirmText}</button>
                </div>
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </div>
      </Dialog>
    </Transition>
  );
}

function InfoDialog({
  open, title = 'Notice', message, onClose,
}: { open: boolean; title?: string; message: string; onClose: () => void }) {
  return (
    <Transition appear show={open} as={Fragment}>
      <Dialog as="div" className="relative z-[100]" onClose={onClose}>
        <Transition.Child as={Fragment}
          enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100"
          leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
          <div className="fixed inset-0 bg-black/40" />
        </Transition.Child>

        <div className="fixed inset-0 overflow-y-auto">
          <div className="flex min-h-full items-center justify-center p-4">
            <Transition.Child as={Fragment}
              enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100"
              leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
              <Dialog.Panel className="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl">
                <Dialog.Title className="text-lg font-semibold text-gray-900">{title}</Dialog.Title>
                <div className="mt-2 text-sm text-gray-700">{message}</div>
                <div className="mt-6 flex justify-end">
                  <button
                    type="button"
                    className="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-500"
                    onClick={onClose}
                  >OK</button>
                </div>
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </div>
      </Dialog>
    </Transition>
  );
}







export default function Taxonomy() {
  
  
    const [activeTab, setActiveTab] = useState<'class'|'category'|'type'>('class');

    // Filters/selection across tabs
    const [selectedClass, setSelectedClass] = useState<ClassRow | null>(null);
    const [selectedCategory, setSelectedCategory] = useState<CategoryRow | null>(null);

    // Common list state
    const [isLoading, setIsLoading] = useState(false);
    const [perPage, setPerPage] = useState(10);
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [searchQuery, setSearchQuery] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [order, setOrder] = useState('code'); // mapped per tab
    const searchTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Data rows (per tab)
    const [classes, setClasses] = useState<ClassRow[]>([]);
    const [categories, setCategories] = useState<CategoryRow[]>([]);
    const [types, setTypes] = useState<TypeRow[]>([]);

    // Modal state
    const [modalOpen, setModalOpen] = useState(false);
    const [formMode, setFormMode] = useState<'add'|'edit'>('add');
    const [editId, setEditId] = useState<number | null>(null);

    // add this near other state hooks
    const [classSearchLabel, setClassSearchLabel] = useState('');
    // Full list for combobox (independent of paged table)
    const [classOptions, setClassOptions] = useState<ClassRow[]>([]);
    
    // Always-deduped view used by the Category combobox (defensive)
    const classOptionsDeduped = useMemo(() => {
      const seen = new Set<string>();
      return classOptions.filter(c => {
        const key = c.class_code?.trim();
        if (!key || seen.has(key)) return false;
        seen.add(key);
        return true;
      });
    }, [classOptions]);

    // Debug: verify counts in console
    useEffect(() => {
      const codes = classOptions.map(c => c.class_code);
      console.log(
        '[Taxonomy] classOptions total:',
        classOptions.length,
        'unique:',
        new Set(codes).size
      );
    }, [classOptions]);

    
    
    // Search label for the Category combobox inside the Type form
    const [categorySearchLabel, setCategorySearchLabel] = useState('');
    const [categoryOptions, setCategoryOptions] = useState<CategoryRow[]>([]);

    // --- Type tab FILTER combobox label ---
    const [typeFilterCategoryLabel, setTypeFilterCategoryLabel] = useState('');

    // --- Type MODAL combobox label ---
    const [typeModalCategoryLabel, setTypeModalCategoryLabel] = useState('');

    const cleanCode = (s: string) => s.toUpperCase().replace(/[^A-Z0-9-]/g, '');

  // Forms (per tab)
  const [classForm, setClassForm] = useState<Partial<ClassRow>>({
    class_code:'', class_name:'', default_life_months:60, default_depr_method:'straight_line', residual_rate:0, is_active:true, sort_order:0
  });
  const [catForm, setCatForm] = useState<Partial<CategoryRow>>({
    class_code: undefined, cat_code:'', cat_name:'', is_active:true, sort_order:0
  });
  const [typeForm, setTypeForm] = useState<Partial<TypeRow>>({
    cat_code: undefined as unknown as string,  // start undefined; will set on pick
    type_code:'', type_name:'', life_months_override: undefined, depr_method_override: undefined,
    residual_rate_override: undefined, is_active:true, sort_order:0
  });


// Alert/confirm state
const [infoOpen, setInfoOpen] = useState(false);
const [infoTitle, setInfoTitle] = useState('Notice');
const [infoMsg, setInfoMsg] = useState('');

const [confirmOpen, setConfirmOpen] = useState(false);
const [confirmTitle, setConfirmTitle] = useState('Are you sure?');
const [confirmMsg, setConfirmMsg] = useState('');
const confirmActionRef = useRef<null | (() => void)>(null);

const [catHasTypes, setCatHasTypes] = useState(false);

async function categoryHasTypes(catCode: string): Promise<boolean> {
  if (!catCode) return false;
  const res = await napi.get<Paged<TypeRow>>('/assets/types', {
    params: { per_page: 1, page: 1, cat_code: catCode }
  });
  return (res.data.data?.length ?? 0) > 0;
}




function showInfo(message: string, title = 'Notice') {
  setInfoTitle(title);
  setInfoMsg(message);
  setInfoOpen(true);
}



    useEffect(() => {
    const init = async () => {
        try {
        const setting = await napi.get('/settings/paginaterecs');
        setPerPage(Number(setting.data?.value) || 10);
        } catch {}
        await Promise.all([
        fetchList(1, activeTab, debouncedSearch),
        loadClassOptions(),                // ⬅️ add this
        loadCategoryOptions(),
        ]);
    };
    init();
    // eslint-disable-next-line
    }, []);




  // Debounce global search
  useEffect(() => {
    if (searchTimeout.current) clearTimeout(searchTimeout.current);
    searchTimeout.current = setTimeout(()=>setDebouncedSearch(searchQuery), 300);
    return () => { if (searchTimeout.current) clearTimeout(searchTimeout.current); };
  }, [searchQuery]);

  // Refetch on search/tab/order change
  useEffect(() => {
    fetchList(1, activeTab, debouncedSearch);
    // eslint-disable-next-line
  }, [debouncedSearch, activeTab, order, selectedClass?.id, selectedCategory?.id]);





  async function fetchList(page=1, tab=activeTab, search='') {
    setIsLoading(true);
    try {
      if (tab === 'class') {
        const res = await napi.get<Paged<ClassRow>>('/assets/classes', { params: { per_page: perPage, page, q: search, order: order === 'recent' ? 'recent' : 'class_code' }});
        setClasses(res.data.data); setCurrentPage(res.data.current_page); setTotalPages(res.data.last_page);
      } else if (tab === 'category') {
        const res = await napi.get<Paged<CategoryRow>>('/assets/categories', { params: { per_page: perPage, page, q: search, order: order==='recent'?'recent':'cat_code', class_code: selectedClass?.class_code || undefined  }});
        setCategories(res.data.data); setCurrentPage(res.data.current_page); setTotalPages(res.data.last_page);
      } else {
        const res = await napi.get<Paged<TypeRow>>('/assets/types', {
          params: {
            per_page: perPage,
            page,
            q: search,
            order: order === 'recent' ? 'recent' : 'type_code',
            cat_code: selectedCategory?.cat_code || undefined   // ⬅️ send cat_code
          }
        });
        setTypes(res.data.data); setCurrentPage(res.data.current_page); setTotalPages(res.data.last_page);
      }
    } finally { setIsLoading(false); }
  }


async function loadClassOptions() {
  // fetch all pages in a predictable order
  const per = 200;
  let page = 1;
  let last = 1;
  const rows: ClassRow[] = [];

  while (true) {
    const res = await napi.get<Paged<ClassRow>>('/assets/classes', {
      params: { per_page: per, page, order: 'class_code' }
    });
    rows.push(...(res.data.data ?? []));
    last = res.data.last_page ?? 1;
    if (page >= last) break;
    page++;
  }

  // DEDUPE by class_code so we never pass duplicates to the UI
  const byCode = new Map<string, ClassRow>();
  for (const r of rows) {
    if (!byCode.has(r.class_code)) byCode.set(r.class_code, r);
  }
  const dedup = Array.from(byCode.values());

  setClassOptions(dedup);
  return dedup;
}




async function loadCategoryOptions() {
  const first = await napi.get<Paged<CategoryRow>>('/assets/categories', {
    params: { per_page: 1000, page: 1, order: 'cat_code' }
  });
  let all: CategoryRow[] = first.data.data ?? [];
  const last = first.data.last_page ?? 1;
  for (let p = 2; p <= last; p++) {
    const res = await napi.get<Paged<CategoryRow>>('/assets/categories', {
      params: { per_page: 100, page: p, order: 'cat_code' }
    });
    all = all.concat(res.data.data ?? []);
  }
  setCategoryOptions(all);
  return all;                      // <— add this
}



async function openAdd() {
  // close any other open combobox dropdowns (toolbar etc.)
  window.dispatchEvent(new Event('faims-close-combos'));

  setFormMode('add');
  setEditId(null);

  if (activeTab === 'class') {
    setClassForm({
      class_code:'', class_name:'', default_life_months:60,
      default_depr_method:'straight_line', residual_rate:0,
      is_active:true, sort_order:0
    });
  }

  if (activeTab === 'category') {
    await loadClassOptions(); // make sure dropdown data is fresh
    setCatForm({
      class_code: selectedClass?.class_code, cat_code:'', cat_name:'',
      is_active:true, sort_order:0
    });

    //const has = await categoryHasTypes(row.cat_code);
    setCatHasTypes(false);
    // (this is the CLASS combobox used in Add Category)
    setClassSearchLabel(
      selectedClass ? `${selectedClass.class_code} • ${selectedClass.class_name}` : ''
    );
  }

  if (activeTab === 'type') {
    setTypeForm({
      cat_code: selectedCategory?.cat_code,     // ⬅️ prefill cat_code
      type_code:'', type_name:'', is_active:true, sort_order:0
    });

    const sel = selectedCategory
      ? `${selectedCategory.cat_code} • ${selectedCategory.cat_name}` : '';
    setTypeModalCategoryLabel(sel);
  }

  setModalOpen(true);
}





/*function openEdit(row: any) {
  // close any other open combobox dropdowns
  window.dispatchEvent(new Event('faims-close-combos'));

  setFormMode('edit');
  setEditId(row.id);

  if (activeTab === 'class') {
    setClassForm({ ...row });
  }

  if (activeTab === 'category') {
    setCatForm({ ...row });
    const cls = classes.find(c => c.class_code === row.class_code);
    setClassSearchLabel(cls ? `${cls.class_code} • ${cls.class_name}` : '');
  }

  if (activeTab === 'type') {
    setTypeForm({ ...row }); // row now has cat_code
    const cat = categoryOptions.find(c => c.cat_code === row.cat_code);
    setTypeModalCategoryLabel(cat ? `${cat.cat_code} • ${cat.cat_name}` : '');
  }


  setModalOpen(true);
}*/

// make it async so we can await the check
async function openEdit(row: ClassRow | CategoryRow | TypeRow) {
  // close any other open combobox dropdowns
  window.dispatchEvent(new Event('faims-close-combos'));

  setFormMode('edit');
  setEditId((row as any).id);

  if (activeTab === 'class') {
    setClassForm(row as ClassRow);
    setModalOpen(true);
    return;
  }

  if (activeTab === 'category') {
    const cat = row as CategoryRow;
    setCatForm(cat);

    const cls = classes.find(c => c.class_code === cat.class_code);
    setClassSearchLabel(cls ? `${cls.class_code} • ${cls.class_name}` : '');

    // ✅ row is in scope here; check if this category has types
    const has = await categoryHasTypes(cat.cat_code);
    setCatHasTypes(has);

    setModalOpen(true);
    return;
  }

  // type
  if (activeTab === 'type') {
    const t = row as TypeRow;
    setTypeForm(t);
    const cat = categoryOptions.find(c => c.cat_code === t.cat_code);
    setTypeModalCategoryLabel(cat ? `${cat.cat_code} • ${cat.cat_name}` : '');
    setModalOpen(true);
    return;
  }
}



  function closeModal(){ setModalOpen(false); }

  async function submit(e: React.FormEvent) {
    e.preventDefault(); await getCsrfTokenFor('lrwsis');

    try {
      if (activeTab === 'class') {
        const payload = classForm;
        if (formMode === 'edit' && editId) {
          await napi.patch(`/assets/classes/${editId}`, payload, { headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' }, withCredentials: true });
        } else {
          await napi.post(`/assets/classes`, payload, { headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' }, withCredentials: true });
        }
        const all = await loadClassOptions();                         // <— refresh
        setClassSearchLabel(''); // optional: clears any stale filter text
        const created = all.find(c => c.class_code === (classForm.class_code || ''));
        if (created) {
          setSelectedClass(created);
          setClassSearchLabel(`${created.class_code} • ${created.class_name}`);
        }      
            
      
      } else if (activeTab === 'category') {
        
        const payload = {
        ...catForm,
        class_code: catForm.class_code ?? selectedClass?.class_code
        };
        if (!payload.class_code) { alert('Select a Class first.'); return; }

        if (formMode === 'edit' && editId) {
          await napi.patch(`/assets/categories/${editId}`, payload, { headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' }, withCredentials: true });
        } else {
          await napi.post(`/assets/categories`, payload, { headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' }, withCredentials: true });
        }

        await loadCategoryOptions();
      } else {
        const payload = { ...typeForm, cat_code: typeForm.cat_code ?? selectedCategory?.cat_code };
        if (!payload.cat_code) { alert('Select a Category first.'); return; }
        if (formMode === 'edit' && editId) {
          await napi.patch(`/assets/types/${editId}`, payload, { headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' }, withCredentials: true });
        } else {
          await napi.post(`/assets/types`, payload, { headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' }, withCredentials: true });
        }
      }
      closeModal();
      await fetchList(currentPage);
    } catch (err:any) {
      //alert(err?.response?.data?.message || 'Save failed');
      const msg = err?.response?.data?.message || 'Save failed';
      // Example: “The class code has already been taken.”
      showInfo(msg, 'Validation Error');
    }
  }

  /*async function remove(id: number) {
    if (!confirm('Delete this record?')) return;
    await getCsrfTokenFor('lrwsis');
    const map = { class:'/assets/classes', category:'/assets/categories', type:'/assets/types' } as const;
    await napi.delete(`${map[activeTab]}/${id}`, { headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' }, withCredentials: true });
    const nextPage = (activeTab==='class'?classes:activeTab==='category'?categories:types).length === 1 && currentPage>1 ? currentPage-1 : currentPage;
    await fetchList(nextPage);
  }*/

async function remove(id: number) {
  // Resolve the row so we can show meaningful text
  const row = (activeTab === 'class'
    ? classes.find(x => x.id === id)
    : activeTab === 'category'
      ? categories.find(x => x.id === id)
      : types.find(x => x.id === id)) as any;

  const label =
    activeTab === 'class' ? `${row?.class_code} • ${row?.class_name}` :
    activeTab === 'category' ? `${row?.cat_code} • ${row?.cat_name}` :
    `${row?.type_code} • ${row?.type_name}`;

  setConfirmTitle('Confirm Deletion');
  setConfirmMsg(`You are about to delete “${label}”. This action cannot be undone.`);
  setConfirmOpen(true);

  confirmActionRef.current = async () => {
    setConfirmOpen(false);
    await getCsrfTokenFor('lrwsis');

    const map = { class:'/assets/classes', category:'/assets/categories', type:'/assets/types' } as const;
    try {
      await napi.delete(`${map[activeTab]}/${id}`, {
        headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' },
        withCredentials: true,
      });

      const lengthOfTab =
        activeTab === 'class' ? classes.length :
        activeTab === 'category' ? categories.length : types.length;

      const nextPage = lengthOfTab === 1 && currentPage > 1 ? currentPage - 1 : currentPage;
      await fetchList(nextPage);

      showInfo(`“${label}” has been deleted.`, 'Deleted');
    } catch (err:any) {
      const status = err?.response?.status;
      const msg    = err?.response?.data?.message;
      if (status === 409 && msg) {
        showInfo(msg, 'Blocked');
      } else {
        showInfo(msg || 'Delete failed', 'Error');
      }
    }
  };
}




  // Helpers for table rendering per tab
  const tableRows = activeTab==='class' ? classes : activeTab==='category' ? categories : types;

  return (
    <div className="p-6 bg-gray-50 min-h-full faims-ui">
      {/* Tabs */}
{/* Tabs */}
<nav className="mb-4 faims-tabs border-b border-gray-200">
  <ul className="flex gap-2">
    {(['class','category','type'] as const).map((t) => {
      const label = t === 'class' ? 'Class (Group)' : t === 'category' ? 'Category (Sub-Group)' : 'Type';
      const active = activeTab === t;
      return (
        <li key={t}>
          <button
            type="button"
            onClick={() => setActiveTab(t)}
            className={`tab ${active ? 'active' : ''}`}
          >
            {label}
          </button>
        </li>
      );
    })}
  </ul>
</nav>


      {/* Filters (left rail for parent selections) */}
      <div className="mb-3 flex items-center gap-2">
        <button onClick={openAdd} className="inline-flex items-center gap-2 bg-green-700 text-white px-4 py-2 rounded hover:bg-green-600">
          <PlusCircleIcon className="h-5 w-5" /> Add New
        </button>

        <input className="w-96 border rounded px-3 py-2" placeholder="Search…"
               value={searchQuery} onChange={e=>setSearchQuery(e.target.value)} />

        <select className="border rounded px-2 py-2" value={order} onChange={e=>setOrder(e.target.value)}>
          <option value="code">Sort: Code</option>
          <option value="name">Sort: Name</option>
          <option value="recent">Sort: Recent</option>
          <option value="sort">Sort: Manual</option>
        </select>

        {activeTab!=='class' && (
          <select className="border rounded px-2 py-2"
            value={selectedClass?.id ?? ''}
            onChange={async e=>{
              const id = Number(e.target.value||0);
              const row = classes.find(c=>c.id===id) || null;
              setSelectedClass(row); setSelectedCategory(null);
            }}>
            <option value="">Select Class…</option>
            {classes.map(c=><option key={c.id} value={c.id}>{c.class_code} • {c.class_name}</option>)}
          </select>
        )}

        {activeTab==='type' && (
            <div className="col-span-4">
    <ComboBox
      label="Category"
      placeholder="Search or select…"
      options={categoryOptions
        .filter(c => !selectedClass || c.class_code === selectedClass.class_code)
        .map(c => ({ id: c.id, label: `${c.cat_code} • ${c.cat_name}` }))}
      valueLabel={typeFilterCategoryLabel}
      onInputChange={setTypeFilterCategoryLabel}
      onSelect={(opt) => {
        setTypeFilterCategoryLabel(opt.label);
        const picked = categoryOptions.find(c => c.id === Number(opt.id)) || null;
        setSelectedCategory(picked);
      }}
      required={false}
    />
            </div>


        )}
      </div>

      {/* Loading */}
      {isLoading && (
        <div className="flex justify-center my-6">
          <div className="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"/>
        </div>
      )}

      {/* Table */}
      {!isLoading && (
        <div className="border bg-white rounded-xl shadow-sm faims-table">
          <table className="w-full text-left">
            <thead className="bg-amber-50 text-gray-800">
              {activeTab==='class' && (
                <tr>
                  <th className="p-2">#</th><th className="p-2">Code</th><th className="p-2">Name</th>
                  <th className="p-2">Life</th><th className="p-2">Method</th><th className="p-2">Residual%</th>
                  <th className="p-2">Active</th><th className="p-2 w-24 text-center">Actions</th>
                </tr>
              )}
              {activeTab==='category' && (
                <tr>
                  <th className="p-2">#</th><th className="p-2">Class</th><th className="p-2">Code</th><th className="p-2">Name</th>
                  <th className="p-2">Active</th><th className="p-2 w-24 text-center">Actions</th>
                </tr>
              )}
              {activeTab==='type' && (
                <tr>
                  <th className="p-2">#</th><th className="p-2">Category</th><th className="p-2">Code</th><th className="p-2">Name</th>
                  <th className="p-2">Life⚑</th><th className="p-2">Method⚑</th><th className="p-2">Residual⚑</th>
                  <th className="p-2">Active</th><th className="p-2 w-28 text-center">Actions</th>
                </tr>
              )}
            </thead>
            <tbody>
              {tableRows.map((r:any,i:number)=>(
                <tr
                    key={r.id}
                    className="border-t border-gray-100 even:bg-gray-50 hover:bg-emerald-50 transition-colors"
                    >
                  <td className="p-2">{(currentPage-1)*perPage + i + 1}</td>
                  {activeTab==='class' && <>
                    <td className="p-2">{r.class_code}</td>
                    <td className="p-2">{r.class_name}</td>
                    <td className="p-2">{r.default_life_months}</td>
                    <td className="p-2">{DEPR_OPTIONS.find(o=>o.value===r.default_depr_method)?.label}</td>
                    <td className="p-2">{r.residual_rate}</td>
                    <td className="p-2">{r.is_active?'Yes':'No'}</td>
                  </>}
                  {activeTab==='category' && <>
                    <td className="p-2">{r.class_code}</td>
                    <td className="p-2">{r.cat_code}</td>
                    <td className="p-2">{r.cat_name}</td>
                    <td className="p-2">{r.is_active ? 'Yes' : 'No'}</td>
                  </>}
                  {activeTab==='type' && <>
                    <td className="p-2">{r.cat_code}</td>
                    <td className="p-2">{r.type_code}</td>
                    <td className="p-2">{r.type_name}</td>
                    <td className="p-2">{r.life_months_override ?? '—'}</td>
                    <td className="p-2">{r.depr_method_override ? DEPR_OPTIONS.find(o=>o.value===r.depr_method_override)?.label : '—'}</td>
                    <td className="p-2">{r.residual_rate_override ?? '—'}</td>
                    <td className="p-2">{r.is_active ? 'Yes' : 'No'}</td>
                  </>}
                  <td className="p-2">
                    <div className="flex justify-center gap-2">
                      <button className="text-blue-600 hover:text-blue-800" onClick={()=>openEdit(r)} title="Edit">
                        <PencilSquareIcon className="h-5 w-5"/>
                      </button>
                      <button className="text-red-600 hover:text-red-800" onClick={()=>remove(r.id)} title="Delete">
                        <TrashIcon className="h-5 w-5"/>
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
              {tableRows.length===0 && <tr><td className="p-4 text-center text-gray-500" colSpan={9}>No records found</td></tr>}
            </tbody>
          </table>
        </div>
      )}

      {/* Pagination */}
      <div className="flex justify-between mt-4 text-sm text-gray-700">
        <button onClick={()=>fetchList(1)} disabled={currentPage===1} className="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50">First</button>
        <button onClick={()=>fetchList(currentPage-1)} disabled={currentPage===1} className="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50">Previous</button>
        <span className="self-center">Page {currentPage} of {totalPages}</span>
        <button onClick={()=>fetchList(currentPage+1)} disabled={currentPage===totalPages} className="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50">Next</button>
        <button onClick={()=>fetchList(totalPages)} disabled={currentPage===totalPages} className="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50">Last</button>
      </div>

      {/* Modal (Add/Edit) */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="absolute inset-0 bg-black/40" onClick={closeModal}/>
          <div className="relative bg-white rounded-xl shadow-xl w-full max-w-4xl mx-4">
            <div className="flex items-center justify-between p-4 border-b">
              <h3 className="text-lg font-semibold">
                {formMode==='edit' ? (activeTab==='class'?'Edit Class':activeTab==='category'?'Edit Category':'Edit Type')
                                   : (activeTab==='class'?'Add Class':activeTab==='category'?'Add Category':'Add Type')}
              </h3>
              <button onClick={closeModal} className="p-1 rounded hover:bg-gray-100"><XMarkIcon className="h-6 w-6 text-gray-600"/></button>
            </div>
            <form onSubmit={submit} className="p-6 grid grid-cols-12 gap-4">
              {activeTab==='class' && <>
                <div className="col-span-3"><label className="block text-sm mb-1">Class Code</label>


                  <input
                    value={classForm.class_code || ''}
                    onChange={e =>
                      setClassForm(f => ({
                        ...f,
                        class_code: cleanCode(e.target.value).slice(0, 8), // allow up to 25, UI can still use 5 if you want
                      }))
                    }
                    pattern="[A-Z0-9\-]{1,25}"
                    title='Use letters A–Z, digits 0–9 and "-" only'
                    className={`faims-field ${formMode==='edit' ? 'cursor-not-allowed' : ''}`}
                    required
                    readOnly={formMode === 'edit'}
                    maxLength={8}
                  />

                </div>
                <div className="col-span-5"><label className="block text-sm mb-1">Class Name</label>
                  <input value={classForm.class_name||''} 
                  onChange={e=>setClassForm(f=>({...f,class_name:e.target.value}))}
                         className="faims-field" required maxLength={150}/>
                </div>
                <div className="col-span-2"><label className="block text-sm mb-1">Life (mo)</label>
                  <input type="number" min={1} value={classForm.default_life_months??60} onChange={e=>setClassForm(f=>({...f,default_life_months:+e.target.value}))}
                         className="faims-field" required/>
                </div>
                <div className="col-span-4"><label className="block text-sm mb-1">Method</label>
                  <select value={classForm.default_depr_method||'straight_line'} onChange={e=>setClassForm(f=>({...f,default_depr_method: e.target.value as DeprMethod}))}
                          className="faims-field" required>
                    {DEPR_OPTIONS.map(o=><option key={o.value} value={o.value}>{o.label}</option>)}
                  </select>
                </div>
                <div className="col-span-2"><label className="block text-sm mb-1">Residual %</label>
                  <input type="number" min={0} max={100} step={0.01} value={classForm.residual_rate??0} onChange={e=>setClassForm(f=>({...f,residual_rate:+e.target.value}))}
                         className="faims-field" required/>
                </div>
                <div className="col-span-2 flex items-end gap-2">
                  <label className="inline-flex items-center gap-2">
                    <input type="checkbox" checked={!!classForm.is_active} onChange={e=>setClassForm(f=>({...f,is_active:e.target.checked}))}/>
                    <span className="text-sm">Active</span>
                  </label>
                </div>
              </>}

              {activeTab==='category' && <>
                <div className="col-span-4">
                <ComboBox
                  label="Class"
                  placeholder="Search or select class…"
                  required
                  readOnly={formMode === 'edit' && catHasTypes}
                  options={classOptionsDeduped.map(c => ({
                    id: c.id,
                    label: `${c.class_code} • ${c.class_name}`
                  }))}
                  valueLabel={classSearchLabel}
                  onInputChange={setClassSearchLabel}
                  onSelect={(opt) => {
                    if (formMode === 'edit' && catHasTypes) return;
                    setClassSearchLabel(opt.label);
                    const picked = classOptionsDeduped.find(c => c.id === Number(opt.id));
                    setCatForm(f => ({ ...f, class_code: picked?.class_code }));   // ⬅️ save code
                  }}
                />



                </div>



                <div className="col-span-3"><label className="block text-sm mb-1">Category Code</label>
                  <input value={catForm.cat_code||''} 
                    onChange={e=>setCatForm(f=>({...f,cat_code:e.target.value.toUpperCase().slice(0,8)}))}
                    className="faims-field" 
                    required 
                    maxLength={8}
                    readOnly={formMode==='edit' && catHasTypes}
                    />

                </div>
                <div className="col-span-5"><label className="block text-sm mb-1">Category Name</label>
                  <input value={catForm.cat_name||''} onChange={e=>setCatForm(f=>({...f,cat_name:e.target.value}))}
                         className="faims-field" required maxLength={150}/>
                </div>
                <div className="col-span-2 flex items-end gap-2">
                  <label className="inline-flex items-center gap-2">
                    <input type="checkbox" checked={!!catForm.is_active} onChange={e=>setCatForm(f=>({...f,is_active:e.target.checked}))}/>
                    <span className="text-sm">Active</span>
                  </label>
                </div>
              </>}

              {activeTab==='type' && <>
                <div className="col-span-4">
                <ComboBox
                  label="Category"
                  placeholder="Search or select category…"
                  options={categoryOptions
                    .filter(c => !selectedClass || c.class_code === selectedClass.class_code)
                    .map(c => ({ id: c.id, label: `${c.cat_code} • ${c.cat_name}` }))}
                  valueLabel={typeModalCategoryLabel}
                  onInputChange={setTypeModalCategoryLabel}
                  onSelect={(opt) => {
                    setTypeModalCategoryLabel(opt.label);
                    const picked = categoryOptions.find(c => c.id === Number(opt.id));
                    setTypeForm(f => ({ ...f, cat_code: picked?.cat_code! })); // ⬅️ save cat_code
                  }}
                  required
                />

                </div>

                <div className="col-span-3"><label className="block text-sm mb-1">Type Code</label>
                  <input value={typeForm.type_code||''} onChange={e=>setTypeForm(f=>({...f,type_code:e.target.value.toUpperCase().slice(0,20)}))}
                         className="faims-field" required maxLength={20}/>
                </div>
                <div className="col-span-5"><label className="block text-sm mb-1">Type Name</label>
                  <input value={typeForm.type_name||''} onChange={e=>setTypeForm(f=>({...f,type_name:e.target.value}))}
                         className="faims-field" required maxLength={150}/>
                </div>
                <div className="col-span-3"><label className="block text-sm mb-1">Life Override (mo)</label>
                  <input type="number" min={1} value={typeForm.life_months_override ?? ''} onChange={e=>setTypeForm(f=>({...f,life_months_override:e.target.value?+e.target.value:undefined}))}
                         className="faims-field" placeholder="inherit"/>
                </div>
                <div className="col-span-4"><label className="block text-sm mb-1">Method Override</label>
                  <select value={typeForm.depr_method_override ?? ''} onChange={e=>setTypeForm(f=>({...f,depr_method_override:e.target.value as DeprMethod || undefined}))}
                          className="faims-field">
                    <option value="">inherit</option>
                    {DEPR_OPTIONS.map(o=><option key={o.value} value={o.value}>{o.label}</option>)}
                  </select>
                </div>
                <div className="col-span-3"><label className="block text-sm mb-1">Residual % Override</label>
                  <input type="number" min={0} max={100} step={0.01} value={typeForm.residual_rate_override ?? ''} onChange={e=>setTypeForm(f=>({...f,residual_rate_override:e.target.value?+e.target.value:undefined}))}
                         className="faims-field" placeholder="inherit"/>
                </div>
                <div className="col-span-2 flex items-end gap-2">
                  <label className="inline-flex items-center gap-2">
                    <input type="checkbox" checked={!!typeForm.is_active} onChange={e=>setTypeForm(f=>({...f,is_active:e.target.checked}))}/>
                    <span className="text-sm">Active</span>
                  </label>
                </div>
                {/* Optional: show preview of next asset code when editing/adding a Type */}
                {formMode==='edit' && typeForm.cat_code && (
                  <div className="col-span-12 text-sm text-gray-600">
                    <button type="button" className="underline"
                      onClick={async()=>{
                        const id = editId!;
                        const res = await napi.get(`/assets/types/${id}/next-code`);
                        alert(`Next code: ${res.data.code}`);
                      }}>
                      Preview next asset code
                    </button>
                  </div>
                )}
              </>}

              <div className="col-span-12 flex justify-end gap-2 mt-2">
                <button type="button" onClick={closeModal} className="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
                <button type="submit" className="px-4 py-2 bg-green-700 text-white rounded hover:bg-green-600">{formMode==='edit'?'Update':'Submit'}</button>
              </div>
            </form>
          </div>
        </div>
      )}


<ConfirmDialog
  open={confirmOpen}
  title={confirmTitle}
  message={confirmMsg}
  confirmText="Yes, delete"
  cancelText="Cancel"
  onClose={() => setConfirmOpen(false)}
  onConfirm={() => confirmActionRef.current?.()}
/>

<InfoDialog
  open={infoOpen}
  title={infoTitle}
  message={infoMsg}
  onClose={() => setInfoOpen(false)}
/>






<style>{`
/* =========================
   FAIMS – Taxonomy Styles
   Scope: .faims-ui (safe to co-exist)
   Theme: Amber (nav/table) + Emerald (form)
   ========================= */

/* ---------- Tabs (amber) ---------- */
.faims-ui .faims-tabs .tab {
  background:#ffffff;
  color:#374151;                 /* gray-700 */
  padding:0.625rem 1.25rem;      /* px-5 py-2.5 */
  border:1px solid #e5e7eb;      /* gray-200 */
  border-bottom:none;
  border-top-left-radius:0.5rem;
  border-top-right-radius:0.5rem;
  position:relative;
  font-weight:700;
  transition:background-color .15s, color .15s, box-shadow .15s, border-color .15s;
}
.faims-ui .faims-tabs .tab:hover {
  background:#fef3c7;            /* amber-100 */
  color:#111827;                  /* gray-900 */
  box-shadow: inset 0 -3px 0 #f59e0b; /* amber-500 underline on hover */
}
.faims-ui .faims-tabs .tab.active {
  background:#ca8a04;            /* amber-600 */
  color:#ffffff;
  border-color:#ca8a04;
}
.faims-ui .faims-tabs .tab.active::after {
  content:"";
  position:absolute; left:0; right:0; bottom:-1px;
  height:4px; background:#ca8a04; /* amber-600 */
  border-top-left-radius:4px; border-top-right-radius:4px;
}

/* ---------- Table (amber) ---------- */
.faims-ui .faims-table table { border-collapse: separate; border-spacing: 0; }
.faims-ui .faims-table thead th {
  background:#fffbeb;            /* amber-50 */
  color:#1f2937;                  /* gray-800 */
}
.faims-ui .faims-table tbody tr td {
  background:#ffffff;
  transition: background-color .12s, font-weight .12s, color .12s;
}
.faims-ui .faims-table tbody tr:nth-child(even) td { 
  background:#fffbeb;            /* amber-50 */
}
.faims-ui .faims-table tbody tr:hover td { 
  background:#fef3c7;            /* amber-100 */
  font-weight:600;
  color:#111827;                  /* gray-900 */
}

/* ---------- Shadows + helpers (amber) ---------- */
.faims-ui .shadow-lg { box-shadow: 0 10px 15px -3px rgb(0 0 0 / .1), 0 4px 6px -4px rgb(0 0 0 / .1); }
.faims-ui .bg-amber-100 { background:#fef3c7; }
.faims-ui .hover\:bg-amber-50:hover { background:#fffbeb; }

/* ====================================
   FORM CONTROLS (emerald emphasis)
   Apply className="faims-field" to:
   <input>, <select>, <textarea>
   ==================================== */
.faims-ui .faims-field {
  width: 100%;
  border: 1.5px solid #e5e7eb;   /* gray-200 default */
  border-radius: 0.5rem;         /* rounded-lg */
  padding: 0.5rem 0.75rem;       /* px-3 py-2 */
  background: #ffffff;
  transition: border-color .15s, box-shadow .15s, background-color .15s, color .15s;
}
.faims-ui .faims-field:hover {
  border-color: #6ee7b7;         /* emerald-300 */
}
.faims-ui .faims-field:focus {
  outline: none;
  border-color: #059669;         /* emerald-600 */
  box-shadow: 0 0 0 3px rgba(16,185,129,.25); /* emerald ring */
}

/* Read-only & Disabled visuals */
.faims-ui .faims-field[readonly] {
  background: #f3f4f6;           /* gray-100 */
  color: #6b7280;                 /* gray-500 */
  cursor: not-allowed;
}
.faims-ui .faims-field:disabled {
  background: #f3f4f6;
  color: #9ca3af;
  cursor: not-allowed;
}

/* Selects: space for caret */
.faims-ui select.faims-field { padding-right: 2.25rem; }

/* Checkboxes / radios in emerald */
.faims-ui input[type="checkbox"],
.faims-ui input[type="radio"] {
  accent-color: #059669;         /* emerald-600 */
}

/* ---------- Dialog buttons (Headless UI) ---------- */
.faims-ui .btn-primary {
  background:#047857;            /* emerald-700 */
  color:#fff;
  padding:0.5rem 1rem;           /* px-4 py-2 */
  border-radius:0.5rem;
  transition: background-color .15s;
}
.faims-ui .btn-primary:hover { background:#059669; } /* emerald-600 */

.faims-ui .btn-secondary {
  background:#e5e7eb;            /* gray-200 */
  color:#111827;
  padding:0.5rem 1rem;
  border-radius:0.5rem;
  transition: background-color .15s;
}
.faims-ui .btn-secondary:hover { background:#d1d5db; } /* gray-300 */


`}</style>



    </div>
  );
}
