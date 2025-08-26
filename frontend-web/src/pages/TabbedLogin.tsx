// src/components/TabbedLogin.tsx
import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { getWithCreds, postWithCsrf } from "@/utils/axiosnapi";
import { useLocation } from 'react-router-dom'
import { getMicro, postMicro } from '@/utils/axios-micro';
import AppAccessAlert from "./components/ui/AppAccessAlert";
import { classifyAuthError } from "@/utils/handleAuthError";
import axiosMicro from "../utils/axios-micro";
import napi from "../utils/axiosnapi";      // for LRWSIS + OPENU
import axiosOpen from "../utils/axios-open";   // OPENU (/open/api/...)
import { loginLrwsis } from "@/utils/axios-lrwsis";
const tabs = [
  { name: "LrWSIS", color: "bg-yellow-400 text-white" },
  { name: "TUA Online University", color: "bg-green-600 text-white" },
  { name: "TUA Microcredentials", color: "bg-yellow-200 text-green-700" },
];

type RegistrationData = {
  lastName: string;
  firstName: string;
  middleName: string;
  mobile: string;
  email: string;
  password: string;
  confirmPassword: string;
  consent: boolean;
};

async function ensureLrwsisCsrf() {
  // This hits /app/lrwsis/csrf-cookie so the XSRF-TOKEN is issued
  // under the lrwsis bucket (Path=/app). Axios will then send it automatically.
  await fetch('/app/lrwsis/csrf-cookie', { credentials: 'include' });
}



