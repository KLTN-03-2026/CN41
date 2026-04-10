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
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
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

    <!-- Active filters -->
    <div v-if="!isTrashed" class="flex flex-wrap gap-3 mb-4">
      <input
        :value="search"
        @input="onSearch"
        type="text"
        placeholder="Tìm kiếm khóa học..."
        class="input-field w-64"
      />
      <select
        :value="status"
        @change="onStatus"
        class="input-field w-40"
      >
        <option value="">Tất cả trạng thái</option>
        <option value="1">Đã đăng</option>
        <option value="0">Nháp</option>
      </select>
      <select
        :value="level"
        @change="onLevel"
        class="input-field w-40"
      >
        <option value="">Tất cả trình độ</option>
        <option value="beginner">Cơ bản</option>
        <option value="intermediate">Trung cấp</option>
        <option value="advanced">Nâng cao</option>
      </select>
    </div>

    <!-- Trashed search -->
    <div v-if="isTrashed" class="flex flex-wrap gap-3 mb-4">
      <input
        :value="trashedSearch"
        @input="onTrashedSearch"
        type="text"
        placeholder="Tìm trong thùng rác..."
        class="input-field w-64"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { TrashIcon } from '@/components/icons'

defineProps<{
  isTrashed: boolean
  trashedCount: number
  search: string
  status: string
  level: string
  trashedSearch: string
}>()

const emit = defineEmits<{
  'switch-tab': [trashed: boolean]
  'update:search': [value: string]
  'update:status': [value: string]
  'update:level': [value: string]
  'update:trashedSearch': [value: string]
  'search-input': []
  'filter-change': []
  'trashed-search-input': []
}>()

function getVal(e: Event) {
  return (e.target as HTMLInputElement).value
}

function onSearch(e: Event) {
  emit('update:search', getVal(e))
  emit('search-input')
}

function onStatus(e: Event) {
  emit('update:status', getVal(e))
  emit('filter-change')
}

function onLevel(e: Event) {
  emit('update:level', getVal(e))
  emit('filter-change')
}

function onTrashedSearch(e: Event) {
  emit('update:trashedSearch', getVal(e))
  emit('trashed-search-input')
}
</script>

<style scoped>
.input-field {
  @apply h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm text-gray-800
         dark:border-gray-700 dark:text-white/90 dark:bg-gray-900
         focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400;
}
</style>
