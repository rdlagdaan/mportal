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
  21: {
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
}
