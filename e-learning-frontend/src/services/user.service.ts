import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse, PaginatedResponse, AdminUser } from '@/types'

export const userService = {
  /** GET /admin/users?per_page=&page= */
  index: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<AdminUser>>> =>
    http.get('/admin/users', { params }),

  /** GET /admin/users/{id} */
  show: (id: number): Promise<AxiosResponse<ApiResponse<AdminUser>>> =>
    http.get(`/admin/users/${id}`),

  /** POST /admin/users */
  store: (data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<AdminUser>>> =>
    http.post('/admin/users', data),

  /** PUT /admin/users/{id} */
  update: (id: number, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<AdminUser>>> =>
    http.put(`/admin/users/${id}`, data),

  /** DELETE /admin/users/{id} */
  destroy: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/users/${id}`),

  /** POST /admin/users/{id}/restore */
  restore: (id: number): Promise<AxiosResponse<ApiResponse<AdminUser>>> =>
    http.post(`/admin/users/${id}/restore`),
}
