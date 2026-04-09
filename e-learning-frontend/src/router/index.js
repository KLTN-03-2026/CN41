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
      component: () => import('@/pages/auth/AdminLoginPage.vue'),
      meta: { requiresGuest: true, guard: 'admin' }
    },
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true, guard: 'admin' },
      children: [
        { path: '',          redirect: '/admin/dashboard' },
        { path: 'dashboard', component: () => import('@/pages/admin/DashboardPage.vue') },
        { path: 'courses',   component: () => import('@/pages/admin/CoursesPage.vue') },
        { path: 'courses/create', component: () => import('@/pages/admin/CourseFormPage.vue') },
        { path: 'courses/:id/edit', component: () => import('@/pages/admin/CourseFormPage.vue') },
        { path: 'categories', component: () => import('@/pages/admin/CategoriesPage.vue') },
        { path: 'users', component: () => import('@/pages/admin/UsersPage.vue') },
        { path: 'teachers', component: () => import('@/pages/admin/TeachersPage.vue') },
        { path: 'students', component: () => import('@/pages/admin/StudentsPage.vue') },
        { path: 'orders', component: () => import('@/pages/admin/OrdersPage.vue') },
        { path: 'posts', component: () => import('@/pages/admin/PostsPage.vue') },
        { path: 'coupons', component: () => import('@/pages/admin/CouponsPage.vue') },
      ]
    },

    // ── CLIENT ─────────────────────────────────────────────
    {
      path: '/',
      component: ClientLayout,
      children: [
        { path: '',           component: () => import('@/pages/client/HomePage.vue') },
        { path: 'courses',    component: () => import('@/pages/client/CoursesPage.vue') },
        { path: 'courses/:slug', component: () => import('@/pages/client/CourseDetailPage.vue') },
        { path: 'posts',      component: () => import('@/pages/client/PostsPage.vue') },
        // Cần auth
        {
          path: 'my-courses',
          component: () => import('@/pages/client/MyCoursesPage.vue'),
          meta: { requiresAuth: true, guard: 'student' }
        },
        // LearnPage → đã chuyển ra ngoài ClientLayout (fullscreen)
        {
          path: 'cart',
          component: () => import('@/pages/client/CartPage.vue'),
          meta: { requiresAuth: true, guard: 'student' }
        },
        {
          path: 'checkout',
          component: () => import('@/pages/client/CheckoutPage.vue'),
          meta: { requiresAuth: true, guard: 'student' }
        },
        {
          path: 'payment/result',
          component: () => import('@/pages/client/PaymentResultPage.vue'),
        },
        {
          path: 'profile',
          component: () => import('@/pages/client/ProfilePage.vue'),
          meta: { requiresAuth: true, guard: 'student' }
        },
      ]
    },

    // ── LEARN PAGE (fullscreen, no layout) ───────────────────
    {
      path: '/courses/:slug/learn',
      component: () => import('@/pages/client/LearnPage.vue'),
    },

    // ── AUTH CLIENT ────────────────────────────────────────
    {
       path: '/login',
       component: () => import('@/pages/auth/LoginPage.vue'),
       meta: { requiresGuest: true, guard: 'student' }
    },
    {
       path: '/register',
       component: () => import('@/pages/auth/RegisterPage.vue'),
       meta: { requiresGuest: true, guard: 'student' }
    },

    // ── ERROR PAGES ────────────────────────────────────────
    { path: '/403', component: () => import('@/pages/ForbiddenPage.vue') },
    { path: '/:pathMatch(.*)*', component: () => import('@/pages/NotFoundPage.vue') },
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
