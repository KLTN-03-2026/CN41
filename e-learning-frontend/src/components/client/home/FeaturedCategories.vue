<template>
  <section class="py-14 bg-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
      <h2 class="text-2xl font-bold text-gray-900">Danh mục nổi bật</h2>
      <p class="text-gray-500 mt-1 text-sm">Khám phá theo lĩnh vực bạn quan tâm</p>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="space-y-4 px-4">
      <div class="flex gap-4">
        <div
          v-for="i in 6"
          :key="i"
          class="flex-shrink-0 animate-pulse flex flex-col items-center gap-3 w-28"
        >
          <div class="w-16 h-16 bg-gray-100 rounded-2xl"></div>
          <div class="h-3 bg-gray-100 rounded w-16"></div>
        </div>
      </div>
      <div class="flex gap-4">
        <div
          v-for="i in 6"
          :key="i"
          class="flex-shrink-0 animate-pulse flex flex-col items-center gap-3 w-28"
        >
          <div class="w-16 h-16 bg-gray-100 rounded-2xl"></div>
          <div class="h-3 bg-gray-100 rounded w-16"></div>
        </div>
      </div>
    </div>

    <template v-else-if="categories.length">
      <!-- Row 1: scroll left -->
      <div class="marquee-wrapper mb-4" @mouseenter="pauseAll" @mouseleave="resumeAll">
        <div class="marquee-track" :class="{ paused: isPaused }">
          <div v-for="(cat, idx) in row1Doubled" :key="`r1-${idx}`" class="marquee-item group">
            <div
              class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-sm transition-transform group-hover:scale-110 group-hover:shadow-md"
              :class="ICON_CONFIG[cat.colorIdx].bg"
            >
              <component
                :is="getCategoryIcon(cat.name)"
                class="w-7 h-7"
                :class="ICON_CONFIG[cat.colorIdx].color"
                :stroke-width="1.75"
              />
            </div>
            <span
              class="text-xs font-medium text-gray-700 text-center leading-tight line-clamp-2 max-w-[80px]"
            >
              {{ cat.name }}
            </span>
          </div>
        </div>
      </div>

      <!-- Row 2: scroll right -->
      <div class="marquee-wrapper" @mouseenter="pauseAll" @mouseleave="resumeAll">
        <div class="marquee-track marquee-reverse" :class="{ paused: isPaused }">
          <div v-for="(cat, idx) in row2Doubled" :key="`r2-${idx}`" class="marquee-item group">
            <div
              class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-sm transition-transform group-hover:scale-110 group-hover:shadow-md"
              :class="ICON_CONFIG[cat.colorIdx].bg"
            >
              <component
                :is="getCategoryIcon(cat.name)"
                class="w-7 h-7"
                :class="ICON_CONFIG[cat.colorIdx].color"
                :stroke-width="1.75"
              />
            </div>
            <span
              class="text-xs font-medium text-gray-700 text-center leading-tight line-clamp-2 max-w-[80px]"
            >
              {{ cat.name }}
            </span>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="text-center py-8 text-gray-400 text-sm">Chưa có danh mục nào.</div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import {
  Code2,
  Globe,
  Palette,
  TrendingUp,
  Briefcase,
  BarChart2,
  Smartphone,
  Bot,
  Languages,
  Camera,
  Music,
  Heart,
  DollarSign,
  Calculator,
  BookOpen,
  Cpu,
  PenTool,
  Layers,
} from 'lucide-vue-next'
import type { Component } from 'vue'
import type { Category } from '@/types/course.types'

const props = defineProps<{
  categories: Category[]
  loading: boolean
}>()

const isPaused = ref(false)
function pauseAll() {
  isPaused.value = true
}
function resumeAll() {
  isPaused.value = false
}

const ICON_CONFIG = [
  { bg: 'bg-blue-50', color: 'text-blue-500' },
  { bg: 'bg-yellow-50', color: 'text-yellow-500' },
  { bg: 'bg-green-50', color: 'text-green-500' },
  { bg: 'bg-pink-50', color: 'text-pink-500' },
  { bg: 'bg-purple-50', color: 'text-purple-500' },
  { bg: 'bg-orange-50', color: 'text-orange-500' },
  { bg: 'bg-cyan-50', color: 'text-cyan-500' },
  { bg: 'bg-red-50', color: 'text-red-500' },
]

