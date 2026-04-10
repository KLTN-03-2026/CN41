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
  role?: string
}

export interface LoginResponse {
  token: string
  student?: Student
  user?: AdminUser
}
