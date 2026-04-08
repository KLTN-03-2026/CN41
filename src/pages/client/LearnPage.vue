<template>
  <div class="learn-page">
    <!-- Sidebar -->
    <aside class="learn-sidebar" :class="{ 'sidebar-open': sidebarOpen }">
      <!-- Header sidebar -->
      <div class="sidebar-header">
        <router-link :to="`/courses/${slug}`" class="back-link">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
          </svg>
          <span>Quay lại khóa học</span>
        </router-link>
        <h2 class="course-title">{{ courseName }}</h2>

        <!-- Progress bar -->
        <div class="progress-area">
          <div class="progress-info">
            <span class="progress-label">Tiến độ học tập</span>
            <span class="progress-count">{{ completedCount }}/{{ lessons.length }} bài</span>
          </div>
          <div class="progress-bar-bg">
            <div
              class="progress-bar-fill"
              :style="{ width: progressPercent + '%' }"
            ></div>
          </div>
          <span class="progress-percent">{{ progressPercent }}%</span>
        </div>
      </div>

      <!-- Lesson list -->
      <div class="sidebar-body">
        <div v-if="listLoading" class="skeleton-list">
          <div v-for="i in 6" :key="i" class="skeleton-item"></div>
        </div>

        <template v-else>
          <!-- Sections with accordion -->
          <div v-for="(section, sIdx) in sections" :key="section.id || sIdx" class="section-group">
            <button
              v-if="section.title"
              @click="toggleSection(sIdx)"
              class="section-header"
            >
              <div class="section-header-left">
                <svg
                  class="section-chevron"
                  :class="{ 'chevron-open': expandedSections[sIdx] }"
                  fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="section-title">{{ section.title }}</span>
              </div>
              <span class="section-count">{{ section.lessons.length }} bài</span>
            </button>

            <div
              class="section-lessons"
              :class="{ 'section-collapsed': section.title && !expandedSections[sIdx] }"
            >
              <button
                v-for="lesson in section.lessons"
                :key="lesson.id"
                @click="selectLesson(lesson)"
                class="lesson-item"
                :class="{
                  'lesson-active': currentLesson?.id === lesson.id,
                  'lesson-completed': lesson.progress?.is_completed
                }"
              >
                <div class="lesson-index">
                  <svg
                    v-if="lesson.progress?.is_completed"
                    class="lesson-icon completed-icon"
                    fill="currentColor" viewBox="0 0 20 20"
                  >
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  <span v-else-if="currentLesson?.id === lesson.id" class="playing-indicator">
                    <span class="playing-bar"></span>
                    <span class="playing-bar"></span>
                    <span class="playing-bar"></span>
                  </span>
                  <span v-else class="lesson-number">{{ getLessonGlobalIndex(lesson) }}</span>
                </div>
                <div class="lesson-info">
                  <p class="lesson-title-text">{{ lesson.title }}</p>
                  <div class="lesson-meta">
                    <span class="lesson-type-badge" :class="'type-' + lesson.type">
                      <svg v-if="lesson.type === 'video'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                      </svg>
                      <svg v-else class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                      </svg>
                      {{ lesson.type === 'video' ? 'Video' : 'Tài liệu' }}
                    </span>
                    <span v-if="lesson.duration" class="lesson-duration">{{ formatSeconds(lesson.duration) }}</span>
                  </div>
                </div>
              </button>
            </div>
          </div>
        </template>
      </div>
    </aside>

    <!-- Overlay for mobile -->
    <div
      v-if="sidebarOpen"
      class="sidebar-overlay"
      @click="sidebarOpen = false"
    ></div>

    <!-- Main content -->
    <main class="learn-main">
      <!-- Top bar -->
      <div class="top-bar">
        <button @click="sidebarOpen = !sidebarOpen" class="menu-toggle">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
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
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </router-link>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="contentLoading" class="content-loading">
        <div class="loading-spinner">
          <svg class="animate-spin w-10 h-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <p>Đang tải nội dung...</p>
        </div>
      </div>

      <!-- No lesson selected -->
      <div v-else-if="!currentLesson" class="empty-state">
        <div class="empty-icon">
          <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h3>Chọn một bài giảng để bắt đầu</h3>
        <p>Hãy chọn bài giảng từ danh sách bên trái</p>
      </div>

      <!-- Lesson content -->
      <template v-else>
        <div class="content-scroll">
          <!-- Video player -->
          <div v-if="currentLesson.type === 'video' && lessonDetail?.video_url" class="video-wrapper">
            <video
              ref="videoEl"
              :src="lessonDetail.video_url"
              controls
              class="video-player"
              @timeupdate="onTimeUpdate"
              @ended="onVideoEnded"
            ></video>
          </div>
          <div v-else-if="currentLesson.type === 'video'" class="video-placeholder">
            <svg class="w-16 h-16 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <p>Video không khả dụng</p>
          </div>

          <!-- Document viewer (hiện trực tiếp, ngang hàng video) -->
          <div
            v-if="currentLesson.type === 'document' && lessonDetail?.document_url"
            class="doc-viewer-main"
            :class="{ 'doc-fullscreen': docFullscreen }"
          >
            <div class="doc-embed-wrapper">
              <!-- Toolbar -->
              <div class="doc-toolbar">
                <div class="doc-toolbar-left">
                  <div class="doc-file-badge">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>{{ getFileExtension(lessonDetail.document_url) }}</span>
                  </div>
                  <span class="doc-toolbar-filename">{{ getFileName(lessonDetail.document_url) }}</span>
                </div>
                <div class="doc-toolbar-right">
                  <a
                    :href="lessonDetail.document_url"
                    download
                    class="doc-tool-btn"
                    title="Tải xuống"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                  </a>
                  <a
                    :href="lessonDetail.document_url"
                    target="_blank"
                    class="doc-tool-btn"
                    title="Mở trong tab mới"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                  </a>
                  <button
                    @click="docFullscreen = !docFullscreen"
                    class="doc-tool-btn"
                    :title="docFullscreen ? 'Thoát toàn màn hình' : 'Toàn màn hình'"
                  >
                    <svg v-if="!docFullscreen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/>
                    </svg>
                  </button>
                </div>
              </div>
              <!-- Embed frame -->
              <div class="doc-embed-frame">
                <!-- PDF -->
                <iframe
                  v-if="isPdfUrl(lessonDetail.document_url)"
                  :src="lessonDetail.document_url"
                  class="doc-iframe"
                  frameborder="0"
                  allowfullscreen
                ></iframe>
                <!-- Ảnh -->
                <div v-else-if="isImageUrl(lessonDetail.document_url)" class="doc-image-viewer">
                  <img
                    :src="lessonDetail.document_url"
                    :alt="currentLesson.title"
                    class="doc-image"
                  />
                </div>
                <!-- File khác -->
                <div v-else class="doc-file-card">
                  <div class="doc-file-icon-large">
                    <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="doc-ext-label">{{ getFileExtension(lessonDetail.document_url) }}</span>
                  </div>
                  <p class="doc-file-name">{{ getFileName(lessonDetail.document_url) }}</p>
                  <p class="doc-file-hint">Định dạng này chưa hỗ trợ xem trực tiếp</p>
                  <div class="doc-file-actions">
                    <a :href="lessonDetail.document_url" target="_blank" class="doc-action-btn primary">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                      </svg>
                      Mở tài liệu
                    </a>
                    <a :href="lessonDetail.document_url" download class="doc-action-btn secondary">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                      </svg>
                      Tải xuống
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Lesson detail -->
          <div class="lesson-content-area">
            <!-- Title + mark complete -->
            <div class="lesson-header">
              <div class="lesson-header-left">
                <h1 class="lesson-main-title">{{ currentLesson.title }}</h1>
                <div class="lesson-header-meta">
                  <span class="lesson-type-tag" :class="'tag-' + currentLesson.type">
                    {{ currentLesson.type === 'video' ? '🎬 Video' : '📄 Tài liệu' }}
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
                <svg v-if="!markingComplete" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg v-else class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
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
                <svg v-if="!markingComplete" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <svg v-else class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
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
                <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
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
              <button
                v-if="prevLesson"
                @click="selectLesson(prevLesson)"
                class="nav-btn nav-prev"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                <div class="nav-btn-text">
                  <span class="nav-label">Bài trước</span>
                  <span class="nav-title">{{ prevLesson.title }}</span>
                </div>
              </button>
              <div v-else></div>
              <button
                v-if="nextLesson"
                @click="selectLesson(nextLesson)"
                class="nav-btn nav-next"
              >
                <div class="nav-btn-text text-right">
                  <span class="nav-label">Bài tiếp theo</span>
                  <span class="nav-title">{{ nextLesson.title }}</span>
                </div>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
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
import { ref, computed, onMounted, onUnmounted, reactive, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { lessonsApi } from '@/api/lessonsApi'
import { formatSeconds } from '@/utils/formatDuration'

const route = useRoute()
const toast = useToast()

const slug         = computed(() => route.params.slug as string)
const courseName   = ref('')
const lessons      = ref<any[]>([])
const sections     = ref<any[]>([])
const listLoading  = ref(true)
const contentLoading = ref(false)
const sidebarOpen  = ref(false)
const markingComplete = ref(false)

const currentLesson = ref<any>(null)
const lessonDetail  = ref<any>(null)
const videoEl       = ref<HTMLVideoElement | null>(null)
const isPurchased   = ref(true)
const docFullscreen = ref(false)

const expandedSections = reactive<Record<number, boolean>>({})

// ── Computed ───────────────────────────────────────────────────
const completedCount = computed(() => lessons.value.filter(l => l.progress?.is_completed).length)
const progressPercent = computed(() =>
  lessons.value.length ? Math.round((completedCount.value / lessons.value.length) * 100) : 0
)
const currentIndex = computed(() => lessons.value.findIndex(l => l.id === currentLesson.value?.id))
const prevLesson   = computed(() => currentIndex.value > 0 ? lessons.value[currentIndex.value - 1] : null)
const nextLesson   = computed(() => currentIndex.value < lessons.value.length - 1 ? lessons.value[currentIndex.value + 1] : null)

// ── Helpers ────────────────────────────────────────────────────
function toggleSection(idx: number) {
  expandedSections[idx] = !expandedSections[idx]
}

function getLessonGlobalIndex(lesson: any) {
  const idx = lessons.value.findIndex(l => l.id === lesson.id)
  return idx >= 0 ? idx + 1 : ''
}

// Kiểm tra URL có phải PDF không
function isPdfUrl(url: string): boolean {
  if (!url) return false
  const lower = url.toLowerCase()
  return lower.endsWith('.pdf') || lower.includes('.pdf?')
}

// Kiểm tra URL có phải ảnh không
function isImageUrl(url: string): boolean {
  if (!url) return false
  const lower = url.toLowerCase()
  return /\.(jpg|jpeg|png|gif|webp|svg|bmp)(\?|$)/.test(lower)
}

// Lấy tên file từ URL
function getFileName(url: string): string {
  if (!url) return 'Tài liệu'
  try {
    const pathname = new URL(url, window.location.origin).pathname
    const name = pathname.split('/').pop() || 'Tài liệu'
    return decodeURIComponent(name)
  } catch {
    return 'Tài liệu'
  }
}

// Lấy phần mở rộng file
function getFileExtension(url: string): string {
  if (!url) return 'FILE'
  try {
    const name = new URL(url, window.location.origin).pathname.split('/').pop() || ''
    const ext = name.split('.').pop()?.toUpperCase() || 'FILE'
    return ext
  } catch {
    return 'FILE'
  }
}

// ── Init ───────────────────────────────────────────────────────
onMounted(async () => {
  try {
    let rawData = null
    let purchased = true
    try {
      const res = await lessonsApi.myLessons(slug.value)
      rawData = res.data.data
    } catch (err: any) {
      if (err.response?.status === 403 || err.response?.status === 401) {
        purchased = false
        const { coursesApi } = await import('@/api/coursesApi')
        const res2 = await coursesApi.publicLessons(slug.value)
        rawData = res2.data.data
      } else {
        throw err
      }
    }

    isPurchased.value = purchased

    const flatLessons: any[] = []
    const sectionList: any[] = []

    if (rawData) {
      if (rawData.sections) {
        rawData.sections.forEach((sec: any, idx: number) => {
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
       first = lessons.value.find(l => !l.progress?.is_completed) || lessons.value[0]
    } else {
       first = lessons.value.find(l => l.is_preview) || lessons.value[0]
    }

    if (first) {
      selectLesson(first)
      // Auto-expand section chứa bài đang chọn
      expandSectionOf(first)
    }
  } catch (err: any) {
    if (err.response?.status === 404) {
      toast.error('Không tìm thấy khóa học')
    }
  } finally {
    listLoading.value = false
  }
})

function expandSectionOf(lesson: any) {
  sections.value.forEach((sec, idx) => {
    if (sec.lessons.some((l: any) => l.id === lesson.id)) {
      expandedSections[idx] = true
    }
  })
}

// ── Select lesson ─────────────────────────────────────────────
async function selectLesson(lesson: any) {
  currentLesson.value = lesson
  lessonDetail.value = null
  contentLoading.value = true
  sidebarOpen.value = false
  expandSectionOf(lesson)

  try {
    if (isPurchased.value) {
      const res = await lessonsApi.myLessonDetail(slug.value, lesson.slug)
      lessonDetail.value = res.data.data
      courseName.value = res.data.data?.course_name || courseName.value

      await nextTick()
      if (lesson.progress?.watched_seconds && videoEl.value) {
        videoEl.value.currentTime = lesson.progress.watched_seconds
      }
    } else {
      if (!lesson.is_preview) {
         toast.warning('Bạn cần mua khóa học để xem bài giảng này.')
         contentLoading.value = false
         return
      }
      const { coursesApi } = await import('@/api/coursesApi')
      const res = await coursesApi.publicPreviewLesson(slug.value, lesson.slug)
      lessonDetail.value = res.data.data
    }
  } catch {
    lessonDetail.value = lesson
  } finally {
    contentLoading.value = false
  }
}

// ── Video progress ─────────────────────────────────────────────
let progressTimer: ReturnType<typeof setInterval> | null = null
let lastSavedSeconds = 0

function onTimeUpdate() {
  if (!videoEl.value || !currentLesson.value || !isPurchased.value) return
  const current = Math.floor(videoEl.value.currentTime)
  if (current - lastSavedSeconds >= 10) {
    lastSavedSeconds = current
    saveProgress(current, false)
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

async function markComplete(isCompleted: boolean) {
  if (!currentLesson.value) return
  markingComplete.value = true
  try {
    const watchedSeconds = videoEl.value ? Math.floor(videoEl.value.currentTime) : (currentLesson.value.duration || 0)
    await lessonsApi.updateProgress(currentLesson.value.id, {
      watched_seconds: watchedSeconds,
      is_completed: isCompleted,
    })
    
    const lesson = lessons.value.find(l => l.id === currentLesson.value!.id)
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

onUnmounted(() => {
  if (progressTimer) clearInterval(progressTimer)
})
</script>

<style scoped>
/* ── Variables ────────────────────────────────────────────── */
:root {
  --sidebar-w: 340px;
  --topbar-h: 56px;
}

/* ── Layout ───────────────────────────────────────────────── */
.learn-page {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background: #0a0a0f;
  font-family: 'Inter', 'Outfit', sans-serif;
}

/* ── Sidebar ──────────────────────────────────────────────── */
.learn-sidebar {
  width: 340px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, #0f1019 0%, #12121e 100%);
  border-right: 1px solid rgba(255,255,255,0.06);
  z-index: 30;
}

.sidebar-header {
  padding: 20px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: rgba(255,255,255,0.45);
  text-decoration: none;
  transition: color 0.2s;
  margin-bottom: 12px;
}

.back-link:hover {
  color: rgba(255,255,255,0.8);
}

.course-title {
  font-size: 15px;
  font-weight: 600;
  color: #fff;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin: 0 0 16px 0;
}

/* ── Progress ─────────────────────────────────────────────── */
.progress-area {
  background: rgba(255,255,255,0.03);
  border-radius: 12px;
  padding: 12px 14px;
  border: 1px solid rgba(255,255,255,0.04);
}

.progress-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.progress-label {
  font-size: 11px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: rgba(255,255,255,0.35);
}

.progress-count {
  font-size: 12px;
  font-weight: 600;
  color: rgba(255,255,255,0.6);
}

.progress-bar-bg {
  width: 100%;
  height: 4px;
  background: rgba(255,255,255,0.08);
  border-radius: 100px;
  overflow: hidden;
}

.progress-bar-fill {
  height: 100%;
  border-radius: 100px;
  background: linear-gradient(90deg, #6366f1, #8b5cf6, #a78bfa);
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.progress-bar-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.progress-percent {
  display: block;
  text-align: right;
  font-size: 11px;
  font-weight: 600;
  color: #a78bfa;
  margin-top: 6px;
}

/* ── Sidebar Body ─────────────────────────────────────────── */
.sidebar-body {
  flex: 1;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,0.1) transparent;
}

.sidebar-body::-webkit-scrollbar {
  width: 4px;
}

.sidebar-body::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.1);
  border-radius: 100px;
}

/* ── Skeleton ─────────────────────────────────────────────── */
.skeleton-list {
  padding: 12px 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.skeleton-item {
  height: 52px;
  background: rgba(255,255,255,0.04);
  border-radius: 10px;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

/* ── Sections ─────────────────────────────────────────────── */
.section-group {
  border-bottom: 1px solid rgba(255,255,255,0.04);
}

.section-group:last-child {
  border-bottom: none;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 14px 20px;
  background: rgba(255,255,255,0.02);
  border: none;
  cursor: pointer;
  transition: background 0.2s;
}

.section-header:hover {
  background: rgba(255,255,255,0.04);
}

.section-header-left {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.section-chevron {
  width: 16px;
  height: 16px;
  color: rgba(255,255,255,0.3);
  flex-shrink: 0;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.chevron-open {
  transform: rotate(90deg);
}

.section-title {
  font-size: 12px;
  font-weight: 600;
  color: rgba(255,255,255,0.7);
  text-transform: uppercase;
  letter-spacing: 0.3px;
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.section-count {
  font-size: 11px;
  color: rgba(255,255,255,0.3);
  flex-shrink: 0;
}

/* ── Section collapse ─────────────────────────────────────── */
.section-lessons {
  max-height: 2000px;
  overflow: hidden;
  transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1),
              opacity 0.3s ease;
  opacity: 1;
}

.section-collapsed {
  max-height: 0;
  opacity: 0;
}

/* ── Lesson Items ─────────────────────────────────────────── */
.lesson-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  width: 100%;
  text-align: left;
  padding: 12px 20px;
  background: transparent;
  border: none;
  border-left: 3px solid transparent;
  cursor: pointer;
  transition: all 0.2s;
}

.lesson-item:hover {
  background: rgba(255,255,255,0.03);
}

.lesson-active {
  background: rgba(99, 102, 241, 0.08) !important;
  border-left-color: #6366f1;
}

.lesson-completed .lesson-title-text {
  color: rgba(255,255,255,0.4);
}

.lesson-index {
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 2px;
}

.lesson-number {
  font-size: 11px;
  font-weight: 600;
  color: rgba(255,255,255,0.25);
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: rgba(255,255,255,0.04);
}

.lesson-active .lesson-number {
  background: rgba(99, 102, 241, 0.2);
  color: #a78bfa;
}

.completed-icon {
  width: 20px;
  height: 20px;
  color: #34d399;
}

/* ── Playing indicator ────────────────────────────────────── */
.playing-indicator {
  display: flex;
  align-items: flex-end;
  gap: 2px;
  height: 16px;
}

.playing-bar {
  width: 3px;
  background: #6366f1;
  border-radius: 2px;
  animation: playingBounce 0.8s ease-in-out infinite;
}

.playing-bar:nth-child(1) { height: 8px; animation-delay: 0s; }
.playing-bar:nth-child(2) { height: 14px; animation-delay: 0.15s; }
.playing-bar:nth-child(3) { height: 10px; animation-delay: 0.3s; }

@keyframes playingBounce {
  0%, 100% { transform: scaleY(0.5); }
  50% { transform: scaleY(1); }
}

/* ── Lesson Info ──────────────────────────────────────────── */
.lesson-info {
  min-width: 0;
  flex: 1;
}

.lesson-title-text {
  font-size: 13px;
  line-height: 1.5;
  color: rgba(255,255,255,0.75);
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.lesson-active .lesson-title-text {
  color: #fff;
  font-weight: 500;
}

.lesson-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 4px;
}

.lesson-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: 10px;
  font-weight: 500;
  padding: 2px 6px;
  border-radius: 4px;
  letter-spacing: 0.2px;
}

.type-video {
  color: #818cf8;
  background: rgba(99, 102, 241, 0.1);
}

.type-document {
  color: #fb923c;
  background: rgba(251, 146, 60, 0.1);
}

.lesson-duration {
  font-size: 11px;
  color: rgba(255,255,255,0.3);
}

/* ── Sidebar Overlay (mobile) ─────────────────────────────── */
.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(4px);
  z-index: 25;
  display: none;
}

/* ── Main Content ─────────────────────────────────────────── */
.learn-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-width: 0;
}

/* ── Top Bar ──────────────────────────────────────────────── */
.top-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 20px;
  height: 56px;
  background: rgba(15, 16, 25, 0.95);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255,255,255,0.06);
  flex-shrink: 0;
}

.menu-toggle {
  display: none;
  color: rgba(255,255,255,0.6);
  background: none;
  border: none;
  padding: 6px;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s;
}

.menu-toggle:hover {
  background: rgba(255,255,255,0.06);
  color: #fff;
}

.top-bar-info {
  flex: 1;
  min-width: 0;
}

.top-bar-title {
  font-size: 14px;
  font-weight: 600;
  color: #fff;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.top-bar-subtitle {
  font-size: 11px;
  color: rgba(255,255,255,0.35);
  margin: 2px 0 0 0;
}

.top-bar-actions {
  flex-shrink: 0;
}

.top-bar-back {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  color: rgba(255,255,255,0.5);
  transition: all 0.2s;
}

.top-bar-back:hover {
  background: rgba(255,255,255,0.06);
  color: #fff;
}

/* ── Content Loading ──────────────────────────────────────── */
.content-loading {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  color: #6366f1;
}

.loading-spinner p {
  font-size: 13px;
  color: rgba(255,255,255,0.4);
}

/* ── Empty State ──────────────────────────────────────────── */
.empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: rgba(255,255,255,0.4);
}

.empty-icon {
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 24px;
  background: rgba(99, 102, 241, 0.06);
  border: 1px solid rgba(99, 102, 241, 0.1);
  margin-bottom: 8px;
}

.empty-icon svg {
  color: #6366f1;
}

.empty-state h3 {
  font-size: 18px;
  font-weight: 600;
  color: rgba(255,255,255,0.7);
  margin: 0;
}

.empty-state p {
  font-size: 14px;
  color: rgba(255,255,255,0.35);
  margin: 0;
}

/* ── Content Scroll ───────────────────────────────────────── */
.content-scroll {
  flex: 1;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,0.08) transparent;
}

.content-scroll::-webkit-scrollbar {
  width: 6px;
}

.content-scroll::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.08);
  border-radius: 100px;
}

/* ── Video ────────────────────────────────────────────────── */
.video-wrapper {
  background: #000;
  position: relative;
}

.video-player {
  width: 100%;
  max-height: 65vh;
  display: block;
}

.video-placeholder {
  background: linear-gradient(135deg, #0f0f1a, #1a1a2e);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  height: 300px;
}

.video-placeholder p {
  color: rgba(255,255,255,0.35);
  font-size: 14px;
}

/* ── Lesson Content Area ──────────────────────────────────── */
.lesson-content-area {
  max-width: 800px;
  margin: 0 auto;
  padding: 32px 32px 48px;
  width: 100%;
}

/* ── Lesson Header ────────────────────────────────────────── */
.lesson-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 24px;
}

.lesson-header-left {
  min-width: 0;
  flex: 1;
}

.lesson-main-title {
  font-size: 22px;
  font-weight: 700;
  color: #fff;
  line-height: 1.3;
  margin: 0 0 8px 0;
}

.lesson-header-meta {
  display: flex;
  align-items: center;
  gap: 12px;
}

.lesson-type-tag {
  font-size: 12px;
  font-weight: 500;
  padding: 4px 10px;
  border-radius: 6px;
}

.tag-video {
  background: rgba(99, 102, 241, 0.1);
  color: #818cf8;
}

.tag-document {
  background: rgba(251, 146, 60, 0.1);
  color: #fb923c;
}

.lesson-duration-tag {
  font-size: 12px;
  color: rgba(255,255,255,0.4);
}

/* ── Mark Complete Button ─────────────────────────────────── */
.btn-complete {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  font-size: 13px;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border: none;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.25);
}

