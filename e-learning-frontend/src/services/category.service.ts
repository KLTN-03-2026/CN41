import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse, PaginatedResponse, Category } from '@/types'

export const categoryService = {
  // ── Admin ──────────────────────────────────────────────────
  // NOTE: Categories module dùng prefix /api/v1/admin (có v1), khác với Courses/Lessons (/api/admin)
  /** GET /admin/categories?per_page=&page= */
  index: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Category>>> =>
    http.get('/admin/categories', { params }),

  /** GET /admin/categories/tree */
  tree: (): Promise<AxiosResponse<ApiResponse<Category[]>>> =>
    http.get('/admin/categories/tree'),

  /** GET /admin/categories/flat-tree */
  flatTree: (): Promise<AxiosResponse<ApiResponse<Category[]>>> =>
    http.get('/admin/categories/flat-tree'),

  /** GET /admin/categories/{id} */
  show: (id: number): Promise<AxiosResponse<ApiResponse<Category>>> =>
    http.get(`/admin/categories/${id}`),

  /** POST /admin/categories */
  store: (data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Category>>> =>
    http.post('/admin/categories', data),

  /** PUT /admin/categories/{id} */
  update: (id: number, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Category>>> =>
    http.put(`/admin/categories/${id}`, data),

  /** DELETE /admin/categories/{id} */
  destroy: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/categories/${id}`),

  /** GET /admin/categories/trashed */
  trashed: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Category>>> =>
    http.get('/admin/categories/trashed', { params }),

  /** POST /admin/categories/{id}/restore */
  restore: (id: number): Promise<AxiosResponse<ApiResponse<Category>>> =>
    http.post(`/admin/categories/${id}/restore`),

  /** DELETE /admin/categories/{id}/force-delete */
  forceDelete: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/categories/${id}/force-delete`),

  // ── Public ─────────────────────────────────────────────────
  /** GET /categories */
  publicList: (): Promise<AxiosResponse<ApiResponse<Category[]>>> =>
    http.get('/categories'),

  /** GET /categories/tree */
  publicTree: (): Promise<AxiosResponse<ApiResponse<Category[]>>> =>
    http.get('/categories/tree'),
}
