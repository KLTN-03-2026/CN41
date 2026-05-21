import type { DirectiveBinding } from 'vue'
import { useAdminAuthStore } from '@/stores/adminAuth.store'

function applyPermission(el: HTMLElement, value: unknown) {
  const adminStore = useAdminAuthStore()
  if (value && typeof value === 'string') {
    el.style.display = adminStore.hasPermission(value) ? '' : 'none'
  }
}

export const permissionDirective = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    applyPermission(el, binding.value)
  },
  updated(el: HTMLElement, binding: DirectiveBinding) {
    applyPermission(el, binding.value)
  },
}