.btn-complete:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.35);
}

.btn-complete:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-completed {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  font-size: 13px;
  font-weight: 600;
  color: #34d399;
  background: rgba(52, 211, 153, 0.08);
  border: 1px solid rgba(52, 211, 153, 0.2);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-completed:hover {
  background: rgba(52, 211, 153, 0.12);
  border-color: rgba(52, 211, 153, 0.3);
}

.btn-completed:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ── Lesson Prose ─────────────────────────────────────────── */
.lesson-prose {
  color: rgba(255,255,255,0.7);
  font-size: 15px;
  line-height: 1.8;
  margin-bottom: 24px;
}

/* ── Lock Overlay ─────────────────────────────────────────── */
.lock-overlay {
  text-align: center;
  padding: 48px 24px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.04), rgba(139, 92, 246, 0.04));
  border: 1px solid rgba(99, 102, 241, 0.1);
  border-radius: 16px;
  margin: 24px 0;
}

.lock-icon-wrap {
  width: 64px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  border-radius: 16px;
  background: rgba(99, 102, 241, 0.08);
  color: #6366f1;
}

.lock-overlay h3 {
  font-size: 18px;
  font-weight: 600;
  color: #fff;
  margin: 0 0 8px 0;
}

.lock-overlay p {
  font-size: 14px;
  color: rgba(255,255,255,0.45);
  margin: 0 0 24px 0;
}

