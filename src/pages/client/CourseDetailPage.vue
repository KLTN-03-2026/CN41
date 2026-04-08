<template>
  <div class="max-w-[1320px] mx-auto px-4 lg:px-8 py-8">
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
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] xl:grid-cols-[1fr_360px] gap-8 xl:gap-10">
        <!-- Main content -->
        <div class="space-y-6 min-w-0">
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

          <!-- Features Grid Component -->
          <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-900 mb-6">Giá trị bạn nhận được</h2>
            <CourseFeaturesGrid :features="courseFeatures" />
          </div>

          <!-- Danh sách bài giảng -->
          <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-900 mb-4">Nội dung khóa học</h2>
            <div v-if="lessonsLoading" class="space-y-2">
              <div v-for="i in 5" :key="i" class="h-12 bg-gray-50 rounded-lg animate-pulse"></div>
            </div>
            <template v-else>
              <div class="mb-4">
                <p class="text-xs text-gray-500">
                  <span class="font-medium text-gray-700">{{ lessonData.sections.length }}</span> chương • 
                  <span class="font-medium text-gray-700">{{ lessonData.sections.reduce((acc, sec) => acc + sec.lessons.length, 0) }}</span> bài giảng
                </p>
              </div>
              <div class="space-y-3">
                <details 
                  v-for="(section, index) in lessonData.sections" 
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
                      @click="handleLessonClick(lesson)"
                      class="flex items-center gap-3 p-2 rounded-lg transition-colors"
                      :class="(lesson.is_preview || lessonData.is_purchased) ? 'hover:bg-gray-50 cursor-pointer group/lesson' : 'opacity-[0.65] cursor-not-allowed'"
                    >
                      <div
                        :class="(lesson.is_preview || lessonData.is_purchased) ? 'bg-blue-50 text-blue-500' : 'bg-gray-100 text-gray-500'"
                        class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                      >
                        <!-- Lock Icon for non-preview && non-purchased -->
                        <svg v-if="!lesson.is_preview && !lessonData.is_purchased" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
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
                        <p class="text-sm font-medium text-gray-800 truncate" :class="(lesson.is_preview || lessonData.is_purchased) ? 'group-hover/lesson:text-blue-600 transition-colors' : ''">{{ lesson.title }}</p>
                      </div>
                      <span v-if="lesson.duration" class="text-[11px] text-gray-400 shrink-0 font-medium">
                        {{ formatSeconds(lesson.duration) }}
                      </span>
                      <span v-if="lesson.is_preview && !lessonData.is_purchased" class="text-[10px] bg-blue-100/50 border border-blue-200 text-blue-600 px-2.5 py-0.5 rounded-full shrink-0 font-medium hover:bg-blue-100 transition-colors">Xem thử</span>
                    </div>
                  </div>
                </details>
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
        <div class="space-y-6">
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
                <template v-if="Number(course.price) === 0">
                  <button
                    @click="handleEnrollFree"
                    :disabled="isEnrolling"
                    class="block w-full text-center py-3 rounded-xl bg-orange-500 text-white font-medium hover:bg-orange-600 transition-colors disabled:opacity-75 disabled:cursor-not-allowed"
                  >
                    <span v-if="isEnrolling">Đang xử lý...</span>
                    <span v-else>Đăng ký học miễn phí</span>
                  </button>
                </template>
                <template v-else>
                  <button
                    @click="addToCart"
                    class="block w-full text-center py-3 rounded-xl bg-blue-500 text-white font-medium hover:bg-blue-600 transition-colors"
                  >
                    Thêm vào giỏ hàng
                  </button>
                  <!-- Thêm nút học thử tại sidebar nếu có bài học thử -->
                  <template v-if="hasPreview && Number(course.price) !== 0">
                    <button
                      @click="openPreview(null)"
                      class="block w-full text-center py-3 mt-3 rounded-xl border border-orange-500 text-orange-500 font-medium hover:bg-orange-50 transition-colors"
                    >
                      Học thử miễn phí
                    </button>
                  </template>
                </template>
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
import { ref, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { coursesApi } from '@/api/coursesApi'
import { useCartStore } from '@/stores/cart'
import { useStudentAuthStore } from '@/stores/studentAuth'
import { formatCurrency } from '@/utils/formatCurrency'
import { formatSeconds } from '@/utils/formatDuration'
import CourseCard from '@/components/client/CourseCard.vue'
import CourseFeaturesGrid, { type FeatureItem } from '@/components/client/CourseFeaturesGrid.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const cartStore = useCartStore()
const studentAuthStore = useStudentAuthStore()

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
const lessonData    = ref<{ is_purchased: boolean; sections: any[] }>({
  is_purchased: false,
  sections: [],
})

const relatedCourses = ref<Course[]>([])
const relatedLoading = ref(false)

const hasPreview = computed(() => {
  return lessonData.value.sections.some(section => 
    section.lessons.some((lesson: any) => lesson.is_preview)
  )
})

const courseFeatures: FeatureItem[] = [
  {
    title: 'Học Tập Linh Hoạt',
    description: 'Chỉ cần có thiết bị kết nối Internet, bạn có thể học bất kỳ nơi nào, bất kỳ lúc nào bạn muốn.',
    icon: '<svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>'
  },
  {
    title: 'Học Thử Miễn Phí',
    description: 'Hệ thống cho phép học viên học thử một số lượng bài giảng trực quan miễn phí để trải nghiệm trước khi quyết định.',
    icon: '<svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>'
  },
  {
    title: 'Truy Cập Tài Nguyên',
    description: 'Không giới hạn băng thông truy cập. Bạn sẽ được sở hữu toàn bộ tài nguyên, code mẫu, slide đính kèm trong khóa học.',
    icon: '<svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm9 7h-6v13h-2v-6h-2v6H9V9H3V7h18v2z"/></svg>'
  },
  {
    title: 'Hỗ Trợ Khóa Học',
    description: 'Học viên sẽ được hỗ trợ trực tiếp ở mỗi bài giảng thông qua kênh Hỏi đáp, hoặc liên hệ qua các cộng đồng zalo/discord.',
    icon: '<svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72l5 2.73 5-2.73v3.72z"/></svg>'
  },
  {
    title: 'Nội Dung Khóa Học',
    description: 'Giáo trình luôn được biên soạn sâu sát với nghiệp vụ thực tế, chia sẻ từ kinh nghiệm đi làm mà bạn khó tìm ở nơi khác.',
    icon: '<svg fill="currentColor" viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>'
  },
  {
    title: 'Cập Nhật Thường Xuyên',
    description: 'Các bài học thường xuyên được tinh chỉnh và bổ sung để tương thích với các phiên bản ngôn ngữ/công nghệ mới nhất.',
    icon: '<svg fill="currentColor" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>'
  }
]

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
  if (!studentAuthStore.isLoggedIn) {
    router.push({ path: '/login', query: { redirect: '/cart' } })
    return
  }

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
  router.push('/cart')
}

const isEnrolling = ref(false)

async function handleEnrollFree() {
  if (!studentAuthStore.isLoggedIn) {
    router.push({ path: '/login', query: { redirect: route.fullPath } })
    return
  }
  
  if (!course.value) return
  
  isEnrolling.value = true
  try {
    await coursesApi.enrollFree(course.value.slug)
    toast.success('Đăng ký khóa học miễn phí thành công! Bắt đầu học nào.')
    // Đánh dấu là đã mua để UI tự cập nhật -> "Vào học ngay"
    lessonData.value.is_purchased = true
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Có lỗi xảy ra khi đăng ký.')
  } finally {
    isEnrolling.value = false
  }
}

// ── Xử lý Học Thử ──────────────────────────────────────────────
function handleLessonClick(lesson: any) {
  if (lessonData.value.is_purchased || lesson.is_preview) {
    router.push(`/courses/${course.value?.slug}/learn`)
  }
}

function openPreview(lesson: any = null) {
  router.push(`/courses/${course.value?.slug}/learn`)
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Ẩn dấu mũi tên mặc định của details/summary */
.custom-details summary::-webkit-details-marker {
  display: none;
}
.custom-details summary {
  list-style: none;
}
</style>
