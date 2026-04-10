<template>
  <div class="card-box">
    <h3 class="section-title">Thumbnail</h3>

    <!-- Preview ảnh đã upload -->
    <div v-if="modelValue" class="relative w-fit mb-4">
      <img
        :src="modelValue"
        alt="Thumbnail preview"
        class="w-56 h-32 object-cover rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
      />
      <button
        type="button"
        @click="removeThumbnail"
        class="absolute -top-2 -right-2 w-6 h-6 flex items-center justify-center bg-red-500 hover:bg-red-600 text-white rounded-full shadow-md transition-colors"
        title="Xóa ảnh"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Dropzone khi chưa có ảnh -->
    <div
      v-else
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="handleDrop"
      @click="fileInput?.click()"
      :class="isDragging ? 'border-blue-400 bg-blue-50 dark:bg-blue-500/10' : 'border-gray-300 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-500'"
      class="border-2 border-dashed rounded-xl px-6 py-8 text-center cursor-pointer transition-all"
    >
      <svg class="w-10 h-10 mx-auto text-gray-400 dark:text-gray-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/>
      </svg>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        <span class="text-blue-500 font-medium">Click để chọn</span> hoặc kéo thả ảnh vào đây
      </p>
      <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">JPG, PNG, WebP — tối đa 5MB</p>
    </div>

    <input
      ref="fileInput"
      type="file"
      accept="image/jpeg,image/png,image/webp"
      class="hidden"
      @change="handleFileSelect"
    />

    <!-- Progress bar -->
    <div v-if="uploadProgress > 0 && uploadProgress < 100" class="mt-3">
      <div class="flex justify-between text-xs text-gray-500 mb-1">
        <span>Đang tải lên...</span>
        <span>{{ uploadProgress }}%</span>
      </div>
      <div class="w-full h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
        <div
          class="h-full bg-blue-500 rounded-full transition-all duration-300"
          :style="{ width: uploadProgress + '%' }"
        />
      </div>
    </div>

    <!-- Upload error -->
    <p v-if="uploadError" class="error-msg mt-2">{{ uploadError }}</p>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { uploadService } from '@/services/upload.service'

defineProps<{
  /** URL ảnh hiện tại (2-way binding) */
  modelValue: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', url: string): void
}>()

const toast = useToast()

const isDragging       = ref(false)
const uploadProgress   = ref(0)
const uploadError      = ref('')
const mediaId          = ref<number | null>(null)
const fileInput        = ref<HTMLInputElement>()
const blobPreview      = ref('')

async function handleFileSelect(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) await uploadThumbnail(file)
  input.value = '' // reset để có thể chọn lại cùng file
}

function handleDrop(event: DragEvent) {
  isDragging.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file && file.type.startsWith('image/')) {
    uploadThumbnail(file)
  } else {
    uploadError.value = 'Vui lòng chọn file ảnh (JPG, PNG, WebP)'
  }
}

async function uploadThumbnail(file: File) {
  // Validate client-side
  const maxSize = 5 * 1024 * 1024 // 5MB
  if (file.size > maxSize) {
    uploadError.value = 'File quá lớn. Tối đa 5MB.'
    return
  }
  const allowedTypes = ['image/jpeg', 'image/png', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    uploadError.value = 'Định dạng không hỗ trợ. Chỉ JPG, PNG, WebP.'
    return
  }

  uploadError.value = ''
  uploadProgress.value = 0

  // Hiển thị preview ngay lập tức bằng blob URL
  if (blobPreview.value) URL.revokeObjectURL(blobPreview.value)
  blobPreview.value = URL.createObjectURL(file)
  emit('update:modelValue', blobPreview.value)

  try {
    const res = await uploadService.image(file, 'thumbnails', (progressEvent: { total?: number; loaded: number }) => {
      if (progressEvent.total) {
        uploadProgress.value = Math.round((progressEvent.loaded / progressEvent.total) * 100)
      }
    })

    const media = res.data.data as { id: number; url: string }
    // Normalize URL: nếu là absolute URL của backend, chuyển về relative path để dùng proxy
    let url: string = media.url
    try {
      const parsed = new URL(url)
      if (parsed.origin !== window.location.origin) {
        url = parsed.pathname
      }
    } catch {
      // url đã là relative path, giữ nguyên
    }
    // Set URL mới trước khi revoke blob để tránh flash trắng
    emit('update:modelValue', url)
    mediaId.value = media.id
    // Revoke sau khi browser đã nhận URL mới
    const blobToRevoke = blobPreview.value
    blobPreview.value = ''
    // Delay 100ms trước khi revoke blob url để browser kịp render URL mới trên DOM, tránh bị chớp ảnh (flicker)
    setTimeout(() => URL.revokeObjectURL(blobToRevoke), 100)
    uploadProgress.value = 100
    toast.success('Upload ảnh thành công')

    // Reset progress sau 1 giây
    setTimeout(() => { uploadProgress.value = 0 }, 1000)
  } catch (err: unknown) {
    const axiosError = err as { response?: { data?: { message?: string } } }
    // Rollback preview nếu upload thất bại
    URL.revokeObjectURL(blobPreview.value)
    blobPreview.value = ''
    emit('update:modelValue', '')
    uploadProgress.value = 0
    const msg = axiosError.response?.data?.message || 'Upload ảnh thất bại'
    uploadError.value = msg
  }
}

async function removeThumbnail() {
  // Nếu có media_id → gọi API xóa file trên server
  if (mediaId.value) {
    try {
      await uploadService.destroy(mediaId.value)
    } catch {
      // Bỏ qua lỗi xóa — vẫn cho xóa trên FE
    }
  }
  if (blobPreview.value) {
    URL.revokeObjectURL(blobPreview.value)
    blobPreview.value = ''
  }
  emit('update:modelValue', '')
  mediaId.value = null
  uploadError.value = ''
}
</script>

<style scoped>
.card-box {
  @apply bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-700 rounded-2xl p-5;
}
.section-title {
  @apply text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4;
}
.error-msg {
  @apply text-xs text-red-500 mt-1;
}
</style>
