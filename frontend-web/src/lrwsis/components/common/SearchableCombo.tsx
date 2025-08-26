import * as React from 'react';
import { useEffect, useMemo, useRef, useState } from 'react';
import napi from '@/utils/axiosnapi';

type Opt = { id?: number | string; value: string; label: string };

export default function SearchableCombo({
  placeholder,
  fetchUrl,
  selectedLabel,
  onChange,
}: {
  placeholder?: string;
  fetchUrl: string;                 // e.g. "assets/lookups/classes"
  selectedLabel?: string;
  onChange: (v: Opt | null) => void;
}) {
  const [open, setOpen] = useState(false);
  const [text, setText] = useState('');
  const [opts, setOpts] = useState<Opt[]>([]);
  const [loading, setLoading] = useState(false);
  const [activeIdx, setActiveIdx] = useState(0);
  const debounce = useRef<ReturnType<typeof setTimeout> | null>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  // Visible options (local filter too, in case server ignores q)
  const visible = useMemo(() => {
    if (!open || !text.trim()) return opts;
    const t = text.toLowerCase();
    return opts.filter(
      o => o.label.toLowerCase().includes(t) || o.value.toLowerCase().includes(t)
    );
  }, [open, text, opts]);

  // Clamp highlight when list changes
  useEffect(() => {
    if (activeIdx > Math.max(visible.length - 1, 0)) setActiveIdx(0);
  }, [visible.length, activeIdx]);

  // Fetch when opened
  useEffect(() => { if (open) void load(); /* eslint-disable-next-line */ }, [open]);

  // Debounce fetch while typing (when open)
  useEffect(() => {
    if (!open) return;
    if (debounce.current) clearTimeout(debounce.current);
    debounce.current = setTimeout(() => void load(), 200);
    return () => { if (debounce.current) clearTimeout(debounce.current); };
    // eslint-disable-next-line
  }, [text, open]);

  async function load() {
    setLoading(true);
    try {
      // send multiple param names so any backend flavor works
      const r = await napi.get(fetchUrl, { params: { q: text, search: text, term: text } });
      const raw = Array.isArray(r.data?.data) ? r.data.data
                : Array.isArray(r.data)      ? r.data
                : [];

      const items: Opt[] = raw.map((x: any) => {
        if ('value' in x && 'label' in x) return { id: x.id, value: String(x.value), label: String(x.label) };
        if ('class_code' in x) return { id: x.id, value: String(x.class_code), label: `${x.class_code} — ${x.class_name ?? ''}`.trim() };
        if ('cat_code'   in x) return { id: x.id, value: String(x.cat_code),   label: `${x.cat_code} — ${x.cat_name ?? ''}`.trim() };
        if ('type_code'  in x) return { id: x.id, value: String(x.type_code),  label: `${x.type_code} — ${x.type_name ?? ''}`.trim() };
        if ('vendor_code'in x) return { id: x.id, value: String(x.vendor_code), label: x.vendor_name ?? x.vendor_code };
        return { id: x.id, value: String(x.value ?? x.code ?? x.id ?? ''), label: String(x.label ?? x.name ?? x.title ?? x.value ?? '') };
      });

      // de-dupe
      const seen = new Set<string>();
      setOpts(items.filter(o => {
        const k = `${o.value}::${o.label}`;
        if (seen.has(k)) return false;
        seen.add(k);
        return true;
      }));
    } catch {
      setOpts([]);
    } finally { setLoading(false); }
  }

  function pick(o: Opt) {
    onChange(o);
    setText('');
    setOpen(false);
    requestAnimationFrame(() => inputRef.current?.focus());
  }

  return (
    <div className={`relative ${open ? 'z-[80]' : 'z-auto'}`}>
      <input
        ref={inputRef}
        // open on mouse down so caret doesn’t jump
        onMouseDown={(e) => {
          e.preventDefault();
          setOpen(true);
          setActiveIdx(0);
          load();
          requestAnimationFrame(() => inputRef.current?.focus());
        }}
        value={open ? text : (selectedLabel || '')}
        onChange={(e) => { setText(e.target.value); setActiveIdx(0); }}
        onBlur={() => setTimeout(() => setOpen(false), 120)}
        onKeyDown={(e) => {
          if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!open) { setOpen(true); setActiveIdx(0); return; }
            setActiveIdx(i => Math.min(i + 1, Math.max(visible.length - 1, 0)));
          } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (!open) { setOpen(true); setActiveIdx(Math.max(visible.length - 1, 0)); return; }
            setActiveIdx(i => Math.max(i - 1, 0));
          } else if (e.key === 'Enter') {
            if (open) { e.preventDefault(); const sel = visible[activeIdx]; if (sel) pick(sel); }
          } else if (e.key === 'Escape') {
            setOpen(false);
          }
        }}
        placeholder={placeholder || 'Search…'}
        className="w-full border rounded px-3 py-2"
      />

      {open && (
        <div className="absolute z-[90] mt-1 w-full max-h-60 overflow-auto bg-white border rounded shadow-lg">
          {loading && <div className="p-2 text-sm text-gray-600">Loading…</div>}
          {!loading && visible.length === 0 && <div className="p-2 text-sm text-gray-600">No results</div>}
          {!loading && visible.map((o, idx) => (
            <div
              key={`${o.value}`}
              className={`px-3 py-2 cursor-pointer ${idx === activeIdx ? 'bg-emerald-50 font-semibold' : 'hover:bg-gray-100'}`}
              onMouseEnter={() => setActiveIdx(idx)}
              onMouseDown={() => pick(o)}
            >
              {o.label}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
