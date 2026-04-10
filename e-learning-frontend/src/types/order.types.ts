

export interface Order {
  id: number
  order_code: string
  student_id: number
  total_amount: number
  subtotal: number
  discount_amount: number
  status: string
  payment_method?: string
  paid_at?: string
  created_at: string
  student?: {
    name: string
    email: string
  }
  items?: OrderItem[]
  transactions?: Transaction[]
}

export interface OrderItem {
  id: number
  order_id: number
  course_id: number
  price: number
  sale_price?: number
  final_price: number
  course?: {
    name: string
    thumbnail?: string
  }
}

export interface Transaction {
  id: number
  order_id: number
  transaction_code?: string
  amount: number
  gateway: string
  status: string
  created_at: string
}
