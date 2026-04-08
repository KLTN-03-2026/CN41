<template>
  <div class="max-w-5xl mx-auto px-4 py-8">
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-20">
      <svg class="animate-spin w-8 h-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
    </div>

    <!-- Not found -->
    <div v-else-if="!course" class="text-center py-20">
      <p class="text-gray-500">Khóa học không tồn tại</p>
      <router-link to="/courses" class="mt-4 inline-block text-blue-500 hover:underline">Xem tất cả khóa học</router-link>
    </div>

    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Breadcrumb -->
          <nav class="text-sm text-gray-500 flex items-center gap-1">
            <router-link to="/courses" class="hover:text-blue-500">Khóa học</router-link>
            <span>/</span>
            <span class="text-gray-800 truncate">{{ course.name }}</span>
          </nav>

          <!-- Title & meta -->
          <div>
            <h1 class="text-2xl font-bold text-gray-900 leading-tight">{{ course.name }}</h1>
            <div class="flex flex-wrap items-center gap-3 mt-3 text-sm text-gray-500">
              <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                {{ course.teacher?.name }}
              </span>
              <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ course.total_students }} học viên
              </span>
              <span
                :class="levelClass(course.level)"
                class="px-2 py-0.5 rounded-full text-xs font-medium"
              >
                {{ levelLabel(course.level) }}
              </span>
            </div>
          </div>

          <!-- Thumbnail mobile -->
          <div class="lg:hidden">
            <img
              v-if="course.thumbnail"
              :src="course.thumbnail"
              :alt="course.name"
              class="w-full rounded-2xl object-cover aspect-video"
            />
          </div>

          <!-- Mô tả -->
          <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-900 mb-3">Mô tả khóa học</h2>
            <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line">
              {{ course.description || 'Chưa có mô tả.' }}
            </p>
          </div>

          <!-- Danh sách bài giảng -->
          <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-900 mb-4">Nội dung khóa học</h2>
            <div v-if="lessonsLoading" class="space-y-2">
              <div v-for="i in 5" :key="i" class="h-12 bg-gray-50 rounded-lg animate-pulse"></div>
            </div>
            <template v-else>
              <p class="text-xs text-gray-400 mb-3">{{ lessonData.lessons.length }} bài giảng</p>
              <div class="space-y-2">
                <div
                  v-for="lesson in lessonData.lessons"
                  :key="lesson.id"
                  class="flex items-center gap-3 p-3 rounded-xl"
                  :class="lesson.is_preview ? 'hover:bg-gray-50 cursor-pointer' : 'opacity-60'"
                >
                  <div
                    :class="lesson.is_preview ? 'bg-blue-50 text-blue-500' : 'bg-gray-100 text-gray-400'"
                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                  >
                    <svg v-if="lesson.type === 'video'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ lesson.title }}</p>
                    <p v-if="!lesson.is_preview && !lessonData.is_purchased" class="text-xs text-gray-400">Cần mua khóa học</p>
                  </div>
                  <span v-if="lesson.duration" class="text-xs text-gray-400 shrink-0">
                    {{ formatSeconds(lesson.duration) }}
                  </span>
                  <span v-if="lesson.is_preview" class="text-xs text-blue-500 shrink-0">Xem thử</span>
                </div>
              </div>
            </template>
          </div>

          <!-- Các khóa học liên quan -->
          <div v-if="relatedCourses.length" class="mt-8">
            <h2 class="font-semibold text-gray-900 mb-4">Các khóa học cùng danh mục</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <CourseCard
                v-for="relCourse in relatedCourses"
                :key="relCourse.id"
                :course="relCourse"
                image-class="h-36"
              />
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="sticky top-6 bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
            <img
              v-if="course.thumbnail"
              :src="course.thumbnail"
              :alt="course.name"
              class="w-full object-cover aspect-video hidden lg:block"
            />
            <div class="p-5">
              <!-- Giá -->
              <div class="mb-4">
                <span v-if="course.sale_price" class="text-2xl font-bold text-blue-600">
                  {{ formatCurrency(Number(course.sale_price)) }}
                </span>
                <span
                  class="text-xl font-bold"
                  :class="course.sale_price ? 'text-gray-400 line-through text-base ml-2' : 'text-blue-600'"
                >
                  {{ formatCurrency(Number(course.price)) }}
                </span>
              </div>

              <!-- CTA -->
              <template v-if="lessonData.is_purchased">
                <router-link
                  :to="`/courses/${course.slug}/learn`"
                  class="block w-full text-center py-3 rounded-xl bg-green-500 text-white font-medium hover:bg-green-600 transition-colors"
                >
                  Vào học ngay
                </router-link>
              </template>
              <template v-else>
                <router-link
                  to="/cart"
                  class="block w-full text-center py-3 rounded-xl bg-blue-500 text-white font-medium hover:bg-blue-600 transition-colors"
                  @click.prevent="addToCart"
                >
                  Thêm vào giỏ hàng
                </router-link>
              </template>

            <!-- Danh mục -->
              <div v-if="course.categories?.length" class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-500 mb-2">Danh mục</p>
                <div class="flex flex-wrap gap-2">
                  <div
                    v-for="cat in course.categories"
                    :key="cat.id"
                    class="flex flex-wrap items-center gap-1 text-xs"
                  >
                    <template v-if="cat.ancestors && cat.ancestors.length">
                      <template v-for="ancestor in cat.ancestors" :key="ancestor.id">
                        <span class="bg-gray-50 text-gray-500 px-2 py-0.5 rounded-full border border-gray-100">{{ ancestor.name }}</span>
                        <span class="text-gray-300">›</span>
                      </template>
                    </template>
                    <span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full font-medium border border-blue-100">
                      {{ cat.name }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { coursesApi } from '@/api/coursesApi'
import { useCartStore } from '@/stores/cart'
import { formatCurrency } from '@/utils/formatCurrency'
import { formatSeconds } from '@/utils/formatDuration'
import CourseCard from '@/components/client/CourseCard.vue'

const route = useRoute()
const toast = useToast()
const cartStore = useCartStore()

interface Course {
  id: number
  name: string
  slug: string
  description?: string | null
  thumbnail?: string | null
  price: string
  sale_price?: string | null
  level: string
  total_students: number
  teacher?: { id: number; name: string } | null
  categories?: { id: number; name: string; ancestors?: { id: number; name: string }[] }[]
}

const course        = ref<Course | null>(null)
const loading       = ref(true)
const lessonsLoading = ref(true)
const lessonData    = ref<{ is_purchased: boolean; lessons: any[] }>({
  is_purchased: false,
  lessons: [],
})

const relatedCourses = ref<Course[]>([])
const relatedLoading = ref(false)

watch(() => route.params.slug, async (newSlug) => {
  if (!newSlug) return
  
  loading.value = true
  lessonsLoading.value = true
  window.scrollTo({ top: 0, behavior: 'smooth' })
  
  const slug = newSlug as string
  try {
    const [courseRes, lessonsRes] = await Promise.allSettled([
      coursesApi.publicShow(slug),
      coursesApi.publicLessons(slug),
    ])

    if (courseRes.status === 'fulfilled') {
      course.value = courseRes.value.data.data
      
      // Fetch related courses using the root category if possible
      if (course.value?.categories?.length) {
        relatedLoading.value = true
        try {
          // Lấy category con tiên quyết
          const cat = course.value.categories[0]
          // Sử dụng ancestor cao nhất (root) nếu có, không thì dùng chính nó
          const rootCatId = (cat.ancestors && cat.ancestors.length) ? cat.ancestors[0].id : cat.id
          
          const relatedRes = await coursesApi.publicIndex({ category_id: rootCatId, per_page: 5 })
          const filtered = relatedRes.data.data.filter((c: Course) => c.id !== course.value?.id)
          relatedCourses.value = filtered.slice(0, 4)
        } catch {
          // ignore
        } finally {
          relatedLoading.value = false
        }
      }
    }
    if (lessonsRes.status === 'fulfilled') {
      lessonData.value = lessonsRes.value.data.data
    }
  } catch {
    toast.error('Không thể tải thông tin khóa học')
  } finally {
    loading.value = false
    lessonsLoading.value = false
  }
}, { immediate: true })

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

function addToCart() {
  if (!course.value) return
  cartStore.addItem({
    id: course.value.id,
    name: course.value.name,
    slug: course.value.slug,
    thumbnail: course.value.thumbnail ?? null,
    price: Number(course.value.price),
    sale_price: course.value.sale_price ? Number(course.value.sale_price) : null,
  })
  toast.success('Đã thêm vào giỏ hàng')
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
