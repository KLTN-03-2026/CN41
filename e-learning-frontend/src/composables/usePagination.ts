import { ref } from 'vue'
import type { Pagination } from '@/types/common.types'

export function usePagination(fetchFn: (page: number) => Promise<void>, initialPerPage = 12) {
  const currentPage = ref(1)
  const lastPage    = ref(1)
  const perPage     = ref(initialPerPage)
  const pagination  = ref<Pagination | null>(null)

  async function setPage(page: number) {
    currentPage.value = page
    await fetchFn(page)
  }

  function updatePagination(paginationData: Pagination | undefined) {
    if (!paginationData) return
    pagination.value      = paginationData
    currentPage.value     = paginationData.current_page
    lastPage.value        = paginationData.last_page
  }

  return { currentPage, lastPage, perPage, pagination, setPage, updatePagination }
}
