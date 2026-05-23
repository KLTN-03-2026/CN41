import http from '@/plugins/axios'

export const teacherLessonService = {
  index: (courseId: number, params: Record<string, unknown> = {}) =>
    http.get(`/teacher/courses/${courseId}/lessons`, { params }),

  store: (courseId: number, data: Record<string, unknown>) =>
    http.post(`/teacher/courses/${courseId}/lessons`, data),

  show: (id: number) =>
    http.get(`/teacher/lessons/${id}`),

  update: (id: number, data: Record<string, unknown>) =>
    http.patch(`/teacher/lessons/${id}`, data),

  destroy: (id: number) =>
    http.delete(`/teacher/lessons/${id}`),

  toggleStatus: (id: number) =>
    http.patch(`/teacher/lessons/${id}/toggle-status`),

  trashed: (params: Record<string, unknown> = {}) =>
    http.get('/teacher/lessons/trashed', { params }),

  restore: (id: number) =>
    http.patch(`/teacher/lessons/${id}/restore`),

  forceDelete: (id: number) =>
    http.delete(`/teacher/lessons/${id}/force-delete`),

  reorder: (orders: unknown[]) =>
    http.post('/teacher/lessons/reorder', { orders }),

  bulkDelete: (ids: number[]) =>
    http.delete('/teacher/lessons/bulk-delete', { data: { ids } }),

  bulkAction: (data: Record<string, unknown>) =>
    http.post('/teacher/lessons/bulk-action', data),

  bulkRestore: (ids: number[]) =>
    http.patch('/teacher/lessons/bulk-restore', { ids }),

  bulkForceDelete: (ids: number[]) =>
    http.delete('/teacher/lessons/bulk-force-delete', { data: { ids } }),
}
