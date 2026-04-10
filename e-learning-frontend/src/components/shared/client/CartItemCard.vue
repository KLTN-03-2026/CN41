<template>
  <div class="flex gap-4 bg-white rounded-xl border border-gray-100 p-4 group hover:border-gray-200 transition-colors">
    <!-- Thumbnail -->
    <div class="w-28 h-20 rounded-lg overflow-hidden shrink-0 bg-gray-100">
      <img
        v-if="item.thumbnail"
        :src="item.thumbnail"
        :alt="item.name"
        class="w-full h-full object-cover"
      />
      <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
        </svg>
      </div>
    </div>

    <!-- Info -->
    <div class="flex-1 min-w-0">
      <h3 class="text-sm font-semibold text-gray-800 truncate">{{ item.name }}</h3>
      <div class="mt-2 flex items-center gap-2">
        <span v-if="item.sale_price" class="text-base font-bold text-blue-600">
          {{ formatCurrency(item.sale_price) }}
        </span>
        <span
          class="font-bold"
          :class="item.sale_price ? 'text-gray-400 line-through text-xs' : 'text-blue-600 text-base'"
        >
          {{ formatCurrency(item.price) }}
        </span>
      </div>
    </div>

    <!-- Remove button -->
    <button
      @click="$emit('remove', item.id)"
      class="self-start p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors opacity-0 group-hover:opacity-100"
      title="Xoá khỏi giỏ hàng"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import { formatCurrency } from '@/utils/formatCurrency'

defineProps<{
  item: {
    id: number
    name: string
    slug?: string
    thumbnail: string | null
    price: number
    sale_price: number | null
  }
}>()

defineEmits<{
  remove: [id: number]
}>()
</script>
