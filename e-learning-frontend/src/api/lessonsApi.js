import http from '@/plugins/axios'

export const lessonsApi = {
  // ── Admin ──────────────────────────────────────────────────
  /** GET /admin/courses/{courseId}/lessons?status=&type=&per_page= */
  index: (courseId, params = {}) =>
    http.get(`/admin/courses/${courseId}/lessons`, { params }),

  /** POST /admin/courses/{courseId}/lessons */
  store: (courseId, data) => http.post(`/admin/courses/${courseId}/lessons`, data),

  /** GET /admin/lessons/{id} */
  show: (id) => http.get(`/admin/lessons/${id}`),

  /** PUT /admin/lessons/{id} */
  update: (id, data) => http.put(`/admin/lessons/${id}`, data),

  /** DELETE /admin/lessons/{id} (soft delete) */
  destroy: (id) => http.delete(`/admin/lessons/${id}`),

  /** PATCH /admin/lessons/{id}/toggle-status */
  toggleStatus: (id) => http.patch(`/admin/lessons/${id}/toggle-status`),

  /** GET /admin/lessons/trashed */
  trashed: (params = {}) => http.get('/admin/lessons/trashed', { params }),

  /** POST /admin/lessons/{id}/restore */
  restore: (id) => http.post(`/admin/lessons/${id}/restore`),

  /** DELETE /admin/lessons/{id}/force-delete */
  forceDelete: (id) => http.delete(`/admin/lessons/${id}/force-delete`),

  /** POST /admin/lessons/reorder */
  reorder: (orders) => http.post('/admin/lessons/reorder', { orders }),

  // ── Client (auth:api) ───────────────────────────────────────
  /** GET /my-courses/{slug}/lessons */
  myLessons: (slug) => http.get(`/my-courses/${slug}/lessons`),

  /** GET /my-courses/{courseSlug}/lessons/{lessonSlug} */
  myLessonDetail: (courseSlug, lessonSlug) =>
    http.get(`/my-courses/${courseSlug}/lessons/${lessonSlug}`),

  /** POST /my-courses/{courseSlug}/lessons/{lessonSlug}/complete */
  complete: (courseSlug, lessonSlug) =>
    http.post(`/my-courses/${courseSlug}/lessons/${lessonSlug}/complete`),

  /** POST /my-courses/{courseSlug}/lessons/{lessonSlug}/progress */
  saveProgress: (courseSlug, lessonSlug, data) =>
    http.post(`/my-courses/${courseSlug}/lessons/${lessonSlug}/progress`, data),

  /** POST /lessons/{id}/progress — cập nhật tiến độ theo lessonId */
  updateProgress: (lessonId, data) =>
    http.post(`/lessons/${lessonId}/progress`, data),

  /** GET /courses/{slug}/progress */
  courseProgress: (courseSlug) =>
    http.get(`/courses/${courseSlug}/progress`),
}
