<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Khóa học</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Quản lý tất cả khóa học</p>
      </div>
      <router-link
        to="/admin/courses/create"
        class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors"
      >
        <PlusIcon class="w-4 h-4" />
        Thêm khóa học
      </router-link>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-4">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Tìm kiếm khóa học..."
        class="input-field w-64"
        @input="debouncedFetch"
      />
      <select v-model="filters.status" class="input-field w-40" @change="fetchPage(1)">
        <option value="">Tất cả trạng thái</option>
        <option value="1">Đã đăng</option>
        <option value="0">Nháp</option>
      </select>
      <select v-model="filters.level" class="input-field w-40" @change="fetchPage(1)">
        <option value="">Tất cả trình độ</option>
        <option value="beginner">Cơ bản</option>
        <option value="intermediate">Trung cấp</option>
        <option value="advanced">Nâng cao</option>
      </select>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/5 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-white/5">
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Khóa học</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Giảng viên</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Giá</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Học viên</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Trạng thái</th>
              <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <tr v-if="loading">
              <td colspan="6" class="text-center py-10 text-gray-400">
                <svg class="animate-spin w-6 h-6 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
              </td>
            </tr>
            <tr v-else-if="!courses.length">
              <td colspan="6" class="text-center py-10 text-gray-400 text-sm">Chưa có khóa học nào</td>
            </tr>
            <tr
              v-for="course in courses"
              :key="course.id"
              class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
            >
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <img
                    v-if="course.thumbnail"
                    :src="course.thumbnail"
                    :alt="course.name"
                    class="w-12 h-8 object-cover rounded shrink-0"
                  />
                  <div
                    v-else
                    class="w-12 h-8 bg-gray-100 dark:bg-gray-800 rounded shrink-0 flex items-center justify-center"
                  >
                    <BoxCubeIcon class="w-4 h-4 text-gray-400" />
                  </div>
                  <div class="min-w-0">
                    <p class="font-medium text-gray-800 dark:text-gray-200 truncate max-w-[200px]">{{ course.name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ levelLabel(course.level) }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                {{ course.teacher?.name || '—' }}
              </td>
              <td class="px-6 py-4">
                <div>
                  <p v-if="course.sale_price" class="font-medium text-green-600 dark:text-green-400">
                    {{ formatCurrency(Number(course.sale_price)) }}
                  </p>
                  <p
                    class="text-gray-600 dark:text-gray-400"
                    :class="{ 'line-through text-xs text-gray-400': course.sale_price }"
                  >
                    {{ formatCurrency(Number(course.price)) }}
                  </p>
                </div>
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                {{ course.total_students || 0 }}
              </td>
              <td class="px-6 py-4">
                <button
                  @click="toggleStatus(course)"
                  :disabled="togglingId === course.id"
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
                  <button
                    @click="confirmDelete(course)"
                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors"
                    title="Xóa"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination && pagination.last_page > 1"
        class="flex items-center justify-between px-6 py-3 border-t border-gray-100 dark:border-gray-700"
      >
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ pagination.from }}–{{ pagination.to }} / {{ pagination.total }} khóa học
        </p>
        <div class="flex gap-1">
          <button
            v-for="p in pagination.last_page"
            :key="p"
            @click="fetchPage(p)"
            :class="p === pagination.current_page
              ? 'bg-blue-500 text-white border-blue-500'
              : 'bg-white text-gray-600 dark:bg-white/5 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/10'"
            class="w-8 h-8 rounded-lg text-sm border border-gray-200 dark:border-gray-700 transition-colors"
          >
            {{ p }}
          </button>
        </div>
      </div>
    </div>

    <!-- Confirm Delete -->
    <Teleport to="body">
      <div
        v-if="deleteTarget"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        @click.self="deleteTarget = null"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-sm p-6">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-2">Xác nhận xóa</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Bạn có chắc muốn xóa khóa học
            <strong class="text-gray-800 dark:text-white/90">{{ deleteTarget.name }}</strong>?
          </p>
          <div class="flex justify-end gap-3">
            <button
              @click="deleteTarget = null"
              class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400"
            >
              Hủy
            </button>
            <button
              @click="doDelete"
              :disabled="deleting"
              class="px-4 py-2 text-sm rounded-lg bg-red-500 text-white hover:bg-red-600 disabled:opacity-50"
            >
              {{ deleting ? 'Đang xóa...' : 'Xóa' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { PlusIcon, TrashIcon, BoxCubeIcon } from '@/icons'
import { coursesApi } from '@/api/coursesApi'
import { formatCurrency } from '@/utils/formatCurrency'

const toast = useToast()

interface Course {
  id: number
  name: string
  slug: string
  thumbnail?: string | null
  price: string
  sale_price?: string | null
  level: string
  status: number
  total_students: number
  teacher?: { id: number; name: string; slug: string } | null
}

const courses     = ref<Course[]>([])
const pagination  = ref<any>(null)
const loading     = ref(true)
const currentPage = ref(1)
const togglingId  = ref<number | null>(null)
const deleteTarget = ref<Course | null>(null)
const deleting    = ref(false)

const filters = reactive({ search: '', status: '', level: '' })

let debounceTimer: ReturnType<typeof setTimeout> | null = null
function debouncedFetch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchPage(1), 400)
}

async function fetchPage(page = 1) {
  loading.value = true
  currentPage.value = page
  try {
    const params: Record<string, any> = { page, per_page: 15 }
    if (filters.search) params.search = filters.search
    if (filters.status !== '') params.status = filters.status
    if (filters.level) params.level = filters.level

    const res = await coursesApi.index(params)
    courses.value = res.data.data
    pagination.value = res.data.pagination
  } catch {
    toast.error('Không thể tải khóa học')
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchPage())

function levelLabel(level: string) {
  return { beginner: 'Cơ bản', intermediate: 'Trung cấp', advanced: 'Nâng cao' }[level] || level
}

async function toggleStatus(course: Course) {
  togglingId.value = course.id
  try {
    await coursesApi.toggleStatus(course.id)
    course.status = course.status === 1 ? 0 : 1
    toast.success(`Đã ${course.status === 1 ? 'đăng' : 'chuyển về nháp'} khóa học`)
  } catch {
    toast.error('Không thể cập nhật trạng thái')
  } finally {
    togglingId.value = null
  }
}

function confirmDelete(course: Course) {
  deleteTarget.value = course
}

async function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await coursesApi.destroy(deleteTarget.value.id)
    toast.success('Xóa khóa học thành công')
    deleteTarget.value = null
    fetchPage(currentPage.value)
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Xóa thất bại')
  } finally {
    deleting.value = false
  }
}
</script>

<style scoped>
.input-field {
  @apply h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm text-gray-800
         dark:border-gray-700 dark:text-white/90 dark:bg-gray-900
         focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400;
}
</style>
