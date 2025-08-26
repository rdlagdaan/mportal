import * as React from 'react'
import { useParams, Link, useNavigate, useOutletContext } from 'react-router-dom'
import { courseDetails } from '../data/staticCatalog'
import {
  CreditCardIcon,
  BuildingLibraryIcon,
  BanknotesIcon,
  ShieldCheckIcon,
  ArrowLeftIcon,
} from '@heroicons/react/24/outline'

type LayoutCtx = { active: 'courses'|'enrolled'|'finished'|'profile'; setActive:(k:LayoutCtx['active'])=>void }
type Method = 'card' | 'otc' | 'dragonpay'

export default function CheckoutPage() {
  const { courseId } = useParams()
  const id = Number(courseId)
  const c = courseDetails[id]
  const navigate = useNavigate()
  const ctx = useOutletContext<LayoutCtx | undefined>()
  React.useEffect(() => { ctx?.setActive('courses') }, [ctx])

  const [method, setMethod] = React.useState<Method>('card')
  const [busy, setBusy] = React.useState(false)

  if (!c) {
    return (
      <div className="rounded-2xl border border-green-100 bg-white/80 p-4">
        <div className="text-green-900">Course not found.</div>
        <Link to="/app/courses" className="mt-3 inline-flex rounded-xl bg-yellow-400 px-3 py-1.5 text-green-950">Back</Link>
      </div>
    )
  }

  const paySuccess = async () => {
    // prototype: pretend payment succeeded → go to Enrolled list
    setBusy(true)
    await new Promise(r => setTimeout(r, 900))
    navigate('/app/enrolled', { replace: true })
  }

  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      <header className="mb-4 flex items-center justify-between">
        <div>
          <h2 className="text-lg font-semibold text-green-900">Checkout</h2>
          <p className="text-sm text-green-700/80">Choose a payment method to enroll</p>
        </div>
        <Link
          to={`/app/courses/${id}`}
          className="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-1.5 text-sm font-medium text-green-900 ring-1 ring-green-200 hover:bg-green-50"
        >
          <ArrowLeftIcon className="h-5 w-5" /> Back to course
        </Link>
      </header>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Left: course summary */}
        <div className="lg:col-span-2 rounded-2xl border border-green-100 bg-white p-4">
          <div className="flex flex-col gap-4 md:flex-row">
            <img src={c.img} alt="" className="h-40 w-full rounded-xl object-cover md:h-44 md:w-72" />
            <div className="flex-1">
              <div className="text-xs font-semibold text-green-700/70">{c.program}</div>
              <h3 className="text-xl font-semibold text-green-900">{c.title}</h3>
              <p className="mt-1 text-sm text-green-800/90">{c.description}</p>

              <div className="mt-3 grid gap-3 sm:grid-cols-2">
                <Info label="Duration" value={c.duration} />
                <Info label="Schedule" value={c.schedule} />
              </div>
            </div>
          </div>

          {/* Payment method selector */}
          <div className="mt-6">
            <h4 className="text-sm font-semibold text-green-900">Payment method</h4>
            <div className="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-3">
              <MethodCard
                icon={CreditCardIcon}
                title="Credit / Debit Card"
                active={method === 'card'}
                onClick={() => setMethod('card')}
              />
              <MethodCard
                icon={BuildingLibraryIcon}
                title="Bank OTC"
                active={method === 'otc'}
                onClick={() => setMethod('otc')}
              />
              <MethodCard
                icon={BanknotesIcon}
                title="Dragonpay"
                active={method === 'dragonpay'}
                onClick={() => setMethod('dragonpay')}
              />
            </div>

            {/* Panels */}
            <div className="mt-4 rounded-2xl border border-green-100 bg-white p-4">
              {method === 'card' && <CardForm busy={busy} onPay={paySuccess} amount={c.price} />}
              {method === 'otc' && <OtcPanel busy={busy} onConfirm={paySuccess} amount={c.price} />}
              {method === 'dragonpay' && <DragonpayPanel busy={busy} onReturn={paySuccess} amount={c.price} />}
            </div>
          </div>
        </div>

        {/* Right: price box */}
        <aside className="rounded-2xl border border-green-100 bg-white p-4">
          <h4 className="text-sm font-semibold text-green-900">Order summary</h4>
          <dl className="mt-3 space-y-2 text-sm">
            <Row label="Course" value={c.title} />
            <Row label="Program" value={c.program} />
            <Row label="Subtotal" value={fmt(c.price.currency, c.price.amount)} />
            <Row label="Fees" value={fmt(c.price.currency, 0)} />
          </dl>
          <div className="mt-3 h-px bg-green-100" />
          <div className="mt-3 flex items-center justify-between font-semibold text-green-900">
            <span>Total</span>
            <span>{fmt(c.price.currency, c.price.amount)}</span>
          </div>
          <div className="mt-4 rounded-xl bg-yellow-50 p-3 text-xs text-yellow-900 ring-1 ring-yellow-200">
            <p className="font-medium">Secure checkout</p>
            <p className="mt-1 flex items-center gap-1">
              <ShieldCheckIcon className="h-4 w-4" /> Payments are simulated in this prototype.
            </p>
          </div>
        </aside>
      </div>
    </section>
  )
}

/* ---------- small pieces ---------- */

function Info({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-xl border border-green-100 bg-white p-3">
      <div className="text-xs font-medium text-green-700/80">{label}</div>
      <div className="text-sm font-semibold text-green-900">{value}</div>
    </div>
  )
}

