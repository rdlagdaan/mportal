import { useEffect, useState } from "react";
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";
import calendar from "dayjs/plugin/calendar";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";
import napi from "@/utils/axiosnapi";

dayjs.extend(relativeTime);
dayjs.extend(calendar);
dayjs.extend(utc);
dayjs.extend(timezone);

type Notice = {
  id: number;
  notice_code: string;
  title: string;
  details: string;
  category: string | null;
  audience: string | null;
  target_student_number: string | null;
  start_date: string | null;
  end_date: string | null;
  venue: string | null;
  start_time: string | null;
  end_time: string | null;
  due_at: string | null;
  grace_until: string | null;
  importance: number;
  status: string;
  is_published: boolean;
  links: { label: string; url: string }[];
  created_at: string;
  updated_at: string;
};

type ApiMeta = {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
  order: string;
  dir: "asc" | "desc";
};

type ApiResponseLoose = any;

function unwrapListAndMeta(r: ApiResponseLoose): { list: Notice[]; meta: Partial<ApiMeta> } {
  const root = r ?? {};
  if (Array.isArray(root.data)) {
    return { list: root.data, meta: root.meta ?? {} };
  }
  if (root.data && Array.isArray(root.data.data)) {
    const p = root.data;
    const meta: Partial<ApiMeta> = root.meta ?? {
      total: p.total,
      per_page: p.per_page,
      current_page: p.current_page,
      last_page: p.last_page,
      order: r?.meta?.order,
      dir: r?.meta?.dir,
    };
    return { list: p.data, meta };
  }
  return { list: [], meta: root.meta ?? {} };
}

