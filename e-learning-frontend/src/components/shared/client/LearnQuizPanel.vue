<template>
  <div class="p-4 md:p-6">
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div
        class="w-6 h-6 border-2 border-purple-500 border-t-transparent rounded-full animate-spin"
      />
    </div>

    <!-- No quiz -->
    <div v-else-if="!quiz" class="text-center py-12 text-gray-400 text-sm">
      Bài kiểm tra đang được chuẩn bị.
    </div>

    <!-- History detail: xem chi tiết 1 lần làm cũ -->
    <div v-else-if="viewingAttempt" class="max-w-lg mx-auto space-y-4">
      <!-- Back button -->
      <button
        @click="viewingAttempt = null"
        class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-purple-500 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 19l-7-7 7-7"
          />
        </svg>
        Quay lại lịch sử
      </button>

      <!-- Score card -->
      <div
        class="bg-white dark:bg-gray-900 rounded-2xl p-6 text-center shadow-sm border border-gray-100 dark:border-gray-800"
      >
        <div class="text-5xl mb-3">{{ attemptEmoji(viewingAttempt) }}</div>
        <h2
          class="text-2xl font-bold mb-0.5"
          :class="
            viewingAttempt.percentage >= 50 ? 'text-green-600 dark:text-green-400' : 'text-red-500'
          "
        >
          {{ viewingAttempt.percentage }}%
        </h2>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">
          {{ attemptLabel(viewingAttempt) }}
        </p>

        <div class="grid grid-cols-2 gap-3 mb-4">
          <div class="bg-gray-50 dark:bg-gray-800 rounded-xl py-3 px-2">
            <p class="text-lg font-bold text-gray-800 dark:text-white">
              {{ viewingAttempt.score }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">Câu đúng</p>
          </div>
          <div class="bg-gray-50 dark:bg-gray-800 rounded-xl py-3 px-2">
            <p class="text-lg font-bold text-gray-800 dark:text-white">
              {{ viewingAttempt.total_questions - viewingAttempt.score }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">Câu sai</p>
          </div>
        </div>

        <p class="text-xs text-gray-400">Nộp lúc: {{ formatDate(viewingAttempt.completed_at) }}</p>
      </div>

      <!-- Per-question breakdown -->
      <QuestionBreakdown :questions="questions" :attempt="viewingAttempt" />
    </div>

    <!-- History list: danh sách các lần làm -->
    <div v-else-if="showHistory" class="max-w-lg mx-auto space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <button
          @click="showHistory = false"
          class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-purple-500 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            />
          </svg>
          Quay lại
        </button>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Lịch sử làm bài</h3>
        <div class="w-16" />
      </div>

      <!-- Attempts list -->
      <div class="space-y-3">
        <button
          v-for="(attempt, i) in attemptHistory"
          :key="attempt.id"
          @click="viewingAttempt = attempt"
          class="w-full bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800 hover:border-purple-300 dark:hover:border-purple-700 transition-colors text-left"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div
                :class="
                  attempt.percentage >= 50
                    ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400'
                    : 'bg-red-100 dark:bg-red-900/30 text-red-500'
                "
                class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold flex-shrink-0"
              >
                {{ attempt.percentage }}%
              </div>
              <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Lần {{ attemptHistory.length - i }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                  {{ attempt.score }}/{{ attempt.total_questions }} câu đúng ·
                  {{ formatDate(attempt.completed_at) }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span
                :class="
                  attempt.percentage >= 50
                    ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400'
                    : 'bg-red-100 dark:bg-red-900/30 text-red-500'
                "
                class="text-xs px-2 py-0.5 rounded-full font-medium"
                >{{ attempt.percentage >= 50 ? 'Đạt' : 'Chưa đạt' }}</span
              >
              <svg
                class="w-4 h-4 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </div>
          </div>
        </button>
      </div>
    </div>

    <!-- Result screen: vừa nộp xong -->
    <div v-else-if="lastAttempt" class="max-w-lg mx-auto space-y-4">
      <!-- Score card -->
      <div
        class="bg-white dark:bg-gray-900 rounded-2xl p-6 text-center shadow-sm border border-gray-100 dark:border-gray-800"
      >
        <div class="text-5xl mb-3">{{ resultEmoji }}</div>
        <h2
          class="text-2xl font-bold mb-0.5"
          :class="
            lastAttempt.percentage >= 50 ? 'text-green-600 dark:text-green-400' : 'text-red-500'
          "
        >
          {{ lastAttempt.percentage }}%
        </h2>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">{{ resultLabel }}</p>

        <div class="grid grid-cols-3 gap-3 mb-4">
          <div class="bg-gray-50 dark:bg-gray-800 rounded-xl py-3 px-2">
            <p class="text-lg font-bold text-gray-800 dark:text-white">{{ lastAttempt.score }}</p>
            <p class="text-xs text-gray-400 mt-0.5">Câu đúng</p>
          </div>
          <div class="bg-gray-50 dark:bg-gray-800 rounded-xl py-3 px-2">
            <p class="text-lg font-bold text-gray-800 dark:text-white">
              {{ lastAttempt.total_questions - lastAttempt.score }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">Câu sai</p>
          </div>
          <div class="bg-gray-50 dark:bg-gray-800 rounded-xl py-3 px-2">
            <p class="text-lg font-bold text-gray-800 dark:text-white">{{ attemptsUsed }}</p>
            <p class="text-xs text-gray-400 mt-0.5">Lần làm</p>
          </div>
        </div>

        <div class="flex flex-col gap-1 text-xs text-gray-400 mb-5">
          <span>Nộp lúc: {{ formatDate(lastAttempt.completed_at) }}</span>
          <span v-if="attemptsLeft > 0">Còn {{ attemptsLeft }} lượt làm lại</span>
          <span v-else class="text-orange-400">Đã dùng hết lượt làm bài</span>
        </div>

        <div class="flex gap-2 justify-center">
          <button
            v-if="attemptHistory.length > 1"
            @click="showHistory = true"
            class="px-4 py-2 text-sm border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 rounded-xl hover:border-gray-300 transition-colors"
          >
            Lịch sử ({{ attemptHistory.length }} lần)
          </button>
          <button
            v-if="attemptsLeft > 0"
            @click="startQuiz"
            class="px-5 py-2 text-sm bg-purple-500 text-white rounded-xl hover:bg-purple-600 transition-colors font-medium"
          >
            Làm lại
          </button>
        </div>
      </div>

      <!-- Per-question breakdown -->
      <QuestionBreakdown :questions="questions" :attempt="lastAttempt" />
    </div>

    <!-- Quiz form -->
    <div v-else-if="started" class="max-w-lg mx-auto space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-gray-800 dark:text-white/90">{{ quiz.title }}</h2>
        <div
          v-if="quiz.time_limit && timeLeft !== null"
          :class="timeLeft < 60 ? 'text-red-500' : 'text-gray-600 dark:text-gray-400'"
          class="font-mono font-bold text-sm"
        >
          {{ formatTime(timeLeft) }}
        </div>
      </div>

      <div
        v-for="(q, i) in questions"
        :key="q.id"
        class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800"
      >
        <p class="text-sm font-medium text-gray-800 dark:text-white/90 mb-3">
          {{ i + 1 }}. {{ q.question }}
        </p>
        <div class="space-y-2">
          <label
            v-for="opt in ['A', 'B', 'C', 'D'] as const"
            :key="opt"
            :class="
              answers[q.id] === opt
                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-800 dark:text-purple-300'
                : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-300'
            "
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl border cursor-pointer transition-all text-sm"
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
                answers[q.id] === opt
                  ? 'bg-purple-500 text-white'
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-500'
              "
              class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
              >{{ opt }}</span
            >
            <span>{{ q[`option_${opt.toLowerCase()}` as keyof typeof q] }}</span>
          </label>
        </div>
      </div>

      <div
        class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800"
      >
        <div class="flex items-center justify-between">
          <p class="text-sm text-gray-500">
            Đã trả lời:
            <span class="font-medium text-gray-800 dark:text-white/90"
              >{{ answeredCount }}/{{ questions.length }}</span
            >
          </p>
          <button
            @click="submitQuiz"
            :disabled="submitting || answeredCount === 0"
            class="px-4 py-2 bg-purple-500 text-white text-sm font-medium rounded-lg hover:bg-purple-600 disabled:opacity-50 transition-colors"
          >
            {{ submitting ? 'Đang nộp...' : 'Nộp bài' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Start screen -->
    <div v-else class="max-w-lg mx-auto space-y-4">
      <div
        class="bg-white dark:bg-gray-900 rounded-2xl p-6 text-center shadow-sm border border-gray-100 dark:border-gray-800"
      >
        <div class="text-4xl mb-3">📝</div>
        <h2 class="font-semibold text-gray-800 dark:text-white/90 mb-1">{{ quiz.title }}</h2>
        <div class="flex justify-center gap-4 text-xs text-gray-400 mb-5">
          <span>{{ questions.length }} câu hỏi</span>
          <span>{{ quiz.max_attempts }} lượt làm</span>
          <span v-if="quiz.time_limit">{{ quiz.time_limit }} phút</span>
        </div>
        <div v-if="attemptsLeft === 0" class="text-sm text-gray-400 mb-4">
          Bạn đã hết lượt làm bài.
        </div>
        <div class="flex gap-2 justify-center">
          <button
            v-if="attemptHistory.length > 0"
            @click="showHistory = true"
            class="px-4 py-2 text-sm border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 rounded-xl hover:border-gray-300 transition-colors"
          >
            Xem lịch sử ({{ attemptHistory.length }} lần)
          </button>
          <button
            v-if="attemptsLeft > 0"
            @click="startQuiz"
            class="px-6 py-2.5 bg-purple-500 text-white text-sm font-medium rounded-xl hover:bg-purple-600 transition-colors"
          >
            Bắt đầu làm bài (còn {{ attemptsLeft }} lượt)
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from 'vue-toastification'
import { quizService } from '@/services/quiz.service'
import type { Quiz, QuizQuestion, QuizAttempt } from '@/services/quiz.service'
import QuestionBreakdown from '@/components/shared/client/QuestionBreakdown.vue'

// ── Main component ────────────────────────────────────────────────────────────
const props = defineProps<{ lessonId: number }>()
const emit = defineEmits<{ completed: [] }>()
const toast = useToast()

const loading = ref(true)
const quiz = ref<Quiz | null>(null)
const questions = ref<QuizQuestion[]>([])
const answers = ref<Record<number, string>>({})
const submitting = ref(false)
const started = ref(false)
const lastAttempt = ref<QuizAttempt | null>(null)
const attemptHistory = ref<QuizAttempt[]>([])
const showHistory = ref(false)
const viewingAttempt = ref<QuizAttempt | null>(null)
const attemptsUsed = ref(0)
const timeLeft = ref<number | null>(null)
let timer: ReturnType<typeof setInterval> | null = null

const answeredCount = computed(() => Object.keys(answers.value).length)
const attemptsLeft = computed(() =>
  Math.max(0, (quiz.value?.max_attempts ?? 0) - attemptsUsed.value),
)
const resultEmoji = computed(() => attemptEmoji(lastAttempt.value))
const resultLabel = computed(() => attemptLabel(lastAttempt.value))

function attemptEmoji(a: QuizAttempt | null) {
  const p = a?.percentage ?? 0
  return p >= 80 ? '🎉' : p >= 50 ? '👍' : '😅'
}
function attemptLabel(a: QuizAttempt | null) {
  const p = a?.percentage ?? 0
  return p >= 80 ? 'Xuất sắc!' : p >= 50 ? 'Đạt yêu cầu' : 'Chưa đạt'
}

function formatDate(iso: string) {
  if (!iso) return ''
  const d = new Date(iso)
  return (
    d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' ' +
    d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
  )
}

onMounted(async () => {
  try {
    const res = await quizService.getByLesson(props.lessonId)
    quiz.value = res.data.data.quiz
    questions.value = res.data.data.questions

    const attRes = await quizService.attempts(quiz.value.id)
    attemptHistory.value = attRes.data.data
    attemptsUsed.value = attemptHistory.value.length
  } catch {
    // No quiz yet
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

function startQuiz() {
  answers.value = {}
  lastAttempt.value = null
  started.value = true
  showHistory.value = false
  viewingAttempt.value = null

  if (quiz.value?.time_limit) {
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
}

function formatTime(s: number) {
  return `${Math.floor(s / 60)
    .toString()
    .padStart(2, '0')}:${(s % 60).toString().padStart(2, '0')}`
}

async function submitQuiz() {
  if (!quiz.value) return
  submitting.value = true
  if (timer) clearInterval(timer)

  const payload: Record<string, string> = {}
  for (const [id, ans] of Object.entries(answers.value)) {
    payload[id] = ans
  }

  try {
    const res = await quizService.submit(quiz.value.id, payload)
    lastAttempt.value = res.data.data
    attemptsUsed.value++
    attemptHistory.value = [res.data.data, ...attemptHistory.value]
    started.value = false
    toast.success(
      `Nộp bài thành công! ${lastAttempt.value.score}/${lastAttempt.value.total_questions} câu đúng`,
    )

    if ((lastAttempt.value.percentage ?? 0) >= 50) {
      emit('completed')
    }
  } catch (err: unknown) {
    const e = err as { response?: { data?: { message?: string } } }
    toast.error(e.response?.data?.message || 'Nộp bài thất bại')
  } finally {
    submitting.value = false
  }
}
</script>
