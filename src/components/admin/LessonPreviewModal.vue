<template>
  <Teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/70 px-4 py-6 overflow-hidden" @click.self="close">
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-4xl max-h-full flex flex-col relative animate-in fade-in zoom-in duration-200">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
          <div class="flex items-center gap-3 overflow-hidden">
            <div :class="typeClass" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
              <svg v-if="lesson?.type === 'video'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              <svg v-else-if="lesson?.type === 'document'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
              <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="overflow-hidden">
              <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 truncate">{{ lesson?.title }}</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ lesson?.type }} content</p>
            </div>
          </div>
          <button @click="close" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg transition-colors">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Content Body -->
        <div class="flex-1 overflow-y-auto p-0 sm:p-6 bg-gray-50 dark:bg-black/20">
          <div v-if="loading" class="flex flex-col items-center justify-center h-64 text-gray-400">
            <svg class="animate-spin h-8 w-8 text-blue-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm">Đang tải nội dung...</p>
          </div>

          <div v-else-if="!lesson" class="flex items-center justify-center h-64 text-red-400">
            Lỗi: Không tìm thấy nội dung bài học.
          </div>

          <div v-else class="h-full">
            <!-- VIDEO TYPE -->
            <div v-if="lesson.type === 'video'" class="w-full flex justify-center">
              <div v-if="mediaUrl" class="w-full aspect-video bg-black rounded-xl overflow-hidden shadow-lg border border-gray-200 dark:border-gray-800">
                <video
                  ref="videoPlayerRef"
                  controls
                  class="w-full h-full"
                  :src="mediaUrl"
                  playsinline
                >
                  Trình duyệt không hỗ trợ xem video.
                </video>
              </div>
              <div v-else class="flex flex-col items-center justify-center py-20 text-gray-400">
                <svg class="w-16 h-16 mb-4 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <p>Bài học này chưa có file video.</p>
              </div>
            </div>

            <!-- DOCUMENT TYPE -->
            <div v-else-if="lesson.type === 'document'" class="h-full flex flex-col">
              <div v-if="mediaUrl" class="flex-1 min-h-[500px] bg-white rounded-xl overflow-hidden flex flex-col shadow-lg border border-gray-200 dark:border-gray-800">
                <!-- PDF Preview -->
                <iframe
                  v-if="isPdf"
                  :src="mediaUrl"
                  class="w-full flex-1"
                  loading="lazy"
                  frameborder="0"
                ></iframe>
                
                <!-- Other files (Docx, Excel) preview using Google Docs (only works for public URLs) -->
                <iframe
                  v-else-if="!isLocalhost"
                  :src="`https://docs.google.com/viewer?url=${encodeURIComponent(mediaUrl)}&embedded=true`"
                  class="w-full flex-1"
                  loading="lazy"
                  frameborder="0"
                ></iframe>

                <!-- Fallback when localhost and not PDF -->
                <div v-else class="flex-1 flex flex-col items-center justify-center p-8 text-center text-gray-500">
                  <svg class="w-16 h-16 mb-4 text-orange-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  <p class="mb-2 text-gray-700 dark:text-gray-300 font-medium">Không thể xem trước loại tài liệu này ở môi trường phát triển (localhost).</p>
                  <p class="text-sm mb-6">Trình duyệt không hỗ trợ xem trực tiếp và Google Docs Viewer không thể truy cập localhost.</p>
                  <a :href="mediaUrl" target="_blank" class="px-5 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium transition-colors shadow-sm">
                    Tải xuống / Mở bằng phần mềm
                  </a>
                </div>

                <div v-if="isPdf || !isLocalhost" class="p-3 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 flex justify-center flex-shrink-0">
                  <a :href="mediaUrl" target="_blank" class="text-blue-500 hover:text-blue-600 hover:underline text-sm flex items-center gap-1.5 font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    Mở tài liệu trong tab mới
                  </a>
                </div>
              </div>
              <div v-else class="flex flex-col items-center justify-center py-20 text-gray-400">
                <svg class="w-16 h-16 mb-4 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <p>Bài học này chưa có file tài liệu.</p>
              </div>
            </div>

            <!-- TEXT TYPE -->
            <div v-else-if="lesson.type === 'text'" class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 max-w-none text-gray-800 dark:text-gray-200 leading-relaxed">
              <div v-if="lesson.content" v-html="lesson.content" class="rich-text-content"></div>
              <p v-else class="text-gray-400 italic">Nội dung văn bản trống.</p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex justify-end bg-gray-50/50 dark:bg-white/5">
          <button @click="close" class="px-5 py-2 text-sm font-medium rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
            Đóng
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAdminAuthStore } from '@/stores/adminAuth'

