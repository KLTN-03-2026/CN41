import http from '@/plugins/axios'

export const teacherSectionService = {
  index: (courseId: number, params: Record<string, unknown> = {}) =>
    http.get(`/teacher/courses/${courseId}/sections`, { params }),

  store: (courseId: number, data: Record<string, unknown>) =>
    http.post(`/teacher/courses/${courseId}/sections`, data),

  update: (id: number, data: Record<string, unknown>) =>
    http.patch(`/teacher/sections/${id}`, data),

  destroy: (id: number) =>
    http.delete(`/teacher/sections/${id}`),

  toggleStatus: (id: number) =>
    http.patch(`/teacher/sections/${id}/toggle-status`),

  reorder: (orders: unknown[]) =>
    http.post('/teacher/sections/reorder', { orders }),
}
