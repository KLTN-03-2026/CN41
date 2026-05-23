<script setup lang="ts">
import { computed, ref } from 'vue'
import type { AppNotification } from '@/composables/useNotifications'

const props = defineProps<{
  notifications: AppNotification[]
  unreadCount: number
}>()

const emit = defineEmits<{
  markRead: [id: number]
  markAllRead: []
}>()

const open = ref(false)

const sortedNotifications = computed(() =>
  [...props.notifications].sort(
    (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
  ),
)

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
  const d = new Date(iso)
  const now = new Date()
  const diff = (now.getTime() - d.getTime()) / 1000

  if (diff < 60) return 'vừa xong'
  if (diff < 3600) return `${Math.floor(diff / 60)} phút trước`
  if (diff < 86400) return `${Math.floor(diff / 3600)} giờ trước`
  return `${Math.floor(diff / 86400)} ngày trước`
}

function handleClickOutside() {
  open.value = false
}

function handleMarkRead(id: number) {
  emit('markRead', id)
}
</script>

<template>
  <div class="relative">
    <!-- Bell button -->
    <button
      class="relative p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition-colors"
      @click="open = !open"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
        />
      </svg>
      <!-- Unread badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold leading-none"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Overlay to close on click outside -->
    <div v-if="open" class="fixed inset-0 z-40" @click="handleClickOutside" />

    <!-- Dropdown panel -->
    <div
      v-if="open"
      class="absolute right-0 mt-2 w-80 z-50 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden"
    >
      <!-- Header -->
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
        <span class="text-sm font-semibold text-gray-800 dark:text-white">
          Thông báo
          <span v-if="unreadCount > 0" class="ml-1 text-xs text-red-500">({{ unreadCount }} chưa đọc)</span>
        </span>
        <button
          v-if="unreadCount > 0"
          class="text-xs text-blue-500 hover:text-blue-700 dark:hover:text-blue-400 transition-colors"
          @click="$emit('markAllRead')"
        >
          Đọc tất cả
        </button>
      </div>

      <!-- List -->
      <ul class="max-h-96 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-700">
        <li v-if="sortedNotifications.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
          Không có thông báo
        </li>
        <li
          v-for="n in sortedNotifications"
          :key="n.id"
          class="flex gap-3 px-4 py-3 cursor-pointer transition-colors"
          :class="n.read_at ? 'bg-white dark:bg-gray-800' : 'bg-blue-50 dark:bg-blue-900/20'"
          @click="handleMarkRead(n.id)"
        >
          <span class="flex-shrink-0 text-xl leading-none mt-0.5">{{ getIcon(n.type) }}</span>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 leading-snug">{{ n.title }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-snug line-clamp-2">{{ n.body }}</p>
            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">{{ formatTime(n.created_at) }}</p>
          </div>
          <!-- Unread dot -->
          <div v-if="!n.read_at" class="flex-shrink-0 mt-1.5">
            <span class="block w-2 h-2 rounded-full bg-blue-500" />
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
