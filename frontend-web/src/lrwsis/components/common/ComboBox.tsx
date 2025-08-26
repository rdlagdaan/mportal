import { useEffect, useMemo, useRef, useState } from 'react';

export type ComboOption = { id: number | string; label: string };

export default function ComboBox({
  label,
  placeholder = 'Search…',
  options,
  valueLabel,
  // onInputChange is now optional and not used for filtering while typing
  onInputChange,
  onSelect,
  required,
  readOnly = false,
}: {
  label: string;
  placeholder?: string;
  options: ComboOption[];
  valueLabel: string;
  onInputChange?: (v: string) => void;
  onSelect: (opt: ComboOption) => void;
  required?: boolean;
  readOnly?: boolean;
}) {
  const [open, setOpen] = useState(false);
  const [activeIdx, setActiveIdx] = useState(0);
  const [text, setText] = useState('');               // local typing buffer
  const inputRef = useRef<HTMLInputElement>(null);

  // de-dupe options defensively
  const opts = useMemo(() => {
    const seen = new Set<string>();
    const out: ComboOption[] = [];
    for (const o of options || []) {
      const k = `${String(o.id)}::${o.label}`;
      if (!seen.has(k)) { seen.add(k); out.push(o); }
    }
    return out;
  }, [options]);

  // filter against local buffer while open, otherwise show selected label
  const term = (open ? text : valueLabel).toLowerCase();
  const filtered = opts.filter(o => o.label.toLowerCase().includes(term));
  const boxRef = useRef<HTMLDivElement>(null);

  // when opening, seed the buffer from the current label and focus the input
  useEffect(() => {
    if (open) {
      setText(valueLabel || '');
      inputRef.current?.focus();
    }
  }, [open, valueLabel]);

  return (
    <div ref={boxRef} className={`relative ${open ? 'z-[80]' : 'z-auto'}`}>
      <label className="block text-xs font-semibold text-green-800 tracking-wide uppercase mb-1">
        {label}
      </label>

      <div className="relative">
        <input
          ref={inputRef}
          className="w-full border-2 border-green-300 rounded-lg px-3 py-2 text-[15px] focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-green-500 bg-white pr-9"
          placeholder={placeholder}
          value={open ? text : valueLabel}
          readOnly={readOnly}
          onFocus={() => setOpen(true)}
          onChange={(e) => {
            if (readOnly) return;
            const v = e.target.value;
            setText(v);                  // keep typing locally; no parent call here
            setActiveIdx(0);
          }}
          onBlur={() => setTimeout(() => setOpen(false), 120)}
          onKeyDown={(e) => {
            if (readOnly) return;
            if (!open) setOpen(true);
            if (e.key === 'ArrowDown') {
              e.preventDefault();
              setActiveIdx(i => Math.min(i + 1, Math.max(filtered.length - 1, 0)));
            } else if (e.key === 'ArrowUp') {
              e.preventDefault();
              setActiveIdx(i => Math.max(i - 1, 0));
            } else if (e.key === 'Enter') {
              e.preventDefault();
              const pick = filtered[activeIdx];
              if (pick) {
                onSelect(pick);
                setText(pick.label);
                setOpen(false);
                requestAnimationFrame(() => inputRef.current?.focus());
              }
            } else if (e.key === 'Escape') {
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
            e.preventDefault();
            setOpen(o => !o);
            requestAnimationFrame(() => inputRef.current?.focus());
          }}
          aria-label="Toggle options"
        >
          ▼
        </button>
      </div>

      {open && (
        <ul className="absolute z-[90] mt-1 max-h-56 w-full overflow-auto rounded-md border bg-white shadow-lg">
          {filtered.length ? (
            filtered.map((o, idx) => (
              <li
                key={`${o.id}-${o.label}`}
                className={`px-3 py-2 cursor-pointer ${idx === activeIdx ? 'bg-amber-100 font-semibold' : 'hover:bg-amber-50'}`}
                onMouseDown={(e) => {
                  e.preventDefault();
                  onSelect(o);
                  setText(o.label);
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
