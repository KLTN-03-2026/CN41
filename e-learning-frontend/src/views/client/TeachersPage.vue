<template>
  <div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Giảng viên</h1>
      <p class="text-gray-500 mt-1">Khám phá các giảng viên chuyên môn hàng đầu</p>
    </div>

    <!-- Search -->
    <div class="mb-6">
      <input
        v-model="search"
        type="text"
        placeholder="Tìm kiếm giảng viên..."
        class="input-field w-72"
        @input="debouncedSearch"
      />
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div
        v-for="i in 8"
        :key="i"
        class="bg-white rounded-2xl border border-gray-100 p-6 flex flex-col items-center gap-3 animate-pulse"
      >
        <div class="w-20 h-20 rounded-full bg-gray-200" />
        <div class="h-4 w-28 bg-gray-200 rounded" />
        <div class="h-3 w-20 bg-gray-100 rounded" />
        <div class="h-3 w-32 bg-gray-100 rounded" />
        <div class="h-8 w-24 bg-gray-100 rounded-lg mt-2" />
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="!teachers.length" class="text-center py-16 text-gray-400">
      <svg
        class="w-12 h-12 mx-auto mb-3 text-gray-300"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
        />
      </svg>
      <p class="text-lg">Không tìm thấy giảng viên nào</p>
    </div>

    <!-- Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div
        v-for="(teacher, index) in teachers"
        :key="teacher.id"
        class="bg-white rounded-2xl border border-gray-100 p-6 flex flex-col items-center text-center gap-3 hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5"
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

        <div>
          <h3 class="font-semibold text-gray-900 text-base">{{ teacher.name }}</h3>
          <p class="text-xs text-gray-400 mt-0.5">{{ teacher.courses_count ?? 0 }} khóa học</p>
        </div>

        <p v-if="teacher.description" class="text-sm text-gray-500 line-clamp-2">
          {{ teacher.description }}
        </p>

        <div v-if="teacher.exp" class="flex items-center gap-1 text-xs text-gray-400">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          {{ teacher.exp }} năm kinh nghiệm
        </div>

        <router-link
          :to="`/teachers/${teacher.slug}`"
          class="mt-auto w-full text-center text-sm font-medium text-blue-600 border border-blue-200 rounded-lg py-1.5 hover:bg-blue-50 transition-colors"
        >
          Xem hồ sơ
        </router-link>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2 mt-8">
      <button
        v-for="p in pagination.last_page"
        :key="p"
        :class="
          p === pagination.current_page
            ? 'bg-blue-500 text-white border-blue-500'
            : 'bg-white text-gray-600 hover:bg-gray-50'
        "
        class="w-9 h-9 rounded-lg text-sm border border-gray-200 transition-colors"
        @click="fetchPage(p)"
      >
        {{ p }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { teacherService } from '@/services/teacher.service'
import type { Teacher } from '@/types/course.types'

const teachers = ref<Teacher[]>([])
const loading = ref(true)
const search = ref('')
const pagination = ref<{ current_page: number; last_page: number } | null>(null)

const AVATAR_GRADIENTS = [
  'from-blue-400 to-blue-600',
  'from-green-400 to-green-600',
  'from-purple-400 to-purple-600',
  'from-orange-400 to-orange-600',
  'from-pink-400 to-pink-600',
  'from-teal-400 to-teal-600',
]

function avatarInitial(name: string): string {
  return name
    .split(' ')
    .map((w) => w[0])
    .slice(-2)
    .join('')
    .toUpperCase()
}

async function loadPage(page = 1) {
  loading.value = true
  try {
    const params: Record<string, unknown> = { page, per_page: 12 }
    if (search.value) params.search = search.value

    const res = await teacherService.publicList(params)
    teachers.value = res.data.data
    pagination.value = res.data.pagination ?? null
  } finally {
    loading.value = false
  }
}

function fetchPage(page: number) {
  loadPage(page)
}

let debounceTimer: ReturnType<typeof setTimeout> | null = null
function debouncedSearch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => loadPage(1), 400)
}

onMounted(() => loadPage())
</script>

<style scoped>
.input-field {
  @apply h-10 px-3 rounded-lg border border-gray-200 bg-white text-sm text-gray-700
         focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400;
}
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
