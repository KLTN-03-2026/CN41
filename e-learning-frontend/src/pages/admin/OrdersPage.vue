<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Quản lý đơn hàng</h1>
      <div class="flex items-center gap-2 text-sm text-gray-500">
        <span class="font-medium text-gray-800">{{ activePagination?.total ?? 0 }}</span> đơn hàng
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-gray-100 p-4 mb-6">
      <div class="flex flex-wrap gap-3">
        <!-- Search -->
        <div class="flex-1 min-w-[200px]">
          <input
            v-model="filters.search"
            @input="debouncedFetch"
            type="text"
            placeholder="Tìm mã đơn, tên/email học viên..."
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-transparent"
          />
        </div>

        <!-- Status -->
        <select
          v-model="filters.status"
          @change="activeSetPage(1)"
          class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
        >
          <option value="">Tất cả trạng thái</option>
          <option value="pending">Chờ thanh toán</option>
          <option value="paid">Đã thanh toán</option>
          <option value="failed">Thất bại</option>
          <option value="cancelled">Đã huỷ</option>
          <option value="refunded">Đã hoàn tiền</option>
        </select>

        <!-- Date range -->
        <input
          v-model="filters.from"
          @change="activeSetPage(1)"
          type="date"
          class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
        />
        <input
          v-model="filters.to"
          @change="activeSetPage(1)"
          type="date"
          class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
        />

        <!-- Reset -->
        <button
          @click="resetFilters"
          class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
        >
          Xoá bộ lọc
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-20">
      <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 text-left">
              <th class="px-4 py-3 font-medium text-gray-500">Mã đơn</th>
              <th class="px-4 py-3 font-medium text-gray-500">Học viên</th>
              <th class="px-4 py-3 font-medium text-gray-500">Khóa học</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-right">Tổng tiền</th>
              <th class="px-4 py-3 font-medium text-gray-500">Trạng thái</th>
              <th class="px-4 py-3 font-medium text-gray-500">Ngày tạo</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="orders.length === 0">
              <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                Không có đơn hàng nào.
              </td>
            </tr>
            <tr
              v-for="order in orders"
              :key="order.id"
              class="border-t border-gray-50 hover:bg-gray-50/50 transition-colors"
            >
              <td class="px-4 py-3">
                <span class="font-medium text-gray-800 text-xs">{{ order.order_code }}</span>
              </td>
              <td class="px-4 py-3">
                <div>
                  <p class="text-gray-800 text-sm">{{ order.student?.name || '—' }}</p>
                  <p class="text-gray-400 text-xs">{{ order.student?.email }}</p>
                </div>
              </td>
              <td class="px-4 py-3">
                <div class="max-w-[200px]">
                  <p
                    v-for="item in order.items?.slice(0, 2)"
                    :key="item.id"
                    class="text-gray-700 text-xs truncate"
                  >
                    {{ item.course?.name }}
                  </p>
                  <p v-if="order.items?.length > 2" class="text-gray-400 text-xs">
                    +{{ order.items.length - 2 }} khóa học khác
                  </p>
                </div>
              </td>
              <td class="px-4 py-3 text-right">
                <span class="font-semibold text-gray-800">{{ formatCurrency(Number(order.total_amount)) }}</span>
              </td>
              <td class="px-4 py-3">
                <OrderStatusBadge :status="order.status" />
              </td>
              <td class="px-4 py-3 text-gray-500 text-xs">
                {{ formatDate(order.created_at) }}
              </td>
              <td class="px-4 py-3 text-center">
                <div class="flex justify-center gap-1">
                  <button
                    @click="openDetail(order.id)"
                    class="p-1.5 text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors"
                    title="Chi tiết"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                  </button>
                  <button
                    @click="deleteOrder.confirm(order)"
                    class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                    title="Xoá"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="activePagination && activePagination.last_page > 1" class="flex justify-between items-center px-4 py-3 border-t border-gray-100">
        <span class="text-xs text-gray-500">
          Hiển thị {{ activePagination.from }}–{{ activePagination.to }} / {{ activePagination.total }}
        </span>
        <div class="flex gap-1">
          <button
            v-for="page in activePagination.last_page"
            :key="page"
            @click="activeSetPage(page)"
            class="w-8 h-8 rounded-lg text-xs font-medium transition-colors"
            :class="page === activePagination.current_page
              ? 'bg-blue-500 text-white'
              : 'text-gray-600 hover:bg-gray-100'"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>

    <!-- Confirm Delete Modal -->
    <ConfirmModal
      :show="deleteOrder.isOpen.value"
      title="Xác nhận xoá"
      :loading="deleteOrder.loading.value"
      confirm-text="Xoá"
      loading-text="Đang xoá..."
      @cancel="deleteOrder.cancel()"
      @confirm="deleteOrder.execute()"
    >
      <p>
        Bạn có chắc muốn xoá đơn hàng
        <strong class="text-gray-800 dark:text-white/90">{{ deleteOrder.target.value?.order_code }}</strong>?
      </p>
    </ConfirmModal>

    <!-- Detail Modal -->
    <OrderDetailModal
      :show="showDetail"
      :order-id="selectedOrderId"
      @close="showDetail = false"
      @updated="loadPage(activeCurrentPage)"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { orderService } from '@/services/order.service'
import { formatCurrency } from '@/utils/formatCurrency'
import OrderStatusBadge from '@/components/common/OrderStatusBadge.vue'
import OrderDetailModal from '@/components/admin/OrderDetailModal.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { usePagination } from '@/composables/usePagination'
import { useDebounceSearch } from '@/composables/useDebounceSearch'
import { useDeleteConfirm } from '@/composables/useDeleteConfirm'

const toast = useToast()

const loading = ref(true)
const orders = ref<any[]>([])

const filters = reactive({
  search: '',
  status: '',
  from: '',
  to: '',
})

const showDetail = ref(false)
const selectedOrderId = ref<number | null>(null)

function formatDate(iso: string) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

// ── Fetch with usePagination ──────────────────────────────────
async function loadPage(page = 1) {
  loading.value = true
  try {
    const params: any = { page, per_page: 15 }
    if (filters.search) params.search = filters.search
    if (filters.status) params.status = filters.status
    if (filters.from)   params.from = filters.from
    if (filters.to)     params.to = filters.to

    const res = await orderService.adminList(params)
    orders.value = res.data.data
    activeUpdatePagination(res.data.pagination)
  } catch {
    toast.error('Không thể tải danh sách đơn hàng.')
  } finally {
    loading.value = false
  }
}

const {
  pagination: activePagination,
  currentPage: activeCurrentPage,
  setPage: activeSetPage,
  updatePagination: activeUpdatePagination,
} = usePagination(loadPage, 15)

// ── Debounce search ───────────────────────────────────────────
const { debounce: debouncedFetch } = useDebounceSearch(() => activeSetPage(1))

// ── Delete confirmation ───────────────────────────────────────
const deleteOrder = useDeleteConfirm({
  async onConfirm(order: any) {
    await orderService.adminDelete(order.id)
    toast.success('Đơn hàng đã được xoá.')
    loadPage(activeCurrentPage.value)
  },
})

function resetFilters() {
  filters.search = ''
  filters.status = ''
  filters.from = ''
  filters.to = ''
  activeSetPage(1)
}

function openDetail(id: number) {
  selectedOrderId.value = id
  showDetail.value = true
}

onMounted(() => loadPage())
</script>
