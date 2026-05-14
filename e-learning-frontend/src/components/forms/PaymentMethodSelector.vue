<template>
  <div class="space-y-3">
    <p class="text-sm font-medium text-gray-700">Phương thức thanh toán</p>
    <div class="space-y-2">
      <!-- VNPAY -->
      <label
        class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
        :class="modelValue === 'vnpay'
          ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-200'
          : 'border-gray-200 hover:border-gray-300'"
      >
        <input
          type="radio"
          :value="'vnpay'"
          :checked="modelValue === 'vnpay'"
          @change="$emit('update:modelValue', 'vnpay')"
          class="w-4 h-4 text-blue-600 focus:ring-blue-500"
        />
        <div class="flex items-center gap-2 flex-1">
          <div class="w-10 h-7 bg-white rounded border border-gray-100 flex items-center justify-center">
            <span class="text-[10px] font-bold text-red-600">VNPAY</span>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-800">VNPAY</p>
            <p class="text-xs text-gray-500">ATM / Internet Banking / QR Code</p>
          </div>
        </div>
      </label>

      <!-- ZaloPay -->
      <label
        class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
        :class="modelValue === 'zalopay'
          ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-200'
          : 'border-gray-200 hover:border-gray-300'"
      >
        <input
          type="radio"
          :value="'zalopay'"
          :checked="modelValue === 'zalopay'"
          @change="$emit('update:modelValue', 'zalopay')"
          class="w-4 h-4 text-blue-600 focus:ring-blue-500"
        />
        <div class="flex items-center gap-2 flex-1">
          <div class="w-10 h-7 bg-white rounded border border-gray-100 flex items-center justify-center">
            <span class="text-[10px] font-bold text-blue-500">Zalo</span>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-800">ZaloPay</p>
            <p class="text-xs text-gray-500">Ví điện tử ZaloPay</p>
          </div>
        </div>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: string
}>()

defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>
