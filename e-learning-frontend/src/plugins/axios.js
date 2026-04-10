import axios from 'axios'

const http = axios.create({
  baseURL: '/api/v1',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
  timeout: 15000,
})

/**
 * Lấy token từ localStorage (remember) hoặc sessionStorage (session-only).
 */
function getToken(key) {
  return localStorage.getItem(key) || sessionStorage.getItem(key)
}

// Request interceptor — tự gắn token
http.interceptors.request.use((config) => {
  if (config.url?.startsWith('/admin')) {
    const token = getToken('adminToken')
    if (token) config.headers.Authorization = `Bearer ${token}`
  } else {
    const token = getToken('studentToken')
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
        const { useAdminAuthStore } = await import('@/stores/adminAuth.store')
        const adminStore = useAdminAuthStore()
        adminStore.token = null
        adminStore.user = null
        localStorage.removeItem('adminToken')
        sessionStorage.removeItem('adminToken')
        window.location.href = '/admin/login'
      } else {
        const { useStudentAuthStore } = await import('@/stores/studentAuth.store')
        const studentStore = useStudentAuthStore()
        studentStore.token = null
        studentStore.student = null
        localStorage.removeItem('studentToken')
        sessionStorage.removeItem('studentToken')
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export default http
