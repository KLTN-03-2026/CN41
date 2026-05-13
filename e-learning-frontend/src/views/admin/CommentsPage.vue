<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">Quản lý Bình luận</h2>
        <p class="text-sm text-gray-500 mt-0.5">Duyệt và quản lý bình luận trên các bài viết</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-xs">
        <input
          v-model="search"
          @input="debouncedFetch"
          type="text"
          placeholder="Tìm kiếm nội dung..."
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
        v-model="statusFilter"
        @change="fetchComments(1)"
        class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-white text-gray-700 outline-none focus:ring-2 focus:ring-blue-500/20"
      >
        <option value="">Tất cả trạng thái</option>
        <option value="1">Đã duyệt</option>
        <option value="0">Chờ duyệt</option>
      </select>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50/50">
              <th class="w-10 px-4 py-3">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  :indeterminate="isIndeterminate"
                  @change="toggleSelectAll"
                  class="rounded border-gray-300 text-blue-500"
                />
              </th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Người dùng</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Nội dung</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Bài viết</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Trạng thái</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Ngày gửi</th>
              <th class="text-right text-xs font-medium text-gray-500 px-4 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <template v-if="loading">
              <tr v-for="i in 5" :key="i">
                <td class="px-4 py-3">
                  <div class="w-4 h-4 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-32 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-48 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-32 bg-gray-100 rounded animate-pulse"></div>
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
            <template v-else-if="comments.length">
              <tr
                v-for="comment in comments"
                :key="comment.id"
                class="hover:bg-gray-50/50 transition-colors"
              >
                <td class="px-4 py-3">
                  <input
                    type="checkbox"
                    :checked="selectedIds.has(comment.id)"
                    @change="toggleSelect(comment.id)"
                    class="rounded border-gray-300 text-blue-500"
                  />
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <img
                      :src="comment.commenter?.avatar || '/images/default-avatar.png'"
                      class="w-8 h-8 rounded-full border border-gray-100"
                    />
                    <span class="font-medium text-gray-900">{{
                      comment.commenter?.name || 'Ẩn danh'
                    }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-gray-700 max-w-[300px]">
                  <div class="truncate" :title="comment.content">{{ comment.content }}</div>
                </td>
                <td class="px-4 py-3 text-xs text-blue-600">
                  <router-link :to="`/admin/posts/${comment.post_id}/edit`" class="hover:underline">
                    {{ comment.post?.title || 'Bài viết #' + comment.post_id }}
                  </router-link>
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="
                      comment.is_approved
                        ? 'bg-green-50 text-green-600'
                        : 'bg-yellow-50 text-yellow-600'
                    "
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                  >
                    {{ comment.is_approved ? 'Đã duyệt' : 'Chờ duyệt' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-500">
                  {{ formatDate(comment.created_at) }}
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button
                      v-if="!comment.is_approved"
                      @click="approveComment(comment.id)"
                      class="p-1.5 rounded-lg hover:bg-green-50 text-green-600 transition-colors"
                      title="Duyệt"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path d="M5 13l4 4L19 7" />
                      </svg>
                    </button>
                    <button
                      @click="deleteComment(comment.id)"
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
              <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">
                Chưa có bình luận nào.
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
          @change="fetchComments"
        />
      </div>
    </div>

    <!-- Bulk Actions -->
    <div
      v-if="selectedIds.size > 0"
      class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[999999] flex items-center gap-3 px-5 py-3 bg-gray-900 text-white rounded-xl shadow-xl"
    >
      <span class="text-sm"
        >Đã chọn <strong>{{ selectedIds.size }}</strong> bình luận</span
      >
      <button
        @click="doBulkDelete"
        :disabled="bulkLoading"
        class="px-3 py-1.5 text-sm bg-red-500 hover:bg-red-600 rounded-lg disabled:opacity-50"
      >
        Xoá
      </button>
      <button
        @click="selectedIds.clear()"
        class="px-3 py-1.5 text-sm bg-gray-700 hover:bg-gray-600 rounded-lg"
      >
        Bỏ chọn
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import PostService from '@/services/post.service'
import type { PostComment } from '@/types/post.types'
import { formatDate } from '@/utils/formatDate'
import PaginationBar from '@/components/common/PaginationBar.vue'

const toast = useToast()

const comments = ref<PostComment[]>([])
const loading = ref(false)
const search = ref('')
const statusFilter = ref('')
const pagination = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

// Selection
const selectedIds = ref<Set<number>>(new Set())
const isAllSelected = computed(
  () => comments.value.length > 0 && selectedIds.value.size === comments.value.length,
)
const isIndeterminate = computed(() => selectedIds.value.size > 0 && !isAllSelected.value)
const toggleSelectAll = () => {
  if (isAllSelected.value) selectedIds.value.clear()
  else comments.value.forEach((c) => selectedIds.value.add(c.id))
}
const toggleSelect = (id: number) => {
  if (selectedIds.value.has(id)) selectedIds.value.delete(id)
  else selectedIds.value.add(id)
}

let debounceTimer: ReturnType<typeof setTimeout> | undefined
const debouncedFetch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchComments(1), 300)
}

async function fetchComments(page = 1) {
  loading.value = true
  selectedIds.value.clear()
  try {
    const params = {
      page,
      per_page: pagination.per_page,
      search: search.value || undefined,
      is_approved: statusFilter.value !== '' ? statusFilter.value : undefined,
    }
    const res = await PostService.getComments(params)
    comments.value = res.data.data
    Object.assign(pagination, res.data.pagination)
  } catch {
    comments.value = []
  } finally {
    loading.value = false
  }
}

async function approveComment(id: number) {
  try {
    await PostService.approveComment(id)
    toast.success('Đã duyệt bình luận')
    fetchComments(pagination.current_page)
  } catch {
    toast.error('Duyệt thất bại')
  }
}

async function deleteComment(id: number) {
  if (!confirm('Xoá bình luận này?')) return
  try {
    await PostService.deleteComment(id)
    toast.success('Đã xoá bình luận')
    fetchComments(pagination.current_page)
  } catch {
    toast.error('Xoá thất bại')
  }
}

const bulkLoading = ref(false)
async function doBulkDelete() {
  if (!confirm(`Xoá ${selectedIds.value.size} bình luận đã chọn?`)) return
  bulkLoading.value = true
  try {
    await PostService.bulkDeleteComments([...selectedIds.value])
    toast.success('Xoá hàng loạt thành công')
    selectedIds.value.clear()
    fetchComments(pagination.current_page)
  } catch {
    toast.error('Xoá hàng loạt thất bại')
  } finally {
    bulkLoading.value = false
  }
}

onMounted(() => {
  fetchComments()
})
</script>
