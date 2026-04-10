<template>
  <div>
    <div v-if="!lessons.length" class="text-center py-6 text-gray-400 text-xs">
      Chưa có bài giảng trong chương này
    </div>
    <table v-else class="w-full text-sm">
      <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
        <LessonItem
          v-for="(lesson, lIdx) in lessons"
          :key="lesson.id"
          :lesson="lesson"
          :index="lIdx"
          :is-selected="selectedLessons.includes(lesson.id)"
          :is-toggling="togglingLesson === lesson.id"
          :is-orphan="isOrphan"
          @toggle-select="$emit('toggle-select', $event)"
          @toggle-status="$emit('toggle-status', $event)"
          @preview="$emit('preview', $event)"
          @edit="$emit('edit', $event)"
          @delete="$emit('delete', $event)"
          @dragstart="$emit('dragstart', lIdx)"
          @drop="$emit('drop', lIdx)"
        />
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import LessonItem from '@/components/shared/admin/LessonItem.vue'
import type { AdminLesson } from '@/types/section-lesson.types'

defineProps<{
  lessons: AdminLesson[]
  selectedLessons: number[]
  togglingLesson: number | null
  isOrphan?: boolean
}>()

defineEmits<{
  'toggle-select': [id: number]
  'toggle-status': [lesson: AdminLesson]
  'preview': [id: number]
  'edit': [lesson: AdminLesson]
  'delete': [lesson: AdminLesson]
  'dragstart': [idx: number]
  'drop': [idx: number]
}>()
</script>
