import http from '@/plugins/axios'

export const sectionsApi = {
  // ── Admin ──────────────────────────────────────────────────

  /** GET /admin/courses/{courseId}/sections?status=&per_page= */
  index: (courseId, params = {}) =>
    http.get(`/admin/courses/${courseId}/sections`, { params }),

  /** POST /admin/courses/{courseId}/sections */
  store: (courseId, data) =>
    http.post(`/admin/courses/${courseId}/sections`, data),

  /** GET /admin/sections/{id} */
  show: (id) => http.get(`/admin/sections/${id}`),

  /** PUT /admin/sections/{id} */
  update: (id, data) => http.put(`/admin/sections/${id}`, data),

  /** DELETE /admin/sections/{id} (soft delete) */
  destroy: (id) => http.delete(`/admin/sections/${id}`),

  /** PATCH /admin/sections/{id}/toggle-status */
  toggleStatus: (id) => http.patch(`/admin/sections/${id}/toggle-status`),

  /** GET /admin/sections/trashed */
  trashed: (params = {}) => http.get('/admin/sections/trashed', { params }),

  /** POST /admin/sections/{id}/restore */
  restore: (id) => http.post(`/admin/sections/${id}/restore`),

  /** DELETE /admin/sections/{id}/force-delete */
  forceDelete: (id) => http.delete(`/admin/sections/${id}/force-delete`),

  /** POST /admin/sections/reorder */
  reorder: (orders) => http.post('/admin/sections/reorder', { orders }),

  // ── Public ─────────────────────────────────────────────────

  /** GET /v1/courses/{slug}/curriculum */
  curriculum: (slug) => http.get(`/v1/courses/${slug}/curriculum`),
}
