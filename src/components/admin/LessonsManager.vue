<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ pagination?.total ?? 0 }} bài giảng
      </p>
      <button
        @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors"
      >
        <PlusIcon class="w-4 h-4" />
        Thêm bài giảng
      </button>
    </div>

    <!-- List -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/5 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-white/5">
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3 w-10">#</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Tiêu đề</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Loại</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Preview</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Thời lượng</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Trạng thái</th>
              <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <tr v-if="loading">
              <td colspan="7" class="text-center py-10 text-gray-400">
                <svg class="animate-spin w-6 h-6 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
              </td>
            </tr>
            <tr v-else-if="!lessons.length">
              <td colspan="7" class="text-center py-10 text-gray-400 text-sm">Chưa có bài giảng nào</td>
            </tr>
            <tr
              v-for="lesson in lessons"
              :key="lesson.id"
              class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
            >
              <td class="px-6 py-3 text-gray-400 text-xs">{{ lesson.order + 1 }}</td>
              <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-200 max-w-[220px] truncate">
                {{ lesson.title }}
              </td>
              <td class="px-6 py-3">
                <span
                  :class="typeClass(lesson.type)"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ typeLabel(lesson.type) }}
                </span>
              </td>
              <td class="px-6 py-3 text-gray-500 dark:text-gray-400 text-xs">
                {{ lesson.is_preview ? 'Có' : '—' }}
              </td>
              <td class="px-6 py-3 text-gray-500 dark:text-gray-400 text-xs">
                {{ lesson.duration ? formatSeconds(lesson.duration) : '—' }}
              </td>
              <td class="px-6 py-3">
                <button
                  @click="toggleStatus(lesson)"
                  :disabled="togglingId === lesson.id"
                  :class="lesson.status === 1
                    ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                    : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400'"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium cursor-pointer disabled:opacity-50"
                >
                  {{ lesson.status === 1 ? 'Đã đăng' : 'Nháp' }}
                </button>
              </td>
              <td class="px-6 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="openEdit(lesson)"
                    class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10 transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                  </button>
                  <button
                    @click="confirmDelete(lesson)"
                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Lesson Form -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        @click.self="closeModal"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-5">
            {{ editingId ? 'Chỉnh sửa bài giảng' : 'Thêm bài giảng' }}
          </h3>

          <form @submit.prevent="submitForm" class="space-y-4">
            <div>
              <label class="label-form">Tiêu đề <span class="text-red-500">*</span></label>
              <input v-model="lForm.title" type="text" class="input-field" :class="{ 'input-error': lErrors.title }" placeholder="Giới thiệu khóa học" />
              <p v-if="lErrors.title" class="error-msg">{{ lErrors.title }}</p>
            </div>

            <div>
              <label class="label-form">Loại bài giảng <span class="text-red-500">*</span></label>
              <select v-model="lForm.type" class="input-field">
                <option value="video">Video</option>
                <option value="document">Tài liệu</option>
                <option value="text">Văn bản</option>
              </select>
            </div>

            <div>
              <label class="label-form">Nội dung</label>
              <textarea v-model="lForm.content" rows="3" class="input-field resize-none" placeholder="Nội dung bài giảng..." />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label-form">Thứ tự</label>
                <input v-model.number="lForm.order" type="number" min="0" class="input-field" placeholder="0" />
              </div>
              <div>
                <label class="label-form">Thời lượng (giây)</label>
                <input v-model.number="lForm.duration" type="number" min="0" class="input-field" placeholder="300" />
              </div>
            </div>

            <div class="flex items-center gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="lForm.is_preview" type="checkbox" class="w-4 h-4 rounded border-gray-300" />
                <span class="text-sm text-gray-700 dark:text-gray-400">Cho xem thử (preview)</span>
              </label>
              <div>
                <select v-model="lForm.status" class="input-field w-auto px-3">
                  <option :value="0">Nháp</option>
                  <option :value="1">Đã đăng</option>
                </select>
              </div>
            </div>

            <p v-if="lSubmitError" class="text-sm text-red-500">{{ lSubmitError }}</p>

            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="closeModal" class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                Hủy
              </button>
              <button
                type="submit"
                :disabled="lSubmitting"
                class="px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 disabled:opacity-50 flex items-center gap-2"
              >
                <svg v-if="lSubmitting" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ editingId ? 'Cập nhật' : 'Tạo mới' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Confirm Delete -->
    <Teleport to="body">
      <div
        v-if="deleteTarget"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        @click.self="deleteTarget = null"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-sm p-6">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-2">Xác nhận xóa</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Bạn có chắc muốn xóa bài giảng
            <strong class="text-gray-800 dark:text-white/90">{{ deleteTarget.title }}</strong>?
          </p>
          <div class="flex justify-end gap-3">
            <button @click="deleteTarget = null" class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">Hủy</button>
            <button @click="doDelete" :disabled="deleting" class="px-4 py-2 text-sm rounded-lg bg-red-500 text-white hover:bg-red-600 disabled:opacity-50">
              {{ deleting ? 'Đang xóa...' : 'Xóa' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { PlusIcon, TrashIcon } from '@/icons'
import { lessonsApi } from '@/api/lessonsApi'
import { formatSeconds } from '@/utils/formatDuration'

const props = defineProps<{ courseId: number }>()
const toast = useToast()

interface Lesson {
  id: number
  title: string
  type: string
  content?: string | null
  order: number
  is_preview: boolean
  duration?: number | null
  status: number
}

const lessons    = ref<Lesson[]>([])
const pagination = ref<any>(null)
const loading    = ref(true)
const togglingId = ref<number | null>(null)
const deleteTarget = ref<Lesson | null>(null)
const deleting   = ref(false)

const showModal   = ref(false)
const editingId   = ref<number | null>(null)
const lSubmitting = ref(false)
const lSubmitError = ref('')
const lErrors     = ref<Record<string, string>>({})

const defaultLForm = () => ({
  title: '',
  type: 'video' as string,
  content: '',
  order: 0 as number,
  duration: null as number | null,
  is_preview: false,
  status: 0 as number,
})
const lForm = ref(defaultLForm())

async function fetchLessons() {
  loading.value = true
  try {
    const res = await lessonsApi.index(props.courseId, { per_page: 100 })
    lessons.value = res.data.data
    pagination.value = res.data.pagination
  } catch {
    toast.error('Không thể tải bài giảng')
  } finally {
    loading.value = false
  }
}

onMounted(fetchLessons)

function typeLabel(type: string) {
  return { video: 'Video', document: 'Tài liệu', text: 'Văn bản' }[type] || type
}
function typeClass(type: string) {
  return {
    video:    'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
    document: 'bg-orange-100 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400',
    text:     'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
  }[type] || 'bg-gray-100 text-gray-600'
}

async function toggleStatus(lesson: Lesson) {
  togglingId.value = lesson.id
  try {
    await lessonsApi.toggleStatus(lesson.id)
    lesson.status = lesson.status === 1 ? 0 : 1
  } catch {
    toast.error('Không thể cập nhật trạng thái')
  } finally {
    togglingId.value = null
  }
}

function openCreate() {
  editingId.value = null
  lForm.value = defaultLForm()
  lErrors.value = {}
  lSubmitError.value = ''
  showModal.value = true
}

function openEdit(lesson: Lesson) {
  editingId.value = lesson.id
  lForm.value = {
    title: lesson.title,
    type: lesson.type,
    content: lesson.content || '',
    order: lesson.order,
    duration: lesson.duration ?? null,
    is_preview: lesson.is_preview,
    status: lesson.status,
  }
  lErrors.value = {}
  lSubmitError.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

async function submitForm() {
  lErrors.value = {}
  lSubmitError.value = ''
  if (!lForm.value.title) { lErrors.value.title = 'Vui lòng nhập tiêu đề'; return }

  lSubmitting.value = true
  const payload = {
    title: lForm.value.title,
    type: lForm.value.type,
    content: lForm.value.content || null,
    order: lForm.value.order,
    duration: lForm.value.duration || null,
    is_preview: lForm.value.is_preview,
    status: lForm.value.status,
  }

  try {
    if (editingId.value) {
      await lessonsApi.update(editingId.value, payload)
      toast.success('Cập nhật bài giảng thành công')
    } else {
      await lessonsApi.store(props.courseId, payload)
      toast.success('Tạo bài giảng thành công')
    }
    closeModal()
    fetchLessons()
  } catch (err: any) {
    const data = err.response?.data
    if (err.response?.status === 422 && data?.errors) {
      for (const [key, msgs] of Object.entries(data.errors as Record<string, string[]>)) {
        lErrors.value[key] = msgs[0]
      }
    } else {
      lSubmitError.value = data?.message || 'Có lỗi xảy ra'
    }
  } finally {
    lSubmitting.value = false
  }
}

function confirmDelete(lesson: Lesson) {
  deleteTarget.value = lesson
}

async function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await lessonsApi.destroy(deleteTarget.value.id)
    toast.success('Xóa bài giảng thành công')
    deleteTarget.value = null
    fetchLessons()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Xóa thất bại')
  } finally {
    deleting.value = false
  }
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
