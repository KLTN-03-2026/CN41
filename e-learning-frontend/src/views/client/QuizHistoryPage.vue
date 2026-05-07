<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4">
    <div class="max-w-2xl mx-auto">
      <!-- Header -->
      <div class="flex items-center gap-3 mb-6">
        <button
          @click="router.back()"
          class="w-8 h-8 flex items-center justify-center rounded-lg bg-white shadow-sm text-gray-500 hover:text-gray-800"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <h1 class="text-lg font-semibold text-gray-800">Lịch sử làm bài</h1>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <div
          class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"
        />
      </div>

      <!-- Empty -->
      <div v-else-if="!attempts.length" class="text-center py-12">
        <p class="text-gray-500 text-sm">Bạn chưa làm bài quiz này.</p>
      </div>

      <!-- List -->
      <div v-else class="space-y-3">
        <div
          v-for="(attempt, i) in attempts"
          :key="attempt.id"
          class="bg-white rounded-2xl shadow-sm overflow-hidden"
        >
          <!-- Summary row — click to expand -->
          <button
            class="w-full px-5 py-4 flex items-center gap-4 hover:bg-gray-50 transition-colors text-left"
            @click="toggleExpand(attempt.id)"
          >
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Lần {{ attempts.length - i }}</span>
                <span class="text-xs text-gray-400">{{ formatDate(attempt.created_at) }}</span>
              </div>
              <div class="flex items-center gap-3">
                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div
                    :style="{ width: attempt.percentage + '%' }"
                    :class="scoreColorBg(attempt.percentage)"
                    class="h-full rounded-full"
                  />
                </div>
                <div class="flex-shrink-0 text-right">
                  <span :class="scoreColorText(attempt.percentage)" class="text-base font-bold">
                    {{ attempt.percentage }}%
                  </span>
                  <span class="text-xs text-gray-400 ml-1">
                    ({{ attempt.score }}/{{ attempt.total_questions }})
                  </span>
                </div>
              </div>
            </div>
            <!-- Chevron -->
            <svg
              :class="expandedId === attempt.id ? 'rotate-180' : ''"
              class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <!-- Detail panel -->
          <div
            v-if="expandedId === attempt.id"
            class="border-t border-gray-100 px-5 pb-5 pt-4 space-y-4"
          >
            <div v-for="(q, index) in attempt.questions ?? []" :key="q.id">
              <!-- Question -->
              <div class="flex items-start gap-2 mb-2">
                <span
                  :class="
                    isCorrectInAttempt(attempt, q.id)
                      ? 'bg-green-100 text-green-600'
                      : 'bg-red-100 text-red-600'
                  "
                  class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold"
                >
                  {{ isCorrectInAttempt(attempt, q.id) ? '✓' : '✗' }}
                </span>
                <p class="text-sm font-medium text-gray-800">{{ index + 1 }}. {{ q.question }}</p>
              </div>
              <!-- Options -->
              <div class="space-y-1.5 ml-7">
                <div
                  v-for="opt in ['A', 'B', 'C', 'D'] as const"
                  :key="opt"
                  :class="optionClass(attempt, q.id, opt)"
                  class="flex items-center gap-2 px-3 py-2 rounded-lg border text-sm"
                >
                  <span
                    :class="optionBadge(attempt, q.id, opt)"
                    class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                    >{{ opt }}</span
                  >
                  <span>{{ q[`option_${opt.toLowerCase()}` as keyof typeof q] }}</span>
                  <span
                    v-if="getCorrect(attempt, q.id) === opt"
                    class="ml-auto text-green-600 text-xs font-medium whitespace-nowrap"
                    >Đáp án đúng</span
                  >
                  <span
                    v-else-if="
                      getStudent(attempt, q.id) === opt && !isCorrectInAttempt(attempt, q.id)
                    "
                    class="ml-auto text-red-500 text-xs font-medium whitespace-nowrap"
                    >Bạn chọn</span
                  >
                </div>
              </div>
            </div>

            <!-- Fallback nếu questions null (attempt cũ trước khi có field này) -->
            <p v-if="!attempt.questions?.length" class="text-sm text-gray-400 text-center py-2">
              Không có dữ liệu chi tiết cho lần làm này.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { quizService } from '@/services/quiz.service'
import type { QuizAttempt } from '@/services/quiz.service'

const route = useRoute()
const router = useRouter()
const quizId = Number(route.params.id)

const loading = ref(true)
const attempts = ref<QuizAttempt[]>([])
const expandedId = ref<number | null>(null)

onMounted(async () => {
  try {
    const res = await quizService.attempts(quizId)
    attempts.value = res.data.data
  } finally {
    loading.value = false
  }
})

function toggleExpand(id: number) {
  expandedId.value = expandedId.value === id ? null : id
}

function formatDate(str: string) {
  return new Date(str).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' })
}

function scoreColorBg(pct: number) {
  if (pct >= 80) return 'bg-green-500'
  if (pct >= 50) return 'bg-yellow-500'
  return 'bg-red-500'
}

function scoreColorText(pct: number) {
  if (pct >= 80) return 'text-green-600'
  if (pct >= 50) return 'text-yellow-600'
  return 'text-red-600'
}

// ── Helpers ──────────────────────────────────────────────────────

function getCorrect(attempt: QuizAttempt, questionId: number): string | undefined {
  if (!attempt.correct_answers) return undefined
  return (attempt.correct_answers as Record<string, string>)[String(questionId)]
}

function getStudent(attempt: QuizAttempt, questionId: number): string | undefined {
  if (!attempt.answers) return undefined
  return (attempt.answers as Record<string, string>)[String(questionId)]
}

function isCorrectInAttempt(attempt: QuizAttempt, questionId: number): boolean {
  const c = getCorrect(attempt, questionId)
  const s = getStudent(attempt, questionId)
  return !!c && !!s && c === s
}

function optionClass(attempt: QuizAttempt, questionId: number, opt: 'A' | 'B' | 'C' | 'D'): string {
  const correct = getCorrect(attempt, questionId)
  const student = getStudent(attempt, questionId)
  if (opt === correct) return 'border-green-400 bg-green-50 text-green-800'
  if (opt === student && student !== correct) return 'border-red-400 bg-red-50 text-red-800'
  return 'border-gray-100 text-gray-500'
}

function optionBadge(attempt: QuizAttempt, questionId: number, opt: 'A' | 'B' | 'C' | 'D'): string {
  const correct = getCorrect(attempt, questionId)
  const student = getStudent(attempt, questionId)
  if (opt === correct) return 'bg-green-500 text-white'
  if (opt === student && student !== correct) return 'bg-red-500 text-white'
  return 'bg-gray-100 text-gray-400'
}
</script>
