import type { Course } from './course.types'

export interface Order {
  id: number
  student_id: number
  total_amount: number
  status: string
  payment_method?: string
  created_at: string
}

export interface OrderItem {
  id: number
  order_id: number
  course_id: number
  price: number
  course?: Course
}
