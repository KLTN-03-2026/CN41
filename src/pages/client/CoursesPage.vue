<template>
  <div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Khóa học</h1>
      <p class="text-gray-500 mt-1">Khám phá các khóa học chất lượng cao</p>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-6">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Tìm kiếm khóa học..."
        class="input-field w-72"
        @input="debouncedFetch"
      />
      <select v-model="filters.level" class="input-field w-40" @change="fetchPage(1)">
        <option value="">Tất cả trình độ</option>
        <option value="beginner">Cơ bản</option>
        <option value="intermediate">Trung cấp</option>
        <option value="advanced">Nâng cao</option>
      </select>
      <select v-model="filters.category_id" class="input-field w-48" @change="fetchPage(1)">
        <option value="">Tất cả danh mục</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
      </select>
    </div>

    <!-- Grid -->
    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div v-for="i in 8" :key="i" class="bg-white rounded-2xl overflow-hidden border border-gray-100 animate-pulse">
        <div class="h-44 bg-gray-100"></div>
        <div class="p-4 space-y-2">
          <div class="h-4 bg-gray-100 rounded w-3/4"></div>
          <div class="h-3 bg-gray-100 rounded w-1/2"></div>
          <div class="h-5 bg-gray-100 rounded w-1/3 mt-3"></div>
        </div>
      </div>
    </div>

    <div v-else-if="!courses.length" class="text-center py-16 text-gray-400">
      <p class="text-lg">Không tìm thấy khóa học nào</p>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <router-link
        v-for="course in courses"
        :key="course.id"
        :to="`/courses/${course.slug}`"
        class="bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-md transition-shadow group"
      >
        <div class="relative h-44 overflow-hidden">
          <img
            v-if="course.thumbnail"
            :src="course.thumbnail"
            :alt="course.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <div v-else class="w-full h-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
            <svg class="w-12 h-12 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
          </div>
          <span
            :class="levelClass(course.level)"
            class="absolute top-2 left-2 text-xs font-medium px-2 py-0.5 rounded-full"
          >
            {{ levelLabel(course.level) }}
          </span>
        </div>

        <div class="p-4">
          <h3 class="font-semibold text-gray-900 text-sm leading-snug line-clamp-2 mb-1">{{ course.name }}</h3>
          <p class="text-xs text-gray-500 mb-3">{{ course.teacher?.name }}</p>
          <div class="flex items-center justify-between">
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
            <span class="text-xs text-gray-400">{{ course.total_students }} học viên</span>
          </div>
        </div>
      </router-link>
    </div>

    <!-- Pagination -->
    <div
      v-if="pagination && pagination.last_page > 1"
      class="flex justify-center gap-2 mt-8"
    >
      <button
        v-for="p in pagination.last_page"
        :key="p"
        @click="fetchPage(p)"
        :class="p === pagination.current_page
          ? 'bg-blue-500 text-white border-blue-500'
          : 'bg-white text-gray-600 hover:bg-gray-50'"
        class="w-9 h-9 rounded-lg text-sm border border-gray-200 transition-colors"
      >
        {{ p }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { coursesApi } from '@/api/coursesApi'
import { categoriesApi } from '@/api/categoriesApi'
import { formatCurrency } from '@/utils/formatCurrency'

interface Course {
  id: number
  name: string
  slug: string
  thumbnail?: string | null
  price: string
  sale_price?: string | null
  level: string
  total_students: number
  teacher?: { id: number; name: string } | null
}

const courses    = ref<Course[]>([])
const categories = ref<{ id: number; name: string }[]>([])
const pagination = ref<any>(null)
const loading    = ref(true)

const filters = reactive({ search: '', level: '', category_id: '' })

let debounceTimer: ReturnType<typeof setTimeout> | null = null
function debouncedFetch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchPage(1), 400)
}

async function fetchPage(page = 1) {
  loading.value = true
  try {
    const params: Record<string, any> = { page, per_page: 12 }
    if (filters.search)      params.search = filters.search
    if (filters.level)       params.level = filters.level
    if (filters.category_id) params.category_id = filters.category_id

    const res = await coursesApi.publicIndex(params)
    courses.value = res.data.data
    pagination.value = res.data.pagination
  } finally {
    loading.value = false
  }
}

async function fetchCategories() {
  try {
    const res = await categoriesApi.publicList()
    categories.value = res.data.data
  } catch {}
}

onMounted(() => {
  fetchPage()
  fetchCategories()
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
