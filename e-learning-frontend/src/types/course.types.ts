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
  order: number
  lessons?: Lesson[]
}

export interface Course {
  id: number
  title: string
  slug: string
  thumbnail?: string
  price: number
  sale_price?: number
  level: string
  status: number
  rating?: number
  teacher?: Teacher
  categories?: Category[]
}
