<template>
  <div>
    <!-- Tabs -->
    <div class="flex items-center gap-1 mb-4 p-1 bg-gray-100 dark:bg-white/5 rounded-xl w-fit">
      <button
        @click="$emit('switch-tab', false)"
        :class="!isTrashed
          ? 'bg-white dark:bg-gray-800 text-gray-800 dark:text-white shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
        </svg>
        Đang hoạt động
      </button>
      <button
        @click="$emit('switch-tab', true)"
        :class="isTrashed
          ? 'bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
      >
        <TrashIcon class="w-4 h-4" />
        Thùng rác
        <span
          v-if="trashedCount > 0"
          class="px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400"
        >
          {{ trashedCount }}
        </span>
      </button>
    </div>

    <!-- Search (active) -->
    <div v-if="!isTrashed" class="mb-4">
      <div class="relative max-w-sm">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input
          :value="searchQuery"
          @input="onSearchInput"
          type="text"
          placeholder="Tìm kiếm danh mục..."
          class="w-full h-10 pl-10 pr-8 rounded-lg border border-gray-200 bg-white text-sm text-gray-800 dark:border-gray-700 dark:bg-white/5 dark:text-white/90 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-colors"
        />
        <button
          v-if="searchQuery"
          @click="$emit('clear-search')"
          class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Search (trashed) -->
    <div v-if="isTrashed" class="mb-4">
      <div class="relative max-w-sm">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input
          :value="trashedSearchQuery"
          @input="onTrashedSearchInput"
          type="text"
          placeholder="Tìm trong thùng rác..."
          class="w-full h-10 pl-10 pr-8 rounded-lg border border-gray-200 bg-white text-sm text-gray-800 dark:border-gray-700 dark:bg-white/5 dark:text-white/90 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-400 transition-colors"
        />
        <button
          v-if="trashedSearchQuery"
          @click="$emit('clear-trashed-search')"
          class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { TrashIcon } from '@/components/icons'

defineProps<{
  isTrashed: boolean
  trashedCount: number
  searchQuery: string
  trashedSearchQuery: string
}>()

const emit = defineEmits<{
  'switch-tab': [trashed: boolean]
  'update:searchQuery': [value: string]
  'clear-search': []
  'trashedSearchInput': [value: string]
  'clear-trashed-search': []
}>()

function onSearchInput(e: Event) {
  emit('update:searchQuery', (e.target as HTMLInputElement).value)
}

function onTrashedSearchInput(e: Event) {
  emit('trashedSearchInput', (e.target as HTMLInputElement).value)
}
</script>
