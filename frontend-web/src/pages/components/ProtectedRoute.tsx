import { ReactNode, useEffect, useState } from "react";
import { Navigate } from "react-router-dom";
import { getWithCreds } from "@/utils/axiosnapi";

type Props = { children: ReactNode };

export default function ProtectedRoute({ children }: Props) {
  const [status, setStatus] = useState<"checking" | "ok" | "no">("checking");

  useEffect(() => {
    let cancelled = false;
    (async () => {
      console.log("[PROTECT] checking /api/me …");
      try {
        const res = await getWithCreds("/me");
        console.log("[PROTECT] /api/me", res.status, res.data);
        const user = res?.data?.user;
        if (!cancelled) setStatus(user ? "ok" : "no");
      } catch (err) {
        console.log("[PROTECT] /api/me ERROR", err);
        if (!cancelled) setStatus("no");
      }
    })();
    return () => { cancelled = true; };
  }, []);

  console.log("[PROTECT] render status =", status);
  if (status === "checking") return null;
  if (status === "ok") return <>{children}</>;
  return <Navigate to="/login" replace />;
}
