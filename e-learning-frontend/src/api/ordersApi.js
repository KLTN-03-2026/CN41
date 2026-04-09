import http from '@/plugins/axios'

export const ordersApi = {
  // ── Student ─────────────────────────────────────────────
  /** POST /orders — Tạo đơn hàng mới → nhận payment_url */
  createOrder: (data) => http.post('/orders', data),

  /** GET /my-orders — Lịch sử đơn hàng (paginated) */
  myOrders: (params = {}) => http.get('/my-orders', { params }),

  /** GET /my-orders/{orderCode} — Chi tiết đơn hàng */
  orderDetail: (orderCode) => http.get(`/my-orders/${orderCode}`),

  /** POST /orders/{orderCode}/retry-payment — Thanh toán lại */
  retryPayment: (orderCode) => http.post(`/orders/${orderCode}/retry-payment`),

  // ── Admin ───────────────────────────────────────────────
  /** GET /admin/orders — Danh sách đơn hàng */
  adminList: (params = {}) => http.get('/admin/orders', { params }),

  /** GET /admin/orders/{id} — Chi tiết đơn hàng */
  adminShow: (id) => http.get(`/admin/orders/${id}`),

  /** PATCH /admin/orders/{id}/status — Cập nhật trạng thái */
  adminUpdateStatus: (id, data) => http.patch(`/admin/orders/${id}/status`, data),

  /** DELETE /admin/orders/{id} — Soft delete */
  adminDelete: (id) => http.delete(`/admin/orders/${id}`),

  /** DELETE /admin/orders/bulk-delete — Bulk soft delete */
  adminBulkDelete: (ids) => http.delete('/admin/orders/bulk-delete', { data: { ids } }),

  /** GET /admin/orders/trashed — Đơn hàng đã xoá */
  adminTrashed: (params = {}) => http.get('/admin/orders/trashed', { params }),

  /** PATCH /admin/orders/{id}/restore — Khôi phục */
  adminRestore: (id) => http.patch(`/admin/orders/${id}/restore`),

  /** GET /admin/orders/stats/revenue — Thống kê doanh thu */
  revenueStats: (params = {}) => http.get('/admin/orders/stats/revenue', { params }),
}
