import { useEffect, useState } from "react";
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";
import napi from "@/utils/axiosnapi";

dayjs.extend(relativeTime);
dayjs.extend(utc);
dayjs.extend(timezone);

type ClassRow = {
  subject_code: string;
  title: string | null;
  units: number | null;
  section_code: string | null;
  final_grade: number | string | null;
  has_permit: boolean;
};

type Term = {
  sy: string;
  sem: string;
  display: string;
  year_level: number | null;
  classes: ClassRow[];
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
  terms: Term[];
};

export default function StudentGrades() {
  const [header, setHeader] = useState<StudentHeader | null>(null);
  const [terms, setTerms] = useState<Term[]>([]);
  const [open, setOpen] = useState<Record<string, boolean>>({});
  const [loading, setLoading] = useState(false);
  const [err, setErr] = useState<string | null>(null);

  const fetchData = async () => {
    setLoading(true);
    setErr(null);
    try {
      const res = await napi.get<ApiResponse>("/student/grades");
      setHeader(res.data.student);
      setTerms(res.data.terms || []);
      // open the most recent term by default
      if ((res.data.terms || []).length > 0) {
        const firstKey = `${res.data.terms[0].sy}|${res.data.terms[0].sem}`;
        setOpen({ [firstKey]: true });
      }
    } catch (e: any) {
      setErr(e?.message || "Failed to load grades.");
      setHeader(null);
      setTerms([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { fetchData(); }, []);

  const toggle = (k: string) => setOpen(prev => ({ ...prev, [k]: !prev[k] }));

  return (
    <div className="p-4 md:p-6 space-y-4">
      <h1 className="text-xl md:text-2xl font-semibold">Student Grades</h1>

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
          <div className="p-6 text-center text-gray-500">No grade records found.</div>
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
                    Year Level: {t.year_level ?? "—"} ({t.classes.length} subjects)
                  </div>
                </div>
                <div className="text-gray-500">{opened ? "▾" : "▸"}</div>
              </button>

              {opened && (
                <div className="px-4 pb-4">
                  <div className="overflow-x-auto">
                    <table className="min-w-full text-sm">
                      <thead>
                        <tr className="text-left text-gray-600 border-b">
                          <th className="py-2 pr-3">Subject</th>
                          <th className="py-2 pr-3">Description</th>
                          <th className="py-2 pr-3">Section</th>
                          <th className="py-2 pr-3">Units</th>
                          <th className="py-2 pr-3">Final Grade*</th>
                        </tr>
                      </thead>
                      <tbody>
                        {t.classes.map((c, i) => (
                          <tr key={c.subject_code + i} className="border-b last:border-b-0">
                            <td className="py-2 pr-3 whitespace-nowrap font-medium">{c.subject_code}</td>
                            <td className="py-2 pr-3">{c.title || "—"}</td>
                            <td className="py-2 pr-3 whitespace-nowrap">{c.section_code || "—"}</td>
                            <td className="py-2 pr-3 whitespace-nowrap">{c.units ?? "—"}</td>
                            
                            
<td
  className={
    "py-2 pr-3 whitespace-nowrap " +
    (!c.has_permit ? "text-red-600 font-semibold" : "")
  }
>
  {c.final_grade == null
    ? "—"
    : `${c.has_permit ? String(c.final_grade) : ""}${!c.has_permit ? "***" : ""}`}
</td>

                          
                          
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                  <div className="text-xs text-gray-500 mt-2">* Final grades shown. Entries without exam permit are highlighted in red and
  have <strong>***</strong> appended to the grade.</div>
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
