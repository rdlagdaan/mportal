export type KnownAuthError = "ACCESS_DENIED" | "INVALID_CREDENTIALS" | "UNKNOWN";

export function classifyAuthError(err: any): KnownAuthError {
  const status = err?.response?.status;
  const code = err?.response?.data?.code;

  if (status === 403 && code === "APP_ACCESS_DENIED") return "ACCESS_DENIED";
  if (status === 401) return "INVALID_CREDENTIALS";
  return "UNKNOWN";
}
