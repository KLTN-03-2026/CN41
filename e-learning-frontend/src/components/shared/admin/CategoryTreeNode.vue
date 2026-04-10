<template>
  <tr
    class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
    :class="[
      cat.depth === 0 && !isFirst ? 'border-t-2 !border-gray-200 dark:!border-gray-600' : '',
      isSelected ? 'bg-blue-50 dark:bg-blue-500/5' : ''
    ]"
  >
    <td class="w-10 px-4 py-2.5">
      <input
        type="checkbox"
        :checked="isSelected"
        @change="$emit('toggle-select', cat.id)"
        class="w-4 h-4 rounded border-gray-300 text-blue-500 focus:ring-blue-500"
      />
    </td>
    <td class="px-6 py-2.5">
      <div class="flex items-center" :style="{ paddingLeft: cat.depth * 24 + 'px' }">
        <button
          v-if="hasChildren"
          @click="$emit('toggle-expand', cat.id)"
          class="mr-1.5 p-0.5 rounded hover:bg-gray-100 dark:hover:bg-white/10 transition-colors text-gray-400 dark:text-gray-500"
        >
          <svg
            class="w-4 h-4 transition-transform duration-200"
            :class="isExpanded ? 'rotate-90' : ''"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
        <span v-else class="mr-1.5 w-5 inline-block"></span>

        <span v-if="cat.depth > 0" class="text-gray-300 dark:text-gray-600 mr-1.5 font-mono text-xs select-none">
          {{ isLastChild ? '└─' : '├─' }}
        </span>

        <span :class="cat.depth === 0 ? 'text-blue-500' : 'text-gray-400 dark:text-gray-500'" class="mr-2 flex-shrink-0">
          <svg v-if="cat.depth === 0" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
          </svg>
          <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
          </svg>
        </span>

        <span class="font-medium" :class="cat.depth === 0 ? 'text-gray-800 dark:text-white/90' : 'text-gray-600 dark:text-gray-300'">
          <span v-if="searchQuery" v-html="highlightName(cat.name)"></span>
          <template v-else>{{ cat.name }}</template>
        </span>
        <span
          v-if="hasChildren && cat.depth === 0"
          class="ml-2 px-1.5 py-0.5 text-[10px] rounded-full bg-blue-50 text-blue-500 dark:bg-blue-500/10 dark:text-blue-400 font-medium"
        >
          {{ childCount }}
        </span>
      </div>
    </td>
    <td class="px-6 py-2.5 text-gray-500 dark:text-gray-400 font-mono text-xs">{{ cat.slug }}</td>
    <td class="px-6 py-2.5">
      <span
        :class="cat.status === 1
          ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
          : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
      >
        {{ cat.status === 1 ? 'Hoạt động' : 'Ẩn' }}
      </span>
    </td>
    <td class="px-6 py-2.5 text-right">
      <div class="flex items-center justify-end gap-2">
        <button
          @click="$emit('edit', cat)"
          class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10 transition-colors"
          title="Chỉnh sửa"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
        </button>
        <button
          @click="$emit('delete', cat)"
          class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors"
          title="Xóa"
        >
          <TrashIcon class="w-4 h-4" />
        </button>
      </div>
    </td>
  </tr>
</template>

<script setup lang="ts">
import { TrashIcon } from '@/components/icons'

interface Category {
  id: number
  name: string
  slug: string
  description?: string | null
  status: number
  depth: number
  is_root: boolean
  parent_id?: number | null
  deleted_at?: string | null
}

const props = defineProps<{
  cat: Category
  isFirst: boolean
  isSelected: boolean
  isExpanded: boolean
  isLastChild: boolean
  hasChildren: boolean
  childCount: number
  searchQuery: string
}>()

defineEmits<{
  'toggle-select': [id: number]
  'toggle-expand': [id: number]
  'edit': [cat: Category]
  'delete': [cat: Category]
}>()

function highlightName(name: string): string {
  const q = props.searchQuery.trim()
  if (!q) return name
  const regex = new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
  return name.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-500/30 rounded px-0.5">$1</mark>')
}
</script>
