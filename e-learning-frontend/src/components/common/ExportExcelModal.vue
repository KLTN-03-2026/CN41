<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="$emit('close')"
    >
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
              <input
                v-model="form.from"
                type="date"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
              <input
                v-model="form.to"
                type="date"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
              />
            </div>
          </div>

          <div v-if="hasStatusFilter">
            <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
            <select
              v-model="form.status"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
            >
              <option value="">Tất cả trạng thái</option>
              <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors"
          >
            Hủy
          </button>
          <button
            @click="handleExport"
            :disabled="loading"
            class="px-5 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 rounded-xl transition-colors flex items-center gap-2"
          >
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            {{ loading ? 'Đang xuất...' : 'Xuất Excel' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import http from '@/plugins/axios'

interface StatusOption {
  value: string
  label: string
}

const props = defineProps<{
  show: boolean
  title: string
  endpoint: string
  extraParams?: Record<string, string | number | undefined>
  hasStatusFilter?: boolean
  statusOptions?: StatusOption[]
}>()

const emit = defineEmits<{
  close: []
}>()

const toast = useToast()
const loading = ref(false)

function defaultFrom(): string {
  const d = new Date()
  d.setDate(1)
  return d.toISOString().slice(0, 10)
}

function defaultTo(): string {
  return new Date().toISOString().slice(0, 10)
}

const form = reactive({
  from: defaultFrom(),
  to: defaultTo(),
  status: '',
})

async function handleExport() {
  loading.value = true
  try {
    const params: Record<string, string | number> = {
      from: form.from,
      to: form.to,
      ...props.extraParams,
    }
    if (props.hasStatusFilter && form.status) {
      params.status = form.status
    }

    const res = await http.get(props.endpoint, {
      params,
      responseType: 'blob',
    })

    const contentDisposition = res.headers['content-disposition'] as string | undefined
    const match = contentDisposition?.match(/filename="?([^";\n]+)"?/)
    const filename = match?.[1] ?? 'export.xlsx'

    const url = URL.createObjectURL(new Blob([res.data]))
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)

    emit('close')
  } catch {
    toast.error('Xuất file thất bại. Vui lòng thử lại.')
  } finally {
    loading.value = false
  }
}
</script>