export default function TabbedLogin() {
  const [activeTab, setActiveTab] = useState(0);
  const [showReg, setShowReg] = useState(false);

  // Login form state
  const [loginEmail, setLoginEmail] = useState("");
  const [loginPassword, setLoginPassword] = useState("");
  const [remember, setRemember] = useState(false);
  const [loading, setLoading] = useState(false);
  const [loginError, setLoginError] = useState("");

  const navigate = useNavigate();

  const { search } = useLocation()
  const justLoggedOut = new URLSearchParams(search).get('loggedout') === '1'

  const [accessDenied, setAccessDenied] = useState(false);
  const [accessDeniedApp, setAccessDeniedApp] = useState<string | null>(null);
  const [genericError, setGenericError] = useState<string | null>(null);


// inside TabbedLogin.tsx
const isLrwsis = activeTab === 0
const isOpenu  = activeTab === 1;
const isMicro  = activeTab === 2





useEffect(() => {
  if (justLoggedOut) return;

  (async () => {
    try {
      // ✅ MICRO ONLY
      const m = await getMicro('/microcredentials/me') // -> /api/microcredentials/me
      if (m?.data?.user) {
        window.location.replace('/app/courses')
        return
      }

      // ❌ remove this block to avoid foreign-session redirects
      // const l = await getWithCreds('/lrwsis/me').catch(() => null)
      // if (l?.data?.user) { window.location.replace('/lrwsis/dashboard'); return }

    } catch {}
  })()
}, [justLoggedOut])





  const handleApplyNow = () => {
    if (isMicro) setShowReg(true);
  };

  // ✅ PUT THE HANDLER INSIDE THE COMPONENT (here)

async function handleLoginSubmit(e: React.FormEvent) {
  e.preventDefault();
  setAccessDenied(false);
  setAccessDeniedApp(null);
  setGenericError(null);

  try {
    if (isMicro) {
      await axiosMicro.post("/microcredentials/login", {
        email: loginEmail,
        password: loginPassword,
        remember,
      });
      // success path for Micro:
      navigate("/courses");
      return;
    }

    if (isLrwsis) {
      setLoading(true);
      setLoginError("");
      try {
        // 1) get CSRF for the lrwsis bucket
        await ensureLrwsisCsrf();

        // 2) login (napi baseURL is /app/api → /app/api/lrwsis/login)
        const res = await napi.post("/lrwsis/login", {
          email: loginEmail,
          password: loginPassword,
          remember,
        });

        // 3) (optional) persist user and redirect to DashboardSis
        if (res?.data?.user) {
          localStorage.setItem("user", JSON.stringify(res.data.user));
        }
        navigate("/lrwsis/dashboard", { replace: true });
        return;
      } catch (err: any) {
        const status = err?.response?.status;
        const code = err?.response?.data?.code;

        if (status === 403 && code === "APP_ACCESS_DENIED") {
          setAccessDenied(true);
          setAccessDeniedApp("LRWSIS");
          return;
        }
        if (status === 401) {
          setLoginError("Invalid email or password.");
          return;
        }
        setLoginError("Something went wrong. Please try again.");
        return;
      } finally {
        setLoading(false);
      }
    }

    if (isOpenu) {
      // axiosOpen has baseURL "/open/api" → becomes /open/api/login ✔
      await axiosOpen.post("/login", {
        email: loginEmail,
        password: loginPassword,
        remember,
      });
      // TODO: navigate to OPENU landing
      return;
    }
  } catch (err: any) {
    const status = err?.response?.status;
    const code = err?.response?.data?.code;

    // Friendly banner on 403
    if (status === 403 && code === "APP_ACCESS_DENIED") {
      setAccessDenied(true);
      if (isMicro) setAccessDeniedApp("Microcredentials");
      else if (isLrwsis) setAccessDeniedApp("LRWSIS");
      else if (isOpenu) setAccessDeniedApp("TUA Open University");
      return;
    }

    // Your existing invalid credentials message
    if (status === 401) {
      if (typeof setLoginError === "function") {
        setLoginError("Invalid email or password.");
      } else {
        setGenericError("Invalid email or password.");
      }
      return;
    }

    if (typeof setLoginError === "function") {
      setLoginError("Something went wrong. Please try again.");
    } else {
      setGenericError("Something went wrong. Please try again.");
    }
  }
}






  return (
    <div className="min-h-screen relative flex items-center justify-center p-6 bg-gradient-to-br from-green-700 via-green-500 to-yellow-300">
      {/* background image */}
      <img
        src="/tua-header.png"
        alt=""
        className="pointer-events-none select-none absolute top-6 left-1/2 -translate-x-1/2 w-[720px] max-w-[92vw] opacity-10"
      />

      <div className="w-full max-w-md relative">
        {/* Logo + Title */}
        <div className="flex items-center justify-center gap-4 bg-gradient-to-r from-green-600 to-yellow-400 p-6 rounded-t-lg">
          <h1 className="text-white text-4xl font-extrabold drop-shadow-lg text-center">
            Trinity University of Asia
          </h1>
          <img
            src="/android-icon-144x144.png"
            alt="Trinity Logo"
            className="h-16 w-16 object-contain drop-shadow-lg"
          />
        </div>

        {/* Tabs + Form Card */}
        <div className="rounded-lg overflow-hidden shadow-md border border-black/10 bg-white/90 backdrop-blur max-h-[85vh] overflow-y-auto">
          {/* Tabs */}
          <div className="grid grid-cols-3">
            {tabs.map((tab, idx) => (
              <button
                key={tab.name}
                onClick={() => setActiveTab(idx)}
                className={`px-3 py-2 text-xs sm:text-sm font-semibold transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-black/20 ${
                  activeTab === idx
                    ? `${tab.color} shadow-inner`
                    : "bg-white text-gray-700 hover:bg-gray-50"
                }`}
              >
                {tab.name}
              </button>
            ))}
          </div>

          {/* Login form */}
          <div className="p-6 bg-white">
            <h2 className="text-2xl font-bold mb-4 text-center text-gray-900">
              {tabs[activeTab].name}
            </h2>

            <form className="space-y-4" onSubmit={handleLoginSubmit} noValidate>
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  Email
                </label>
                <input
                  type="email"
                  placeholder="you@example.com"
                  value={loginEmail}
                  onChange={(e) => setLoginEmail(e.target.value)}
                  required
                  className="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 shadow-md focus:border-emerald-600 focus:ring-emerald-600 sm:text-sm"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700">
                  Password
                </label>
                <input
                  type="password"
                  placeholder="••••••••"
                  value={loginPassword}
                  onChange={(e) => setLoginPassword(e.target.value)}
                  required
                  className="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 shadow-md focus:border-emerald-600 focus:ring-emerald-600 sm:text-sm"
                />
              </div>

              <div className="flex items-center justify-between">
                <label className="inline-flex items-center gap-2 text-sm text-gray-700">
                  <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    checked={remember}
                    onChange={(e) => setRemember(e.target.checked)}
                    className="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-600"
                  />
                  Remember me
                </label>
                <a
                  href="#"
                  className="text-sm font-semibold text-emerald-700 hover:text-emerald-600"
                >
                  Forgot password?
                </a>
              </div>

              {loginError && (
                <div className="text-red-600 text-sm">{loginError}</div>
              )}

              {accessDenied && (
                <AppAccessAlert
                  appName={accessDeniedApp ?? "this application"}
                  className="mb-3"
                />
              )}

              {genericError && (
                <div
                  role="alert"
                  className="mb-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"
                >
                  {genericError}
                </div>
              )}


            <button
              type="submit"
              disabled={!(isMicro || isLrwsis || isOpenu) || loading}
              title={isMicro || isLrwsis || isOpenu ? "" : "Sign-in available on these tabs"}
              className={`w-full py-2.5 rounded-md font-semibold shadow-md transition-colors duration-200 ${tabs[activeTab]?.color ?? ""} ${!(isMicro||isLrwsis||isOpenu)||loading ? "opacity-60 cursor-not-allowed" : ""}`}
            >
              {loading ? "Signing in..." : "Sign In"}
            </button>


              <button
                type="button"
                onClick={handleApplyNow}
                className={`w-full py-2.5 rounded-md font-semibold shadow-md transition-colors duration-200 ${
                  isMicro
                    ? "bg-blue-600 hover:bg-blue-700 text-white"
                    : "bg-gray-100 text-gray-400 cursor-not-allowed"
                }`}
                disabled={!isMicro}
                title={
                  isMicro ? "Open registration form" : "Only available for TUA Microcredentials"
                }
              >
                Apply Now
              </button>
            </form>

            {/* Social divider */}
            <div className="mt-8">
              <div className="relative">
                <div aria-hidden="true" className="absolute inset-0 flex items-center">
                  <div className="w-full border-t border-gray-200" />
                </div>
                <div className="relative flex justify-center text-sm font-medium">
                  <span className="bg-white px-3 text-gray-700">Or continue with</span>
                </div>
              </div>

              <div className="mt-6 grid grid-cols-2 gap-4">
                <a
                  href="#"
                  className="flex w-full items-center justify-center gap-3 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                >
                  {/* Google */}
                  <svg viewBox="0 0 24 24" aria-hidden="true" className="h-5 w-5">
                    <path d="M12.0003 4.75C13.7703 4.75 15.3553 5.36 16.6053 6.55L20.0303 3.125C17.9502 1.19 15.2353 0 12.0003 0C7.31028 0 3.25527 2.69 1.28027 6.61L5.27028 9.705C6.21525 6.86 8.87028 4.75 12.0003 4.75Z" fill="#EA4335"/>
                    <path d="M23.49 12.275C23.49 11.49 23.415 10.73 23.3 10H12V14.51H18.47C18.18 15.99 17.34 17.25 16.08 18.1L19.945 21.1C22.2 19.01 23.49 15.92 23.49 12.275Z" fill="#4285F4"/>
                    <path d="M5.265 14.295C5.025 13.57 4.885 12.8 4.885 12C4.885 11.2 5.02 10.43 5.265 9.705L1.275 6.61C0.46 8.23 0 10.06 0 12C0 13.94 0.46 15.77 1.28 17.39L5.265 14.295Z" fill="#FBBC05"/>
                    <path d="M12.0004 24.0001C15.2404 24.0001 17.9654 22.935 19.9454 21.095L16.0804 18.095C15.0054 18.82 13.6204 19.245 12.0004 19.245C8.8704 19.245 6.21537 17.135 5.2654 14.29L1.27539 17.385C3.25539 21.31 7.3104 24.0001 12.0004 24.0001Z" fill="#34A853"/>
                  </svg>
                  <span>Google</span>
                </a>

                <a
                  href="#"
                  className="flex w-full items-center justify-center gap-3 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                >
                  {/* Facebook */}
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" className="h-5 w-5 fill-[#1877F2]">
                    <path d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S293.32 0 266.46 0c-73.24 0-121.29 44.38-121.29 124.72v70.62H86.41V288h58.76v224h92.66V288z" />
                  </svg>
                  <span>Facebook</span>
                </a>
              </div>
            </div>
          </div>
        </div>

        <div className="absolute inset-x-0 -bottom-3 h-3 rounded-b-lg bg-black/10 blur-md" />
      </div>

      {showReg && (
        <RegistrationModal
          onClose={() => setShowReg(false)}
          onSubmit={async (payload) => {
            await postWithCsrf("/microcredentials/apply", payload);
            alert("Registration submitted! We'll email the program officer.");
            setShowReg(false);
          }}
        />
      )}
    </div>
  );
}

function RegistrationModal({
  onClose,
  onSubmit,
}: {
  onClose: () => void;
  onSubmit: (data: RegistrationData) => Promise<void> | void;
}) {
  const [form, setForm] = useState<RegistrationData>({
    lastName: "",
    firstName: "",
    middleName: "",
    mobile: "",
    email: "",
    password: "",
    confirmPassword: "",
    consent: false,
  });
  const [submitting, setSubmitting] = useState(false);

  const update = (k: keyof RegistrationData, v: any) =>
    setForm((f) => ({ ...f, [k]: v }));

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.consent) {
      alert("Please agree to the Privacy Notice & Consent.");
      return;
    }
    if (form.password !== form.confirmPassword) {
      alert("Passwords do not match.");
      return;
    }
    setSubmitting(true);
    try {
      await onSubmit(form);
    } catch (err: any) {
      console.error(err);
      alert(err?.response?.data?.message || "Submission failed. Please try again.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 grid place-items-center p-4">
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />
      
      <div className="relative z-10 w-full max-w-2xl rounded-xl bg-white shadow-xl max-h-[90vh] overflow-y-auto"> {/* ⬅️ added max-h and overflow-y */}
        <div className="flex items-center justify-between px-6 py-4 border-b">
          <h3 className="text-lg font-semibold">TUA Microcredentials — Application</h3>
          <button onClick={onClose} className="text-gray-500 hover:text-gray-700">✕</button>
        </div>

        <form onSubmit={handleSubmit} className="px-6 py-6">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div className="sm:col-span-1">
              <label className="block text-sm font-medium text-gray-700">Last name</label>
              <input
                value={form.lastName}
                onChange={(e) => update("lastName", e.target.value)}
                required
                className="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 shadow-lg focus:border-emerald-600 focus:ring-emerald-600"
              />
            </div>
            <div className="sm:col-span-1">
              <label className="block text-sm font-medium text-gray-700">First name</label>
              <input
                value={form.firstName}
                onChange={(e) => update("firstName", e.target.value)}
                required
                className="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 shadow-lg focus:border-emerald-600 focus:ring-emerald-600"
              />
            </div>
            <div className="sm:col-span-1">
              <label className="block text-sm font-medium text-gray-700">Middle name</label>
              <input
                value={form.middleName}
                onChange={(e) => update("middleName", e.target.value)}
                className="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 shadow-lg focus:border-emerald-600 focus:ring-emerald-600"
              />
            </div>

            <div className="sm:col-span-1">
              <label className="block text-sm font-medium text-gray-700">Mobile number</label>
              <input
                value={form.mobile}
                onChange={(e) => update("mobile", e.target.value)}
                inputMode="tel"
                placeholder="09XXXXXXXXX"
                required
                className="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 shadow-lg focus:border-emerald-600 focus:ring-emerald-600"
              />
            </div>

            <div className="sm:col-span-2">
              <label className="block text-sm font-medium text-gray-700">Email</label>
              <input
                type="email"
                value={form.email}
                onChange={(e) => update("email", e.target.value)}
                required
                className="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 shadow-lg focus:border-emerald-600 focus:ring-emerald-600"
              />
            </div>

            <div className="sm:col-span-1">
              <label className="block text-sm font-medium text-gray-700">Password</label>
              <input
                type="password"
                value={form.password}
                onChange={(e) => update("password", e.target.value)}
                required
                className="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 shadow-lg focus:border-emerald-600 focus:ring-emerald-600"
              />
            </div>

            <div className="sm:col-span-1">
              <label className="block text-sm font-medium text-gray-700">Confirm Password</label>
              <input
                type="password"
                value={form.confirmPassword}
                onChange={(e) => update("confirmPassword", e.target.value)}
                required
                className="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 shadow-lg focus:border-emerald-600 focus:ring-emerald-600"
              />
            </div>
          </div>

          <div className="mt-6 space-y-3">
            <div className="rounded-md bg-yellow-50 p-3 text-sm text-yellow-900 border border-yellow-200">
              <strong>Privacy Notice:</strong> We collect your name, contact details, and email to process your Microcredentials application. Your data will be used only for evaluation, enrollment, certification, and official communications.
            </div>

            <label className="flex items-start gap-3 text-sm text-gray-700">
              <input
                type="checkbox"
                checked={form.consent}
                onChange={(e) => update("consent", e.target.checked)}
                className="mt-0.5 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-600"
                required
              />
              <span>
                I have read and agree to the Privacy Notice, and I consent to the processing of my personal data for Microcredentials application and enrollment.
              </span>
            </label>
          </div>

          <div className="mt-6 flex items-center justify-end gap-3">
            <button
              type="button"
              className="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
              onClick={onClose}
              disabled={submitting}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="px-4 py-2 rounded-md bg-emerald-600 text-white font-semibold hover:bg-emerald-700 disabled:opacity-60"
              disabled={submitting}
            >
              {submitting ? "Submitting..." : "Submit Application"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}