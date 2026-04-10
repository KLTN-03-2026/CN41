<template>
  <div class="max-w-[900px] mx-auto px-4 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Xác nhận đơn hàng</h1>
      <p class="text-sm text-gray-500 mt-1">Kiểm tra thông tin trước khi thanh toán</p>
    </div>

    <!-- Empty cart redirect -->
    <div v-if="cartStore.count === 0" class="text-center py-20">
      <p class="text-gray-500 mb-4">Giỏ hàng trống. Hãy thêm khóa học trước khi thanh toán.</p>
      <router-link to="/courses" class="inline-flex px-5 py-2.5 bg-blue-500 text-white rounded-xl font-medium hover:bg-blue-600 transition-colors">
        Khám phá khóa học
      </router-link>
    </div>

    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-8">
        <!-- Left: Order details -->
        <div class="space-y-6">
          <!-- Order items -->
          <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-900 mb-4">Khóa học ({{ cartStore.count }})</h2>
            <div class="space-y-3">
              <div
                v-for="item in cartStore.items"
                :key="item.id"
                class="flex items-center gap-3 py-3 border-b border-gray-50 last:border-0"
              >
                <div class="w-16 h-10 rounded-lg overflow-hidden shrink-0 bg-gray-100">
                  <img v-if="item.thumbnail" :src="item.thumbnail" :alt="item.name" class="w-full h-full object-cover" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-800 truncate">{{ item.name }}</p>
                </div>
                <div class="text-right shrink-0">
                  <p v-if="item.sale_price" class="text-sm font-bold text-blue-600">{{ formatCurrency(item.sale_price) }}</p>
                  <p :class="item.sale_price ? 'text-xs text-gray-400 line-through' : 'text-sm font-bold text-blue-600'">
                    {{ formatCurrency(item.price) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment method -->
          <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <PaymentMethodSelector v-model="paymentMethod" />
          </div>
        </div>

        <!-- Right: Summary + Checkout -->
        <div class="lg:sticky lg:top-6 h-fit">
          <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-4">
            <h2 class="font-semibold text-gray-900">Tổng thanh toán</h2>

            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">Tạm tính</span>
                <span class="text-gray-800">{{ formatCurrency(cartStore.originalTotal) }}</span>
              </div>
              <div v-if="totalDiscount > 0" class="flex justify-between">
                <span class="text-gray-500">Giảm giá</span>
                <span class="text-green-600">-{{ formatCurrency(totalDiscount) }}</span>
              </div>
            </div>

            <hr class="border-gray-100" />

            <div class="flex justify-between">
              <span class="font-semibold text-gray-900">Tổng cộng</span>
              <span class="text-xl font-bold text-blue-600">{{ formatCurrency(cartStore.total) }}</span>
            </div>

            <!-- Error message -->
            <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 rounded-lg">
              <p class="text-sm text-red-600">{{ errorMessage }}</p>
            </div>

            <!-- Checkout button -->
            <button
              @click="handleCheckout"
              :disabled="isProcessing"
              class="block w-full text-center py-3.5 rounded-xl bg-blue-500 text-white font-semibold hover:bg-blue-600 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
            >
              <template v-if="isProcessing">
                <svg class="animate-spin inline-block w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Đang xử lý...
              </template>
              <template v-else>
                Thanh toán ngay
              </template>
            </button>

            <!-- Back to cart -->
            <router-link to="/cart" class="block text-center text-sm text-gray-500 hover:text-blue-600 transition-colors">
              ← Quay lại giỏ hàng
            </router-link>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useCartStore } from '@/stores/cart.store'
import { orderService } from '@/services/order.service'
import { formatCurrency } from '@/utils/formatCurrency'
import PaymentMethodSelector from '@/components/forms/PaymentMethodSelector.vue'

const router = useRouter()
const toast = useToast()
const cartStore = useCartStore()

const paymentMethod = ref('vnpay')
const isProcessing = ref(false)
const errorMessage = ref('')

const totalDiscount = computed(() => {
  return cartStore.items.reduce((sum, item) => {
    if (item.sale_price && item.sale_price < item.price) {
      return sum + (item.price - item.sale_price)
    }
    return sum
  }, 0)
})

async function handleCheckout() {
  if (cartStore.count === 0) return

  isProcessing.value = true
  errorMessage.value = ''

  try {
    const res = await orderService.createOrder({
      course_ids: cartStore.courseIds,
    })

    const { payment_url, order } = res.data.data

    if (payment_url) {
      // Redirect đến VNPAY — lưu order_code để clear cart sau khi thành công
      localStorage.setItem('pending_order_code', order.order_code)
      window.location.href = payment_url
    } else {
      // Free order — đã auto enroll
      cartStore.clear()
      toast.success('Đơn hàng miễn phí đã được xử lý thành công!')
      router.push(`/payment/result?order_code=${order.order_code}&status=success&message=Đăng+ký+thành+công`)
    }
  } catch (err: any) {
    const data = err.response?.data
    if (data?.errors?.course_ids) {
      // Student đã sở hữu khóa học
      errorMessage.value = Array.isArray(data.errors.course_ids) 
        ? data.errors.course_ids[0] 
        : data.errors.course_ids
    } else {
      errorMessage.value = data?.message || 'Có lỗi xảy ra khi tạo đơn hàng.'
    }
    toast.error(errorMessage.value)
  } finally {
    isProcessing.value = false
  }
}
</script>
