import { ReactNode, useEffect, useState } from "react";
import { Navigate } from "react-router-dom";
import { getWithCreds } from "@/utils/axiosnapi";

type Props = { children: ReactNode };

export default function ProtectedRoute({ children }: Props) {
  const [status, setStatus] = useState<"checking" | "ok" | "no">("checking");

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        // uses axiosnapi with withCredentials=true
        const res = await getWithCreds("/me");
        const user = res?.data?.user;
        if (!cancelled) setStatus(user ? "ok" : "no");
      } catch {
        if (!cancelled) setStatus("no");
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  if (status === "checking") return null; // or a small spinner
  if (status === "ok") return <>{children}</>;
  return <Navigate to="/login" replace />;
}
