<template>
  <div class="bg-white rounded-2xl border border-gray-100 p-5">
    <h2 class="font-semibold text-gray-900 mb-4">Nội dung khóa học</h2>
    <div v-if="loading" class="space-y-2">
      <div v-for="i in 5" :key="i" class="h-12 bg-gray-50 rounded-lg animate-pulse"></div>
    </div>
    <template v-else>
      <div class="mb-4">
        <p class="text-xs text-gray-500">
          <span class="font-medium text-gray-700">{{ sections.length }}</span> chương • 
          <span class="font-medium text-gray-700">{{ totalLessons }}</span> bài giảng
        </p>
      </div>
      <div class="space-y-3">
        <details 
          v-for="(section, index) in sections" 
          :key="section.id"
          class="border border-gray-100 rounded-xl overflow-hidden group custom-details"
          :open="index === 0"
        >
          <summary 
            class="w-full bg-gray-50 px-4 py-3 flex items-center justify-between hover:bg-gray-100 transition-colors cursor-pointer list-none select-none"
          >
            <span class="font-medium text-sm text-gray-800">{{ section.title }}</span>
            <div class="flex items-center gap-3">
              <span class="text-xs text-gray-500">{{ section.lessons.length }} bài giảng</span>
              <svg class="w-4 h-4 text-gray-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </summary>
          
          <div class="px-2 py-2 space-y-1 bg-white border-t border-gray-100 max-h-96 overflow-y-auto">
            <div
              v-for="lesson in section.lessons"
              :key="lesson.id"
              @click="$emit('lesson-click', lesson)"
              class="flex items-center gap-3 p-2 rounded-lg transition-colors"
              :class="(lesson.is_preview || isPurchased) ? 'hover:bg-gray-50 cursor-pointer group/lesson' : 'opacity-[0.65] cursor-not-allowed'"
            >
              <div
                :class="(lesson.is_preview || isPurchased) ? 'bg-blue-50 text-blue-500' : 'bg-gray-100 text-gray-500'"
                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
              >
                <!-- Lock Icon for non-preview && non-purchased -->
                <svg v-if="!lesson.is_preview && !isPurchased" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                </svg>
                <!-- Play Icon for video -->
                <svg v-else-if="lesson.type === 'video'" class="w-4 h-4 ml-0.5 group-hover/lesson:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                </svg>
                <!-- Document Icon for doc -->
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate" :class="(lesson.is_preview || isPurchased) ? 'group-hover/lesson:text-blue-600 transition-colors' : ''">{{ lesson.title }}</p>
              </div>
              <span v-if="lesson.duration" class="text-[11px] text-gray-400 shrink-0 font-medium">
                {{ formatSeconds(lesson.duration) }}
              </span>
              <span v-if="lesson.is_preview && !isPurchased" class="text-[10px] bg-blue-100/50 border border-blue-200 text-blue-600 px-2.5 py-0.5 rounded-full shrink-0 font-medium hover:bg-blue-100 transition-colors">Xem thử</span>
            </div>
          </div>
        </details>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { formatSeconds } from '@/utils/formatDuration'

interface Lesson {
  id: number
  title: string
  type: string
  duration?: number | null
  is_preview: boolean
}

interface Section {
  id: number
  title: string
  lessons: Lesson[]
}

const props = defineProps<{
  sections: Section[]
  isPurchased: boolean
  loading: boolean
}>()

defineEmits<{
  (e: 'lesson-click', lesson: Lesson): void
}>()

const totalLessons = computed(() =>
  props.sections.reduce((acc, sec) => acc + sec.lessons.length, 0)
)
</script>

<style scoped>
/* Ẩn dấu mũi tên mặc định của details/summary */
.custom-details summary::-webkit-details-marker {
  display: none;
}
.custom-details summary {
  list-style: none;
}
</style>
