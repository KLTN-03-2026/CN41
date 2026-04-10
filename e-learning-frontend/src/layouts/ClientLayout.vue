<template>
  <div class="min-h-screen flex flex-col bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm relative">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        
        <!-- Left Side: Menu toggle & Logo -->
        <div class="flex items-center gap-3">
          <button @click="toggleMobileMenu" class="md:hidden p-1 -ml-1 text-gray-600 hover:text-primary-600 focus:outline-none">
            <Menu v-if="!isMobileMenuOpen" class="w-6 h-6" />
            <X v-else class="w-6 h-6" />
          </button>

          <!-- Logo -->
          <router-link to="/" class="flex items-center gap-2 font-bold text-xl text-gray-800">
            <BookOpen class="w-7 h-7 text-primary-600" />
            <span class="hidden sm:inline">E-Learning</span>
          </router-link>
        </div>

        <!-- Nav links (Desktop) -->
        <div class="hidden md:flex justify-center flex-1 mx-4 items-center gap-8 text-sm font-medium text-gray-600">
          <router-link to="/courses" class="hover:text-primary-600 transition-colors" active-class="text-primary-600">Khóa học</router-link>
          <router-link to="/posts" class="hover:text-primary-600 transition-colors" active-class="text-primary-600">Tin tức</router-link>
        </div>

        <!-- Right side -->
        <div class="flex items-center gap-3 sm:gap-5">
          <!-- Cart -->
          <router-link v-if="studentStore.isLoggedIn" to="/cart" class="relative p-2 text-gray-600 hover:text-primary-600 transition-colors">
            <ShoppingCart class="w-6 h-6" />
            <span v-if="cartStore.count > 0" class="absolute top-0 right-0 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white">
              {{ cartStore.count }}
            </span>
          </router-link>

          <!-- Guest Actions -->
          <template v-if="!studentStore.isLoggedIn">
            <div class="hidden sm:flex items-center gap-3">
              <router-link to="/login" class="btn-secondary h-10 flex items-center leading-none">Đăng nhập</router-link>
              <router-link to="/register" class="btn-primary h-10 flex items-center leading-none">Đăng ký</router-link>
            </div>
          </template>

          <!-- User Dropdown Menu -->
          <template v-else>
            <div class="relative group">
              <button class="flex items-center gap-2 focus:outline-none block p-0">
                <div class="w-9 h-9 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-sm border border-primary-200">
                  {{ studentStore.fullName?.charAt(0).toUpperCase() || 'U' }}
                </div>
                <ChevronDown class="w-4 h-4 text-gray-500 hidden sm:block" />
              </button>
              
              <!-- Dropdown content -->
              <div class="absolute right-0 top-full mt-2 w-56 bg-white border border-gray-100 rounded-xl shadow-lg py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                <div class="px-4 py-3 border-b border-gray-100 mb-1">
                  <p class="text-sm font-semibold text-gray-800 truncate">{{ studentStore.fullName || 'Người dùng' }}</p>
                  <p class="text-xs text-gray-500 truncate">{{ studentStore.student?.email || 'email@example.com' }}</p>
                </div>
                
                <router-link to="/my-courses" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition-colors">Khóa học của tôi</router-link>
                <router-link to="/profile" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition-colors">Tài khoản cá nhân</router-link>
                <router-link to="/orders" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition-colors">Lịch sử đơn hàng</router-link>
                
                <div class="border-t border-gray-100 mt-1 pt-1">
                  <button @click="handleLogout" class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">Đăng xuất</button>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Mobile Menu Dropdown Panel -->
      <div v-show="isMobileMenuOpen" class="md:hidden absolute top-16 left-0 right-0 bg-white border-b border-gray-200 shadow-xl z-30 px-4 py-4 space-y-3">
        <router-link @click="isMobileMenuOpen = false" to="/courses" class="block text-base font-medium text-gray-800 hover:text-primary-600 transition-colors">Khóa học</router-link>
        <router-link @click="isMobileMenuOpen = false" to="/posts" class="block text-base font-medium text-gray-800 hover:text-primary-600 transition-colors">Tin tức</router-link>
        
        <template v-if="!studentStore.isLoggedIn">
          <hr class="border-gray-100 my-3" />
          <div class="sm:hidden flex flex-col gap-2">
            <router-link @click="isMobileMenuOpen = false" to="/login" class="btn-secondary w-full justify-center h-10 flex items-center leading-none">Đăng nhập</router-link>
            <router-link @click="isMobileMenuOpen = false" to="/register" class="btn-primary w-full justify-center h-10 flex items-center leading-none">Đăng ký</router-link>
          </div>
        </template>
      </div>
    </nav>

    <!-- Page content -->
    <main class="flex-1 w-full mx-auto">
      <router-view />
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex justify-center md:justify-start mb-4 md:mb-0">
            <span class="flex items-center gap-2 font-bold text-lg text-gray-800">
              <BookOpen class="w-6 h-6 text-primary-600" />
              E-Learning
            </span>
          </div>
          <p class="text-center text-sm text-gray-500">
            &copy; 2026 E-Learning Marketplace. Thực hiện bởi Phan Văn Thành.
          </p>
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
import { BookOpen, ShoppingCart, ChevronDown, Menu, X } from 'lucide-vue-next'
import { useToast } from 'vue-toastification'

export default {
  components: { BookOpen, ShoppingCart, ChevronDown, Menu, X },
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
      toggleMobileMenu
    }
  }
}
</script>
