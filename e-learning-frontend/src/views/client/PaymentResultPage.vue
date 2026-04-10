<template>
  <div class="max-w-[600px] mx-auto px-4 py-16 text-center">
    <!-- Loading -->
    <div v-if="loading" class="py-20">
      <svg class="animate-spin mx-auto w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
    </div>

    <template v-else>
      <!-- Success -->
      <template v-if="status === 'success'">
        <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Thanh toán thành công!</h1>
        <p class="text-gray-500 mb-2">Mã đơn hàng: <span class="font-medium text-gray-700">{{ orderCode }}</span></p>
        <p class="text-gray-500 mb-8">Bạn đã được ghi danh vào khóa học. Hãy bắt đầu học ngay!</p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <router-link
            to="/my-courses"
            class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-500 text-white rounded-xl font-medium hover:bg-blue-600 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
            </svg>
            Vào học ngay
          </router-link>
          <router-link
            to="/my-orders"
            class="inline-flex items-center justify-center gap-2 px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors"
          >
            Xem đơn hàng
          </router-link>
        </div>
      </template>

      <!-- Failed -->
      <template v-else>
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Thanh toán thất bại</h1>
        <p class="text-gray-500 mb-2" v-if="orderCode">Mã đơn hàng: <span class="font-medium text-gray-700">{{ orderCode }}</span></p>
        <p class="text-gray-500 mb-8">{{ message || 'Giao dịch không thành công. Bạn có thể thử lại hoặc chọn phương thức khác.' }}</p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <button
            v-if="orderCode"
            @click="handleRetry"
            :disabled="retrying"
            class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-500 text-white rounded-xl font-medium hover:bg-blue-600 transition-colors disabled:opacity-60"
          >
            <template v-if="retrying">Đang xử lý...</template>
            <template v-else>
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
              </svg>
              Thử lại
            </template>
          </button>
          <router-link
            to="/"
            class="inline-flex items-center justify-center gap-2 px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors"
          >
            Về trang chủ
          </router-link>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useCartStore } from '@/stores/cart.store'
import { orderService } from '@/services/order.service'

const route = useRoute()
const toast = useToast()
const cartStore = useCartStore()

const loading    = ref(true)
const status     = ref('')
const orderCode  = ref('')
const message    = ref('')
const retrying   = ref(false)

onMounted(() => {
  status.value    = (route.query.status as string) || 'failed'
  orderCode.value = (route.query.order_code as string) || ''
  message.value   = (route.query.message as string) || ''

  // Clear cart on success
  if (status.value === 'success') {
    cartStore.clear()
    localStorage.removeItem('pending_order_code')
  }

  loading.value = false
})

async function handleRetry() {
  if (!orderCode.value) return

  retrying.value = true
  try {
    const res = await orderService.retryPayment(orderCode.value)
    const { payment_url } = res.data.data

    if (payment_url) {
      window.location.href = payment_url
    }
  } catch (err: unknown) {
    const axiosError = err as { response?: { data?: { message?: string } } }
    toast.error(axiosError.response?.data?.message || 'Không thể tạo liên kết thanh toán mới.')
  } finally {
    retrying.value = false
  }
}
</script>
