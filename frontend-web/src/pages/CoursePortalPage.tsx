import * as React from 'react'
import { useParams, Link, useNavigate, useOutletContext } from 'react-router-dom'
import {
  AcademicCapIcon,
  ClipboardDocumentCheckIcon,
  ClipboardDocumentListIcon,
  MegaphoneIcon,
  CalendarDaysIcon,
  UserCircleIcon,
  ArrowLeftIcon,
  CheckCircleIcon,
  ClockIcon,
  TrophyIcon,
} from '@heroicons/react/24/outline'
import { portals } from '../data/staticCoursePortal'

type LayoutCtx = { active: 'courses' | 'enrolled' | 'finished' | 'profile'; setActive: (k: LayoutCtx['active']) => void }
type TabKey = 'overview' | 'activities' | 'syllabus' | 'announcements' | 'calendar'

const TABS: { key: TabKey; label: string; icon: React.ComponentType<any> }[] = [
  { key: 'overview', label: 'Overview', icon: AcademicCapIcon },
  { key: 'activities', label: 'Activities', icon: ClipboardDocumentListIcon },
  { key: 'syllabus', label: 'Syllabus', icon: ClipboardDocumentCheckIcon },
  { key: 'announcements', label: 'Announcements', icon: MegaphoneIcon },
  { key: 'calendar', label: 'Calendar', icon: CalendarDaysIcon },
]

export default function CoursePortalPage() {
  const { courseId } = useParams()
  const id = Number(courseId)
  const portal = portals[id]
  const navigate = useNavigate()

  // keep sidebar highlight on "Enrolled"
  const ctx = useOutletContext<LayoutCtx | undefined>()
  React.useEffect(() => { ctx?.setActive('enrolled') }, [ctx])

  const [tab, setTab] = React.useState<TabKey>('overview')

  if (!portal) {
    return (
      <div className="rounded-2xl border border-green-100 bg-white/80 p-4">
        <div className="text-green-900">Course portal not found.</div>
        <Link to="/enrolled" className="mt-3 inline-flex rounded-xl bg-yellow-400 px-3 py-1.5 text-green-950">Back to Enrolled</Link>
      </div>
    )
  }

  const finished = portal.activities.filter(a => a.status === 'Done')
  const pending = portal.activities.filter(a => a.status !== 'Done')

  return (
    <section className="rounded-2xl border border-green-100 bg-white/80 p-4 shadow-sm backdrop-blur">
      {/* Header */}
      <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div className="flex items-start gap-4">
          <img
            src={portal.banner}
            alt=""
            className="h-20 w-32 rounded-xl object-cover ring-1 ring-green-100"
          />
          <div>
            <div className="text-xs font-semibold text-green-700/80">{portal.program}</div>
            <h1 className="text-2xl font-semibold text-green-900">{portal.title}</h1>
            <div className="mt-2 flex items-center gap-2 text-sm text-green-800/90">
              {portal.teacher.avatar
                ? <img src={portal.teacher.avatar} className="h-6 w-6 rounded-full object-cover" alt="" />
                : <UserCircleIcon className="h-5 w-5" />
              }
              <span>Faculty: <span className="font-medium text-green-900">{portal.teacher.name}</span></span>
            </div>
          </div>
        </div>

        <Link
          to="/enrolled"
          className="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-1.5 text-sm font-medium text-green-900 ring-1 ring-green-200 hover:bg-green-50 self-start"
        >
          <ArrowLeftIcon className="h-5 w-5" /> Back to Enrolled
        </Link>
      </div>

      {/* Progress */}
      <div className="mt-4 rounded-2xl border border-green-100 bg-white p-4">
        <div className="flex items-center justify-between">
          <div className="text-sm font-medium text-green-900">Course Progress</div>
          <div className="text-sm text-green-800/90">{portal.progress}%</div>
        </div>
        <div className="mt-2 h-2 rounded-full bg-green-100">
          <div className="h-2 rounded-full bg-gradient-to-r from-green-600 to-yellow-400" style={{ width: `${portal.progress}%` }} />
        </div>
        <div className="mt-3 grid gap-3 sm:grid-cols-3">
          <Stat icon={CheckCircleIcon} label="Finished activities" value={finished.length.toString()} />
          <Stat icon={ClockIcon} label="Remaining activities" value={pending.length.toString()} />
          <Stat icon={TrophyIcon} label="Current grade" value={portal.currentGrade} />
        </div>
      </div>

      {/* Tabs */}
      <div className="mt-6">
        <div className="flex flex-wrap gap-2">
          {TABS.map(t => (
            <button
              key={t.key}
              onClick={() => setTab(t.key)}
              className={[
                'inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-sm ring-1 transition',
                tab === t.key
                  ? 'bg-yellow-200/70 text-green-900 ring-yellow-300'
                  : 'bg-white text-green-900 ring-green-200 hover:bg-yellow-100',
              ].join(' ')}
            >
              <t.icon className="h-4 w-4" />
              {t.label}
            </button>
          ))}
        </div>

        <div className="mt-4">
          {tab === 'overview' && <Overview pending={pending} announcements={portal.announcements} />}
          {tab === 'activities' && <Activities finished={finished} pending={pending} />}
          {tab === 'syllabus' && <Syllabus sections={portal.syllabus} />}
          {tab === 'announcements' && <Announcements items={portal.announcements} />}
          {tab === 'calendar' && <Calendar items={portal.calendar} />}
        </div>
      </div>
    </section>
  )
}

