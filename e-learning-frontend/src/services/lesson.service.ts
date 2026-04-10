import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse, PaginatedResponse, Lesson } from '@/types'

export const lessonService = {
  // ── Admin ──────────────────────────────────────────────────
  /** GET /admin/courses/{courseId}/lessons?status=&type=&per_page= */
  index: (courseId: number, params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Lesson>>> =>
    http.get(`/admin/courses/${courseId}/lessons`, { params }),

  /** POST /admin/courses/{courseId}/lessons */
  store: (courseId: number, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Lesson>>> =>
    http.post(`/admin/courses/${courseId}/lessons`, data),

  /** GET /admin/lessons/{id} */
  show: (id: number): Promise<AxiosResponse<ApiResponse<Lesson>>> =>
    http.get(`/admin/lessons/${id}`),

  /** PUT /admin/lessons/{id} */
  update: (id: number, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Lesson>>> =>
    http.put(`/admin/lessons/${id}`, data),

  /** DELETE /admin/lessons/{id} (soft delete) */
  destroy: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/lessons/${id}`),

  /** PATCH /admin/lessons/{id}/toggle-status */
  toggleStatus: (id: number): Promise<AxiosResponse<ApiResponse<Lesson>>> =>
    http.patch(`/admin/lessons/${id}/toggle-status`),

  /** GET /admin/lessons/trashed */
  trashed: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Lesson>>> =>
    http.get('/admin/lessons/trashed', { params }),

  /** POST /admin/lessons/{id}/restore */
  restore: (id: number): Promise<AxiosResponse<ApiResponse<Lesson>>> =>
    http.post(`/admin/lessons/${id}/restore`),

  /** DELETE /admin/lessons/{id}/force-delete */
  forceDelete: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/lessons/${id}/force-delete`),

  /** POST /admin/lessons/reorder */
  reorder: (orders: unknown[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post('/admin/lessons/reorder', { orders }),

  /** DELETE /admin/lessons/bulk-delete */
  bulkDelete: (ids: number[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete('/admin/lessons/bulk-delete', { data: { ids } }),

  /** POST /admin/lessons/bulk-restore */
  bulkRestore: (ids: number[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post('/admin/lessons/bulk-restore', { ids }),

  /** DELETE /admin/lessons/bulk-force-delete */
  bulkForceDelete: (ids: number[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete('/admin/lessons/bulk-force-delete', { data: { ids } }),

  /** POST /admin/lessons/bulk-action */
  bulkAction: (data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post('/admin/lessons/bulk-action', data),

  // ── Client (auth:api) ───────────────────────────────────────
  /** GET /my-courses/{slug}/lessons */
  myLessons: (slug: string): Promise<AxiosResponse<ApiResponse<Lesson[]>>> =>
    http.get(`/my-courses/${slug}/lessons`),

  /** GET /my-courses/{courseSlug}/lessons/{lessonSlug} */
  myLessonDetail: (courseSlug: string, lessonSlug: string): Promise<AxiosResponse<ApiResponse<Lesson>>> =>
    http.get(`/my-courses/${courseSlug}/lessons/${lessonSlug}`),

  /** POST /my-courses/{courseSlug}/lessons/{lessonSlug}/complete */
  complete: (courseSlug: string, lessonSlug: string): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post(`/my-courses/${courseSlug}/lessons/${lessonSlug}/complete`),

  /** POST /my-courses/{courseSlug}/lessons/{lessonSlug}/progress */
  saveProgress: (courseSlug: string, lessonSlug: string, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post(`/my-courses/${courseSlug}/lessons/${lessonSlug}/progress`, data),

  /** POST /lessons/{id}/progress — cập nhật tiến độ theo lessonId */
  updateProgress: (lessonId: number, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post(`/lessons/${lessonId}/progress`, data),

  /** GET /courses/{slug}/progress */
  courseProgress: (courseSlug: string): Promise<AxiosResponse<ApiResponse<unknown>>> =>
    http.get(`/courses/${courseSlug}/progress`),
}
