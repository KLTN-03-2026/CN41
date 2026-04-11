<template>
  <div
    class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/5 overflow-hidden"
  >
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-white/5">
            <th class="w-10 px-4 py-3">
              <input
                type="checkbox"
                :checked="isAllSelected"
                :indeterminate="isIndeterminate"
                @change="$emit('toggle-select-all', $event)"
                class="w-4 h-4 rounded border-gray-300 text-blue-500 focus:ring-blue-500"
              />
            </th>
            <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">
              Tên danh mục
            </th>
            <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">
              Slug
            </th>
            <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">
              Trạng thái
            </th>
            <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">
              Thao tác
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
          <tr v-if="loading">
            <td colspan="5" class="text-center py-10 text-gray-400">
              <svg
                class="animate-spin w-6 h-6 mx-auto"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                />
              </svg>
            </td>
          </tr>
          <tr v-else-if="isSearching && !visibleCategories.length">
            <td colspan="5" class="text-center py-10 text-gray-400 text-sm">
              Không tìm thấy danh mục nào cho "<strong class="text-gray-600 dark:text-gray-300">{{
                searchQuery
              }}</strong
              >"
            </td>
          </tr>
          <tr v-else-if="!allCategories.length">
            <td colspan="5" class="text-center py-10 text-gray-400 text-sm">
              Chưa có danh mục nào
            </td>
          </tr>
          <CategoryTreeNode
            v-for="(cat, idx) in visibleCategories"
            :key="cat.id"
            :cat="cat"
            :is-first="idx === 0"
            :is-selected="selectedIds.has(cat.id)"
            :is-expanded="expandedIds.has(cat.id)"
            :is-last-child="isLastChild(cat, idx)"
            :has-children="hasChildren(cat.id)"
            :child-count="getChildCount(cat.id)"
            :search-query="searchQuery"
            @toggle-select="$emit('toggle-select', $event)"
            @toggle-expand="$emit('toggle-expand', $event)"
            @edit="$emit('edit', $event)"
            @delete="$emit('delete', $event)"
          />
        </tbody>
      </table>
    </div>

    <!-- Footer info & Pagination -->
    <div
      v-if="allCategories.length"
      class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between"
    >
      <p class="text-xs text-gray-500 dark:text-gray-400">
        <template v-if="isSearching">Tìm thấy {{ matchCount }} kết quả / </template>
        <template v-if="pagination">
          {{ pagination.from }}–{{ pagination.to }} / {{ pagination.total }} danh mục gốc
        </template>
        <template v-else> Tổng {{ allCategories.length }} danh mục </template>
      </p>

      <div v-if="pagination && pagination.last_page > 1" class="flex gap-1">
        <button
          v-for="p in pagination.last_page"
          :key="p"
          @click="$emit('page-change', p)"
          :class="
            p === pagination.current_page
              ? 'bg-blue-500 text-white border-blue-500'
              : 'bg-white text-gray-600 dark:bg-white/5 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/10'
          "
          class="w-8 h-8 rounded-lg text-sm border border-gray-200 dark:border-gray-700 transition-colors"
        >
          {{ p }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import CategoryTreeNode from '@/components/shared/admin/CategoryTreeNode.vue'
import type { AdminCategory } from '@/types/admin-category.types'

defineProps<{
  allCategories: AdminCategory[]
  visibleCategories: AdminCategory[]
  loading: boolean
  isSearching: boolean
  searchQuery: string
  matchCount: number
  selectedIds: Set<number>
  expandedIds: Set<number>
  isAllSelected: boolean
  isIndeterminate: boolean
  hasChildren: (id: number) => boolean
  getChildCount: (id: number) => number
  isLastChild: (cat: AdminCategory, idx: number) => boolean
  pagination: import('@/types/common.types').Pagination | null
}>()

defineEmits<{
  'toggle-select-all': [event: Event]
  'toggle-select': [id: number]
  'toggle-expand': [id: number]
  edit: [cat: AdminCategory]
  delete: [cat: AdminCategory]
  'page-change': [page: number]
}>()
</script>
