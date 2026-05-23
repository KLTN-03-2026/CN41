import { createRouter, createWebHistory } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import TeacherLayout from '@/layouts/TeacherLayout.vue'
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
      meta: { requiresGuest: true, guard: 'admin' },
    },
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true, guard: 'admin' },
      children: [
        { path: '', redirect: '/admin/dashboard' },
        { path: 'dashboard',      component: () => import('@/views/admin/DashboardPage.vue'),     meta: { permission: 'dashboard.view' } },
        { path: 'courses',        component: () => import('@/views/admin/CoursesPage.vue'),        meta: { permission: 'courses.view' } },
        { path: 'courses/create', component: () => import('@/views/admin/CourseFormPage.vue'),     meta: { permission: 'courses.create' } },
        { path: 'courses/:id/edit', component: () => import('@/views/admin/CourseFormPage.vue'),   meta: { permission: 'courses.edit' } },
        { path: 'categories',     component: () => import('@/views/admin/CategoriesPage.vue'),     meta: { permission: 'categories.view' } },
        { path: 'roles',          component: () => import('@/views/admin/RolesPage.vue'),          meta: { permission: 'roles.view' } },
        { path: 'users',          component: () => import('@/views/admin/UsersPage.vue'),          meta: { permission: 'users.view' } },
        { path: 'teachers',       component: () => import('@/views/admin/TeachersPage.vue'),       meta: { permission: 'users.view' } },
        { path: 'students',       component: () => import('@/views/admin/StudentsPage.vue'),       meta: { permission: 'students.view' } },
        { path: 'orders',         component: () => import('@/views/admin/OrdersPage.vue'),         meta: { permission: 'orders.view' } },
        { path: 'posts',          component: () => import('@/views/admin/PostsPage.vue'),          meta: { permission: 'posts.view' } },
        { path: 'posts/create',   component: () => import('@/views/admin/PostFormPage.vue'),       meta: { permission: 'posts.create' } },
        { path: 'posts/:id/edit', component: () => import('@/views/admin/PostFormPage.vue'),       meta: { permission: 'posts.edit' } },
        { path: 'post-categories', component: () => import('@/views/admin/PostCategoriesPage.vue'), meta: { permission: 'posts.view' } },
        { path: 'tags',           component: () => import('@/views/admin/TagsPage.vue'),           meta: { permission: 'tags.view' } },
        { path: 'post-comments',  component: () => import('@/views/admin/CommentsPage.vue'),       meta: { permission: 'comments.view' } },
        { path: 'coupons',        component: () => import('@/views/admin/CouponsPage.vue'),        meta: { permission: 'coupons.view' } },
        { path: 'system-logs',    component: () => import('@/views/admin/ActivityLogsPage.vue'),   meta: { permission: 'system.logs.view' } },
        // Commission — admin
        {
          path: 'payouts',
          name: 'admin.payouts',
          component: () => import('@/views/admin/PayoutsPage.vue'),
          meta: { requiresAuth: true, guard: 'admin' },
        },
        {
          path: 'teacher-earnings',
          name: 'admin.teacher-earnings',
          component: () => import('@/views/admin/TeacherEarningsPage.vue'),
          meta: { requiresAuth: true, guard: 'admin' },
        },
        {
          path: 'commission-settings',
          name: 'admin.commission-settings',
          component: () => import('@/views/admin/CommissionSettingsPage.vue'),
          meta: { requiresAuth: true, guard: 'admin' },
        },
      ],
    },

    // ── TEACHER PORTAL ─────────────────────────────────────
    {
      path: '/teacher',
      component: TeacherLayout,
      meta: { requiresAuth: true, guard: 'admin', role: 'teacher' },
      children: [
        { path: '', redirect: '/teacher/dashboard' },
        {
          path: 'dashboard',
          name: 'teacher.dashboard',
          component: () => import('@/views/teacher/TeacherDashboardPage.vue'),
        },
        {
          path: 'courses',
          name: 'teacher.courses',
          component: () => import('@/views/teacher/TeacherCoursesPage.vue'),
        },
        {
          path: 'courses/create',
          name: 'teacher.courses.create',
          component: () => import('@/views/teacher/TeacherCourseFormPage.vue'),
        },
        {
          path: 'courses/:id/edit',
          name: 'teacher.courses.edit',
          component: () => import('@/views/teacher/TeacherCourseFormPage.vue'),
        },
        {
          path: 'earnings',
          name: 'teacher.earnings',
          component: () => import('@/views/teacher/EarningsPage.vue'),
        },
        {
          path: 'profile',
          name: 'teacher.profile',
          component: () => import('@/views/teacher/TeacherProfilePage.vue'),
        },
        {
          path: 'posts',
          name: 'teacher.posts',
          component: () => import('@/views/teacher/TeacherPostsPage.vue'),
        },
        {
          path: 'posts/create',
          name: 'teacher.posts.create',
          component: () => import('@/views/teacher/TeacherPostFormPage.vue'),
        },
        {
          path: 'posts/:id/edit',
          name: 'teacher.posts.edit',
          component: () => import('@/views/teacher/TeacherPostFormPage.vue'),
        },
      ],
    },

    // ── CLIENT ─────────────────────────────────────────────
    {
      path: '/',
      component: ClientLayout,
      children: [
        { path: '', component: () => import('@/views/client/HomePage.vue') },
        { path: 'courses', component: () => import('@/views/client/CoursesPage.vue') },
        { path: 'courses/:slug', component: () => import('@/views/client/CourseDetailPage.vue') },
        { path: 'teachers', component: () => import('@/views/client/TeachersPage.vue') },
        {
          path: 'teachers/:slug',
          component: () => import('@/views/client/TeacherProfilePage.vue'),
        },
        { path: 'posts', component: () => import('@/views/client/BlogPage.vue') },
        { path: 'posts/:slug', component: () => import('@/views/client/PostDetailPage.vue') },
        // Cần auth
        {
          path: 'my-courses',
          component: () => import('@/views/client/MyCoursesPage.vue'),
          meta: { requiresAuth: true, guard: 'student' },
        },
        {
          path: 'my-orders',
          component: () => import('@/views/client/MyOrdersPage.vue'),
          meta: { requiresAuth: true, guard: 'student' },
        },
        // LearnPage → đã chuyển ra ngoài ClientLayout (fullscreen)
        {
          path: 'cart',
          component: () => import('@/views/client/CartPage.vue'),
          meta: { requiresAuth: true, guard: 'student' },
        },
        {
          path: 'checkout',
          component: () => import('@/views/client/CheckoutPage.vue'),
          meta: { requiresAuth: true, guard: 'student' },
        },
        {
          path: 'payment/result',
          component: () => import('@/views/client/PaymentResultPage.vue'),
        },
        {
          path: 'profile',
          component: () => import('@/views/client/ProfilePage.vue'),
          meta: { requiresAuth: true, guard: 'student' },
        },
      ],
    },

    // ── LEARN PAGE (fullscreen, no layout) ───────────────────
    {
      path: '/courses/:slug/learn',
      component: () => import('@/views/client/LearnPage.vue'),
      meta: { requiresAuth: true, guard: 'student' },
    },

    // ── QUIZ PAGES (fullscreen, no layout) ───────────────────
    {
      path: '/lessons/:lessonId/quiz',
      name: 'quiz',
      component: () => import('@/views/client/QuizPage.vue'),
      meta: { requiresAuth: true, guard: 'student' },
    },
    {
      path: '/quizzes/:id/history',
      name: 'quiz-history',
      component: () => import('@/views/client/QuizHistoryPage.vue'),
      meta: { requiresAuth: true, guard: 'student' },
    },

    // ── AUTH CLIENT ────────────────────────────────────────
    {
      path: '/login',
      component: () => import('@/views/auth/LoginPage.vue'),
      meta: { requiresGuest: true, guard: 'student' },
    },
    {
      path: '/register',
      component: () => import('@/views/auth/RegisterPage.vue'),
      meta: { requiresGuest: true, guard: 'student' },
    },
    {
      path: '/verify-email',
      component: () => import('@/views/auth/VerifyEmailPage.vue'),
      meta: { requiresAuth: true, guard: 'student' },
    },
    {
      path: '/verify-email/result',
      component: () => import('@/views/auth/VerifyEmailResultPage.vue'),
    },
    {
      path: '/forgot-password',
      component: () => import('@/views/auth/ForgotPasswordPage.vue'),
      meta: { requiresGuest: true, guard: 'student' },
    },
    {
      path: '/reset-password',
      component: () => import('@/views/auth/ResetPasswordPage.vue'),
      meta: { requiresGuest: true, guard: 'student' },
    },

    // ── ERROR PAGES ────────────────────────────────────────
    { path: '/403', component: () => import('@/views/ForbiddenPage.vue') },
    { path: '/:pathMatch(.*)*', component: () => import('@/views/NotFoundPage.vue') },
  ],
})