const ICON_MAP: Array<{ keywords: string[]; icon: Component }> = [
  { keywords: ['lập trình', 'code', 'coding', 'python', 'java', 'php', 'c++'], icon: Code2 },
  { keywords: ['web', 'html', 'css', 'frontend', 'backend'], icon: Globe },
  { keywords: ['thiết kế', 'design', 'figma', 'ui', 'ux', 'đồ họa'], icon: Palette },
  { keywords: ['marketing', 'seo', 'quảng cáo', 'digital'], icon: TrendingUp },
  { keywords: ['kinh doanh', 'business', 'quản trị', 'khởi nghiệp'], icon: Briefcase },
  { keywords: ['data', 'dữ liệu', 'phân tích', 'excel', 'bi'], icon: BarChart2 },
  { keywords: ['mobile', 'android', 'ios', 'flutter', 'react native'], icon: Smartphone },
  { keywords: ['ai', 'máy học', 'deep learning', 'ml', 'trí tuệ'], icon: Bot },
  { keywords: ['ngoại ngữ', 'tiếng anh', 'tiếng nhật', 'ngôn ngữ'], icon: Languages },
  { keywords: ['nhiếp ảnh', 'photography', 'ảnh', 'video'], icon: Camera },
  { keywords: ['âm nhạc', 'music', 'guitar', 'piano'], icon: Music },
  { keywords: ['sức khỏe', 'yoga', 'fitness', 'thể dục'], icon: Heart },
  { keywords: ['tài chính', 'đầu tư', 'chứng khoán', 'kế toán'], icon: DollarSign },
  { keywords: ['toán', 'thống kê', 'xác suất'], icon: Calculator },
  { keywords: ['phần cứng', 'mạng', 'network', 'devops', 'cloud'], icon: Cpu },
  { keywords: ['viết', 'content', 'copywriting', 'sáng tạo'], icon: PenTool },
  { keywords: ['vue', 'react', 'angular', 'javascript', 'typescript'], icon: Layers },
]

function getCategoryIcon(name: string): Component {
  const lower = name.toLowerCase()
  for (const { keywords, icon } of ICON_MAP) {
    if (keywords.some((k) => lower.includes(k))) return icon
  }
  return BookOpen
}

// Gắn colorIdx cố định theo vị trí gốc để màu không thay đổi khi double
type CatWithColor = Category & { colorIdx: number }

const categoriesWithColor = computed<CatWithColor[]>(() =>
  props.categories.map((cat, i) => ({ ...cat, colorIdx: i % ICON_CONFIG.length })),
)

// Repeat đủ để lấp đầy màn hình rồi mới loop — tránh khoảng trắng khi ít items
function fillTrack<T>(items: T[], minCount = 12): T[] {
  if (!items.length) return []
  const times = Math.ceil(minCount / items.length)
  const filled = Array.from({ length: times }, () => items).flat()
  // Double để animation loop liền mạch (translateX -50%)
  return [...filled, ...filled]
}

const row1 = computed(() => {
  const half = Math.ceil(categoriesWithColor.value.length / 2)
  return categoriesWithColor.value.slice(0, half)
})
const row2 = computed(() => {
  const half = Math.ceil(categoriesWithColor.value.length / 2)
  return categoriesWithColor.value.slice(half)
})

const row1Doubled = computed(() => fillTrack(row1.value))
const row2Doubled = computed(() => fillTrack(row2.value))
</script>

<style scoped>
.marquee-wrapper {
  width: 100%;
  overflow: hidden;
  /* fade hai cạnh */
  mask-image: linear-gradient(to right, transparent 0%, black 6%, black 94%, transparent 100%);
  -webkit-mask-image: linear-gradient(
    to right,
    transparent 0%,
    black 6%,
    black 94%,
    transparent 100%
  );
}

.marquee-track {
  display: flex;
  gap: 1.25rem;
  width: max-content;
  animation: scroll-left 30s linear infinite;
}

.marquee-track.marquee-reverse {
  animation: scroll-right 30s linear infinite;
}

.marquee-track.paused {
  animation-play-state: paused;
}

.marquee-item {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.625rem;
  padding: 0.75rem;
  border-radius: 1rem;
  cursor: default;
  transition: background-color 0.2s;
  width: 7rem;
}

.marquee-item:hover {
  background-color: #f9fafb;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@keyframes scroll-left {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}

@keyframes scroll-right {
  0% {
    transform: translateX(-50%);
  }
  100% {
    transform: translateX(0);
  }
}
</style>
