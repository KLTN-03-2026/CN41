<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Khóa học của tôi</h1>
      <router-link
        to="/teacher/courses/create"
        class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Thêm khóa học
      </router-link>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
          <tr>
            <th class="px-5 py-3 text-left font-semibold">Tên khóa học</th>
            <th class="px-5 py-3 text-right font-semibold">Học viên</th>
            <th class="px-5 py-3 text-right font-semibold">Giá</th>
            <th class="px-5 py-3 text-center font-semibold">Trạng thái</th>
            <th class="px-5 py-3 text-center font-semibold">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="px-5 py-10 text-center text-gray-400">Đang tải...</td>
          </tr>
          <tr v-else-if="!courses.length">
            <td colspan="5" class="px-5 py-10 text-center text-gray-400">Chưa có khóa học nào.</td>
          </tr>
          <tr
            v-for="course in courses"
            :key="course.id"
            class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50"
          >
            <td class="px-5 py-3">
              <p class="font-medium text-gray-900 dark:text-white">{{ course.name }}</p>
              <p class="text-xs text-gray-400">{{ course.slug }}</p>
            </td>
            <td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">
              {{ course.total_students.toLocaleString('vi-VN') }}
            </td>
            <td class="px-5 py-3 text-right">
              <span v-if="course.sale_price" class="text-green-700 dark:text-green-400 font-medium">
                {{ Number(course.sale_price).toLocaleString('vi-VN') }} ₫
              </span>
              <span v-else class="text-gray-700 dark:text-gray-300 font-medium">
                {{ Number(course.price).toLocaleString('vi-VN') }} ₫
              </span>
            </td>
            <td class="px-5 py-3 text-center">
              <span
                :class="[
                  'inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                  course.status === 1
                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                ]"
              >
                {{ course.status === 1 ? 'Đã xuất bản' : 'Bản nháp' }}
              </span>
            </td>
            <td class="px-5 py-3 text-center">
              <router-link
                :to="`/teacher/courses/${course.id}/edit`"
                class="inline-flex items-center gap-1 px-3 py-1 text-xs text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-700 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
              >
                Sửa
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="pagination.last_page > 1"
        class="px-5 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-sm text-gray-500"
      >
        <span>Tổng {{ pagination.total }} khóa học</span>
        <div class="flex gap-1">
          <button
            v-for="page in pagination.last_page"
            :key="page"
            @click="changePage(page)"
            :class="[
              'px-3 py-1 rounded text-sm',
              page === pagination.current_page
                ? 'bg-blue-600 text-white'
                : 'hover:bg-gray-100 dark:hover:bg-gray-700',
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useTeacherCourses } from '@/composables/useTeacherCourses'

const { courses, pagination, loading, loadCourses, changePage } = useTeacherCourses()
onMounted(() => loadCourses())
</script>
