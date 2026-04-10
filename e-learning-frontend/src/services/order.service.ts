import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse, PaginatedResponse, Order } from '@/types'

export const orderService = {
  // ── Student ─────────────────────────────────────────────
  /** POST /orders — Tạo đơn hàng mới → nhận payment_url */
  createOrder: (data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<{ payment_url: string }>>> =>
    http.post('/orders', data),

  /** GET /my-orders — Lịch sử đơn hàng (paginated) */
  myOrders: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Order>>> =>
    http.get('/my-orders', { params }),

  /** GET /my-orders/{orderCode} — Chi tiết đơn hàng */
  orderDetail: (orderCode: string): Promise<AxiosResponse<ApiResponse<Order>>> =>
    http.get(`/my-orders/${orderCode}`),

  /** POST /orders/{orderCode}/retry-payment — Thanh toán lại */
  retryPayment: (orderCode: string): Promise<AxiosResponse<ApiResponse<{ payment_url: string }>>> =>
    http.post(`/orders/${orderCode}/retry-payment`),

  // ── Admin ───────────────────────────────────────────────
  /** GET /admin/orders — Danh sách đơn hàng */
  adminList: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Order>>> =>
    http.get('/admin/orders', { params }),

  /** GET /admin/orders/{id} — Chi tiết đơn hàng */
  adminShow: (id: number): Promise<AxiosResponse<ApiResponse<Order>>> =>
    http.get(`/admin/orders/${id}`),

  /** PATCH /admin/orders/{id}/status — Cập nhật trạng thái */
  adminUpdateStatus: (id: number, data: Record<string, unknown>): Promise<AxiosResponse<ApiResponse<Order>>> =>
    http.patch(`/admin/orders/${id}/status`, data),

  /** DELETE /admin/orders/{id} — Soft delete */
  adminDelete: (id: number): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete(`/admin/orders/${id}`),

  /** DELETE /admin/orders/bulk-delete — Bulk soft delete */
  adminBulkDelete: (ids: number[]): Promise<AxiosResponse<ApiResponse<null>>> =>
    http.delete('/admin/orders/bulk-delete', { data: { ids } }),

  /** GET /admin/orders/trashed — Đơn hàng đã xoá */
  adminTrashed: (params: Record<string, unknown> = {}): Promise<AxiosResponse<PaginatedResponse<Order>>> =>
    http.get('/admin/orders/trashed', { params }),

  /** PATCH /admin/orders/{id}/restore — Khôi phục */
  adminRestore: (id: number): Promise<AxiosResponse<ApiResponse<Order>>> =>
    http.patch(`/admin/orders/${id}/restore`),

  /** GET /admin/orders/stats/revenue — Thống kê doanh thu */
  revenueStats: (params: Record<string, unknown> = {}): Promise<AxiosResponse<ApiResponse<unknown>>> =>
    http.get('/admin/orders/stats/revenue', { params }),
}
