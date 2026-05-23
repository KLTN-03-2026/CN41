import http from '@/plugins/axios'

export const notificationService = {
  // Admin notifications
  getAdminNotifications: () => http.get('/admin/notifications'),
  markAdminRead: (id: number) => http.patch(`/admin/notifications/${id}/read`),
  markAdminAllRead: () => http.patch('/admin/notifications/mark-all-read'),

  // Teacher notifications
  getTeacherNotifications: () => http.get('/teacher/notifications'),
  markTeacherRead: (id: number) => http.patch(`/teacher/notifications/${id}/read`),
  markTeacherAllRead: () => http.patch('/teacher/notifications/mark-all-read'),
}
