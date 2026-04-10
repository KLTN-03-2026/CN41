<template>
  <div class="max-w-[900px] mx-auto px-4 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Giỏ hàng</h1>
      <span class="text-sm text-gray-500">{{ cartStore.count }} khóa học</span>
    </div>

    <!-- Empty state -->
    <div v-if="cartStore.count === 0" class="text-center py-20">
      <svg class="mx-auto w-16 h-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
      </svg>
      <p class="text-gray-500 mb-4">Giỏ hàng của bạn đang trống</p>
      <router-link
        to="/courses"
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-500 text-white rounded-xl font-medium hover:bg-blue-600 transition-colors"
      >
        Khám phá khóa học
      </router-link>
    </div>

    <!-- Cart content -->
    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-8">
        <!-- Items list -->
        <div class="space-y-3">
          <CartItemCard
            v-for="item in cartStore.items"
            :key="item.id"
            :item="item"
            @remove="handleRemove"
          />

          <!-- Clear cart -->
          <div class="flex justify-end pt-2">
            <button
              @click="handleClearCart"
              class="text-sm text-red-500 hover:text-red-600 hover:underline transition-colors"
            >
              Xoá tất cả
            </button>
          </div>
        </div>

        <!-- Summary sidebar -->
        <div class="lg:sticky lg:top-6 h-fit">
          <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm space-y-4">
            <h2 class="font-semibold text-gray-900">Tóm tắt đơn hàng</h2>

            <!-- Subtotal -->
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Tạm tính ({{ cartStore.count }} khóa học)</span>
              <span class="text-gray-800 font-medium">{{ formatCurrency(cartStore.originalTotal) }}</span>
            </div>

            <!-- Discount if any sale prices -->
            <div v-if="totalDiscount > 0" class="flex justify-between text-sm">
              <span class="text-gray-500">Giảm giá</span>
              <span class="text-green-600 font-medium">-{{ formatCurrency(totalDiscount) }}</span>
            </div>

            <hr class="border-gray-100" />

            <!-- Total -->
            <div class="flex justify-between">
              <span class="font-semibold text-gray-900">Tổng cộng</span>
              <span class="text-xl font-bold text-blue-600">{{ formatCurrency(cartStore.total) }}</span>
            </div>

            <!-- Checkout button -->
            <router-link
              to="/checkout"
              class="block w-full text-center py-3 rounded-xl bg-blue-500 text-white font-medium hover:bg-blue-600 transition-colors"
            >
              Tiến hành thanh toán
            </router-link>

            <!-- Continue shopping -->
            <router-link
              to="/courses"
              class="block w-full text-center py-2.5 text-sm text-gray-600 hover:text-blue-600 transition-colors"
            >
              ← Tiếp tục mua sắm
            </router-link>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useToast } from 'vue-toastification'
import { useCartStore } from '@/stores/cart.store'
import { formatCurrency } from '@/utils/formatCurrency'
import CartItemCard from '@/components/client/CartItemCard.vue'

const toast = useToast()
const cartStore = useCartStore()

const totalDiscount = computed(() => {
  return cartStore.items.reduce((sum, item) => {
    if (item.sale_price && item.sale_price < item.price) {
      return sum + (item.price - item.sale_price)
    }
    return sum
  }, 0)
})

function handleRemove(courseId: number) {
  cartStore.removeItem(courseId)
  toast.success('Đã xóa khỏi giỏ hàng')
}

function handleClearCart() {
  cartStore.clear()
  toast.success('Đã xóa tất cả khóa học khỏi giỏ hàng')
}
</script>