.btn-buy-course {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 28px;
  font-size: 14px;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-radius: 10px;
  text-decoration: none;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.25);
}

.btn-buy-course:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.35);
}

/* ── Document Viewer ──────────────────────────────────────── */
.doc-viewer-main {
  padding: 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.doc-fullscreen {
  position: fixed !important;
  inset: 0;
  z-index: 100;
  background: #0a0a0f;
  padding: 0 !important;
}

.doc-fullscreen .doc-embed-wrapper {
  border-radius: 0;
  border: none;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.doc-fullscreen .doc-embed-frame {
  flex: 1;
}

.doc-fullscreen .doc-iframe {
  height: 100%;
  min-height: auto;
}

.doc-embed-wrapper {
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 14px;
  overflow: hidden;
  background: rgba(255,255,255,0.02);
}

/* ── Toolbar ──────────────────────────────────────────────── */
.doc-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  background: linear-gradient(180deg, rgba(20, 20, 35, 0.98), rgba(15, 15, 25, 0.95));
  border-bottom: 1px solid rgba(255,255,255,0.06);
  backdrop-filter: blur(12px);
  gap: 12px;
}

.doc-toolbar-left {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  flex: 1;
}

.doc-file-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.5px;
  color: #818cf8;
  background: rgba(99, 102, 241, 0.12);
  border: 1px solid rgba(99, 102, 241, 0.15);
  border-radius: 6px;
  flex-shrink: 0;
}

