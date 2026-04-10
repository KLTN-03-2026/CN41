<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4"
      @click.self="closeModal"
    >
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-5">
          {{ isEdit ? 'Chỉnh sửa chương' : 'Thêm chương mới' }}
        </h3>
        <form @submit.prevent="$emit('submit')" class="space-y-4">
          <div>
            <label class="label-form">Tiêu đề <span class="text-red-500">*</span></label>
            <input v-model="form.title" type="text" class="input-field" :class="{ 'input-error': errors.title }" placeholder="Chương 1: Giới thiệu" />
            <p v-if="errors.title" class="error-msg">{{ errors.title }}</p>
          </div>
          <div>
            <label class="label-form">Mô tả</label>
            <textarea v-model="form.description" rows="2" class="input-field resize-none" placeholder="Mô tả nội dung chương..." />
          </div>
          <div class="flex items-center gap-4">
            <div class="flex-1">
              <label class="label-form">Thứ tự</label>
              <input v-model.number="form.order" type="number" min="0" class="input-field" placeholder="0" />
            </div>
            <div>
              <label class="label-form">Trạng thái</label>
              <select v-model="form.status" class="input-field w-auto px-3">
                <option :value="0">Nháp</option>
                <option :value="1">Đã đăng</option>
              </select>
            </div>
          </div>

          <p v-if="submitError" class="text-sm text-red-500">{{ submitError }}</p>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="closeModal" class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
              Hủy
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 disabled:opacity-50 flex items-center gap-2"
            >
              <svg v-if="submitting" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ isEdit ? 'Cập nhật' : 'Tạo mới' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
const props = defineProps<{
  show: boolean
  isEdit: boolean
  submitting: boolean
  errors: Record<string, string>
  submitError: string
  form: {
    title: string
    description: string
    order: number
    status: number
  }
}>()

const emit = defineEmits<{
  'update:show': [value: boolean]
  'submit': []
}>()

function closeModal() {
  emit('update:show', false)
}
</script>

<style scoped>
.label-form {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1;
}
.input-field {
  @apply w-full px-3 rounded-lg border border-gray-300 bg-transparent text-sm text-gray-800
         dark:border-gray-700 dark:text-white/90 dark:bg-gray-900
         focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400;
  height: 40px;
}
textarea.input-field {
  height: auto;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}
.input-error {
  @apply border-red-400 focus:ring-red-400/20;
}
.error-msg {
  @apply text-xs text-red-500 mt-1;
}
</style>
