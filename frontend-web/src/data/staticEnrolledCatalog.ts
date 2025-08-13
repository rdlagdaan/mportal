export type EnrolledCourseListItem = {
  id: number
  title: string
  img: string
  schedule: string
  status: 'Enrolled' | 'In Progress'
}

export type EnrolledProgram = {
  id: number
  name: string
  img: string
  blurb: string
  courses: EnrolledCourseListItem[]
}

/**
 * Static demo of the learner's current enrollments,
 * grouped by program to match the CoursesPage layout.
 * Course IDs align with `courseDetails` so /courses/:id works.
 */
export const enrolledPrograms: EnrolledProgram[] = [
 /* {
    id: 2,
    name: 'Web Development',
    img: 'https://images.unsplash.com/photo-1529400971008-f566de0e6dfc?q=80&w=800&auto=format&fit=crop',
    blurb: 'Frontend to backend foundations.',
    courses: [
      {
        id: 21, // React Basics
        title: 'React Basics',
        img: 'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=600&auto=format&fit=crop',
        schedule: 'Sat 9:00–12:00 (Aug 24 – Sep 21)',
        status: 'Enrolled',
      },
    ],
  },
  {
    id: 1,
    name: 'Data Analytics',
    img: 'https://images.unsplash.com/photo-1551281044-8e8b89f0ee3b?q=80&w=800&auto=format&fit=crop',
    blurb: 'Hands-on analytics using Python and SQL.',
    courses: [
      {
        id: 12, // SQL for Analysts
        title: 'SQL for Analysts',
        img: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=600&auto=format&fit=crop',
        schedule: 'Wed 18:00–21:00 (Aug 20 – Sep 17)',
        status: 'Enrolled',
      },
    ],
  },
]


[*/
  {
    "id": 4,
    "name": "Healthcare Hospitality & Tourism Concierge",
    "img": "https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=800&auto=format&fit=crop",
    "blurb": "Concierge, cross‑cultural comms, and wellness coordination for medical travelers.",
    "courses": [
      {
        "id": 43,
        "title": "Healthcare Hospitality & Tourism Concierge",
        "img": "https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=600&auto=format&fit=crop",
        "schedule": "Mon 18:00–21:00 (Sep 1 – Sep 15)",
        "status": "Enrolled"
      },
      {
        "id": 44,
        "title": "Cross‑Cultural Healthcare Hospitality & Tourism Communication",
        "img": "https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=600&auto=format&fit=crop",
        "schedule": "Wed 18:00–21:00 (Sep 3 – Sep 17)",
        "status": "Enrolled"
      },
      {
        "id": 45,
        "title": "Healthcare Travel & Wellness Coordination",
        "img": "https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=600&auto=format&fit=crop",
        "schedule": "Fri 18:00–21:00 (Sep 5 – Sep 19)",
        "status": "Enrolled"
      }
    ]
  },
  {
    "id": 5,
    "name": "Community Pharmacy",
    "img": "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=800&auto=format&fit=crop",
    "blurb": "Operations, workflow, and inventory foundations for pharmacy practice.",
    "courses": [
      {
        "id": 51,
        "title": "Workplace Health & Safety (Community Pharmacy)",
        "img": "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=600&auto=format&fit=crop",
        "schedule": "Tue 18:00–21:00 (Aug 26 – Sep 9)",
        "status": "Enrolled"
      },
      {
        "id": 52,
        "title": "Community Pharmacy Workflow",
        "img": "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=600&auto=format&fit=crop",
        "schedule": "Thu 18:00–21:00 (Aug 28 – Sep 11)",
        "status": "Enrolled"
      },
      {
        "id": 53,
        "title": "Community Pharmacy Supplies & Inventory",
        "img": "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=600&auto=format&fit=crop",
        "schedule": "Sat 9:00–12:00 (Aug 30 – Sep 13)",
        "status": "Enrolled"
      }
    ]
  }
]
