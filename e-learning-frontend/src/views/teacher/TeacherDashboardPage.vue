<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Tổng quan</h1>

    <div v-if="loading" class="text-center py-12 text-gray-400">Đang tải...</div>

    <template v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
          <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Khóa học</p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total_courses }}</p>
          <p class="text-xs text-gray-400 mt-1">khóa đã tạo</p>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
          <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Học viên</p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total_students.toLocaleString('vi-VN') }}</p>
          <p class="text-xs text-gray-400 mt-1">học viên đã đăng ký</p>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-green-200 dark:border-green-800 p-5 bg-green-50 dark:bg-green-900/10">
          <p class="text-xs text-green-600 uppercase font-semibold mb-1">Số dư khả dụng</p>
          <p class="text-3xl font-bold text-green-700 dark:text-green-400">
            {{ Number(stats.available_balance).toLocaleString('vi-VN') }} ₫
          </p>
          <p class="text-xs text-gray-400 mt-1">sẵn sàng rút</p>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-blue-200 dark:border-blue-800 p-5 bg-blue-50 dark:bg-blue-900/10">
          <p class="text-xs text-blue-600 uppercase font-semibold mb-1">Tổng đã kiếm</p>
          <p class="text-3xl font-bold text-blue-700 dark:text-blue-400">
            {{ Number(stats.total_earned).toLocaleString('vi-VN') }} ₫
          </p>
          <p class="text-xs text-gray-400 mt-1">tổng hoa hồng</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <router-link
          to="/teacher/earnings"
          class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:border-blue-300 transition-colors group"
        >
          <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-700 mb-1">Thu nhập của tôi</h3>
          <p class="text-sm text-gray-500">Xem lịch sử hoa hồng và yêu cầu rút tiền</p>
        </router-link>
        <router-link
          to="/teacher/courses"
          class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:border-blue-300 transition-colors group"
        >
          <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-700 mb-1">Khóa học của tôi</h3>
          <p class="text-sm text-gray-500">Xem danh sách các khóa học bạn giảng dạy</p>
        </router-link>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useTeacherDashboard } from '@/composables/useTeacherDashboard'

const { stats, loading, loadDashboard } = useTeacherDashboard()
onMounted(() => loadDashboard())
</script>
