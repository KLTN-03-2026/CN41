<template>
  <tr
    :class="isSelected ? 'bg-blue-50 dark:bg-blue-500/5' : ''"
    class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
  >
    <td class="w-10 px-4 py-4">
      <input
        type="checkbox"
        :checked="isSelected"
        @change="$emit('toggle-select', course.id)"
        class="w-4 h-4 rounded border-gray-300 text-blue-500 focus:ring-blue-500"
      />
    </td>
    <td class="px-6 py-4">
      <div class="flex items-center gap-3">
        <img
          v-if="course.thumbnail"
          :src="course.thumbnail"
          :alt="course.name"
          class="w-12 h-8 object-cover rounded shrink-0"
          :class="{ 'opacity-60': isTrashed }"
        />
        <div
          v-else
          class="w-12 h-8 bg-gray-100 dark:bg-gray-800 rounded shrink-0 flex items-center justify-center"
          :class="{ 'opacity-60': isTrashed }"
        >
          <BoxCubeIcon class="w-4 h-4 text-gray-400" />
        </div>
        <div class="min-w-0">
          <p
            class="font-medium truncate max-w-[200px]"
            :class="isTrashed ? 'text-gray-500 dark:text-gray-400' : 'text-gray-800 dark:text-gray-200'"
          >
            {{ course.name }}
          </p>
          <p class="text-xs text-gray-400 mt-0.5">{{ levelLabel(course.level) }}</p>
        </div>
      </div>
    </td>
    <td class="px-6 py-4" :class="isTrashed ? 'text-gray-500 dark:text-gray-500' : 'text-gray-600 dark:text-gray-400'">
      {{ course.teacher?.name || '—' }}
    </td>
    <td class="px-6 py-4">
      <template v-if="!isTrashed">
        <p v-if="course.sale_price" class="font-medium text-green-600 dark:text-green-400">
          {{ formatCurrency(Number(course.sale_price)) }}
        </p>
        <p
          class="text-gray-600 dark:text-gray-400"
          :class="{ 'line-through text-xs text-gray-400': course.sale_price }"
        >
          {{ formatCurrency(Number(course.price)) }}
        </p>
      </template>
      <span v-else class="text-gray-500 dark:text-gray-500">
        {{ formatCurrency(Number(course.price)) }}
      </span>
    </td>

    <!-- Active-only columns -->
    <template v-if="!isTrashed">
      <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
        {{ course.total_students || 0 }}
      </td>
      <td class="px-6 py-4">
        <button
          @click="$emit('toggle-status', course)"
          :disabled="isToggling"
          :class="course.status === 1
            ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400 hover:bg-green-200'
            : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400 hover:bg-yellow-200'"
          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium transition-colors disabled:opacity-50 cursor-pointer"
        >
          {{ course.status === 1 ? 'Đã đăng' : 'Nháp' }}
        </button>
      </td>
      <td class="px-6 py-4 text-right">
        <div class="flex items-center justify-end gap-2">
          <router-link
            :to="`/admin/courses/${course.id}/edit`"
            class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10 transition-colors"
            title="Chỉnh sửa"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </router-link>
          <router-link
            :to="`/admin/courses/${course.id}/edit?tab=lessons`"
            class="p-1.5 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg dark:hover:bg-purple-500/10 transition-colors"
            title="Nội dung (Chương & Bài giảng)"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
          </router-link>
          <button
            @click="$emit('delete', course)"
            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors"
            title="Xóa"
          >
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>
      </td>
    </template>

    <!-- Trashed-only columns -->
    <template v-else>
      <td class="px-6 py-4 text-gray-500 dark:text-gray-500 text-xs">
        {{ formatDate(course.deleted_at) }}
      </td>
      <td class="px-6 py-4 text-right">
        <div class="flex items-center justify-end gap-2">
          <button
            @click="$emit('restore', course)"
            :disabled="restoringId === course.id"
            class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg dark:hover:bg-green-500/10 transition-colors disabled:opacity-50"
            title="Khôi phục"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
          </button>
          <button
            @click="$emit('force-delete', course)"
            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors"
            title="Xóa vĩnh viễn"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </td>
    </template>
  </tr>
</template>

<script setup lang="ts">
import { TrashIcon, BoxCubeIcon } from '@/components/icons'
import { formatCurrency } from '@/utils/formatCurrency'
import type { AdminCourse } from '@/types/admin-category.types'

defineProps<{
  course: AdminCourse
  isSelected: boolean
  isToggling: boolean
  isTrashed: boolean
  restoringId: number | null
}>()

defineEmits<{
  'toggle-select': [id: number]
  'toggle-status': [course: AdminCourse]
  'delete': [course: AdminCourse]
  'restore': [course: AdminCourse]
  'force-delete': [course: AdminCourse]
}>()

function levelLabel(level: string) {
  return ({ beginner: 'Cơ bản', intermediate: 'Trung cấp', advanced: 'Nâng cao' } as Record<string, string>)[level] || level
}

function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
