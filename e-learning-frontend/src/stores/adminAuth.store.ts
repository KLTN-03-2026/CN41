import { defineStore } from 'pinia'
import type { AdminUser } from '@/types'
import { STORAGE_KEYS } from '@/constants/app'
import { authService } from '@/services/auth.service'

interface ActionResult {
  success: boolean
  message?: string
  errors?: Record<string, string[]>
}

/**
 * Lấy token từ localStorage (remember) hoặc sessionStorage (session-only).
 */
function getStoredToken(): string | null {
  return localStorage.getItem(STORAGE_KEYS.ADMIN_TOKEN)
    || sessionStorage.getItem(STORAGE_KEYS.ADMIN_TOKEN)
}

export const useAdminAuthStore = defineStore('adminAuth', {
  state: () => ({
    token: getStoredToken() as string | null,
    user: null as AdminUser | null,
    loading: false,
  }),

  getters: {
    isLoggedIn: (state): boolean => !!state.token,
    userName: (state): string => state.user?.name || '',
  },

  actions: {
    async login(email: string, password: string, remember = false): Promise<ActionResult> {
      this.loading = true
      try {
        const res = await authService.adminLogin(email, password)
        if (!res.data?.data?.token) {
          return { success: false, message: 'Invalid response from server' }
        }
        this.token = res.data.data.token
        this.user = res.data.data.user ?? null

        // Lưu token theo lựa chọn "Ghi nhớ đăng nhập"
        if (remember) {
          localStorage.setItem(STORAGE_KEYS.ADMIN_TOKEN, this.token)
        } else {
          sessionStorage.setItem(STORAGE_KEYS.ADMIN_TOKEN, this.token)
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

    async fetchMe(): Promise<ActionResult> {
      try {
        const res = await authService.adminMe()
        this.user = (res.data.data as AdminUser) ?? null
        return { success: true }
      } catch {
        await this.logout()
        return { success: false }
      }
    },

    async logout(): Promise<ActionResult> {
      try {
        await authService.adminLogout()
      } catch {
        // Ignore logout errors as we're clearing state anyway
      }
      this.token = null
      this.user = null
      // Xóa ở cả 2 storage
      localStorage.removeItem(STORAGE_KEYS.ADMIN_TOKEN)
      sessionStorage.removeItem(STORAGE_KEYS.ADMIN_TOKEN)
      return { success: true }
    },
  },
})
