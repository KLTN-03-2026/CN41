import { defineStore } from 'pinia'
import type { Student } from '@/types'
import { STORAGE_KEYS } from '@/constants/app'
import { authService } from '@/services/auth.service'

interface ActionResult {
  success: boolean
  message?: string
  errors?: Record<string, unknown>
}

/**
 * Lấy token từ localStorage (remember) hoặc sessionStorage (session-only).
 */
function getStoredToken(): string | null {
  return localStorage.getItem(STORAGE_KEYS.STUDENT_TOKEN)
    || sessionStorage.getItem(STORAGE_KEYS.STUDENT_TOKEN)
}

export const useStudentAuthStore = defineStore('studentAuth', {
  state: () => ({
    token: getStoredToken() as string | null,
    student: null as Student | null,
    loading: false,
  }),

  getters: {
    isLoggedIn: (state): boolean => !!state.token,
    fullName: (state): string => state.student?.name || '',
  },

  actions: {
    async login(email: string, password: string, remember = false): Promise<ActionResult> {
      this.loading = true
      try {
        const res = await authService.studentLogin(email, password)
        if (!res.data?.data?.token) {
          return { success: false, message: 'Invalid response from server' }
        }
        this.token = res.data.data.token
        this.student = res.data.data.student ?? null

        // Lưu token theo lựa chọn "Ghi nhớ đăng nhập"
        if (remember) {
          localStorage.setItem(STORAGE_KEYS.STUDENT_TOKEN, this.token)
        } else {
          sessionStorage.setItem(STORAGE_KEYS.STUDENT_TOKEN, this.token)
        }

        return { success: true }
      } catch (err: unknown) {
        const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
        return {
          success: false,
          message: e.response?.data?.message || 'Login failed',
          errors: e.response?.data?.errors,
        }
      } finally {
        this.loading = false
      }
    },

    async register(data: Record<string, unknown>): Promise<ActionResult> {
      this.loading = true
      try {
        const res = await authService.studentRegister(data)
        if (!res.data?.data?.token) {
          return { success: false, message: 'Invalid response from server' }
        }
        this.token = res.data.data.token
        this.student = res.data.data.student ?? null
        localStorage.setItem(STORAGE_KEYS.STUDENT_TOKEN, this.token)
        return { success: true }
      } catch (err: unknown) {
        const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
        return {
          success: false,
          message: e.response?.data?.message || 'Registration failed',
          errors: e.response?.data?.errors,
        }
      } finally {
        this.loading = false
      }
    },

    async fetchMe(): Promise<ActionResult> {
      try {
        const res = await authService.studentMe()
        this.student = (res.data.data as Student) ?? null
        return { success: true }
      } catch {
        await this.logout()
        return { success: false }
      }
    },

    async logout(): Promise<ActionResult> {
      try {
        await authService.studentLogout()
      } catch {
        // Ignore logout errors as we're clearing state anyway
      }
      this.token = null
      this.student = null
      // Xóa ở cả 2 storage
      localStorage.removeItem(STORAGE_KEYS.STUDENT_TOKEN)
      sessionStorage.removeItem(STORAGE_KEYS.STUDENT_TOKEN)
      return { success: true }
    },
  },
})
