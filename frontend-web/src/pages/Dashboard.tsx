// src/pages/Dashboard.tsx
import * as React from 'react';
import { useEffect, useState, lazy, Suspense } from 'react';
import type { JSX } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Bars3Icon,
  ChevronDownIcon,
  ChevronRightIcon,
  ClipboardDocumentIcon,
} from '@heroicons/react/24/outline';

import napi, { logout, getWithCreds } from '@/utils/axiosnapi';

// Lazy modules
const Role = lazy(() => import('./components/settings/Role'));
const Pbn_entry_form = lazy(() => import('./components/quedan_tracking/Pbn_entry_form'));

const componentMap: Record<string, React.LazyExoticComponent<() => JSX.Element>> = {
  roles: Role,
  pbn_entry_forms: Pbn_entry_form,
};

interface SubModule {
  sub_module_id: number;
  sub_module_name: string;
  component_path: string | null;
}
interface Module {
  module_id: number;
  module_name: string;
  sub_modules: SubModule[];
}
interface System {
  system_id: number;
  system_name: string;
  modules: Module[];
}

export default function Dashboard() {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [systems, setSystems] = useState<System[]>([]);
  const [openSystems, setOpenSystems] = useState<number[]>([]);
  const [openModules, setOpenModules] = useState<number[]>([]);
  const [selectedContent, setSelectedContent] = useState<string>('Select a sub-module to view.');
  const [selectedSubModuleName, setSelectedSubModuleName] = useState<string>('Module Name');

  const [username, setUsername] = useState('');
  const [companyId, setCompanyId] = useState('');
  const [companyName, setCompanyName] = useState('');
  const [companyLogo, setCompanyLogo] = useState('');
  const [searchQuery, setSearchQuery] = useState('');

  const navigate = useNavigate();

  // Optional local user bootstrap (kept from your code)
  useEffect(() => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      const user = JSON.parse(storedUser);
      setUsername(user.first_name);
      setCompanyId(user.company_id);
    }
  }, []);

  // Company info (axios baseURL = '/api', so no '/api' prefix)
  useEffect(() => {
    if (!companyId) return;
    (async () => {
      try {
        const res = await napi.get(`/companies/${companyId}`);
        setCompanyName(res.data.name);
      } catch {
        console.error('Failed to load company name');
      }
    })();
  }, [companyId]);

  useEffect(() => {
    if (!companyId) return;
    (async () => {
      try {
        const res = await napi.get(`/companies/${companyId}`);
        setCompanyLogo(res.data.logo);
      } catch {
        console.error('Failed to load company logo');
      }
    })();
  }, [companyId]);

  // Auth + modules (let ProtectedRoute control redirects; no navigate to /login here)
  useEffect(() => {
    (async () => {
      try {
        const me = await getWithCreds('/me');
        if (me.data?.user?.name) setUsername(me.data.user.name);

        const mod = await napi.get<System[]>('/user/modules');
        setSystems(mod.data);
      } catch (err) {
        console.error('Auth/modules fetch failed', err);
      }
    })();
  }, []);

  const getFilteredModules = (modules: Module[]) =>
    modules
      .map((module) => {
        const moduleMatches = module.module_name.toLowerCase().includes(searchQuery.toLowerCase());
        const filteredSubModules = module.sub_modules.filter((sub) =>
          sub.sub_module_name.toLowerCase().includes(searchQuery.toLowerCase())
        );
        if (moduleMatches || filteredSubModules.length > 0) {
          return {
            ...module,
            sub_modules: moduleMatches ? module.sub_modules : filteredSubModules,
          };
        }
        return null;
      })
      .filter((m): m is Module => m !== null);

  const toggleSystem = (id: number) =>
    setOpenSystems((prev) => (prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]));

  const toggleModule = (id: number) =>
    setOpenModules((prev) => (prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]));

  async function handleLogout() {
    try {
      await logout();
      window.location.replace('/app/login'); // SPA login
    } catch {
      alert('Logout failed');
    }
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-green-700 via-green-500 to-yellow-300">
      <div className="flex w-full min-h-screen">
        {/* Sidebar (gradient to match TabbedLogin) */}
        <div
          className={`transition-all duration-300 ${
            sidebarOpen ? 'w-72' : 'w-0'
          } overflow-hidden shadow-xl rounded-r-2xl flex flex-col
              bg-gradient-to-b from-green-700 via-green-600 to-yellow-400 text-white`}
        >
          {/* Title Bar */}
          <div className="p-4 border-b border-white/20 font-bold text-lg flex justify-between items-center bg-white/10 backdrop-blur-sm">
            <span className="flex items-center gap-2">
              {companyLogo ? (
                <img src={`/${companyLogo}.jpg`} alt={companyLogo} className="h-8 w-8 object-contain rounded" />
              ) : (
                <span className="inline-block h-8 w-8 rounded bg-white/20" />
              )}
              {companyName || 'Systems'}
            </span>
            <button
              onClick={() => setSidebarOpen(false)}
              className="p-2 rounded-full bg-white/10 hover:bg-white/20 transition"
              title="Collapse"
            >
              <Bars3Icon className="h-6 w-6" />
            </button>
          </div>

          {/* Search */}
          <div className="px-3 py-2 bg-white/5 backdrop-blur-sm">
            <input
              type="text"
              placeholder="Search modules or submodules..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full rounded-md px-3 py-2 text-sm
                         text-emerald-900 placeholder:text-emerald-700/70
                         bg-white/90 border border-white/30
                         focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:border-emerald-300"
            />
          </div>

          {/* Hierarchy */}
          <nav className="p-3 overflow-y-auto">
            {systems.map((system) => (
              <div key={system.system_id} className="mb-2">
                {/* System */}
                <button
                  onClick={() => toggleSystem(system.system_id)}
                  className="flex items-center w-full text-left font-semibold px-2 py-2
                             rounded-md bg-emerald-900/60 hover:bg-emerald-900/50 transition"
                >
                  {openSystems.includes(system.system_id) ? (
                    <ChevronDownIcon className="h-4 w-4 mr-2" />
                  ) : (
                    <ChevronRightIcon className="h-4 w-4 mr-2" />
                  )}
                  {system.system_name}
                </button>

                {/* Modules */}
                {openSystems.includes(system.system_id) && (
                  <div className="ml-2 mt-2 space-y-1">
                    {getFilteredModules(system.modules).map((module) => (
                      <div key={module.module_id}>
                        <button
                          onClick={() => toggleModule(module.module_id)}
                          className="flex items-center w-full text-left text-sm font-medium px-2 py-1.5
                                     rounded-md bg-white/10 text-white hover:bg-white/20 transition"
                        >
                          {openModules.includes(module.module_id) ? (
                            <ChevronDownIcon className="h-3 w-3 mr-2" />
                          ) : (
                            <ChevronRightIcon className="h-3 w-3 mr-2" />
                          )}
                          {module.module_name}
                        </button>

                        {/* Submodules */}
                        {openModules.includes(module.module_id) && (
                          <ul className="ml-5 mt-1 space-y-1">
                            {module.sub_modules.map((sub) => (
                              <li key={sub.sub_module_id}>
                                <button
                                  onClick={() => {
                                    setSelectedContent(
                                      sub.component_path || `You selected: ${sub.sub_module_name}`
                                    );
                                    setSelectedSubModuleName(sub.sub_module_name);
                                  }}
                                  className="w-full text-left px-3 py-1.5 rounded-md
                                             bg-white/10 text-white hover:bg-white/20
                                             border border-white/20 transition"
                                >
                                  <ClipboardDocumentIcon className="h-3 w-3 inline mr-2" />
                                  {sub.sub_module_name}
                                </button>
                              </li>
                            ))}
                          </ul>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </nav>
        </div>

        {/* Main Content */}
        <div className="flex-1 flex flex-col">
          {/* Header (same gradient as login header) */}
          <div className="bg-gradient-to-r from-green-600 to-yellow-400 text-white px-6 py-3 flex items-center justify-between shadow">
            <h1 className="text-lg font-semibold drop-shadow-sm">{selectedSubModuleName}</h1>

            <div className="relative group">
              {/* Avatar + Toggle */}
              <div className="flex items-center space-x-2 cursor-pointer select-none">
                <span className="text-sm">Welcome, {username || 'User'}</span>
                <img
                  src="https://via.placeholder.com/32"
                  alt="User Avatar"
                  className="w-8 h-8 rounded-full border-2 border-white/70"
                />
              </div>

              {/* Dropdown */}
              <div className="absolute right-0 mt-2 w-36 bg-white text-emerald-800 rounded-lg shadow-lg ring-1 ring-emerald-100 opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                <button
                  onClick={handleLogout}
                  className="flex items-center gap-2 px-4 py-2 hover:bg-emerald-50 w-full text-left text-sm rounded-lg"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h5a2 2 0 012 2v1"/>
                  </svg>
                  Logout
                </button>
              </div>
            </div>
          </div>

          {/* Body (translucent card feel like login) */}
          <div className="flex-1 p-6 bg-white/90 backdrop-blur">
            {!sidebarOpen && (
              <button
                onClick={() => setSidebarOpen(true)}
                className="mb-4 text-emerald-700 hover:text-emerald-900 transition"
                title="Expand sidebar"
              >
                <Bars3Icon className="h-5 w-5" />
              </button>
            )}

            <Suspense fallback={<div className="p-4 text-emerald-700">Loading module...</div>}>
              {selectedContent && componentMap[selectedContent] ? (
                <div className="w-full h-full">
                  {React.createElement(componentMap[selectedContent])}
                </div>
              ) : (
                <div className="w-full h-full text-emerald-900">
                  {typeof selectedContent === 'string'
                    ? selectedContent
                    : 'Select a sub-module to view.'}
                </div>
              )}
            </Suspense>
          </div>
        </div>
      </div>
    </div>
  );
}
