export type Activity = { id:number; title:string; type:'Quiz'|'Assignment'|'Project'|'Exam'; due:string; status:'Done'|'Pending'|'Overdue'; score?:string }
export type Announcement = { id:number; date:string; title:string; body:string }
export type CalendarEvent = { id:number; date:string; label:string }
export type SyllabusSection = { week:string; topics:string[] }
export type CoursePortal = {
  id:number
  title:string
  program:string
  banner:string
  teacher:{ name:string; avatar?:string }
  progress:number
  currentGrade:string
  activities:Activity[]
  announcements:Announcement[]
  syllabus:SyllabusSection[]
  calendar:CalendarEvent[]
}

/** Demo portals keyed by course id (21 = React Basics, 12 = SQL for Analysts) */
export const portals: Record<number, CoursePortal> = {
 /* 21: {
    id:21,
    title:'React Basics',
    program:'Web Development',
    banner:'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=1200&auto=format&fit=crop',
    teacher:{ name:'Mae Rivera', avatar:'https://images.unsplash.com/photo-1527980965255-d3b416303d12?q=80&w=200&auto=format&fit=crop' },
    progress: 62,
    currentGrade: '89%',
    activities: [
      { id:1, title:'Quiz 1 — Components', type:'Quiz', due:'Aug 25', status:'Done', score:'18/20' },
      { id:2, title:'Assignment — Todo App', type:'Assignment', due:'Aug 28', status:'Done', score:'A-' },
      { id:3, title:'Quiz 2 — Hooks', type:'Quiz', due:'Sep 2', status:'Pending' },
      { id:4, title:'Mini Project — Dashboard', type:'Project', due:'Sep 9', status:'Pending' },
    ],
    announcements: [
      { id:1, date:'Aug 18', title:'Welcome to React Basics!', body:'Slides and starter repos are in the Files section. See the calendar for due dates.' },
      { id:2, date:'Aug 22', title:'Office hours', body:'I’ll be online Tue 7–8 PM on Teams for Q&A.' },
    ],
    syllabus: [
      { week:'Week 1 — Components & JSX', topics:['JSX rules','Functional components','Props & children'] },
      { week:'Week 2 — State & Effects', topics:['useState/useEffect','Lifting state','Data fetching'] },
      { week:'Week 3 — Routing & Forms', topics:['react-router','Controlled inputs','Validation basics'] },
      { week:'Week 4 — Project', topics:['State management choices','App structure','Deployment tips'] },
    ],
    calendar: [
      { id:1, date:'Aug 25 (Sun)', label:'Quiz 1 due' },
      { id:2, date:'Aug 28 (Wed)', label:'Assignment: Todo App due' },
      { id:3, date:'Sep 02 (Mon)', label:'Quiz 2 due' },
      { id:4, date:'Sep 09 (Mon)', label:'Mini Project due' },
    ],
  },

  12: {
    id:12,
    title:'SQL for Analysts',
    program:'Data Analytics',
    banner:'https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=1200&auto=format&fit=crop',
    teacher:{ name:'J. Dela Cruz' },
    progress: 35,
    currentGrade: '—',
    activities: [
      { id:1, title:'Hands-on 1 — SELECTs', type:'Assignment', due:'Aug 22', status:'Done', score:'15/15' },
      { id:2, title:'Quiz — JOINs', type:'Quiz', due:'Aug 29', status:'Pending' },
    ],
    announcements: [
      { id:1, date:'Aug 19', title:'Repo & datasets', body:'Download the sample database from the course files.' },
    ],
    syllabus: [
      { week:'Week 1 — Basics', topics:['SELECT','WHERE','ORDER BY'] },
      { week:'Week 2 — Joins', topics:['INNER/LEFT/RIGHT','Multi-join patterns'] },
      { week:'Week 3 — Aggregations', topics:['GROUP BY','HAVING','Windows'] },
      { week:'Week 4 — CTE & Cleaning', topics:['CTE patterns','Common data fixes'] },
    ],
    calendar: [
      { id:1, date:'Aug 29 (Thu)', label:'Join Quiz due' },
      { id:2, date:'Sep 05 (Thu)', label:'Aggregation exercise due' },
    ],
  },
}*/

//{
  "43": {
    "id": 43,
    "title": "Healthcare Hospitality & Tourism Concierge",
    "program": "Healthcare Hospitality & Tourism Concierge",
    "banner": "https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=1200&auto=format&fit=crop",
    "teacher": { "name": "A. Sabillano", "avatar": "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?q=80&w=200&auto=format&fit=crop" },
    "progress": 0,
    "currentGrade": "—",
    "activities": [
      { "id": 1, "title": "Checklist — Concierge Desk Setup", "type": "Assignment", "due": "Sep 03", "status": "Pending" },
      { "id": 2, "title": "Quiz — Service Recovery", "type": "Quiz", "due": "Sep 08", "status": "Pending" },
      { "id": 3, "title": "Role‑play — Care Navigation", "type": "Project", "due": "Sep 15", "status": "Pending" }
    ],
    "announcements": [
      { "id": 1, "date": "Sep 01", "title": "Welcome!", "body": "Bring your case templates; we’ll map a patient journey in class." }
    ],
    "syllabus": [
      { "week": "Week 1 — Concierge Role & Workflow", "topics": ["Scope & ethics", "Desk procedures", "Care navigation"] },
      { "week": "Week 2 — Service Recovery", "topics": ["Complaint handling", "Escalation ladders", "KPIs"] },
      { "week": "Week 3 — Quality & Handoffs", "topics": ["Handover notes", "Shift KPIs", "Final role‑play"] }
    ],
    "calendar": [
      { "id": 1, "date": "Sep 03 (Wed)", "label": "Desk Setup Checklist due" },
      { "id": 2, "date": "Sep 08 (Mon)", "label": "Service Recovery Quiz" },
      { "id": 3, "date": "Sep 15 (Mon)", "label": "Care Navigation Role‑play" }
    ]
  },

  "44": {
    "id": 44,
    "title": "Cross‑Cultural Healthcare Hospitality & Tourism Communication",
    "program": "Healthcare Hospitality & Tourism Concierge",
    "banner": "https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=1200&auto=format&fit=crop",
    "teacher": { "name": "A. Sabillano", "avatar": "https://images.unsplash.com/photo-1544005313-94ddf0286df2?q=80&w=200&auto=format&fit=crop" },
    "progress": 0,
    "currentGrade": "—",
    "activities": [
      { "id": 1, "title": "Reflection — Cultural Lens", "type": "Assignment", "due": "Sep 05", "status": "Pending" },
      { "id": 2, "title": "Quiz — Empathic Phrasing", "type": "Quiz", "due": "Sep 10", "status": "Pending" }
    ],
    "announcements": [
      { "id": 1, "date": "Sep 03", "title": "Language pack", "body": "We’ll share common phrases for sensitive situations." }
    ],
    "syllabus": [
      { "week": "Week 1 — Cultural Frameworks", "topics": ["Values & norms", "Bias checks"] },
      { "week": "Week 2 — Language & Tone", "topics": ["Empathy scripts", "Non‑verbals"] },
      { "week": "Week 3 — Simulations", "topics": ["Difficult conversations", "Debrief & feedback"] }
    ],
    "calendar": [
      { "id": 1, "date": "Sep 05 (Fri)", "label": "Reflection due" },
      { "id": 2, "date": "Sep 10 (Wed)", "label": "Quiz — Empathic Phrasing" }
    ]
  },

  "45": {
    "id": 45,
    "title": "Healthcare Travel & Wellness Coordination",
    "program": "Healthcare Hospitality & Tourism Concierge",
    "banner": "https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=1200&auto=format&fit=crop",
    "teacher": { "name": "A. Sabillano", "avatar": "https://images.unsplash.com/photo-1531123897727-8f129e1688ce?q=80&w=200&auto=format&fit=crop" },
    "progress": 0,
    "currentGrade": "—",
    "activities": [
      { "id": 1, "title": "Planner — Patient Itinerary", "type": "Project", "due": "Sep 12", "status": "Pending" },
      { "id": 2, "title": "Checklist — Vendor SLA", "type": "Assignment", "due": "Sep 19", "status": "Pending" }
    ],
    "announcements": [
      { "id": 1, "date": "Sep 05", "title": "Vendors list", "body": "Sample SLAs and contacts posted in Files." }
    ],
    "syllabus": [
      { "week": "Week 1 — Journey Mapping", "topics": ["Touchpoints", "Risks & buffers"] },
      { "week": "Week 2 — Wellness Add‑ons", "topics": ["Packages", "Contraindications"] },
      { "week": "Week 3 — Vendor SLAs", "topics": ["Metrics", "Quality audits"] }
    ],
    "calendar": [
      { "id": 1, "date": "Sep 12 (Fri)", "label": "Itinerary Planner due" },
      { "id": 2, "date": "Sep 19 (Fri)", "label": "Vendor SLA Checklist due" }
    ]
  },

  "51": {
    "id": 51,
    "title": "Workplace Health & Safety (Community Pharmacy)",
    "program": "Community Pharmacy",
    "banner": "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=1200&auto=format&fit=crop",
    "teacher": { "name": "R. Tambis & L. Sim" },
    "progress": 0,
    "currentGrade": "—",
    "activities": [
      { "id": 1, "title": "Safety Audit Walkthrough", "type": "Assignment", "due": "Sep 02", "status": "Pending" },
      { "id": 2, "title": "Quiz — Hazard Controls", "type": "Quiz", "due": "Sep 09", "status": "Pending" }
    ],
    "announcements": [
      { "id": 1, "date": "Aug 26", "title": "PPE reminder", "body": "Wear closed shoes and bring your PPE checklist." }
    ],
    "syllabus": [
      { "week": "Week 1 — Standards & PPE", "topics": ["OSHA/DOH", "PPE selection"] },
      { "week": "Week 2 — Hazard Controls", "topics": ["Spill kits", "Sharps"] },
      { "week": "Week 3 — Incident Response", "topics": ["Logs", "Root cause"] }
    ],
    "calendar": [
      { "id": 1, "date": "Sep 02 (Tue)", "label": "Safety Audit due" },
      { "id": 2, "date": "Sep 09 (Tue)", "label": "Hazard Controls Quiz" }
    ]
  },

  "52": {
    "id": 52,
    "title": "Community Pharmacy Workflow",
    "program": "Community Pharmacy",
    "banner": "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=1200&auto=format&fit=crop",
    "teacher": { "name": "R. Tambis & L. Sim" },
    "progress": 0,
    "currentGrade": "—",
    "activities": [
      { "id": 1, "title": "Flowchart — Dispensing", "type": "Assignment", "due": "Sep 04", "status": "Pending" },
      { "id": 2, "title": "Quiz — Counseling Steps", "type": "Quiz", "due": "Sep 11", "status": "Pending" }
    ],
    "announcements": [
      { "id": 1, "date": "Aug 28", "title": "Templates posted", "body": "Download the intake and counseling templates." }
    ],
    "syllabus": [
      { "week": "Week 1 — Intake & Triage", "topics": ["Script validation", "Red flags"] },
      { "week": "Week 2 — Dispensing Flow", "topics": ["Labeling", "DDI checks"] },
      { "week": "Week 3 — Counseling", "topics": ["Teach‑back", "Follow‑ups"] }
    ],
    "calendar": [
      { "id": 1, "date": "Sep 04 (Thu)", "label": "Dispensing Flowchart due" },
      { "id": 2, "date": "Sep 11 (Thu)", "label": "Counseling Quiz" }
    ]
  },

  "53": {
    "id": 53,
    "title": "Community Pharmacy Supplies & Inventory",
    "program": "Community Pharmacy",
    "banner": "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=1200&auto=format&fit=crop",
    "teacher": { "name": "R. Tambis & L. Sim" },
    "progress": 0,
    "currentGrade": "—",
    "activities": [
      { "id": 1, "title": "Stock Count — FEFO", "type": "Assignment", "due": "Sep 06", "status": "Pending" },
      { "id": 2, "title": "Quiz — Reorder Points", "type": "Quiz", "due": "Sep 13", "status": "Pending" }
    ],
    "announcements": [
      { "id": 1, "date": "Aug 30", "title": "Cold chain log", "body": "Print the temperature log sheets for the exercise." }
    ],
    "syllabus": [
      { "week": "Week 1 — Storage & FEFO", "topics": ["Temp ranges", "Cold chain"] },
      { "week": "Week 2 — Forecasting", "topics": ["ROP/EOQ", "Seasonality"] },
      { "week": "Week 3 — Audits", "topics": ["Cycle counts", "Variance analysis"] }
    ],
    "calendar": [
      { "id": 1, "date": "Sep 06 (Sat)", "label": "FEFO Stock Count due" },
      { "id": 2, "date": "Sep 13 (Sat)", "label": "Reorder Points Quiz" }
    ]
  }
}