export default function Notices() {
  const [items, setItems] = useState<Notice[]>([]);
  const [q, setQ] = useState("");
  const [loading, setLoading] = useState(false);
  const [page, setPage] = useState(1);
  const [per, setPer] = useState(10);
  const [total, setTotal] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [error, setError] = useState<string | null>(null);

  const order = "due_at";
  const dir: "asc" | "desc" = "desc";

  const fetchData = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await napi.get<ApiResponseLoose>("/university/notices", {
        params: { q, page, per, order, dir },
      });
      const { list, meta } = unwrapListAndMeta(res.data);
      setItems(list ?? []);
      setTotal(typeof meta.total === "number" ? meta.total : (list?.length ?? 0));
      setLastPage(typeof meta.last_page === "number" ? meta.last_page : 1);
    } catch (e: any) {
      setError(e?.message || "Failed to load notices.");
      setItems([]);
      setTotal(0);
      setLastPage(1);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [q, page, per]);

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setPage(1);
    fetchData();
  };

  const badge = (n: number) => {
    const labels = ["Low", "Low", "Normal", "High", "Urgent", "Critical"];
    const text = labels[n] ?? "Normal";
    return (
      <span className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100">
        {text}
      </span>
    );
  };

  const chip = (text?: string | null) =>
    text ? (
      <span className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700">
        {text}
      </span>
    ) : null;

  const InfoRow = ({ label, value }: { label: string; value?: string | null }) =>
    value ? (
      <div className="text-sm">
        <span className="font-medium">{label}: </span>
        <span className="text-gray-700">{value}</span>
      </div>
    ) : null;

  return (
    <div className="p-4 md:p-6 space-y-4">
      <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <h1 className="text-xl md:text-2xl font-semibold">Important Notices</h1>
        <form onSubmit={onSubmit} className="flex gap-2">
          <input
            className="border rounded-lg px-3 py-2 w-64"
            placeholder="Search title, details, code…"
            value={q}
            onChange={(e) => setQ(e.target.value)}
          />
          <select
            className="border rounded-lg px-2 py-2"
            value={per}
            onChange={(e) => {
              setPer(parseInt(e.target.value, 10));
              setPage(1);
            }}
          >
            {[10, 20, 50].map((n) => (
              <option key={n} value={n}>
                {n}/page
              </option>
            ))}
          </select>
          <button
            type="submit"
            className="rounded-lg px-4 py-2 bg-emerald-600 text-white hover:bg-emerald-700"
          >
            Search
          </button>
        </form>
      </div>

      <div className="border rounded-xl overflow-hidden">
        <div className="bg-gray-50 px-4 py-2 text-sm text-gray-600">
          Showing {items.length} of {total} — sorted by Due Date (newest first)
        </div>

        {error && <div className="px-4 py-3 text-sm text-red-700 bg-red-50">{error}</div>}

        <div className="divide-y">
          {loading && <div className="p-6 text-center text-gray-500">Loading…</div>}

          {!loading && !error && items.length === 0 && (
            <div className="p-6 text-center text-gray-500">No notices found.</div>
          )}

          {!loading &&
            !error &&
            items.map((n) => {
              const s = n.start_date ? dayjs(n.start_date).format("MMM D, YYYY") : null;
              const e = n.end_date ? dayjs(n.end_date).format("MMM D, YYYY") : null;
              const dateRange = s && e ? `${s} — ${e}` : s || e || "";

              const due = n.due_at ? dayjs(n.due_at).format("MMM D, YYYY h:mm A") : null;
              const grace = n.grace_until ? dayjs(n.grace_until).format("MMM D, YYYY h:mm A") : null;

              return (
                <article key={n.id} className="p-4 md:p-6 hover:bg-gray-50">
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <div className="flex items-center gap-2 flex-wrap">
                        <h2 className="text-lg font-semibold">{n.title}</h2>
                        {badge(n.importance ?? 2)}
                        {chip(n.category)}
                      </div>
                      <div className="text-xs text-gray-500">
                        Code: {n.notice_code} • Updated {dayjs(n.updated_at).fromNow()}
                      </div>
                    </div>
                  </div>

                  <div className="mt-3 text-gray-800 whitespace-pre-wrap">{n.details}</div>

                  <div className="mt-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    {dateRange && <InfoRow label="Date" value={dateRange} />}
                    {n.venue && <InfoRow label="Venue" value={n.venue} />}
                    {(n.start_time || n.end_time) && (
                      <InfoRow
                        label="Time"
                        value={
                          n.start_time && n.end_time
                            ? `${n.start_time}–${n.end_time}`
                            : (n.start_time || n.end_time)!
                        }
                      />
                    )}
                    {n.audience && <InfoRow label="Audience" value={n.audience} />}
                    {n.target_student_number && (
                      <InfoRow label="Target" value={n.target_student_number} />
                    )}
                    {due && <InfoRow label="Due" value={due} />}
                    {grace && <InfoRow label="Grace Until" value={grace} />}
                    <InfoRow label="Status" value={n.status} />
                  </div>

                  {Array.isArray(n.links) && n.links.length > 0 && (
                    <div className="mt-3 flex flex-wrap gap-2">
                      {n.links.map((l, i) => (
                        <a
                          key={i}
                          href={l.url}
                          target="_blank"
                          rel="noreferrer"
                          className="text-emerald-700 hover:underline text-sm"
                        >
                          {l.label}
                        </a>
                      ))}
                    </div>
                  )}
                </article>
              );
            })}
        </div>

        <div className="flex items-center justify-between px-4 py-3 bg-gray-50">
          <div className="text-sm text-gray-600">Page {page} of {lastPage}</div>
          <div className="flex gap-2">
            <button className="px-3 py-1 rounded border disabled:opacity-50" onClick={() => setPage(1)} disabled={page <= 1}>First</button>
            <button className="px-3 py-1 rounded border disabled:opacity-50" onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page <= 1}>Prev</button>
            <button className="px-3 py-1 rounded border disabled:opacity-50" onClick={() => setPage(p => Math.min(lastPage, p + 1))} disabled={page >= lastPage}>Next</button>
            <button className="px-3 py-1 rounded border disabled:opacity-50" onClick={() => setPage(lastPage)} disabled={page >= lastPage}>Last</button>
          </div>
        </div>
      </div>
    </div>
  );
}
