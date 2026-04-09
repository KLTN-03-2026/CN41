import { defineStore } from 'pinia'
import { authApi } from '@/api/authApi'

export const useStudentAuthStore = defineStore('studentAuth', {
  state: () => ({
    token: localStorage.getItem('studentToken') || null,
    student: null,
    loading: false,
  }),

  getters: {
    isLoggedIn: (state) => !!state.token,
    fullName:   (state) => state.student?.name || '',
  },

  actions: {
    async login(email, password) {
      this.loading = true
      try {
        const res = await authApi.studentLogin(email, password)
        if (!res.data?.data?.token) {
          return { success: false, message: 'Invalid response from server' }
        }
        this.token   = res.data.data.token
        this.student = res.data.data.student
        localStorage.setItem('studentToken', this.token)
        return { success: true }
      } catch (err) {
        return { 
          success: false, 
          message: err.response?.data?.message || 'Login failed',
          errors: err.response?.data?.errors,
        }
      } finally {
        this.loading = false
      }
    },

    async register(data) {
      this.loading = true
      try {
        const res = await authApi.studentRegister(data)
        if (!res.data?.data?.token) {
          return { success: false, message: 'Invalid response from server' }
        }
        this.token   = res.data.data.token
        this.student = res.data.data.student
        localStorage.setItem('studentToken', this.token)
        return { success: true }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Registration failed',
          errors: err.response?.data?.errors,
        }
      } finally {
        this.loading = false
      }
    },

    async fetchMe() {
      try {
        const res = await authApi.studentMe()
        this.student = res.data.data
      } catch {
        this.logout()
      }
    },

    async logout() {
      try { await authApi.studentLogout() } catch {}
      this.token   = null
      this.student = null
      localStorage.removeItem('studentToken')
    },
  }
})
