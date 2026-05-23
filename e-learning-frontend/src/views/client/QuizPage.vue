<template>
  <div class="min-h-screen bg-gradient-to-b from-indigo-50 via-blue-50 to-gray-50 py-10 px-4">
    <div class="max-w-2xl mx-auto">

      <!-- Loading -->
      <div v-if="loading" class="flex flex-col items-center py-24 gap-4">
        <div class="w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin" />
        <p class="text-gray-400 text-sm">Đang tải bài quiz...</p>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="text-center py-24">
        <div class="text-5xl mb-4">⚠️</div>
        <p class="text-gray-600 font-medium">{{ error }}</p>
        <button @click="router.back()" class="mt-5 px-5 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50 font-medium shadow-sm">
          Quay lại
        </button>
      </div>

      <!-- Exceeded attempts -->
      <div v-else-if="quiz && attemptsExceeded" class="bg-white rounded-3xl p-10 text-center shadow-md border border-gray-100">
        <div class="text-6xl mb-4">🚫</div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Đã hết lượt làm bài</h2>
        <p class="text-gray-500 mb-6">Bạn đã dùng hết {{ quiz.max_attempts }}/{{ quiz.max_attempts }} lượt cho bài quiz này.</p>
        <button
          @click="router.push({ name: 'quiz-history', params: { id: quiz.id } })"
          class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-200"
        >
          Xem lịch sử làm bài
        </button>
      </div>

      <!-- ── RESULT MODE ── -->
      <div v-else-if="resultData">
        <!-- Score card -->
        <div
          :class="resultData.percentage >= 80 ? 'from-green-500 to-emerald-600' : resultData.percentage >= 50 ? 'from-yellow-400 to-orange-500' : 'from-red-500 to-rose-600'"
          class="bg-gradient-to-br rounded-3xl p-8 mb-6 text-white text-center shadow-xl"
        >
          <div class="text-6xl mb-3">
            {{ resultData.percentage >= 80 ? '🎉' : resultData.percentage >= 50 ? '👍' : '😢' }}
          </div>
          <div class="text-6xl font-black mb-1">{{ resultData.percentage }}%</div>
          <p class="text-white/80 text-lg font-medium mb-4">
            {{ resultData.score }}/{{ resultData.total_questions }} câu đúng
          </p>
          <div class="h-3 bg-white/30 rounded-full overflow-hidden max-w-xs mx-auto mb-6">
            <div
              :style="{ width: resultData.percentage + '%' }"
              class="h-full bg-white rounded-full transition-all duration-700"
            />
          </div>
          <div class="flex justify-center gap-3">
            <button
              @click="router.push({ name: 'quiz-history', params: { id: quiz!.id } })"
              class="px-5 py-2.5 bg-white/20 hover:bg-white/30 border border-white/40 text-white font-semibold rounded-xl transition-colors"
            >
              Lịch sử làm bài
            </button>
            <button
              @click="router.back()"
              class="px-5 py-2.5 bg-white text-gray-800 font-semibold rounded-xl hover:bg-gray-50 transition-colors shadow-sm"
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
            :class="isCorrect(q.id) ? 'border-green-200 bg-green-50/50' : 'border-red-200 bg-red-50/50'"
            class="rounded-2xl p-5 border-2 shadow-sm"
          >
            <div class="flex items-start gap-3 mb-4">
              <span
                :class="isCorrect(q.id) ? 'bg-green-500 text-white' : 'bg-red-500 text-white'"
                class="mt-0.5 flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold shadow-sm"
              >
                {{ isCorrect(q.id) ? '✓' : '✗' }}
              </span>
              <p class="font-semibold text-gray-800 text-base leading-snug">{{ index + 1 }}. {{ q.question }}</p>
            </div>
            <div class="space-y-2.5">
              <div
                v-for="opt in ['A', 'B', 'C', 'D'] as const"
                :key="opt"
                :class="optionResultClass(q.id, opt)"
                class="flex items-center gap-3 px-4 py-3.5 rounded-xl border-2 text-base font-medium"
              >
                <span
                  :class="optionBadgeClass(q.id, opt)"
                  class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                >{{ opt }}</span>
                <span class="flex-1">{{ q[`option_${opt.toLowerCase()}` as keyof typeof q] }}</span>
                <span v-if="getCorrectAnswer(q.id) === opt" class="text-green-700 text-xs font-bold bg-green-100 px-2 py-1 rounded-lg">✓ Đáp án đúng</span>
                <span v-else-if="getStudentAnswer(q.id) === opt && !isCorrect(q.id)" class="text-red-600 text-xs font-bold bg-red-100 px-2 py-1 rounded-lg">Bạn chọn</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── QUIZ FORM ── -->
      <div v-else-if="quiz && questions.length">

        <!-- Header -->
        <div class="bg-white rounded-3xl p-6 mb-6 shadow-md border border-gray-100">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <h1 class="font-bold text-gray-900 text-xl leading-tight">{{ quiz.title }}</h1>
              <p v-if="quiz.description" class="text-gray-500 mt-1.5 text-base">{{ quiz.description }}</p>
            </div>
            <!-- Timer -->
            <div v-if="quiz.time_limit && timeLeft !== null" class="flex-shrink-0">
              <div
                :class="timeLeft < 60 ? 'bg-red-50 border-red-300 text-red-600' : 'bg-indigo-50 border-indigo-200 text-indigo-700'"
                class="border-2 rounded-2xl px-4 py-2 text-center"
              >
                <div class="text-2xl font-mono font-black">{{ formatTime(timeLeft) }}</div>
                <p class="text-xs font-medium opacity-70">còn lại</p>
              </div>
            </div>
          </div>
          <div class="flex flex-wrap gap-3 mt-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700 text-sm font-semibold rounded-lg">
              📝 {{ questions.length }} câu hỏi
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 text-sm font-semibold rounded-lg">
              🔁 {{ quiz.max_attempts }} lượt làm
            </span>
            <span v-if="quiz.time_limit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 text-orange-700 text-sm font-semibold rounded-lg">
              ⏱ {{ quiz.time_limit }} phút
            </span>
          </div>
        </div>

        <!-- Questions -->
        <div class="space-y-5 mb-6">
          <div
            v-for="(q, index) in questions"
            :key="q.id"
            class="bg-white rounded-3xl p-6 shadow-md border border-gray-100"
          >
            <!-- Question header -->
            <div class="flex items-start gap-3 mb-5">
              <span class="flex-shrink-0 w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                {{ index + 1 }}
              </span>
              <p class="font-semibold text-gray-900 text-base leading-relaxed">{{ q.question }}</p>
            </div>

            <!-- Options -->
            <div class="space-y-3">
              <label
                v-for="opt in ['A', 'B', 'C', 'D'] as const"
                :key="opt"
                :class="
                  answers[q.id] === opt
                    ? 'border-indigo-500 bg-indigo-50 shadow-md shadow-indigo-100'
                    : 'border-gray-200 bg-white hover:border-indigo-300 hover:bg-indigo-50/40'
                "
                class="flex items-center gap-4 px-5 py-4 rounded-2xl border-2 cursor-pointer transition-all duration-150"
              >
                <input type="radio" :name="`q_${q.id}`" :value="opt" v-model="answers[q.id]" class="hidden" />
                <span
                  :class="answers[q.id] === opt ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-500'"
                  class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold flex-shrink-0 transition-colors"
                >{{ opt }}</span>
                <span
                  :class="answers[q.id] === opt ? 'text-indigo-900 font-semibold' : 'text-gray-700'"
                  class="text-base leading-snug"
                >{{ q[`option_${opt.toLowerCase()}` as keyof typeof q] }}</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Submit bar -->
        <div class="bg-white rounded-3xl px-6 py-5 shadow-md border border-gray-100 flex items-center justify-between gap-4">
          <div>
            <p class="text-gray-500 text-sm">Đã trả lời</p>
            <p class="font-bold text-gray-900 text-lg">{{ answeredCount }}<span class="text-gray-400 font-normal text-base">/{{ questions.length }} câu</span></p>
          </div>
          <!-- Progress dots -->
          <div class="flex-1 hidden sm:flex items-center gap-1 flex-wrap">
            <span
              v-for="(q, i) in questions"
              :key="q.id"
              :class="answers[q.id] ? 'bg-indigo-500' : 'bg-gray-200'"
              class="w-2.5 h-2.5 rounded-full transition-colors"
              :title="`Câu ${i + 1}`"
            />
          </div>
          <button
            @click="submitQuiz"
            :disabled="submitting || answeredCount === 0"
            class="px-7 py-3.5 bg-indigo-600 text-white font-bold text-base rounded-2xl hover:bg-indigo-700 disabled:opacity-40 transition-all shadow-lg shadow-indigo-200 disabled:shadow-none"
          >
            {{ submitting ? 'Đang nộp...' : 'Nộp bài' }}
          </button>
        </div>
      </div>

      <!-- No quiz -->
      <div v-else-if="!loading" class="text-center py-24">
        <div class="text-5xl mb-4">📭</div>
        <p class="text-gray-500 font-medium">Bài học này chưa có quiz.</p>
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
  } finally {
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
