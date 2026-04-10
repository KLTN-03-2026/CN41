import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse, PaginatedResponse, Teacher } from '@/types'

export const teacherService = {
  // ── Admin ──────────────────────────────────────────────────
  /** GET /admin/teachers?search=&status=&per_page= */
  index: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Teacher>>> =>
    http.get('/admin/teachers', { params }),

  /** GET /admin/teachers/{id} */
  show: (id: number): Promise<AxiosResponse<ApiResponse<Teacher>>> =>
    http.get(`/admin/teachers/${id}`),

  /** POST /admin/teachers */
  store: (data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Teacher>>> =>
    http.post('/admin/teachers', data),

  /** PUT /admin/teachers/{id} */
  update: (id: number, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Teacher>>> =>
    http.put(`/admin/teachers/${id}`, data),

  /** DELETE /admin/teachers/{id} (soft delete) */
  destroy: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/teachers/${id}`),

  /** PATCH /admin/teachers/{id}/toggle-status */
  toggleStatus: (id: number): Promise<AxiosResponse<ApiResponse<Teacher>>> =>
    http.patch(`/admin/teachers/${id}/toggle-status`),

  /** GET /admin/teachers/trashed */
  trashed: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Teacher>>> =>
    http.get('/admin/teachers/trashed', { params }),

  /** POST /admin/teachers/{id}/restore */
  restore: (id: number): Promise<AxiosResponse<ApiResponse<Teacher>>> =>
    http.post(`/admin/teachers/${id}/restore`),

  /** DELETE /admin/teachers/{id}/force-delete */
  forceDelete: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/teachers/${id}/force-delete`),

  /** DELETE /admin/teachers/bulk-delete */
  bulkDelete: (ids: number[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete('/admin/teachers/bulk-delete', { data: { ids } }),

  // ── Public ─────────────────────────────────────────────────
  /** GET /teachers */
  publicList: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Teacher>>> =>
    http.get('/teachers', { params }),

  /** GET /teachers/{slug} */
  publicShow: (slug: string): Promise<AxiosResponse<ApiResponse<Teacher>>> =>
    http.get(`/teachers/${slug}`),
}
