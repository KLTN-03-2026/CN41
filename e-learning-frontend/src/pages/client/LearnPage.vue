<template>
  <div class="flex h-screen overflow-hidden bg-gray-950">
    <!-- Sidebar: danh sách bài giảng -->
    <div
      class="flex-shrink-0 w-80 bg-gray-900 border-r border-gray-800 flex flex-col"
      :class="sidebarOpen ? 'block' : 'hidden lg:flex'"
    >
      <!-- Course title -->
      <div class="p-4 border-b border-gray-800">
        <router-link :to="`/courses/${slug}`" class="flex items-center gap-2 text-gray-400 hover:text-white text-sm mb-3 transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
          </svg>
          Quay lại
        </router-link>
        <h2 class="text-white font-semibold text-sm leading-snug line-clamp-2">{{ courseName }}</h2>
        <!-- Progress -->
        <div class="mt-3">
          <div class="flex justify-between text-xs text-gray-500 mb-1">
            <span>Hoàn thành</span>
            <span>{{ completedCount }}/{{ lessons.length }}</span>
          </div>
          <div class="w-full bg-gray-800 rounded-full h-1">
            <div
              class="bg-blue-500 h-1 rounded-full transition-all duration-500"
              :style="{ width: progressPercent + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Lesson list -->
      <div class="flex-1 overflow-y-auto py-2">
        <div v-if="listLoading" class="space-y-2 p-4">
          <div v-for="i in 6" :key="i" class="h-12 bg-gray-800 rounded-lg animate-pulse"></div>
        </div>
        <button
          v-for="lesson in lessons"
          :key="lesson.id"
          @click="selectLesson(lesson)"
          :class="currentLesson?.id === lesson.id
            ? 'bg-blue-600/20 border-l-2 border-blue-500 text-white'
            : 'text-gray-400 hover:bg-gray-800 hover:text-gray-200 border-l-2 border-transparent'"
          class="w-full text-left px-4 py-3 flex items-start gap-3 transition-colors"
        >
          <div class="mt-0.5 shrink-0">
            <svg
              v-if="lesson.progress?.is_completed"
              class="w-4 h-4 text-green-400"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <svg
              v-else-if="lesson.type === 'video'"
              class="w-4 h-4"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs leading-snug line-clamp-2">{{ lesson.title }}</p>
            <p v-if="lesson.duration" class="text-xs text-gray-600 mt-0.5">
              {{ formatSeconds(lesson.duration) }}
            </p>
          </div>
        </button>
      </div>
    </div>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Top bar -->
      <div class="flex items-center justify-between px-4 py-3 bg-gray-900 border-b border-gray-800 lg:hidden">
        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-400 hover:text-white">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
        <p class="text-white text-sm truncate max-w-[200px]">{{ currentLesson?.title }}</p>
        <div></div>
      </div>

      <!-- Loading -->
      <div v-if="contentLoading" class="flex-1 flex items-center justify-center">
        <svg class="animate-spin w-8 h-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
      </div>

      <!-- No lesson selected -->
      <div v-else-if="!currentLesson" class="flex-1 flex items-center justify-center text-gray-500">
        <p>Chọn một bài giảng để bắt đầu</p>
      </div>

      <!-- Video lesson -->
      <template v-else>
        <div class="flex-1 overflow-y-auto">
          <!-- Video player -->
          <div v-if="currentLesson.type === 'video' && lessonDetail?.video_url" class="bg-black">
            <video
              ref="videoEl"
              :src="lessonDetail.video_url"
              controls
              class="w-full max-h-[60vh]"
              @timeupdate="onTimeUpdate"
              @ended="onVideoEnded"
            ></video>
          </div>
          <div v-else-if="currentLesson.type === 'video'" class="bg-black flex items-center justify-center h-64">
            <p class="text-gray-500 text-sm">Video không khả dụng</p>
          </div>

          <!-- Content -->
          <div class="p-6 max-w-3xl mx-auto">
            <div class="flex items-start justify-between gap-4 mb-4">
              <h1 class="text-xl font-bold text-white">{{ currentLesson.title }}</h1>
              <button
                v-if="!currentLesson.progress?.is_completed"
                @click="markComplete"
                :disabled="markingComplete"
                class="shrink-0 px-4 py-2 text-sm rounded-lg bg-green-500 text-white hover:bg-green-600 disabled:opacity-50 transition-colors"
              >
                {{ markingComplete ? 'Đang lưu...' : 'Đánh dấu hoàn thành' }}
              </button>
              <span v-else class="shrink-0 flex items-center gap-1 text-green-400 text-sm">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Đã hoàn thành
              </span>
            </div>

            <!-- Lesson content -->
            <div
              v-if="lessonDetail?.content"
              class="prose prose-invert prose-sm max-w-none text-gray-300 leading-relaxed"
            >
              <p class="whitespace-pre-line">{{ lessonDetail.content }}</p>
            </div>

            <!-- Document link -->
            <div v-if="currentLesson.type === 'document' && lessonDetail?.document_url" class="mt-4">
              <a
                :href="lessonDetail.document_url"
                target="_blank"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-800 text-gray-300 rounded-xl hover:bg-gray-700 transition-colors text-sm"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Tải tài liệu
              </a>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between mt-8 pt-6 border-t border-gray-800">
              <button
                v-if="prevLesson"
                @click="selectLesson(prevLesson)"
                class="flex items-center gap-2 px-4 py-2 text-sm text-gray-400 hover:text-white transition-colors"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Bài trước
              </button>
              <div v-else></div>
              <button
                v-if="nextLesson"
                @click="selectLesson(nextLesson)"
                class="flex items-center gap-2 px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
              >
                Bài tiếp theo
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { lessonsApi } from '@/api/lessonsApi'
import { formatSeconds } from '@/utils/formatDuration'

