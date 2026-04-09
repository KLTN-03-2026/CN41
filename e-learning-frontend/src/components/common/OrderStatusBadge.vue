<template>
  <span
    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
    :class="badgeClass"
  >
    <span class="w-1.5 h-1.5 rounded-full" :class="dotClass"></span>
    {{ label }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  status: string
}>()

const config: Record<string, { label: string; badge: string; dot: string }> = {
  pending:   { label: 'Chờ thanh toán', badge: 'bg-amber-50 text-amber-700 border border-amber-200',   dot: 'bg-amber-500' },
  paid:      { label: 'Đã thanh toán', badge: 'bg-emerald-50 text-emerald-700 border border-emerald-200', dot: 'bg-emerald-500' },
  failed:    { label: 'Thất bại',      badge: 'bg-red-50 text-red-700 border border-red-200',         dot: 'bg-red-500' },
  cancelled: { label: 'Đã huỷ',       badge: 'bg-gray-50 text-gray-600 border border-gray-200',      dot: 'bg-gray-400' },
  refunded:  { label: 'Đã hoàn tiền', badge: 'bg-purple-50 text-purple-700 border border-purple-200', dot: 'bg-purple-500' },
}

const current = computed(() => config[props.status] || config.pending)
const label = computed(() => current.value.label)
const badgeClass = computed(() => current.value.badge)
const dotClass = computed(() => current.value.dot)
</script>
