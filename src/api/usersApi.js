import http from '@/plugins/axios'

export const usersApi = {
  /** GET /admin/users?per_page=&page= */
  index: (params = {}) => http.get('/admin/users', { params }),

  /** GET /admin/users/{id} */
  show: (id) => http.get(`/admin/users/${id}`),

  /** POST /admin/users */
  store: (data) => http.post('/admin/users', data),

  /** PUT /admin/users/{id} */
  update: (id, data) => http.put(`/admin/users/${id}`, data),

  /** DELETE /admin/users/{id} */
  destroy: (id) => http.delete(`/admin/users/${id}`),

  /** POST /admin/users/{id}/restore */
  restore: (id) => http.post(`/admin/users/${id}/restore`),
}
