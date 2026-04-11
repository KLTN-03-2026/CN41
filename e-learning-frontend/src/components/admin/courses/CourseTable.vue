<template>
  <div>
    <!-- ── Active Table ── -->
    <div
      v-if="!isTrashed"
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
                Khóa học
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">
                Giảng viên
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">
                Giá
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">
                Học viên
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
              <td colspan="7" class="text-center py-10 text-gray-400">
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
            <tr v-else-if="!courses.length">
              <td colspan="7" class="text-center py-10 text-gray-400 text-sm">
                Chưa có khóa học nào
              </td>
            </tr>
            <CourseTableRow
              v-for="course in courses"
              :key="course.id"
              :course="course"
              :is-selected="selectedIds.has(course.id)"
              :is-toggling="togglingId === course.id"
              :is-trashed="false"
              :restoring-id="null"
              @toggle-select="$emit('toggle-select', $event)"
              @toggle-status="$emit('toggle-status', $event)"
              @delete="$emit('delete', $event)"
            />
          </tbody>
        </table>
      </div>

      <!-- Active Pagination -->
      <div
        v-if="pagination && pagination.last_page > 1"
        class="flex items-center justify-between px-6 py-3 border-t border-gray-100 dark:border-gray-700"
      >
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ pagination.from }}–{{ pagination.to }} / {{ pagination.total }} khóa học
        </p>
        <PaginationBar
          :current-page="pagination.current_page"
          :last-page="pagination.last_page"
          @change="$emit('page-change', $event)"
        />
      </div>
    </div>

    <!-- ── Trashed Table ── -->
    <div
      v-if="isTrashed"
      class="rounded-2xl border border-red-200 bg-white dark:border-red-900/50 dark:bg-white/5 overflow-hidden"
    >
      <div
        class="px-6 py-3 bg-red-50 dark:bg-red-500/5 border-b border-red-100 dark:border-red-900/30"
      >
        <div class="flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
          <svg
            class="w-4 h-4 flex-shrink-0"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
            />
          </svg>
          <span>Các khóa học trong thùng rác. Bạn có thể khôi phục hoặc xóa vĩnh viễn.</span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-white/5">
              <th class="w-10 px-4 py-3">
                <input
                  type="checkbox"
                  :checked="isTrashedAllSelected"
                  :indeterminate="isTrashedIndeterminate"
                  @change="$emit('toggle-trashed-select-all', $event)"
                  class="w-4 h-4 rounded border-gray-300 text-red-500 focus:ring-red-500"
                />
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">
                Khóa học
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">
                Giảng viên
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">
                Giá
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">
                Đã xóa lúc
              </th>
              <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">
                Thao tác
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <tr v-if="trashedLoading">
              <td colspan="6" class="text-center py-10 text-gray-400">
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
            <tr v-else-if="!trashedCourses.length">
              <td colspan="6" class="text-center py-10">
                <div class="flex flex-col items-center gap-2">
                  <svg
                    class="w-10 h-10 text-gray-300 dark:text-gray-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.5"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                  <p class="text-sm text-gray-400">Thùng rác trống</p>
                </div>
              </td>
            </tr>
            <CourseTableRow
              v-for="course in trashedCourses"
              :key="course.id"
              :course="course"
              :is-selected="trashedSelectedIds.has(course.id)"
              :is-toggling="false"
              :is-trashed="true"
              :restoring-id="restoringId"
              @toggle-select="$emit('toggle-trashed-select', $event)"
              @restore="$emit('restore', $event)"
              @force-delete="$emit('force-delete', $event)"
            />
          </tbody>
        </table>
      </div>

      <!-- Trashed Pagination -->
      <div
        v-if="trashedPagination && trashedPagination.last_page > 1"
        class="flex items-center justify-between px-6 py-3 border-t border-gray-100 dark:border-gray-700"
      >
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ trashedPagination.from }}–{{ trashedPagination.to }} /
          {{ trashedPagination.total }} khóa học
        </p>
        <PaginationBar
          :current-page="trashedPagination.current_page"
          :last-page="trashedPagination.last_page"
          active-class="bg-red-500 text-white border-red-500"
          @change="$emit('trashed-page-change', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import CourseTableRow from '@/components/admin/courses/CourseTableRow.vue'
import PaginationBar from '@/components/common/PaginationBar.vue'
import type { AdminCourse } from '@/types/admin-category.types'

import type { Pagination } from '@/types/common.types'

defineProps<{
  isTrashed: boolean
  // active
  courses: AdminCourse[]
  loading: boolean
  selectedIds: Set<number>
  isAllSelected: boolean
  isIndeterminate: boolean
  togglingId: number | null
  pagination: Pagination | null
  // trashed
  trashedCourses: AdminCourse[]
  trashedLoading: boolean
  trashedSelectedIds: Set<number>
  isTrashedAllSelected: boolean
  isTrashedIndeterminate: boolean
  restoringId: number | null
  trashedPagination: Pagination | null
}>()

defineEmits<{
  'toggle-select-all': [event: Event]
  'toggle-select': [id: number]
  'toggle-status': [course: AdminCourse]
  delete: [course: AdminCourse]
  'page-change': [page: number]
  'toggle-trashed-select-all': [event: Event]
  'toggle-trashed-select': [id: number]
  restore: [course: AdminCourse]
  'force-delete': [course: AdminCourse]
  'trashed-page-change': [page: number]
}>()
</script>
