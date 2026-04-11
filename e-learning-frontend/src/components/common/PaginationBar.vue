<template>
  <div class="flex items-center gap-1">
    <!-- Prev -->
    <button
      :disabled="currentPage <= 1"
      @click="$emit('change', currentPage - 1)"
      class="w-8 h-8 flex items-center justify-center rounded-lg border text-sm transition-colors disabled:opacity-40 disabled:cursor-not-allowed border-gray-200 dark:border-gray-700 bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/10"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
    </button>

    <!-- Page buttons -->
    <template v-for="item in pages" :key="item">
      <span
        v-if="item === '...'"
        class="w-8 h-8 flex items-center justify-center text-gray-400 text-sm select-none"
        >…</span
      >
      <button
        v-else
        @click="$emit('change', item as number)"
        :class="
          item === currentPage
            ? activeClass
            : 'bg-white text-gray-600 dark:bg-white/5 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/10'
        "
        class="w-8 h-8 rounded-lg text-sm border border-gray-200 dark:border-gray-700 transition-colors"
      >
        {{ item }}
      </button>
    </template>

    <!-- Next -->
    <button
      :disabled="currentPage >= lastPage"
      @click="$emit('change', currentPage + 1)"
      class="w-8 h-8 flex items-center justify-center rounded-lg border text-sm transition-colors disabled:opacity-40 disabled:cursor-not-allowed border-gray-200 dark:border-gray-700 bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/10"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  currentPage: number
  lastPage: number
  activeClass?: string
}>()

defineEmits<{
  change: [page: number]
}>()

const activeClass = computed(() => props.activeClass ?? 'bg-blue-500 text-white border-blue-500')

const pages = computed(() => {
  const total = props.lastPage
  const current = props.currentPage
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

  const items: (number | '...')[] = [1]

  if (current > 3) items.push('...')

  const start = Math.max(2, current - 1)
  const end = Math.min(total - 1, current + 1)
  for (let i = start; i <= end; i++) items.push(i)

  if (current < total - 2) items.push('...')
  items.push(total)

  return items
})
</script>