.doc-file-badge svg {
  color: #818cf8;
}

.doc-toolbar-filename {
  font-size: 13px;
  font-weight: 500;
  color: rgba(255,255,255,0.6);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.doc-toolbar-right {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.doc-tool-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: rgba(255,255,255,0.45);
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.doc-tool-btn:hover {
  background: rgba(255,255,255,0.08);
  color: #fff;
}

.doc-tool-btn:active {
  transform: scale(0.92);
}

/* ── Embed Frame ──────────────────────────────────────────── */
.doc-embed-frame {
  min-height: 200px;
  position: relative;
}

.doc-iframe {
  width: 100%;
  height: 70vh;
  min-height: 500px;
  border: none;
  background: #fff;
}

/* ── Image Viewer ─────────────────────────────────────────── */
.doc-image-viewer {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  background: linear-gradient(135deg, rgba(10,10,15,0.8), rgba(15,15,25,0.8));
  padding: 24px;
}

.doc-image {
  max-width: 100%;
  max-height: 75vh;
  object-fit: contain;
  display: block;
  border-radius: 8px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.4);
}

/* ── File Card Fallback ───────────────────────────────────── */
.doc-file-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 24px;
  text-align: center;
  background: linear-gradient(180deg, rgba(15,15,25,0.5), rgba(10,10,18,0.8));
}

