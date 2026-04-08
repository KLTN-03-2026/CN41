import http from '@/plugins/axios'

export const authApi = {
  // Admin
  adminLogin: (email, password) => http.post('/admin/auth/login', { email, password }),
  adminLogout: () => http.post('/admin/auth/logout'),
  adminMe: () => http.get('/admin/auth/me'),

  // Student
  studentLogin: (email, password) => http.post('/auth/login', { email, password }),
  studentRegister: (data) => http.post('/auth/register', data),
  studentLogout: () => http.post('/auth/logout'),
  studentMe: () => http.get('/auth/me'),

  // Common
  forgotPassword: (email) => http.post('/auth/forgot-password', { email }),
  resetPassword: (data) => http.post('/auth/reset-password', data),
}
