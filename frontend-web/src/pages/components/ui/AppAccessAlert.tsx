import React from "react";

type Props = { className?: string; appName: string };

export default function AppAccessAlert({ className = "", appName }: Props) {
  return (
    <div
      role="alert"
      className={`rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800 ${className}`}
    >
      <div className="font-semibold">Access denied</div>
      <div>Your account doesn’t have access to {appName}.</div>
    </div>
  );
}
