<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bài viết của tôi</h1>
      <router-link
        to="/teacher/posts/create"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors"
      >
        + Viết bài mới
      </router-link>
    </div>

    <div class="mb-4">
      <select
        v-model="filters.approval_status"
        @change="fetchPosts()"
        class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none"
      >
        <option value="">Tất cả trạng thái</option>
        <option value="pending">Chờ duyệt</option>
        <option value="approved">Đã duyệt</option>
        <option value="rejected">Từ chối</option>
      </select>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
          <tr>
            <th class="px-5 py-3 text-left font-semibold">Tiêu đề</th>
            <th class="px-5 py-3 text-left font-semibold">Danh mục</th>
            <th class="px-5 py-3 text-center font-semibold">Trạng thái</th>
            <th class="px-5 py-3 text-left font-semibold">Ngày tạo</th>
            <th class="px-5 py-3 text-center font-semibold">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="px-5 py-10 text-center text-gray-400">Đang tải...</td>
          </tr>
          <tr v-else-if="!posts.length">
            <td colspan="5" class="px-5 py-10 text-center text-gray-400">Chưa có bài viết nào.</td>
          </tr>
          <tr
            v-for="post in posts"
            :key="post.id"
            class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50"
          >
            <td class="px-5 py-3">
              <p class="font-medium text-gray-900 dark:text-white">{{ post.title }}</p>
              <p class="text-xs text-gray-400">{{ post.slug }}</p>
              <p v-if="post.rejection_reason" class="text-xs text-red-500 mt-1">
                Lý do từ chối: {{ post.rejection_reason }}
              </p>
            </td>
            <td class="px-5 py-3 text-gray-600 dark:text-gray-400">
              {{ post.category?.name ?? '—' }}
            </td>
            <td class="px-5 py-3 text-center">
              <span
                :class="[
                  'inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                  post.approval_status === 'approved'
                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                    : post.approval_status === 'rejected'
                    ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                    : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                ]"
              >
                {{
                  post.approval_status === 'approved'
                    ? 'Đã duyệt'
                    : post.approval_status === 'rejected'
                    ? 'Từ chối'
                    : 'Chờ duyệt'
                }}
              </span>
            </td>
            <td class="px-5 py-3 text-gray-500 text-xs">
              {{ new Date(post.created_at).toLocaleDateString('vi-VN') }}
            </td>
            <td class="px-5 py-3 text-center">
              <div class="flex items-center justify-center gap-2">
                <router-link
                  :to="`/teacher/posts/${post.id}/edit`"
                  class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs font-medium"
                >
                  Sửa
                </router-link>
                <button
                  @click="deletePost(post.id)"
                  class="text-red-600 hover:text-red-800 dark:text-red-400 text-xs font-medium"
                >
                  Xóa
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div
        v-if="pagination.last_page > 1"
        class="px-5 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-sm text-gray-500"
      >
        <span>Tổng {{ pagination.total }} bài viết</span>
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
import { useTeacherPosts } from '@/composables/useTeacherPosts'

const { posts, pagination, loading, filters, fetchPosts, deletePost, changePage } = useTeacherPosts()
onMounted(() => fetchPosts())
</script>
