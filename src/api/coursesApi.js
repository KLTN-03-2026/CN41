import http from '@/plugins/axios'

export const coursesApi = {
  // ── Admin ──────────────────────────────────────────────────
  /** GET /admin/courses?search=&status=&teacher_id=&category_id=&level=&per_page= */
  index: (params = {}) => http.get('/admin/courses', { params }),

  /** GET /admin/courses/{id} */
  show: (id) => http.get(`/admin/courses/${id}`),

  /** POST /admin/courses */
  store: (data) => http.post('/admin/courses', data),

  /** PUT /admin/courses/{id} */
  update: (id, data) => http.put(`/admin/courses/${id}`, data),

  /** DELETE /admin/courses/{id} (soft delete) */
  destroy: (id) => http.delete(`/admin/courses/${id}`),

  /** PATCH /admin/courses/{id}/toggle-status */
  toggleStatus: (id) => http.patch(`/admin/courses/${id}/toggle-status`),

  /** GET /admin/courses/trashed */
  trashed: (params = {}) => http.get('/admin/courses/trashed', { params }),

  /** POST /admin/courses/{id}/restore */
  restore: (id) => http.post(`/admin/courses/${id}/restore`),

  /** DELETE /admin/courses/{id}/force-delete */
  forceDelete: (id) => http.delete(`/admin/courses/${id}/force-delete`),

  /** DELETE /admin/courses/bulk-delete */
  bulkDelete: (ids) => http.delete('/admin/courses/bulk-delete', { data: { ids } }),

  /** POST /admin/courses/bulk-restore */
  bulkRestore: (ids) => http.post('/admin/courses/bulk-restore', { ids }),

  /** DELETE /admin/courses/bulk-force-delete */
  bulkForceDelete: (ids) => http.delete('/admin/courses/bulk-force-delete', { data: { ids } }),

  // ── Public ─────────────────────────────────────────────────
  /** GET /courses?search=&category_id=&level=&per_page= */
  publicIndex: (params = {}) => http.get('/courses', { params }),

  /** GET /courses/{slug} */
  publicShow: (slug) => http.get(`/courses/${slug}`),

  /** GET /courses/{slug}/lessons */
  publicLessons: (slug) => http.get(`/courses/${slug}/lessons`),

  /** GET /courses/{course_slug}/preview-lesson/{lesson_slug} */
  publicPreviewLesson: (courseSlug, lessonSlug) =>
    http.get(`/courses/${courseSlug}/preview-lesson/${lessonSlug}`),

  // ── Client (auth:api) ───────────────────────────────────────
  /** GET /my-courses?per_page= */
  myCourses: (params = {}) => http.get('/my-courses', { params }),

  /** POST /courses/{slug}/enroll-free */
  enrollFree: (slug) => http.post(`/courses/${slug}/enroll-free`),
}
