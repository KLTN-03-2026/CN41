<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 px-4">
    <div class="text-center max-w-md">
      <div class="mb-6 flex justify-center">
        <div class="w-20 h-20 rounded-full bg-red-100 dark:bg-red-500/10 flex items-center justify-center">
          <svg class="w-10 h-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>
      </div>
      <h1 class="text-3xl font-bold text-gray-800 dark:text-white/90 mb-2">403</h1>
      <h2 class="text-lg font-semibold text-gray-700 dark:text-white/80 mb-3">Không có quyền truy cập</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">
        Tài khoản của bạn không có quyền truy cập trang này. Vui lòng liên hệ quản trị viên nếu bạn cho rằng đây là lỗi.
      </p>
      <div class="flex items-center justify-center gap-3">
        <button
          @click="goBack"
          class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 transition-colors"
        >
          Quay lại
        </button>
        <router-link
          to="/admin/dashboard"
          class="px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors"
        >
          Trang chủ
        </router-link>
        <button
          @click="logout"
          class="px-4 py-2 text-sm rounded-lg bg-red-500 text-white hover:bg-red-600 transition-colors"
        >
          Đăng xuất
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAdminAuthStore } from '@/stores/adminAuth.store'

const router = useRouter()
const adminStore = useAdminAuthStore()

function goBack() {
  router.back()
}

async function logout() {
  await adminStore.logout()
  router.push('/admin/login')
}
</script>
