<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4">
    <div class="max-w-2xl mx-auto">
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

      <div v-if="loading" class="flex justify-center py-12">
        <div
          class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"
        />
      </div>

      <div v-else-if="!attempts.length" class="text-center py-12">
        <p class="text-gray-500 text-sm">Bạn chưa làm bài quiz này.</p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="(attempt, i) in attempts"
          :key="attempt.id"
          class="bg-white rounded-2xl p-5 shadow-sm"
        >
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-medium text-gray-700">Lần {{ attempts.length - i }}</span>
            <span class="text-xs text-gray-400">{{ formatDate(attempt.created_at) }}</span>
          </div>
          <div class="flex items-center gap-4">
            <div class="flex-1">
              <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                <div
                  :style="{ width: attempt.percentage + '%' }"
                  :class="
                    attempt.percentage >= 80
                      ? 'bg-green-500'
                      : attempt.percentage >= 50
                        ? 'bg-yellow-500'
                        : 'bg-red-500'
                  "
                  class="h-full rounded-full transition-all"
                />
              </div>
            </div>
            <div class="text-right flex-shrink-0">
              <span
                :class="
                  attempt.percentage >= 80
                    ? 'text-green-600'
                    : attempt.percentage >= 50
                      ? 'text-yellow-600'
                      : 'text-red-600'
                "
                class="text-lg font-bold"
                >{{ attempt.percentage }}%</span
              >
              <p class="text-xs text-gray-400">
                {{ attempt.score }}/{{ attempt.total_questions }} câu
              </p>
            </div>
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

onMounted(async () => {
  try {
    const res = await quizService.attempts(quizId)
    attempts.value = res.data.data
  } finally {
    loading.value = false
  }
})

function formatDate(str: string) {
  return new Date(str).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' })
}
</script>
