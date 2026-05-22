<template>
  <div class="learn-page">
    <!-- Sidebar -->
    <aside class="learn-sidebar" :class="{ 'sidebar-open': sidebarOpen }">
      <LearnSidebar
        :course-name="courseName"
        :slug="slug"
        :lessons="lessons"
        :sections="sections"
        :expanded-sections="expandedSections"
        :current-lesson="currentLesson"
        :list-loading="listLoading"
        @select-lesson="selectLesson"
        @toggle-section="toggleSection"
      />
    </aside>

    <!-- Overlay for mobile -->
    <div v-if="sidebarOpen" class="sidebar-overlay" @click="sidebarOpen = false"></div>

    <!-- Main content -->
    <main class="learn-main">
      <!-- Top bar -->
      <div class="top-bar">
        <button @click="sidebarOpen = !sidebarOpen" class="menu-toggle">
          <svg
            class="w-5 h-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <div class="top-bar-info">
          <p class="top-bar-title" v-if="currentLesson">{{ currentLesson.title }}</p>
          <p class="top-bar-subtitle" v-if="currentLesson">
            Bài {{ currentIndex + 1 }} / {{ lessons.length }}
          </p>
        </div>
        <div class="top-bar-actions">
          <router-link :to="`/courses/${slug}`" class="top-bar-back" title="Quay lại khóa học">
            <svg
              class="w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </router-link>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="contentLoading" class="content-loading">
        <div class="loading-spinner">
          <svg
            class="animate-spin w-10 h-10"
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
          <p>Đang tải nội dung...</p>
        </div>
      </div>

      <!-- No lesson selected -->
      <div v-else-if="!currentLesson" class="empty-state">
        <div class="empty-icon">
          <svg
            class="w-16 h-16"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="1"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
            />
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </div>
        <h3>Chọn một bài giảng để bắt đầu</h3>
        <p>Hãy chọn bài giảng từ danh sách bên trái</p>
      </div>

      <!-- Lesson content -->
      <template v-else>
        <div class="content-scroll">
          <!-- Video player -->
          <LearnVideoPlayer
            v-if="currentLesson.type === 'video'"
            ref="videoPlayerRef"
            :url="lessonDetail?.video_url"
            :watermark-text="studentAuthStore.student?.email ?? undefined"
            logo-url="/images/logo/logo.svg"
            :watched-seconds="currentLesson.progress?.watched_seconds"
            @timeupdate="onTimeUpdate"
            @ended="onVideoEnded"
          />

          <!-- Enrolled notice for video -->
          <div
            v-if="currentLesson.type === 'video' && isPurchased"
            class="enrolled-notice"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Video được đánh dấu thủy ấn bằng email của bạn để bảo vệ bản quyền. Vui lòng không chia sẻ nội dung này.</span>
          </div>

          <!-- Document viewer -->
          <LearnDocumentViewer
            v-if="currentLesson.type === 'document' && documentUrl"
            :url="documentUrl"
            :title="currentLesson.title"
          />

          <!-- Enrolled notice for document -->
          <div
            v-if="currentLesson.type === 'document' && isPurchased"
            class="enrolled-notice"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Tài liệu dành riêng cho học viên đã đăng ký khóa học. Vui lòng không chia sẻ nội dung này.</span>
          </div>

          <!-- Quiz panel -->
          <LearnQuizPanel
            v-if="currentLesson.type === 'quiz'"
            :lesson-id="currentLesson.id"
            @completed="markComplete(true)"
          />

          <!-- Note panel (chỉ hiện cho học viên đã mua) -->
          <LearnLessonNote
            v-if="isPurchased && currentLesson"
            :lesson-id="currentLesson.id"
            :current-video-time="currentLesson.type === 'video' ? currentVideoTime : undefined"
            @seek-to="seekVideo"
          />

          <!-- Lesson detail -->
          <div class="lesson-content-area">
            <!-- Title + mark complete -->
            <div class="lesson-header">
              <div class="lesson-header-left">
                <h1 class="lesson-main-title">{{ currentLesson.title }}</h1>
                <div class="lesson-header-meta">
                  <span class="lesson-type-tag" :class="'tag-' + currentLesson.type">
                    {{
                      currentLesson.type === 'video'
                        ? '🎬 Video'
                        : currentLesson.type === 'quiz'
                          ? '📝 Bài kiểm tra'
                          : '📄 Tài liệu'
                    }}
                  </span>
                  <span v-if="currentLesson.duration" class="lesson-duration-tag">
                    ⏱ {{ formatSeconds(currentLesson.duration) }}
                  </span>
                </div>
              </div>
              <button
                v-if="isPurchased && !currentLesson.progress?.is_completed"
                @click="markComplete(true)"
                :disabled="markingComplete"
                class="btn-complete"
              >
                <svg
                  v-if="!markingComplete"
                  class="w-5 h-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                <svg
                  v-else
                  class="animate-spin w-5 h-5"
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
                {{ markingComplete ? 'Đang lưu...' : 'Hoàn thành bài học' }}
              </button>
              <button
                v-else-if="isPurchased"
                @click="markComplete(false)"
                :disabled="markingComplete"
                class="btn-completed"
                title="Hủy đánh dấu hoàn thành"
              >
                <svg
                  v-if="!markingComplete"
                  class="w-5 h-5"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"
                  />
                </svg>
                <svg
                  v-else
                  class="animate-spin w-5 h-5"
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
                {{ markingComplete ? 'Đang lưu...' : 'Đã hoàn thành ✓' }}
              </button>
            </div>

            <!-- Lesson text content -->
            <div
              v-if="lessonDetail?.content && !lessonDetail.content.startsWith('/storage/')"
              class="lesson-prose"
            >
              <p class="whitespace-pre-line">{{ lessonDetail.content }}</p>
            </div>

            <!-- Not purchased -->
            <div v-else-if="!isPurchased && !currentLesson?.is_preview" class="lock-overlay">
              <div class="lock-icon-wrap">
                <svg
                  class="w-12 h-12"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="1.5"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                  />
                </svg>
              </div>
              <h3>Nội dung bị khóa</h3>
              <p>Bạn cần mua khóa học để xem nội dung bài giảng này.</p>
              <router-link :to="`/courses/${slug}`" class="btn-buy-course">
                Mua khóa học ngay
              </router-link>
            </div>

            <!-- Navigation -->
            <div class="lesson-nav">
              <button v-if="prevLesson" @click="selectLesson(prevLesson)" class="nav-btn nav-prev">
                <svg
                  class="w-5 h-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <div class="nav-btn-text">
                  <span class="nav-label">Bài trước</span>
                  <span class="nav-title">{{ prevLesson.title }}</span>
                </div>
              </button>
              <div v-else></div>
              <button v-if="nextLesson" @click="selectLesson(nextLesson)" class="nav-btn nav-next">
                <div class="nav-btn-text text-right">
                  <span class="nav-label">Bài tiếp theo</span>
                  <span class="nav-title">{{ nextLesson.title }}</span>
                </div>
                <svg
                  class="w-5 h-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { lessonService } from '@/services/lesson.service'
