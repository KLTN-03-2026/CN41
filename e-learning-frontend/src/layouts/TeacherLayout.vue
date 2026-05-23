<template>
  <div class="min-h-screen flex bg-gray-50 dark:bg-gray-950">
    <!-- Sidebar -->
    <aside
      class="w-[220px] min-h-screen bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 flex flex-col fixed top-0 left-0 z-50"
    >
      <!-- Logo -->
      <div class="p-5 border-b border-gray-200 dark:border-gray-700">
        <router-link to="/teacher/dashboard">
          <img src="/images/logo/logo.svg" alt="EduLearn" class="dark:hidden" width="120" />
          <img
            src="/images/logo/logo-dark.svg"
            alt="EduLearn"
            class="hidden dark:block"
            width="120"
          />
        </router-link>
        <p class="mt-2 text-xs text-blue-600 dark:text-blue-400 font-medium">Cổng giảng viên</p>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-4 overflow-y-auto">
        <p class="text-xs text-gray-400 uppercase mb-3 px-2 font-semibold tracking-wider">Menu</p>
        <ul class="space-y-1">
          <li v-for="item in menuItems" :key="item.path">
            <router-link
              :to="item.path"
              :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                isActive(item.path)
                  ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400'
                  : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800',
              ]"
            >
              <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
              <span>{{ item.name }}</span>
            </router-link>
          </li>
        </ul>
      </nav>

      <!-- User info + logout -->
      <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-3 px-1">
          <div
            class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-700 dark:text-blue-300 text-sm font-bold flex-shrink-0"
          >
            {{ userInitial }}
          </div>
          <span
            class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate"
            :title="userName"
          >
            {{ userName }}
          </span>
        </div>
        <button
          @click="handleLogout"
          class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
        >
          <LogoutIcon class="w-4 h-4" />
          Đăng xuất
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 ml-[220px]">
      <!-- Top header -->
      <header class="sticky top-0 z-40 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-6 h-14 flex items-center justify-end gap-3">
        <NotificationMenu />
      </header>

      <div class="p-6 max-w-screen-xl mx-auto">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminAuthStore } from '@/stores/adminAuth.store'
import {
  GridIcon,
  BoxCubeIcon,
  BarChartIcon,
  UserCircleIcon,
  LogoutIcon,
  PageIcon,
} from '@/components/icons'
import NotificationMenu from '@/components/layout/header/NotificationMenu.vue'

const route = useRoute()
const router = useRouter()
const adminStore = useAdminAuthStore()

const menuItems = [
  { name: 'Tổng quan', path: '/teacher/dashboard', icon: GridIcon },
  { name: 'Khóa học của tôi', path: '/teacher/courses', icon: BoxCubeIcon },
  { name: 'Bài viết', path: '/teacher/posts', icon: PageIcon },
  { name: 'Thu nhập', path: '/teacher/earnings', icon: BarChartIcon },
  { name: 'Hồ sơ cá nhân', path: '/teacher/profile', icon: UserCircleIcon },
]

const isActive = (path: string) => route.path === path || route.path.startsWith(path + '/')
const userName = computed(() => adminStore.user?.name || 'Giảng viên')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())

async function handleLogout() {
  await adminStore.logout()
  router.push('/admin/login')
}
</script>
