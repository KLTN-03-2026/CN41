import { createRouter, createWebHistory } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import ClientLayout from '@/layouts/ClientLayout.vue'
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // ── ADMIN ──────────────────────────────────────────────
    {
      path: '/admin/login',
      component: () => import('@/views/auth/AdminLoginPage.vue'),
      meta: { requiresGuest: true, guard: 'admin' }
    },
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true, guard: 'admin' },
      children: [
        { path: '',          redirect: '/admin/dashboard' },
        { path: 'dashboard', component: () => import('@/views/admin/DashboardPage.vue') },
        { path: 'courses',   component: () => import('@/views/admin/CoursesPage.vue') },
        { path: 'courses/create', component: () => import('@/views/admin/CourseFormPage.vue') },
        { path: 'courses/:id/edit', component: () => import('@/views/admin/CourseFormPage.vue') },
        { path: 'categories', component: () => import('@/views/admin/CategoriesPage.vue') },
        { path: 'users', component: () => import('@/views/admin/UsersPage.vue') },
        { path: 'teachers', component: () => import('@/views/admin/TeachersPage.vue') },
        { path: 'students', component: () => import('@/views/admin/StudentsPage.vue') },
        { path: 'orders', component: () => import('@/views/admin/OrdersPage.vue') },
        { path: 'posts', component: () => import('@/views/admin/PostsPage.vue') },
        { path: 'coupons', component: () => import('@/views/admin/CouponsPage.vue') },
      ]
    },

    // ── CLIENT ─────────────────────────────────────────────
    {
      path: '/',
      component: ClientLayout,
      children: [
        { path: '',           component: () => import('@/views/client/HomePage.vue') },
        { path: 'courses',    component: () => import('@/views/client/CoursesPage.vue') },
        { path: 'courses/:slug', component: () => import('@/views/client/CourseDetailPage.vue') },
        { path: 'posts',      component: () => import('@/views/client/PostsPage.vue') },
        // Cần auth
        {
          path: 'my-courses',
          component: () => import('@/views/client/MyCoursesPage.vue'),
          meta: { requiresAuth: true, guard: 'student' }
        },
        {
          path: 'my-orders',
          component: () => import('@/views/client/MyOrdersPage.vue'),
          meta: { requiresAuth: true, guard: 'student' }
        },
        // LearnPage → đã chuyển ra ngoài ClientLayout (fullscreen)
        {
          path: 'cart',
          component: () => import('@/views/client/CartPage.vue'),
          meta: { requiresAuth: true, guard: 'student' }
        },
        {
          path: 'checkout',
          component: () => import('@/views/client/CheckoutPage.vue'),
          meta: { requiresAuth: true, guard: 'student' }
        },
        {
          path: 'payment/result',
          component: () => import('@/views/client/PaymentResultPage.vue'),
        },
        {
          path: 'profile',
          component: () => import('@/views/client/ProfilePage.vue'),
          meta: { requiresAuth: true, guard: 'student' }
        },
      ]
    },

    // ── LEARN PAGE (fullscreen, no layout) ───────────────────
    {
      path: '/courses/:slug/learn',
      component: () => import('@/views/client/LearnPage.vue'),
    },

    // ── AUTH CLIENT ────────────────────────────────────────
    {
       path: '/login',
       component: () => import('@/views/auth/LoginPage.vue'),
       meta: { requiresGuest: true, guard: 'student' }
    },
    {
       path: '/register',
       component: () => import('@/views/auth/RegisterPage.vue'),
       meta: { requiresGuest: true, guard: 'student' }
    },

    // ── ERROR PAGES ────────────────────────────────────────
    { path: '/403', component: () => import('@/views/ForbiddenPage.vue') },
    { path: '/:pathMatch(.*)*', component: () => import('@/views/NotFoundPage.vue') },
  ]
})

// ── Navigation Guard ───────────────────────────────────────
router.beforeEach((to, from, next) => {
  NProgress.start()
  const adminToken   = localStorage.getItem('adminToken')
  const studentToken = localStorage.getItem('studentToken')

  // Route cần auth
  if (to.meta.requiresAuth) {
    if (to.meta.guard === 'admin' && !adminToken) {
      return next('/admin/login')
    }
    if (to.meta.guard === 'student' && !studentToken) {
      return next({ path: '/login', query: { redirect: to.fullPath } })
    }
  }

  // Route chỉ dành cho guest (login page)
  if (to.meta.requiresGuest) {
    if (to.meta.guard === 'admin' && adminToken) return next('/admin/dashboard')
    // Trang /login client: redirect nếu đã login (student hoặc admin)
    if (to.meta.guard === 'student') {
      if (studentToken) return next('/')
      if (adminToken) return next('/admin/dashboard')
    }
  }

  next()
})

router.afterEach(() => {
  NProgress.done()
})

export default router
