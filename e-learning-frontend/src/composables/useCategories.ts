import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { categoryService } from '@/services/category.service'
import { useDeleteConfirm } from '@/composables/useDeleteConfirm'
import { useDebounceSearch } from '@/composables/useDebounceSearch'
import { useBulkSelect } from '@/composables/useBulkSelect'
import { useFormErrors } from '@/composables/useFormErrors'
import type { AdminCategory } from '@/types/admin-category.types'

export function useCategories() {
  const toast = useToast()

  // ── Tab ────────────────────────────────────────────────────────
  const isTrashed = ref(false)

  // ── Active ─────────────────────────────────────────────────────
  const allCategories = ref<AdminCategory[]>([])
  const flatTree = ref<{ id: number; name: string; depth: number }[]>([])
  const loading = ref(true)

  async function fetchCategories() {
    loading.value = true
    try {
      const res = await categoryService.flatTree()
      allCategories.value = res.data.data
    } catch {
      toast.error('Không thể tải danh mục')
    } finally {
      loading.value = false
    }
  }

  async function fetchFlatTree() {
    try {
      const res = await categoryService.flatTree()
      flatTree.value = res.data.data
    } catch (err) {
      console.error('Failed to fetch flat tree', err)
    }
  }

  // ── Trashed ────────────────────────────────────────────────────
  const trashedCategories = ref<AdminCategory[]>([])
  const trashedPagination = ref<import('@/types/common.types').Pagination | null>(null)
  const trashedLoading = ref(false)
  const trashedCount = ref(0)
  const trashedSearchQuery = ref('')
  const restoringId = ref<number | null>(null)

  const { debounce: debouncedFetchTrashed } = useDebounceSearch(() => fetchTrashedCategories())

  async function fetchTrashedCategories(page = 1) {
    trashedLoading.value = true
    try {
      const params: Record<string, string | number> = { page, per_page: 20 }
      if (trashedSearchQuery.value) params.search = trashedSearchQuery.value

      const res = await categoryService.trashed(params)
      trashedCategories.value = res.data.data
      trashedPagination.value = res.data.pagination
      trashedCount.value = res.data.pagination?.total || res.data.data?.length || 0
    } catch {
      toast.error('Không thể tải thùng rác')
    } finally {
      trashedLoading.value = false
    }
  }

  async function fetchTrashedCount() {
    try {
      const res = await categoryService.trashed({ per_page: 1 })
      trashedCount.value = res.data.pagination?.total || res.data.data?.length || 0
    } catch (err) {
      console.error('Failed to fetch trashed count', err)
    }
  }

  // ── Bulk select (active) ───────────────────────────────────────
  // NOTE: items phải là hàm trả về mảng hiển thị (visibleCategories)
  // Composable nhận getter qua prop, sẽ được bind ở nơi gọi
  const activeItems = ref<AdminCategory[]>([])
  const {
    selectedIds,
    isAllSelected,
    isIndeterminate,
    toggleSelectAll,
    toggleSelect,
    clear: clearSelection,
  } = useBulkSelect({ items: () => activeItems.value })

  // ── Bulk select (trashed) ──────────────────────────────────────
  const {
    selectedIds: trashedSelectedIds,
    isAllSelected: isTrashedAllSelected,
    isIndeterminate: isTrashedIndeterminate,
    toggleSelectAll: toggleTrashedSelectAll,
    toggleSelect: toggleTrashedSelect,
    clear: clearTrashedSelection,
  } = useBulkSelect({ items: () => trashedCategories.value })

  const bulkDeleting = ref(false)
  const bulkUpdating = ref(false)
  const bulkRestoring = ref(false)
  const bulkForceDeleting = ref(false)
  const bulkActionsRef = ref<{ closeModal: () => void } | null>(null)

  // ── Tab switch ─────────────────────────────────────────────────
  function switchTab(trashed: boolean) {
    if (isTrashed.value === trashed) return
    isTrashed.value = trashed
    if (trashed) fetchTrashedCategories()
  }

  // ── Form ───────────────────────────────────────────────────────
  const showModal = ref(false)
  const editingId = ref<number | null>(null)
  const submitting = ref(false)
  const { errors: formErrors, submitError, clearErrors, handleApiError } = useFormErrors()

  const defaultForm = () => ({
    name: '',
    slug: '',
    description: '',
    status: 1,
    parent_id: null as number | null,
  })
  const form = ref(defaultForm())

  function autoSlug() {
    if (editingId.value) return
    form.value.slug = form.value.name
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[đĐ]/g, 'd')
      .toLowerCase()
      .replace(/\+\+/g, '-plus-plus')
      .replace(/\+/g, '-plus')
      .replace(/#/g, '-sharp')
      .replace(/&/g, '-and')
      .replace(/\./g, '-')
      .replace(/[^a-z0-9\s-]/g, '')
      .trim()
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '')
  }

  function openCreate() {
    editingId.value = null
    form.value = defaultForm()
    clearErrors()
    showModal.value = true
  }

  function openEdit(cat: AdminCategory) {
    editingId.value = cat.id
    form.value = {
      name: cat.name,
      slug: cat.slug,
      description: cat.description || '',
      status: cat.status,
      parent_id: cat.parent_id ?? null,
    }
    clearErrors()
    showModal.value = true
  }

  function closeModal() {
    showModal.value = false
  }

  async function submitForm() {
    clearErrors()
    submitting.value = true
    const payload = {
      name: form.value.name,
      slug: form.value.slug,
      description: form.value.description || null,
      status: form.value.status,
      parent_id: form.value.parent_id,
    }
    try {
      if (editingId.value) {
        await categoryService.update(editingId.value, payload)
        toast.success('Cập nhật danh mục thành công')
      } else {
        await categoryService.store(payload)
        toast.success('Tạo danh mục thành công')
      }
      closeModal()
      fetchCategories()
      fetchFlatTree()
    } catch (err: unknown) {
      handleApiError(err)
    } finally {
      submitting.value = false
    }
  }

  // ── Delete confirms ────────────────────────────────────────────
  const softDelete = useDeleteConfirm({
    async onConfirm(cat: AdminCategory) {
      try {
        await categoryService.destroy(cat.id)
        toast.success('Xóa danh mục thành công')
        fetchCategories()
        fetchFlatTree()
        fetchTrashedCount()
      } catch (err: unknown) {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Không thể xóa danh mục.'
        toast.error(message)
      }
    },
  })

  const forceDelete = useDeleteConfirm({
    async onConfirm(cat: AdminCategory) {
      try {
        await categoryService.forceDelete(cat.id)
        toast.success('Đã xóa vĩnh viễn danh mục')
        fetchTrashedCategories()
        fetchTrashedCount()
      } catch (err: unknown) {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Không thể xóa vĩnh viễn danh mục.'
        toast.error(message)
      }
    },
  })

  // ── Restore single ─────────────────────────────────────────────
  async function doRestoreCategory(cat: AdminCategory) {
    restoringId.value = cat.id
    try {
      await categoryService.restore(cat.id)
      toast.success(`Đã khôi phục "${cat.name}"`)
      fetchTrashedCategories()
      fetchTrashedCount()
      fetchCategories()
      fetchFlatTree()
      fetchCategories()
      fetchFlatTree()
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      toast.error(axiosError.response?.data?.message || 'Khôi phục thất bại')
    } finally {
      restoringId.value = null
    }
  }

  // ── Bulk: active ───────────────────────────────────────────────
  async function doBulkDelete() {
    bulkDeleting.value = true
    try {
      const ids = [...selectedIds]
      await Promise.all(ids.map((id) => categoryService.destroy(id)))
      toast.success(`Đã xóa ${ids.length} danh mục`)
      clearSelection()
      bulkActionsRef.value?.closeModal()
      fetchCategories()
      fetchFlatTree()
      fetchTrashedCount()
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      toast.error(axiosError.response?.data?.message || 'Xóa nhiều thất bại')
    } finally {
      bulkDeleting.value = false
    }
  }

  async function bulkToggleStatus(status: number) {
    bulkUpdating.value = true
    try {
      const ids = [...selectedIds]
      await Promise.all(ids.map((id) => categoryService.update(id, { status })))
      toast.success(`Đã cập nhật ${ids.length} danh mục`)
      clearSelection()
      bulkActionsRef.value?.closeModal()
      fetchCategories()
      fetchFlatTree()
    } catch {
      toast.error('Cập nhật trạng thái thất bại')
    } finally {
      bulkUpdating.value = false
    }
  }

  // ── Bulk: trashed ──────────────────────────────────────────────
  async function doBulkRestoreCategories() {
    bulkRestoring.value = true
    try {
      const ids = [...trashedSelectedIds]
      await Promise.all(ids.map((id) => categoryService.restore(id)))
      toast.success(`Đã khôi phục ${ids.length} danh mục`)
      clearTrashedSelection()
      bulkActionsRef.value?.closeModal()
      fetchTrashedCategories()
      fetchTrashedCount()
      fetchCategories()
      fetchFlatTree()
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      toast.error(axiosError.response?.data?.message || 'Khôi phục nhiều thất bại')
    } finally {
      bulkRestoring.value = false
    }
  }

  async function doBulkForceDeleteCategories() {
    bulkForceDeleting.value = true
    try {
      const ids = [...trashedSelectedIds]
      await Promise.all(ids.map((id) => categoryService.forceDelete(id)))
      toast.success(`Đã xóa vĩnh viễn ${ids.length} danh mục`)
      clearTrashedSelection()
      bulkActionsRef.value?.closeModal()
      fetchTrashedCategories()
      fetchTrashedCount()
      fetchTrashedCategories()
      fetchTrashedCount()
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      toast.error(axiosError.response?.data?.message || 'Xóa vĩnh viễn nhiều thất bại')
    } finally {
      bulkForceDeleting.value = false
    }
  }

  return {
    // tab
    isTrashed,
    switchTab,
    // active
    allCategories,
    flatTree,
    loading,
    activeItems,
    fetchCategories,
    fetchFlatTree,
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
    trashedCategories,
    trashedPagination,
    trashedLoading,
    trashedCount,
    trashedSearchQuery,
    restoringId,
    fetchTrashedCategories,
    fetchTrashedCount,
    debouncedFetchTrashed,
    doRestoreCategory,
    // bulk trashed
    trashedSelectedIds,
    isTrashedAllSelected,
    isTrashedIndeterminate,
    toggleTrashedSelectAll,
    toggleTrashedSelect,
    clearTrashedSelection,
    bulkRestoring,
    bulkForceDeleting,
    doBulkRestoreCategories,
    doBulkForceDeleteCategories,
    bulkActionsRef,
    // form
    showModal,
    editingId,
    submitting,
    formErrors,
    submitError,
    form,
    autoSlug,
    openCreate,
    openEdit,
    closeModal,
    submitForm,
    // delete confirms
    softDelete,
    forceDelete,
  }
}
