<script setup lang="ts">
import { onMounted } from 'vue'
import { useFeatureFlags } from '@/composables/useFeatureFlags'

const { flags, loading, toggling, loadFlags, toggleFlag } = useFeatureFlags()

onMounted(loadFlags)
</script>

<template>
  <div class="p-6 max-w-2xl">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Quản lý tính năng hệ thống</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Bật/tắt các tính năng toàn hệ thống. Thay đổi có hiệu lực ngay lập tức.
      </p>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-3">
      <div
        v-for="i in 3"
        :key="i"
        class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 animate-pulse"
      >
        <div class="flex items-center justify-between">
          <div class="space-y-2">
            <div class="h-4 w-40 bg-gray-200 dark:bg-gray-700 rounded" />
            <div class="h-3 w-64 bg-gray-100 dark:bg-gray-800 rounded" />
          </div>
          <div class="h-6 w-11 bg-gray-200 dark:bg-gray-700 rounded-full" />
        </div>
      </div>
    </div>

    <!-- Flag cards -->
    <div v-else class="space-y-3">
      <div
        v-for="flag in flags"
        :key="flag.key"
        class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex items-center justify-between gap-4"
      >
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-0.5">
            <span class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ flag.label }}</span>
            <span
              :class="flag.active
                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'"
              class="text-xs px-2 py-0.5 rounded-full font-medium"
            >
              {{ flag.active ? 'Đang hoạt động' : 'Đã tắt' }}
            </span>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ flag.description }}</p>
        </div>

        <!-- Toggle switch -->
        <button
          type="button"
          :disabled="toggling === flag.key"
          @click="toggleFlag(flag.key, !flag.active)"
          :class="flag.active ? 'bg-blue-500' : 'bg-gray-200 dark:bg-gray-700'"
          class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
          :title="flag.active ? 'Nhấn để tắt' : 'Nhấn để bật'"
        >
          <span
            :class="flag.active ? 'translate-x-5' : 'translate-x-0'"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200"
          />
        </button>
      </div>
    </div>
  </div>
</template>
