export interface Student {
  id: number
  name: string
  email: string
  avatar?: string
  email_verified_at?: string
}

export interface AdminUser {
  id: number
  name: string
  email: string
  avatar: string | null
  email_verified_at?: string | null
  status?: number
  roles?: string[] | { id: number; name: string }[]
  permissions?: string[]
  created_at?: string
  updated_at?: string
  deleted_at?: string | null
}

export interface LoginResponse {
  token: string
  student?: Student
  user?: AdminUser
}
