<template>
  <section ref="sectionRef" class="bg-white border-b border-gray-100 py-10">
    <div class="max-w-6xl mx-auto px-4">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="(stat, index) in STATS"
          :key="index"
          class="flex flex-col items-center text-center gap-3"
        >
          <div :class="['w-14 h-14 rounded-full flex items-center justify-center', stat.color]">
            <component :is="stat.icon" class="w-6 h-6" />
          </div>
          <div class="text-3xl font-extrabold text-gray-900">
            {{ displayValues[index] }}
          </div>
          <div class="text-sm text-gray-500">{{ stat.label }}</div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { BookOpen, Users, Award, Star } from 'lucide-vue-next'

const STATS = [
  {
    icon: BookOpen,
    value: '500+',
    numericValue: 500,
    suffix: '+',
    label: 'Khóa học',
    color: 'bg-blue-50 text-blue-500',
  },
  {
    icon: Users,
    value: '10,000+',
    numericValue: 10000,
    suffix: '+',
    label: 'Học viên',
    color: 'bg-green-50 text-green-500',
  },
  {
    icon: Award,
    value: '50+',
    numericValue: 50,
    suffix: '+',
    label: 'Giảng viên',
    color: 'bg-purple-50 text-purple-500',
  },
  {
    icon: Star,
    value: '4.8/5',
    numericValue: null,
    suffix: '',
    label: 'Đánh giá',
    color: 'bg-yellow-50 text-yellow-500',
  },
]

const sectionRef = ref<HTMLElement | null>(null)
const displayValues = ref(STATS.map((s) => (s.numericValue === null ? s.value : '0' + s.suffix)))
let animated = false
let observer: IntersectionObserver | null = null

function easeOut(t: number): number {
  return 1 - Math.pow(1 - t, 3)
}

function formatNumber(n: number): string {
  return n.toLocaleString('en-US')
}

function runCountUp() {
  if (animated) return
  animated = true

  const duration = 1500
  const start = performance.now()

  function tick(now: number) {
    const elapsed = now - start
    const progress = Math.min(elapsed / duration, 1)
    const eased = easeOut(progress)

    STATS.forEach((stat, i) => {
      if (stat.numericValue === null) {
        displayValues.value[i] = stat.value
      } else {
        const current = Math.round(eased * stat.numericValue)
        displayValues.value[i] = formatNumber(current) + stat.suffix
      }
    })

    if (progress < 1) requestAnimationFrame(tick)
  }

  requestAnimationFrame(tick)
}

onMounted(() => {
  observer = new IntersectionObserver(
    ([entry]) => {
      if (entry.isIntersecting) runCountUp()
    },
    { threshold: 0.3 },
  )
  if (sectionRef.value) observer.observe(sectionRef.value)
})

onUnmounted(() => {
  observer?.disconnect()
})
</script>
