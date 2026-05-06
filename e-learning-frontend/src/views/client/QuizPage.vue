<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4">
    <div class="max-w-2xl mx-auto">
      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-20">
        <div
          class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"
        />
      </div>

      <!-- Error -->
      <div v-else-if="error" class="text-center py-20">
        <p class="text-gray-500">{{ error }}</p>
        <button @click="router.back()" class="mt-4 text-sm text-blue-500 hover:underline">
          Quay lại
        </button>
      </div>

      <!-- Exceeded attempts -->
      <div
        v-else-if="quiz && attemptsExceeded"
        class="bg-white rounded-2xl p-8 text-center shadow-sm"
      >
        <div class="text-4xl mb-3">🚫</div>
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Đã hết lượt làm bài</h2>
        <p class="text-sm text-gray-500 mb-5">
          Bạn đã dùng hết {{ quiz.max_attempts }}/{{ quiz.max_attempts }} lượt.
        </p>
        <button
          @click="router.push({ name: 'quiz-history', params: { id: quiz.id } })"
          class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600"
        >
          Xem lịch sử làm bài
        </button>
      </div>

      <!-- Quiz form -->
      <div v-else-if="quiz && questions.length">
        <!-- Header -->
        <div class="bg-white rounded-2xl p-5 mb-4 shadow-sm">
          <div class="flex items-start justify-between">
            <div>
              <h1 class="font-semibold text-gray-800 text-lg">{{ quiz.title }}</h1>
              <p v-if="quiz.description" class="text-sm text-gray-500 mt-1">
                {{ quiz.description }}
              </p>
            </div>
            <!-- Timer -->
            <div v-if="quiz.time_limit && timeLeft !== null" class="text-right">
              <div
                :class="timeLeft < 60 ? 'text-red-500' : 'text-gray-700'"
                class="text-xl font-mono font-bold"
              >
                {{ formatTime(timeLeft) }}
              </div>
              <p class="text-xs text-gray-400">còn lại</p>
            </div>
          </div>
          <div class="flex gap-4 mt-3 text-xs text-gray-400">
            <span>{{ questions.length }} câu hỏi</span>
            <span>{{ quiz.max_attempts }} lượt làm</span>
            <span v-if="quiz.time_limit">{{ quiz.time_limit }} phút</span>
          </div>
        </div>

        <!-- Questions -->
        <div class="space-y-4 mb-6">
          <div
            v-for="(q, index) in questions"
            :key="q.id"
            class="bg-white rounded-2xl p-5 shadow-sm"
          >
            <p class="font-medium text-gray-800 mb-3 text-sm">{{ index + 1 }}. {{ q.question }}</p>
            <div class="space-y-2">
              <label
                v-for="opt in ['A', 'B', 'C', 'D'] as const"
                :key="opt"
                :class="
                  answers[q.id] === opt
                    ? 'border-blue-500 bg-blue-50 text-blue-800'
                    : 'border-gray-200 text-gray-700 hover:border-gray-300'
                "
                class="flex items-center gap-3 px-4 py-3 rounded-xl border cursor-pointer transition-all text-sm"
              >
                <input
                  type="radio"
                  :name="`q_${q.id}`"
                  :value="opt"
                  v-model="answers[q.id]"
                  class="hidden"
                />
                <span
                  :class="
                    answers[q.id] === opt ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-500'
                  "
                  class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                  >{{ opt }}</span
                >
                <span>{{ q[`option_${opt.toLowerCase()}` as keyof typeof q] }}</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Submit -->
        <div class="bg-white rounded-2xl p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">
              Đã trả lời:
              <span class="font-medium text-gray-800"
                >{{ answeredCount }}/{{ questions.length }}</span
              >
            </p>
            <button
              @click="submitQuiz"
              :disabled="submitting || answeredCount === 0"
              class="px-5 py-2 bg-blue-500 text-white text-sm font-medium rounded-lg hover:bg-blue-600 disabled:opacity-50 transition-colors"
            >
              {{ submitting ? 'Đang nộp...' : 'Nộp bài' }}
            </button>
          </div>
        </div>
      </div>

      <!-- No quiz -->
      <div v-else-if="!loading" class="text-center py-20">
        <p class="text-gray-500 text-sm">Bài học này chưa có quiz.</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { quizService } from '@/services/quiz.service'
import type { Quiz, QuizQuestion } from '@/services/quiz.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const lessonId = Number(route.params.lessonId)

const loading = ref(true)
const error = ref('')
const quiz = ref<Quiz | null>(null)
const questions = ref<QuizQuestion[]>([])
const answers = ref<Record<number, string>>({})
const submitting = ref(false)
const attemptsExceeded = ref(false)
const timeLeft = ref<number | null>(null)
let timer: ReturnType<typeof setInterval> | null = null

const answeredCount = computed(() => Object.keys(answers.value).length)

onMounted(async () => {
  try {
    const res = await quizService.getByLesson(lessonId)
    quiz.value = res.data.data.quiz
    questions.value = res.data.data.questions

    // Setup timer
    if (quiz.value.time_limit) {
      timeLeft.value = quiz.value.time_limit * 60
      timer = setInterval(() => {
        if (timeLeft.value !== null) {
          timeLeft.value--
          if (timeLeft.value <= 0) {
            clearInterval(timer!)
            submitQuiz()
          }
        }
      }, 1000)
    }
  } catch (err: unknown) {
    const axiosError = err as { response?: { status?: number; data?: { message?: string } } }
    if (axiosError.response?.status === 403) {
      attemptsExceeded.value = true
    } else if (axiosError.response?.status === 404) {
      error.value = 'Bài học này chưa có quiz.'
    } else {
      error.value = 'Không thể tải quiz.'
    }
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

function formatTime(seconds: number): string {
  const m = Math.floor(seconds / 60)
    .toString()
    .padStart(2, '0')
  const s = (seconds % 60).toString().padStart(2, '0')
  return `${m}:${s}`
}

async function submitQuiz() {
  if (!quiz.value) return
  submitting.value = true
  if (timer) clearInterval(timer)

  const payload: Record<string, string> = {}
  for (const [qId, ans] of Object.entries(answers.value)) {
    payload[qId] = ans
  }

  try {
    const res = await quizService.submit(quiz.value.id, payload)
    const attempt = res.data.data
    toast.success(`Nộp bài thành công! Điểm: ${attempt.score}/${attempt.total_questions}`)
    router.push({ name: 'quiz-result', params: { id: quiz.value.id, attemptId: attempt.id } })
  } catch (err: unknown) {
    const axiosError = err as { response?: { data?: { message?: string } } }
    toast.error(axiosError.response?.data?.message || 'Nộp bài thất bại')
    submitting.value = false
  }
}
</script>
