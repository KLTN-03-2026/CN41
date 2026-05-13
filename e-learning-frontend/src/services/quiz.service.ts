import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse, PaginatedResponse } from '@/types'

export interface Quiz {
  id: number
  lesson_id: number
  title: string
  description: string | null
  max_attempts: number
  time_limit: number | null
  status: number
  questions_count: number
  created_at: string
  updated_at: string
}

export interface QuizQuestion {
  id: number
  quiz_id: number
  question: string
  option_a: string
  option_b: string
  option_c: string
  option_d: string
  correct_option?: 'A' | 'B' | 'C' | 'D'
  order: number
}

export interface QuizAttemptQuestion {
  id: number
  question: string
  option_a: string
  option_b: string
  option_c: string
  option_d: string
  order: number
}

export interface QuizAttempt {
  id: number
  quiz_id: number
  student_id: number
  score: number
  total_questions: number
  percentage: number
  answers: Record<string, string>
  correct_answers: Record<string, string> | null
  questions: QuizAttemptQuestion[] | null
  completed_at: string
  created_at: string
}

export interface QuizDetail {
  quiz: Quiz
  questions: QuizQuestion[]
}

export interface ChapterPdf {
  id: number
  name: string
  url: string
}

export interface GenerateJobStatus {
  status: 'pending' | 'processing' | 'done' | 'failed'
  questions: QuizQuestion[]
  error_message?: string
}

export const quizService = {
  // ── Admin ──────────────────────────────────────────────────
  index: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Quiz>>> =>
    http.get('/admin/quizzes', { params }),

  store: (data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Quiz>>> =>
    http.post('/admin/quizzes', data),

  show: (id: number): Promise<AxiosResponse<ApiResponse<Quiz>>> => http.get(`/admin/quizzes/${id}`),

  update: (id: number, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Quiz>>> =>
    http.patch(`/admin/quizzes/${id}`, data),

  destroy: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/quizzes/${id}`),

  toggleStatus: (id: number): Promise<AxiosResponse<ApiResponse<Quiz>>> =>
    http.patch(`/admin/quizzes/${id}/toggle-status`),

  generate: (
    id: number,
    data: { count?: number; custom_prompt?: string },
  ): Promise<AxiosResponse<ApiResponse<QuizQuestion[]>>> =>
    http.post(`/admin/quizzes/${id}/generate`, data),

  // ── Student ────────────────────────────────────────────────
  getByLesson: (lessonId: number): Promise<AxiosResponse<ApiResponse<QuizDetail>>> =>
    http.get(`/lessons/${lessonId}/quiz`),

  submit: (
    id: number,
    answers: Record<string, string>,
  ): Promise<AxiosResponse<ApiResponse<QuizAttempt>>> =>
    http.post(`/quizzes/${id}/submit`, { answers }),

  attempts: (id: number): Promise<AxiosResponse<ApiResponse<QuizAttempt[]>>> =>
    http.get(`/quizzes/${id}/attempts`),

  // ── Admin lesson-quiz ──────────────────────────────────────
  lessonQuizGet: (lessonId: number): Promise<AxiosResponse<ApiResponse<{ questions: QuizQuestion[] } | null>>> =>
    http.get(`/admin/lesson-quiz/${lessonId}`),

  lessonQuizChapterPdfs: (lessonId: number): Promise<AxiosResponse<ApiResponse<ChapterPdf[]>>> =>
    http.get(`/admin/lesson-quiz/${lessonId}/chapter-pdfs`),

  lessonQuizGenerate: (
    lessonId: number,
    data: FormData,
  ): Promise<AxiosResponse<ApiResponse<{ job_id: number }>>> =>
    http.post(`/admin/lesson-quiz/${lessonId}/generate`, data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  lessonQuizJobStatus: (jobId: number): Promise<AxiosResponse<ApiResponse<GenerateJobStatus>>> =>
    http.get(`/admin/lesson-quiz/jobs/${jobId}`),

  updateQuestion: (
    id: number,
    data: Partial<Omit<QuizQuestion, 'id' | 'quiz_id' | 'order'>>,
  ): Promise<AxiosResponse<ApiResponse<QuizQuestion>>> =>
    http.patch(`/admin/quiz-questions/${id}`, data),

  destroyQuestion: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/quiz-questions/${id}`),
}
