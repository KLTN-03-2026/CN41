import { defineStore } from 'pinia'
import type { Course } from '@/types'
import { STORAGE_KEYS } from '@/constants/app'

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: (() => {
      try {
        return JSON.parse(localStorage.getItem(STORAGE_KEYS.CART) || '[]') as Course[]
      } catch {
        return [] as Course[]
      }
    })(),
  }),

  getters: {
    count: (state): number => state.items.length,
    total: (state): number => state.items.reduce((sum, item) => sum + (item.sale_price ?? item.price), 0),
    originalTotal: (state): number => state.items.reduce((sum, item) => sum + item.price, 0),
    courseIds: (state): number[] => state.items.map((i) => i.id),
  },

  actions: {
    addItem(course: Course): void {
      if (!this.items.find((i) => i.id === course.id)) {
        this.items.push(course)
        this._persist()
      }
    },
    removeItem(courseId: number): void {
      this.items = this.items.filter((i) => i.id !== courseId)
      this._persist()
    },
    clear(): void {
      this.items = []
      this._persist()
    },
    _persist(): void {
      localStorage.setItem(STORAGE_KEYS.CART, JSON.stringify(this.items))
    },
  },
})
