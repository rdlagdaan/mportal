// frontend-web/src/components/ProtectedRoute.tsx
import { Navigate, useNavigate } from "react-router-dom";
import useAuth from "@/hooks/useAuth";
import { logout } from "@/utils/axiosnapi";
import type { JSX } from 'react';

export default function ProtectedRoute({ children }: { children: JSX.Element }) {
  const { user, loading } = useAuth();
  const navigate = useNavigate();

  async function handleLogout() {
    try {
      await logout();
      navigate("/login");
    } catch {
      alert("Logout failed");
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen grid place-items-center">
        <div className="animate-pulse text-gray-600">Checking session…</div>
      </div>
    );
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  return (
    <div className="min-h-screen flex flex-col">
      {/* Header with logout */}
      <header className="bg-white shadow-sm flex items-center justify-between px-6 py-4">
        <div>
          <h1 className="text-lg font-semibold text-gray-900">
            Welcome, {user.name}
          </h1>
        </div>
        <button
          onClick={handleLogout}
          className="px-3 py-1.5 rounded-md bg-red-600 text-white hover:bg-red-700 text-sm font-medium"
        >
          Logout
        </button>
      </header>

      {/* Page content */}
      <main className="flex-1 bg-gray-50">{children}</main>
    </div>
  );
}
