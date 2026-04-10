import { ref, reactive } from 'vue'
import { useToast } from 'vue-toastification'
import { courseService } from '@/services/course.service'
import { usePagination } from '@/composables/usePagination'
import { useDebounceSearch } from '@/composables/useDebounceSearch'
import { useDeleteConfirm } from '@/composables/useDeleteConfirm'
import { useBulkSelect } from '@/composables/useBulkSelect'
import type { AdminCourse } from '@/types/admin-category.types'

export function useCourses() {
  const toast = useToast()

  // ── Tab ────────────────────────────────────────────────────────
  const isTrashed = ref(false)

  // ── Active ─────────────────────────────────────────────────────
  const courses    = ref<AdminCourse[]>([])
  const loading    = ref(true)
  const togglingId = ref<number | null>(null)
  const filters    = reactive({ search: '', status: '', level: '' })

  async function loadActivePage(page = 1) {
    loading.value = true
    try {
      const params: Record<string, any> = { page, per_page: 15 }
      if (filters.search) params.search = filters.search
      if (filters.status !== '') params.status = filters.status
      if (filters.level) params.level = filters.level

      const res = await courseService.index(params)
      courses.value = res.data.data
      activeUpdatePagination(res.data.pagination)
    } catch {
      toast.error('Không thể tải khóa học')
    } finally {
      loading.value = false
    }
  }

  const {
    pagination: activePagination,
    currentPage: activeCurrentPage,
    setPage: activeSetPage,
    updatePagination: activeUpdatePagination,
  } = usePagination(loadActivePage, 15)

  const { debounce: debouncedFetch } = useDebounceSearch(() => activeSetPage(1))

  // ── Trashed ────────────────────────────────────────────────────
  const trashedCourses  = ref<AdminCourse[]>([])
  const trashedLoading  = ref(false)
  const trashedCount    = ref(0)
  const restoringId     = ref<number | null>(null)
  const trashedFilters  = reactive({ search: '' })

  async function loadTrashedPage(page = 1) {
    trashedLoading.value = true
    try {
      const params: Record<string, any> = { page, per_page: 15 }
      if (trashedFilters.search) params.search = trashedFilters.search

      const res = await courseService.trashed(params)
      trashedCourses.value = res.data.data
      trashedUpdatePagination(res.data.pagination)
      trashedCount.value = res.data.pagination?.total || res.data.data?.length || 0
    } catch {
      toast.error('Không thể tải thùng rác')
    } finally {
      trashedLoading.value = false
    }
  }

  const {
    pagination: trashedPagination,
    currentPage: trashedCurrentPage,
    setPage: trashedSetPage,
    updatePagination: trashedUpdatePagination,
  } = usePagination(loadTrashedPage, 15)

  const { debounce: debouncedFetchTrashed } = useDebounceSearch(() => trashedSetPage(1))

  async function fetchTrashedCount() {
    try {
      const res = await courseService.trashed({ per_page: 1 })
      trashedCount.value = res.data.pagination?.total || res.data.data?.length || 0
    } catch {}
  }

  // ── Tab switch ─────────────────────────────────────────────────
  function switchTab(trashed: boolean) {
    if (isTrashed.value === trashed) return
    isTrashed.value = trashed
    if (trashed) loadTrashedPage(1)
  }

  // ── Bulk select (active) ───────────────────────────────────────
  const {
    selectedIds,
    isAllSelected,
    isIndeterminate,
    toggleSelectAll,
    toggleSelect,
    clear: clearSelection,
  } = useBulkSelect({ items: () => courses.value })

  const bulkDeleting = ref(false)
  const bulkUpdating = ref(false)

  // ── Bulk select (trashed) ──────────────────────────────────────
  const {
    selectedIds: trashedSelectedIds,
    isAllSelected: isTrashedAllSelected,
    isIndeterminate: isTrashedIndeterminate,
    toggleSelectAll: toggleTrashedSelectAll,
    toggleSelect: toggleTrashedSelect,
    clear: clearTrashedSelection,
  } = useBulkSelect({ items: () => trashedCourses.value })

  const bulkForceDeleting = ref(false)
  const bulkRestoring     = ref(false)
  const bulkActionsRef    = ref<any>(null)

  // ── Toggle status ──────────────────────────────────────────────
  async function toggleStatus(course: AdminCourse) {
    togglingId.value = course.id
    try {
      await courseService.toggleStatus(course.id)
      course.status = course.status === 1 ? 0 : 1
      toast.success(`Đã ${course.status === 1 ? 'đăng' : 'chuyển về nháp'} khóa học`)
    } catch {
      toast.error('Không thể cập nhật trạng thái')
    } finally {
      togglingId.value = null
    }
  }

  // ── Delete confirms ────────────────────────────────────────────
  const softDelete = useDeleteConfirm({
    async onConfirm(course: AdminCourse) {
      await courseService.destroy(course.id)
      toast.success('Xóa khóa học thành công')
      loadActivePage(activeCurrentPage.value)
      fetchTrashedCount()
    },
  })

  const forceDelete = useDeleteConfirm({
    async onConfirm(course: AdminCourse) {
      await courseService.forceDelete(course.id)
      toast.success('Đã xóa vĩnh viễn khóa học')
      loadTrashedPage(trashedCurrentPage.value)
      fetchTrashedCount()
    },
  })

  // ── Restore single ─────────────────────────────────────────────
  async function doRestore(course: AdminCourse) {
    restoringId.value = course.id
    try {
      await courseService.restore(course.id)
      toast.success(`Đã khôi phục "${course.name}"`)
      loadTrashedPage(trashedCurrentPage.value)
      fetchTrashedCount()
      loadActivePage(activeCurrentPage.value)
    } catch (err: any) {
      toast.error(err.response?.data?.message || 'Khôi phục thất bại')
    } finally {
      restoringId.value = null
    }
  }

  // ── Bulk: active ───────────────────────────────────────────────
  async function doBulkDelete() {
    bulkDeleting.value = true
    try {
      await courseService.bulkDelete([...selectedIds])
      toast.success(`Đã xóa ${selectedIds.size} khóa học`)
      clearSelection()
      bulkActionsRef.value?.closeModal()
      loadActivePage(activeCurrentPage.value)
      fetchTrashedCount()
    } catch (err: any) {
      toast.error(err.response?.data?.message || 'Xóa nhiều thất bại')
    } finally {
      bulkDeleting.value = false
    }
  }

  async function bulkToggleStatus(status: number) {
    bulkUpdating.value = true
    try {
      const ids = [...selectedIds]
      await Promise.all(ids.map(id => courseService.update(id, { status })))
      toast.success(`Đã cập nhật ${ids.length} khóa học`)
      clearSelection()
      bulkActionsRef.value?.closeModal()
      loadActivePage(activeCurrentPage.value)
    } catch {
      toast.error('Cập nhật trạng thái thất bại')
    } finally {
      bulkUpdating.value = false
    }
  }

  // ── Bulk: trashed ──────────────────────────────────────────────
  async function doBulkRestore() {
    bulkRestoring.value = true
    try {
      await courseService.bulkRestore([...trashedSelectedIds])
      toast.success(`Đã khôi phục ${trashedSelectedIds.size} khóa học`)
      clearTrashedSelection()
      bulkActionsRef.value?.closeModal()
      loadTrashedPage(trashedCurrentPage.value)
      fetchTrashedCount()
      loadActivePage(activeCurrentPage.value)
    } catch (err: any) {
      toast.error(err.response?.data?.message || 'Khôi phục nhiều thất bại')
    } finally {
      bulkRestoring.value = false
    }
  }

  async function doBulkForceDelete() {
    bulkForceDeleting.value = true
    try {
      await courseService.bulkForceDelete([...trashedSelectedIds])
      toast.success(`Đã xóa vĩnh viễn ${trashedSelectedIds.size} khóa học`)
      clearTrashedSelection()
      bulkActionsRef.value?.closeModal()
      loadTrashedPage(trashedCurrentPage.value)
      fetchTrashedCount()
    } catch (err: any) {
      toast.error(err.response?.data?.message || 'Xóa vĩnh viễn nhiều thất bại')
    } finally {
      bulkForceDeleting.value = false
    }
  }

  return {
    // tab
    isTrashed,
    switchTab,
    // active
    courses,
    loading,
    togglingId,
    filters,
    loadActivePage,
    activePagination,
    activeCurrentPage,
    activeSetPage,
    debouncedFetch,
    toggleStatus,
    // bulk active
    selectedIds,
    isAllSelected,
    isIndeterminate,
    toggleSelectAll,
    toggleSelect,
    clearSelection,
    bulkDeleting,
    bulkUpdating,
    doBulkDelete,
    bulkToggleStatus,
    // trashed
    trashedCourses,
    trashedLoading,
    trashedCount,
    restoringId,
    trashedFilters,
    loadTrashedPage,
    trashedPagination,
    trashedCurrentPage,
    trashedSetPage,
    debouncedFetchTrashed,
    fetchTrashedCount,
    doRestore,
    // bulk trashed
    trashedSelectedIds,
    isTrashedAllSelected,
    isTrashedIndeterminate,
    toggleTrashedSelectAll,
    toggleTrashedSelect,
    clearTrashedSelection,
    bulkForceDeleting,
    bulkRestoring,
    doBulkRestore,
    doBulkForceDelete,
    bulkActionsRef,
    // delete confirms
    softDelete,
    forceDelete,
  }
}
