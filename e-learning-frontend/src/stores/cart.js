import { defineStore } from 'pinia'

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: (() => { try { return JSON.parse(localStorage.getItem('cart') || '[]') } catch { return [] } })(),
  }),

  getters: {
    count:    (state) => state.items.length,
    total:    (state) => state.items.reduce((sum, item) => sum + (item.sale_price ?? item.price), 0),
    originalTotal: (state) => state.items.reduce((sum, item) => sum + item.price, 0),
    courseIds:(state) => state.items.map(i => i.id),
  },

  actions: {
    addItem(course) {
      if (!this.items.find(i => i.id === course.id)) {
        this.items.push(course)
        this._persist()
      }
    },
    removeItem(courseId) {
      this.items = this.items.filter(i => i.id !== courseId)
      this._persist()
    },
    clear() {
      this.items = []
      this._persist()
    },
    _persist() {
      localStorage.setItem('cart', JSON.stringify(this.items))
    },
  }
})
