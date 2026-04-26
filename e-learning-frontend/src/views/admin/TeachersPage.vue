<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Giảng viên</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Quản lý tất cả giảng viên</p>
      </div>
      <button v-if="!isTrashed" @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors">
        <PlusIcon class="w-4 h-4" /> Thêm giảng viên
      </button>
    </div>

    <!-- Tabs + Search + Status Filter -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
      <div class="flex gap-1 p-1 bg-gray-100 dark:bg-white/5 rounded-lg">
        <button @click="switchTab(false)" :class="!isTrashed ? 'bg-white dark:bg-white/10 shadow-sm font-medium' : ''"
          class="px-3 py-1.5 text-sm rounded-md text-gray-600 dark:text-gray-300 transition-colors">Tất cả</button>
        <button @click="switchTab(true)" :class="isTrashed ? 'bg-white dark:bg-white/10 shadow-sm font-medium' : ''"
          class="px-3 py-1.5 text-sm rounded-md text-gray-600 dark:text-gray-300 transition-colors">
          Thùng rác <span v-if="trashedCount" class="ml-1 text-xs text-red-500">({{ trashedCount }})</span>
        </button>
      </div>
      <div class="relative flex-1 max-w-xs">
        <input v-model="search" @input="debouncedFetch" type="text" placeholder="Tìm theo tên..."
          class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" />
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </div>
      <select v-if="!isTrashed" v-model="statusFilter" @change="loadPage(1)"
        class="px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 outline-none focus:ring-2 focus:ring-blue-500/20">
        <option value="">Tất cả trạng thái</option>
        <option value="1">Đang hoạt động</option>
        <option value="0">Vô hiệu hoá</option>
      </select>
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
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">Giảng viên</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">Kinh nghiệm</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">Trạng thái</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">Ngày tạo</th>
              <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <template v-if="loading">
              <tr v-for="i in 5" :key="i">
                <td class="px-4 py-3"><div class="w-4 h-4 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div></td>
                <td class="px-4 py-3"><div class="h-4 w-32 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div></td>
                <td class="px-4 py-3"><div class="h-4 w-20 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div></td>
                <td class="px-4 py-3"><div class="h-4 w-20 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div></td>
                <td class="px-4 py-3"><div class="h-4 w-24 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div></td>
                <td class="px-4 py-3"><div class="h-4 w-16 bg-gray-100 dark:bg-gray-800 rounded animate-pulse ml-auto"></div></td>
              </tr>
            </template>

            <template v-else-if="teachers.length">
              <tr v-for="t in teachers" :key="t.id" class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                <td class="px-4 py-3">
                  <input type="checkbox" :checked="selectedIds.has(t.id)" @change="toggleSelect(t.id)"
                    class="rounded border-gray-300 dark:border-gray-600 text-blue-500 focus:ring-blue-500/20" />
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <img v-if="t.image" :src="t.image" :alt="t.name" class="w-8 h-8 rounded-full object-cover shrink-0" />
                    <div v-else class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-500/10 flex items-center justify-center text-purple-600 dark:text-purple-400 font-medium text-xs shrink-0">
                      {{ t.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <p class="font-medium text-gray-800 dark:text-white/90 truncate">{{ t.name }}</p>
                      <p class="text-xs text-gray-400 truncate">{{ t.slug }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">{{ t.exp || '—' }}</td>
                <td class="px-4 py-3">
                  <button v-if="!isTrashed" @click="doToggleStatus(t)" :disabled="togglingId === t.id"
                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 focus:outline-none disabled:opacity-50"
                    :class="t.status === 1 ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'">
                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200"
                      :class="t.status === 1 ? 'translate-x-4' : 'translate-x-1'" />
                  </button>
                  <span v-else class="text-xs text-gray-400">{{ t.status === 1 ? 'Hoạt động' : 'Vô hiệu' }}</span>
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ formatDate(t.created_at) }}</td>
                <td class="px-4 py-3 text-right">
                  <div v-if="!isTrashed" class="flex items-center justify-end gap-1">
                    <button @click="openEdit(t)" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500 dark:text-gray-400 transition-colors" title="Sửa">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button @click="confirmDelete(t)" class="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 text-gray-500 dark:text-gray-400 hover:text-red-500 transition-colors" title="Xoá">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                  <div v-else class="flex items-center justify-end gap-1">
                    <button @click="doRestore(t.id)" :disabled="restoringId === t.id"
                      class="p-1.5 rounded-lg hover:bg-green-50 dark:hover:bg-green-500/10 text-gray-500 hover:text-green-600 transition-colors disabled:opacity-50" title="Khôi phục">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                    <button @click="confirmDelete(t)" class="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 text-gray-500 hover:text-red-500 transition-colors" title="Xoá vĩnh viễn">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </template>

            <tr v-else>
              <td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">
                {{ isTrashed ? 'Thùng rác trống.' : 'Chưa có giảng viên nào.' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination.last_page > 1" class="flex justify-end px-4 py-3 border-t border-gray-100 dark:border-gray-700">
        <PaginationBar :current-page="pagination.current_page" :last-page="pagination.last_page" @change="loadPage" />
      </div>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedIds.size > 0"
      class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[999999] flex items-center gap-3 px-5 py-3 bg-gray-900 dark:bg-gray-800 text-white rounded-xl shadow-xl">
      <span class="text-sm">Đã chọn <strong>{{ selectedIds.size }}</strong> giảng viên</span>
      <button v-if="!isTrashed" @click="doBulkDelete" :disabled="bulkLoading"
        class="px-3 py-1.5 text-sm bg-red-500 hover:bg-red-600 rounded-lg transition-colors disabled:opacity-50">Xoá</button>
      <button v-if="isTrashed" @click="doBulkRestore" :disabled="bulkLoading"
        class="px-3 py-1.5 text-sm bg-green-500 hover:bg-green-600 rounded-lg transition-colors disabled:opacity-50">Khôi phục</button>
      <button @click="selectedIds.clear()" class="px-3 py-1.5 text-sm bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors">Bỏ chọn</button>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-[999999] flex items-center justify-center bg-black/50" @click.self="showModal = false">
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 mb-4">{{ editingTeacher ? 'Sửa giảng viên' : 'Thêm giảng viên' }}</h3>
        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tên giảng viên *</label>
            <input v-model="form.name" type="text" required
              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mô tả</label>
            <textarea v-model="form.description" rows="3"
              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none resize-none"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kinh nghiệm</label>
            <input v-model="form.exp" type="text" placeholder="VD: 5 năm..."
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

    <!-- Confirm Delete Modal -->
    <ConfirmModal :show="showDeleteModal" :title="isTrashed ? 'Xoá vĩnh viễn' : 'Xác nhận xoá'" :loading="deleteLoading"
      :confirm-text="isTrashed ? 'Xoá vĩnh viễn' : 'Xoá'" loading-text="Đang xoá..." :icon="isTrashed ? 'warning' : undefined"
      @cancel="showDeleteModal = false" @confirm="doDelete">
      <p>Bạn có chắc muốn xoá giảng viên <strong class="text-gray-800 dark:text-white/90">{{ deletingTeacher?.name }}</strong>?
        <span v-if="!isTrashed" class="block mt-1 text-xs text-gray-400">Giảng viên sẽ được chuyển vào thùng rác.</span>
        <span v-else class="block mt-1 text-xs text-red-400">Hành động này không thể hoàn tác!</span>
      </p>
    </ConfirmModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { teacherService } from '@/services/teacher.service'
import type { Teacher } from '@/types'
import { formatDate } from '@/utils/formatDate'
import { PlusIcon } from '@/components/icons'
import PaginationBar from '@/components/common/PaginationBar.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

const teachers = ref<Teacher[]>([])
const loading = ref(false)
const search = ref('')
const statusFilter = ref('')
const isTrashed = ref(false)
const trashedCount = ref(0)
const pagination = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

// Selection
const selectedIds = ref<Set<number>>(new Set())
const isAllSelected = computed(() => teachers.value.length > 0 && selectedIds.value.size === teachers.value.length)
const isIndeterminate = computed(() => selectedIds.value.size > 0 && !isAllSelected.value)
const toggleSelectAll = () => {
  if (isAllSelected.value) selectedIds.value.clear()
  else teachers.value.forEach(t => selectedIds.value.add(t.id))
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
    const params: Record<string, unknown> = { page, per_page: pagination.per_page, search: search.value || undefined }
    if (!isTrashed.value && statusFilter.value !== '') params.status = statusFilter.value
    const fn = isTrashed.value ? teacherService.trashed : teacherService.index
    const res = await fn(params)
    teachers.value = res.data.data
    Object.assign(pagination, res.data.pagination)
  } catch { teachers.value = [] }
  finally { loading.value = false }
}

async function fetchTrashedCount() {
  try {
    const res = await teacherService.trashed({ per_page: 1 })
    trashedCount.value = res.data.pagination?.total ?? 0
  } catch { trashedCount.value = 0 }
}

function switchTab(trashed: boolean) {
  isTrashed.value = trashed
  search.value = ''
  statusFilter.value = ''
  loadPage(1)
  if (!trashed) fetchTrashedCount()
}

// ── Toggle Status ──
const togglingId = ref<number | null>(null)
async function doToggleStatus(t: Teacher) {
  togglingId.value = t.id
  try {
    const res = await teacherService.toggleStatus(t.id)
    const idx = teachers.value.findIndex(x => x.id === t.id)
    if (idx !== -1) teachers.value[idx] = res.data.data
    toast.success(res.data.message)
  } catch { toast.error('Không thể thay đổi trạng thái.') }
  finally { togglingId.value = null }
}

// ── Create / Edit Modal ──
const showModal = ref(false)
const editingTeacher = ref<Teacher | null>(null)
const submitting = ref(false)
const formError = ref('')
const form = reactive({ name: '', description: '', exp: '' })

function openCreate() {
  editingTeacher.value = null
  Object.assign(form, { name: '', description: '', exp: '' })
  formError.value = ''
  showModal.value = true
}

function openEdit(t: Teacher) {
  editingTeacher.value = t
  Object.assign(form, { name: t.name, description: (t as any).description || '', exp: (t as any).exp || '' })
  formError.value = ''
  showModal.value = true
}

async function submitForm() {
  submitting.value = true
  formError.value = ''
  try {
    const data: Record<string, unknown> = { name: form.name }
    if (form.description) data.description = form.description
    if (form.exp) data.exp = form.exp

    if (editingTeacher.value) {
      await teacherService.update(editingTeacher.value.id, data)
      toast.success('Cập nhật giảng viên thành công!')
    } else {
      await teacherService.store(data)
      toast.success('Thêm giảng viên thành công!')
    }
    showModal.value = false
    loadPage(pagination.current_page)
  } catch (err: any) {
    formError.value = err.response?.data?.message || 'Có lỗi xảy ra.'
  } finally { submitting.value = false }
}

// ── Delete ──
const showDeleteModal = ref(false)
const deletingTeacher = ref<Teacher | null>(null)
const deleteLoading = ref(false)
const restoringId = ref<number | null>(null)

function confirmDelete(t: Teacher) { deletingTeacher.value = t; showDeleteModal.value = true }

async function doDelete() {
  if (!deletingTeacher.value) return
  deleteLoading.value = true
  try {
    if (isTrashed.value) await teacherService.forceDelete(deletingTeacher.value.id)
    else await teacherService.destroy(deletingTeacher.value.id)
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
    await teacherService.restore(id)
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
    await teacherService.bulkDelete([...selectedIds.value])
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
    await teacherService.bulkRestore([...selectedIds.value])
    toast.success('Khôi phục hàng loạt thành công!')
    selectedIds.value.clear()
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch { toast.error('Khôi phục hàng loạt thất bại.') }
  finally { bulkLoading.value = false }
}

onMounted(() => { loadPage(); fetchTrashedCount() })
</script>
