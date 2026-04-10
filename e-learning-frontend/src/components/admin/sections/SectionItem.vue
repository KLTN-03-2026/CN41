<template>
  <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/5 overflow-hidden">
    <!-- Section header -->
    <div
      class="flex items-center gap-3 px-5 py-3.5 cursor-pointer select-none hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
      @click="$emit('toggle-expand', section.id)"
    >
      <!-- Expand icon -->
      <svg
        class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0"
        :class="{ 'rotate-90': isExpanded }"
        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
      </svg>

      <!-- Checkbox chọn tất cả bài trong section -->
      <div @click.stop class="flex items-center justify-center mr-1">
        <input
          type="checkbox"
          :checked="isAllSelected"
          @change="$emit('select-all', section, $event)"
          class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
          title="Chọn tất cả bài giảng trong chương này"
        />
      </div>

      <!-- Section info -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="text-xs font-mono text-gray-400">{{ index + 1 }}.</span>
          <h4 class="font-medium text-gray-800 dark:text-gray-200 truncate">{{ section.title }}</h4>
          <span
            :class="section.status === 1
              ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
              : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400'"
            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium"
          >
            {{ section.status === 1 ? 'Đã đăng' : 'Nháp' }}
          </span>
        </div>
        <p class="text-xs text-gray-400 mt-0.5">{{ (section.lessons || []).length }} bài giảng</p>
      </div>

      <!-- Section actions -->
      <div class="flex items-center gap-1" @click.stop>
        <!-- Reorder up -->
        <button
          v-if="index > 0"
          @click="$emit('reorder', index, index - 1)"
          class="p-1 text-gray-400 hover:text-gray-600 rounded transition-colors"
          title="Di chuyển lên"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
          </svg>
        </button>
        <!-- Reorder down -->
        <button
          v-if="!isLast"
          @click="$emit('reorder', index, index + 1)"
          class="p-1 text-gray-400 hover:text-gray-600 rounded transition-colors"
          title="Di chuyển xuống"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>

        <!-- Add lesson -->
        <button
          @click="$emit('add-lesson', section.id)"
          class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10 transition-colors"
          title="Thêm bài giảng vào chương này"
        >
          <PlusIcon class="w-4 h-4" />
        </button>

        <!-- Toggle status -->
        <button
          @click="$emit('toggle-status', section)"
          :disabled="isToggling"
          class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg dark:hover:bg-green-500/10 transition-colors disabled:opacity-50"
          title="Toggle trạng thái"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </button>

        <!-- Edit -->
        <button
          @click="$emit('edit', section)"
          class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10 transition-colors"
          title="Sửa chương"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
        </button>

        <!-- Delete -->
        <button
          @click="$emit('delete', section)"
          class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors"
          title="Xóa chương"
        >
          <TrashIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Lessons slot (expandable) -->
    <div v-if="isExpanded" class="border-t border-gray-100 dark:border-gray-700">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { PlusIcon, TrashIcon } from '@/components/icons'
import type { AdminSection } from '@/types/section-lesson.types'

defineProps<{
  section: AdminSection
  index: number
  isLast: boolean
  isExpanded: boolean
  isAllSelected: boolean
  isToggling: boolean
}>()

defineEmits<{
  'toggle-expand': [id: number]
  'select-all': [section: AdminSection, event: Event]
  'reorder': [fromIdx: number, toIdx: number]
  'add-lesson': [sectionId: number]
  'toggle-status': [section: AdminSection]
  'edit': [section: AdminSection]
  'delete': [section: AdminSection]
}>()
</script>