function getToken(key) {
  return localStorage.getItem(key) || sessionStorage.getItem(key)
}

// ── Navigation Guard ───────────────────────────────────────
router.beforeEach(async (to) => {
  NProgress.start()
  const adminToken = getToken('adminToken')
  const studentToken = getToken('studentToken')

  let adminStore = null
  let studentStore = null

  // Global Initialization cho Student (để lấy email_verified_at)
  if (studentToken && to.meta.guard !== 'admin') {
    const { useStudentAuthStore } = await import('@/stores/studentAuth.store')
    studentStore = useStudentAuthStore()
    if (!studentStore.student) {
      await studentStore.fetchMe()
    }

    // Email verification guard — chặn mọi trang nếu chưa verify (kể cả trang chủ)
    const unverified = studentStore.student && !studentStore.student.email_verified_at
    const allowedWhenUnverified = [
      '/verify-email',
      '/verify-email/result',
      '/login',
      '/register',
      '/forgot-password',
      '/reset-password',
    ]
    if (unverified && !allowedWhenUnverified.includes(to.path)) {
      return '/verify-email'
    }
  }

  // Global Initialization cho Admin
  if (adminToken && to.meta.guard === 'admin') {
    const { useAdminAuthStore } = await import('@/stores/adminAuth.store')
    adminStore = useAdminAuthStore()
    if (!adminStore.user) {
      await adminStore.fetchMe()
    }
  }

  // Route cần auth — dùng store state sau fetchMe(), tránh stale raw token
  if (to.meta.requiresAuth) {
    if (to.meta.guard === 'admin') {
      const loggedIn = adminStore ? adminStore.isLoggedIn : !!adminToken
      if (!loggedIn) return '/admin/login'
    }
    if (to.meta.guard === 'student') {
      const loggedIn = studentStore ? studentStore.isLoggedIn : !!studentToken
      if (!loggedIn) return { path: '/login', query: { redirect: to.fullPath } }
    }
  }

  // Permission guard — chỉ áp dụng sau khi đã xác nhận đăng nhập
  if (adminStore && adminStore.isLoggedIn && to.meta.permission) {
    if (!adminStore.hasPermission(to.meta.permission)) {
      return '/403'
    }
  }

  // Role guard — required role on top of auth
  if (adminStore && adminStore.isLoggedIn && to.meta.role) {
    const userRoles = adminStore.user?.roles || []
    if (!userRoles.includes(to.meta.role)) {
      return '/403'
    }
  }

  // Route chỉ dành cho guest (login, register, forgot-password...)
  if (to.meta.requiresGuest) {
    const isAdminLoggedIn = adminStore ? adminStore.isLoggedIn : !!adminToken
    const isStudentLoggedIn = studentStore ? studentStore.isLoggedIn : !!studentToken

    if (to.meta.guard === 'admin' && isAdminLoggedIn) {
      const userRoles = adminStore?.user?.roles || []
      return userRoles.includes('teacher') ? '/teacher/dashboard' : '/admin/dashboard'
    }

    if (to.meta.guard === 'student') {
      if (isStudentLoggedIn) return '/'
    }
  }

  return true
})

router.afterEach(() => {
  NProgress.done()
})

export default router
