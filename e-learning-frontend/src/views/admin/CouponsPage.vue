<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">Mã giảm giá</h2>
        <p class="text-sm text-gray-500 mt-0.5">Quản lý tất cả mã giảm giá</p>
      </div>
      <button
        v-if="!isTrashed"
        v-permission="'coupons.create'"
        @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors"
      >
        <PlusIcon class="w-4 h-4" /> Thêm mã
      </button>
    </div>

    <!-- Tabs + Search + Filter -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
      <div class="flex gap-1 p-1 bg-gray-100 rounded-lg">
        <button
          @click="switchTab(false)"
          :class="!isTrashed ? 'bg-white shadow-sm font-medium' : ''"
          class="px-3 py-1.5 text-sm rounded-md text-gray-600 transition-colors"
        >
          Tất cả
        </button>
        <button
          @click="switchTab(true)"
          :class="isTrashed ? 'bg-white shadow-sm font-medium' : ''"
          class="px-3 py-1.5 text-sm rounded-md text-gray-600 transition-colors"
        >
          Thùng rác
          <span v-if="trashedCount" class="ml-1 text-xs text-red-500">({{ trashedCount }})</span>
        </button>
      </div>
      <div class="relative flex-1 max-w-xs">
        <input
          v-model="search"
          @input="debouncedFetch"
          type="text"
          placeholder="Tìm theo mã..."
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
        v-if="!isTrashed"
        v-model="statusFilter"
        @change="loadPage(1)"
        class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-white text-gray-700 outline-none focus:ring-2 focus:ring-blue-500/20"
      >
        <option value="">Tất cả trạng thái</option>
        <option value="1">Đang hoạt động</option>
        <option value="0">Vô hiệu hoá</option>
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
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Mã</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Loại / Giá trị</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Đã dùng</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Trạng thái</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Thời hạn</th>
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
                  <div class="h-4 w-24 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-20 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-16 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-16 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-28 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-16 bg-gray-100 rounded animate-pulse ml-auto"></div>
                </td>
              </tr>
            </template>
            <template v-else-if="coupons.length">
              <tr v-for="c in coupons" :key="c.id" class="hover:bg-gray-50/50 transition-colors">
                <td class="px-4 py-3">
                  <input
                    type="checkbox"
                    :checked="selectedIds.has(c.id)"
                    @change="toggleSelect(c.id)"
                    class="rounded border-gray-300 text-blue-500"
                  />
                </td>
                <td class="px-4 py-3">
                  <span
                    class="font-mono font-semibold text-blue-600 text-xs bg-blue-50 px-2 py-1 rounded"
                    >{{ c.code }}</span
                  >
                </td>
                <td class="px-4 py-3 text-gray-700 text-xs">
                  <span v-if="c.type === 'fixed'">{{ formatCurrency(Number(c.value)) }}</span>
                  <span v-else
                    >{{ c.value }}%<span v-if="c.max_discount" class="text-gray-400">
                      (max {{ formatCurrency(Number(c.max_discount)) }})</span
                    ></span
                  >
                </td>
                <td class="px-4 py-3 text-xs text-gray-600">
                  {{ c.used_count }}/{{ c.usage_limit ?? '∞' }}
                </td>
                <td class="px-4 py-3">
                  <button
                    v-if="!isTrashed"
                    @click="doToggleStatus(c)"
                    :disabled="togglingId === c.id"
                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 disabled:opacity-50"
                    :class="c.status === 1 ? 'bg-green-500' : 'bg-gray-300'"
                  >
                    <span
                      class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200"
                      :class="c.status === 1 ? 'translate-x-4' : 'translate-x-1'"
                    />
                  </button>
                  <span v-else class="text-xs text-gray-400">{{
                    c.status === 1 ? 'Hoạt động' : 'Vô hiệu'
                  }}</span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-500">
                  <template v-if="c.start_date || c.end_date">
                    <span v-if="c.is_expired" class="text-red-500 font-medium">Hết hạn</span>
                    <span v-else
                      >{{ c.start_date ? formatDate(c.start_date) : '...' }} —
                      {{ c.end_date ? formatDate(c.end_date) : '...' }}</span
                    >
                  </template>
                  <span v-else class="text-gray-400">Không giới hạn</span>
                </td>
                <td class="px-4 py-3 text-right">
                  <div v-if="!isTrashed" class="flex items-center justify-end gap-1">
                    <button
                      v-permission="'coupons.edit'"
                      @click="openEdit(c)"
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
                    </button>
                    <button
                      v-permission="'coupons.delete'"
                      @click="confirmDelete(c)"
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
                  <div v-else class="flex items-center justify-end gap-1">
                    <button
                      @click="doRestore(c.id)"
                      :disabled="restoringId === c.id"
                      class="p-1.5 rounded-lg hover:bg-green-50 text-gray-500 hover:text-green-600 transition-colors disabled:opacity-50"
                      title="Khôi phục"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                      </svg>
                    </button>
                    <button
                      @click="confirmDelete(c)"
                      class="p-1.5 rounded-lg hover:bg-red-50 text-gray-500 hover:text-red-500 transition-colors"
                      title="Xoá vĩnh viễn"
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
                {{ isTrashed ? 'Thùng rác trống.' : 'Chưa có mã giảm giá nào.' }}
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
          @change="loadPage"
        />
      </div>
    </div>

    <!-- Bulk Actions -->
    <div
      v-if="selectedIds.size > 0"
      class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[999999] flex items-center gap-3 px-5 py-3 bg-gray-900 text-white rounded-xl shadow-xl"
    >
      <span class="text-sm"
        >Đã chọn <strong>{{ selectedIds.size }}</strong> mã</span
      >
      <button
        v-if="!isTrashed"
        @click="doBulkDelete"
        :disabled="bulkLoading"
        class="px-3 py-1.5 text-sm bg-red-500 hover:bg-red-600 rounded-lg disabled:opacity-50"
      >
        Xoá
      </button>
      <button
        v-if="isTrashed"
        @click="doBulkRestore"
        :disabled="bulkLoading"
        class="px-3 py-1.5 text-sm bg-green-500 hover:bg-green-600 rounded-lg disabled:opacity-50"
      >
        Khôi phục
      </button>
      <button
        @click="selectedIds.clear()"
        class="px-3 py-1.5 text-sm bg-gray-700 hover:bg-gray-600 rounded-lg"
      >
        Bỏ chọn
      </button>
    </div>

    <!-- Create/Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-[999999] flex items-center justify-center bg-black/50"
      @click.self="showModal = false"
    >
      <div
        class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto"
      >
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
          {{ editingCoupon ? 'Sửa mã giảm giá' : 'Thêm mã giảm giá' }}
        </h3>
        <form @submit.prevent="submitForm" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mã giảm giá *</label>
              <input
                v-model="form.code"
                type="text"
                required
                placeholder="VD: SALE50K"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg uppercase font-mono focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Loại *</label>
              <select
                v-model="form.type"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none"
              >
                <option value="fixed">Cố định (VNĐ)</option>
                <option value="percentage">Phần trăm (%)</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Giá trị *</label>
              <input
                v-model="form.value"
                type="number"
                required
                min="0"
                step="any"
                :placeholder="form.type === 'fixed' ? '50000' : '10'"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none"
              />
            </div>
            <div v-if="form.type === 'percentage'">
              <label class="block text-sm font-medium text-gray-700 mb-1">Giảm tối đa (VNĐ)</label>
              <input
                v-model="form.max_discount"
                type="number"
                min="0"
                placeholder="100000"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none"
              />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Đơn tối thiểu (VNĐ)</label
              >
              <input
                v-model="form.min_order_value"
                type="number"
                min="0"
                placeholder="0"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Giới hạn sử dụng</label>
              <input
                v-model="form.usage_limit"
                type="number"
                min="1"
                placeholder="Không giới hạn"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none"
              />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Ngày bắt đầu</label>
              <input
                v-model="form.start_date"
                type="datetime-local"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Ngày kết thúc</label>
              <input
                v-model="form.end_date"
                type="datetime-local"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none"
              />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
            <textarea
              v-model="form.description"
              rows="2"
              placeholder="Mô tả ngắn..."
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none resize-none"
            ></textarea>
          </div>
          <p v-if="formError" class="text-sm text-red-500">{{ formError }}</p>
          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50"
            >
              Huỷ
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 disabled:opacity-50"
            >
              {{ submitting ? 'Đang lưu...' : 'Lưu' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete Modal -->
    <ConfirmModal
      :show="showDeleteModal"
      :title="isTrashed ? 'Xoá vĩnh viễn' : 'Xác nhận xoá'"
      :loading="deleteLoading"
      :confirm-text="isTrashed ? 'Xoá vĩnh viễn' : 'Xoá'"
      loading-text="Đang xoá..."
      :icon="isTrashed ? 'warning' : undefined"
      @cancel="showDeleteModal = false"
      @confirm="doDelete"
    >
      <p>
        Bạn có chắc muốn xoá mã
        <strong class="text-gray-800 font-mono">{{ deletingCoupon?.code }}</strong
        >?
        <span v-if="!isTrashed" class="block mt-1 text-xs text-gray-400"
          >Mã sẽ được chuyển vào thùng rác.</span
        >
        <span v-else class="block mt-1 text-xs text-red-400"
          >Hành động này không thể hoàn tác!</span
        >
      </p>
    </ConfirmModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { couponService } from '@/services/coupon.service'
import type { Coupon } from '@/services/coupon.service'
import { formatDate } from '@/utils/formatDate'
import { formatCurrency } from '@/utils/formatCurrency'
import { PlusIcon } from '@/components/icons'
import PaginationBar from '@/components/common/PaginationBar.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

const coupons = ref<Coupon[]>([])
const loading = ref(false)
const search = ref('')
const statusFilter = ref('')
const isTrashed = ref(false)
const trashedCount = ref(0)
const pagination = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

// Selection
const selectedIds = ref<Set<number>>(new Set())
const isAllSelected = computed(
  () => coupons.value.length > 0 && selectedIds.value.size === coupons.value.length,
)
const isIndeterminate = computed(() => selectedIds.value.size > 0 && !isAllSelected.value)
const toggleSelectAll = () => {
  if (isAllSelected.value) selectedIds.value.clear()
  else coupons.value.forEach((c) => selectedIds.value.add(c.id))
}
const toggleSelect = (id: number) => {
  if (selectedIds.value.has(id)) selectedIds.value.delete(id)
  else selectedIds.value.add(id)
}

// Fetch
let debounceTimer: ReturnType<typeof setTimeout>
const debouncedFetch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => loadPage(1), 300)
}

