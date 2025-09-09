import React, { useEffect, useState } from "react";
import napi from "@/utils/axiosnapi";
import SearchSelect from "@/components/libraries/SearchSelect";

/* ---------- types ---------- */
export type AddressIds = {
  regionId?: number | null;
  provinceId?: number | null;
  cmid?: number | null;
  barangayId?: number | null;
  zipcodeId?: number | null;
};
export type AddressLabels = {
  regionName?: string;
  provinceName?: string;
  cityName?: string;
  barangayName?: string;
  zipcode?: string;
};
type Option = { id: number; label: string };

type Props = {
  value?: AddressIds;
  onChange?: (ids: AddressIds, labels: AddressLabels) => void;
  required?: boolean;
  disabled?: boolean;
  title?: string;
  showZipcode?: boolean;
  /** shows counts + last error at the bottom */
  debug?: boolean;
};

const emptyIds: AddressIds = { regionId: null, provinceId: null, cmid: null, barangayId: null, zipcodeId: null };

/* ---------- component ---------- */
export default function ComponentAddress({
  value,
  onChange,
  required,
  disabled,
  title = "Address",
  showZipcode = true,
  debug = false,
}: Props) {
  const [ids, setIds] = useState<AddressIds>(value ?? emptyIds);

  const [regions, setRegions] = useState<Option[]>([]);
  const [provinces, setProvinces] = useState<Option[]>([]);
  const [cities, setCities] = useState<Option[]>([]);
  const [barangays, setBarangays] = useState<Option[]>([]);
  const [zipcodes, setZipcodes] = useState<Option[]>([]);
  const [lastError, setLastError] = useState<string | null>(null);

  const labels = {
    regionName:  regions.find(x=>x.id===ids.regionId)?.label,
    provinceName:provinces.find(x=>x.id===ids.provinceId)?.label,
    cityName:    cities.find(x=>x.id===ids.cmid)?.label,
    barangayName:barangays.find(x=>x.id===ids.barangayId)?.label,
    zipcode:     zipcodes.find(x=>x.id===ids.zipcodeId)?.label,
  };

  // IMPORTANT: napi baseURL is '/api'; call endpoints WITHOUT '/api'
  useEffect(() => { (async () => {
    try {
      const { data } = await napi.get("/regions");
      setRegions(data.map((r:any)=>({ id: r.id, label: r.region_name })));
      setLastError(null);
    } catch (e:any) {
      setLastError(`GET /regions failed: ${e?.message ?? e}`);
    }
  })(); }, []);

  // sync when parent passes new value (edit/reset)
  useEffect(() => { if (value) setIds(value); },
    [value?.regionId, value?.provinceId, value?.cmid, value?.barangayId, value?.zipcodeId]);

  useEffect(() => {
    if (!ids.regionId) { setProvinces([]); setCities([]); setBarangays([]); setZipcodes([]); return; }
    (async () => {
      try {
        const { data } = await napi.get("/provinces", { params: { region_id: ids.regionId }});
        setProvinces(data.map((p:any)=>({ id: p.id, label: p.province_name })));
        setLastError(null);
      } catch (e:any) {
        setLastError(`GET /provinces failed: ${e?.message ?? e}`);
      }
    })();
  }, [ids.regionId]);

  useEffect(() => {
    if (!ids.provinceId) { setCities([]); setBarangays([]); setZipcodes([]); return; }
    (async () => {
      try {
        const { data } = await napi.get("/cities", { params: { province_id: ids.provinceId }});
        setCities(data.map((c:any)=>({ id: c.cmid, label: c.name })));
        setLastError(null);
      } catch (e:any) {
        setLastError(`GET /cities failed: ${e?.message ?? e}`);
      }
    })();
  }, [ids.provinceId]);

  useEffect(() => {
    if (!ids.cmid) { setBarangays([]); setZipcodes([]); return; }
    (async () => {
      try {
        const { data } = await napi.get("/barangays", { params: { cmid: ids.cmid }});
        setBarangays(data.map((b:any)=>({ id: b.barangay_id, label: b.barangay })));
        setLastError(null);
      } catch (e:any) {
        setLastError(`GET /barangays failed: ${e?.message ?? e}`);
      }
    })();
  }, [ids.cmid]);

  useEffect(() => {
    (async () => {
      const params:any = {};
      if (ids.provinceId) params.province_id = ids.provinceId;
      if (ids.cmid)       params.cmid = ids.cmid;
      if (ids.barangayId) params.barangay_id = ids.barangayId;
      if (Object.keys(params).length === 0) { setZipcodes([]); return; }
      try {
        const { data } = await napi.get("/zipcodes", { params });
        setZipcodes(data.map((z:any)=>({ id: z.zipcode_id, label: z.zipcode })));
        setLastError(null);
      } catch (e:any) {
        setLastError(`GET /zipcodes failed: ${e?.message ?? e}`);
      }
    })();
  }, [ids.provinceId, ids.cmid, ids.barangayId]);

  const emit = (partial: Partial<AddressIds>) => {
    const next = { ...ids, ...partial };
    setIds(next);
    onChange?.(next, labels);
  };

  const onRegion   = (e: React.ChangeEvent<HTMLSelectElement>) => emit({ regionId:   e.target.value ? Number(e.target.value) : null, provinceId:null, cmid:null, barangayId:null, zipcodeId:null });
  const onProvince = (e: React.ChangeEvent<HTMLSelectElement>) => emit({ provinceId: e.target.value ? Number(e.target.value) : null, cmid:null, barangayId:null, zipcodeId:null });
  const onCity     = (e: React.ChangeEvent<HTMLSelectElement>) => emit({ cmid:       e.target.value ? Number(e.target.value) : null, barangayId:null, zipcodeId:null });
  const onBarangay = (e: React.ChangeEvent<HTMLSelectElement>) => emit({ barangayId: e.target.value ? Number(e.target.value) : null, zipcodeId:null });

  const onZipcode  = async (e: React.ChangeEvent<HTMLSelectElement>) => {
    const zipcodeId = e.target.value ? Number(e.target.value) : null;
    if (!zipcodeId) { emit({ zipcodeId:null }); return; }
    try {
      const { data } = await napi.get("/address/resolve", { params: { zipcode_id: zipcodeId }});
      emit({
        regionId:   data.region_id   ?? null,
        provinceId: data.province_id ?? null,
        cmid:       data.cmid        ?? null,
        barangayId: data.barangay_id ?? null,
        zipcodeId:  data.zipcode_id  ?? zipcodeId,
      });
      setLastError(null);
    } catch (e:any) {
      setLastError(`GET /address/resolve failed: ${e?.message ?? e}`);
    }
  };

  return (
    // [all:revert] neutralizes opinionated global styles that were shrinking your selects
    <section className="w-full col-span-full">
      <div className="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div className="mb-2 text-sm font-semibold text-gray-700">{title}</div>

{/* ROW 1: Region • Province • City/Municipality */}
<div className="grid gap-4 sm:grid-cols-12">
  <Field label="Region" className="sm:col-span-4">
    <SearchSelect
      className="w-full min-w-0"
      value={ids.regionId ?? null}
      onChange={(val) =>
        emit({ regionId: val, provinceId: null, cmid: null, barangayId: null, zipcodeId: null })
      }
      options={regions}
      placeholder="-- Select Region --"
      disabled={disabled}
      required={required}
    />
  </Field>

  <Field label="Province" className="sm:col-span-4">
    <SearchSelect
      className="w-full min-w-0"
      value={ids.provinceId ?? null}
      onChange={(val) =>
        emit({ provinceId: val, cmid: null, barangayId: null, zipcodeId: null })
      }
      options={provinces}
      placeholder={ids.regionId ? "-- Select Province --" : "Select region first"}
      disabled={disabled || !ids.regionId}
      required={required}
    />
  </Field>

  <Field label="City / Municipality" className="sm:col-span-4">
    <SearchSelect
      className="w-full min-w-0"
      value={ids.cmid ?? null}
      onChange={(val) => emit({ cmid: val, barangayId: null, zipcodeId: null })}
      options={cities}
      placeholder={ids.provinceId ? "-- Select City/Muni --" : "Select province first"}
      disabled={disabled || !ids.provinceId}
      required={required}
    />
  </Field>
</div>

{/* ROW 2: Barangay • Zipcode */}
<div className="mt-4 grid gap-4 sm:grid-cols-12">
  <Field label="Barangay" className="sm:col-span-6">
    <SearchSelect
      className="w-full min-w-0"
      value={ids.barangayId ?? null}
      onChange={(val) => emit({ barangayId: val, zipcodeId: null })}
      options={barangays}
      placeholder={ids.cmid ? "-- Select Barangay --" : "Select city/muni first"}
      disabled={disabled || !ids.cmid}
      required={required}
    />
  </Field>

  <Field label="Zipcode" className="sm:col-span-6">
    <SearchSelect
      className="w-full min-w-0"
      value={ids.zipcodeId ?? null}
      onChange={async (val) => {
        if (!val) { emit({ zipcodeId: null }); return; }
        const { data } = await napi.get("/address/resolve", { params: { zipcode_id: val } });
        emit({
          regionId:   data.region_id   ?? null,
          provinceId: data.province_id ?? null,
          cmid:       data.cmid        ?? null,
          barangayId: data.barangay_id ?? null,
          zipcodeId:  data.zipcode_id  ?? val,
        });
      }}
      options={zipcodes}
      placeholder={(zipcodes.length>0 || ids.barangayId || ids.cmid) ? "-- Select Zipcode --" : "Pick a location first"}
      disabled={disabled || (zipcodes.length===0 && !(ids.regionId||ids.provinceId||ids.cmid||ids.barangayId))}
    />
  </Field>
</div>


        {debug && (
          <div className="mt-3 rounded-md bg-gray-50 p-2 text-[11px] text-gray-600">
            counts → R:{regions.length} P:{provinces.length} C:{cities.length} B:{barangays.length} Z:{zipcodes.length}
            {lastError ? <div className="mt-1 text-red-600">lastError: {lastError}</div> : null}
          </div>
        )}
      </div>
    </section>
  );
}

/* ---------- subcomponents ---------- */
function Field({ label, className, children }: { label: string; className?: string; children: React.ReactNode }) {
  return (
    <div className={`w-full ${className ?? ""}`}>
      <label className="block text-xs font-medium text-gray-700">{label}</label>
      <div className="mt-1">
        {children}
      </div>
    </div>
  );
}

function Select({
  value, onChange, disabled, required, options, placeholder,
}: {
  value: number | null | undefined;
  onChange: (e: React.ChangeEvent<HTMLSelectElement>) => void;
  disabled?: boolean;
  required?: boolean;
  options: Option[];
  placeholder: string;
}) {
  return (
    <select
      className="block w-full min-w-[220px] rounded-xl border border-gray-300 bg-white p-2 text-sm
                 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      value={value ?? ""}
      onChange={onChange}
      disabled={disabled}
      required={required}
    >
      <option value="">{placeholder}</option>
      {options.map(o => <option key={o.id} value={o.id}>{o.label}</option>)}
    </select>
  );
}
