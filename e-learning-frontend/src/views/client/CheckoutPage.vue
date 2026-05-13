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
      <router-link
        to="/courses"
        class="inline-flex px-5 py-2.5 bg-blue-500 text-white rounded-xl font-medium hover:bg-blue-600 transition-colors"
      >
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
                  <img
                    v-if="item.thumbnail"
                    :src="item.thumbnail"
                    :alt="item.name"
                    class="w-full h-full object-cover"
                  />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-800 truncate">{{ item.name }}</p>
                </div>
                <div class="text-right shrink-0">
                  <p v-if="item.sale_price" class="text-sm font-bold text-blue-600">
                    {{ formatCurrency(item.sale_price) }}
                  </p>
                  <p
                    :class="
                      item.sale_price
                        ? 'text-xs text-gray-400 line-through'
                        : 'text-sm font-bold text-blue-600'
                    "
                  >
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
        <div class="lg:sticky lg:top-6 h-fit space-y-4">
          <!-- Coupon block -->
          <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <h2 class="font-semibold text-gray-900 mb-3">Mã giảm giá</h2>

            <!-- Applied coupon badge -->
            <div
              v-if="appliedCoupon"
              class="flex items-center justify-between p-3 bg-green-50 border border-green-100 rounded-xl"
            >
              <div>
                <p class="text-sm font-medium text-green-800">
                  Đã áp dụng: {{ appliedCoupon.code }}
                </p>
                <p class="text-xs text-green-600 mt-0.5">{{ appliedCoupon.message }}</p>
              </div>
              <button
                @click="removeCoupon"
                class="p-1.5 hover:bg-green-100 rounded-lg text-green-700 transition-colors"
                title="Xoá mã"
              >
                <svg
                  class="w-4 h-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Input coupon + toggle available list -->
            <div v-else class="space-y-3">
              <div class="flex gap-2">
                <input
                  v-model="couponCode"
                  @keyup.enter="applyCoupon"
                  type="text"
                  placeholder="Nhập mã giảm giá..."
                  class="flex-1 px-3 py-2 border border-gray-200 rounded-xl text-sm font-mono uppercase focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-transparent transition-all"
                  :disabled="isValidating"
                />
                <button
                  @click="applyCoupon"
                  :disabled="isValidating || !couponCode.trim()"
                  class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-gray-800 transition-colors disabled:opacity-50"
                >
                  <template v-if="isValidating">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                      <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                      />
                      <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                      />
                    </svg>
                  </template>
                  <template v-else>Áp dụng</template>
                </button>
              </div>
              <p v-if="couponError" class="text-xs text-red-500">{{ couponError }}</p>

              <!-- Toggle: Xem mã có sẵn -->
              <button
                @click="toggleAvailableCoupons"
                class="flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-700 font-medium transition-colors"
              >
                <svg
                  class="w-3.5 h-3.5 transition-transform duration-200"
                  :class="showAvailableCoupons ? 'rotate-180' : ''"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
                <span>{{
                  showAvailableCoupons ? 'Ẩn mã giảm giá' : 'Xem mã giảm giá có sẵn'
                }}</span>
              </button>

              <!-- Available coupons list -->
              <div
                v-if="showAvailableCoupons"
                class="border border-dashed border-blue-200 rounded-xl overflow-hidden"
              >
                <!-- Loading -->
                <div v-if="loadingAvailable" class="p-4 text-center">
                  <svg
                    class="animate-spin w-5 h-5 mx-auto text-blue-400"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle
                      class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"
                    />
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                    />
                  </svg>
                </div>

                <!-- Empty -->
                <div
                  v-else-if="availableCoupons.length === 0"
                  class="p-4 text-center text-xs text-gray-400"
                >
                  Hiện không có mã giảm giá nào.
                </div>

                <!-- Coupon list -->
                <div v-else class="divide-y divide-blue-50">
                  <div
                    v-for="coupon in availableCoupons"
                    :key="coupon.code"
                    class="flex items-center justify-between px-3 py-2.5 hover:bg-blue-50 transition-colors cursor-pointer group"
                    @click="selectAvailableCoupon(coupon.code)"
                  >
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2">
                        <span
                          class="text-xs font-mono font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded-md"
                        >
                          {{ coupon.code }}
                        </span>
                        <span class="text-xs text-gray-600">
                          {{
                            coupon.type === 'fixed'
                              ? `Giảm ${formatCurrency(Number(coupon.value))}`
                              : `Giảm ${coupon.value}%${coupon.max_discount ? ` (tối đa ${formatCurrency(Number(coupon.max_discount))})` : ''}`
                          }}
                        </span>
                      </div>
                      <div class="mt-0.5 flex items-center gap-2 flex-wrap">
                        <span v-if="coupon.min_order_value" class="text-[10px] text-gray-400">
                          Đơn tối thiểu {{ formatCurrency(Number(coupon.min_order_value)) }}
                        </span>
                        <span v-if="coupon.end_date" class="text-[10px] text-orange-500">
                          HSD: {{ formatDatetime(coupon.end_date) }}
                        </span>
                        <span v-if="coupon.remaining !== null" class="text-[10px] text-gray-400">
                          Còn {{ coupon.remaining }} lượt
                        </span>
                        <span
                          v-if="coupon.description"
                          class="text-[10px] text-gray-400 truncate max-w-[120px]"
                        >
                          {{ coupon.description }}
                        </span>
                      </div>
                    </div>
                    <span
                      class="text-xs text-blue-500 group-hover:text-blue-700 font-medium ml-2 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                      Chọn
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Total block -->
          <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-4">
            <h2 class="font-semibold text-gray-900">Tổng thanh toán</h2>

            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">Tạm tính</span>
                <span class="text-gray-800">{{ formatCurrency(cartStore.total) }}</span>
              </div>
              <div v-if="appliedCoupon" class="flex justify-between">
                <span class="text-gray-500">Mã giảm giá</span>
                <span class="text-green-600"
                  >-{{ formatCurrency(appliedCoupon.discount_amount) }}</span
                >
              </div>
            </div>

            <hr class="border-gray-100" />

            <div class="flex justify-between">
              <span class="font-semibold text-gray-900">Tổng cộng</span>
              <span class="text-xl font-bold text-blue-600">{{ formatCurrency(finalTotal) }}</span>
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
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  />
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                  />
                </svg>
                Đang xử lý...
              </template>
              <template v-else> Thanh toán ngay </template>
            </button>

            <!-- Back to cart -->
            <router-link
              to="/cart"
              class="block text-center text-sm text-gray-500 hover:text-blue-600 transition-colors"
            >
              ← Quay lại giỏ hàng
            </router-link>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { useCartStore } from '@/stores/cart.store'
import { formatCurrency } from '@/utils/formatCurrency'
import { formatDatetime } from '@/utils/formatDate'
import { useCheckout } from '@/composables/useCheckout'
import PaymentMethodSelector from '@/components/forms/PaymentMethodSelector.vue'

const cartStore = useCartStore()

const {
  paymentMethod,
  isProcessing,
  errorMessage,
  couponCode,
  couponError,
  isValidating,
  appliedCoupon,
  showAvailableCoupons,
  availableCoupons,
  loadingAvailable,
  finalTotal,
  toggleAvailableCoupons,
  selectAvailableCoupon,
  applyCoupon,
  removeCoupon,
  handleCheckout,
} = useCheckout()
</script>
