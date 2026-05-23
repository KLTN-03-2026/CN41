/**
 * Helpers để hiển thị kết quả quiz — tách ra để dễ test.
 *
 * Quy tắc màu:
 *  - Option là đáp án đúng                       → xanh (bất kể học viên có chọn hay không)
 *  - Option học viên chọn nhưng SAI (≠ đáp án đúng) → đỏ
 *  - Các option còn lại                           → xám/trắng
 */

export type Option = 'A' | 'B' | 'C' | 'D'

export function getCorrectAnswer(
  correctAnswers: Record<string, string> | null | undefined,
  questionId: number,
): string | undefined {
  if (!correctAnswers) return undefined
  return correctAnswers[String(questionId)]
}

export function getStudentAnswer(
  answers: Record<string, string> | null | undefined,
  questionId: number,
): string | undefined {
  if (!answers) return undefined
  return answers[String(questionId)]
}

export function isQuestionCorrect(
  correctAnswers: Record<string, string> | null | undefined,
  answers: Record<string, string> | null | undefined,
  questionId: number,
): boolean {
  const correct = getCorrectAnswer(correctAnswers, questionId)
  const chosen = getStudentAnswer(answers, questionId)
  return !!correct && !!chosen && correct === chosen
}

export function optionResultClass(
  correctAnswers: Record<string, string> | null | undefined,
  answers: Record<string, string> | null | undefined,
  questionId: number,
  opt: Option,
): string {
  const correct = getCorrectAnswer(correctAnswers, questionId)
  const chosen = getStudentAnswer(answers, questionId)

  if (opt === correct) {
    return 'border-green-400 bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 font-medium'
  }
  if (opt === chosen && chosen !== correct) {
    return 'border-red-400 bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 font-medium'
  }
  return 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-400'
}

export function optionBadgeClass(
  correctAnswers: Record<string, string> | null | undefined,
  answers: Record<string, string> | null | undefined,
  questionId: number,
  opt: Option,
): string {
  const correct = getCorrectAnswer(correctAnswers, questionId)
  const chosen = getStudentAnswer(answers, questionId)

  if (opt === correct) return 'bg-green-500 text-white'
  if (opt === chosen && chosen !== correct) return 'bg-red-500 text-white'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'
}

/** Label nhỏ bên phải mỗi option */
export function optionLabel(
  correctAnswers: Record<string, string> | null | undefined,
  answers: Record<string, string> | null | undefined,
  questionId: number,
  opt: Option,
): '✓ Đúng' | '✗ Bạn chọn' | null {
  const correct = getCorrectAnswer(correctAnswers, questionId)
  const chosen = getStudentAnswer(answers, questionId)

  if (opt === correct) return '✓ Đúng'
  if (opt === chosen && chosen !== correct) return '✗ Bạn chọn'
  return null
}
