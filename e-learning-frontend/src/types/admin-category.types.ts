/** Category như trong admin panel (flat-tree từ API, kèm depth, status, deleted_at) */
export interface AdminCategory {
  id: number
  name: string
  slug: string
  description?: string | null
  status: number
  depth: number
  is_root: boolean
  parent_id?: number | null
  deleted_at?: string | null
}

/** Course như trong admin panel (có teacher, total_students, deleted_at) */
export interface AdminCourse {
  id: number
  name: string
  slug: string
  thumbnail?: string | null
  price: string
  sale_price?: string | null
  level: string
  status: number
  total_students: number
  teacher?: { id: number; name: string; slug: string } | null
  deleted_at?: string | null
}