const adminAuth = useAdminAuthStore()

const props = defineProps({
  lesson: {
    type: Object,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const isOpen = ref(false)
const emit = defineEmits(['close'])

const videoPlayerRef = ref<HTMLVideoElement | null>(null)

// --- Xử lý Hotkeys cho Video ---
const handleKeydown = (e: KeyboardEvent) => {
  if (!isOpen.value || !videoPlayerRef.value || props.lesson?.type !== 'video') return

  // Không can thiệp khi user đang gõ trong input/textarea/contenteditable
  const target = e.target as HTMLElement
  if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target.isContentEditable) return

  const video = videoPlayerRef.value
  const skipTime = 5 // 5 giây tiêu chuẩn

  switch (e.code) {
    case 'Space':
      e.preventDefault()
      if (video.paused) video.play()
      else video.pause()
      break
    case 'ArrowRight':
      e.preventDefault()
      video.currentTime = Math.min(video.duration, video.currentTime + skipTime)
      break
    case 'ArrowLeft':
      e.preventDefault()
      video.currentTime = Math.max(0, video.currentTime - skipTime)
      break
    case 'ArrowUp':
      e.preventDefault()
      video.volume = Math.min(1, video.volume + 0.1)
      break
    case 'ArrowDown':
      e.preventDefault()
      video.volume = Math.max(0, video.volume - 0.1)
      break
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})
// -----------------------------

const close = () => {
  if (videoPlayerRef.value) {
    videoPlayerRef.value.pause()
    // Xóa src + gọi load() để browser hủy network connection và giải phóng RAM buffer
    videoPlayerRef.value.removeAttribute('src')
    videoPlayerRef.value.load()
  }
  isOpen.value = false
  emit('close')
}

const open = () => {
  isOpen.value = true
}

defineExpose({ open, close })

const typeClass = computed(() => {
  switch (props.lesson?.type) {
    case 'video': return 'bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400'
    case 'document': return 'bg-orange-100 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400'
    case 'text': return 'bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-400'
    default: return 'bg-gray-100 text-gray-600'
  }
})

// Lấy media URL từ quan hệ video hoặc document
const mediaUrl = computed(() => {
  if (!props.lesson) return null
  if (props.lesson.type === 'video' && props.lesson.video?.id) {
    // Truyền token qua query param vì <video src> không gửi Authorization header
    const token = adminAuth.token
    return `/api/v1/media/${props.lesson.video.id}/stream${token ? `?token=${token}` : ''}`
  }
  if (props.lesson.type === 'document' && props.lesson.document?.url) {
    return normalizeUrl(props.lesson.document.url)
  }
  return null
})

const isPdf = computed(() => {
  if (!props.lesson || props.lesson.type !== 'document') return false
  if (props.lesson.document?.mime_type === 'application/pdf') return true
  if (props.lesson.document?.url?.toLowerCase().endsWith('.pdf')) return true
  return false
})

const isLocalhost = computed(() => {
  const hostname = window.location.hostname
  return hostname === 'localhost' || hostname === '127.0.0.1' || hostname.endsWith('.localhost') || hostname.endsWith('.test')
})

function normalizeUrl(url: string) {
  if (!url) return ''
  if (url.startsWith('http')) return url
  // Nếu là relative path, thêm proxy prefix /api (nếu cần) hoặc rely vào Vite proxy
  return url
}
</script>

<style scoped>
@keyframes zoom-in {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
.animate-in {
  animation: zoom-in 0.2s ease-out;
}
</style>