async function loadPage(page = 1) {
  loading.value = true
  selectedIds.value.clear()
  try {
    const params: Record<string, unknown> = {
      page,
      per_page: pagination.per_page,
      search: search.value || undefined,
    }
    if (!isTrashed.value && statusFilter.value !== '') params.status = statusFilter.value
    const fn = isTrashed.value ? couponService.trashed : couponService.index
    const res = await fn(params)
    coupons.value = res.data.data
    Object.assign(pagination, res.data.pagination)
  } catch {
    coupons.value = []
  } finally {
    loading.value = false
  }
}

async function fetchTrashedCount() {
  try {
    const res = await couponService.trashed({ per_page: 1 })
    trashedCount.value = res.data.pagination?.total ?? 0
  } catch {
    trashedCount.value = 0
  }
}

function switchTab(trashed: boolean) {
  isTrashed.value = trashed
  search.value = ''
  statusFilter.value = ''
  loadPage(1)
  if (!trashed) fetchTrashedCount()
}

// Toggle Status
const togglingId = ref<number | null>(null)
async function doToggleStatus(c: Coupon) {
  togglingId.value = c.id
  try {
    const res = await couponService.toggleStatus(c.id)
    const idx = coupons.value.findIndex((x) => x.id === c.id)
    if (idx !== -1) coupons.value[idx] = res.data.data
    toast.success(res.data.message)
  } catch {
    toast.error('Không thể thay đổi trạng thái.')
  } finally {
    togglingId.value = null
  }
}