import { formatSeconds } from '@/utils/formatDuration'
import LearnSidebar from '@/components/shared/client/LearnSidebar.vue'
import LearnVideoPlayer from '@/components/shared/client/LearnVideoPlayer.vue'
import LearnDocumentViewer from '@/components/shared/client/LearnDocumentViewer.vue'
import LearnQuizPanel from '@/components/shared/client/LearnQuizPanel.vue'
import LearnLessonNote from '@/components/shared/client/LearnLessonNote.vue'
import { useStudentAuthStore } from '@/stores/studentAuth.store'

import type { Lesson, Section } from '@/types/common.types'

const route = useRoute()
const toast = useToast()
const studentAuthStore = useStudentAuthStore()

const slug = computed(() => route.params.slug as string)
const courseName = ref('')
const lessons = ref<Lesson[]>([])
const sections = ref<Section[]>([])
const listLoading = ref(true)
const contentLoading = ref(false)
const sidebarOpen = ref(false)
const markingComplete = ref(false)

const currentLesson = ref<Lesson | null>(null)
const lessonDetail = ref<Lesson | null>(null)
const videoPlayerRef = ref<{ videoElement?: HTMLVideoElement } | null>(null)
const isPurchased = ref(true)
const currentVideoTime = ref(0)

const expandedSections = reactive<Record<number, boolean>>({})

// ── Computed ───────────────────────────────────────────────────
const documentUrl = computed(() => {
  if (!lessonDetail.value?.document_id) return null
  const base = import.meta.env.VITE_API_URL ?? '/api/v1'
  const token = studentAuthStore.token
  return `${base}/media/${lessonDetail.value.document_id}/document${token ? '?token=' + encodeURIComponent(token) : ''}`
})

const currentIndex = computed(() =>
  lessons.value.findIndex((l) => l.id === currentLesson.value?.id),
)
const prevLesson = computed(() =>
  currentIndex.value > 0 ? lessons.value[currentIndex.value - 1] : null,
)
const nextLesson = computed(() =>
  currentIndex.value < lessons.value.length - 1 ? lessons.value[currentIndex.value + 1] : null,
)

// ── Helpers ────────────────────────────────────────────────────
function toggleSection(idx: number) {
  expandedSections[idx] = !expandedSections[idx]
}

