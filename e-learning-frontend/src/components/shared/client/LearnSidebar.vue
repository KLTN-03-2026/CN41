<template>
  <div class="sidebar-inner">
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
            @click="$emit('toggle-section', sIdx)"
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
              @click="$emit('select-lesson', lesson)"
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
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { formatSeconds } from '@/utils/formatDuration'

interface Lesson {
  id: number
  title: string
  type: 'video' | 'document'
  duration?: number
  progress?: { is_completed?: boolean }
}

interface Section {
  id: number | null
  title: string
  lessons: Lesson[]
}

const props = defineProps<{
  courseName: string
  slug: string
  lessons: Lesson[]
  sections: Section[]
  expandedSections: Record<number, boolean>
  currentLesson: Lesson | null
  listLoading: boolean
}>()

defineEmits<{
  (e: 'select-lesson', lesson: Lesson): void
  (e: 'toggle-section', idx: number): void
}>()

const completedCount = computed(() => props.lessons.filter(l => l.progress?.is_completed).length)
const progressPercent = computed(() =>
  props.lessons.length ? Math.round((completedCount.value / props.lessons.length) * 100) : 0
)

function getLessonGlobalIndex(lesson: Lesson) {
  const idx = props.lessons.findIndex(l => l.id === lesson.id)
  return idx >= 0 ? idx + 1 : ''
}
</script>

<style scoped>
/* NOTE: The CSS for sidebar components will be moved here in a final styling pass if necessary. 
   Right now, the CSS remains primarily in LearnPage.vue or global. 
   If needed, we can extract .sidebar-inner, etc. */
.sidebar-inner {
  display: flex;
  flex-direction: column;
  height: 100%;
}
</style>
