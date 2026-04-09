import http from '@/plugins/axios'

export const categoriesApi = {
  // ── Admin ──────────────────────────────────────────────────
  // NOTE: Categories module dùng prefix /api/v1/admin (có v1), khác với Courses/Lessons (/api/admin)
  /** GET /admin/categories?per_page=&page= */
  index: (params = {}) => http.get('/admin/categories', { params }),

  /** GET /admin/categories/tree */
  tree: () => http.get('/admin/categories/tree'),

  /** GET /admin/categories/flat-tree */
  flatTree: () => http.get('/admin/categories/flat-tree'),

  /** GET /admin/categories/{id} */
  show: (id) => http.get(`/admin/categories/${id}`),

  /** POST /admin/categories */
  store: (data) => http.post('/admin/categories', data),

  /** PUT /admin/categories/{id} */
  update: (id, data) => http.put(`/admin/categories/${id}`, data),

  /** DELETE /admin/categories/{id} */
  destroy: (id) => http.delete(`/admin/categories/${id}`),

  /** GET /admin/categories/trashed */
  trashed: (params = {}) => http.get('/admin/categories/trashed', { params }),

  /** POST /admin/categories/{id}/restore */
  restore: (id) => http.post(`/admin/categories/${id}/restore`),

  /** DELETE /admin/categories/{id}/force-delete */
  forceDelete: (id) => http.delete(`/admin/categories/${id}/force-delete`),

  // ── Public ─────────────────────────────────────────────────
  /** GET /categories */
  publicList: () => http.get('/categories'),

  /** GET /categories/tree */
  publicTree: () => http.get('/categories/tree'),
}