/* ---------- little building blocks ---------- */

function Stat({ icon: Icon, label, value }:{icon:React.ComponentType<any>; label:string; value:string}) {
  return (
    <div className="flex items-center gap-3 rounded-xl border border-green-100 bg-white p-3">
      <Icon className="h-5 w-5 text-green-700/80" />
      <div>
        <div className="text-xs text-green-700/80">{label}</div>
        <div className="text-sm font-semibold text-green-900">{value}</div>
      </div>
    </div>
  )
}

function Overview({
  pending, announcements,
}:{pending:any[]; announcements:any[]}) {
  return (
    <div className="grid gap-4 lg:grid-cols-3">
      <div className="rounded-2xl border border-green-100 bg-white p-4 lg:col-span-2">
        <h3 className="text-sm font-semibold text-green-900">Next up</h3>
        {pending.length === 0 ? (
          <p className="mt-2 text-sm text-green-800/90">You’re all caught up. 🎉</p>
        ) : (
          <ul className="mt-2 space-y-2">
            {pending.slice(0, 3).map(a => (
              <li key={a.id} className="rounded-xl border border-green-100 p-3">
                <div className="text-sm font-medium text-green-900">{a.title}</div>
                <div className="text-xs text-green-700/80">{a.type} • Due {a.due}</div>
              </li>
            ))}
          </ul>
        )}
      </div>
      <div className="rounded-2xl border border-green-100 bg-white p-4">
        <h3 className="text-sm font-semibold text-green-900">Latest announcement</h3>
        {announcements[0] ? (
          <div className="mt-2">
            <div className="text-xs text-green-700/80">{announcements[0].date}</div>
            <div className="font-medium text-green-900">{announcements[0].title}</div>
            <p className="mt-1 text-sm text-green-800/90">{announcements[0].body}</p>
          </div>
        ) : <p className="mt-2 text-sm text-green-800/90">No announcements.</p>}
      </div>
    </div>
  )
}

function Activities({
  finished, pending,
}:{finished:any[]; pending:any[]}) {
  return (
    <div className="grid gap-4 lg:grid-cols-2">
      <div className="rounded-2xl border border-green-100 bg-white p-4">
        <h3 className="text-sm font-semibold text-green-900">Remaining</h3>
        <ul className="mt-2 space-y-2">
          {pending.length ? pending.map(a => (
            <li key={a.id} className="rounded-xl border border-green-100 p-3">
              <div className="text-sm font-medium text-green-900">{a.title}</div>
              <div className="text-xs text-green-700/80">{a.type} • Due {a.due}</div>
            </li>
          )) : <p className="text-sm text-green-800/90">Nothing pending.</p>}
        </ul>
      </div>
      <div className="rounded-2xl border border-green-100 bg-white p-4">
        <h3 className="text-sm font-semibold text-green-900">Finished & grades</h3>
        <ul className="mt-2 space-y-2">
          {finished.length ? finished.map(a => (
            <li key={a.id} className="rounded-2xl border border-green-100 p-3">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-sm font-medium text-green-900">{a.title}</div>
                  <div className="text-xs text-green-700/80">{a.type}</div>
                </div>
                <span className="rounded-full bg-yellow-200 px-2 py-0.5 text-xs font-semibold text-green-900">
                  {a.score ?? '—'}
                </span>
              </div>
            </li>
          )) : <p className="text-sm text-green-800/90">No graded items yet.</p>}
        </ul>
      </div>
    </div>
  )
}

function Syllabus({ sections }:{sections:{week:string; topics:string[]}[]}) {
  return (
    <div className="rounded-2xl border border-green-100 bg-white p-4">
      <h3 className="text-sm font-semibold text-green-900">Course Syllabus</h3>
      <div className="mt-2 grid gap-3 md:grid-cols-2">
        {sections.map((s, i) => (
          <div key={i} className="rounded-xl border border-green-100 p-3">
            <div className="text-sm font-semibold text-green-900">{s.week}</div>
            <ul className="mt-1 list-disc pl-5 text-sm text-green-800/90">
              {s.topics.map((t, j) => <li key={j}>{t}</li>)}
            </ul>
          </div>
        ))}
      </div>
    </div>
  )
}

function Announcements({ items }:{items:{id:number;date:string;title:string;body:string}[]}) {
  return (
    <div className="space-y-3">
      {items.map(a => (
        <div key={a.id} className="rounded-2xl border border-green-100 bg-white p-4">
          <div className="text-xs text-green-700/80">{a.date}</div>
          <div className="font-medium text-green-900">{a.title}</div>
          <p className="mt-1 text-sm text-green-800/90">{a.body}</p>
        </div>
      ))}
      {!items.length && <p className="text-sm text-green-800/90">No announcements yet.</p>}
    </div>
  )
}

function Calendar({ items }:{items:{id:number;date:string;label:string}[]}) {
  return (
    <div className="rounded-2xl border border-green-100 bg-white p-4">
      <h3 className="text-sm font-semibold text-green-900">Calendar</h3>
      <ul className="mt-2 space-y-2">
        {items.map(e => (
          <li key={e.id} className="flex items-center justify-between rounded-xl border border-green-100 p-3">
            <span className="text-sm text-green-900">{e.label}</span>
            <span className="text-xs text-green-700/80">{e.date}</span>
          </li>
        ))}
        {!items.length && <p className="text-sm text-green-800/90">No events scheduled.</p>}
      </ul>
    </div>
  )
}
