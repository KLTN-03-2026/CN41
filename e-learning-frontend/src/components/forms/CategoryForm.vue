<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4"
        @click.self="emit('close')"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md p-6">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-5">
            {{ editingId ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' }}
          </h3>

          <form @submit.prevent="emit('submit')" class="space-y-4">
            <div>
              <label class="label-form">Tên danh mục <span class="text-red-500">*</span></label>
              <input
                :value="form.name"
                @input="handleNameInput"
                type="text"
                class="input-field"
                :class="{ 'input-error': errors.name }"
                placeholder="Lập trình"
              />
              <p v-if="errors.name" class="error-msg">{{ errors.name }}</p>
            </div>

            <div>
              <label class="label-form">Slug <span class="text-red-500">*</span></label>
              <input
                :value="form.slug"
                @input="handleSlugInput"
                type="text"
                class="input-field font-mono text-sm"
                :class="{ 'input-error': errors.slug }"
                placeholder="lap-trinh"
              />
              <p v-if="errors.slug" class="error-msg">{{ errors.slug }}</p>
            </div>

            <div>
              <label class="label-form">Danh mục cha</label>
              <select
                :value="form.parent_id ?? ''"
                @change="handleParentChange"
                class="input-field"
              >
                <option value="">— Không có (danh mục gốc) —</option>
                <option
                  v-for="item in flatTree"
                  :key="item.id"
                  :value="item.id"
                  :disabled="item.id === editingId"
                >
                  {{ '—'.repeat(item.depth) }} {{ item.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="label-form">Mô tả</label>
              <textarea
                :value="form.description"
                @input="handleDescriptionInput"
                rows="2"
                class="input-field resize-none"
                placeholder="Mô tả ngắn..."
              />
            </div>

            <div>
              <label class="label-form">Trạng thái</label>
              <select :value="form.status" @change="handleStatusChange" class="input-field">
                <option :value="1">Hoạt động</option>
                <option :value="0">Ẩn</option>
              </select>
            </div>

            <p v-if="submitError" class="text-sm text-red-500">{{ submitError }}</p>

            <div class="flex justify-end gap-3 pt-2">
              <button
                type="button"
                @click="emit('close')"
                class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5"
              >
                Hủy
              </button>
              <button
                type="submit"
                :disabled="submitting"
                class="px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 disabled:opacity-50 flex items-center gap-2"
              >
                <svg
                  v-if="submitting"
                  class="animate-spin w-4 h-4"
                  xmlns="http://www.w3.org/2000/svg"
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
                {{ editingId ? 'Cập nhật' : 'Tạo mới' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
interface CategoryFormData {
  name: string
  slug: string
  description: string
  status: number
  parent_id: number | null
}

const props = defineProps<{
  show: boolean
  editingId: number | null
  form: CategoryFormData
  errors: Record<string, string>
  submitError: string
  submitting: boolean
  flatTree: { id: number; name: string; depth: number }[]
}>()

const emit = defineEmits<{
  close: []
  submit: []
  'update:form': [form: CategoryFormData]
  'auto-slug': []
}>()

function getInputValue(e: Event): string {
  return (e.target as HTMLInputElement).value
}

function handleNameInput(e: Event) {
  emit('update:form', { ...props.form, name: getInputValue(e) })
  if (!props.editingId) {
    emit('auto-slug')
  }
}

function handleSlugInput(e: Event) {
  emit('update:form', { ...props.form, slug: getInputValue(e) })
}

function handleParentChange(e: Event) {
  const val = getInputValue(e)
  emit('update:form', { ...props.form, parent_id: val ? Number(val) : null })
}

function handleDescriptionInput(e: Event) {
  emit('update:form', { ...props.form, description: getInputValue(e) })
}

function handleStatusChange(e: Event) {
  emit('update:form', { ...props.form, status: Number(getInputValue(e)) })
}
</script>

<style scoped>
.label-form {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1;
}
.input-field {
  @apply w-full h-10 px-3 rounded-lg border border-gray-300 bg-transparent text-sm text-gray-800
         dark:border-gray-700 dark:text-white/90 dark:bg-gray-900
         focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400;
}
textarea.input-field {
  @apply h-auto py-2;
}
.input-error {
  @apply border-red-400 focus:ring-red-400/20;
}
.error-msg {
  @apply text-xs text-red-500 mt-1;
}
</style>
