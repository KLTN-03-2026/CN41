<template>
  <router-link
    :to="`/courses/${course.slug}`"
    class="bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-md transition-shadow group flex flex-col h-full"
  >
    <div class="relative overflow-hidden shrink-0" :class="imageClass">
      <img
        v-if="course.thumbnail"
        :src="course.thumbnail"
        :alt="course.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
      <div v-else class="w-full h-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
        <svg class="w-10 h-10 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
      </div>
      <span
        :class="levelClass(course.level)"
        class="absolute top-2 left-2 font-medium px-2 py-0.5 rounded-full text-xs"
      >
        {{ levelLabel(course.level) }}
      </span>
    </div>

    <div class="p-4 flex flex-col flex-1">
      <h3 class="font-semibold text-gray-900 leading-snug line-clamp-2 mb-1" :class="titleClass">{{ course.name }}</h3>
      <p class="text-xs text-gray-500 mb-3">{{ course.teacher?.name || 'Giảng viên' }}</p>
      <div class="mt-auto flex items-center justify-between">
        <div>
          <span v-if="course.sale_price" class="font-bold text-blue-600 text-sm">
            {{ formatCurrency(Number(course.sale_price)) }}
          </span>
          <span
            class="font-bold text-sm"
            :class="course.sale_price ? 'line-through text-gray-400 text-xs ml-1' : 'text-blue-600'"
          >
            {{ formatCurrency(Number(course.price)) }}
          </span>
        </div>
        <span v-if="showStudents" class="text-xs text-gray-400">{{ course.total_students || 0 }} học viên</span>
      </div>
    </div>
  </router-link>
</template>

<script setup lang="ts">
import { formatCurrency } from '@/utils/formatCurrency'

interface Course {
  id: number
  name: string
  slug: string
  thumbnail?: string | null
  price: string | number
  sale_price?: string | number | null
  level: string
  total_students?: number
  teacher?: { id: number; name: string } | null
}

withDefaults(defineProps<{
  course: Course;
  imageClass?: string;
  titleClass?: string;
  showStudents?: boolean;
}>(), {
  imageClass: 'h-44',
  titleClass: 'text-sm',
  showStudents: true
})

function levelLabel(level: string) {
  return { beginner: 'Cơ bản', intermediate: 'Trung cấp', advanced: 'Nâng cao' }[level] || level
}
function levelClass(level: string) {
  return {
    beginner:     'bg-green-100 text-green-700',
    intermediate: 'bg-yellow-100 text-yellow-700',
    advanced:     'bg-red-100 text-red-700',
  }[level] || 'bg-gray-100 text-gray-600'
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