.doc-file-icon-large {
  position: relative;
  width: 88px;
  height: 88px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 22px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.06));
  border: 1px solid rgba(99, 102, 241, 0.12);
  color: rgba(99, 102, 241, 0.4);
  margin-bottom: 20px;
}

.doc-ext-label {
  position: absolute;
  bottom: -6px;
  right: -6px;
  padding: 3px 8px;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.5px;
  color: #fff;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
}

.doc-file-name {
  font-size: 16px;
  font-weight: 600;
  color: rgba(255,255,255,0.85);
  margin: 0 0 8px 0;
  word-break: break-all;
  max-width: 400px;
}

.doc-file-hint {
  font-size: 13px;
  color: rgba(255,255,255,0.35);
  margin: 0 0 28px 0;
}

.doc-file-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.doc-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 20px;
  font-size: 13px;
  font-weight: 600;
  border-radius: 10px;
  text-decoration: none;
  transition: all 0.2s;
  cursor: pointer;
}

.doc-action-btn.primary {
  color: #fff;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
}

.doc-action-btn.primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
}

.doc-action-btn.secondary {
  color: rgba(255,255,255,0.6);
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
}

.doc-action-btn.secondary:hover {
  color: #fff;
  background: rgba(255,255,255,0.08);
}

/* ── Navigation ───────────────────────────────────────────── */
.lesson-nav {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-top: 40px;
  padding-top: 24px;
  border-top: 1px solid rgba(255,255,255,0.06);
}

