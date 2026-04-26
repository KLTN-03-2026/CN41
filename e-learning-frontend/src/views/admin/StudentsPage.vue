<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Học viên</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Quản lý tất cả học viên</p>
      </div>
      <button v-if="!isTrashed" @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors">
        <PlusIcon class="w-4 h-4" /> Thêm học viên
      </button>
    </div>

    <!-- Tabs + Search -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
      <div class="flex gap-1 p-1 bg-gray-100 dark:bg-white/5 rounded-lg">
        <button @click="switchTab(false)" :class="!isTrashed ? 'bg-white dark:bg-white/10 shadow-sm font-medium' : ''"
          class="px-3 py-1.5 text-sm rounded-md text-gray-600 dark:text-gray-300 transition-colors">
          Tất cả
        </button>
        <button @click="switchTab(true)" :class="isTrashed ? 'bg-white dark:bg-white/10 shadow-sm font-medium' : ''"
          class="px-3 py-1.5 text-sm rounded-md text-gray-600 dark:text-gray-300 transition-colors">
          Thùng rác <span v-if="trashedCount" class="ml-1 text-xs text-red-500">({{ trashedCount }})</span>
        </button>
      </div>
      <div class="relative flex-1 max-w-xs">
        <input v-model="search" @input="debouncedFetch" type="text" placeholder="Tìm theo tên, email..."
          class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" />
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </div>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/5 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-white/[0.02]">
              <th class="w-10 px-4 py-3">
                <input type="checkbox" :checked="isAllSelected" :indeterminate="isIndeterminate" @change="toggleSelectAll"
                  class="rounded border-gray-300 dark:border-gray-600 text-blue-500 focus:ring-blue-500/20" />
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">Học viên</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">Email</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">Ngày sinh</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">Xác minh</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">Ngày tạo</th>
              <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <!-- Loading skeleton -->
            <template v-if="loading">
              <tr v-for="i in 5" :key="i">
                <td class="px-4 py-3"><div class="w-4 h-4 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div></td>
                <td class="px-4 py-3"><div class="h-4 w-32 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div></td>
                <td class="px-4 py-3"><div class="h-4 w-40 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div></td>
                <td class="px-4 py-3"><div class="h-4 w-24 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div></td>
                <td class="px-4 py-3"><div class="h-4 w-16 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div></td>
                <td class="px-4 py-3"><div class="h-4 w-24 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div></td>
                <td class="px-4 py-3"><div class="h-4 w-16 bg-gray-100 dark:bg-gray-800 rounded animate-pulse ml-auto"></div></td>
              </tr>
            </template>

            <!-- Data rows -->
            <template v-else-if="students.length">
              <tr v-for="s in students" :key="s.id" class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                <td class="px-4 py-3">
                  <input type="checkbox" :checked="selectedIds.has(s.id)" @change="toggleSelect(s.id)"
                    class="rounded border-gray-300 dark:border-gray-600 text-blue-500 focus:ring-blue-500/20" />
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400 font-medium text-xs shrink-0">
                      {{ s.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <span class="font-medium text-gray-800 dark:text-white/90">{{ s.name }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ s.email }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ s.date_of_birth ? formatDate(s.date_of_birth) : '—' }}</td>
                <td class="px-4 py-3">
                  <span v-if="s.email_verified_at" class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Đã xác minh
                  </span>
                  <span v-else class="text-xs text-gray-400">Chưa</span>
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ formatDate(s.created_at) }}</td>
                <td class="px-4 py-3 text-right">
                  <div v-if="!isTrashed" class="flex items-center justify-end gap-1">
                    <button @click="openDetail(s)" class="p-1.5 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-500/10 text-gray-500 dark:text-gray-400 hover:text-blue-500 transition-colors" title="Xem chi tiết">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <button @click="openEdit(s)" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500 dark:text-gray-400 transition-colors" title="Sửa">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button @click="confirmDelete(s)" class="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 text-gray-500 dark:text-gray-400 hover:text-red-500 transition-colors" title="Xoá">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                  <div v-else class="flex items-center justify-end gap-1">
                    <button @click="doRestore(s.id)" :disabled="restoringId === s.id"
                      class="p-1.5 rounded-lg hover:bg-green-50 dark:hover:bg-green-500/10 text-gray-500 hover:text-green-600 transition-colors disabled:opacity-50" title="Khôi phục">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                    <button @click="confirmForceDelete(s)" class="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 text-gray-500 hover:text-red-500 transition-colors" title="Xoá vĩnh viễn">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </template>

            <!-- Empty -->
            <tr v-else>
              <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">
                {{ isTrashed ? 'Thùng rác trống.' : 'Chưa có học viên nào.' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex justify-end px-4 py-3 border-t border-gray-100 dark:border-gray-700">
        <PaginationBar :current-page="pagination.current_page" :last-page="pagination.last_page" @change="loadPage" />
      </div>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedIds.size > 0"
      class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[999999] flex items-center gap-3 px-5 py-3 bg-gray-900 dark:bg-gray-800 text-white rounded-xl shadow-xl">
      <span class="text-sm">Đã chọn <strong>{{ selectedIds.size }}</strong> học viên</span>
      <button v-if="!isTrashed" @click="doBulkDelete" :disabled="bulkLoading"
        class="px-3 py-1.5 text-sm bg-red-500 hover:bg-red-600 rounded-lg transition-colors disabled:opacity-50">Xoá</button>
      <button v-if="isTrashed" @click="doBulkRestore" :disabled="bulkLoading"
        class="px-3 py-1.5 text-sm bg-green-500 hover:bg-green-600 rounded-lg transition-colors disabled:opacity-50">Khôi phục</button>
      <button @click="selectedIds.clear()" class="px-3 py-1.5 text-sm bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors">Bỏ chọn</button>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-[999999] flex items-center justify-center bg-black/50" @click.self="showModal = false">
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 mb-4">{{ editingStudent ? 'Sửa học viên' : 'Thêm học viên' }}</h3>
        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Họ tên *</label>
            <input v-model="form.name" type="text" required
              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
            <input v-model="form.email" type="email" required
              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none" />
          </div>
          <div v-if="!editingStudent">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mật khẩu *</label>
            <input v-model="form.password" type="password" :required="!editingStudent"
              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ngày sinh</label>
            <input v-model="form.date_of_birth" type="date"
              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none" />
          </div>
          <p v-if="formError" class="text-sm text-red-500">{{ formError }}</p>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showModal = false" class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Huỷ</button>
            <button type="submit" :disabled="submitting"
              class="px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors disabled:opacity-50">
              {{ submitting ? 'Đang lưu...' : 'Lưu' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Student Detail Modal -->
    <div v-if="showDetail" class="fixed inset-0 z-[999999] flex items-center justify-center bg-black/70 backdrop-blur-sm" @click.self="showDetail = false">
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg mx-4 max-h-[85vh] flex flex-col">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Chi tiết học viên</h3>
          <button @click="showDetail = false" class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-400 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div v-if="detailLoading" class="p-6 space-y-3">
          <div v-for="i in 4" :key="i" class="h-4 bg-gray-100 dark:bg-gray-800 rounded animate-pulse" :style="{width: (60+i*10)+'%'}"></div>
        </div>
        <div v-else-if="detailStudent" class="overflow-y-auto p-6 space-y-5">
          <!-- Info -->
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-xl shrink-0">{{ detailStudent.name?.charAt(0)?.toUpperCase() }}</div>
            <div>
              <p class="font-semibold text-gray-800 dark:text-white/90 text-base">{{ detailStudent.name }}</p>
              <p class="text-sm text-gray-500">{{ detailStudent.email }}</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="bg-gray-50 dark:bg-white/5 rounded-xl p-3">
              <p class="text-gray-400 text-xs">Ngày sinh</p>
              <p class="font-medium text-gray-700 dark:text-gray-200">{{ detailStudent.date_of_birth ? formatDate(detailStudent.date_of_birth) : '—' }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-white/5 rounded-xl p-3">
              <p class="text-gray-400 text-xs">Xác minh email</p>
              <p class="font-medium" :class="detailStudent.email_verified_at ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">{{ detailStudent.email_verified_at ? 'Đã xác minh' : 'Chưa' }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-white/5 rounded-xl p-3">
              <p class="text-gray-400 text-xs">Đơn hàng</p>
              <p class="font-medium text-gray-700 dark:text-gray-200">{{ detailStudent.orders_count ?? 0 }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-white/5 rounded-xl p-3">
              <p class="text-gray-400 text-xs">Tổng chi tiêu</p>
              <p class="font-medium text-gray-700 dark:text-gray-200">{{ formatCurrency(detailStudent.total_spent ?? 0) }}</p>
            </div>
          </div>
          <!-- Enrolled Courses -->
          <div>
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Khóa học đã đăng ký ({{ detailStudent.enrolled_courses?.length ?? 0 }})</h4>
            <div v-if="detailStudent.enrolled_courses?.length" class="space-y-2 max-h-48 overflow-y-auto">
              <div v-for="c in detailStudent.enrolled_courses" :key="c.id"
                class="flex items-center gap-3 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                <img v-if="c.thumbnail" :src="getThumbnail(c.thumbnail)" class="w-12 h-8 rounded-lg object-cover shrink-0" />
                <div v-else class="w-12 h-8 rounded-lg bg-gray-200 dark:bg-gray-700 shrink-0"></div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-800 dark:text-white/90 truncate">{{ c.name }}</p>
                  <p class="text-xs text-gray-400">{{ formatCurrency(c.sale_price ?? c.price) }} · {{ c.enrolled_at ? formatDate(c.enrolled_at) : '' }}</p>
                </div>
              </div>
            </div>
            <p v-else class="text-sm text-gray-400 italic">Chưa đăng ký khóa học nào.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm Delete Modal -->
    <ConfirmModal :show="showDeleteModal" title="Xác nhận xoá" :loading="deleteLoading" confirm-text="Xoá" loading-text="Đang xoá..."
      @cancel="showDeleteModal = false" @confirm="doDelete">
      <p>Bạn có chắc muốn xoá học viên <strong class="text-gray-800 dark:text-white/90">{{ deletingStudent?.name }}</strong>?
        <span v-if="!isTrashed" class="block mt-1 text-xs text-gray-400">Học viên sẽ được chuyển vào thùng rác.</span>
        <span v-else class="block mt-1 text-xs text-red-400">Hành động này không thể hoàn tác!</span>
      </p>
    </ConfirmModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { studentService, type Student } from '@/services/student.service'
import { formatDate } from '@/utils/formatDate'
import { formatCurrency } from '@/utils/formatCurrency'
import { PlusIcon } from '@/components/icons'
import PaginationBar from '@/components/common/PaginationBar.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

// ── State ──
const students = ref<Student[]>([])
const loading = ref(false)
const search = ref('')
const isTrashed = ref(false)
const trashedCount = ref(0)
const pagination = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

// Selection
const selectedIds = ref<Set<number>>(new Set())
const isAllSelected = computed(() => students.value.length > 0 && selectedIds.value.size === students.value.length)
const isIndeterminate = computed(() => selectedIds.value.size > 0 && !isAllSelected.value)
const toggleSelectAll = () => {
  if (isAllSelected.value) selectedIds.value.clear()
  else students.value.forEach(s => selectedIds.value.add(s.id))
}
const toggleSelect = (id: number) => {
  if (selectedIds.value.has(id)) selectedIds.value.delete(id)
  else selectedIds.value.add(id)
}

// ── Fetch ──
let debounceTimer: ReturnType<typeof setTimeout>
const debouncedFetch = () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => loadPage(1), 300) }

async function loadPage(page = 1) {
  loading.value = true
  selectedIds.value.clear()
  try {
    const fn = isTrashed.value ? studentService.trashed : studentService.index
    const res = await fn({ page, per_page: pagination.per_page, search: search.value || undefined })
    students.value = res.data.data
    Object.assign(pagination, res.data.pagination)
  } catch { students.value = [] }
  finally { loading.value = false }
}

async function fetchTrashedCount() {
  try {
    const res = await studentService.trashed({ per_page: 1 })
    trashedCount.value = res.data.pagination?.total ?? 0
  } catch { trashedCount.value = 0 }
}

function switchTab(trashed: boolean) {
  isTrashed.value = trashed
  search.value = ''
  loadPage(1)
  if (!trashed) fetchTrashedCount()
}

// ── Detail Modal ──
const showDetail = ref(false)
const detailLoading = ref(false)
const detailStudent = ref<any>(null)

async function openDetail(s: Student) {
  showDetail.value = true
  detailLoading.value = true
  detailStudent.value = null
  try {
    const res = await studentService.show(s.id)
    detailStudent.value = res.data.data
  } catch { detailStudent.value = null }
  finally { detailLoading.value = false }
}

function getThumbnail(path: string): string {
  if (!path) return ''
  if (path.startsWith('http') || path.startsWith('/storage')) return path
  return `/storage/${path}`
}

// ── Create / Edit Modal ──
const showModal = ref(false)
const editingStudent = ref<Student | null>(null)
const submitting = ref(false)
const formError = ref('')
const form = reactive({ name: '', email: '', password: '', date_of_birth: '' })

function openCreate() {
  editingStudent.value = null
  Object.assign(form, { name: '', email: '', password: '', date_of_birth: '' })
  formError.value = ''
  showModal.value = true
}

function openEdit(s: Student) {
  editingStudent.value = s
  Object.assign(form, { name: s.name, email: s.email, password: '', date_of_birth: s.date_of_birth || '' })
  formError.value = ''
  showModal.value = true
}

async function submitForm() {
  submitting.value = true
  formError.value = ''
  try {
    const data: Record<string, unknown> = { name: form.name, email: form.email }
    if (form.date_of_birth) data.date_of_birth = form.date_of_birth
    if (!editingStudent.value) data.password = form.password

    if (editingStudent.value) {
      await studentService.update(editingStudent.value.id, data)
      toast.success('Cập nhật học viên thành công!')
    } else {
      await studentService.store(data)
      toast.success('Thêm học viên thành công!')
    }
    showModal.value = false
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch (err: any) {
    formError.value = err.response?.data?.message || 'Có lỗi xảy ra.'
  } finally { submitting.value = false }
}

// ── Delete ──
const showDeleteModal = ref(false)
const deletingStudent = ref<Student | null>(null)
const deleteLoading = ref(false)
const restoringId = ref<number | null>(null)

function confirmDelete(s: Student) { deletingStudent.value = s; showDeleteModal.value = true }
function confirmForceDelete(s: Student) { deletingStudent.value = s; showDeleteModal.value = true }

async function doDelete() {
  if (!deletingStudent.value) return
  deleteLoading.value = true
  try {
    if (isTrashed.value) await studentService.forceDelete(deletingStudent.value.id)
    else await studentService.destroy(deletingStudent.value.id)
    toast.success('Xoá thành công!')
    showDeleteModal.value = false
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch (err: any) { toast.error(err.response?.data?.message || 'Xoá thất bại.') }
  finally { deleteLoading.value = false }
}

async function doRestore(id: number) {
  restoringId.value = id
  try {
    await studentService.restore(id)
    toast.success('Khôi phục thành công!')
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch { toast.error('Khôi phục thất bại.') }
  finally { restoringId.value = null }
}

// ── Bulk ──
const bulkLoading = ref(false)

async function doBulkDelete() {
  bulkLoading.value = true
  try {
    await studentService.bulkDelete([...selectedIds.value])
    toast.success('Xoá hàng loạt thành công!')
    selectedIds.value.clear()
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch { toast.error('Xoá hàng loạt thất bại.') }
  finally { bulkLoading.value = false }
}

async function doBulkRestore() {
  bulkLoading.value = true
  try {
    await studentService.bulkRestore([...selectedIds.value])
    toast.success('Khôi phục hàng loạt thành công!')
    selectedIds.value.clear()
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch { toast.error('Khôi phục hàng loạt thất bại.') }
  finally { bulkLoading.value = false }
}

// ── Init ──
onMounted(() => { loadPage(); fetchTrashedCount() })
</script>
