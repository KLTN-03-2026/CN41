<template>
  <div class="max-w-[900px] mx-auto px-4 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Lịch sử đơn hàng</h1>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-20">
      <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
    </div>

    <!-- Empty state -->
    <div v-else-if="orders.length === 0" class="text-center py-20">
      <svg class="mx-auto w-16 h-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
      </svg>
      <p class="text-gray-500 mb-4">Bạn chưa có đơn hàng nào</p>
      <router-link to="/courses" class="inline-flex px-5 py-2.5 bg-blue-500 text-white rounded-xl font-medium hover:bg-blue-600 transition-colors">
        Khám phá khóa học
      </router-link>
    </div>

    <!-- Orders list -->
    <template v-else>
      <div class="space-y-4">
        <div
          v-for="order in orders"
          :key="order.id"
          class="bg-white rounded-2xl border border-gray-100 p-5 hover:border-gray-200 transition-colors"
        >
          <!-- Order header -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <span class="text-sm font-medium text-gray-800">{{ order.order_code }}</span>
              <OrderStatusBadge :status="order.status" />
            </div>
            <span class="text-xs text-gray-400">{{ formatDate(order.created_at) }}</span>
          </div>

          <!-- Order items -->
          <div class="space-y-2 mb-4">
            <div
              v-for="item in order.items"
              :key="item.id"
              class="flex items-center gap-3"
            >
              <div class="w-14 h-9 rounded-lg overflow-hidden shrink-0 bg-gray-100">
                <img v-if="item.course?.thumbnail" :src="item.course.thumbnail" :alt="item.course?.name" class="w-full h-full object-cover" />
              </div>
              <p class="text-sm text-gray-700 flex-1 truncate">{{ item.course?.name }}</p>
              <span class="text-sm font-medium text-gray-800 shrink-0">{{ formatCurrency(Number(item.final_price)) }}</span>
            </div>
          </div>

          <!-- Order footer -->
          <div class="flex items-center justify-between pt-3 border-t border-gray-50">
            <div class="text-sm">
              <span class="text-gray-500">Tổng:</span>
              <span class="ml-1 font-bold text-blue-600">{{ formatCurrency(Number(order.total_amount)) }}</span>
            </div>
            <div class="flex gap-2">
              <button
                v-if="order.status === 'pending' || order.status === 'failed'"
                @click="handleRetry(order.order_code)"
                class="text-xs px-3 py-1.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
              >
                Thanh toán lại
              </button>
              <router-link
                v-if="order.status === 'paid'"
                to="/my-courses"
                class="text-xs px-3 py-1.5 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition-colors"
              >
                Vào học
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex justify-center gap-2 mt-8">
        <button
          v-for="page in pagination.last_page"
          :key="page"
          @click="fetchOrders(page)"
          class="w-9 h-9 rounded-lg text-sm font-medium transition-colors"
          :class="page === pagination.current_page
            ? 'bg-blue-500 text-white'
            : 'bg-white border border-gray-200 text-gray-600 hover:border-blue-300'"
        >
          {{ page }}
        </button>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { orderService } from '@/services/order.service'
import { formatCurrency } from '@/utils/formatCurrency'
import OrderStatusBadge from '@/components/common/OrderStatusBadge.vue'

const toast = useToast()

const loading = ref(true)
const orders = ref<any[]>([])
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
})

function formatDate(iso: string) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function fetchOrders(page = 1) {
  loading.value = true
  try {
    const res = await orderService.myOrders({ page, per_page: 10 })
    orders.value = res.data.data
    Object.assign(pagination, res.data.pagination)
  } catch {
    toast.error('Không thể tải lịch sử đơn hàng.')
  } finally {
    loading.value = false
  }
}

async function handleRetry(orderCode: string) {
  try {
    const res = await orderService.retryPayment(orderCode)
    const { payment_url } = res.data.data
    if (payment_url) {
      window.location.href = payment_url
    }
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Không thể tạo liên kết thanh toán mới.')
  }
}

onMounted(() => fetchOrders())
</script>
