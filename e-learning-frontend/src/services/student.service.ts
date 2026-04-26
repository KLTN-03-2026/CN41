import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse, PaginatedResponse } from '@/types'

export interface Student {
  id: number
  name: string
  email: string
  avatar: string | null
  date_of_birth: string | null
  email_verified_at: string | null
  created_at: string
  updated_at: string
}

export const studentService = {
  // ── Admin ──────────────────────────────────────────────────
  /** GET /admin/students?search=&per_page= */
  index: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Student>>> =>
    http.get('/admin/students', { params }),

  /** GET /admin/students/{id} */
  show: (id: number): Promise<AxiosResponse<ApiResponse<Student>>> =>
    http.get(`/admin/students/${id}`),

  /** POST /admin/students */
  store: (data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Student>>> =>
    http.post('/admin/students', data),

  /** PUT /admin/students/{id} */
  update: (id: number, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Student>>> =>
    http.put(`/admin/students/${id}`, data),

  /** DELETE /admin/students/{id} (soft delete) */
  destroy: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/students/${id}`),

  /** GET /admin/students/trashed */
  trashed: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Student>>> =>
    http.get('/admin/students/trashed', { params }),

  /** POST /admin/students/{id}/restore */
  restore: (id: number): Promise<AxiosResponse<ApiResponse<Student>>> =>
    http.post(`/admin/students/${id}/restore`),

  /** DELETE /admin/students/{id}/force-delete */
  forceDelete: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/students/${id}/force-delete`),

  /** DELETE /admin/students/bulk-delete */
  bulkDelete: (ids: number[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete('/admin/students/bulk-delete', { data: { ids } }),

  /** POST /admin/students/bulk-restore */
  bulkRestore: (ids: number[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post('/admin/students/bulk-restore', { ids }),

  /** DELETE /admin/students/bulk-force-delete */
  bulkForceDelete: (ids: number[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete('/admin/students/bulk-force-delete', { data: { ids } }),
}
