import { createEcho, getEcho } from '@/plugins/echo'

export interface QuizJobResult {
  status: 'done' | 'failed'
  quiz_id?: number
  questions?: unknown[]
  error?: string
}

export function useQuizJobChannel() {
  function waitForJob(jobId: number): Promise<QuizJobResult> {
    return new Promise((resolve, reject) => {
      const token = localStorage.getItem('adminToken') ?? ''
      const echo = getEcho() ?? createEcho(token)

      echo.private(`quiz-job.${jobId}`).listen('.QuizGenerationCompleted', (event: QuizJobResult) => {
        echo.leave(`quiz-job.${jobId}`)
        if (event.status === 'done') resolve(event)
        else reject(new Error(event.error ?? 'Sinh câu hỏi thất bại'))
      })

      setTimeout(() => {
        echo.leave(`quiz-job.${jobId}`)
        reject(new Error('Hết thời gian chờ. Vui lòng thử lại.'))
      }, 180_000)
    })
  }

  return { waitForJob }
}
