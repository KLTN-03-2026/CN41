import { ref, computed, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { lessonService } from '@/services/lesson.service'
import { useDeleteConfirm } from '@/composables/useDeleteConfirm'
import { useFormErrors } from '@/composables/useFormErrors'
import type { AdminSection, AdminLesson, LessonForm } from '@/types/section-lesson.types'

export function useLessonsManager(
  courseId: number,
  sectionsList: Readonly<{ value: AdminSection[] }>,
  orphanLessons: Readonly<{ value: AdminLesson[] }>,
  totalLessons: Readonly<{ value: number }>,
  fetchAll: () => Promise<void>,
) {
  const toast = useToast()

  // ── Tab state ──────────────────────────────────────────────────
  const currentTab = ref<'active' | 'trashed'>('active')
  const trashedLessons = ref<AdminLesson[]>([])
  const loadingTrashed = ref(false)

  // ── Selection state ────────────────────────────────────────────
  const selectedLessons = ref<number[]>([])

  const isAllSelected = computed(() => {
    if (currentTab.value === 'active') {
      const total = totalLessons.value
      return total > 0 && selectedLessons.value.length === total
    }
    return trashedLessons.value.length > 0 && selectedLessons.value.length === trashedLessons.value.length
  })

  const isOrphanAllSelected = computed(() => {
    if (orphanLessons.value.length === 0) return false
    return orphanLessons.value.every((l: AdminLesson) => selectedLessons.value.includes(l.id))
  })

  function toggleLessonSelect(id: number) {
    const idx = selectedLessons.value.indexOf(id)
    if (idx >= 0) selectedLessons.value.splice(idx, 1)
    else selectedLessons.value.push(id)
  }

  function toggleSelectAll(checked: boolean) {
    if (checked) {
      if (currentTab.value === 'active') {
        const ids: number[] = orphanLessons.value.map((l: AdminLesson) => l.id)
        sectionsList.value.forEach((s: AdminSection) => {
          ids.push(...(s.lessons || []).map((l: AdminLesson) => l.id))
        })
        selectedLessons.value = ids
      } else {
        selectedLessons.value = trashedLessons.value.map((l: AdminLesson) => l.id)
      }
    } else {
      selectedLessons.value = []
    }
  }

  function handleSelectAllChange(e: Event) {
    toggleSelectAll((e.target as HTMLInputElement).checked)
  }

  function isSectionAllSelected(section: AdminSection): boolean {
    if (!section.lessons || section.lessons.length === 0) return false
    return section.lessons.every((l: AdminLesson) => selectedLessons.value.includes(l.id))
  }

  function handleSectionSelectAll(section: AdminSection, e: Event) {
    const checked = (e.target as HTMLInputElement).checked
    if (!section.lessons) return
    const sIds = section.lessons.map((l: AdminLesson) => l.id)
    if (checked) {
      selectedLessons.value = Array.from(new Set([...selectedLessons.value, ...sIds]))
    } else {
      selectedLessons.value = selectedLessons.value.filter(id => !sIds.includes(id))
    }
  }

  function handleOrphanSelectAll(e: Event) {
    const checked = (e.target as HTMLInputElement).checked
    const sIds = orphanLessons.value.map((l: AdminLesson) => l.id)
    if (checked) {
      selectedLessons.value = Array.from(new Set([...selectedLessons.value, ...sIds]))
    } else {
      selectedLessons.value = selectedLessons.value.filter(id => !sIds.includes(id))
    }
  }

  // ── Trashed ────────────────────────────────────────────────────
  async function fetchTrashed() {
    loadingTrashed.value = true
    try {
      const res = await lessonService.trashed({ course_id: courseId, per_page: 100 })
      const resData = res.data as any
      trashedLessons.value = Array.isArray(resData?.data)
        ? resData.data
        : (resData?.data?.data || [])
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      const data = axiosErr.response?.data
      let msg = data?.message || 'Lỗi tải thùng rác bài giảng'
      if (data?.errors) {
        msg += ': ' + Object.values(data.errors).map((e: string[]) => e[0]).join(', ')
      }
      toast.error(msg)
    } finally {
      loadingTrashed.value = false
    }
  }

  watch(currentTab, (val) => {
    selectedLessons.value = []
    if (val === 'trashed') fetchTrashed()
    else fetchAll()
  })

  // ── Toggle status ──────────────────────────────────────────────
  const togglingLesson = ref<number | null>(null)

  async function toggleLessonStatus(lesson: AdminLesson) {
    togglingLesson.value = lesson.id
    try {
      await lessonService.toggleStatus(lesson.id)
      lesson.status = lesson.status === 1 ? 0 : 1
    } catch {
      toast.error('Không thể cập nhật trạng thái bài giảng')
    } finally {
      togglingLesson.value = null
    }
  }

  // ── Reorder ────────────────────────────────────────────────────
  const draggedLessonIdx = ref<number | null>(null)

  async function reorderLesson(section: AdminSection, fromIdx: number, toIdx: number) {
    const arr = [...(section.lessons || [])]
    const [item] = arr.splice(fromIdx, 1)
    arr.splice(toIdx, 0, item)
    section.lessons = arr

    const orders = arr.map((l: AdminLesson, i: number) => ({ id: l.id, order: i }))
    try {
      await lessonService.reorder(orders)
    } catch {
      toast.error('Sắp xếp bài giảng thất bại')
      fetchAll()
    }
  }

  async function reorderLessonDrag(section: AdminSection, toIdx: number) {
    if (draggedLessonIdx.value === null || draggedLessonIdx.value === toIdx) {
      draggedLessonIdx.value = null
      return
    }
    const fromIdx = draggedLessonIdx.value
    await reorderLesson(section, fromIdx, toIdx)
    draggedLessonIdx.value = null
  }

  // ── Lesson Form ────────────────────────────────────────────────
  const showLessonModal = ref(false)
  const editingLessonId = ref<number | null>(null)
  const lSubmitting = ref(false)
  const {
    errors: lErrors,
    submitError: lSubmitError,
    handleApiError: handleLessonError,
    clearErrors: clearLessonErrors,
  } = useFormErrors()

  const defaultLForm = (): LessonForm => ({
    section_id: null,
    title: '',
    type: 'video',
    content: '',
    media_id: null,
    order: 0,
    duration: null,
    is_preview: false,
    status: 0,
  })
  const lForm = ref(defaultLForm())

  function openCreateLesson(sectionId: number | null) {
    editingLessonId.value = null
    lForm.value = defaultLForm()
    lForm.value.section_id = sectionId

    let nextOrder = 0
    if (sectionId) {
      const section = sectionsList.value.find((s: AdminSection) => s.id === sectionId)
      if (section?.lessons) nextOrder = section.lessons.length
    } else {
      nextOrder = orphanLessons.value.length
    }
    lForm.value.order = nextOrder
    clearLessonErrors()
    showLessonModal.value = true
  }

  function openEditLesson(lesson: AdminLesson) {
    editingLessonId.value = lesson.id
    lForm.value = {
      section_id: lesson.section_id ?? null,
      title: lesson.title,
      type: lesson.type,
      content: lesson.content || '',
      media_id: null,
      order: lesson.order,
      duration: lesson.duration ?? null,
      is_preview: lesson.is_preview,
      status: lesson.status,
    }
    clearLessonErrors()
    showLessonModal.value = true
  }

  async function submitLesson() {
    clearLessonErrors()
    if (!lForm.value.title) {
      lErrors.value.title = 'Vui lòng nhập tiêu đề'
      return
    }

    lSubmitting.value = true
    const payload: any = {
      section_id: lForm.value.section_id || null,
      title: lForm.value.title,
      type: lForm.value.type,
      content: lForm.value.content || null,
      order: lForm.value.order,
      duration: lForm.value.type === 'video' ? (lForm.value.duration ?? null) : null,
      is_preview: lForm.value.is_preview ? 1 : 0,
      status: lForm.value.status,
    }

    if (lForm.value.media_id) {
      if (lForm.value.type === 'video') payload.video_id = lForm.value.media_id
      else if (lForm.value.type === 'document') payload.document_id = lForm.value.media_id
    }

    try {
      if (editingLessonId.value) {
        await lessonService.update(editingLessonId.value, payload)
        toast.success('Cập nhật bài giảng thành công')
      } else {
        await lessonService.store(courseId, payload)
        toast.success('Tạo bài giảng thành công')
      }
      showLessonModal.value = false
      fetchAll()
    } catch (err: any) {
      handleLessonError(err)
      if (lSubmitError.value) toast.error(lSubmitError.value)
    } finally {
      lSubmitting.value = false
    }
  }

  // ── Delete confirm ─────────────────────────────────────────────
  const deleteLesson = useDeleteConfirm({
    async onConfirm(lesson: AdminLesson) {
      await lessonService.destroy(lesson.id)
      toast.success('Xóa bài giảng thành công')
      fetchAll()
    },
  })

  const restoreLessonConfirm = useDeleteConfirm({
    async onConfirm(lesson: AdminLesson) {
      await lessonService.restore(lesson.id)
      toast.success('Khôi phục thành công')
      fetchTrashed()
    },
  })

  const forceDeleteLessonConfirm = useDeleteConfirm({
    async onConfirm(lesson: AdminLesson) {
      await lessonService.forceDelete(lesson.id)
      toast.success('Đã xóa vĩnh viễn')
      fetchTrashed()
    },
  })

  function handleRestoreLessonTr(lesson: AdminLesson) {
    restoreLessonConfirm.confirm(lesson)
  }

  function handleForceDeleteLessonTr(lesson: AdminLesson) {
    forceDeleteLessonConfirm.confirm(lesson)
  }

  // ── Preview ────────────────────────────────────────────────────
  const previewLesson = ref<any>(null)
  const previewLoading = ref(false)
  const previewModalRef = ref<any>(null)

  async function handlePreviewLesson(lessonId: number) {
    previewLesson.value = null
    previewLoading.value = true
    previewModalRef.value?.open()
    try {
      const res = await lessonService.show(lessonId)
      previewLesson.value = res.data.data
    } catch {
      toast.error('Không thể tải nội dung xem trước')
      previewModalRef.value?.close()
    } finally {
      previewLoading.value = false
    }
  }

  // ── Bulk Actions ───────────────────────────────────────────────
  const bulkActionLoading = ref(false)
  const bulkActionsRef = ref<any>(null)

  async function doBulkStatusLessons(statusVal: 'activate' | 'deactivate') {
    bulkActionLoading.value = true
    try {
      await lessonService.bulkAction({ ids: selectedLessons.value, action: statusVal })
      toast.success('Cập nhật trạng thái thành công')
      selectedLessons.value = []
      bulkActionsRef.value?.closeModal()
      fetchAll()
    } catch {
      toast.error('Chưa thể cập nhật trạng thái hàng loạt')
    } finally {
      bulkActionLoading.value = false
    }
  }

  async function doBulkDeleteLessons() {
    bulkActionLoading.value = true
    try {
      await lessonService.bulkDelete(selectedLessons.value)
      toast.success('Xóa bài giảng thành công')
      selectedLessons.value = []
      bulkActionsRef.value?.closeModal()
      fetchAll()
    } catch {
      toast.error('Chưa thể xóa hàng loạt')
    } finally {
      bulkActionLoading.value = false
    }
  }

  async function doBulkRestoreLessons() {
    bulkActionLoading.value = true
    try {
      await lessonService.bulkRestore(selectedLessons.value)
      toast.success('Khôi phục thành công')
      selectedLessons.value = []
      bulkActionsRef.value?.closeModal()
      fetchTrashed()
    } catch {
      toast.error('Chưa thể khôi phục hàng loạt')
    } finally {
      bulkActionLoading.value = false
    }
  }

  async function doBulkForceDeleteLessons() {
    bulkActionLoading.value = true
    try {
      await lessonService.bulkForceDelete(selectedLessons.value)
      toast.success('Xóa vĩnh viễn thành công')
      selectedLessons.value = []
      bulkActionsRef.value?.closeModal()
      fetchTrashed()
    } catch {
      toast.error('Chưa thể xóa vĩnh viễn hàng loạt')
    } finally {
      bulkActionLoading.value = false
    }
  }

  async function doBulkAssignSection(sectionId: number | null) {
    bulkActionLoading.value = true
    try {
      await lessonService.bulkAction({
        ids: selectedLessons.value,
        action: 'assign-section',
        section_id: sectionId,
      })
      const sectionName = sectionId
        ? sectionsList.value.find((s: AdminSection) => s.id === sectionId)?.title || 'chương đã chọn'
        : 'Chưa phân chương'
      toast.success(`Đã gán ${selectedLessons.value.length} bài giảng vào "${sectionName}"`)
      selectedLessons.value = []
      bulkActionsRef.value?.closeModal()
      fetchAll()
    } catch {
      toast.error('Chưa thể phân chương hàng loạt')
    } finally {
      bulkActionLoading.value = false
    }
  }

  return {
    // tab
    currentTab,
    trashedLessons,
    loadingTrashed,
    fetchTrashed,
    // selection
    selectedLessons,
    isAllSelected,
    isOrphanAllSelected,
    toggleLessonSelect,
    toggleSelectAll,
    handleSelectAllChange,
    isSectionAllSelected,
    handleSectionSelectAll,
    handleOrphanSelectAll,
    // status toggle
    togglingLesson,
    toggleLessonStatus,
    // reorder
    draggedLessonIdx,
    reorderLesson,
    reorderLessonDrag,
    // lesson form
    showLessonModal,
    editingLessonId,
    lSubmitting,
    lErrors,
    lSubmitError,
    lForm,
    openCreateLesson,
    openEditLesson,
    submitLesson,
    // delete / restore / force-delete
    deleteLesson,
    restoreLessonConfirm,
    forceDeleteLessonConfirm,
    handleRestoreLessonTr,
    handleForceDeleteLessonTr,
    // preview
    previewLesson,
    previewLoading,
    previewModalRef,
    handlePreviewLesson,
    // bulk
    bulkActionLoading,
    bulkActionsRef,
    doBulkStatusLessons,
    doBulkDeleteLessons,
    doBulkRestoreLessons,
    doBulkForceDeleteLessons,
    doBulkAssignSection,
  }
}
