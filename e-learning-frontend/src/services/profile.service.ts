import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse } from '@/types'
import type { Student } from '@/types/auth.types'

export const profileService = {
  /** GET /profile — lấy thông tin profile hiện tại */
  get: (): Promise<AxiosResponse<ApiResponse<Student>>> => http.get('/profile'),

  /** PATCH /profile — cập nhật name, email, date_of_birth */
  update: (data: {
    name?: string
    email?: string
    date_of_birth?: string | null
  }): Promise<AxiosResponse<ApiResponse<Student>>> => http.patch('/profile', data),

  /** POST /profile/avatar — upload file ảnh đại diện */
  uploadAvatar: (file: File): Promise<AxiosResponse<ApiResponse<{ avatar: string }>>> => {
    const form = new FormData()
    form.append('file', file)
    return http.post('/profile/avatar', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  /** POST /profile/change-password — xác thực mật khẩu cũ rồi gửi email reset */
  changePassword: (data: {
    current_password: string
    new_password: string
    new_password_confirmation: string
  }): Promise<AxiosResponse<ApiResponse<null>>> => http.post('/profile/change-password', data),
}
