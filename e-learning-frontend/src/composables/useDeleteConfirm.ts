import { ref, computed, type Ref } from 'vue'

interface UseDeleteConfirmOptions<T> {
  onConfirm: (item: T) => Promise<void>
}

export function useDeleteConfirm<T = unknown>(options: UseDeleteConfirmOptions<T>) {
  const target = ref<T | null>(null) as Ref<T | null>
  const isOpen = computed(() => target.value !== null)
  const loading = ref(false)

  function confirm(item: T) {
    target.value = item
  }

  function cancel() {
    target.value = null
  }

  async function execute() {
    if (!target.value) return
    loading.value = true
    try {
      await options.onConfirm(target.value)
      target.value = null
    } finally {
      loading.value = false
    }
  }

  return { target, isOpen, loading, confirm, cancel, execute }
}