// ── Init ───────────────────────────────────────────────────────
onMounted(async () => {
  try {
    let rawData = null
    let purchased = false
    
    const { courseService } = await import('@/services/course.service')
    const publicRes = await courseService.publicLessons(slug.value)
    purchased = publicRes.data.data.is_purchased
    
    if (purchased) {
      const res = await lessonService.myLessons(slug.value)
      rawData = res.data.data
    } else {
      rawData = publicRes.data.data
    }

    isPurchased.value = purchased

    const flatLessons: Lesson[] = []
    const sectionList: Section[] = []

    if (rawData) {
      if (rawData.sections) {
        rawData.sections.forEach((sec: Section, idx: number) => {
          const sectionLessons = sec.lessons || []
          flatLessons.push(...sectionLessons)
          sectionList.push({
            id: sec.id,
            title: sec.title,
            lessons: sectionLessons,
          })
          // Mở rộng section đầu tiên
          expandedSections[idx] = idx === 0
        })
      }
      if (rawData.orphan_lessons && rawData.orphan_lessons.length > 0) {
        flatLessons.push(...rawData.orphan_lessons)
        // Thêm orphan lessons như một section không tên
        const orphanIdx = sectionList.length
        sectionList.push({
          id: null,
          title: sectionList.length > 0 ? 'Bài học khác' : '',
          lessons: rawData.orphan_lessons,
        })
        expandedSections[orphanIdx] = true
      }
    }

    lessons.value = flatLessons
    sections.value = sectionList

    // Tìm bài đầu tiên
    let first = lessons.value[0]
    if (purchased) {
      first = lessons.value.find((l) => !l.progress?.is_completed) || lessons.value[0]
    } else {
      first = lessons.value.find((l) => l.is_preview) || lessons.value[0]
    }

    if (first) {
      selectLesson(first)
      // Auto-expand section chứa bài đang chọn
      expandSectionOf(first)
    }
  } catch (err: unknown) {
    const axiosError = err as { response?: { status?: number } }
    if (axiosError.response?.status === 404) {
      toast.error('Không tìm thấy khóa học')
    }
  } finally {
    listLoading.value = false
  }
})

function expandSectionOf(lesson: Lesson) {
  sections.value.forEach((sec, idx) => {
    if (sec.lessons.some((l: Lesson) => l.id === lesson.id)) {
      expandedSections[idx] = true
    }
  })
}

// ── Select lesson ─────────────────────────────────────────────
async function selectLesson(lesson: Lesson) {
  currentLesson.value = lesson
  lessonDetail.value = null
  contentLoading.value = true
  sidebarOpen.value = false
  expandSectionOf(lesson)

  try {
    if (isPurchased.value) {
      const res = await lessonService.myLessonDetail(slug.value, lesson.slug)
      lessonDetail.value = res.data.data
      courseName.value = res.data.data?.course_name || courseName.value
    } else {
      if (!lesson.is_preview) {
        toast.warning('Bạn cần mua khóa học để xem bài giảng này.')
        contentLoading.value = false
        return
      }
      const { courseService } = await import('@/services/course.service')
      const res = await courseService.publicPreviewLesson(slug.value, lesson.slug)
      lessonDetail.value = res.data.data
    }
  } catch {
    lessonDetail.value = lesson
  } finally {
    contentLoading.value = false
  }
}

// ── Video progress ─────────────────────────────────────────────
let lastSavedSeconds = 0

function onTimeUpdate(currentTime: number) {
  currentVideoTime.value = currentTime
  if (!currentLesson.value || !isPurchased.value) return
  if (currentTime - lastSavedSeconds >= 10) {
    lastSavedSeconds = currentTime
    saveProgress(currentTime, false)
  }
}

function seekVideo(seconds: number) {
  if (videoPlayerRef.value?.videoElement) {
    videoPlayerRef.value.videoElement.currentTime = seconds
    videoPlayerRef.value.videoElement.play().catch(() => {})
  }
}

async function onVideoEnded() {
  if (!currentLesson.value || !isPurchased.value) return
  await saveProgress(currentLesson.value.duration || 0, true)
  markLessonComplete()
}

async function saveProgress(watchedSeconds: number, isCompleted: boolean) {
  if (!currentLesson.value) return
  try {
    await lessonService.updateProgress(currentLesson.value.id, {
      watched_seconds: watchedSeconds,
      is_completed: isCompleted,
    })
    if (isCompleted) markLessonComplete()
  } catch {
    // progress save failure is non-critical, don't interrupt the learner
  }
}

function markLessonComplete() {
  if (!currentLesson.value) return
  const lesson = lessons.value.find((l) => l.id === currentLesson.value!.id)
  if (lesson && !lesson.progress?.is_completed) {
    lesson.progress = { ...lesson.progress, is_completed: true }
    currentLesson.value = { ...currentLesson.value, progress: lesson.progress }
  }
}

async function markComplete(isCompleted: boolean) {
  if (!currentLesson.value) return
  markingComplete.value = true
  try {
    const watchedSeconds = videoPlayerRef.value?.videoElement
      ? Math.floor(videoPlayerRef.value.videoElement.currentTime)
      : currentLesson.value.duration || 0
    await lessonService.updateProgress(currentLesson.value.id, {
      watched_seconds: watchedSeconds,
      is_completed: isCompleted,
    })

    const lesson = lessons.value.find((l) => l.id === currentLesson.value!.id)
    if (lesson) {
      if (!lesson.progress) lesson.progress = {}
      lesson.progress.is_completed = isCompleted
      currentLesson.value = { ...currentLesson.value, progress: lesson.progress }
    }

    toast.success(isCompleted ? 'Đã đánh dấu hoàn thành!' : 'Đã hủy đánh dấu hoàn thành.')
  } catch {
    toast.error('Không thể lưu tiến độ')
  } finally {
    markingComplete.value = false
  }
}

</script>

<style src="@/assets/css/learn-page.css"></style>
