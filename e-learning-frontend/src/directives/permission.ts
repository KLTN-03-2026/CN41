import type { DirectiveBinding } from 'vue'
import { useAdminAuthStore } from '@/stores/adminAuth.store'

export const permissionDirective = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    const { value } = binding
    const adminStore = useAdminAuthStore()

    if (value && typeof value === 'string') {
      const hasPermission = adminStore.hasPermission(value)

      if (!hasPermission) {
        el.parentNode?.removeChild(el)
      }
    }
  },
  updated(el: HTMLElement, binding: DirectiveBinding) {
    const { value } = binding
    const adminStore = useAdminAuthStore()

    if (value && typeof value === 'string') {
      const hasPermission = adminStore.hasPermission(value)

      if (!hasPermission) {
        el.style.display = 'none'
      } else {
        el.style.display = ''
      }
    }
  },
}
