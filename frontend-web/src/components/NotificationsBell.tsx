import { useEffect, useState } from "react";
import echo from "@/utils/echo";
// import napi from "@/utils/axiosnapi"; // not needed here

export default function NotificationsBell({
  userId,
  employeeId,
}: { userId?: number; employeeId?: number }) {
  const [me, setMe] = useState<{ id?: number; employee_id?: number } | null>(null);
  const [count, setCount] = useState(0);

  // Ensure /app-scoped CSRF cookie, then fetch the current user
  // Ensure /app-scoped CSRF cookie, then fetch the current user
  useEffect(() => {
    if (userId && employeeId) return;

    (async () => {
      try {
        console.log("[bell] get csrf cookie @ /app/lrwsis/csrf-cookie");
        await fetch("/app/lrwsis/csrf-cookie", { credentials: "include" });

        // Try LRWSIS /me first
        console.log("[bell] get /app/api/lrwsis/me");
        let resp = await fetch("/app/api/lrwsis/me", { credentials: "include" });

        // If blocked by gates, fall back to simple whoami
        if (!resp.ok) {
          console.warn("[bell] /app/api/lrwsis/me failed", resp.status, "→ fallback /app/api/whoami");
          resp = await fetch("/app/api/whoami", { credentials: "include" });
        }
        if (!resp.ok) {
          console.warn("[bell] whoami failed too", resp.status);
          return;
        }

        const json = await resp.json();
        const user = json?.user ?? json;
        const id = user?.id ?? undefined;
        const eid = user?.employee_id ?? undefined;

        setMe({ id, employee_id: eid });
        console.log("[bell] resolved IDs =", { id, employee_id: eid });
      } catch (e) {
        console.warn("[bell] bootstrap (csrf + id) failed", e);
      }
    })();
  }, [userId, employeeId]);


  // Subscribe once we have IDs
  useEffect(() => {
    const uid = userId ?? me?.id;
    const eid = employeeId ?? me?.employee_id;
    if (!uid && !eid) return;

    // Ensure Echo’s auth headers include XSRF (belt & suspenders)
    const xsrf = decodeURIComponent(
      (document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)?.[1] ?? "")
    );
    (echo as any).connector.options.auth = (echo as any).connector.options.auth || {};
    (echo as any).connector.options.auth.headers = {
      "X-Requested-With": "XMLHttpRequest",
      "X-XSRF-TOKEN": xsrf,
    };

    console.log("[bell] subscribing", { uid, eid });

    let chanUser: any = null;
    let chanLeave: any = null;

    if (uid) {
      chanUser = echo
        .private(`App.Models.User.${uid}`)
        .notification((n: any) => {
          console.log("[bell] notification", n);
          setCount((c) => c + 1);
        });
    }

    if (eid) {
      chanLeave = echo
        .private(`leave.status.${eid}`) // no "private-" prefix
        .listen(".LeaveStatusUpdated", (payload: any) => {
          console.log("[bell] LeaveStatusUpdated", payload);
          setCount((c) => c + 1);
        });
    }

    return () => {
      try {
        chanUser?.stopListening("notification");
        chanLeave?.stopListening(".LeaveStatusUpdated");
      } catch {}
    };
  }, [userId, employeeId, me?.id, me?.employee_id]);

  return (
    <button
      type="button"
      className="relative inline-flex items-center justify-center h-10 w-10 rounded-full bg-white/90 text-green-700 hover:bg-white shadow ring-1 ring-green-600/20"
      title="Notifications"
      aria-label="Notifications"
    >
      <span className="text-lg">🔔</span>
      {count > 0 && (
        <span className="absolute -top-1 -right-1 min-w-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] leading-4 text-center">
          {count}
        </span>
      )}
    </button>
  );
}
