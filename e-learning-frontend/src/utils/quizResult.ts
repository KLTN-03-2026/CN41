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
  theme: 'light' | 'dark' = 'light',
): string {
  const correct = getCorrectAnswer(correctAnswers, questionId)
  const chosen = getStudentAnswer(answers, questionId)

  if (opt === correct) {
    return theme === 'dark'
      ? 'border-green-400 bg-green-900/20 text-green-300'
      : 'border-green-400 bg-green-50 text-green-800'
  }
  if (opt === chosen && chosen !== correct) {
    return theme === 'dark'
      ? 'border-red-400 bg-red-900/20 text-red-300'
      : 'border-red-400 bg-red-50 text-red-800'
  }
  return theme === 'dark' ? 'border-gray-700 text-gray-400' : 'border-gray-100 text-gray-500'
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
  return 'bg-gray-100 text-gray-400'
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
