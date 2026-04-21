import { useEffect, useState } from "react";
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";
import napi from "@/utils/axiosnapi";

dayjs.extend(relativeTime);
dayjs.extend(utc);
dayjs.extend(timezone);

const peso = (n: number | null | undefined) =>
  typeof n === "number" ? n.toLocaleString(undefined, { style: "currency", currency: "PHP" }) : "—";

type Assessment = {
  tuition_fee: number;
  miscellaneous_fee: number;
  other_fee: number;
  installment_fee: number;
  additional_fee: number;
  total: number;
};

type Payment = {
  or_number: number | null;
  bir_number: number | null;
  payment_for: string | null;
  transaction_id: string | null;
  date_paid: string | null; // ISO
  amount_paid: number;
  running_balance: number;
};

type Term = {
  sy: string;
  sem: string;
  display: string;
  assessment: Assessment;
  payments: Payment[];
  total_paid: number;
  opening_balance: number;   // NEW
  starting_balance: number;  // NEW = opening + assessment.total
  balance: number;           // ending
};

type StudentHeader = {
  student_number: string;
  full_name: string;
  course_code: string | null;
  course_name: string | null;
  college: string | null;
  year_level: number | null;
};

type ApiResponse = {
  ok: boolean;
  student: StudentHeader;
  terms: Term[];           // ASC order from backend
  final_balance?: number;  // optional
};

