import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse, LoginResponse } from '@/types'

export const authService = {
  // Admin
  adminLogin: (email: string, password: string): Promise<AxiosResponse<ApiResponse<LoginResponse>>> =>
    http.post('/admin/auth/login', { email, password }),

  adminLogout: (): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post('/admin/auth/logout'),

  adminMe: (): Promise<AxiosResponse<ApiResponse<LoginResponse['user']>>> =>
    http.get('/admin/auth/me'),

  // Student
  studentLogin: (email: string, password: string): Promise<AxiosResponse<ApiResponse<LoginResponse>>> =>
    http.post('/auth/login', { email, password }),

  studentRegister: (data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<LoginResponse>>> =>
    http.post('/auth/register', data),

  studentLogout: (): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post('/auth/logout'),

  studentMe: (): Promise<AxiosResponse<ApiResponse<LoginResponse['student']>>> =>
    http.get('/auth/me'),

  // Common
  forgotPassword: (email: string): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post('/auth/forgot-password', { email }),

  resetPassword: (data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post('/auth/reset-password', data),

  studentResendVerification: (data: { email: string }): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post('/auth/resend-verification', data),
}
