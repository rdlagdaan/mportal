import React, { Fragment, useMemo, useState } from "react";
import { Combobox, Transition } from "@headlessui/react";

export type Option = { id: number; label: string };

type Props = {
  value: number | null | undefined;
  onChange: (next: number | null) => void;
  options: Option[];
  placeholder?: string;
  disabled?: boolean;
  required?: boolean;
  className?: string;
};

export default function SearchSelect({
  value,
  onChange,
  options,
  placeholder = "Search…",
  disabled,
  required,
  className,
}: Props) {
  const selected = useMemo(() => options.find(o => o.id === value) || null, [options, value]);
  const [query, setQuery] = useState("");

  const filtered =
    query.trim() === ""
      ? options
      : options.filter(o => o.label.toLowerCase().includes(query.toLowerCase()));

  return (
    <div className={`relative ${className ?? ""}`}>
      <Combobox
        value={selected}
        onChange={(opt: Option | null) => onChange(opt ? opt.id : null)}
        disabled={disabled}
        nullable
        as="div"
        className="w-full"
      >
        <div className="relative">
          <Combobox.Input
            aria-required={required}
            className="w-full rounded-xl border border-gray-300 bg-white p-2 pr-9 text-sm
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-60"
            displayValue={(opt: Option | null) => opt?.label ?? ""}
            onChange={e => setQuery(e.target.value)}
            placeholder={placeholder}
          />
          <Combobox.Button className="absolute inset-y-0 right-0 flex items-center px-2">
            <svg viewBox="0 0 20 20" fill="currentColor" className="h-4 w-4 text-gray-500">
              <path fillRule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clipRule="evenodd"/>
            </svg>
          </Combobox.Button>
        </div>

        <Transition as={Fragment} leave="transition ease-in duration-100" leaveFrom="opacity-100" leaveTo="opacity-0">
          <Combobox.Options
            className="absolute left-0 right-0 z-50 mt-1 max-h-60 w-full overflow-auto rounded-xl
                       border border-gray-200 bg-white p-1 text-sm shadow-lg"
          >
            {filtered.length === 0 ? (
              <div className="px-3 py-2 text-gray-500">No results</div>
            ) : (
              filtered.map(opt => (
                <Combobox.Option
                  key={opt.id}
                  value={opt}
                  className={({ active }) =>
                    `cursor-pointer rounded-lg px-3 py-2 ${active ? "bg-indigo-600 text-white" : ""}`
                  }
                >
                  {opt.label}
                </Combobox.Option>
              ))
            )}
          </Combobox.Options>
        </Transition>
      </Combobox>
    </div>
  );
}
