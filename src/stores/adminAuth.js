import { defineStore } from 'pinia'
import { authApi } from '@/api/authApi'

export const useAdminAuthStore = defineStore('adminAuth', {
  state: () => ({
    token: localStorage.getItem('adminToken') || null,
    user: null,
    loading: false,
  }),

  getters: {
    isLoggedIn: (state) => !!state.token,
  },

  actions: {
    async login(email, password) {
      this.loading = true
      try {
        const res = await authApi.adminLogin(email, password)
        if (!res.data?.data?.token) {
           return { success: false, message: 'Invalid response from server' }
        }
        this.token = res.data.data.token
        this.user  = res.data.data.user
        localStorage.setItem('adminToken', this.token)
        return { success: true }
      } catch (err) {
        return { 
          success: false, 
          message: err.response?.data?.message || 'Login failed',
          errors: err.response?.data?.errors
        }
      } finally {
        this.loading = false
      }
    },

    async fetchMe() {
      try {
        const res = await authApi.adminMe()
        this.user = res.data.data
      } catch {
        this.logout()
      }
    },

    async logout() {
      try { await authApi.adminLogout() } catch {}
      this.token = null
      this.user  = null
      localStorage.removeItem('adminToken')
    },
  }
})
