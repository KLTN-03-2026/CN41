export const PAGINATION = {
  DEFAULT_PER_PAGE: 15,
  MAX_PER_PAGE: 100,
} as const

export const TOAST = {
  TIMEOUT: 3000,
} as const

export const STORAGE_KEYS = {
  ADMIN_TOKEN: 'adminToken',
  STUDENT_TOKEN: 'studentToken',
  CART: 'cart',
  THEME: 'theme',
} as const

export const COURSE_LEVELS = ['beginner', 'intermediate', 'advanced'] as const
export type CourseLevel = typeof COURSE_LEVELS[number]

export const ORDER_STATUS = {
  PENDING: 'pending',
  PAID: 'paid',
  FAILED: 'failed',
  CANCELLED: 'cancelled',
} as const
