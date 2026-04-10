import { defineStore } from 'pinia'
import type { AdminUser } from '@/types'
import { STORAGE_KEYS } from '@/constants/app'
import { authService } from '@/services/auth.service'

interface ActionResult {
  success: boolean
  message?: string
  errors?: Record<string, string[]>
}

export const useAdminAuthStore = defineStore('adminAuth', {
  state: () => ({
    token: localStorage.getItem(STORAGE_KEYS.ADMIN_TOKEN) as string | null,
    user: null as AdminUser | null,
    loading: false,
  }),

  getters: {
    isLoggedIn: (state): boolean => !!state.token,
    userName: (state): string => state.user?.name || '',
  },

  actions: {
    async login(email: string, password: string): Promise<ActionResult> {
      this.loading = true
      try {
        const res = await authService.adminLogin(email, password)
        if (!res.data?.data?.token) {
          return { success: false, message: 'Invalid response from server' }
        }
        this.token = res.data.data.token
        this.user = res.data.data.user ?? null
        localStorage.setItem(STORAGE_KEYS.ADMIN_TOKEN, this.token)
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
      } catch {}
      this.token = null
      this.user = null
      localStorage.removeItem(STORAGE_KEYS.ADMIN_TOKEN)
      return { success: true }
    },
  },
})
