<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { commissionService } from '@/services/commission.service'

const toast = useToast()
const teacherRate = ref<number>(70)
const loading = ref(false)
const saving = ref(false)
const platformRate = computed(() => (100 - teacherRate.value).toFixed(2))

async function load() {
  loading.value = true
  try {
    const res = await commissionService.getSettings()
    teacherRate.value = Number(res.data.data.teacher_rate)
  } finally {
    loading.value = false
  }
}

async function save() {
  if (saving.value) return
  saving.value = true
  try {
    await commissionService.updateSettings({ teacher_rate: teacherRate.value })
    toast.success('Cài đặt đã được lưu.')
  } catch {
    toast.error('Lưu thất bại.')
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="p-6 max-w-md">
    <h1 class="text-2xl font-bold mb-6">Cài đặt hoa hồng</h1>
    <div class="bg-white rounded-lg shadow p-6">
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Tỷ lệ giảng viên (%)</label>
        <div class="flex items-center gap-3">
          <input v-model.number="teacherRate" type="number" min="0" max="100" step="0.5"
            class="border rounded px-3 py-2 w-32 text-sm" />
          <span class="text-sm text-gray-500">→ Nền tảng nhận: <strong>{{ platformRate }}%</strong></span>
        </div>
      </div>
      <button @click="save" :disabled="saving"
        class="px-4 py-2 bg-blue-600 text-white rounded text-sm disabled:opacity-50">
        {{ saving ? 'Đang lưu...' : 'Lưu cài đặt' }}
      </button>
    </div>
  </div>
</template>
