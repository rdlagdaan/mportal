import { useEffect, useRef, useState } from 'react';
import napi from '@/utils/axiosnapi';
import { getCsrfTokenFor } from '@/utils/csrf';
import Cookies from 'js-cookie';
import { PencilSquareIcon, TrashIcon, PlusCircleIcon, XMarkIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';

const COLS = 9; // update this if you add/remove columns
// --- helpers (put near other small utils) ---
function normalizePaged<T = any>(p: any): { data: T[]; current_page: number; last_page: number } {
  // shape A: { data: { current_page, data:[...], last_page, ... }, current_page, last_page }
  if (p?.data && Array.isArray(p.data.data)) {
    return {
      data: p.data.data as T[],
      current_page: Number(p.data.current_page ?? 1),
      last_page: Number(p.data.last_page ?? 1),
    };
  }
  // shape B: { data:[...], current_page, last_page }
  if (Array.isArray(p?.data)) {
    return {
      data: p.data as T[],
      current_page: Number(p.current_page ?? 1),
      last_page: Number(p.last_page ?? 1),
    };
  }
  return { data: [], current_page: 1, last_page: 1 };
}

const asArray = <T,>(x: any): T[] => (Array.isArray(x) ? (x as T[]) : []);




/* ------------------------------------------------------------------
   Employees Profile Module (List + Modal with tabs)
   - Matches FAIMS UI patterns you shared (tabs, zebra table, modal)
   - Uses your napi axios instance and CSRF helpers
   - Routes expected (adjust to your backend route names):
       GET    /hrsi/employees                 -> list (paged)
       POST   /hrsi/employees                 -> create basic employee
       GET    /hrsi/employees/:id             -> load detail for tabs
       PATCH  /hrsi/employees/:id             -> update base/personal info
       PATCH  /hrsi/employees/:id/contact     -> upsert contact info
       PATCH  /hrsi/employees/:id/govt        -> upsert government IDs
       GET    /hrsi/lookups                   -> get org_units, roles, ranks, job_statuses, positions, religions, civil_statuses
       GET    /hrsi/employees/:id/education   -> list rows; POST/PATCH/DELETE similar
       GET    /hrsi/employees/:id/appointments
     Swap the paths if your API is /app/api/...; the napi base likely prefixes /app.
------------------------------------------------------------------- */

/* ------------------- Common Types ------------------- */
export type Paged<T> = { data: T[]; current_page: number; last_page: number };

// Row for the listing grid
export type EmployeeRow = {
  id: number;
  employee_number: string;
  last_name: string;
  first_name: string;
  middle_name?: string | null;
  current_department?: string | null;
  current_role?: string | null; // comma-delimited list ok
  current_position?: string | null;
};

// Lookups for comboboxes
type ComboOption = { id: number | string; label: string };

export type OrgUnit = { id: number; code: string; name: string; type: string; parent_id?: number | null };
export type EmployeeRolle = { id: number; code: string; name: string }; // note: employee_rolles table
export type Rank = { id: number; code: string; name: string };
export type JobStatus = { id: number; code: string; name: string };
export type JobPosition = { id: number; code: string; name: string };
export type Religion = { id: number; code: string; name: string };
export type CivilStatus = { id: number; code: string; name: string };

/* ------------------- Detail Types ------------------- */
export type PersonalInfo = {
  employee_number: string;
  status: 'ACTIVE' | 'INACTIVE' | 'RETIRED' | 'RESIGNED';
  date_hired?: string | null; // ISO yyyy-mm-dd
  last_name: string;
  first_name: string;
  middle_name?: string | null;
  nick_name?: string | null;
  date_of_birth?: string | null; // ISO
  birth_place?: string | null;
  sex?: 'MALE'|'FEMALE'|'OTHER'|'PREFER_NOT_TO_SAY';
  citizenship?: string | null;
  religion_id?: number | null;
  civil_status_id?: number | null;
  blood_type?: string | null; // e.g., O-, O+, etc
  height_cm?: number | null;
  weight_kg?: number | null;
  photo_url?: string | null;      // current photo URL from server
  photo_file?: File | null;       // new upload (when user picks a file)

};

export type ContactInfo = {
  mobile_number?: string | null;
  landline?: string | null;
  personal_email?: string | null;
  company_email?: string | null;
  // city address
  city_address_line?: string | null;
  city_barangay?: string | null;
  city_town?: string | null;
  city_city?: string | null;
  city_zip?: string | null;
  city_country?: string | null;
  // provincial address
  prov_address_line?: string | null;
  prov_barangay?: string | null;
  prov_town?: string | null;
  prov_city?: string | null;
  prov_zip?: string | null;
  prov_country?: string | null;
};

export type GovernmentInfo = {
  sss?: string | null;
  tin?: string | null;
  pagibig?: string | null;
  philhealth?: string | null;
  prc?: string | null;
};

export type FamilyInfo = {
  father_name?: string | null;
  father_birthday?: string | null;
  father_occupation?: string | null;
  father_employer?: string | null;
  parent_address?: string | null;
  mother_name?: string | null;
  mother_birthday?: string | null;
  mother_occupation?: string | null;
  mother_employer?: string | null;
};

export type EducationRow = {
  id: number;
  school_level: string; // ELEMENTARY, HIGH SCHOOL, COLLEGE, VOCATIONAL, GRADUATE, etc.
  school_name: string;
  school_location?: string | null;
  date_from?: string | null; // ISO
  date_to?: string | null;   // ISO
  completed?: boolean;
  course?: string | null;
  honors?: string | null;
};

export type AppointmentRow = {
  id: number;
  position_id?: number | null;
  position_name?: string | null;
  org_unit_id?: number | null;
  org_unit_name?: string | null;
  effective_from?: string | null;
  effective_to?: string | null;
  classification?: 'FACULTY' | 'STAFF' | 'ADMIN' | string; // display only
  status?: string | null; // e.g., REGULAR, CONTRACTUAL
  salary?: number | null;
};

/* =====================================================
   Reusable searchable ComboBox
   (adapted from your reference code, kept identical UX)
===================================================== */
function ComboBox({
  label,
  placeholder = 'Search…',
  options,
  valueLabel,
  onInputChange,
  onSelect,
  required,
}: {
  label: string;
  placeholder?: string;
  options: ComboOption[];
  valueLabel: string;
  onInputChange: (v: string) => void;
  onSelect: (opt: ComboOption) => void;
  required?: boolean;
}) {
  const [open, setOpen] = useState(false);
  const [activeIdx, setActiveIdx] = useState(0);
  const inputRef = useRef<HTMLInputElement>(null);
  const boxRef = useRef<HTMLDivElement>(null);

  const filtered = options.filter((o) => o.label.toLowerCase().includes((valueLabel || '').toLowerCase()));

  useEffect(() => { inputRef.current?.focus(); }, [open, valueLabel]);

  useEffect(() => {
    const onDocClick = (e: MouseEvent) => { if (!boxRef.current?.contains(e.target as Node)) setOpen(false); };
    document.addEventListener('mousedown', onDocClick);
    return () => document.removeEventListener('mousedown', onDocClick);
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
          className="w-full border rounded px-3 py-2 pr-9"
          placeholder={placeholder}
          value={valueLabel}
          onFocus={() => setOpen(true)}
          onChange={(e) => { onInputChange(e.target.value); setOpen(true); setActiveIdx(0); }}
          onKeyDown={(e) => {
            if (!open) setOpen(true);
            if (e.key === 'ArrowDown') { e.preventDefault(); setActiveIdx((i) => Math.min(i + 1, Math.max(filtered.length - 1, 0))); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); setActiveIdx((i) => Math.max(i - 1, 0)); }
            else if (e.key === 'Enter') { e.preventDefault(); const pick = filtered[activeIdx]; if (pick) { onSelect(pick); setOpen(false); requestAnimationFrame(() => inputRef.current?.focus()); } }
            else if (e.key === 'Escape') { setOpen(false); requestAnimationFrame(() => inputRef.current?.focus()); }
          }}
          required={required}
        />
        <button type="button" className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
          onMouseDown={(e) => { e.preventDefault(); setOpen((o) => !o); requestAnimationFrame(() => inputRef.current?.focus()); }} aria-label="Toggle options">▼</button>
      </div>
      {open && (
        <ul className="absolute z-50 mt-1 max-h-56 w-full overflow-auto rounded-md border bg-white shadow-lg">
          {filtered.length ? (
            filtered.map((o, idx) => (
              <li key={`${o.id}`} className={`px-3 py-2 cursor-pointer ${idx === activeIdx ? 'bg-amber-100 font-semibold' : 'hover:bg-amber-50'}`}
                onMouseDown={(e) => { e.preventDefault(); onSelect(o); setOpen(false); requestAnimationFrame(() => inputRef.current?.focus()); }}>
                {o.label}
              </li>
            ))
          ) : (<li className="px-3 py-2 text-sm text-gray-500">No matches</li>)}
        </ul>
      )}
      <p className="mt-1 text-xs text-gray-500">Type to search; pick an item to bind.</p>
    </div>
  );
}

/* =====================================================
   Main Component
===================================================== */
export default function EmployeesProfile() {
 
  type EmployeeRow = {
    id: number;
    employee_number: string;
    last_name: string;
    first_name: string;
    middle_name?: string | null;
    current_department?: string | null;
    current_role?: string | null;
    current_position?: string | null;
  };

  const [employees, setEmployees] = useState<EmployeeRow[]>([]);
  //const rows = Array.isArray(employees) ? employees : [];
  /* --------------- List state --------------- */
  const [rows, setRows] = useState<EmployeeRow[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [perPage, setPerPage] = useState(10);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [searchQuery, setSearchQuery] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [order, setOrder] = useState<'empno'|'last'|'recent'>('empno');
  const searchTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

  /* --------------- Modal & tabs --------------- */
  const [modalOpen, setModalOpen] = useState(false);
  const [formMode, setFormMode] = useState<'add'|'edit'>('add');
  const [editId, setEditId] = useState<number | null>(null);
  const [activeTab, setActiveTab] = useState<'personal'|'contact'|'govt'|'family'|'education'|'dept'>('personal');

  /* --------------- Lookups --------------- */
  const [orgUnits, setOrgUnits] = useState<OrgUnit[]>([]);
  const [rolles, setRolles] = useState<EmployeeRolle[]>([]);
  const [ranks, setRanks] = useState<Rank[]>([]);
  const [jobStats, setJobStats] = useState<JobStatus[]>([]);
  const [positions, setPositions] = useState<JobPosition[]>([]);
  const [religions, setReligions] = useState<Religion[]>([]);
  const [civilStatuses, setCivilStatuses] = useState<CivilStatus[]>([]);

  /* --------------- Detail forms --------------- */
  const [personal, setPersonal] = useState<PersonalInfo>({
    employee_number: '', status: 'ACTIVE', last_name: '', first_name: '', middle_name: '',
    sex: 'MALE', blood_type: null,   photo_url: null, photo_file: null,
  });
  const [contact, setContact] = useState<ContactInfo>({});
  const [govt, setGovt] = useState<GovernmentInfo>({});
  const [family, setFamily] = useState<FamilyInfo>({});
  const [educRows, setEducRows] = useState<EducationRow[]>([]);
  const [deptRows, setDeptRows] = useState<AppointmentRow[]>([]);

  // preview existing photo url if you have one on `personal.photo_url`
  const [photoPreview, setPhotoPreview] = useState<string | null>(personal.photo_url ?? null);
  const photoInputRef = useRef<HTMLInputElement>(null);


  /* --------------- Init --------------- */
  useEffect(() => {
    const init = async () => {
      try {
        // optional: load perPage setting from server like your other module
        const setting = await napi.get('/settings/paginaterecs');
        setPerPage(Number(setting.data?.value) || 10);
      } catch {}
      await Promise.all([fetchList(1, debouncedSearch), loadLookups()]);
    };
    init();
    // eslint-disable-next-line
  }, []);

  // Debounce global search
  useEffect(() => {
    if (searchTimeout.current) clearTimeout(searchTimeout.current);
    searchTimeout.current = setTimeout(() => setDebouncedSearch(searchQuery), 300);
    return () => { if (searchTimeout.current) clearTimeout(searchTimeout.current); };
  }, [searchQuery]);





  // Refetch on search/order change
  useEffect(() => { fetchList(1, debouncedSearch); }, [debouncedSearch, order]);

  async function loadLookups() {
    try {
      const res = await napi.get('/hrsi/lookups');
      setOrgUnits(res.data.org_units || []);
      setRolles(res.data.employee_rolles || []);
      setRanks(res.data.ranks || []);
      setJobStats(res.data.job_statuses || []);
      setPositions(res.data.job_positions || []);
      setReligions(res.data.religions || []);
      setCivilStatuses(res.data.civil_statuses || []);
    } catch (e) { /* non-fatal */ }
  }


// open file dialog
const pickPhoto = () => photoInputRef.current?.click();

// when user picks a file
function onPhotoChange(e: React.ChangeEvent<HTMLInputElement>) {
  const file = e.target.files?.[0];
  if (!file) return;
  setPersonal(p => ({ ...p, photo_file: file }));
  const url = URL.createObjectURL(file);
  setPhotoPreview(prev => {
    if (prev?.startsWith('blob:')) URL.revokeObjectURL(prev);
    return url;
  });
}



async function fetchEmployees(page = 1, search = '') {
  setIsLoading(true);
  try {
    const res = await napi.get('/hrsi/employees', {
      params: { per_page: perPage, page, q: search, order: 'name' },
      withCredentials: true, // using Sanctum cookie
    });

    // res.data could be either shape; normalize it
    const paged = normalizePaged<EmployeeRow>(res.data);
    setEmployees(asArray<EmployeeRow>(paged.data));
    setCurrentPage(paged.current_page || 1);
    setTotalPages(paged.last_page || 1);
  } catch (e) {
    console.error('employees fetch failed', e);
    setEmployees([]); setCurrentPage(1); setTotalPages(1);
  } finally {
    setIsLoading(false);
  }
}


useEffect(() => {
  fetchEmployees(1, debouncedSearch);
  // eslint-disable-next-line react-hooks/exhaustive-deps
}, [debouncedSearch, perPage]);


const gotoPage = (p: number) => {
  const page = Math.max(1, Math.min(p, totalPages || 1));
  setCurrentPage(page);
  fetchEmployees(page, debouncedSearch);
};



  /* -------------------- List CRUD -------------------- */
  async function fetchList(page = 1, search = '') {
    setIsLoading(true);
    try {
      const res = await napi.get<Paged<EmployeeRow>>('/hrsi/employees', {
        params: { per_page: perPage, page, q: search, order }
      });
      setRows(res.data.data); setCurrentPage(res.data.current_page); setTotalPages(res.data.last_page);
    } finally {
      setIsLoading(false);
    }
  }

function openAdd() {
  window.dispatchEvent(new Event('faims-close-combos'));
  setFormMode('add'); setEditId(null); setActiveTab('personal');

  setPersonal({
    employee_number: '',
    status: 'ACTIVE',
    last_name: '',
    first_name: '',
    middle_name: '',
    sex: 'MALE',
    blood_type: null,

    // ⬇️ new
    photo_url: null,
    photo_file: null,
  } as PersonalInfo);

  setPhotoPreview(null);          // ⬅️ clear preview

  setContact({}); setGovt({}); setFamily({}); setEducRows([]); setDeptRows([]);
  setModalOpen(true);
}


async function openEdit(row: EmployeeRow) {
  window.dispatchEvent(new Event('faims-close-combos'));
  setFormMode('edit'); setEditId(row.id); setActiveTab('personal');
  setModalOpen(true);

  try {
    const res = await napi.get(`/hrsi/employees/${row.id}`);
    // adjust if your shape differs
    const p = res.data.personal as PersonalInfo;

    // If your API uses a different field (avatar_url, image_url, photo, etc.), map it here:
    const serverPhotoUrl =
      p?.photo_url ??
      (res.data.personal?.avatar_url ?? res.data.personal?.image_url ?? res.data.personal?.photo ?? null);

    setPersonal({
      ...p,
      photo_url: serverPhotoUrl ?? null,
      photo_file: null,
    });

    setContact(res.data.contact as ContactInfo);
    setGovt(res.data.govt as GovernmentInfo);
    setFamily(res.data.family as FamilyInfo);
    setEducRows(res.data.education || []);
    setDeptRows(res.data.appointments || []);

    setPhotoPreview(serverPhotoUrl ?? null);   // ⬅️ show current photo
  } catch (e) {
    // handle error (toast/log)
  }
}

async function savePersonal() {
  const hasFile = !!personal.photo_file;
  if (hasFile) {
    const fd = new FormData();
    // append scalar fields
    Object.entries(personal).forEach(([k, v]) => {
      if (k === 'photo_file') return;
      fd.append(k, v == null ? '' : String(v));
    });
    fd.append('photo', personal.photo_file as File);

    await napi.post(`/hrsi/employees/${editId}/personal`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  } else {
    await napi.post(`/hrsi/employees/${editId}/personal`, personal);
  }
}


  function closeModal() { setModalOpen(false); }

  async function saveActiveTab(e: React.FormEvent) {
    e.preventDefault();
    await getCsrfTokenFor('lrwsis');
    const headers = { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' } as const;
    try {
      const id = editId;
      if (activeTab === 'personal') {
        if (formMode === 'add') {
          const created = await napi.post('/hrsi/employees', personal, { headers, withCredentials: true });
          setEditId(created.data.id); setFormMode('edit');
        } else if (id) {
          await napi.patch(`/hrsi/employees/${id}`, personal, { headers, withCredentials: true });
        }
      } else if (activeTab === 'contact' && editId) {
        await napi.patch(`/hrsi/employees/${editId}/contact`, contact, { headers, withCredentials: true });
      } else if (activeTab === 'govt' && editId) {
        await napi.patch(`/hrsi/employees/${editId}/govt`, govt, { headers, withCredentials: true });
      } else if (activeTab === 'family' && editId) {
        await napi.patch(`/hrsi/employees/${editId}/family`, family, { headers, withCredentials: true });
      }
      if (activeTab === 'personal') await fetchList(currentPage);
      alert('Saved');
    } catch (err: any) {
      alert(err?.response?.data?.message || 'Save failed');
    }
  }

  async function remove(id: number) {
    if (!confirm('Delete this employee?')) return;
    await getCsrfTokenFor('lrwsis');
    await napi.delete(`/hrsi/employees/${id}`, { headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' }, withCredentials: true });
    const nextPage = rows.length === 1 && currentPage > 1 ? currentPage - 1 : currentPage;
    await fetchList(nextPage);
  }

  /* -------------------- Education mini-CRUD -------------------- */
  const [eduModalOpen, setEduModalOpen] = useState(false);
  const [eduForm, setEduForm] = useState<Partial<EducationRow>>({ completed: true });
  const [eduMode, setEduMode] = useState<'add'|'edit'>('add');
  const [eduEditId, setEduEditId] = useState<number | null>(null);

  function openEduAdd() { setEduMode('add'); setEduEditId(null); setEduForm({ completed: true }); setEduModalOpen(true); }
  function openEduEdit(r: EducationRow) { setEduMode('edit'); setEduEditId(r.id); setEduForm(r); setEduModalOpen(true); }

  async function saveEdu(e: React.FormEvent) {
    e.preventDefault(); if (!editId) return;
    await getCsrfTokenFor('lrwsis');
    const headers = { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' } as const;
    if (eduMode === 'add') {
      const res = await napi.post(`/hrsi/employees/${editId}/education`, eduForm, { headers, withCredentials: true });
      setEducRows((rows) => [res.data, ...rows]);
    } else if (eduEditId) {
      const res = await napi.patch(`/hrsi/employees/${editId}/education/${eduEditId}`, eduForm, { headers, withCredentials: true });
      setEducRows((rows) => rows.map((r) => (r.id === eduEditId ? res.data : r)));
    }
    setEduModalOpen(false);
  }

  async function removeEdu(id: number) {
    if (!editId) return; if (!confirm('Delete this education record?')) return;
    await getCsrfTokenFor('lrwsis');
    await napi.delete(`/hrsi/employees/${editId}/education/${id}`, { headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' }, withCredentials: true });
    setEducRows((rows) => rows.filter((r) => r.id !== id));
  }

  /* -------------------- Department/Appointment mini-CRUD -------------------- */
  const [apptModalOpen, setApptModalOpen] = useState(false);
  const [apptForm, setApptForm] = useState<Partial<AppointmentRow>>({});
  const [apptMode, setApptMode] = useState<'add'|'edit'>('add');
  const [apptEditId, setApptEditId] = useState<number | null>(null);
  const [orgLabel, setOrgLabel] = useState('');
  const [posLabel, setPosLabel] = useState('');

  function openApptAdd() { setApptMode('add'); setApptEditId(null); setApptForm({}); setOrgLabel(''); setPosLabel(''); setApptModalOpen(true); }
  function openApptEdit(r: AppointmentRow) { setApptMode('edit'); setApptEditId(r.id); setApptForm(r); setOrgLabel(r.org_unit_name || ''); setPosLabel(r.position_name || ''); setApptModalOpen(true); }

  async function saveAppt(e: React.FormEvent) {
    e.preventDefault(); if (!editId) return;
    await getCsrfTokenFor('lrwsis');
    const headers = { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' } as const;
    if (apptMode === 'add') {
      const res = await napi.post(`/hrsi/employees/${editId}/appointments`, apptForm, { headers, withCredentials: true });
      setDeptRows((rows) => [res.data, ...rows]);
    } else if (apptEditId) {
      const res = await napi.patch(`/hrsi/employees/${editId}/appointments/${apptEditId}`, apptForm, { headers, withCredentials: true });
      setDeptRows((rows) => rows.map((r) => (r.id === apptEditId ? res.data : r)));
    }
    setApptModalOpen(false);
  }

  async function removeAppt(id: number) {
    if (!editId) return; if (!confirm('Delete this appointment?')) return;
    await getCsrfTokenFor('lrwsis');
    await napi.delete(`/hrsi/employees/${editId}/appointments/${id}`, { headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' }, withCredentials: true });
    setDeptRows((rows) => rows.filter((r) => r.id !== id));
  }

  /* -------------------- Render -------------------- */
  const tabs: { key: typeof activeTab; label: string }[] = [
    { key: 'personal', label: 'Personal Info' },
    { key: 'contact', label: 'Contact Info' },
    { key: 'govt', label: 'Government Info' },
    { key: 'family', label: 'Family Info' },
    { key: 'education', label: 'Education Info' },
    { key: 'dept', label: 'Department Info' },
  ];

  return (
    <div className="p-6 bg-gray-50 min-h-full faims-ui">
      {/* Header widgets can go here */}

      {/* Toolbar */}
      <div className="mb-3 flex items-center gap-2">
        <button onClick={openAdd} className="inline-flex items-center gap-2 bg-green-700 text-white px-4 py-2 rounded hover:bg-green-600">
          <PlusCircleIcon className="h-5 w-5" /> Add New
        </button>

        <div className="relative">
          <MagnifyingGlassIcon className="h-5 w-5 absolute left-2 top-1/2 -translate-y-1/2 text-gray-500" />
          <input className="w-96 border rounded pl-8 pr-3 py-2" placeholder="Search by name or employee no."
                 value={searchQuery} onChange={(e)=>setSearchQuery(e.target.value)} />
        </div>

        <select className="border rounded px-2 py-2" value={order} onChange={e=>setOrder(e.target.value as any)}>
          <option value="empno">Sort: Employee #</option>
          <option value="last">Sort: Last name</option>
          <option value="recent">Sort: Recent</option>
        </select>
      </div>

      {/* Loading */}
      {isLoading && (
        <div className="flex justify-center my-6"><div className="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"/></div>
      )}

      {/* Table */}
      
      {!isLoading && (
        
        <div className="border bg-white rounded-xl shadow-sm faims-table">
          <table className="w-full text-left">
            <thead className="bg-amber-50 text-gray-800">
              <tr>
                <th className="p-2">#</th>
                <th className="p-2">Employee Number</th>
                <th className="p-2">Last Name</th>
                <th className="p-2">First Name</th>
                <th className="p-2">Middle Name</th>
                <th className="p-2">Current Department</th>
                <th className="p-2">Role</th>
                <th className="p-2">Current Position</th>
                <th className="p-2 w-24 text-center">Actions</th>
              </tr>
            </thead>
 <tbody>
    {(Array.isArray(employees) ? employees : []).map((r, i) => (
      <tr key={r.id} className="border-t border-gray-100 even:bg-gray-50 hover:bg-emerald-50">
        <td className="p-2">{(currentPage - 1) * perPage + i + 1}</td>
        <td className="p-2">{r.employee_number}</td>
        <td className="p-2">{r.last_name}</td>
        <td className="p-2">{r.first_name}</td>
        <td className="p-2">{r.middle_name || '—'}</td>
        <td className="p-2">{r.current_department || '—'}</td>
        <td className="p-2">{r.current_role || '—'}</td>
        <td className="p-2">{r.current_position || '—'}</td>

        {/* Actions */}
        <td className="p-2">
          <div className="flex justify-center gap-2">
            <button
              className="text-blue-600 hover:text-blue-800"
              onClick={() => openEdit(r)}          // <-- your edit handler
              title="Edit"
            >
              <PencilSquareIcon className="h-5 w-5" />
            </button>
            <button
              className="text-red-600 hover:text-red-800"
              onClick={() => remove(r.id)}         // <-- your delete handler
              title="Delete"
            >
              <TrashIcon className="h-5 w-5" />
            </button>
          </div>
        </td>
      </tr>
    ))}

    {(Array.isArray(employees) ? employees : []).length === 0 && !isLoading && (
      <tr>
        <td className="p-4 text-center text-gray-500" colSpan={COLS}>
          No records found
        </td>
      </tr>
    )}
  </tbody>
          </table>
        </div>
      )}

      {/* Pagination */}
      <div className="flex justify-between mt-4 text-sm text-gray-700">
        <button onClick={() => gotoPage(1)} disabled={currentPage===1} className="px-3 py-1 bg-gray-200 rounded disabled:opacity-50">First</button>
        <button onClick={() => gotoPage(currentPage-1)} disabled={currentPage===1} className="px-3 py-1 bg-gray-200 rounded disabled:opacity-50">Previous</button>
        <span className="self-center">Page {currentPage} of {totalPages}</span>
        <button onClick={() => gotoPage(currentPage+1)} disabled={currentPage===totalPages} className="px-3 py-1 bg-gray-200 rounded disabled:opacity-50">Next</button>
        <button onClick={() => gotoPage(totalPages)} disabled={currentPage===totalPages} className="px-3 py-1 bg-gray-200 rounded disabled:opacity-50">Last</button>
      </div>


      {/* Modal (Add/Edit) */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="absolute inset-0 bg-black/40" onClick={closeModal}/>
          <div className="relative bg-white rounded-xl shadow-xl w-full max-w-6xl mx-4">
            <div className="flex items-center justify-between p-4 border-b">
              <h3 className="text-lg font-semibold">{formMode==='edit' ? 'Update Employee' : 'Add Employee'}</h3>
              <button onClick={closeModal} className="p-1 rounded hover:bg-gray-100"><XMarkIcon className="h-6 w-6 text-gray-600"/></button>
            </div>

            {/* Tabs */}
            <nav className="px-4 pt-2 faims-tabs border-b border-gray-200">
              <ul className="flex gap-2 flex-wrap">
                {tabs.map(t => (
                  <li key={t.key}>
                    <button type="button" onClick={()=>setActiveTab(t.key)} className={`tab ${activeTab===t.key ? 'active' : ''}`}>{t.label}</button>
                  </li>
                ))}
              </ul>
            </nav>

            {/* Forms */}
            <form onSubmit={saveActiveTab} className="p-6 grid grid-cols-12 gap-4 max-h-[70vh] overflow-auto">
              {activeTab==='personal' && (
<>
  {/* Row 1–2: Photo (left) + two stacked rows (right) */}
  <div className="col-span-12">
    <div className="grid grid-cols-12 gap-4">
      {/* Photo card */}
      <div className="col-span-3">
        <div className="border rounded-xl bg-white p-4 flex flex-col items-center">
          <img
            src={photoPreview || '/images/avatar-placeholder.png'}
            alt="Employee"
            className="h-40 w-40 rounded-full object-cover border"
          />
          <input
            ref={photoInputRef}
            type="file"
            accept="image/*"
            className="hidden"
            onChange={onPhotoChange}
          />
          <button
            type="button"
            onClick={pickPhoto}
            className="mt-3 px-3 py-1.5 rounded bg-slate-700 text-white hover:bg-slate-600"
          >
            Update Photo
          </button>
        </div>
      </div>

      {/* Right side fields */}
      <div className="col-span-9">
        <div className="grid grid-cols-12 gap-4">
          {/* Row A: Emp# / Status / Date Hired */}
          <div className="col-span-4">
            <label className="block text-sm mb-1">Employee Number</label>
            <input
              className="w-full border rounded px-3 py-2 font-mono"
              value={personal.employee_number}
              onChange={e=>setPersonal(p=>({...p, employee_number:e.target.value}))}
              required
            />
          </div>
          <div className="col-span-4">
            <label className="block text-sm mb-1">Status</label>
            <select
              className="w-full border rounded px-2 py-2"
              value={personal.status}
              onChange={e=>setPersonal(p=>({...p, status:e.target.value as any}))}
            >
              <option>ACTIVE</option>
              <option>INACTIVE</option>
              <option>RETIRED</option>
              <option>RESIGNED</option>
            </select>
          </div>
          <div className="col-span-4">
            <label className="block text-sm mb-1">Date Hired</label>
            <input
              type="date"
              className="w-full border rounded px-3 py-2"
              value={personal.date_hired || ''}
              onChange={e=>setPersonal(p=>({...p, date_hired:e.target.value || null}))}
            />
          </div>

          {/* Row B: Last / First / Middle */}
          <div className="col-span-4">
            <label className="block text-sm mb-1">Last Name</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={personal.last_name}
              onChange={e=>setPersonal(p=>({...p,last_name:e.target.value}))}
              required
            />
          </div>
          <div className="col-span-4">
            <label className="block text-sm mb-1">First Name</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={personal.first_name}
              onChange={e=>setPersonal(p=>({...p,first_name:e.target.value}))}
              required
            />
          </div>
          <div className="col-span-4">
            <label className="block text-sm mb-1">Middle Name</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={personal.middle_name || ''}
              onChange={e=>setPersonal(p=>({...p,middle_name:e.target.value}))}
            />
          </div>
        </div>
      </div>
    </div>
  </div>

  {/* Row 3: Nick / Birth Date / Birth Place / Gender */}
  <div className="col-span-12 grid grid-cols-12 gap-4">
    <div className="col-span-3">
      <label className="block text-sm mb-1">Nick Name</label>
      <input
        className="w-full border rounded px-3 py-2"
        value={personal.nick_name || ''}
        onChange={e=>setPersonal(p=>({...p,nick_name:e.target.value}))}
      />
    </div>
    <div className="col-span-3">
      <label className="block text-sm mb-1">Birth Date</label>
      <input
        type="date"
        className="w-full border rounded px-3 py-2"
        value={personal.date_of_birth || ''}
        onChange={e=>setPersonal(p=>({...p,date_of_birth:e.target.value || null}))}
      />
    </div>
    <div className="col-span-3">
      <label className="block text-sm mb-1">Birth Place</label>
      <input
        className="w-full border rounded px-3 py-2"
        value={personal.birth_place || ''}
        onChange={e=>setPersonal(p=>({...p,birth_place:e.target.value}))}
      />
    </div>
    <div className="col-span-3">
      <label className="block text-sm mb-1">Gender</label>
      <select
        className="w-full border rounded px-2 py-2"
        value={personal.sex}
        onChange={e=>setPersonal(p=>({...p,sex:e.target.value as any}))}
      >
        <option value="MALE">MALE</option>
        <option value="FEMALE">FEMALE</option>
        <option value="OTHER">OTHER</option>
        <option value="PREFER_NOT_TO_SAY">PREFER NOT TO SAY</option>
      </select>
    </div>
  </div>

  {/* Row 4: Citizenship / Religion / Civil Status / Blood Type */}
  <div className="col-span-12 grid grid-cols-12 gap-4">
    <div className="col-span-3">
      <label className="block text-sm mb-1">Citizenship</label>
      <input
        className="w-full border rounded px-3 py-2"
        value={personal.citizenship||''}
        onChange={e=>setPersonal(p=>({...p,citizenship:e.target.value}))}
      />
    </div>
    <div className="col-span-3">
      <ComboBox
        label="Religion"
        options={religions.map(r=>({id:r.id,label:`${r.name}`}))}
        valueLabel={religions.find(r=>r.id===personal.religion_id)?.name || ''}
        onInputChange={()=>{}}
        onSelect={(opt)=>setPersonal(p=>({...p,religion_id:Number(opt.id)}))}
      />
    </div>
    <div className="col-span-3">
      <ComboBox
        label="Civil Status"
        options={civilStatuses.map(r=>({id:r.id,label:`${r.name}`}))}
        valueLabel={civilStatuses.find(r=>r.id===personal.civil_status_id)?.name || ''}
        onInputChange={()=>{}}
        onSelect={(opt)=>setPersonal(p=>({...p,civil_status_id:Number(opt.id)}))}
      />
    </div>
    <div className="col-span-3">
      <label className="block text-sm mb-1">Blood Type</label>
      <select
        className="w-full border rounded px-2 py-2"
        value={personal.blood_type || ''}
        onChange={e=>setPersonal(p=>({...p,blood_type:e.target.value||null}))}
      >
        <option value="">—</option>
        {['O+','O-','A+','A-','B+','B-','AB+','AB-'].map(b=> <option key={b} value={b}>{b}</option>)}
      </select>
    </div>
  </div>

  {/* Row 5: Height / Weight */}
  <div className="col-span-12 grid grid-cols-12 gap-4">
    <div className="col-span-3">
      <label className="block text-sm mb-1">Height (cm)</label>
      <input
        type="number"
        className="w-full border rounded px-3 py-2"
        value={personal.height_cm ?? ''}
        onChange={e=>setPersonal(p=>({...p,height_cm:e.target.value?+e.target.value:null}))}
      />
    </div>
    <div className="col-span-3">
      <label className="block text-sm mb-1">Weight (kg)</label>
      <input
        type="number"
        className="w-full border rounded px-3 py-2"
        value={personal.weight_kg ?? ''}
        onChange={e=>setPersonal(p=>({...p,weight_kg:e.target.value?+e.target.value:null}))}
      />
    </div>
  </div>

  {/* Submit */}
  <div className="col-span-12 flex justify-end gap-2 mt-2">
    <button type="submit" className="px-4 py-2 bg-green-700 text-white rounded hover:bg-green-600">
      {formMode==='edit' ? 'Update' : 'Submit'}
    </button>
  </div>
</>

              )}

              {activeTab==='contact' && (
                <>
                  <div className="col-span-6">
                    <label className="block text-sm mb-1">Mobile Number</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.mobile_number||''} onChange={e=>setContact(c=>({...c,mobile_number:e.target.value}))}/>
                  </div>
                  <div className="col-span-6">
                    <label className="block text-sm mb-1">Landline</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.landline||''} onChange={e=>setContact(c=>({...c,landline:e.target.value}))}/>
                  </div>

                  <div className="col-span-6">
                    <label className="block text-sm mb-1">Personal Email</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.personal_email||''} onChange={e=>setContact(c=>({...c,personal_email:e.target.value}))}/>
                  </div>
                  <div className="col-span-6">
                    <label className="block text-sm mb-1">Company Email</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.company_email||''} onChange={e=>setContact(c=>({...c,company_email:e.target.value}))}/>
                  </div>

                  <div className="col-span-12 font-semibold text-gray-800">CITY ADDRESS</div>
                  <div className="col-span-6"><label className="block text-sm mb-1">Street / Address</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.city_address_line||''} onChange={e=>setContact(c=>({...c,city_address_line:e.target.value}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">Barangay</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.city_barangay||''} onChange={e=>setContact(c=>({...c,city_barangay:e.target.value}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">Town</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.city_town||''} onChange={e=>setContact(c=>({...c,city_town:e.target.value}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">City</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.city_city||''} onChange={e=>setContact(c=>({...c,city_city:e.target.value}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">Zip Code</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.city_zip||''} onChange={e=>setContact(c=>({...c,city_zip:e.target.value}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">Country</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.city_country||'PHILIPPINES'} onChange={e=>setContact(c=>({...c,city_country:e.target.value}))}/></div>

                  <div className="col-span-12 font-semibold text-gray-800">PROVINCIAL ADDRESS</div>
                  <div className="col-span-6"><label className="block text-sm mb-1">Street / Address</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.prov_address_line||''} onChange={e=>setContact(c=>({...c,prov_address_line:e.target.value}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">Barangay</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.prov_barangay||''} onChange={e=>setContact(c=>({...c,prov_barangay:e.target.value}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">Town</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.prov_town||''} onChange={e=>setContact(c=>({...c,prov_town:e.target.value}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">City</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.prov_city||''} onChange={e=>setContact(c=>({...c,prov_city:e.target.value}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">Zip Code</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.prov_zip||''} onChange={e=>setContact(c=>({...c,prov_zip:e.target.value}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">Country</label>
                    <input className="w-full border rounded px-3 py-2" value={contact.prov_country||'PHILIPPINES'} onChange={e=>setContact(c=>({...c,prov_country:e.target.value}))}/></div>

                  <div className="col-span-12 flex justify-end gap-2 mt-2">
                    <button type="submit" className="px-4 py-2 bg-green-700 text-white rounded hover:bg-green-600">Save Contact</button>
                  </div>
                </>
              )}

              {activeTab==='govt' && (
                <>
                  <div className="col-span-6"><label className="block text-sm mb-1">SSS Number</label>
                    <input className="w-full border rounded px-3 py-2" value={govt.sss||''} onChange={e=>setGovt(g=>({...g,sss:e.target.value}))}/></div>
                  <div className="col-span-6"><label className="block text-sm mb-1">TIN Number</label>
                    <input className="w-full border rounded px-3 py-2" value={govt.tin||''} onChange={e=>setGovt(g=>({...g,tin:e.target.value}))}/></div>

                  <div className="col-span-6"><label className="block text-sm mb-1">PAG-IBIG</label>
                    <input className="w-full border rounded px-3 py-2" value={govt.pagibig||''} onChange={e=>setGovt(g=>({...g,pagibig:e.target.value}))}/></div>
                  <div className="col-span-6"><label className="block text-sm mb-1">PHILHEALTH</label>
                    <input className="w-full border rounded px-3 py-2" value={govt.philhealth||''} onChange={e=>setGovt(g=>({...g,philhealth:e.target.value}))}/></div>

                  <div className="col-span-6"><label className="block text-sm mb-1">PRC License</label>
                    <input className="w-full border rounded px-3 py-2" value={govt.prc||''} onChange={e=>setGovt(g=>({...g,prc:e.target.value}))}/></div>

                  <div className="col-span-12 flex justify-end gap-2 mt-2">
                    <button type="submit" className="px-4 py-2 bg-green-700 text-white rounded hover:bg-green-600">Save IDs</button>
                  </div>
                </>
              )}

              {activeTab==='family' && (
                <>
                  <div className="col-span-12 font-semibold text-gray-800">Father Information</div>
                  <div className="col-span-6"><label className="block text-sm mb-1">Father Name</label>
                    <input className="w-full border rounded px-3 py-2" value={family.father_name||''} onChange={e=>setFamily(f=>({...f,father_name:e.target.value}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">Father Birthday</label>
                    <input type="date" className="w-full border rounded px-3 py-2" value={family.father_birthday||''} onChange={e=>setFamily(f=>({...f,father_birthday:e.target.value||null}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">Age (auto)</label>
                    <input disabled className="w-full border rounded px-3 py-2 bg-gray-100" value={family.father_birthday?Math.floor((Date.now()-Date.parse(family.father_birthday))/(365.25*24*3600*1000)):'—'}/></div>
                  <div className="col-span-6"><label className="block text-sm mb-1">Father Occupation</label>
                    <input className="w-full border rounded px-3 py-2" value={family.father_occupation||''} onChange={e=>setFamily(f=>({...f,father_occupation:e.target.value}))}/></div>
                  <div className="col-span-6"><label className="block text-sm mb-1">Father's Employer</label>
                    <input className="w-full border rounded px-3 py-2" value={family.father_employer||''} onChange={e=>setFamily(f=>({...f,father_employer:e.target.value}))}/></div>

                  <div className="col-span-12 font-semibold text-gray-800">Mother Information</div>
                  <div className="col-span-6"><label className="block text-sm mb-1">Mother Name</label>
                    <input className="w-full border rounded px-3 py-2" value={family.mother_name||''} onChange={e=>setFamily(f=>({...f,mother_name:e.target.value}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">Mother Birthday</label>
                    <input type="date" className="w-full border rounded px-3 py-2" value={family.mother_birthday||''} onChange={e=>setFamily(f=>({...f,mother_birthday:e.target.value||null}))}/></div>
                  <div className="col-span-3"><label className="block text-sm mb-1">Age (auto)</label>
                    <input disabled className="w-full border rounded px-3 py-2 bg-gray-100" value={family.mother_birthday?Math.floor((Date.now()-Date.parse(family.mother_birthday))/(365.25*24*3600*1000)):'—'}/></div>
                  <div className="col-span-6"><label className="block text-sm mb-1">Mother Occupation</label>
                    <input className="w-full border rounded px-3 py-2" value={family.mother_occupation||''} onChange={e=>setFamily(f=>({...f,mother_occupation:e.target.value}))}/></div>
                  <div className="col-span-6"><label className="block text-sm mb-1">Mother Employer</label>
                    <input className="w-full border rounded px-3 py-2" value={family.mother_employer||''} onChange={e=>setFamily(f=>({...f,mother_employer:e.target.value}))}/></div>

                  <div className="col-span-12">
                    <label className="block text-sm mb-1">Parent Address</label>
                    <input className="w-full border rounded px-3 py-2" value={family.parent_address||''} onChange={e=>setFamily(f=>({...f,parent_address:e.target.value}))}/>
                  </div>

                  <div className="col-span-12 flex justify-end gap-2 mt-2">
                    <button type="submit" className="px-4 py-2 bg-green-700 text-white rounded hover:bg-green-600">Save Family Info</button>
                  </div>
                </>
              )}

              {activeTab==='education' && (
                <div className="col-span-12">
                  <div className="flex items-center justify-between mb-2">
                    <h4 className="font-semibold">Education Information</h4>
                    <button type="button" onClick={openEduAdd} className="inline-flex items-center gap-2 bg-green-700 text-white px-3 py-1.5 rounded hover:bg-green-600"><PlusCircleIcon className="h-5 w-5"/> Add New</button>
                  </div>
                  <div className="border bg-white rounded-xl shadow-sm faims-table">
                    <table className="w-full text-left">
                      <thead className="bg-amber-50"><tr>
                        <th className="p-2">#</th><th className="p-2">School Level</th><th className="p-2">School Name</th><th className="p-2">Location</th><th className="p-2">Date From</th><th className="p-2">Date To</th><th className="p-2">Completed</th><th className="p-2">Course</th><th className="p-2">Honors/Awards</th><th className="p-2 w-24 text-center">Action</th>
                      </tr></thead>
                      <tbody>
                        {educRows.map((r,i)=> (
                          <tr key={r.id} className="border-t border-gray-100 even:bg-gray-50 hover:bg-emerald-50 transition-colors">
                            <td className="p-2">{i+1}</td>
                            <td className="p-2">{r.school_level}</td>
                            <td className="p-2">{r.school_name}</td>
                            <td className="p-2">{r.school_location||''}</td>
                            <td className="p-2">{r.date_from||''}</td>
                            <td className="p-2">{r.date_to||''}</td>
                            <td className="p-2">{r.completed ? 'YES' : 'NO'}</td>
                            <td className="p-2">{r.course||''}</td>
                            <td className="p-2">{r.honors||''}</td>
                            <td className="p-2">
                              <div className="flex justify-center gap-2">
                                <button className="text-blue-600 hover:text-blue-800" type="button" onClick={()=>openEduEdit(r)} title="Edit"><PencilSquareIcon className="h-5 w-5"/></button>
                                <button className="text-red-600 hover:text-red-800" type="button" onClick={()=>removeEdu(r.id)} title="Delete"><TrashIcon className="h-5 w-5"/></button>
                              </div>
                            </td>
                          </tr>
                        ))}
                        {educRows.length===0 && <tr><td className="p-4 text-center text-gray-500" colSpan={10}>No records</td></tr>}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {activeTab==='dept' && (
                <div className="col-span-12">
                  <div className="flex items-center justify-between mb-2">
                    <h4 className="font-semibold">Department Information (Appointments)</h4>
                    <button type="button" onClick={openApptAdd} className="inline-flex items-center gap-2 bg-green-700 text-white px-3 py-1.5 rounded hover:bg-green-600"><PlusCircleIcon className="h-5 w-5"/> Add New</button>
                  </div>
                  <div className="border bg-white rounded-xl shadow-sm faims-table">
                    <table className="w-full text-left">
                      <thead className="bg-amber-50"><tr>
                        <th className="p-2">#</th><th className="p-2">Position</th><th className="p-2">Workplace</th><th className="p-2">Effective From</th><th className="p-2">Effective To</th><th className="p-2">Classification</th><th className="p-2">Status</th><th className="p-2">Salary</th><th className="p-2 w-24 text-center">Action</th>
                      </tr></thead>
                      <tbody>
                        {deptRows.map((r,i)=> (
                          <tr key={r.id} className="border-t border-gray-100 even:bg-gray-50 hover:bg-emerald-50 transition-colors">
                            <td className="p-2">{i+1}</td>
                            <td className="p-2">{r.position_name||''}</td>
                            <td className="p-2">{r.org_unit_name||''}</td>
                            <td className="p-2">{r.effective_from||''}</td>
                            <td className="p-2">{r.effective_to||''}</td>
                            <td className="p-2">{r.classification||''}</td>
                            <td className="p-2">{r.status||''}</td>
                            <td className="p-2">{typeof r.salary==='number' ? r.salary.toFixed(2) : ''}</td>
                            <td className="p-2">
                              <div className="flex justify-center gap-2">
                                <button className="text-blue-600 hover:text-blue-800" type="button" onClick={()=>openApptEdit(r)} title="Edit"><PencilSquareIcon className="h-5 w-5"/></button>
                                <button className="text-red-600 hover:text-red-800" type="button" onClick={()=>removeAppt(r.id)} title="Delete"><TrashIcon className="h-5 w-5"/></button>
                              </div>
                            </td>
                          </tr>
                        ))}
                        {deptRows.length===0 && <tr><td className="p-4 text-center text-gray-500" colSpan={9}>No records</td></tr>}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </form>
          </div>
        </div>
      )}

      {/* Education Modal */}
      {eduModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="absolute inset-0 bg-black/40" onClick={()=>setEduModalOpen(false)}/>
          <div className="relative bg-white rounded-xl shadow-xl w-full max-w-3xl mx-4">
            <div className="flex items-center justify-between p-4 border-b">
              <h3 className="text-lg font-semibold">{eduMode==='edit' ? 'Edit Education' : 'Add Education'}</h3>
              <button onClick={()=>setEduModalOpen(false)} className="p-1 rounded hover:bg-gray-100"><XMarkIcon className="h-6 w-6 text-gray-600"/></button>
            </div>
            <form onSubmit={saveEdu} className="p-6 grid grid-cols-12 gap-4">
              <div className="col-span-4"><label className="block text-sm mb-1">School Level</label>
                <select className="w-full border rounded px-2 py-2" value={eduForm.school_level||''} onChange={e=>setEduForm(f=>({...f,school_level:e.target.value}))} required>
                  {['ELEMENTARY','HIGH SCHOOL','VOCATIONAL','COLLEGE','GRADUATE'].map(x=> <option key={x} value={x}>{x}</option>)}
                </select></div>
              <div className="col-span-8"><label className="block text-sm mb-1">School Name</label>
                <input className="w-full border rounded px-3 py-2" value={eduForm.school_name||''} onChange={e=>setEduForm(f=>({...f,school_name:e.target.value}))} required/></div>
              <div className="col-span-6"><label className="block text-sm mb-1">School Location</label>
                <input className="w-full border rounded px-3 py-2" value={eduForm.school_location||''} onChange={e=>setEduForm(f=>({...f,school_location:e.target.value}))}/></div>
              <div className="col-span-3"><label className="block text-sm mb-1">Date From</label>
                <input type="date" className="w-full border rounded px-3 py-2" value={eduForm.date_from||''} onChange={e=>setEduForm(f=>({...f,date_from:e.target.value||null}))}/></div>
              <div className="col-span-3"><label className="block text-sm mb-1">Date To</label>
                <input type="date" className="w-full border rounded px-3 py-2" value={eduForm.date_to||''} onChange={e=>setEduForm(f=>({...f,date_to:e.target.value||null}))}/></div>
              <div className="col-span-3 flex items-end gap-2">
                <label className="inline-flex items-center gap-2"><input type="checkbox" checked={!!eduForm.completed} onChange={e=>setEduForm(f=>({...f,completed:e.target.checked}))}/><span className="text-sm">Completed</span></label>
              </div>
              <div className="col-span-6"><label className="block text-sm mb-1">Course</label>
                <input className="w-full border rounded px-3 py-2" value={eduForm.course||''} onChange={e=>setEduForm(f=>({...f,course:e.target.value}))}/></div>
              <div className="col-span-6"><label className="block text-sm mb-1">Honors/Awards</label>
                <input className="w-full border rounded px-3 py-2" value={eduForm.honors||''} onChange={e=>setEduForm(f=>({...f,honors:e.target.value}))}/></div>
              <div className="col-span-12 flex justify-end gap-2 mt-2">
                <button type="button" onClick={()=>setEduModalOpen(false)} className="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
                <button type="submit" className="px-4 py-2 bg-green-700 text-white rounded hover:bg-green-600">{eduMode==='edit'?'Update':'Submit'}</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Appointment Modal */}
      {apptModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="absolute inset-0 bg-black/40" onClick={()=>setApptModalOpen(false)}/>
          <div className="relative bg-white rounded-xl shadow-xl w-full max-w-4xl mx-4">
            <div className="flex items-center justify-between p-4 border-b">
              <h3 className="text-lg font-semibold">{apptMode==='edit' ? 'Edit Appointment' : 'Add Appointment'}</h3>
              <button onClick={()=>setApptModalOpen(false)} className="p-1 rounded hover:bg-gray-100"><XMarkIcon className="h-6 w-6 text-gray-600"/></button>
            </div>
            <form onSubmit={saveAppt} className="p-6 grid grid-cols-12 gap-4">
              <div className="col-span-6">
                <ComboBox label="Workplace" options={orgUnits.map(o=>({id:o.id,label:`${o.code} • ${o.name}`}))}
                  valueLabel={orgLabel} onInputChange={setOrgLabel}
                  onSelect={(opt)=>{ setOrgLabel(opt.label); setApptForm(f=>({...f,org_unit_id:Number(opt.id)})); }} required/>
              </div>
              <div className="col-span-6">
                <ComboBox label="Position" options={positions.map(o=>({id:o.id,label:`${o.name}`}))}
                  valueLabel={posLabel} onInputChange={setPosLabel}
                  onSelect={(opt)=>{ setPosLabel(opt.label); setApptForm(f=>({...f,position_id:Number(opt.id)})); }} required/>
              </div>
              <div className="col-span-3"><label className="block text-sm mb-1">Effective From</label>
                <input type="date" className="w-full border rounded px-3 py-2" value={apptForm.effective_from||''} onChange={e=>setApptForm(f=>({...f,effective_from:e.target.value||null}))} required/></div>
              <div className="col-span-3"><label className="block text-sm mb-1">Effective To</label>
                <input type="date" className="w-full border rounded px-3 py-2" value={apptForm.effective_to||''} onChange={e=>setApptForm(f=>({...f,effective_to:e.target.value||null}))}/></div>
              <div className="col-span-3"><label className="block text-sm mb-1">Classification</label>
                <select className="w-full border rounded px-2 py-2" value={apptForm.classification||''} onChange={e=>setApptForm(f=>({...f,classification:e.target.value}))}><option value="">—</option><option>FACULTY</option><option>STAFF</option><option>ADMIN</option></select></div>
              <div className="col-span-3"><label className="block text-sm mb-1">Status</label>
                <select className="w-full border rounded px-2 py-2" value={apptForm.status||''} onChange={e=>setApptForm(f=>({...f,status:e.target.value}))}><option value="">—</option>{jobStats.map(s=><option key={s.id} value={s.name}>{s.name}</option>)}</select></div>
              <div className="col-span-3"><label className="block text-sm mb-1">Salary</label>
                <input type="number" className="w-full border rounded px-3 py-2" value={apptForm.salary ?? ''} onChange={e=>setApptForm(f=>({...f,salary:e.target.value?+e.target.value:null}))}/></div>

              <div className="col-span-12 flex justify-end gap-2 mt-2">
                <button type="button" onClick={()=>setApptModalOpen(false)} className="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
                <button type="submit" className="px-4 py-2 bg-green-700 text-white rounded hover:bg-green-600">{apptMode==='edit'?'Update':'Submit'}</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Scoped Styles (amber scheme) */}
      <style>{`
      .faims-ui .faims-tabs .tab{background:#fff;color:#374151;padding:.625rem 1.25rem;border:1px solid #e5e7eb;border-bottom:none;border-top-left-radius:.5rem;border-top-right-radius:.5rem;position:relative;font-weight:700;transition:background-color .15s,color .15s,box-shadow .15s}
      .faims-ui .faims-tabs .tab:hover{background:#fef3c7;color:#111827;box-shadow:inset 0 -3px 0 #f59e0b}
      .faims-ui .faims-tabs .tab.active{background:#ca8a04;color:#fff}
      .faims-ui .faims-tabs .tab.active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:4px;background:#ca8a04;border-top-left-radius:4px;border-top-right-radius:4px}
      .faims-ui .faims-table table{border-collapse:separate;border-spacing:0}
      .faims-ui .faims-table thead th{background:#fffbeb;color:#1f2937}
      .faims-ui .faims-table tbody tr td{background:#fff;transition:background-color .12s,font-weight .12s,color .12s}
      .faims-ui .faims-table tbody tr:nth-child(even) td{background:#fffbeb}
      .faims-ui .faims-table tbody tr:hover td{background:#fef3c7;font-weight:600;color:#111827}
      `}</style>
    </div>
  );
}
