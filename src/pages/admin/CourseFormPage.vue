<template>
  <div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <router-link
        to="/admin/courses"
        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg dark:hover:bg-white/10 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
      </router-link>
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">
          {{ isEdit ? 'Chỉnh sửa khóa học' : 'Thêm khóa học mới' }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5" v-if="isEdit">ID: {{ courseId }}</p>
      </div>
    </div>

    <!-- Tabs (chỉ hiện khi edit) -->
    <div v-if="isEdit" class="flex gap-1 mb-6 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl w-fit">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key"
        :class="activeTab === tab.key
          ? 'bg-white dark:bg-gray-700 text-gray-800 dark:text-white/90 shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
        class="px-4 py-1.5 text-sm rounded-lg transition-all"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab: Thông tin khóa học -->
    <div v-if="activeTab === 'info'">
      <div v-if="pageLoading" class="flex justify-center py-10">
        <svg class="animate-spin w-6 h-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
      </div>

      <form v-else @submit.prevent="submitForm" class="space-y-5">
        <!-- Tên khóa học -->
        <div class="card-box">
          <h3 class="section-title">Thông tin cơ bản</h3>
          <div class="space-y-4">
            <div>
              <label class="label-form">Tên khóa học <span class="text-red-500">*</span></label>
              <input v-model="form.name" type="text" class="input-field" :class="{ 'input-error': errors.name }" placeholder="Laravel 12 từ cơ bản đến nâng cao" @input="autoSlug" />
              <p v-if="errors.name" class="error-msg">{{ errors.name }}</p>
            </div>
            <div>
              <label class="label-form">Slug <span class="text-red-500">*</span></label>
              <input v-model="form.slug" type="text" class="input-field font-mono text-sm" :class="{ 'input-error': errors.slug }" placeholder="laravel-12-tu-co-ban-den-nang-cao" />
              <p v-if="errors.slug" class="error-msg">{{ errors.slug }}</p>
            </div>
            <div>
              <label class="label-form">Mô tả</label>
              <textarea v-model="form.description" rows="4" class="input-field resize-none" placeholder="Mô tả chi tiết về khóa học..." />
            </div>
          </div>
        </div>

        <!-- Phân loại -->
        <div class="card-box">
          <h3 class="section-title">Phân loại</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="label-form">Giảng viên <span class="text-red-500">*</span></label>
              <select v-model="form.teacher_id" class="input-field" :class="{ 'input-error': errors.teacher_id }">
                <option :value="null">— Chọn giảng viên —</option>
                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>
              <p v-if="errors.teacher_id" class="error-msg">{{ errors.teacher_id }}</p>
            </div>
            <div>
              <label class="label-form">Danh mục</label>
              <select v-model="form.category_id" class="input-field">
                <option :value="null">— Chọn danh mục —</option>
                <option v-for="c in flatCategories" :key="c.id" :value="c.id">
                  {{ '—'.repeat(c.depth) }} {{ c.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="label-form">Trình độ <span class="text-red-500">*</span></label>
              <select v-model="form.level" class="input-field" :class="{ 'input-error': errors.level }">
                <option value="beginner">Cơ bản</option>
                <option value="intermediate">Trung cấp</option>
                <option value="advanced">Nâng cao</option>
              </select>
              <p v-if="errors.level" class="error-msg">{{ errors.level }}</p>
            </div>
            <div>
              <label class="label-form">Trạng thái</label>
              <select v-model="form.status" class="input-field">
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
              <input v-model.number="form.price" type="number" min="0" class="input-field" :class="{ 'input-error': errors.price }" placeholder="599000" />
              <p v-if="errors.price" class="error-msg">{{ errors.price }}</p>
            </div>
            <div>
              <label class="label-form">Giá khuyến mãi (VNĐ)</label>
              <input v-model.number="form.sale_price" type="number" min="0" class="input-field" placeholder="399000" />
            </div>
          </div>
        </div>

        <!-- Thumbnail -->
        <div class="card-box">
          <h3 class="section-title">Thumbnail</h3>
          <div>
            <label class="label-form">URL ảnh thumbnail</label>
            <input v-model="form.thumbnail" type="url" class="input-field" placeholder="https://..." />
            <img
              v-if="form.thumbnail"
              :src="form.thumbnail"
              alt="Thumbnail preview"
              class="mt-3 w-48 h-28 object-cover rounded-lg border border-gray-200 dark:border-gray-700"
            />
          </div>
        </div>

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
    </div>

    <!-- Tab: Bài giảng -->
    <div v-if="activeTab === 'lessons' && isEdit">
      <LessonsManager :course-id="courseId!" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { coursesApi } from '@/api/coursesApi'
import { categoriesApi } from '@/api/categoriesApi'
import { teachersApi } from '@/api/teachersApi'
import LessonsManager from '@/components/admin/LessonsManager.vue'

const route  = useRoute()
const router = useRouter()
const toast  = useToast()

const courseId  = computed(() => route.params.id ? Number(route.params.id) : null)
const isEdit    = computed(() => !!courseId.value)
const activeTab = ref<'info' | 'lessons'>('info')
const tabs = [
  { key: 'info',    label: 'Thông tin' },
  { key: 'lessons', label: 'Bài giảng' },
]

const pageLoading = ref(false)
const submitting  = ref(false)
const submitError = ref('')
const errors      = ref<Record<string, string>>({})

const teachers       = ref<{ id: number; name: string }[]>([])
const flatCategories = ref<{ id: number; name: string; depth: number }[]>([])

const defaultForm = () => ({
  name: '',
  slug: '',
  description: '',
  teacher_id: null as number | null,
  category_id: null as number | null,
  level: 'beginner' as string,
  status: 0 as number,
  price: 0 as number,
  sale_price: null as number | null,
  thumbnail: '' as string,
})
const form = ref(defaultForm())

// ── Init ───────────────────────────────────────────────────────
onMounted(async () => {
  // Load teachers + categories in parallel
  const [teacherRes, catRes] = await Promise.all([
    teachersApi.index({ per_page: 100 }).catch(() => null),
    categoriesApi.flatTree().catch(() => null),
  ])
  if (teacherRes) teachers.value = teacherRes.data.data
  if (catRes) flatCategories.value = catRes.data.data

  // Load course nếu edit
  if (isEdit.value) {
    pageLoading.value = true
    try {
      const res = await coursesApi.show(courseId.value!)
      const c = res.data.data
      form.value = {
        name: c.name,
        slug: c.slug,
        description: c.description || '',
        teacher_id: c.teacher?.id ?? null,
        category_id: c.categories?.[0]?.id ?? null,
        level: c.level,
        status: c.status,
        price: Number(c.price),
        sale_price: c.sale_price ? Number(c.sale_price) : null,
        thumbnail: c.thumbnail || '',
      }
    } catch {
      toast.error('Không thể tải thông tin khóa học')
    } finally {
      pageLoading.value = false
    }
  }
})

// ── Auto slug ─────────────────────────────────────────────────
function autoSlug() {
  if (isEdit.value) return
  form.value.slug = form.value.name
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[đĐ]/g, 'd')
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-')
}

// ── Submit ─────────────────────────────────────────────────────
async function submitForm() {
  errors.value = {}
  submitError.value = ''

  // Client-side required check
  if (!form.value.name) { errors.value.name = 'Vui lòng nhập tên khóa học'; return }
  if (!form.value.slug) { errors.value.slug = 'Vui lòng nhập slug'; return }
  if (!form.value.teacher_id) { errors.value.teacher_id = 'Vui lòng chọn giảng viên'; return }

  submitting.value = true
  const payload: Record<string, any> = {
    name: form.value.name,
    slug: form.value.slug,
    description: form.value.description || null,
    teacher_id: form.value.teacher_id,
    category_id: form.value.category_id,
    level: form.value.level,
    status: form.value.status,
    price: form.value.price,
    sale_price: form.value.sale_price || null,
    thumbnail: form.value.thumbnail || null,
  }

  try {
    if (isEdit.value) {
      await coursesApi.update(courseId.value!, payload)
      toast.success('Cập nhật khóa học thành công')
    } else {
      const res = await coursesApi.store(payload)
      toast.success('Tạo khóa học thành công')
      router.push(`/admin/courses/${res.data.data.id}/edit`)
    }
  } catch (err: any) {
    const data = err.response?.data
    if (err.response?.status === 422 && data?.errors) {
      for (const [key, msgs] of Object.entries(data.errors as Record<string, string[]>)) {
        errors.value[key] = msgs[0]
      }
    } else {
      submitError.value = data?.message || 'Có lỗi xảy ra, vui lòng thử lại'
    }
  } finally {
    submitting.value = false
  }
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
