export interface ApiResponse<T = unknown> {
  success: boolean
  message: string
  data: T
}

export interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  pagination: Pagination
}

export interface Lesson {
  id: number
  title: string
  slug: string
  type: 'video' | 'document'
  duration?: number
  is_preview?: boolean
  video_url?: string
  document_url?: string
  content?: string
  course_name?: string
  order?: number
  status?: number
  progress?: {
    is_completed?: boolean
    watched_seconds?: number
  }
}

export interface Section {
  id: number | null
  title: string
  lessons: Lesson[]
}
