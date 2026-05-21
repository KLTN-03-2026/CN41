import http from '@/plugins/axios'
import type { AxiosResponse } from 'axios'
import type { ApiResponse } from '@/types'

export interface Permission {
  id: number
  name: string
  guard_name: string
}

export interface Role {
  id: number
  name: string
  guard_name: string
  users_count?: number
  permissions: Permission[]
}

export const roleService = {
  /** GET /admin/roles */
  index: (): Promise<AxiosResponse<ApiResponse<Role[]>>> => http.get('/admin/roles'),

  /** GET /admin/permissions */
  getPermissions: (): Promise<AxiosResponse<ApiResponse<Permission[]>>> =>
    http.get('/admin/permissions'),

  /** POST /admin/roles */
  store: (data: {
    name: string
    permissions: string[]
  }): Promise<AxiosResponse<ApiResponse<Role>>> => http.post('/admin/roles', data),

  /** PUT /admin/roles/{id} */
  update: (
    id: number,
    data: { name?: string; permissions?: string[] },
  ): Promise<AxiosResponse<ApiResponse<Role>>> => http.patch(`/admin/roles/${id}`, data),

  /** DELETE /admin/roles/{id} */
  destroy: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/roles/${id}`),
}
