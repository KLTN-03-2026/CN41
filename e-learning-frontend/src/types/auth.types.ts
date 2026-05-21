export interface Student {
  id: number
  name: string
  email: string
  avatar?: string
  date_of_birth?: string
  email_verified_at?: string
}

/** Used by adminAuth store — /me returns roles as string[] */
export interface AuthAdminUser {
  id: number
  name: string
  email: string
  avatar: string | null
  email_verified_at?: string | null
  status?: number
  roles?: string[]
  permissions?: string[]
}

/** Used by admin users list — /admin/users returns roles as {id, name}[] */
export interface AdminUser {
  id: number
  name: string
  email: string
  avatar: string | null
  email_verified_at?: string | null
  status?: number
  roles?: { id: number; name: string }[]
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
