<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4"
      @click.self="closeModal"
    >
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-5">
          {{ isEdit ? 'Chỉnh sửa bài giảng' : 'Thêm bài giảng' }}
        </h3>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Chương -->
          <div>
            <label class="label-form">Chương</label>
            <select v-model="localForm.section_id" class="input-field">
              <option :value="null">— Chưa phân chương —</option>
              <option v-for="s in sectionsList" :key="s.id" :value="s.id">{{ s.title }}</option>
            </select>
          </div>

          <!-- Tiêu đề -->
          <div>
            <label class="label-form">Tiêu đề <span class="text-red-500">*</span></label>
            <input v-model="localForm.title" type="text" class="input-field" :class="{ 'input-error': errors.title }" placeholder="Giới thiệu khóa học" />
            <p v-if="errors.title" class="error-msg">{{ errors.title }}</p>
          </div>

          <!-- Loại bài giảng -->
          <div>
            <label class="label-form">Loại bài giảng <span class="text-red-500">*</span></label>
            <select v-model="localForm.type" class="input-field">
              <option value="video">Video</option>
              <option value="document">Tài liệu</option>
            </select>
          </div>

          <!-- Nội dung tải lên -->
          <div>
            <label class="label-form">Nội dung tải lên (Video / Tài liệu) <span class="text-red-500">*</span></label>
            
            <div v-if="localForm.content" class="flex flex-col gap-2 relative">
               <input v-model="localForm.content" type="text" class="input-field pr-10" />
               <button
                type="button"
                @click="localForm.content = ''"
                class="absolute right-2 top-2 w-6 h-6 flex items-center justify-center bg-gray-100 hover:bg-red-100 text-gray-500 hover:text-red-500 rounded-md transition-colors"
               >
                 <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
               </button>
            </div>

            <div
              v-else
              @dragover.prevent=""
              @drop.prevent="handleLessonDrop"
              @click="!lUploading && lFileInput?.click()"
              :class="[lUploading ? 'opacity-70 cursor-not-allowed' : 'cursor-pointer hover:border-blue-400 dark:hover:border-blue-500', 'border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl px-4 py-8 text-center transition-all']"
            >
               <div v-if="lUploading" class="max-w-xs mx-auto">
                  <div class="flex justify-between text-xs text-blue-600 dark:text-blue-400 font-medium mb-2">
                     <span>Đang tải lên...</span>
                     <span>{{ lUploadProgress }}%</span>
                  </div>
                  <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                     <div class="h-full bg-blue-500 rounded-full transition-all duration-300" :style="{ width: lUploadProgress + '%' }" />
                  </div>
               </div>
               <div v-else class="flex flex-col items-center justify-center space-y-2">
                  <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                  </svg>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    <span class="text-blue-500">Nhấp để tải lên</span> hoặc kéo thả file
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ localForm.type === 'video' ? 'Hỗ trợ MP4, WebM (Max: 50MB)' : 'Hỗ trợ PDF, DOCX (Max: 10MB)' }}
                  </p>
               </div>
            </div>
            <input ref="lFileInput" type="file" class="hidden" :accept="localForm.type === 'video' ? 'video/*' : '*/*'" @change="handleLessonFileSelect" />
            <p v-if="lUploadError" class="error-msg mt-2">{{ lUploadError }}</p>
            <p v-if="errors.content" class="error-msg mt-1">{{ errors.content }}</p>
            <p v-if="errors.video_id" class="error-msg mt-1">{{ errors.video_id }}</p>
            <p v-if="errors.document_id" class="error-msg mt-1">{{ errors.document_id }}</p>
          </div>

          <!-- Thời lượng (nếu là video) -->
          <div v-if="localForm.type === 'video'">
            <label class="label-form">Thời lượng (giây)</label>
            <input v-model.number="localForm.duration" type="number" min="0" class="input-field cursor-not-allowed bg-gray-50 dark:bg-gray-800/50" readonly disabled placeholder="Tự động tính khi tải lên video" />
          </div>

          <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="localForm.is_preview" type="checkbox" class="w-4 h-4 rounded border-gray-300" />
              <span class="text-sm text-gray-700 dark:text-gray-400">Cho xem thử (preview)</span>
            </label>
            <div>
              <select v-model="localForm.status" class="input-field w-auto px-3">
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
import { ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { uploadService } from '@/services/upload.service'

const props = defineProps<{
  show: boolean
  isEdit: boolean
  submitting: boolean
  errors: Record<string, string>
  submitError: string
  sectionsList: Array<{ id: number, title: string }>
  form: {
    section_id: number | null
    title: string
    type: string
    content: string | null
    media_id: number | null
    order: number
    duration: number | null
    is_preview: boolean
    status: number
  }
}>()

const emit = defineEmits<{
  'update:show': [value: boolean]
  'update:form': [value: any]
  'submit': [data: any]
}>()

const toast = useToast()

// Local state to avoid mutating props directly
const localForm = ref({ ...props.form })

// Sync local form when modal opens or prop changes
watch(
  () => props.show,
  (isShown) => {
    if (isShown) {
      localForm.value = { ...props.form }
      lUploadError.value = ''
      lUploadProgress.value = 0
    }
  }
)

// Upload state
const lUploading = ref(false)
const lUploadProgress = ref(0)
const lUploadError = ref('')
const lFileInput = ref<HTMLInputElement>()

async function handleLessonDrop(event: DragEvent) {
  const file = event.dataTransfer?.files?.[0]
  if (file) uploadLessonFile(file)
}
async function handleLessonFileSelect(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) await uploadLessonFile(file)
  if (input) input.value = ''
}
function extractVideoDuration(file: File): Promise<number | null> {
  return new Promise((resolve) => {
    const video = document.createElement('video')
    video.preload = 'metadata'
    video.onloadedmetadata = () => {
      window.URL.revokeObjectURL(video.src)
      resolve(Math.round(video.duration))
    }
    video.onerror = () => resolve(null)
    video.src = URL.createObjectURL(file)
  })
}

async function uploadLessonFile(file: File) {
  lUploadError.value = ''
  lUploadProgress.value = 0
  
  if (localForm.value.type === 'video' && !file.type.startsWith('video/')) {
    lUploadError.value = 'Vui lòng chọn file video.'
    return
  }

  if (localForm.value.type === 'video') {
    const duration = await extractVideoDuration(file)
    if (duration) localForm.value.duration = duration
  }
  
  lUploading.value = true
  try {
    const onProgress = (progressEvent: any) => {
      if (progressEvent.total) {
        lUploadProgress.value = Math.round((progressEvent.loaded / progressEvent.total) * 100)
      }
    }
    
    let res;
    if (localForm.value.type === 'video') {
       res = await uploadService.video(file, onProgress)
    } else {
       res = await uploadService.document(file, onProgress)
    }
    
    let url = res.data.data.url
    localForm.value.media_id = res.data.data.id
    try {
      const parsed = new URL(url)
      if (parsed.origin !== window.location.origin) {
        url = parsed.pathname
      }
    } catch {}
    
    localForm.value.content = url
    toast.success('Tải lên thành công')
  } catch (err: any) {
    lUploadError.value = err.response?.data?.message || 'Tải lên thất bại'
  } finally {
    lUploading.value = false
    setTimeout(() => { lUploadProgress.value = 0 }, 1000)
  }
}

function handleSubmit() {
  emit('submit', localForm.value)
}

function closeModal() {
  emit('update:show', false)
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
.input-error {
  @apply border-red-400 focus:ring-red-400/20;
}
.error-msg {
  @apply text-xs text-red-500 mt-1;
}
</style>
