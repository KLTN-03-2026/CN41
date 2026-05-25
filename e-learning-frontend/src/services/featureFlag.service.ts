import http from '@/plugins/axios'
import type { AxiosResponse } from 'axios'
import type { ApiResponse } from '@/types'

export interface FeatureFlag {
  key: string
  label: string
  description: string
  active: boolean
}

export const featureFlagService = {
  /** GET /admin/feature-flags */
  index: (): Promise<AxiosResponse<ApiResponse<FeatureFlag[]>>> =>
    http.get('/admin/feature-flags'),

  /** PATCH /admin/feature-flags/{flag} */
  update: (flag: string, active: boolean): Promise<AxiosResponse<ApiResponse<FeatureFlag>>> =>
    http.patch(`/admin/feature-flags/${flag}`, { active }),
}