// Create / Edit Modal
const showModal = ref(false)
const editingCoupon = ref<Coupon | null>(null)
const submitting = ref(false)
const formError = ref('')
const form = reactive({
  code: '',
  type: 'fixed' as 'fixed' | 'percentage',
  value: '',
  min_order_value: '',
  max_discount: '',
  usage_limit: '',
  start_date: '',
  end_date: '',
  description: '',
})

function resetForm() {
  Object.assign(form, {
    code: '',
    type: 'fixed',
    value: '',
    min_order_value: '',
    max_discount: '',
    usage_limit: '',
    start_date: '',
    end_date: '',
    description: '',
  })
}

function formatDateForInput(iso: string | null): string {
  if (!iso) return ''
  return new Date(iso).toISOString().slice(0, 16)
}

function openCreate() {
  editingCoupon.value = null
  resetForm()
  formError.value = ''
  showModal.value = true
}

function openEdit(c: Coupon) {
  editingCoupon.value = c
  Object.assign(form, {
    code: c.code,
    type: c.type,
    value: c.value,
    min_order_value: c.min_order_value ?? '',
    max_discount: c.max_discount ?? '',
    usage_limit: c.usage_limit ?? '',
    start_date: formatDateForInput(c.start_date),
    end_date: formatDateForInput(c.end_date),
    description: c.description ?? '',
  })
  formError.value = ''
  showModal.value = true
}

