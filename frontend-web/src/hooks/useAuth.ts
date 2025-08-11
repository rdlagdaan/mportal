// frontend-web/src/hooks/useAuth.ts
import { useEffect, useState, useCallback } from "react";

export type AuthUser = { id: number; name: string; email: string } | null;

export default function useAuth() {
  const [user, setUser] = useState<AuthUser>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
        // frontend-web/src/hooks/useAuth.ts
        // change the fetch line to use absolute API path:
        const res = await fetch(`/api/me`, {
        credentials: "include",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        });

      if (res.ok) {
        const data = await res.json();
        setUser(data?.user ?? null);
      } else {
        setUser(null);
      }
    } catch (e: any) {
      setError(e?.message ?? "Auth check failed");
      setUser(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    refresh();
  }, [refresh]);

  return { user, loading, error, refresh };
}
