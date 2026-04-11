export interface Category {
  id: number
  name: string
  slug: string
  parent_id?: number
  children?: Category[]
}

export interface Teacher {
  id: number
  name: string
  email: string
  bio?: string
  avatar?: string
}

export interface Lesson {
  id: number
  section_id: number
  title: string
  slug: string
  is_preview: boolean
  video_url?: string
  duration?: number
}

export interface Section {
  id: number
  course_id: number
  title: string
  description?: string
  order: number
  status: number
  lessons: Lesson[]
}

export interface AdminSectionForm {
  title: string
  description: string
  order: number
  status: number
}

export interface SectionForm {
  title: string
  description: string
  order: number
  status: number
}

export interface Course {
  id: number
  name: string
  slug: string
  description?: string
  thumbnail?: string
  price: number
  sale_price?: number
  level: string
  status: number
  rating?: number
  teacher?: Teacher
  categories?: Category[]
}

export interface AdminCourseForm {
  name: string
  slug: string
  description: string
  teacher_id: number | null
  category_id: number | null
  level: string
  price: number
  sale_price: number | null
  status: number
  thumbnail: string | null
}
