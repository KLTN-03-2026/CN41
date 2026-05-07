<template>
  <section class="bg-white py-16">
    <div class="max-w-6xl mx-auto px-4">
      <div class="flex items-center justify-between mb-10">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Giảng viên nổi bật</h2>
          <p class="mt-2 text-gray-500">Học cùng những chuyên gia hàng đầu.</p>
        </div>
        <router-link
          to="/teachers"
          class="hidden sm:flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors"
        >
          Xem tất cả
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </router-link>
      </div>

      <!-- Skeleton -->
      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="i in 4"
          :key="i"
          class="rounded-xl border border-gray-100 p-6 flex flex-col items-center gap-3 animate-pulse"
        >
          <div class="w-20 h-20 rounded-full bg-gray-200" />
          <div class="h-4 w-28 bg-gray-200 rounded" />
          <div class="h-3 w-20 bg-gray-100 rounded" />
          <div class="h-3 w-32 bg-gray-100 rounded" />
          <div class="h-3 w-24 bg-gray-100 rounded" />
        </div>
      </div>

      <!-- Teachers grid -->
      <div v-else-if="teachers.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="(teacher, index) in teachers"
          :key="teacher.id"
          class="group rounded-xl border border-gray-100 p-6 flex flex-col items-center text-center gap-3 hover:shadow-lg transition-all duration-200 hover:scale-[1.02]"
        >
          <!-- Avatar -->
          <div class="w-20 h-20 rounded-full overflow-hidden flex-shrink-0">
            <img
              v-if="teacher.image"
              :src="teacher.image"
              :alt="teacher.name"
              class="w-full h-full object-cover"
            />
            <div
              v-else
              :class="[
                'w-full h-full bg-gradient-to-br flex items-center justify-center text-white font-bold text-xl',
                AVATAR_GRADIENTS[index % AVATAR_GRADIENTS.length],
              ]"
            >
              {{ avatarInitial(teacher.name) }}
            </div>
          </div>

          <div class="font-semibold text-gray-900">{{ teacher.name }}</div>
          <div class="text-xs text-gray-400">{{ teacher.courses_count ?? 0 }} khóa học</div>
          <p v-if="teacher.description" class="text-sm text-gray-500 line-clamp-2">
            {{ teacher.description }}
          </p>

          <router-link
            :to="`/teachers/${teacher.slug}`"
            class="mt-auto text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1"
          >
            Xem hồ sơ
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </router-link>
        </div>
      </div>

      <!-- Empty -->
      <div v-else class="text-center text-gray-400 py-10">Chưa có giảng viên nào.</div>

      <!-- Mobile: xem tất cả -->
      <div class="sm:hidden mt-8 text-center">
        <router-link
          to="/teachers"
          class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 border border-blue-200 rounded-lg px-4 py-2 hover:bg-blue-50 transition-colors"
        >
          Xem tất cả giảng viên
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </router-link>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { Teacher } from '@/types/course.types'

defineProps<{
  teachers: Teacher[]
  loading: boolean
}>()

const AVATAR_GRADIENTS = [
  'from-blue-400 to-blue-600',
  'from-green-400 to-green-600',
  'from-purple-400 to-purple-600',
  'from-orange-400 to-orange-600',
]

function avatarInitial(name: string): string {
  return name
    .split(' ')
    .map((w) => w[0])
    .slice(-2)
    .join('')
    .toUpperCase()
}
</script>
