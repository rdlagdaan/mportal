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
  {
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
