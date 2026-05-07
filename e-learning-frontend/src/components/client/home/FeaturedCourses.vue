<template>
  <section class="py-14 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-end justify-between mb-8">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Khóa học nổi bật</h2>
          <p class="text-gray-500 mt-1 text-sm">Được đánh giá cao nhất bởi học viên</p>
        </div>
        <router-link
          to="/courses"
          class="text-primary-600 hover:text-primary-700 text-sm font-medium hidden sm:block"
        >
          Xem tất cả →
        </router-link>
      </div>

      <!-- Skeleton -->
      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="i in 8"
          :key="i"
          class="bg-white rounded-2xl overflow-hidden border border-gray-100 animate-pulse"
        >
          <div class="h-44 bg-gray-100"></div>
          <div class="p-4 space-y-2">
            <div class="h-4 bg-gray-100 rounded w-3/4"></div>
            <div class="h-3 bg-gray-100 rounded w-1/2"></div>
            <div class="flex items-center gap-1 mt-2">
              <div class="h-3 bg-gray-100 rounded w-20"></div>
            </div>
            <div class="h-5 bg-gray-100 rounded w-1/3 mt-2"></div>
          </div>
        </div>
      </div>

      <!-- Grid -->
      <div v-else-if="courses.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <router-link
          v-for="course in courses"
          :key="course.id"
          :to="`/courses/${course.slug}`"
          class="bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-lg transition-shadow group flex flex-col"
        >
          <!-- Thumbnail -->
          <div class="relative h-44 shrink-0 overflow-hidden">
            <img
              v-if="course.thumbnail"
              :src="course.thumbnail"
              :alt="course.name"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            <div
              v-else
              class="w-full h-full bg-gradient-to-br from-primary-100 to-blue-200 flex items-center justify-center"
            >
              <svg
                class="w-10 h-10 text-primary-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                />
              </svg>
            </div>
            <span
              :class="levelClass(course.level)"
              class="absolute top-2 left-2 text-xs font-medium px-2 py-0.5 rounded-full"
            >
              {{ levelLabel(course.level) }}
            </span>
          </div>

          <!-- Body -->
          <div class="p-4 flex flex-col flex-1">
            <h3 class="font-semibold text-gray-900 text-sm leading-snug line-clamp-2 mb-1">
              {{ course.name }}
            </h3>
            <p class="text-xs text-gray-500 mb-2">{{ course.teacher?.name || 'Giảng viên' }}</p>

            <!-- Rating -->
            <div class="flex items-center gap-1 mb-3">
              <div class="flex">
                <svg
                  v-for="star in 5"
                  :key="star"
                  class="w-3.5 h-3.5"
                  :class="
                    star <= Math.round(course.rating ?? 0) ? 'text-yellow-400' : 'text-gray-200'
                  "
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                  />
                </svg>
              </div>
              <span class="text-xs text-gray-500">{{ course.rating?.toFixed(1) ?? '0.0' }}</span>
              <span class="text-xs text-gray-400">({{ course.total_students ?? 0 }} học viên)</span>
            </div>

            <!-- Price -->
            <div class="mt-auto">
              <span
                v-if="course.sale_price && Number(course.sale_price) > 0"
                class="font-bold text-primary-600 text-sm"
              >
                {{ formatCurrency(Number(course.sale_price)) }}
              </span>
              <span
                class="font-bold text-sm ml-1"
                :class="
                  course.sale_price && Number(course.sale_price) > 0
                    ? 'line-through text-gray-400 text-xs'
                    : 'text-primary-600'
                "
              >
                {{ Number(course.price) === 0 ? 'Miễn phí' : formatCurrency(Number(course.price)) }}
              </span>
            </div>
          </div>
        </router-link>
      </div>

      <div v-else class="text-center py-12 text-gray-400 text-sm">Chưa có khóa học nổi bật.</div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { formatCurrency } from '@/utils/formatCurrency'
import type { Course } from '@/types/course.types'

defineProps<{
  courses: Course[]
  loading: boolean
}>()

function levelLabel(level: string) {
  return { beginner: 'Cơ bản', intermediate: 'Trung cấp', advanced: 'Nâng cao' }[level] || level
}

function levelClass(level: string) {
  return (
    (
      {
        beginner: 'bg-green-100 text-green-700',
        intermediate: 'bg-yellow-100 text-yellow-700',
        advanced: 'bg-red-100 text-red-700',
      } as Record<string, string>
    )[level] ?? 'bg-gray-100 text-gray-600'
  )
}
</script>
