<template>
  <div class="min-h-screen flex flex-col bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm relative">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Left Side: Menu toggle & Logo -->
        <div class="flex items-center gap-3">
          <button
            @click="toggleMobileMenu"
            class="md:hidden p-1 -ml-1 text-gray-600 hover:text-primary-600 focus:outline-none"
          >
            <Menu v-if="!isMobileMenuOpen" class="w-6 h-6" />
            <X v-else class="w-6 h-6" />
          </button>

          <!-- Logo -->
          <router-link to="/" class="flex items-center gap-1.5">
            <img src="/images/logo/logo.svg" alt="EduLearn" height="32" class="h-8 w-auto" />
          </router-link>
        </div>

        <!-- Nav links (Desktop) -->
        <div
          class="hidden md:flex justify-center flex-1 mx-4 items-center gap-8 text-sm font-medium text-gray-600"
        >
          <router-link
            to="/courses"
            class="hover:text-primary-600 transition-colors"
            active-class="text-primary-600"
            >Khóa học</router-link
          >
          <router-link
            to="/teachers"
            class="hover:text-primary-600 transition-colors"
            active-class="text-primary-600"
            >Giảng viên</router-link
          >
          <router-link
            to="/posts"
            class="hover:text-primary-600 transition-colors"
            active-class="text-primary-600"
            >Tin tức</router-link
          >
        </div>

        <!-- Right side -->
        <div class="flex items-center gap-3 sm:gap-5">
          <!-- Cart -->
          <router-link
            v-if="studentStore.isLoggedIn"
            to="/cart"
            class="relative p-2 text-gray-600 hover:text-primary-600 transition-colors"
          >
            <ShoppingCart class="w-6 h-6" />
            <span
              v-if="cartStore.count > 0"
              class="absolute top-0 right-0 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white"
            >
              {{ cartStore.count }}
            </span>
          </router-link>

          <!-- Guest Actions -->
          <template v-if="!studentStore.isLoggedIn">
            <div class="hidden sm:flex items-center gap-3">
              <router-link to="/login" class="btn-secondary h-10 flex items-center leading-none"
                >Đăng nhập</router-link
              >
              <router-link to="/register" class="btn-primary h-10 flex items-center leading-none"
                >Đăng ký</router-link
              >
            </div>
          </template>

          <!-- User Dropdown Menu -->
          <template v-else>
            <div class="relative group">
              <button class="flex items-center gap-2 focus:outline-none block p-0">
                <img
                  v-if="studentStore.student?.avatar"
                  :src="studentStore.student.avatar"
                  :alt="studentStore.fullName"
                  class="w-9 h-9 rounded-full object-cover border border-primary-200"
                />
                <div
                  v-else
                  class="w-9 h-9 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-sm border border-primary-200"
                >
                  {{ studentStore.fullName?.charAt(0).toUpperCase() || 'U' }}
                </div>
                <ChevronDown class="w-4 h-4 text-gray-500 hidden sm:block" />
              </button>

              <!-- Dropdown content -->
              <div
                class="absolute right-0 top-full mt-2 w-56 bg-white border border-gray-100 rounded-xl shadow-lg py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50"
              >
                <div class="px-4 py-3 border-b border-gray-100 mb-1">
                  <p class="text-sm font-semibold text-gray-800 truncate">
                    {{ studentStore.fullName || 'Người dùng' }}
                  </p>
                  <p class="text-xs text-gray-500 truncate">
                    {{ studentStore.student?.email || 'email@example.com' }}
                  </p>
                </div>

                <router-link
                  to="/my-courses"
                  class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition-colors"
                  >Khóa học của tôi</router-link
                >
                <router-link
                  to="/profile"
                  class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition-colors"
                  >Tài khoản cá nhân</router-link
                >
                <router-link
                  to="/my-orders"
                  class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition-colors"
                  >Lịch sử đơn hàng</router-link
                >

                <div class="border-t border-gray-100 mt-1 pt-1">
                  <button
                    @click="handleLogout"
                    class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                  >
                    Đăng xuất
                  </button>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Mobile Menu Dropdown Panel -->
      <div
        v-show="isMobileMenuOpen"
        class="md:hidden absolute top-16 left-0 right-0 bg-white border-b border-gray-200 shadow-xl z-30 px-4 py-4 space-y-3"
      >
        <router-link
          @click="isMobileMenuOpen = false"
          to="/courses"
          class="block text-base font-medium text-gray-800 hover:text-primary-600 transition-colors"
          >Khóa học</router-link
        >
        <router-link
          @click="isMobileMenuOpen = false"
          to="/teachers"
          class="block text-base font-medium text-gray-800 hover:text-primary-600 transition-colors"
          >Giảng viên</router-link
        >
        <router-link
          @click="isMobileMenuOpen = false"
          to="/posts"
          class="block text-base font-medium text-gray-800 hover:text-primary-600 transition-colors"
          >Tin tức</router-link
        >

        <template v-if="!studentStore.isLoggedIn">
          <hr class="border-gray-100 my-3" />
          <div class="sm:hidden flex flex-col gap-2">
            <router-link
              @click="isMobileMenuOpen = false"
              to="/login"
              class="btn-secondary w-full justify-center h-10 flex items-center leading-none"
              >Đăng nhập</router-link
            >
            <router-link
              @click="isMobileMenuOpen = false"
              to="/register"
              class="btn-primary w-full justify-center h-10 flex items-center leading-none"
              >Đăng ký</router-link
            >
          </div>
        </template>
      </div>
    </nav>

    <!-- Page content -->
    <main class="flex-1 w-full mx-auto">
      <router-view />
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 pb-8">
        <!-- Main grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
          <!-- Brand column -->
          <div class="lg:col-span-1">
            <router-link to="/" class="inline-flex items-center gap-1.5 mb-4">
              <img src="/images/logo/logo-dark.svg" alt="EduLearn" class="h-8 w-auto" />
            </router-link>
            <p class="text-sm text-gray-400 leading-relaxed">
              Nền tảng học trực tuyến hàng đầu — nơi bạn tiếp cận hàng trăm khoá học chất lượng cao
              từ những giảng viên uy tín.
            </p>
            <!-- Social icons -->
            <div class="flex gap-3 mt-5">
              <a
                href="#"
                aria-label="Facebook"
                class="w-9 h-9 rounded-lg bg-gray-800 hover:bg-primary-600 flex items-center justify-center transition-colors"
              >
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                  <path
                    d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                  />
                </svg>
              </a>
              <a
                href="#"
                aria-label="YouTube"
                class="w-9 h-9 rounded-lg bg-gray-800 hover:bg-red-600 flex items-center justify-center transition-colors"
              >
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                  <path
                    d="M23.495 6.205a3.007 3.007 0 0 0-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 0 0 .527 6.205a31.247 31.247 0 0 0-.522 5.805 31.247 31.247 0 0 0 .522 5.783 3.007 3.007 0 0 0 2.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 0 0 2.088-2.088 31.247 31.247 0 0 0 .5-5.783 31.247 31.247 0 0 0-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"
                  />
                </svg>
              </a>
              <a
                href="#"
                aria-label="TikTok"
                class="w-9 h-9 rounded-lg bg-gray-800 hover:bg-gray-600 flex items-center justify-center transition-colors"
              >
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                  <path
                    d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"
                  />
                </svg>
              </a>
            </div>
          </div>

          <!-- Khám phá -->
          <div>
            <h3 class="text-white font-semibold text-sm uppercase tracking-wider mb-4">Khám phá</h3>
            <ul class="space-y-2.5 text-sm">
              <li>
                <router-link to="/courses" class="hover:text-white transition-colors"
                  >Tất cả khoá học</router-link
                >
              </li>
              <li>
                <router-link to="/teachers" class="hover:text-white transition-colors"
                  >Giảng viên</router-link
                >
              </li>
              <li>
                <router-link to="/posts" class="hover:text-white transition-colors"
                  >Blog & Tin tức</router-link
                >
              </li>
              <li>
                <router-link to="/courses?level=beginner" class="hover:text-white transition-colors"
                  >Khoá học cho người mới</router-link
                >
              </li>
              <li>
                <router-link to="/courses?level=advanced" class="hover:text-white transition-colors"
                  >Khoá học nâng cao</router-link
                >
              </li>
            </ul>
          </div>

          <!-- Tài khoản -->
          <div>
            <h3 class="text-white font-semibold text-sm uppercase tracking-wider mb-4">
              Tài khoản
            </h3>
            <ul class="space-y-2.5 text-sm">
              <li>
                <router-link to="/login" class="hover:text-white transition-colors"
                  >Đăng nhập</router-link
                >
              </li>
              <li>
                <router-link to="/register" class="hover:text-white transition-colors"
                  >Đăng ký</router-link
                >
              </li>
              <li>
                <router-link to="/my-courses" class="hover:text-white transition-colors"
                  >Khoá học của tôi</router-link
                >
              </li>
              <li>
                <router-link to="/profile" class="hover:text-white transition-colors"
                  >Tài khoản cá nhân</router-link
                >
              </li>
              <li>
                <router-link to="/my-orders" class="hover:text-white transition-colors"
                  >Lịch sử đơn hàng</router-link
                >
              </li>
            </ul>
          </div>

          <!-- Liên hệ -->
          <div>
            <h3 class="text-white font-semibold text-sm uppercase tracking-wider mb-4">Liên hệ</h3>
            <ul class="space-y-3 text-sm">
              <li class="flex items-start gap-2.5">
                <MapPin class="w-4 h-4 mt-0.5 text-primary-400 shrink-0" />
                <span>227 Nguyễn Văn Cừ, Quận 5, TP. Hồ Chí Minh</span>
              </li>
              <li class="flex items-center gap-2.5">
                <Mail class="w-4 h-4 text-primary-400 shrink-0" />
                <a href="mailto:support@edulearn.vn" class="hover:text-white transition-colors"
                  >support@edulearn.vn</a
                >
              </li>
              <li class="flex items-center gap-2.5">
                <Phone class="w-4 h-4 text-primary-400 shrink-0" />
                <a href="tel:+84901234567" class="hover:text-white transition-colors"
                  >+84 901 234 567</a
                >
              </li>
            </ul>
          </div>
        </div>

        <!-- Divider -->
        <div
          class="border-t border-gray-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500"
        >
          <p>
            &copy; 2026 EduLearn Marketplace. Thực hiện bởi
            <span class="text-gray-400 font-medium">Phan Văn Thành</span>.
          </p>
          <div class="flex gap-5">
            <a href="#" class="hover:text-gray-300 transition-colors">Chính sách bảo mật</a>
            <a href="#" class="hover:text-gray-300 transition-colors">Điều khoản sử dụng</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script lang="ts">
import { ref } from 'vue'
import { useStudentAuthStore } from '@/stores/studentAuth.store'
import { useCartStore } from '@/stores/cart.store'
import { useRouter } from 'vue-router'
import { ShoppingCart, ChevronDown, Menu, X, MapPin, Mail, Phone } from 'lucide-vue-next'
import { useToast } from 'vue-toastification'

export default {
  components: { ShoppingCart, ChevronDown, Menu, X, MapPin, Mail, Phone },
  setup() {
    const studentStore = useStudentAuthStore()
    const cartStore = useCartStore()
    const router = useRouter()
    const toast = useToast()
    const isMobileMenuOpen = ref(false)

    const handleLogout = async () => {
      await studentStore.logout()
      toast.info('Đã đăng xuất khỏi tài khoản')
      router.push('/')
      isMobileMenuOpen.value = false
    }

    const toggleMobileMenu = () => {
      isMobileMenuOpen.value = !isMobileMenuOpen.value
    }

    return {
      studentStore,
      cartStore,
      handleLogout,
      isMobileMenuOpen,
      toggleMobileMenu,
    }
  },
}
</script>
