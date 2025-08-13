export type CourseListItem = { id:number; title:string; img:string }
export type Program = { id:number; name:string; img:string; blurb:string; courses:CourseListItem[] }

export const programs: Program[] = [
  {
    id: 1,
    name: 'Data Analytics',
    img: 'https://images.unsplash.com/photo-1551281044-8e8b89f0ee3b?q=80&w=800&auto=format&fit=crop',
    blurb: 'Hands-on analytics using Python and SQL.',
    courses: [
      { id: 11, title: 'Intro to Data Viz', img: 'https://images.unsplash.com/photo-1551281044-8e8b89f0ee3b?q=80&w=600&auto=format&fit=crop' },
      { id: 12, title: 'SQL for Analysts', img: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=600&auto=format&fit=crop' },
    ],
  },
  {
    id: 2,
    name: 'Web Development',
    img: 'https://images.unsplash.com/photo-1529400971008-f566de0e6dfc?q=80&w=800&auto=format&fit=crop',
    blurb: 'Frontend to backend foundations.',
    courses: [
      { id: 21, title: 'React Basics', img: 'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=600&auto=format&fit=crop' },
      { id: 22, title: 'Laravel Essentials', img: 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?q=80&w=600&auto=format&fit=crop' },
    ],
  },
  {
    id: 3,
    name: 'Business Management',
    img: 'https://images.unsplash.com/photo-1523958203904-cdcb402031fd?q=80&w=800&auto=format&fit=crop',
    blurb: 'Leadership, finance and marketing foundations.',
    courses: [
      { id: 31, title: 'Entrepreneurship Fundamentals', img: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?q=80&w=600&auto=format&fit=crop' },
      { id: 32, title: 'Marketing Essentials', img: 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?q=80&w=600&auto=format&fit=crop' },
    ],
  },
  {
    id: 4,
    name: 'Hospitality Management',
    img: 'https://images.unsplash.com/photo-1528909514045-2fa4ac7a08ba?q=80&w=800&auto=format&fit=crop',
    blurb: 'Hotel operations, service excellence and F&B.',
    courses: [
      { id: 41, title: 'Hotel Operations 101', img: 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=600&auto=format&fit=crop' },
      { id: 42, title: 'Food & Beverage Service Basics', img: 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?q=80&w=600&auto=format&fit=crop' },
    ],
  },
]

export type CourseDetail = {
  id:number; title:string; program:string; img:string;
  description:string; sampleOutline:string[]; duration:string; schedule:string; price:{currency:string; amount:number};
  teacher:{ name:string; title?:string; avatar?:string }
}

export const courseDetails: Record<number, CourseDetail> = {
  /*11: {
    id:11,
    title:'Intro to Data Viz',
    program:'Data Analytics',
    img:'https://images.unsplash.com/photo-1551281044-8e8b89f0ee3b?q=80&w=1200&auto=format&fit=crop',
    description:'Learn the principles of data visualization and build charts with modern libraries.',
    sampleOutline:['Why visualize?','Chart types & when to use','Color & accessibility','Hands-on with charts'],
    duration:'4 weeks (12 hours total)',
    schedule:'Sat 9:00–12:00, Aug 24 – Sep 21',
    price:{currency:'PHP', amount:3500},
    teacher:{name:'Dr. A. Santos', title:'Data Scientist', avatar:'https://images.unsplash.com/photo-1607746882042-944635dfe10e?q=80&w=200&auto=format&fit=crop'}
  },
  12: {
    id:12,
    title:'SQL for Analysts',
    program:'Data Analytics',
    img:'https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=1200&auto=format&fit=crop',
    description:'Practical SQL for retrieving, aggregating, and cleaning data.',
    sampleOutline:['SELECT & WHERE','JOINs deep dive','Aggregations & windows','Common table expressions'],
    duration:'5 weeks (15 hours total)',
    schedule:'Wed 18:00–21:00, Aug 20 – Sep 17',
    price:{currency:'PHP', amount:4200},
    teacher:{name:'J. Dela Cruz', title:'Analytics Lead', avatar:'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?q=80&w=200&auto=format&fit=crop'}
  },
  21: {
    id:21,
    title:'React Basics',
    program:'Web Development',
    img:'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=1200&auto=format&fit=crop',
    description:'Build interactive UIs with components, hooks, and client-side routing.',
    sampleOutline:['JSX & components','Props & state','Hooks (useState/useEffect)','Routing & forms'],
    duration:'4 weeks (16 hours total)',
    schedule:'Tue 19:00–23:00, Aug 19 – Sep 9',
    price:{currency:'PHP', amount:4800},
    teacher:{name:'Mae Rivera', title:'Frontend Engineer', avatar:'https://images.unsplash.com/photo-1527980965255-d3b416303d12?q=80&w=200&auto=format&fit=crop'}
  },
  22: {
    id:22,
    title:'Laravel Essentials',
    program:'Web Development',
    img:'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?q=80&w=1200&auto=format&fit=crop',
    description:'Learn routing, controllers, Eloquent, and Blade to build robust backends.',
    sampleOutline:['Routing & controllers','Eloquent ORM','Auth basics','APIs & validation'],
    duration:'4 weeks (16 hours total)',
    schedule:'Thu 19:00–23:00, Aug 21 – Sep 11',
    price:{currency:'PHP', amount:4800},
    teacher:{name:'Karl Uy', title:'Backend Engineer', avatar:'https://images.unsplash.com/photo-1556157382-97eda2d62296?q=80&w=200&auto=format&fit=crop'}
  },
  31: {
    id:31,
    title:'Entrepreneurship Fundamentals',
    program:'Business Management',
    img:'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?q=80&w=1200&auto=format&fit=crop',
    description:'From idea to execution: validate, plan, and pitch a business concept.',
    sampleOutline:['Opportunity mapping','Lean canvas','Go-to-market','Pitch deck basics'],
    duration:'3 weeks (9 hours total)',
    schedule:'Sat 9:00–12:00, Sep 7 – Sep 21',
    price:{currency:'PHP', amount:3000},
    teacher:{name:'Lia Mendoza', title:'Startup Mentor', avatar:'https://images.unsplash.com/photo-1544005313-94ddf0286df2?q=80&w=200&auto=format&fit=crop'}
  },
  32: {
    id:32,
    title:'Marketing Essentials',
    program:'Business Management',
    img:'https://images.unsplash.com/photo-1519389950473-47ba0277781c?q=80&w=1200&auto=format&fit=crop',
    description:'Core marketing concepts: segmentation, positioning, and campaigns.',
    sampleOutline:['STP framework','Brand & messaging','Channels & content','Analytics basics'],
    duration:'4 weeks (12 hours total)',
    schedule:'Wed 18:00–21:00, Sep 4 – Sep 25',
    price:{currency:'PHP', amount:3800},
    teacher:{name:'Ramon Cruz', title:'Marketing Strategist', avatar:'https://images.unsplash.com/photo-1547425260-76bcadfb4f2c?q=80&w=200&auto=format&fit=crop'}
  },
  41: {
    id:41,
    title:'Hotel Operations 101',
    program:'Hospitality Management',
    img:'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=1200&auto=format&fit=crop',
    description:'Front office, housekeeping, and guest relations fundamentals.',
    sampleOutline:['Front office flow','Housekeeping standards','Guest experience','KPIs & service recovery'],
    duration:'3 weeks (9 hours total)',
    schedule:'Mon 18:00–21:00, Sep 2 – Sep 16',
    price:{currency:'PHP', amount:3600},
    teacher:{name:'Ana Gomez', title:'Hotel Supervisor', avatar:'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?q=80&w=200&auto=format&fit=crop'}
  },
  42: {
    id:42,
    title:'Food & Beverage Service Basics',
    program:'Hospitality Management',
    img:'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?q=80&w=1200&auto=format&fit=crop',
    description:'Service SOPs, menu knowledge, and F&B hygiene practices.',
    sampleOutline:['Service sequence','Menu & pairing','Hygiene & safety','Handling feedback'],
    duration:'3 weeks (9 hours total)',
    schedule:'Fri 18:00–21:00, Sep 6 – Sep 20',
    price:{currency:'PHP', amount:3400},
    teacher:{name:'Paolo Reyes', title:'F&B Trainer', avatar:'https://images.unsplash.com/photo-1544006659-f0b21884ce1d?q=80&w=200&auto=format&fit=crop'}
  },
}
*/

  43: {
    id: 43,
    title: 'Healthcare Hospitality & Tourism Concierge',
    program: 'Healthcare Hospitality & Tourism Concierge',
    img: 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=1200&auto=format&fit=crop',
    description: 'Front office, care navigation, and guest-relations basics for healthcare travelers.',
    sampleOutline: ['Role of concierge', 'Care-navigation workflow', 'Service recovery', 'KPIs & handoffs'],
    duration: '3 weeks (18 hours total)',
    schedule: 'Mon 18:00–21:00, Sep 1 – Sep 15',
    price: { currency: 'PHP', amount: 3600 },
    teacher: { name: 'A. Sabillano', title: 'Concierge Program Lead', avatar: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?q=80&w=200&auto=format&fit=crop' }
  },
  44: {
    id: 44,
    title: 'Cross-Cultural Healthcare Hospitality & Tourism Communication',
    program: 'Healthcare Hospitality & Tourism Concierge',
    img: 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=1200&auto=format&fit=crop',
    description: 'Communicate with empathy across cultures in medical travel and hospitality settings.',
    sampleOutline: ['Cultural frameworks', 'Language & tone', 'De-escalation', 'Case role-plays'],
    duration: '3 weeks (18 hours total)',
    schedule: 'Wed 18:00–21:00, Sep 3 – Sep 17',
    price: { currency: 'PHP', amount: 3600 },
    teacher: { name: 'A. Sabillano', title: 'Hospitality Communications Trainer', avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?q=80&w=200&auto=format&fit=crop' }
  },
  45: {
    id: 45,
    title: 'Healthcare Travel & Wellness Coordination',
    program: 'Healthcare Hospitality & Tourism Concierge',
    img: 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=1200&auto=format&fit=crop',
    description: 'Plan end-to-end patient journeys: itineraries, wellness add-ons, and vendor coordination.',
    sampleOutline: ['Journey mapping', 'Wellness packages', 'Vendor SLAs', 'Quality assurance'],
    duration: '3 weeks (18 hours total)',
    schedule: 'Fri 18:00–21:00, Sep 5 – Sep 19',
    price: { currency: 'PHP', amount: 3800 },
    teacher: { name: 'A. Sabillano', title: 'Wellness Coordination Specialist', avatar: 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?q=80&w=200&auto=format&fit=crop' }
  },
  51: {
    id: 51,
    title: 'Workplace Health & Safety (Community Pharmacy)',
    program: 'Community Pharmacy',
    img: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=1200&auto=format&fit=crop',
    description: 'Safety standards, incident response, and regulatory basics for pharmacy operations.',
    sampleOutline: ['OSHA/DOH basics', 'Hazard controls', 'Incident reporting', 'Emergency drills'],
    duration: '3 weeks (18 hours total)',
    schedule: 'Tue 18:00–21:00, Aug 26 – Sep 9',
    price: { currency: 'PHP', amount: 3400 },
    teacher: { name: 'R. Tambis & L. Sim', title: 'Pharmacy Operations Trainers', avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?q=80&w=200&auto=format&fit=crop' }
  },
  52: {
    id: 52,
    title: 'Community Pharmacy Workflow',
    program: 'Community Pharmacy',
    img: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=1200&auto=format&fit=crop',
    description: 'From script intake to counseling: streamline daily pharmacy tasks.',
    sampleOutline: ['Intake & triage', 'Dispensing flow', 'Counseling steps', 'Quality checks'],
    duration: '3 weeks (18 hours total)',
    schedule: 'Thu 18:00–21:00, Aug 28 – Sep 11',
    price: { currency: 'PHP', amount: 3600 },
    teacher: { name: 'R. Tambis & L. Sim', title: 'Community Pharmacy Coaches', avatar: 'https://images.unsplash.com/photo-1556157382-97eda2d62296?q=80&w=200&auto=format&fit=crop' }
  },
  53: {
    id: 53,
    title: 'Community Pharmacy Supplies & Inventory',
    program: 'Community Pharmacy',
    img: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=1200&auto=format&fit=crop',
    description: 'Forecasting, storage, FEFO, and audit routines to keep stock safe and available.',
    sampleOutline: ['FEFO & cold chain', 'Reorder points', 'Cycle counts', 'Shrink control'],
    duration: '3 weeks (18 hours total)',
    schedule: 'Sat 9:00–12:00, Aug 30 – Sep 13',
    price: { currency: 'PHP', amount: 3600 },
    teacher: { name: 'R. Tambis & L. Sim', title: 'Inventory & Compliance Leads', avatar: 'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?q=80&w=200&auto=format&fit=crop' }
  }
}


// -