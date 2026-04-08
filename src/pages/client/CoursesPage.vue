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
      <select v-model="filters.category_id" class="input-field w-48 border-gray-300 focus:ring-blue-500" @change="fetchPage(1)">
        <option value="">Tất cả danh mục</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
          {{ '— '.repeat(cat.depth || 0) }}{{ cat.name }}
        </option>
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
      <CourseCard
        v-for="course in courses"
        :key="course.id"
        :course="course"
      />
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
import CourseCard from '@/components/client/CourseCard.vue'

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
const categories = ref<{ id: number; name: string; depth?: number }[]>([])
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
