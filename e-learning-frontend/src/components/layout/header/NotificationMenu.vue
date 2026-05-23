<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useAdminAuthStore } from '@/stores/adminAuth.store'
import { useNotifications } from '@/composables/useNotifications'

const authStore = useAdminAuthStore()

const isTeacher = computed(() => authStore.user?.roles?.includes('teacher') ?? false)

const { notifications, unreadCount, loading, fetchNotifications, markRead, markAllRead, connectEcho } =
  useNotifications(isTeacher.value ? 'teacher' : 'admin')

const ICONS: Record<string, string> = {
  enrollment: '🎓',
  payout_request: '💰',
  payout_decision: '✅',
  course_pending: '📚',
  new_comment: '💬',
}

function getIcon(type: string): string {
  return ICONS[type] ?? '🔔'
}

function formatTime(iso: string): string {
  const diff = (Date.now() - new Date(iso).getTime()) / 1000
  if (diff < 60) return 'vừa xong'
  if (diff < 3600) return `${Math.floor(diff / 60)} phút trước`
  if (diff < 86400) return `${Math.floor(diff / 3600)} giờ trước`
  return `${Math.floor(diff / 86400)} ngày trước`
}

const open = ref(false)

onMounted(async () => {
  await fetchNotifications()

  const token = authStore.token
  if (!token) return

  const recipientId = isTeacher.value ? authStore.user?.teacher_id : authStore.user?.id
  if (recipientId) {
    connectEcho(token, recipientId)
  }
})
</script>

<template>
  <div class="relative">
    <!-- Bell button -->
    <button
      class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-gray-900 h-11 w-11 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
      @click="open = !open"
    >
      <!-- Unread ping -->
      <span v-if="unreadCount > 0" class="absolute top-0.5 right-0.5 z-10 flex h-2 w-2">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75" />
        <span class="relative inline-flex rounded-full h-2 w-2 bg-orange-500" />
      </span>
      <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z" fill="" />
      </svg>
      <!-- Numeric badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-white text-[9px] font-bold"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Overlay -->
    <div v-if="open" class="fixed inset-0 z-40" @click="open = false" />

    <!-- Dropdown -->
    <div
      v-if="open"
      class="absolute -right-[240px] mt-4 flex h-auto max-h-[480px] w-[350px] flex-col rounded-2xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800 sm:w-[361px] lg:right-0 z-50 overflow-hidden"
    >
      <!-- Header -->
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex-shrink-0">
        <h5 class="text-base font-semibold text-gray-800 dark:text-white">
          Thông báo
          <span v-if="unreadCount > 0" class="ml-1 text-xs text-red-500">({{ unreadCount }})</span>
        </h5>
        <button
          v-if="unreadCount > 0"
          class="text-xs text-blue-500 hover:text-blue-700 dark:hover:text-blue-400 transition-colors"
          @click="markAllRead"
        >
          Đọc tất cả
        </button>
      </div>

      <!-- Spinner -->
      <div v-if="loading" class="flex justify-center py-8">
        <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <!-- List -->
      <ul v-else class="flex-1 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-700">
        <li v-if="notifications.length === 0" class="px-4 py-10 text-center text-sm text-gray-400">
          Không có thông báo
        </li>
        <li
          v-for="n in notifications"
          :key="n.id"
          class="flex gap-3 px-4 py-3 cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
          :class="n.read_at ? '' : 'bg-blue-50 dark:bg-blue-900/20'"
          @click="markRead(n.id)"
        >
          <span class="flex-shrink-0 text-xl leading-none mt-0.5">{{ getIcon(n.type) }}</span>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 leading-snug">{{ n.title }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ n.body }}</p>
            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">{{ formatTime(n.created_at) }}</p>
          </div>
          <div v-if="!n.read_at" class="flex-shrink-0 mt-2">
            <span class="block w-2 h-2 rounded-full bg-blue-500" />
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
