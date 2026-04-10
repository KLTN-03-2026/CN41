import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse, PaginatedResponse, Course } from '@/types'

export const courseService = {
  // ── Admin ──────────────────────────────────────────────────
  /** GET /admin/courses?search=&status=&teacher_id=&category_id=&level=&per_page= */
  index: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Course>>> =>
    http.get('/admin/courses', { params }),

  /** GET /admin/courses/{id} */
  show: (id: number): Promise<AxiosResponse<ApiResponse<Course>>> =>
    http.get(`/admin/courses/${id}`),

  /** POST /admin/courses */
  store: (data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Course>>> =>
    http.post('/admin/courses', data),

  /** PUT /admin/courses/{id} */
  update: (id: number, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Course>>> =>
    http.put(`/admin/courses/${id}`, data),

  /** DELETE /admin/courses/{id} (soft delete) */
  destroy: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/courses/${id}`),

  /** PATCH /admin/courses/{id}/toggle-status */
  toggleStatus: (id: number): Promise<AxiosResponse<ApiResponse<Course>>> =>
    http.patch(`/admin/courses/${id}/toggle-status`),

  /** GET /admin/courses/trashed */
  trashed: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Course>>> =>
    http.get('/admin/courses/trashed', { params }),

  /** POST /admin/courses/{id}/restore */
  restore: (id: number): Promise<AxiosResponse<ApiResponse<Course>>> =>
    http.post(`/admin/courses/${id}/restore`),

  /** DELETE /admin/courses/{id}/force-delete */
  forceDelete: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/courses/${id}/force-delete`),

  /** DELETE /admin/courses/bulk-delete */
  bulkDelete: (ids: number[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete('/admin/courses/bulk-delete', { data: { ids } }),

  /** POST /admin/courses/bulk-restore */
  bulkRestore: (ids: number[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post('/admin/courses/bulk-restore', { ids }),

  /** DELETE /admin/courses/bulk-force-delete */
  bulkForceDelete: (ids: number[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete('/admin/courses/bulk-force-delete', { data: { ids } }),

  // ── Public ─────────────────────────────────────────────────
  /** GET /courses?search=&category_id=&level=&per_page= */
  publicIndex: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Course>>> =>
    http.get('/courses', { params }),

  /** GET /courses/{slug} */
  publicShow: (slug: string): Promise<AxiosResponse<ApiResponse<Course>>> =>
    http.get(`/courses/${slug}`),

  /** GET /courses/{slug}/lessons */
  publicLessons: (slug: string): Promise<AxiosResponse<ApiResponse<unknown>>> =>
    http.get(`/courses/${slug}/lessons`),

  /** GET /courses/{course_slug}/preview-lesson/{lesson_slug} */
  publicPreviewLesson: (courseSlug: string, lessonSlug: string): Promise<AxiosResponse<ApiResponse<unknown>>> =>
    http.get(`/courses/${courseSlug}/preview-lesson/${lessonSlug}`),

  // ── Client (auth:api) ───────────────────────────────────────
  /** GET /my-courses?per_page= */
  myCourses: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Course>>> =>
    http.get('/my-courses', { params }),

  /** POST /courses/{slug}/enroll-free */
  enrollFree: (slug: string): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post(`/courses/${slug}/enroll-free`),
}
