import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse, PaginatedResponse } from '@/types'

export interface ActivityLog {
  id: number
  log_name: string
  description: string
  subject_type: string
  subject_id: number
  causer_name: string
  properties: Record<string, unknown>
  created_at: string
  human_time: string
}

export const systemService = {
  /** GET /admin/system/logs */
  getLogs: (
    params: Record<string, unknown> = {},
  ): Promise<AxiosResponse<PaginatedResponse<ActivityLog>>> =>
    http.get('/admin/system/logs', { params }),

  /** DELETE /admin/system/logs/clear */
  clearLogs: (): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete('/admin/system/logs/clear'),
}
