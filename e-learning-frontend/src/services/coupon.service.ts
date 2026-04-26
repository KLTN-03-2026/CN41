import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse, PaginatedResponse } from '@/types'

export interface Coupon {
  id: number
  code: string
  type: 'fixed' | 'percentage'
  value: string | number
  min_order_value: string | number | null
  max_discount: string | number | null
  usage_limit: number | null
  used_count: number
  remaining: number | null
  start_date: string | null
  end_date: string | null
  status: number
  is_expired: boolean
  is_valid: boolean
  description: string | null
  created_at: string
  updated_at: string
}

export interface CouponValidation {
  valid: boolean
  code: string
  type: string
  value: number
  discount_amount: number
  new_total: number
  message: string
}

export const couponService = {
  // ── Admin ──────────────────────────────────────────────────
  /** GET /admin/coupons */
  index: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Coupon>>> =>
    http.get('/admin/coupons', { params }),

  /** POST /admin/coupons */
  store: (data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Coupon>>> =>
    http.post('/admin/coupons', data),

  /** GET /admin/coupons/{id} */
  show: (id: number): Promise<AxiosResponse<ApiResponse<Coupon>>> =>
    http.get(`/admin/coupons/${id}`),

  /** PUT /admin/coupons/{id} */
  update: (id: number, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Coupon>>> =>
    http.put(`/admin/coupons/${id}`, data),

  /** DELETE /admin/coupons/{id} (soft delete) */
  destroy: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/coupons/${id}`),

  /** PATCH /admin/coupons/{id}/toggle-status */
  toggleStatus: (id: number): Promise<AxiosResponse<ApiResponse<Coupon>>> =>
    http.patch(`/admin/coupons/${id}/toggle-status`),

  /** GET /admin/coupons/trashed */
  trashed: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Coupon>>> =>
    http.get('/admin/coupons/trashed', { params }),

  /** POST /admin/coupons/{id}/restore */
  restore: (id: number): Promise<AxiosResponse<ApiResponse<Coupon>>> =>
    http.post(`/admin/coupons/${id}/restore`),

  /** DELETE /admin/coupons/{id}/force-delete */
  forceDelete: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/coupons/${id}/force-delete`),

  /** DELETE /admin/coupons/bulk-delete */
  bulkDelete: (ids: number[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete('/admin/coupons/bulk-delete', { data: { ids } }),

  /** POST /admin/coupons/bulk-restore */
  bulkRestore: (ids: number[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.post('/admin/coupons/bulk-restore', { ids }),

  // ── Public (Student) ──────────────────────────────────────
  /** POST /coupons/validate */
  validate: (data: { code: string; subtotal: number }): Promise<AxiosResponse<ApiResponse<CouponValidation>>> =>
    http.post('/coupons/validate', data),
}
