<template>
  <div
    class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800"
  >
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Chi tiết từng câu</h3>
    <div class="space-y-4">
      <div v-for="(q, i) in questions" :key="q.id">
        <div class="flex items-start gap-2 mb-2">
          <span
            :class="isCorrect(q.id) ? 'bg-green-500' : 'bg-red-500'"
            class="flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center text-white text-xs font-bold mt-0.5"
          >
            {{ isCorrect(q.id) ? '✓' : '✗' }}
          </span>
          <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
            {{ i + 1 }}. {{ q.question }}
          </p>
        </div>
        <div class="space-y-1.5 pl-7">
          <div
            v-for="opt in (['A', 'B', 'C', 'D'] as const)"
            :key="opt"
            :class="optionClass(q.id, opt)"
            class="flex items-center gap-2.5 px-3 py-2 rounded-xl border text-xs"
          >
            <span
              :class="badgeClass(q.id, opt)"
              class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
            >
              {{ opt }}
            </span>
            <span>{{ q[`option_${opt.toLowerCase()}` as keyof typeof q] }}</span>
            <span
              v-if="getLabel(q.id, opt) === '✓ Đúng'"
              class="ml-auto text-green-600 dark:text-green-400 font-medium"
              >✓ Đúng</span
            >
            <span
              v-else-if="getLabel(q.id, opt) === '✗ Bạn chọn'"
              class="ml-auto text-red-500 font-medium"
              >✗ Bạn chọn</span
            >
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  isQuestionCorrect,
  optionResultClass,
  optionBadgeClass,
  optionLabel,
} from '@/utils/quizResult'
import type { Option } from '@/utils/quizResult'
import type { QuizQuestion, QuizAttempt } from '@/services/quiz.service'

const props = defineProps<{
  questions: QuizQuestion[]
  attempt: QuizAttempt
}>()

function isCorrect(questionId: number) {
  return isQuestionCorrect(props.attempt.correct_answers, props.attempt.answers, questionId)
}

function optionClass(questionId: number, opt: Option) {
  return optionResultClass(
    props.attempt.correct_answers,
    props.attempt.answers,
    questionId,
    opt,
    'dark',
  )
}

function badgeClass(questionId: number, opt: Option) {
  return optionBadgeClass(props.attempt.correct_answers, props.attempt.answers, questionId, opt)
}

function getLabel(questionId: number, opt: Option) {
  return optionLabel(props.attempt.correct_answers, props.attempt.answers, questionId, opt)
}
</script>
