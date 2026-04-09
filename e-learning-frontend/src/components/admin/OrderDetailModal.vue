<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$emit('close')"></div>

      <!-- Modal -->
      <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] overflow-y-auto">
        <!-- Header -->
        <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between z-10 rounded-t-2xl">
          <h2 class="text-lg font-bold text-gray-900">Chi tiết đơn hàng</h2>
          <button @click="$emit('close')" class="p-1.5 hover:bg-gray-100 rounded-lg transition-colors">
            <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-16">
          <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </div>

        <template v-else-if="order">
          <div class="p-6 space-y-6">
            <!-- Order info -->
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-500">Mã đơn hàng</span>
                <p class="font-medium text-gray-800 mt-0.5">{{ order.order_code }}</p>
              </div>
              <div>
                <span class="text-gray-500">Trạng thái</span>
                <div class="mt-1"><OrderStatusBadge :status="order.status" /></div>
              </div>
              <div>
                <span class="text-gray-500">Học viên</span>
                <p class="font-medium text-gray-800 mt-0.5">{{ order.student?.name || '—' }}</p>
                <p class="text-xs text-gray-400">{{ order.student?.email }}</p>
              </div>
              <div>
                <span class="text-gray-500">Phương thức</span>
                <p class="font-medium text-gray-800 mt-0.5 uppercase">{{ order.payment_method }}</p>
              </div>
              <div>
                <span class="text-gray-500">Ngày tạo</span>
                <p class="font-medium text-gray-800 mt-0.5">{{ formatDate(order.created_at) }}</p>
              </div>
              <div>
                <span class="text-gray-500">Ngày thanh toán</span>
                <p class="font-medium text-gray-800 mt-0.5">{{ order.paid_at ? formatDate(order.paid_at) : '—' }}</p>
              </div>
            </div>

            <!-- Items -->
            <div>
              <h3 class="text-sm font-semibold text-gray-700 mb-3">Khóa học ({{ order.items?.length || 0 }})</h3>
              <div class="space-y-2">
                <div
                  v-for="item in order.items"
                  :key="item.id"
                  class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl"
                >
                  <div class="w-14 h-9 rounded-lg overflow-hidden shrink-0 bg-gray-200">
                    <img v-if="item.course?.thumbnail" :src="item.course.thumbnail" class="w-full h-full object-cover" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ item.course?.name }}</p>
                  </div>
                  <div class="text-right shrink-0">
                    <p class="text-sm font-medium text-gray-800">{{ formatCurrency(Number(item.final_price)) }}</p>
                    <p v-if="item.sale_price" class="text-xs text-gray-400 line-through">{{ formatCurrency(Number(item.price)) }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Financial summary -->
            <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">Tạm tính</span>
                <span class="text-gray-800">{{ formatCurrency(Number(order.subtotal)) }}</span>
              </div>
              <div v-if="Number(order.discount_amount) > 0" class="flex justify-between">
                <span class="text-gray-500">Giảm giá</span>
                <span class="text-green-600">-{{ formatCurrency(Number(order.discount_amount)) }}</span>
              </div>
              <hr class="border-gray-200" />
              <div class="flex justify-between font-semibold text-base">
                <span class="text-gray-900">Tổng cộng</span>
                <span class="text-blue-600">{{ formatCurrency(Number(order.total_amount)) }}</span>
              </div>
            </div>

            <!-- Transactions -->
            <div v-if="order.transactions?.length">
              <h3 class="text-sm font-semibold text-gray-700 mb-3">Lịch sử giao dịch</h3>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-gray-500 border-b border-gray-100">
                      <th class="pb-2 font-medium">Mã GD</th>
                      <th class="pb-2 font-medium">Cổng</th>
                      <th class="pb-2 font-medium">Số tiền</th>
                      <th class="pb-2 font-medium">Trạng thái</th>
                      <th class="pb-2 font-medium">Thời gian</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="tx in order.transactions"
                      :key="tx.id"
                      class="border-b border-gray-50"
                    >
                      <td class="py-2 text-gray-800">{{ tx.transaction_code || '—' }}</td>
                      <td class="py-2 text-gray-600 uppercase">{{ tx.gateway }}</td>
                      <td class="py-2 text-gray-800">{{ formatCurrency(Number(tx.amount)) }}</td>
                      <td class="py-2">
                        <span
                          class="text-xs px-2 py-0.5 rounded-full font-medium"
                          :class="{
                            'bg-emerald-100 text-emerald-700': tx.status === 'success',
                            'bg-amber-100 text-amber-700': tx.status === 'pending',
                            'bg-red-100 text-red-700': tx.status === 'failed',
                          }"
                        >{{ tx.status }}</span>
                      </td>
                      <td class="py-2 text-gray-500 text-xs">{{ formatDate(tx.created_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Admin update status -->
            <div class="border-t border-gray-100 pt-4">
              <h3 class="text-sm font-semibold text-gray-700 mb-3">Cập nhật trạng thái</h3>
              <div class="flex gap-2">
                <select v-model="newStatus" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                  <option value="pending">Chờ thanh toán</option>
                  <option value="paid">Đã thanh toán</option>
                  <option value="failed">Thất bại</option>
                  <option value="cancelled">Đã huỷ</option>
                  <option value="refunded">Đã hoàn tiền</option>
                </select>
                <button
                  @click="handleUpdateStatus"
                  :disabled="updating || newStatus === order.status"
                  class="px-4 py-2 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ updating ? 'Đang lưu...' : 'Lưu' }}
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { ordersApi } from '@/api/ordersApi'
import { formatCurrency } from '@/utils/formatCurrency'
import OrderStatusBadge from '@/components/common/OrderStatusBadge.vue'

const props = defineProps<{
  show: boolean
  orderId: number | null
}>()

const emit = defineEmits<{
  close: []
  updated: []
}>()

const toast = useToast()
const loading = ref(false)
const order = ref<any>(null)
const newStatus = ref('')
const updating = ref(false)

function formatDate(iso: string) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

watch(() => props.show, async (isOpen) => {
  if (isOpen && props.orderId) {
    loading.value = true
    order.value = null
    try {
      const res = await ordersApi.adminShow(props.orderId)
      order.value = res.data.data
      newStatus.value = order.value.status
    } catch {
      toast.error('Không thể tải chi tiết đơn hàng.')
    } finally {
      loading.value = false
    }
  }
})

async function handleUpdateStatus() {
  if (!order.value || newStatus.value === order.value.status) return

  updating.value = true
  try {
    await ordersApi.adminUpdateStatus(order.value.id, { status: newStatus.value })
    toast.success('Cập nhật trạng thái thành công.')
    order.value.status = newStatus.value
    emit('updated')
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Có lỗi xảy ra.')
  } finally {
    updating.value = false
  }
}
</script>