async function submitForm() {
  submitting.value = true
  formError.value = ''
  try {
    const data: Record<string, unknown> = {
      code: form.code,
      type: form.type,
      value: Number(form.value),
      // Luôn gửi các field optional — null để clear giá trị cũ khi edit
      min_order_value: form.min_order_value !== '' ? Number(form.min_order_value) : null,
      max_discount: form.max_discount !== '' ? Number(form.max_discount) : null,
      usage_limit: form.usage_limit !== '' ? Number(form.usage_limit) : null,
      start_date: form.start_date || null,
      end_date: form.end_date || null,
      description: form.description || null,
    }

    if (editingCoupon.value) {
      await couponService.update(editingCoupon.value.id, data)
      toast.success('Cập nhật mã giảm giá thành công!')
    } else {
      await couponService.store(data)
      toast.success('Thêm mã giảm giá thành công!')
    }
    showModal.value = false
    loadPage(pagination.current_page)
  } catch (err) {
    const errData = (
      err as { response?: { data?: { message?: string; errors?: Record<string, unknown[]> } } }
    ).response?.data
    // Hiển thị lỗi field-level đầu tiên nếu có
    if (errData?.errors) {
      const firstFieldErrors = Object.values(errData.errors)[0]
      formError.value = Array.isArray(firstFieldErrors)
        ? (firstFieldErrors[0] as string)
        : errData.message
    } else {
      formError.value = errData?.message || 'Có lỗi xảy ra.'
    }
  } finally {
    submitting.value = false
  }
}

// Delete
const showDeleteModal = ref(false)
const deletingCoupon = ref<Coupon | null>(null)
const deleteLoading = ref(false)
const restoringId = ref<number | null>(null)

function confirmDelete(c: Coupon) {
  deletingCoupon.value = c
  showDeleteModal.value = true
}

async function doDelete() {
  if (!deletingCoupon.value) return
  deleteLoading.value = true
  try {
    if (isTrashed.value) await couponService.forceDelete(deletingCoupon.value.id)
    else await couponService.destroy(deletingCoupon.value.id)
    toast.success('Xoá thành công!')
    showDeleteModal.value = false
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch (err) {
    toast.error(
      (err as { response?: { data?: { message?: string } } }).response?.data?.message ||
        'Xoá thất bại.',
    )
  } finally {
    deleteLoading.value = false
  }
}

async function doRestore(id: number) {
  restoringId.value = id
  try {
    await couponService.restore(id)
    toast.success('Khôi phục thành công!')
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch {
    toast.error('Khôi phục thất bại.')
  } finally {
    restoringId.value = null
  }
}

// Bulk
const bulkLoading = ref(false)

async function doBulkDelete() {
  bulkLoading.value = true
  try {
    await couponService.bulkDelete([...selectedIds.value])
    toast.success('Xoá hàng loạt thành công!')
    selectedIds.value.clear()
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch {
    toast.error('Xoá hàng loạt thất bại.')
  } finally {
    bulkLoading.value = false
  }
}

async function doBulkRestore() {
  bulkLoading.value = true
  try {
    await couponService.bulkRestore([...selectedIds.value])
    toast.success('Khôi phục hàng loạt thành công!')
    selectedIds.value.clear()
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch {
    toast.error('Khôi phục hàng loạt thất bại.')
  } finally {
    bulkLoading.value = false
  }
}

onMounted(() => {
  loadPage()
  fetchTrashedCount()
})
</script>