export default function StudentLedger() {
  const [header, setHeader] = useState<StudentHeader | null>(null);
  const [terms, setTerms] = useState<Term[]>([]);
  const [open, setOpen] = useState<Record<string, boolean>>({});
  const [loading, setLoading] = useState(false);
  const [err, setErr] = useState<string | null>(null);

  const fetchData = async () => {
    setLoading(true);
    setErr(null);
    try {
      const res = await napi.get<ApiResponse>("/student/ledger");
      setHeader(res.data.student);
      setTerms(res.data.terms || []);
      // Open the LATEST term by default (last item because ASC order)
      if ((res.data.terms || []).length > 0) {
        const last = res.data.terms[res.data.terms.length - 1];
        const k = `${last.sy}|${last.sem}`;
        setOpen({ [k]: true });
      }
    } catch (e: any) {
      setErr(e?.message || "Failed to load ledger.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { fetchData(); }, []);

  const toggle = (k: string) => setOpen(prev => ({ ...prev, [k]: !prev[k] }));

  return (
    <div className="p-4 md:p-6 space-y-4">
      <h1 className="text-xl md:text-2xl font-semibold">Student Ledger</h1>

      {err && <div className="px-4 py-3 bg-red-50 text-red-700 text-sm">{err}</div>}

      {header && (
        <div className="rounded-xl border p-4 md:p-5 bg-white">
          <div className="text-lg font-semibold">{header.full_name}</div>
          <div className="text-sm text-gray-700 mt-1">
            <span className="font-medium">Student No:</span> {header.student_number}
          </div>
          <div className="text-sm text-gray-700 mt-1">
            <span className="font-medium">College:</span> {header.college || "—"}
            <span className="mx-2">•</span>
            <span className="font-medium">Course:</span> {header.course_code || "—"} {header.course_name ? `— ${header.course_name}` : ""}
            <span className="mx-2">•</span>
            <span className="font-medium">Year Level:</span> {header.year_level ?? "—"}
          </div>
        </div>
      )}

      <div className="rounded-xl border overflow-hidden bg-white">
        {loading && <div className="p-6 text-center text-gray-500">Loading…</div>}
        {!loading && terms.length === 0 && (
          <div className="p-6 text-center text-gray-500">No ledger records found.</div>
        )}

        {!loading && terms.map((t) => {
          const k = `${t.sy}|${t.sem}`;
          const opened = !!open[k];
          return (
            <div key={k} className="border-b last:border-b-0">
              <button
                className="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-50"
                onClick={() => toggle(k)}
              >
                <div className="text-left">
                  <div className="font-medium">{t.display}</div>
                  <div className="text-xs text-gray-500">
                    Carry-in: {peso(t.opening_balance)} • Assessment: {peso(t.assessment.total)} • Paid: {peso(t.total_paid)} •
                    <span className={`ml-1 ${t.balance > 0 ? "text-red-600 font-semibold" : ""}`}>
                      Ending Balance: {peso(t.balance)}
                    </span>
                  </div>
                </div>
                <div className="text-gray-500">{opened ? "▾" : "▸"}</div>
              </button>

              {opened && (
                <div className="px-4 pb-4 space-y-4">
                  {/* Quick note on start-of-term */}
                  <div className="text-xs text-gray-600">
                    Beginning balance this term: <span className="font-medium">{peso(t.starting_balance)}</span>
                    {" "} (Carry-in {peso(t.opening_balance)} + Assessment {peso(t.assessment.total)})
                  </div>

                  {/* Assessment breakdown */}
                  <div className="overflow-x-auto">
                    <table className="min-w-full text-sm">
                      <thead>
                        <tr className="text-left text-gray-600 border-b">
                          <th className="py-2 pr-3">Item</th>
                          <th className="py-2 pr-3">Amount</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr><td className="py-2 pr-3">Tuition Fee</td><td className="py-2 pr-3">{peso(t.assessment.tuition_fee)}</td></tr>
                        <tr><td className="py-2 pr-3">Miscellaneous Fee</td><td className="py-2 pr-3">{peso(t.assessment.miscellaneous_fee)}</td></tr>
                        <tr><td className="py-2 pr-3">Other Fee</td><td className="py-2 pr-3">{peso(t.assessment.other_fee)}</td></tr>
                        <tr><td className="py-2 pr-3">Installment Fee</td><td className="py-2 pr-3">{peso(t.assessment.installment_fee)}</td></tr>
                        <tr><td className="py-2 pr-3">Additional Fee</td><td className="py-2 pr-3">{peso(t.assessment.additional_fee)}</td></tr>
                        <tr className="border-t font-semibold">
                          <td className="py-2 pr-3">Total Assessment</td>
                          <td className="py-2 pr-3">{peso(t.assessment.total)}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  {/* Payments */}
                  <div className="overflow-x-auto">
                    <table className="min-w-full text-sm">
                      <thead>
                        <tr className="text-left text-gray-600 border-b">
                          <th className="py-2 pr-3">Date Paid</th>
                          <th className="py-2 pr-3">OR #</th>
                          <th className="py-2 pr-3">BIR #</th>
                          <th className="py-2 pr-3">Payment For</th>
                          <th className="py-2 pr-3">Transaction ID</th>
                          <th className="py-2 pr-3">Amount Paid</th>
                          <th className="py-2 pr-3">Running Balance</th>
                        </tr>
                      </thead>
                      <tbody>
                        {t.payments.length === 0 && (
                          <tr><td className="py-3 text-gray-500" colSpan={7}>No payments recorded.</td></tr>
                        )}
                        {t.payments.map((p, i) => (
                          <tr key={i} className="border-b last:border-b-0">
                            <td className="py-2 pr-3 whitespace-nowrap">{p.date_paid ? dayjs(p.date_paid).format("MMM D, YYYY h:mm A") : "—"}</td>
                            <td className="py-2 pr-3 whitespace-nowrap">{p.or_number ?? "—"}</td>
                            <td className="py-2 pr-3 whitespace-nowrap">{p.bir_number ?? "—"}</td>
                            <td className="py-2 pr-3">{p.payment_for || "—"}</td>
                            <td className="py-2 pr-3 whitespace-nowrap">{p.transaction_id || "—"}</td>
                            <td className="py-2 pr-3 whitespace-nowrap">{peso(p.amount_paid)}</td>
                            <td className={`py-2 pr-3 whitespace-nowrap ${p.running_balance > 0 ? "text-red-600 font-semibold" : ""}`}>
                              {peso(p.running_balance)}
                            </td>
                          </tr>
                        ))}
                        {t.payments.length > 0 && (
                          <tr className="border-t font-semibold">
                            <td className="py-2 pr-3" colSpan={5}>Total Paid</td>
                            <td className="py-2 pr-3">{peso(t.total_paid)}</td>
                            <td className={`py-2 pr-3 ${t.balance > 0 ? "text-red-600 font-semibold" : ""}`}>
                              {peso(t.balance)}
                            </td>
                          </tr>
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
