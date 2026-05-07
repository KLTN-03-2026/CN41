import { describe, it, expect } from 'vitest'
import {
  getCorrectAnswer,
  getStudentAnswer,
  isQuestionCorrect,
  optionResultClass,
  optionBadgeClass,
  optionLabel,
} from './quizResult'

// Fixture: quiz 3 câu
// Q1 (id=1): đúng=A, học viên chọn A → đúng
// Q2 (id=2): đúng=B, học viên chọn A → sai
// Q3 (id=3): đúng=C, học viên không chọn → bỏ trống
const correctAnswers = { '1': 'A', '2': 'B', '3': 'C' }
const studentAnswers = { '1': 'A', '2': 'A' } // Q3 bỏ trống

describe('getCorrectAnswer', () => {
  it('trả về đáp án đúng theo question id', () => {
    expect(getCorrectAnswer(correctAnswers, 1)).toBe('A')
    expect(getCorrectAnswer(correctAnswers, 2)).toBe('B')
  })

  it('trả về undefined khi correct_answers là null', () => {
    expect(getCorrectAnswer(null, 1)).toBeUndefined()
  })

  it('trả về undefined khi question_id không có trong map', () => {
    expect(getCorrectAnswer(correctAnswers, 99)).toBeUndefined()
  })
})

describe('getStudentAnswer', () => {
  it('trả về đáp án học viên chọn', () => {
    expect(getStudentAnswer(studentAnswers, 1)).toBe('A')
    expect(getStudentAnswer(studentAnswers, 2)).toBe('A')
  })

  it('trả về undefined khi câu bỏ trống', () => {
    expect(getStudentAnswer(studentAnswers, 3)).toBeUndefined()
  })
})

describe('isQuestionCorrect', () => {
  it('Q1: chọn A đúng = A → đúng', () => {
    expect(isQuestionCorrect(correctAnswers, studentAnswers, 1)).toBe(true)
  })

  it('Q2: chọn A sai (đúng là B) → sai', () => {
    expect(isQuestionCorrect(correctAnswers, studentAnswers, 2)).toBe(false)
  })

  it('Q3: bỏ trống → sai', () => {
    expect(isQuestionCorrect(correctAnswers, studentAnswers, 3)).toBe(false)
  })

  it('correct_answers null → luôn false', () => {
    expect(isQuestionCorrect(null, studentAnswers, 1)).toBe(false)
  })
})

describe('optionResultClass — Q1 chọn đúng (A=A)', () => {
  it('A (đúng, học viên chọn) → xanh', () => {
    const cls = optionResultClass(correctAnswers, studentAnswers, 1, 'A')
    expect(cls).toContain('green')
    expect(cls).not.toContain('red')
  })

  it('B/C/D (không chọn, không đúng) → xám', () => {
    expect(optionResultClass(correctAnswers, studentAnswers, 1, 'B')).toContain('gray')
    expect(optionResultClass(correctAnswers, studentAnswers, 1, 'C')).toContain('gray')
  })
})

describe('optionResultClass — Q2 chọn sai (chọn A, đúng là B)', () => {
  it('A (học viên chọn, sai) → đỏ', () => {
    const cls = optionResultClass(correctAnswers, studentAnswers, 2, 'A')
    expect(cls).toContain('red')
    expect(cls).not.toContain('green')
  })

  it('B (đáp án đúng, không chọn) → xanh', () => {
    const cls = optionResultClass(correctAnswers, studentAnswers, 2, 'B')
    expect(cls).toContain('green')
    expect(cls).not.toContain('red')
  })

  it('C/D (không liên quan) → xám', () => {
    expect(optionResultClass(correctAnswers, studentAnswers, 2, 'C')).toContain('gray')
    expect(optionResultClass(correctAnswers, studentAnswers, 2, 'D')).toContain('gray')
  })
})

describe('optionBadgeClass', () => {
  it('Q1-A (đúng): bg-green-500', () => {
    expect(optionBadgeClass(correctAnswers, studentAnswers, 1, 'A')).toBe('bg-green-500 text-white')
  })

  it('Q2-A (chọn sai): bg-red-500', () => {
    expect(optionBadgeClass(correctAnswers, studentAnswers, 2, 'A')).toBe('bg-red-500 text-white')
  })

  it('Q2-B (đúng nhưng không chọn): bg-green-500', () => {
    expect(optionBadgeClass(correctAnswers, studentAnswers, 2, 'B')).toBe('bg-green-500 text-white')
  })

  it('Q2-C (không liên quan): xám', () => {
    expect(optionBadgeClass(correctAnswers, studentAnswers, 2, 'C')).toContain('gray')
  })
})

describe('optionLabel', () => {
  it('Q1-A (đúng): "✓ Đúng"', () => {
    expect(optionLabel(correctAnswers, studentAnswers, 1, 'A')).toBe('✓ Đúng')
  })

  it('Q2-B (đáp án đúng, không chọn): "✓ Đúng"', () => {
    expect(optionLabel(correctAnswers, studentAnswers, 2, 'B')).toBe('✓ Đúng')
  })

  it('Q2-A (chọn sai): "✗ Bạn chọn"', () => {
    expect(optionLabel(correctAnswers, studentAnswers, 2, 'A')).toBe('✗ Bạn chọn')
  })

  it('Q1-B (không chọn, không đúng): null', () => {
    expect(optionLabel(correctAnswers, studentAnswers, 1, 'B')).toBeNull()
  })

  it('Q2-C (không liên quan): null', () => {
    expect(optionLabel(correctAnswers, studentAnswers, 2, 'C')).toBeNull()
  })

  it('correct_answers null: option học viên chọn vẫn hiện "✗ Bạn chọn" (không thể xác định đúng/sai)', () => {
    // correct_answers null → không biết đáp án đúng → không hiện "✓ Đúng"
    // nhưng "✗ Bạn chọn" vẫn hiện vì đây là option học viên đã chọn
    expect(optionLabel(null, studentAnswers, 1, 'A')).toBe('✗ Bạn chọn')
    // Option không chọn → null
    expect(optionLabel(null, studentAnswers, 1, 'B')).toBeNull()
  })
})

describe('Edge cases', () => {
  it('correct_answers key integer trong JSON vẫn match được', () => {
    // PHP trả về {1: "A"} — JS parse thành {"1": "A"}, String(1) = "1" → match
    const ca = { 1: 'A' } as unknown as Record<string, string>
    expect(getCorrectAnswer(ca, 1)).toBe('A')
  })

  it('không hiển thị đỏ khi học viên chọn đúng dù key numeric', () => {
    const ca = { '5': 'C' }
    const sa = { '5': 'C' } // chọn đúng
    expect(optionResultClass(ca, sa, 5, 'C')).toContain('green')
    expect(optionResultClass(ca, sa, 5, 'C')).not.toContain('red')
    expect(optionBadgeClass(ca, sa, 5, 'C')).toBe('bg-green-500 text-white')
    expect(optionLabel(ca, sa, 5, 'C')).toBe('✓ Đúng')
  })

  it('100% đúng: không có option nào đỏ', () => {
    const ca = { '1': 'A', '2': 'B', '3': 'C', '4': 'D' }
    const sa = { '1': 'A', '2': 'B', '3': 'C', '4': 'D' }
    for (const qId of [1, 2, 3, 4]) {
      for (const opt of ['A', 'B', 'C', 'D'] as const) {
        expect(optionResultClass(ca, sa, qId, opt)).not.toContain('red')
        expect(optionBadgeClass(ca, sa, qId, opt)).not.toContain('red')
      }
    }
  })
})
