import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse, PaginatedResponse, Section } from '@/types'

export const sectionService = {
  // ── Admin ──────────────────────────────────────────────────

  /** GET /admin/courses/{courseId}/sections?status=&per_page= */
  index: (courseId: number, params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Section>>> =>
    http.get(`/admin/courses/${courseId}/sections`, { params }),

  /** POST /admin/courses/{courseId}/sections */
  store: (courseId: number, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Section>>> =>
    http.post(`/admin/courses/${courseId}/sections`, data),

  /** GET /admin/sections/{id} */
  show: (id: number): Promise<AxiosResponse<ApiResponse<Section>>> =>
    http.get(`/admin/sections/${id}`),

  /** PUT /admin/sections/{id} */
  update: (id: number, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Section>>> =>
    http.put(`/admin/sections/${id}`, data),

  /** DELETE /admin/sections/{id} (soft delete) */
  destroy: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/sections/${id}`),

  /** PATCH /admin/sections/{id}/toggle-status */
  toggleStatus: (id: number): Promise<AxiosResponse<ApiResponse<Section>>> =>
    http.patch(`/admin/sections/${id}/toggle-status`),

  /** GET /admin/sections/trashed */
  trashed: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Section>>> =>
    http.get('/admin/sections/trashed', { params }),

  /** POST /admin/sections/{id}/restore */
  restore: (id: number): Promise<AxiosResponse<ApiResponse<Section>>> =>
    http.post(`/admin/sections/${id}/restore`),

  /** DELETE /admin/sections/{id}/force-delete */
  forceDelete: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/sections/${id}/force-delete`),

  /** POST /admin/sections/reorder */
  reorder: (orders: unknown[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post('/admin/sections/reorder', { orders }),

  // ── Public ─────────────────────────────────────────────────

  /** GET /v1/courses/{slug}/curriculum */
  curriculum: (slug: string): Promise<AxiosResponse<ApiResponse<unknown>>> =>
    http.get(`/v1/courses/${slug}/curriculum`),
}
