import axios from 'axios'

const http = axios.create({
  baseURL: '/api/v1',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
  timeout: 15000,
})

// Request interceptor — tự gắn token
http.interceptors.request.use((config) => {
  if (config.url?.startsWith('/admin')) {
    const token = localStorage.getItem('adminToken')
    if (token) config.headers.Authorization = `Bearer ${token}`
  } else {
    const token = localStorage.getItem('studentToken')
    if (token) config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor — xử lý lỗi chung
http.interceptors.response.use(
  (res) => res,
  async (error) => {
    const status = error.response?.status
    const isAdminRoute = error.config?.url?.startsWith('/admin')
    if (status === 401) {
      if (isAdminRoute) {
        const { useAdminAuthStore } = await import('@/stores/adminAuth')
        const adminStore = useAdminAuthStore()
        adminStore.token = null
        adminStore.user = null
        localStorage.removeItem('adminToken')
        window.location.href = '/admin/login'
      } else {
        const { useStudentAuthStore } = await import('@/stores/studentAuth')
        const studentStore = useStudentAuthStore()
        studentStore.token = null
        studentStore.student = null
        localStorage.removeItem('studentToken')
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export default http
