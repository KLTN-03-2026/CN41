export interface AdminLesson {
  id: number
  title: string
  type: string
  content?: string | null
  section_id?: number | null
  order: number
  is_preview: boolean
  duration?: number | null
  status: number
}

export interface AdminSection {
  id: number
  course_id: number
  title: string
  description?: string | null
  order: number
  status: number
  lessons: AdminLesson[]
}

export interface SectionForm {
  title: string
  description: string
  order: number
  status: number
}

export interface LessonForm {
  section_id: number | null
  title: string
  type: string
  content: string
  media_id: number | null
  order: number
  duration: number | null
  is_preview: boolean
  status: number
}