.nav-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  max-width: 48%;
}

.nav-btn:hover {
  background: rgba(255,255,255,0.05);
  border-color: rgba(99, 102, 241, 0.2);
}

.nav-btn svg {
  color: rgba(255,255,255,0.4);
  flex-shrink: 0;
}

.nav-btn:hover svg {
  color: #6366f1;
}

.nav-btn-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.nav-label {
  font-size: 11px;
  font-weight: 500;
  color: rgba(255,255,255,0.35);
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.nav-title {
  font-size: 13px;
  font-weight: 500;
  color: rgba(255,255,255,0.7);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.nav-next {
  margin-left: auto;
}

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 1024px) {
  .learn-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    transform: translateX(-100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 40;
  }

  .sidebar-open {
    transform: translateX(0);
  }

  .sidebar-overlay {
    display: block;
  }

  .menu-toggle {
    display: flex;
  }

  .lesson-content-area {
    padding: 24px 16px 40px;
  }

  .lesson-header {
    flex-direction: column;
    gap: 16px;
  }

  .lesson-nav {
    flex-direction: column;
  }

  .nav-btn {
    max-width: 100%;
  }
}

@media (max-width: 640px) {
  .learn-sidebar {
    width: 300px;
  }

  .lesson-main-title {
    font-size: 18px;
  }

  .video-player {
    max-height: 50vh;
  }
}
</style>