const route = useRoute()
const toast = useToast()

const slug         = computed(() => route.params.slug as string)
const courseName   = ref('')
const lessons      = ref<any[]>([])
const listLoading  = ref(true)
const contentLoading = ref(false)
const sidebarOpen  = ref(false)
const markingComplete = ref(false)

const currentLesson = ref<any>(null)
const lessonDetail  = ref<any>(null)
const videoEl       = ref<HTMLVideoElement | null>(null)

// ── Computed ───────────────────────────────────────────────────
const completedCount = computed(() => lessons.value.filter(l => l.progress?.is_completed).length)
const progressPercent = computed(() =>
  lessons.value.length ? Math.round((completedCount.value / lessons.value.length) * 100) : 0
)
const currentIndex = computed(() => lessons.value.findIndex(l => l.id === currentLesson.value?.id))
const prevLesson   = computed(() => currentIndex.value > 0 ? lessons.value[currentIndex.value - 1] : null)
const nextLesson   = computed(() => currentIndex.value < lessons.value.length - 1 ? lessons.value[currentIndex.value + 1] : null)

// ── Init ───────────────────────────────────────────────────────
onMounted(async () => {
  try {
    const res = await lessonsApi.myLessons(slug.value)
    lessons.value = res.data.data
    // Tìm bài chưa hoàn thành đầu tiên
    const first = lessons.value.find(l => !l.progress?.is_completed) || lessons.value[0]
    if (first) selectLesson(first)
  } catch (err: any) {
    if (err.response?.status === 403) {
      toast.error('Bạn chưa mua khóa học này')
    }
  } finally {
    listLoading.value = false
  }
})

// ── Select lesson ─────────────────────────────────────────────
async function selectLesson(lesson: any) {
  currentLesson.value = lesson
  lessonDetail.value = null
  contentLoading.value = true
  sidebarOpen.value = false

  try {
    const res = await lessonsApi.myLessonDetail(slug.value, lesson.slug)
    lessonDetail.value = res.data.data
    courseName.value = res.data.data?.course_name || courseName.value

    // Nếu có video và đã xem một phần, seek đến thời điểm đó
    if (lesson.progress?.watched_seconds && videoEl.value) {
      videoEl.value.currentTime = lesson.progress.watched_seconds
    }
  } catch {
    // Nếu endpoint chưa có, hiển thị nội dung từ list
    lessonDetail.value = lesson
  } finally {
    contentLoading.value = false
  }
}

// ── Video progress ─────────────────────────────────────────────
let progressTimer: ReturnType<typeof setInterval> | null = null
let lastSavedSeconds = 0

function onTimeUpdate() {
  if (!videoEl.value || !currentLesson.value) return
  const current = Math.floor(videoEl.value.currentTime)
  // Save mỗi 10 giây
  if (current - lastSavedSeconds >= 10) {
    lastSavedSeconds = current
    saveProgress(current, false)
  }
}

async function onVideoEnded() {
  if (!currentLesson.value) return
  await saveProgress(currentLesson.value.duration || 0, true)
  markLessonComplete()
}

async function saveProgress(watchedSeconds: number, isCompleted: boolean) {
  if (!currentLesson.value) return
  try {
    await lessonsApi.updateProgress(currentLesson.value.id, {
      watched_seconds: watchedSeconds,
      is_completed: isCompleted,
    })
    if (isCompleted) markLessonComplete()
  } catch {}
}

function markLessonComplete() {
  if (!currentLesson.value) return
  const lesson = lessons.value.find(l => l.id === currentLesson.value!.id)
  if (lesson && !lesson.progress?.is_completed) {
    lesson.progress = { ...lesson.progress, is_completed: true }
    currentLesson.value = { ...currentLesson.value, progress: lesson.progress }
  }
}

async function markComplete() {
  if (!currentLesson.value) return
  markingComplete.value = true
  try {
    const watchedSeconds = videoEl.value ? Math.floor(videoEl.value.currentTime) : (currentLesson.value.duration || 0)
    await lessonsApi.updateProgress(currentLesson.value.id, {
      watched_seconds: watchedSeconds,
      is_completed: true,
    })
    markLessonComplete()
    toast.success('Đã đánh dấu hoàn thành!')
  } catch {
    toast.error('Không thể lưu tiến độ')
  } finally {
    markingComplete.value = false
  }
}

onUnmounted(() => {
  if (progressTimer) clearInterval(progressTimer)
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
