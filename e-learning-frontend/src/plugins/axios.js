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
// Bỏ qua redirect 401 cho các endpoint auth (login/register trả 401 khi sai credentials là bình thường)
const AUTH_PATHS = ['/admin/auth/login', '/auth/login', '/auth/register', '/auth/forgot-password', '/auth/reset-password']

http.interceptors.response.use(
  (res) => res,
  async (error) => {
    const status = error.response?.status
    const requestUrl = error.config?.url || ''
    const isAuthEndpoint = AUTH_PATHS.some((p) => requestUrl.includes(p))

    // Chỉ redirect khi 401 xảy ra trên route CẦN auth (token hết hạn), không phải trên login/register
    if (status === 401 && !isAuthEndpoint) {
      const isAdminRoute = requestUrl.startsWith('/admin')
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
