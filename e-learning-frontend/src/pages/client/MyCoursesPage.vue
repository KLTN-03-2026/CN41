<template>
  <div class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Khóa học của tôi</h1>
      <p class="text-gray-500 mt-1">Các khóa học bạn đã mua</p>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="i in 6" :key="i" class="bg-white rounded-2xl overflow-hidden border border-gray-100 animate-pulse">
        <div class="h-40 bg-gray-100"></div>
        <div class="p-4 space-y-2">
          <div class="h-4 bg-gray-100 rounded w-3/4"></div>
          <div class="h-3 bg-gray-100 rounded w-1/2"></div>
          <div class="h-2 bg-gray-100 rounded w-full mt-3"></div>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="!courses.length" class="text-center py-16">
      <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
      </svg>
      <p class="text-gray-500 mb-4">Bạn chưa có khóa học nào</p>
      <router-link to="/courses" class="px-5 py-2.5 bg-blue-500 text-white text-sm rounded-xl hover:bg-blue-600 transition-colors">
        Khám phá khóa học
      </router-link>
    </div>

    <!-- Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="course in courses"
        :key="course.id"
        class="bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-md transition-shadow"
      >
        <div class="relative h-40 overflow-hidden">
          <img
            v-if="course.thumbnail"
            :src="course.thumbnail"
            :alt="course.name"
            class="w-full h-full object-cover"
          />
          <div v-else class="w-full h-full bg-gradient-to-br from-blue-100 to-blue-200"></div>
        </div>

        <div class="p-4">
          <h3 class="font-semibold text-gray-900 text-sm leading-snug line-clamp-2 mb-2">{{ course.name }}</h3>

          <!-- Progress bar -->
          <div class="mb-3">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
              <span>Tiến độ</span>
              <span>{{ course.progress_percent ?? 0 }}%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5">
              <div
                class="bg-blue-500 h-1.5 rounded-full transition-all"
                :style="{ width: (course.progress_percent ?? 0) + '%' }"
              ></div>
            </div>
          </div>

          <router-link
            :to="`/courses/${course.slug}/learn`"
            class="block w-full text-center py-2 text-sm rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors font-medium"
          >
            {{ (course.progress_percent ?? 0) > 0 ? 'Tiếp tục học' : 'Bắt đầu học' }}
          </router-link>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2 mt-8">
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
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { courseService } from '@/services/course.service'

const toast = useToast()

interface MyCourse {
  id: number
  name: string
  slug: string
  thumbnail?: string | null
  progress_percent?: number
}

const courses    = ref<MyCourse[]>([])
const pagination = ref<any>(null)
const loading    = ref(true)

async function fetchPage(page = 1) {
  loading.value = true
  try {
    const res = await courseService.myCourses({ page, per_page: 12 })
    courses.value = res.data.data
    pagination.value = res.data.pagination
  } catch {
    toast.error('Không thể tải khóa học')
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchPage())
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