function MethodCard({
  icon: Icon, title, active, onClick,
}: { icon: React.ComponentType<any>; title: string; active: boolean; onClick: () => void }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={[
        'flex items-center gap-3 rounded-xl border px-3 py-2 text-left transition',
        active
          ? 'border-yellow-300 bg-yellow-100'
          : 'border-green-100 bg-white hover:bg-yellow-50',
      ].join(' ')}
    >
      <Icon className="h-5 w-5 text-green-700" />
      <span className="text-sm font-medium text-green-900">{title}</span>
    </button>
  )
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex items-center justify-between">
      <dt className="text-green-700/80">{label}</dt>
      <dd className="text-green-900">{value}</dd>
    </div>
  )
}

function fmt(curr: string, amt: number) {
  const code = curr === 'PHP' ? 'PHP' : 'USD'
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: code, minimumFractionDigits: 2 }).format(amt)
}

/* ---------- Method panels ---------- */

function CardForm({ busy, onPay, amount }: { busy:boolean; onPay:()=>void; amount:{currency:string;amount:number} }) {
  const [name, setName] = React.useState('Randy Lagdaan')
  const [num, setNum] = React.useState('')
  const [exp, setExp] = React.useState('')
  const [cvc, setCvc] = React.useState('')

  const disabled = busy || !name || num.length < 12 || !/^\d{2}\/\d{2}$/.test(exp) || cvc.length < 3

  return (
    <form
      onSubmit={(e) => { e.preventDefault(); if (!disabled) onPay() }}
      className="grid grid-cols-1 gap-3 sm:grid-cols-2"
    >
      <label className="block sm:col-span-2">
        <span className="text-xs font-medium text-green-800">Name on card</span>
        <input value={name} onChange={(e)=>setName(e.target.value)} className="mt-1 w-full rounded-xl border border-green-200 px-3 py-2" />
      </label>
      <label className="block sm:col-span-2">
        <span className="text-xs font-medium text-green-800">Card number</span>
        <input value={num} onChange={(e)=>setNum(e.target.value.replace(/[^\d ]/g,''))} placeholder="4242 4242 4242 4242" className="mt-1 w-full rounded-xl border border-green-200 px-3 py-2" />
      </label>
      <label className="block">
        <span className="text-xs font-medium text-green-800">Expiry (MM/YY)</span>
        <input value={exp} onChange={(e)=>setExp(e.target.value)} placeholder="12/27" className="mt-1 w-full rounded-xl border border-green-200 px-3 py-2" />
      </label>
      <label className="block">
        <span className="text-xs font-medium text-green-800">CVC</span>
        <input value={cvc} onChange={(e)=>setCvc(e.target.value.replace(/\D/g,''))} placeholder="123" className="mt-1 w-full rounded-xl border border-green-200 px-3 py-2" />
      </label>

      <button
        type="submit"
        disabled={disabled}
        className="sm:col-span-2 mt-1 rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700 disabled:opacity-50"
      >
        {busy ? 'Processing…' : `Pay ${fmt(amount.currency, amount.amount)}`}
      </button>
    </form>
  )
}

function OtcPanel({ busy, onConfirm, amount }:{busy:boolean; onConfirm:()=>void; amount:{currency:string;amount:number}}) {
  const [bank,setBank] = React.useState('BPI')
  const ref = React.useMemo(()=> 'MC-' + Math.random().toString(36).slice(2,8).toUpperCase(), [])
  return (
    <div>
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <label className="block sm:col-span-2">
          <span className="text-xs font-medium text-green-800">Choose bank</span>
          <select value={bank} onChange={(e)=>setBank(e.target.value)} className="mt-1 w-full rounded-xl border border-green-200 px-3 py-2">
            <option>BPI</option>
            <option>BDO</option>
            <option>Metrobank</option>
            <option>Landbank</option>
          </select>
        </label>
        <label className="block">
          <span className="text-xs font-medium text-green-800">Reference code</span>
          <input readOnly value={ref} className="mt-1 w-full rounded-xl border border-green-200 bg-yellow-50 px-3 py-2" />
        </label>
      </div>

      <ol className="mt-3 list-decimal space-y-1 pl-5 text-sm text-green-800/90">
        <li>Visit any <span className="font-medium">{bank}</span> branch within 48 hours.</li>
        <li>Provide reference code <span className="font-semibold">{ref}</span>.</li>
        <li>Pay {fmt(amount.currency, amount.amount)} and keep your receipt.</li>
      </ol>

      <button
        disabled={busy}
        onClick={onConfirm}
        className="mt-4 rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700 disabled:opacity-50"
      >
        I’ve paid — continue
      </button>
    </div>
  )
}

function DragonpayPanel({ busy, onReturn, amount }:{busy:boolean; onReturn:()=>void; amount:{currency:string;amount:number}}) {
  const [launched, setLaunched] = React.useState(false)
  return (
    <div>
      {!launched ? (
        <>
          <p className="text-sm text-green-800/90">You’ll be redirected to Dragonpay to complete the payment.</p>
          <button
            disabled={busy}
            onClick={()=>setLaunched(true)}
            className="mt-3 rounded-xl bg-yellow-400 px-4 py-2 font-medium text-green-950 hover:bg-yellow-500 disabled:opacity-50"
          >
            Proceed to Dragonpay
          </button>
        </>
      ) : (
        <div className="rounded-xl border border-green-100 bg-yellow-50 p-3 text-sm text-green-900">
          <p className="font-semibold">Dragonpay sandbox (mock)</p>
          <p className="mt-1">Amount: {fmt(amount.currency, amount.amount)}</p>
          <div className="mt-3 flex gap-2">
            <button
              disabled={busy}
              onClick={onReturn}
              className="rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700 disabled:opacity-50"
            >
              Return & confirm payment
            </button>
            <button
              disabled={busy}
              onClick={()=>setLaunched(false)}
              className="rounded-xl bg-white px-4 py-2 text-green-900 ring-1 ring-green-200 hover:bg-green-50"
            >
              Cancel
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
