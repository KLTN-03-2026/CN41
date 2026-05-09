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

      <!-- ── RESULT MODE ── -->
      <div v-else-if="resultData">
        <!-- Score card -->
        <div class="bg-white rounded-2xl p-6 mb-5 shadow-sm text-center">
          <div class="text-5xl mb-3">
            {{ resultData.percentage >= 80 ? '🎉' : resultData.percentage >= 50 ? '👍' : '😢' }}
          </div>
          <h2 class="text-xl font-bold text-gray-800 mb-1">
            {{ resultData.score }}/{{ resultData.total_questions }} câu đúng
          </h2>
          <div
            :class="
              resultData.percentage >= 80
                ? 'text-green-600'
                : resultData.percentage >= 50
                  ? 'text-yellow-600'
                  : 'text-red-600'
            "
            class="text-3xl font-extrabold mb-3"
          >
            {{ resultData.percentage }}%
          </div>
          <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden max-w-xs mx-auto mb-4">
            <div
              :style="{ width: resultData.percentage + '%' }"
              :class="
                resultData.percentage >= 80
                  ? 'bg-green-500'
                  : resultData.percentage >= 50
                    ? 'bg-yellow-500'
                    : 'bg-red-500'
              "
              class="h-full rounded-full transition-all duration-700"
            />
          </div>
          <div class="flex justify-center gap-3">
            <button
              @click="router.push({ name: 'quiz-history', params: { id: quiz!.id } })"
              class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50"
            >
              Lịch sử làm bài
            </button>
            <button
              @click="router.back()"
              class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600"
            >
              Quay lại bài học
            </button>
          </div>
        </div>

        <!-- Per-question review -->
        <div class="space-y-4">
          <div
            v-for="(q, index) in questions"
            :key="q.id"
            class="bg-white rounded-2xl p-5 shadow-sm"
          >
            <div class="flex items-start gap-2 mb-3">
              <span
                :class="isCorrect(q.id) ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'"
                class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold"
              >
                {{ isCorrect(q.id) ? '✓' : '✗' }}
              </span>
              <p class="font-medium text-gray-800 text-sm">{{ index + 1 }}. {{ q.question }}</p>
            </div>
            <div class="space-y-2">
              <div
                v-for="opt in ['A', 'B', 'C', 'D'] as const"
                :key="opt"
                :class="optionResultClass(q.id, opt)"
                class="flex items-center gap-3 px-4 py-3 rounded-xl border text-sm"
              >
                <span
                  :class="optionBadgeClass(q.id, opt)"
                  class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                  >{{ opt }}</span
                >
                <span>{{ q[`option_${opt.toLowerCase()}` as keyof typeof q] }}</span>
                <span
                  v-if="getCorrectAnswer(q.id) === opt"
                  class="ml-auto text-green-600 text-xs font-medium"
                  >Đáp án đúng</span
                >
                <span
                  v-else-if="getStudentAnswer(q.id) === opt && !isCorrect(q.id)"
                  class="ml-auto text-red-500 text-xs font-medium"
                  >Bạn chọn</span
                >
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── QUIZ FORM ── -->
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
import type { Quiz, QuizQuestion, QuizAttempt } from '@/services/quiz.service'
import {
  getCorrectAnswer as getCorrectAnswerUtil,
  getStudentAnswer as getStudentAnswerUtil,
  isQuestionCorrect,
  optionResultClass as optionResultClassUtil,
  optionBadgeClass as optionBadgeClassUtil,
} from '@/utils/quizResult'
import type { Option } from '@/utils/quizResult'

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
const resultData = ref<QuizAttempt | null>(null)
let timer: ReturnType<typeof setInterval> | null = null

const answeredCount = computed(() => Object.keys(answers.value).length)

onMounted(async () => {
  try {
    const res = await quizService.getByLesson(lessonId)
    quiz.value = res.data.data.quiz
    questions.value = res.data.data.questions

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
    resultData.value = res.data.data
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (err: unknown) {
    const axiosError = err as { response?: { data?: { message?: string } } }
    toast.error(axiosError.response?.data?.message || 'Nộp bài thất bại')
    submitting.value = false
  }
}

// ── Helpers for result display — delegate to @/utils/quizResult ──

function getCorrectAnswer(questionId: number) {
  return getCorrectAnswerUtil(resultData.value?.correct_answers, questionId)
}

function getStudentAnswer(questionId: number) {
  return getStudentAnswerUtil(resultData.value?.answers, questionId)
}

function isCorrect(questionId: number): boolean {
  return isQuestionCorrect(resultData.value?.correct_answers, resultData.value?.answers, questionId)
}

function optionResultClass(questionId: number, opt: 'A' | 'B' | 'C' | 'D'): string {
  return optionResultClassUtil(
    resultData.value?.correct_answers,
    resultData.value?.answers,
    questionId,
    opt as Option,
  )
}

function optionBadgeClass(questionId: number, opt: 'A' | 'B' | 'C' | 'D'): string {
  return optionBadgeClassUtil(
    resultData.value?.correct_answers,
    resultData.value?.answers,
    questionId,
    opt as Option,
  )
}
</script>
