import { reactive, computed, type ComputedRef } from 'vue'

interface UseBulkSelectOptions<T extends { id: number }> {
  /** Reactive getter for the current list of items (active or visible) */
  items: () => T[]
}

export function useBulkSelect<T extends { id: number }>(options: UseBulkSelectOptions<T>) {
  const selectedIds = reactive(new Set<number>())

  const isAllSelected: ComputedRef<boolean> = computed(() => {
    const list = options.items()
    return list.length > 0 && list.every(item => selectedIds.has(item.id))
  })

  const isIndeterminate: ComputedRef<boolean> = computed(() => {
    return selectedIds.size > 0 && !isAllSelected.value
  })

  function toggleSelectAll() {
    const list = options.items()
    if (isAllSelected.value) {
      list.forEach(item => selectedIds.delete(item.id))
    } else {
      list.forEach(item => selectedIds.add(item.id))
    }
  }

  function toggleSelect(id: number) {
    if (selectedIds.has(id)) {
      selectedIds.delete(id)
    } else {
      selectedIds.add(id)
    }
  }

  function clear() {
    selectedIds.clear()
  }

  return {
    selectedIds,
    isAllSelected,
    isIndeterminate,
    toggleSelectAll,
    toggleSelect,
    clear,
  }
}
