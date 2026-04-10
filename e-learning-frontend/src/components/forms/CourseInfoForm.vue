<template>
  <form @submit.prevent="emit('submit')" class="space-y-5">
    <!-- Tên khóa học -->
    <div class="card-box">
      <h3 class="section-title">Thông tin cơ bản</h3>
      <div class="space-y-4">
        <div>
          <label class="label-form">Tên khóa học <span class="text-red-500">*</span></label>
          <input
            :value="form.name"
            @input="updateField('name', $event)"
            type="text"
            class="input-field"
            :class="{ 'input-error': errors.name }"
            placeholder="Laravel 12 từ cơ bản đến nâng cao"
          />
          <p v-if="errors.name" class="error-msg">{{ errors.name }}</p>
        </div>
        <div>
          <label class="label-form">Slug <span class="text-red-500">*</span></label>
          <div class="flex gap-2">
            <input
              :value="form.slug"
              @input="updateField('slug', $event)"
              type="text"
              class="input-field font-mono text-sm flex-1"
              :class="{ 'input-error': errors.slug, 'opacity-60 cursor-not-allowed': isEdit && !slugUnlocked }"
              :disabled="isEdit && !slugUnlocked"
              placeholder="laravel-12-tu-co-ban-den-nang-cao"
            />
            <button
              v-if="isEdit && !slugUnlocked"
              type="button"
              @click="emit('update:slugUnlocked', true)"
              class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-orange-600 hover:border-orange-300 transition-colors whitespace-nowrap"
              title="Đổi slug có thể ảnh hưởng SEO và link đã chia sẻ"
            >
              🔒 Mở khóa
            </button>
          </div>
          <p v-if="errors.slug" class="error-msg">{{ errors.slug }}</p>
          <p v-if="isEdit && slugUnlocked" class="text-xs text-orange-500 mt-1">
            ⚠️ Đổi slug sẽ ảnh hưởng URL, SEO và các link đã chia sẻ
          </p>
        </div>
        <div>
          <label class="label-form">Mô tả</label>
          <textarea
            :value="form.description"
            @input="updateField('description', $event)"
            rows="4"
            class="input-field resize-none"
            placeholder="Mô tả chi tiết về khóa học..."
          />
        </div>
      </div>
    </div>

    <!-- Phân loại -->
    <div class="card-box">
      <h3 class="section-title">Phân loại</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label-form">Giảng viên <span class="text-red-500">*</span></label>
          <select
            :value="form.teacher_id"
            @change="updateFieldNum('teacher_id', $event)"
            class="input-field"
            :class="{ 'input-error': errors.teacher_id }"
          >
            <option :value="null">— Chọn giảng viên —</option>
            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
          <p v-if="errors.teacher_id" class="error-msg">{{ errors.teacher_id }}</p>
        </div>
        <div>
          <label class="label-form">Danh mục</label>
          <select
            :value="form.category_id"
            @change="updateFieldNum('category_id', $event)"
            class="input-field"
          >
            <option :value="null">— Chọn danh mục —</option>
            <option v-for="c in flatCategories" :key="c.id" :value="c.id">
              {{ '—'.repeat(c.depth) }} {{ c.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="label-form">Trình độ <span class="text-red-500">*</span></label>
          <select
            :value="form.level"
            @change="updateField('level', $event)"
            class="input-field"
            :class="{ 'input-error': errors.level }"
          >
            <option value="beginner">Cơ bản</option>
            <option value="intermediate">Trung cấp</option>
            <option value="advanced">Nâng cao</option>
          </select>
          <p v-if="errors.level" class="error-msg">{{ errors.level }}</p>
        </div>
        <div>
          <label class="label-form">Trạng thái</label>
          <select
            :value="form.status"
            @change="updateFieldNum('status', $event)"
            class="input-field"
          >
            <option :value="0">Nháp</option>
            <option :value="1">Đăng công khai</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Giá -->
    <div class="card-box">
      <h3 class="section-title">Giá bán</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label-form">Giá gốc (VNĐ) <span class="text-red-500">*</span></label>
          <input
            :value="form.price"
            @input="updateFieldNum('price', $event)"
            type="number"
            min="0"
            class="input-field"
            :class="{ 'input-error': errors.price }"
            placeholder="599000"
          />
          <p v-if="errors.price" class="error-msg">{{ errors.price }}</p>
        </div>
        <div>
          <label class="label-form">Giá khuyến mãi (VNĐ)</label>
          <input
            :value="form.sale_price !== null ? form.sale_price : ''"
            @input="updateFieldNumNull('sale_price', $event)"
            type="number"
            min="0"
            class="input-field"
            :class="{ 'input-error': errors.sale_price }"
            placeholder="399000"
          />
          <p v-if="errors.sale_price" class="error-msg">{{ errors.sale_price }}</p>
        </div>
      </div>
    </div>

    <!-- Thumbnail Upload -->
    <ThumbnailUpload
      :modelValue="form.thumbnail"
      @update:modelValue="emit('update:form', { ...form, thumbnail: $event })"
    />

    <!-- Error chung -->
    <p v-if="submitError" class="text-sm text-red-500 bg-red-50 dark:bg-red-500/10 px-4 py-3 rounded-lg">
      {{ submitError }}
    </p>

    <div class="flex justify-end gap-3">
      <router-link
        to="/admin/courses"
        class="px-5 py-2.5 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5"
      >
        Hủy
      </router-link>
      <button
        type="submit"
        :disabled="submitting"
        class="px-5 py-2.5 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 disabled:opacity-50 flex items-center gap-2"
      >
        <svg v-if="submitting" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        {{ isEdit ? 'Cập nhật' : 'Tạo khóa học' }}
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import ThumbnailUpload from '@/components/forms/ThumbnailUpload.vue'

const props = defineProps<{
  form: Record<string, any>
  errors: Record<string, string>
  teachers: { id: number; name: string }[]
  flatCategories: { id: number; name: string; depth: number }[]
  isEdit: boolean
  slugUnlocked: boolean
  submitError: string
  submitting: boolean
}>()

const emit = defineEmits<{
  'update:form': [val: Record<string, any>]
  'update:slugUnlocked': [val: boolean]
  'auto-slug': []
  'submit': []
}>()

function getVal(e: Event) {
  return (e.target as HTMLInputElement).value
}

function updateField(field: string, e: Event) {
  const newForm = { ...props.form, [field]: getVal(e) }
  emit('update:form', newForm)
  if (field === 'name' && !props.isEdit) {
    emit('auto-slug')
  }
}

function updateFieldNum(field: string, e: Event) {
  const val = getVal(e)
  const newForm = { ...props.form, [field]: val ? Number(val) : 0 }
  emit('update:form', newForm)
}

function updateFieldNumNull(field: string, e: Event) {
  const val = getVal(e)
  const newForm = { ...props.form, [field]: val ? Number(val) : null }
  emit('update:form', newForm)
}
</script>

<style scoped>
.card-box {
  @apply bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-700 rounded-2xl p-5;
}
.section-title {
  @apply text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4;
}
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
