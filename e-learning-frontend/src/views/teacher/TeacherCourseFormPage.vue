<template>
  <div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <router-link
        to="/teacher/courses"
        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg dark:hover:bg-white/10 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
      </router-link>
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">
          {{ isEdit ? (activeTab === 'lessons' ? 'Nội dung khóa học' : 'Thông tin khóa học') : 'Thêm khóa học mới' }}
        </h2>
        <p v-if="isEdit" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          {{ form.name || `ID: ${courseId}` }}
        </p>
      </div>
    </div>

    <!-- Tabs (chỉ hiện khi edit) -->
    <div v-if="isEdit" class="flex gap-1 mb-6 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl w-fit">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key"
        :class="
          activeTab === tab.key
            ? 'bg-white dark:bg-gray-700 text-gray-800 dark:text-white/90 shadow-sm'
            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
        "
        class="px-4 py-1.5 text-sm rounded-lg transition-all"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab: Thông tin -->
    <div v-if="activeTab === 'info'">
      <div v-if="pageLoading" class="flex justify-center py-10">
        <svg class="animate-spin w-6 h-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>
      <CourseInfoForm
        v-else
        :form="form"
        @update:form="form = $event"
        :errors="formErrors"
        :teachers="[]"
        :flat-categories="flatCategories"
        :is-edit="isEdit"
        :slug-unlocked="slugUnlocked"
        @update:slug-unlocked="slugUnlocked = $event"
        :submit-error="submitError"
        :submitting="submitting"
        @submit="submitForm"
        @auto-slug="autoSlug"
      />
    </div>

    <!-- Tab: Nội dung (Sections + Lessons) -->
    <div v-if="activeTab === 'lessons' && isEdit">
      <TeacherSectionsLessonsManager :course-id="courseId ?? 0" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { commissionService } from '@/services/commission.service'
import { categoryService } from '@/services/category.service'
import CourseInfoForm from '@/components/forms/CourseInfoForm.vue'
import TeacherSectionsLessonsManager from '@/components/shared/teacher/TeacherSectionsLessonsManager.vue'
import { useFormErrors } from '@/composables/useFormErrors'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const courseId = computed(() => (route.params.id ? Number(route.params.id) : null))
const isEdit = computed(() => !!courseId.value)
const initialTab = route.query.tab === 'lessons' ? 'lessons' : 'info'
const activeTab = ref<'info' | 'lessons'>(initialTab as 'info' | 'lessons')
const tabs = [
  { key: 'info' as const, label: 'Thông tin' },
  { key: 'lessons' as const, label: 'Nội dung' },
]

const pageLoading = ref(false)
const submitting = ref(false)
const slugUnlocked = ref(false)
const { errors: formErrors, submitError, clearErrors, handleApiError } = useFormErrors()

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

watch(courseId, (newId, oldId) => {
  if (newId !== oldId) {
    form.value = defaultForm()
    clearErrors()
    activeTab.value = 'info'
  }
})

function autoSlug() {
  const slug = form.value.name
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/đ/g, 'd')
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-')
  form.value = { ...form.value, slug }
}

onMounted(async () => {
  const catRes = await categoryService.flatTree().catch(() => null)
  if (catRes) flatCategories.value = catRes.data.data

  if (isEdit.value) {
    pageLoading.value = true
    try {
      const res = await commissionService.showCourse(courseId.value!)
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
      toast.error('Không thể tải thông tin khóa học.')
      router.push('/teacher/courses')
    } finally {
      pageLoading.value = false
    }
  }
})

async function submitForm() {
  clearErrors()
  submitting.value = true

  const payload: Record<string, unknown> = {
    name: form.value.name,
    slug: form.value.slug,
    description: form.value.description || null,
    price: form.value.price,
    sale_price: form.value.sale_price ?? null,
    level: form.value.level,
    status: form.value.status,
    thumbnail: form.value.thumbnail || null,
  }
  if (form.value.category_id) {
    payload.category_ids = [form.value.category_id]
  }

  try {
    if (isEdit.value) {
      await commissionService.updateCourse(courseId.value!, payload)
      toast.success('Cập nhật khóa học thành công!')
    } else {
      const res = await commissionService.createCourse(payload)
      const newId = res.data.data.id
      toast.success('Tạo khóa học thành công!')
      router.push(`/teacher/courses/${newId}/edit?tab=lessons`)
    }
  } catch (err: unknown) {
    handleApiError(err)
  } finally {
    submitting.value = false
  }
}
</script>
