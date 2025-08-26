import { useEffect, useRef, useState } from 'react';
import napi from '@/utils/axiosnapi';
import { getCsrfToken } from '@/utils/csrf';
import Cookies from 'js-cookie';
import {
  PencilSquareIcon, TrashIcon, PlusCircleIcon, XMarkIcon
} from '@heroicons/react/24/outline';

type Row = {
  id: number;
  group_code: string;
  group_name: string;
  name: string;
  default_life_months: number;
  default_depr_method: 'straight_line' | 'declining_balance' | 'units_of_production' | 'none';
  residual_rate: number; // %
  is_active: boolean;
  sort_order: number;
  created_at?: string;
  updated_at?: string;
};

const DEPR_OPTIONS = [
  { value: 'straight_line', label: 'Straight Line' },
  { value: 'declining_balance', label: 'Declining Balance' },
  { value: 'units_of_production', label: 'Units of Production' },
  { value: 'none', label: 'None' },
] as const;

export default function AssetGroup() {
  // table data
  const [rows, setRows] = useState<Row[]>([]);
  const [isLoading, setIsLoading] = useState(false);

  // pagination + search + sort
  const [perPage, setPerPage] = useState(10);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [searchQuery, setSearchQuery] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [order, setOrder] = useState<'group_code' | 'group_name' | 'name' | 'recent' | 'sort'>('group_code');
  const searchTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

  // modal + form
  const [modalOpen, setModalOpen] = useState(false);
  const [formMode, setFormMode] = useState<'add' | 'edit'>('add');
  const [editId, setEditId] = useState<number | null>(null);
  const [form, setForm] = useState<Partial<Row>>({
    group_code: '',
    group_name: '',
    name: '',
    default_life_months: 60,
    default_depr_method: 'straight_line',
    residual_rate: 0,
    is_active: true,
    sort_order: 0,
  });

  // init per-page and first fetch
  useEffect(() => {
    const init = async () => {
      try {
        const setting = await napi.get('/api/settings/paginaterecs');
        const pageSize = Number(setting.data?.value) || 10;
        setPerPage(pageSize);
        await fetchRows(1, pageSize, debouncedSearch, order);
      } catch {
        await fetchRows(1, 10, debouncedSearch, order);
      }
    };
    init();
    // eslint-disable-next-line
  }, []);

  // debounce search typing
  useEffect(() => {
    if (searchTimeout.current) clearTimeout(searchTimeout.current);
    searchTimeout.current = setTimeout(() => setDebouncedSearch(searchQuery), 300);
    return () => { if (searchTimeout.current) clearTimeout(searchTimeout.current); };
  }, [searchQuery]);

  // fetch on debounced search or order change
  useEffect(() => {
    fetchRows(1, perPage, debouncedSearch, order);
    // eslint-disable-next-line
  }, [debouncedSearch, order]);

  async function fetchRows(page = 1, per = perPage, search = '', orderBy = order) {
    setIsLoading(true);
    try {
      const res = await napi.get(`/api/assets/groups`, {
        params: { per_page: per, page, q: search, order: orderBy, active_only: false }
      });
      setRows(res.data.data);
      setTotalPages(res.data.last_page);
      setCurrentPage(res.data.current_page);
    } catch (err) {
      console.error('Failed to load asset groups', err);
    } finally {
      setIsLoading(false);
    }
  }

  function openAdd() {
    setFormMode('add');
    setEditId(null);
    setForm({
      group_code: '',
      group_name: '',
      name: '',
      default_life_months: 60,
      default_depr_method: 'straight_line',
      residual_rate: 0,
      is_active: true,
      sort_order: 0,
    });
    setModalOpen(true);
  }

  function openEdit(row: Row) {
    setFormMode('edit');
    setEditId(row.id);
    setForm({ ...row });
    setModalOpen(true);
  }

  function closeModal() {
    setModalOpen(false);
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    await getCsrfToken();

    const required = ['group_code','group_name','name','default_life_months','default_depr_method','residual_rate'];
    for (const k of required) {
      // @ts-ignore
      if (form[k] === undefined || form[k] === null || form[k] === '') {
        alert('Please complete all fields.');
        return;
      }
    }

    try {
      if (formMode === 'edit' && editId) {
        await napi.patch(`/api/assets/groups/${editId}`, form, {
          headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' },
          withCredentials: true,
        });
        alert('Asset group updated');
      } else {
        await napi.post(`/api/assets/groups`, form, {
          headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' },
          withCredentials: true,
        });
        alert('Asset group added');
      }
      closeModal();
      await fetchRows(currentPage, perPage, searchQuery, order);
    } catch (error: any) {
      alert(error?.response?.data?.message || 'Error saving asset group');
    }
  }

  async function handleDelete(id: number) {
    if (!confirm('Delete this asset group?')) return;
    await getCsrfToken();
    try {
      await napi.delete(`/api/assets/groups/${id}`, {
        headers: { 'X-XSRF-TOKEN': Cookies.get('XSRF-TOKEN') || '' },
        withCredentials: true,
      });
      alert('Deleted');
      const nextPage = rows.length === 1 && currentPage > 1 ? currentPage - 1 : currentPage;
      await fetchRows(nextPage, perPage, searchQuery, order);
    } catch {
      alert('Delete failed');
    }
  }

  return (
    <div className="p-6 bg-gray-50 min-h-full">
      {/* Toolbar */}
      <div className="mb-4 flex items-center gap-2">
        <button
          onClick={openAdd}
          className="inline-flex items-center gap-2 bg-green-700 text-white px-4 py-2 rounded hover:bg-green-600"
        >
          <PlusCircleIcon className="h-5 w-5" />
          Add New Asset Group
        </button>

        <input
          type="text"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          placeholder="Search code, group name, or name…"
          className="w-96 border rounded px-3 py-2 text-gray-800"
        />

        <select
          value={order}
          onChange={(e) => setOrder(e.target.value as any)}
          className="border rounded px-2 py-2 text-gray-800"
          title="Sort"
        >
          <option value="group_code">Sort: Code</option>
          <option value="group_name">Sort: Group Name</option>
          <option value="name">Sort: Name</option>
          <option value="recent">Sort: Recent</option>
          <option value="sort">Sort: Manual</option>
        </select>
      </div>

      {/* Loading */}
      {isLoading && (
        <div className="flex justify-center my-6">
          <div className="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>
      )}

      {/* Table */}
      {!isLoading && (
        <div className="border bg-white rounded shadow">
          <table className="w-full text-left">
            <thead className="bg-gray-100 text-gray-600">
              <tr>
                <th className="p-2">#</th>
                <th className="p-2">Group Code</th>
                <th className="p-2">Group Name</th>
                <th className="p-2">Name</th>
                <th className="p-2">Life (mo)</th>
                <th className="p-2">Method</th>
                <th className="p-2">Residual %</th>
                <th className="p-2">Active</th>
                <th className="p-2 w-24 text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r, i) => (
                <tr key={r.id} className="border-t border-gray-100">
                  <td className="p-2">{(currentPage - 1) * perPage + i + 1}</td>
                  <td className="p-2">{r.group_code}</td>
                  <td className="p-2">{r.group_name}</td>
                  <td className="p-2">{r.name}</td>
                  <td className="p-2">{r.default_life_months}</td>
                  <td className="p-2">{DEPR_OPTIONS.find(o => o.value === r.default_depr_method)?.label}</td>
                  <td className="p-2">{r.residual_rate}</td>
                  <td className="p-2">{r.is_active ? 'Yes' : 'No'}</td>
                  <td className="p-2">
                    <div className="flex justify-center gap-2">
                      <button
                        onClick={() => openEdit(r)}
                        className="text-blue-600 hover:text-blue-800"
                        title="Edit"
                      >
                        <PencilSquareIcon className="h-5 w-5" />
                      </button>
                      <button
                        onClick={() => handleDelete(r.id)}
                        className="text-red-600 hover:text-red-800"
                        title="Delete"
                      >
                        <TrashIcon className="h-5 w-5" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
              {rows.length === 0 && (
                <tr><td className="p-4 text-center text-gray-500" colSpan={9}>No records found</td></tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      {/* Pagination */}
      <div className="flex justify-between mt-4 text-sm text-gray-700">
        <button onClick={() => fetchRows(1)} disabled={currentPage === 1}
          className="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50">First</button>
        <button onClick={() => fetchRows(currentPage - 1)} disabled={currentPage === 1}
          className="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50">Previous</button>
        <span className="self-center">Page {currentPage} of {totalPages}</span>
        <button onClick={() => fetchRows(currentPage + 1)} disabled={currentPage === totalPages}
          className="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50">Next</button>
        <button onClick={() => fetchRows(totalPages)} disabled={currentPage === totalPages}
          className="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50">Last</button>
      </div>

      {/* Modal */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="absolute inset-0 bg-black/40" onClick={closeModal} />
          <div className="relative bg-white rounded-xl shadow-xl w-full max-w-4xl mx-4">
            <div className="flex items-center justify-between p-4 border-b">
              <h3 className="text-lg font-semibold">
                {formMode === 'edit' ? 'Update Asset Group' : 'Add New Asset Group'}
              </h3>
              <button onClick={closeModal} className="p-1 rounded hover:bg-gray-100">
                <XMarkIcon className="h-6 w-6 text-gray-600" />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="p-6 grid grid-cols-12 gap-4">
              <div className="col-span-3">
                <label className="block text-sm text-gray-600 mb-1">Group Code</label>
                <input
                  type="text"
                  value={form.group_code ?? ''}
                  onChange={(e) => setForm(f => ({ ...f, group_code: e.target.value }))}
                  className="w-full border rounded px-3 py-2"
                  required maxLength={50}
                />
              </div>

              <div className="col-span-4">
                <label className="block text-sm text-gray-600 mb-1">Group Name</label>
                <input
                  type="text"
                  value={form.group_name ?? ''}
                  onChange={(e) => setForm(f => ({ ...f, group_name: e.target.value }))}
                  className="w-full border rounded px-3 py-2"
                  required maxLength={150}
                />
              </div>

              <div className="col-span-5">
                <label className="block text-sm text-gray-600 mb-1">Name (Asset Name)</label>
                <input
                  type="text"
                  value={form.name ?? ''}
                  onChange={(e) => setForm(f => ({ ...f, name: e.target.value }))}
                  className="w-full border rounded px-3 py-2"
                  required maxLength={150}
                />
              </div>

              <div className="col-span-3">
                <label className="block text-sm text-gray-600 mb-1">Life (months)</label>
                <input
                  type="number" min={1}
                  value={form.default_life_months ?? 60}
                  onChange={(e) => setForm(f => ({ ...f, default_life_months: Number(e.target.value) }))}
                  className="w-full border rounded px-3 py-2"
                  required
                />
              </div>

              <div className="col-span-4">
                <label className="block text-sm text-gray-600 mb-1">Depreciation Method</label>
                <select
                  value={form.default_depr_method ?? 'straight_line'}
                  onChange={(e) => setForm(f => ({ ...f, default_depr_method: e.target.value as Row['default_depr_method'] }))}
                  className="w-full border rounded px-3 py-2"
                  required
                >
                  {DEPR_OPTIONS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
                </select>
              </div>

              <div className="col-span-3">
                <label className="block text-sm text-gray-600 mb-1">Residual (%)</label>
                <input
                  type="number" min={0} max={100} step={0.01}
                  value={form.residual_rate ?? 0}
                  onChange={(e) => setForm(f => ({ ...f, residual_rate: Number(e.target.value) }))}
                  className="w-full border rounded px-3 py-2"
                  required
                />
              </div>

              <div className="col-span-2 flex items-end gap-2">
                <label className="inline-flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={!!form.is_active}
                    onChange={(e) => setForm(f => ({ ...f, is_active: e.target.checked }))}
                    className="h-4 w-4"
                  />
                  <span className="text-sm text-gray-700">Active</span>
                </label>
              </div>

              <div className="col-span-12 flex justify-end gap-2 mt-2">
                <button type="button" onClick={closeModal} className="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                  Cancel
                </button>
                <button type="submit" className="px-4 py-2 bg-green-700 text-white rounded hover:bg-green-600">
                  {formMode === 'edit' ? 'Update' : 'Submit'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
