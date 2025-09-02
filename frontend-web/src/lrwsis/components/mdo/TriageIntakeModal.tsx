import { useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import napi from "@/utils/axiosnapi";

type NewEncounterPayload = {
  company_id: number;
  person_id: number;
  provider_id?: number | null;
  chief_complaint?: string;
  encounter_type?: "clinic" | "dental" | "immunization";
};

type VitalsPayload = {
  encounter_id: number;
  systolic?: number | null;
  diastolic?: number | null;
  hr?: number | null;
  rr?: number | null;
  temp_c?: number | null;
  spo2?: number | null;
  height_cm?: number | null;
  weight_kg?: number | null;
};

/* ------------------- Field helpers ------------------- */
function Field({
  label,
  children,
  required,
  help,
}: {
  label: string;
  children: React.ReactNode;
  required?: boolean;
  help?: string;
}) {
  return (
    <div className="space-y-1.5">
      <label className="block text-sm font-medium text-gray-800">
        {label}
        {required && <span className="text-red-600 ml-0.5">*</span>}
      </label>
      {children}
      {help && <p className="text-xs text-gray-500">{help}</p>}
    </div>
  );
}

function NumberInput(props: React.InputHTMLAttributes<HTMLInputElement>) {
  return (
    <input
      type="number"
      inputMode="decimal"
      {...props}
      className={[
        "block w-full rounded-lg border border-gray-300 bg-white px-3 py-2",
        "text-gray-900 shadow-sm outline-none",
        "placeholder:text-gray-400",
        "focus:border-amber-500 focus:ring-2 focus:ring-amber-200",
        props.className || "",
      ].join(" ")}
      onWheel={(e) => (e.target as HTMLInputElement).blur()}
    />
  );
}

function TextArea(props: React.TextareaHTMLAttributes<HTMLTextAreaElement>) {
  return (
    <textarea
      {...props}
      className={[
        "block w-full rounded-lg border border-gray-300 bg-white px-3 py-2",
        "text-gray-900 shadow-sm outline-none",
        "placeholder:text-gray-400",
        "focus:border-amber-500 focus:ring-2 focus:ring-amber-200",
        props.className || "",
      ].join(" ")}
    />
  );
}

/* =======================================================================
   Inner modal component (logic + UI)
======================================================================= */
function TriageIntakeInner({
  open,
  onClose,
  defaultCompanyId = 1,
}: {
  open: boolean;
  onClose: () => void;
  defaultCompanyId?: number;
}) {
  const [companyId, setCompanyId] = useState<number>(defaultCompanyId);
  const [personId, setPersonId] = useState<number>(0);
  const [providerId, setProviderId] = useState<number | undefined>(undefined);
  const [chiefComplaint, setChiefComplaint] = useState("");
  const [encounterId, setEncounterId] = useState<number | null>(null);
  const [saving, setSaving] = useState(false);
  const [msg, setMsg] = useState<string>("");

  // vitals
  const [systolic, setSystolic] = useState<number | undefined>();
  const [diastolic, setDiastolic] = useState<number | undefined>();
  const [hr, setHr] = useState<number | undefined>();
  const [rr, setRr] = useState<number | undefined>();
  const [tempC, setTempC] = useState<number | undefined>();
  const [spo2, setSpo2] = useState<number | undefined>();
  const [heightCm, setHeightCm] = useState<number | undefined>();
  const [weightKg, setWeightKg] = useState<number | undefined>();

  const dialogRef = useRef<HTMLDivElement | null>(null);
  const firstFocusableRef = useRef<HTMLButtonElement | null>(null);

  // Reset + lock scroll
  useEffect(() => {
    if (!open) return;
    setMsg("");
    setSaving(false);

    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    const id = window.setTimeout(() => firstFocusableRef.current?.focus(), 0);

    return () => {
      document.body.style.overflow = prev;
      window.clearTimeout(id);
    };
  }, [open]);

  // Esc key
  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") onClose();
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [open, onClose]);

  function handleBackdropClick(e: React.MouseEvent<HTMLDivElement>) {
    if (dialogRef.current && !dialogRef.current.contains(e.target as Node)) {
      onClose();
    }
  }

  async function openEncounter(e: React.FormEvent) {
    e.preventDefault();
    if (!companyId || !personId) {
      setMsg("Company and Person are required.");
      return;
    }
    setSaving(true);
    setMsg("");
    try {
      const payload: NewEncounterPayload = {
        company_id: companyId,
        person_id: personId,
        provider_id: providerId,
        chief_complaint: chiefComplaint?.trim() || undefined,
        encounter_type: "clinic",
      };
      const { data } = await napi.post("/mdo/triage/encounters", payload);
      setEncounterId(data.encounter_id);
      setMsg(`Encounter opened: ${data.encounter_id}`);
    } catch (e: any) {
      setMsg(e?.response?.data?.message || "Failed to open encounter");
    } finally {
      setSaving(false);
    }
  }

  async function saveVitals() {
    if (!encounterId) {
      setMsg("Open an encounter first.");
      return;
    }
    setSaving(true);
    setMsg("");
    try {
      const payload: VitalsPayload = {
        encounter_id: encounterId,
        systolic,
        diastolic,
        hr,
        rr,
        temp_c: tempC,
        spo2,
        height_cm: heightCm,
        weight_kg: weightKg,
      };
      await napi.post("/mdo/triage/vitals", payload);
      setMsg("Vitals saved.");
    } catch (e: any) {
      setMsg(e?.response?.data?.message || "Failed to save vitals");
    } finally {
      setSaving(false);
    }
  }

  async function sendToQueue() {
    if (!encounterId) {
      setMsg("Open an encounter first.");
      return;
    }
    setSaving(true);
    setMsg("");
    try {
      const { data } = await napi.post("/mdo/triage/queue", {
        company_id: companyId,
        encounter_id: encounterId,
        station: "consult",
      });
      setMsg(`Queued at position ${data.position}`);
    } catch (e: any) {
      setMsg(e?.response?.data?.message || "Failed to queue");
    } finally {
      setSaving(false);
    }
  }

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-[1000] flex items-center justify-center p-4"
      onMouseDown={handleBackdropClick}
      role="dialog"
      aria-modal="true"
    >
      <div className="absolute inset-0 bg-black/40" />

      <div
        ref={dialogRef}
        className="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-4 ring-1 ring-gray-200 max-h-[85vh] overflow-y-auto"
        onMouseDown={(e) => e.stopPropagation()}
      >
        <div className="sticky top-0 bg-white/90 backdrop-blur border-b rounded-t-2xl">
          <div className="flex items-center justify-between p-4">
            <h2 className="text-xl font-semibold">New Encounter</h2>
            <button
              type="button"
              onClick={onClose}
              className="p-1 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-amber-300"
              ref={firstFocusableRef}
            >
              ✕
            </button>
          </div>
        </div>

        <form onSubmit={openEncounter} className="p-6 space-y-6">
          {/* Encounter Info */}
          <div className="rounded-xl border border-gray-200 bg-gray-50/40 p-4">
            <h4 className="mb-3 text-base font-semibold text-gray-800">
              Encounter Information
            </h4>
            <div className="grid grid-cols-12 gap-4">
              <div className="col-span-12 md:col-span-3">
                <Field label="Company ID" required>
                  <NumberInput
                    value={companyId}
                    placeholder="e.g. 1"
                    onChange={(e) =>
                      setCompanyId(
                        (e.target as HTMLInputElement).value === ""
                          ? defaultCompanyId
                          : Number((e.target as HTMLInputElement).value)
                      )
                    }
                  />
                </Field>
              </div>
              <div className="col-span-12 md:col-span-3">
                <Field label="Person ID" required help="Patient identifier">
                  <NumberInput
                    value={personId || ""}
                    placeholder="Enter patient ID"
                    onChange={(e) =>
                      setPersonId(
                        (e.target as HTMLInputElement).value === ""
                          ? 0
                          : Number((e.target as HTMLInputElement).value)
                      )
                    }
                  />
                </Field>
              </div>
              <div className="col-span-12 md:col-span-3">
                <Field label="Provider ID (optional)">
                  <NumberInput
                    value={providerId ?? ""}
                    placeholder="Enter provider ID"
                    onChange={(e) =>
                      setProviderId(
                        (e.target as HTMLInputElement).value === ""
                          ? undefined
                          : Number((e.target as HTMLInputElement).value)
                      )
                    }
                  />
                </Field>
              </div>
              <div className="col-span-12">
                <Field label="Chief Complaint">
                  <TextArea
                    rows={3}
                    placeholder="Fever, cough, headache since yesterday"
                    value={chiefComplaint}
                    onChange={(e) =>
                      setChiefComplaint((e.target as HTMLTextAreaElement).value)
                    }
                  />
                </Field>
              </div>
            </div>
          </div>

          {/* Vitals */}
          <div className="rounded-xl border border-gray-200 bg-gray-50/40 p-4">
            <h4 className="mb-3 text-base font-semibold text-gray-800">Vitals</h4>
            <div className="grid grid-cols-12 gap-4">
              <div className="col-span-12 sm:col-span-3">
                <Field label="Systolic">
                  <NumberInput
                    placeholder="120"
                    value={systolic ?? ""}
                    onChange={(e) =>
                      setSystolic(
                        (e.target as HTMLInputElement).value === ""
                          ? undefined
                          : Number((e.target as HTMLInputElement).value)
                      )
                    }
                  />
                </Field>
              </div>
              <div className="col-span-12 sm:col-span-3">
                <Field label="Diastolic">
                  <NumberInput
                    placeholder="80"
                    value={diastolic ?? ""}
                    onChange={(e) =>
                      setDiastolic(
                        (e.target as HTMLInputElement).value === ""
                          ? undefined
                          : Number((e.target as HTMLInputElement).value)
                      )
                    }
                  />
                </Field>
              </div>
              <div className="col-span-12 sm:col-span-3">
                <Field label="HR">
                  <NumberInput
                    placeholder="beats/min"
                    value={hr ?? ""}
                    onChange={(e) =>
                      setHr(
                        (e.target as HTMLInputElement).value === ""
                          ? undefined
                          : Number((e.target as HTMLInputElement).value)
                      )
                    }
                  />
                </Field>
              </div>
              <div className="col-span-12 sm:col-span-3">
                <Field label="RR">
                  <NumberInput
                    placeholder="breaths/min"
                    value={rr ?? ""}
                    onChange={(e) =>
                      setRr(
                        (e.target as HTMLInputElement).value === ""
                          ? undefined
                          : Number((e.target as HTMLInputElement).value)
                      )
                    }
                  />
                </Field>
              </div>
              <div className="col-span-12 sm:col-span-3">
                <Field label="Temp °C">
                  <NumberInput
                    step="0.1"
                    placeholder="37.2"
                    value={tempC ?? ""}
                    onChange={(e) =>
                      setTempC(
                        (e.target as HTMLInputElement).value === ""
                          ? undefined
                          : Number((e.target as HTMLInputElement).value)
                      )
                    }
                  />
                </Field>
              </div>
              <div className="col-span-12 sm:col-span-3">
                <Field label="SpO₂ %">
                  <NumberInput
                    placeholder="98"
                    value={spo2 ?? ""}
                    onChange={(e) =>
                      setSpo2(
                        (e.target as HTMLInputElement).value === ""
                          ? undefined
                          : Number((e.target as HTMLInputElement).value)
                      )
                    }
                  />
                </Field>
              </div>
              <div className="col-span-12 sm:col-span-3">
                <Field label="Height (cm)">
                  <NumberInput
                    step="0.1"
                    placeholder="170"
                    value={heightCm ?? ""}
                    onChange={(e) =>
                      setHeightCm(
                        (e.target as HTMLInputElement).value === ""
                          ? undefined
                          : Number((e.target as HTMLInputElement).value)
                      )
                    }
                  />
                </Field>
              </div>
              <div className="col-span-12 sm:col-span-3">
                <Field label="Weight (kg)">
                  <NumberInput
                    step="0.1"
                    placeholder="65"
                    value={weightKg ?? ""}
                    onChange={(e) =>
                      setWeightKg(
                        (e.target as HTMLInputElement).value === ""
                          ? undefined
                          : Number((e.target as HTMLInputElement).value)
                      )
                    }
                  />
                </Field>
              </div>
            </div>
          </div>

          {msg && (
            <div className="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-indigo-800">
              {msg}
            </div>
          )}

          <div className="flex justify-end gap-2">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-800 hover:bg-gray-100"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={saving || !companyId || !personId}
              className="px-5 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50"
            >
              {saving ? "Saving…" : "Save"}
            </button>
          </div>

          <div className="mt-3 flex gap-2">
            <button
              type="button"
              onClick={saveVitals}
              disabled={saving || !encounterId}
              className="px-3 py-1.5 rounded border border-sky-600 text-sky-700 hover:bg-sky-50 disabled:opacity-50"
            >
              Save Vitals
            </button>
            <button
              type="button"
              onClick={sendToQueue}
              disabled={saving || !encounterId}
              className="px-3 py-1.5 rounded border border-amber-600 text-amber-700 hover:bg-amber-50 disabled:opacity-50"
            >
              Send to Consult Queue
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* =======================================================================
   Default export as a PORTAL
======================================================================= */
export default function TriageIntakeModal(props: {
  open: boolean;
  onClose: () => void;
  defaultCompanyId?: number;
}) {
  if (!props.open) return null;
  return createPortal(<TriageIntakeInner {...props} />, document.body);
}
