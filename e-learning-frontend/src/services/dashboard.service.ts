import type { AxiosResponse } from 'axios'
import http from '@/plugins/axios'
import type { ApiResponse } from '@/types'

export interface DashboardStats {
  summary: {
    total_students: number
    total_courses: number
    total_orders: number
    total_revenue: number
  }
  monthly_revenue: {
    month: number
    revenue: number
  }[]
  top_courses: {
    id: number
    title: string
    thumbnail: string | null
    price: number
    sales_count: number
    revenue: number
  }[]
  recent_orders: {
    id: number
    order_code: string
    student_name: string
    student_email: string
    course_title: string
    amount: number
    status: string
    created_at: string
  }[]
}

export const dashboardService = {
  /** GET /admin/dashboard/stats — Lấy thống kê tổng quan cho Admin Dashboard */
  getAdminStats: (): Promise<AxiosResponse<ApiResponse<DashboardStats>>> =>
    http.get('/admin/dashboard/stats'),
}
