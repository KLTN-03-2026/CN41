import http from '@/plugins/axios'

export const commissionService = {
  // Admin
  getSettings: () =>
    http.get('/admin/commission-settings'),
  updateSettings: (data: { teacher_rate: number }) =>
    http.patch('/admin/commission-settings', data),
  getAdminPayouts: (params: Record<string, unknown>) =>
    http.get('/admin/payouts', { params }),
  approvePayout: (id: number, data: { admin_note?: string }) =>
    http.patch(`/admin/payouts/${id}/approve`, data),
  rejectPayout: (id: number, data: { admin_note?: string }) =>
    http.patch(`/admin/payouts/${id}/reject`, data),
  markPaid: (id: number) =>
    http.patch(`/admin/payouts/${id}/mark-paid`),
  getTeacherEarningsSummary: () =>
    http.get('/admin/teacher-earnings'),

  // Teacher portal
  getMyEarnings: (params: Record<string, unknown>) =>
    http.get('/teacher/earnings', { params }),
  getMyPayouts: (params: Record<string, unknown>) =>
    http.get('/teacher/payouts', { params }),
  requestPayout: (data: { amount: number; teacher_note?: string }) =>
    http.post('/teacher/payouts', data),
  getTeacherDashboard: () =>
    http.get('/teacher/dashboard'),
  getTeacherCourses: (params: Record<string, unknown>) =>
    http.get('/teacher/courses', { params }),
  getTeacherProfile: () =>
    http.get('/teacher/profile'),
  updateTeacherProfile: (data: Record<string, unknown>) =>
    http.patch('/teacher/profile', data),
}
