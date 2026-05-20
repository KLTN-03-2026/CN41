import axios from 'axios'

const http = axios.create({
  baseURL: '/api/v1',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  timeout: 60000, // Tăng lên 60s để chờ các API AI/Generate lâu
})

/**
 * Lấy token từ localStorage (remember) hoặc sessionStorage (session-only).
 */
function getToken(key) {
  return localStorage.getItem(key) || sessionStorage.getItem(key)
}

// Request interceptor — tự gắn token
// /admin và /teacher đều dùng guard admin (adminToken)
http.interceptors.request.use((config) => {
  if (config.url?.startsWith('/admin') || config.url?.startsWith('/teacher')) {
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
// Logout paths included — prevents 401 loop when store.logout() fires the logout API call
const AUTH_PATHS = [
  '/admin/auth/login',
  '/admin/auth/logout',
  '/auth/login',
  '/auth/logout',
  '/auth/register',
  '/auth/forgot-password',
  '/auth/reset-password',
]

http.interceptors.response.use(
  (res) => res,
  async (error) => {
    const status = error.response?.status
    const requestUrl = error.config?.url || ''
    const isAuthEndpoint = AUTH_PATHS.some((p) => requestUrl.includes(p))

    // Chỉ redirect khi 401 xảy ra trên route CẦN auth (token hết hạn), không phải trên login/register
    if (status === 401 && !isAuthEndpoint) {
      const isAdminRoute = requestUrl.startsWith('/admin') || requestUrl.startsWith('/teacher')
      if (isAdminRoute) {
        const { useAdminAuthStore } = await import('@/stores/adminAuth.store')
        await useAdminAuthStore().logout()
        window.location.href = '/admin/login'
      } else {
        const { useStudentAuthStore } = await import('@/stores/studentAuth.store')
        await useStudentAuthStore().logout()
        window.location.href = '/login'
      }
    }

    // Xử lý chung cho lỗi 403 (Không có quyền)
    if (status === 403) {
      const { useToast } = await import('vue-toastification')
      const toast = useToast()
      toast.error('Bạn không có quyền thực hiện hành động này!')
    }

    return Promise.reject(error)
  },
)

export default http
