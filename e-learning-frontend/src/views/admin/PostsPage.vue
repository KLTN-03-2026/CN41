<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">Bài viết</h2>
        <p class="text-sm text-gray-500 mt-0.5">Quản lý tin tức và nội dung blog</p>
      </div>
      <router-link
        to="/admin/posts/create"
        class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors"
      >
        <PlusIcon class="w-4 h-4" /> Viết bài mới
      </router-link>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-xs">
        <input
          v-model="search"
          @input="debouncedFetch"
          type="text"
          placeholder="Tìm kiếm tiêu đề..."
          class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg bg-white text-gray-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
        />
        <svg
          class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <circle cx="11" cy="11" r="8" />
          <path d="m21 21-4.35-4.35" />
        </svg>
      </div>

      <select
        v-model="categoryFilter"
        @change="fetchPosts(1)"
        class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-white text-gray-700 outline-none focus:ring-2 focus:ring-blue-500/20"
      >
        <option value="">Tất cả danh mục</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
      </select>

      <select
        v-model="statusFilter"
        @change="fetchPosts(1)"
        class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-white text-gray-700 outline-none focus:ring-2 focus:ring-blue-500/20"
      >
        <option value="">Tất cả trạng thái</option>
        <option value="1">Đã xuất bản</option>
        <option value="0">Bản nháp</option>
      </select>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50/50">
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Bài viết</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Danh mục</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Tác giả</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Trạng thái</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Ngày tạo</th>
              <th class="text-right text-xs font-medium text-gray-500 px-4 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <template v-if="loading">
              <tr v-for="i in 5" :key="i">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gray-100 rounded animate-pulse"></div>
                    <div class="space-y-2">
                      <div class="h-4 w-48 bg-gray-100 rounded animate-pulse"></div>
                      <div class="h-3 w-32 bg-gray-100 rounded animate-pulse"></div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-20 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-24 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-16 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-20 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-16 bg-gray-100 rounded animate-pulse ml-auto"></div>
                </td>
              </tr>
            </template>
            <template v-else-if="posts.length">
              <tr
                v-for="post in posts"
                :key="post.id"
                class="hover:bg-gray-50/50 transition-colors"
              >
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <img
                      :src="post.thumbnail || '/images/placeholder.webp'"
                      class="w-12 h-12 rounded object-cover border border-gray-100"
                    />
                    <div class="max-w-[300px]">
                      <div class="font-medium text-gray-900 truncate" :title="post.title">
                        {{ post.title }}
                      </div>
                      <div class="text-xs text-gray-500 truncate">/{{ post.slug }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-xs">
                  <span
                    v-if="post.category"
                    class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600"
                  >
                    {{ post.category.name }}
                  </span>
                  <span v-else class="text-gray-400">Không có</span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-700">
                  {{ post.author?.name || 'Ẩn danh' }}
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="
                      post.is_published
                        ? 'bg-green-50 text-green-600'
                        : 'bg-yellow-50 text-yellow-600'
                    "
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                  >
                    {{ post.is_published ? 'Đã xuất bản' : 'Bản nháp' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-500">
                  {{ formatDate(post.created_at) }}
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <router-link
                      :to="`/admin/posts/${post.id}/edit`"
                      class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors"
                      title="Sửa"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                      </svg>
                    </router-link>
                    <button
                      @click="deletePost(post.id)"
                      class="p-1.5 rounded-lg hover:bg-red-50 text-gray-500 hover:text-red-500 transition-colors"
                      title="Xoá"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-else>
              <td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">
                Chưa có bài viết nào.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        v-if="pagination.last_page > 1"
        class="flex justify-end px-4 py-3 border-t border-gray-100"
      >
        <PaginationBar
          :current-page="pagination.current_page"
          :last-page="pagination.last_page"
          @change="fetchPosts"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { PlusIcon } from '@/components/icons'
import { usePosts } from '@/composables/usePosts'
import { formatDate } from '@/utils/formatDate'
import PaginationBar from '@/components/common/PaginationBar.vue'
import PostService from '@/services/post.service'
import type { PostCategory } from '@/types/post.types'

const { posts, loading, search, categoryFilter, statusFilter, pagination, fetchPosts, deletePost } =
  usePosts()

const categories = ref<PostCategory[]>([])

async function fetchCategories() {
  try {
    const res = await PostService.getCategories()
    categories.value = res.data.data
  } catch (err) {
    console.error(err)
  }
}

let debounceTimer: any
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchPosts(1)
  }, 300)
}

onMounted(() => {
  fetchPosts()
  fetchCategories()
})
</script>
