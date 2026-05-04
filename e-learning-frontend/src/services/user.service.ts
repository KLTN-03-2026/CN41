import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse, PaginatedResponse, AdminUser } from '@/types'

export const userService = {
  /** GET /admin/users?per_page=&page= */
  index: (
    params: Record<string, unknown> = {},
  ): Promise<AxiosResponse<PaginatedResponse<AdminUser>>> => http.get('/admin/users', { params }),

  /** GET /admin/users/{id} */
  show: (id: number): Promise<AxiosResponse<ApiResponse<AdminUser>>> =>
    http.get(`/admin/users/${id}`),

  /** POST /admin/users */
  store: (data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<AdminUser>>> =>
    http.post('/admin/users', data),

  /** PUT /admin/users/{id} */
  update: (
    id: number,
    data: Record<string, unknown>,
  ): Promise<AxiosResponse<ApiResponse<AdminUser>>> => http.put(`/admin/users/${id}`, data),

  /** DELETE /admin/users/{id} */
  destroy: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/users/${id}`),

  /** GET /admin/users/trashed */
  trashed: (
    params: Record<string, unknown> = {},
  ): Promise<AxiosResponse<PaginatedResponse<AdminUser>>> =>
    http.get('/admin/users/trashed', { params }),

  /** DELETE /admin/users/{id}/force-delete */
  forceDelete: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/users/${id}/force-delete`),

  /** POST /admin/users/{id}/restore */
  restore: (id: number): Promise<AxiosResponse<ApiResponse<AdminUser>>> =>
    http.post(`/admin/users/${id}/restore`),

  /** DELETE /admin/users/bulk-delete */
  bulkDelete: (ids: number[]): Promise<AxiosResponse<ApiResponse<any>>> =>
    http.delete('/admin/users/bulk-delete', { data: { ids } }),

  /** POST /admin/users/bulk-restore */
  bulkRestore: (ids: number[]): Promise<AxiosResponse<ApiResponse<any>>> =>
    http.post('/admin/users/bulk-restore', { ids }),

  /** DELETE /admin/users/bulk-force-delete */
  bulkForceDelete: (ids: number[]): Promise<AxiosResponse<ApiResponse<any>>> =>
    http.delete('/admin/users/bulk-force-delete', { data: { ids } }),

  /** POST /admin/users/bulk-action */
  bulkAction: (ids: number[], action: string): Promise<AxiosResponse<ApiResponse<any>>> =>
    http.post('/admin/users/bulk-action', { ids, action }),

  /** POST /admin/users/bulk-assign-role */
  bulkAssignRole: (ids: number[], role: string): Promise<AxiosResponse<ApiResponse<any>>> =>
    http.post('/admin/users/bulk-assign-role', { ids, role }),

  /** POST /admin/users/{id}/assign-role */
  assignRole: (id: number, role: string): Promise<AxiosResponse<ApiResponse<any>>> =>
    http.post(`/admin/users/${id}/assign-role`, { role }),

  /** GET /admin/users/roles */
  getRoles: (): Promise<AxiosResponse<ApiResponse<{ id: number; name: string }[]>>> =>
    http.get('/admin/users/roles'),
}
