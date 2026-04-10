<template>
  <tr
    class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group"
    :class="isOrphan ? '' : 'cursor-grab active:cursor-grabbing'"
    :draggable="!isOrphan"
    @dragstart="!isOrphan && $emit('dragstart')"
    @dragover.prevent
    @drop.prevent="!isOrphan && $emit('drop')"
  >
    <td class="pl-4 pr-1 py-2.5 w-8">
      <input
        type="checkbox"
        :checked="isSelected"
        @click.stop="$emit('toggle-select', lesson.id)"
        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
      />
    </td>
    <td class="pl-2 pr-2 py-2.5 text-gray-400 text-xs w-8">{{ index + 1 }}</td>
    <td class="px-2 py-2.5 font-medium text-gray-800 dark:text-gray-200 max-w-[200px] truncate">
      {{ lesson.title }}
    </td>
    <td class="px-2 py-2.5">
      <span
        :class="typeClass"
        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium"
      >
        {{ typeLabel }}
      </span>
    </td>
    <td class="px-2 py-2.5">
      <span
        v-if="lesson.is_preview"
        class="inline-flex items-center px-1.5 py-0.5 rounded border border-indigo-200 bg-indigo-50 text-indigo-600 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-400 text-[10px] font-medium whitespace-nowrap"
      >
        Học thử
      </span>
    </td>
    <td class="px-2 py-2.5 text-gray-500 dark:text-gray-400 text-xs">
      {{ lesson.duration ? formatSeconds(lesson.duration) : '—' }}
    </td>
    <td class="px-2 py-2.5">
      <button
        @click="$emit('toggle-status', lesson)"
        :disabled="isToggling"
        :class="lesson.status === 1
          ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
          : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400'"
        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium cursor-pointer disabled:opacity-50"
      >
        {{ lesson.status === 1 ? 'Đã đăng' : 'Nháp' }}
      </button>
    </td>
    <td class="px-2 py-2.5 text-right">
      <div class="flex items-center justify-end gap-1">
        <!-- Drag Handle -->
        <button
          v-if="!isOrphan"
          class="p-1 text-gray-400 hover:text-gray-600 transition-colors opacity-0 group-hover:opacity-100"
          title="Kéo thả hàng để sắp xếp"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/></svg>
        </button>
        <!-- Preview -->
        <button
          @click="$emit('preview', lesson.id)"
          class="p-1 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg dark:hover:bg-indigo-500/10 transition-colors"
          title="Xem trước nội dung"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </button>
        <!-- Edit -->
        <button
          @click="$emit('edit', lesson)"
          class="p-1 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10 transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
        </button>
        <!-- Delete -->
        <button
          @click="$emit('delete', lesson)"
          class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors"
        >
          <TrashIcon class="w-3.5 h-3.5" />
        </button>
      </div>
    </td>
  </tr>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { TrashIcon } from '@/components/icons'
import { formatSeconds } from '@/utils/formatDuration'

interface Lesson {
  id: number
  title: string
  type: string
  content?: string | null
  section_id?: number | null
  order: number
  is_preview: boolean
  duration?: number | null
  status: number
}

const props = defineProps<{
  lesson: Lesson
  index: number
  isSelected: boolean
  isToggling: boolean
  isOrphan?: boolean
}>()

defineEmits<{
  'toggle-select': [id: number]
  'toggle-status': [lesson: Lesson]
  'preview': [id: number]
  'edit': [lesson: Lesson]
  'delete': [lesson: Lesson]
  'dragstart': []
  'drop': []
}>()

const typeLabel = computed(() =>
  ({ video: 'Video', document: 'Tài liệu' }[props.lesson.type] || props.lesson.type)
)

const typeClass = computed(() =>
  ({
    video:    'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
    document: 'bg-orange-100 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400',
  }[props.lesson.type] || 'bg-gray-100 text-gray-600')
)
</script>
