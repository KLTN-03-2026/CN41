import { ref, reactive, computed } from 'vue'
import { useToast } from 'vue-toastification'
import { sectionService } from '@/services/section.service'
import { lessonService } from '@/services/lesson.service'
import { useDeleteConfirm } from '@/composables/useDeleteConfirm'
import { useFormErrors } from '@/composables/useFormErrors'
import type { AdminSection, AdminLesson, SectionForm } from '@/types/section-lesson.types'

export function useSectionsManager(courseId: number) {
  const toast = useToast()

  // ── State ──────────────────────────────────────────────────────
  const sectionsList = ref<AdminSection[]>([])
  const orphanLessons = ref<AdminLesson[]>([])
  const loading = ref(true)
  const expandedSections = reactive(new Set<number | string>())

  const totalLessons = computed(() => {
    let total = orphanLessons.value.length
    for (const s of sectionsList.value) {
      total += (s.lessons || []).length
    }
    return total
  })

  // ── Fetch ──────────────────────────────────────────────────────
  async function fetchAll() {
    loading.value = true
    try {
      const [sectionsRes, lessonsRes] = await Promise.all([
        sectionService.index(courseId, { per_page: 100 }),
        lessonService.index(courseId, { per_page: 100 }),
      ])

      const allSections: AdminSection[] = ((sectionsRes.data as any).data || []).map((s: any) => ({
        ...s,
        lessons: [],
      }))

      const allLessons: AdminLesson[] = (lessonsRes.data as any).data || []

      const sectionMap = new Map<number, AdminSection>()
      for (const s of allSections) {
        sectionMap.set(s.id, s)
      }

      const orphans: AdminLesson[] = []
      for (const lesson of allLessons) {
        if (lesson.section_id && sectionMap.has(lesson.section_id)) {
          sectionMap.get(lesson.section_id)!.lessons.push(lesson)
        } else {
          orphans.push(lesson)
        }
      }

      allSections.sort((a, b) => a.order - b.order)
      for (const s of allSections) {
        s.lessons.sort((a, b) => a.order - b.order)
      }

      sectionsList.value = allSections
      orphanLessons.value = orphans
    } catch {
      toast.error('Không thể tải nội dung khóa học')
    } finally {
      loading.value = false
    }
  }

  // ── Expand / Collapse ──────────────────────────────────────────
  function toggleExpand(id: number | string) {
    if (expandedSections.has(id)) expandedSections.delete(id)
    else expandedSections.add(id)
  }

  // ── Reorder ────────────────────────────────────────────────────
  async function reorderSection(fromIdx: number, toIdx: number) {
    const arr = [...sectionsList.value]
    const [item] = arr.splice(fromIdx, 1)
    arr.splice(toIdx, 0, item)
    sectionsList.value = arr

    const orders = arr.map((s, i) => ({ id: s.id, order: i }))
    try {
      await sectionService.reorder(orders)
    } catch {
      toast.error('Sắp xếp chương thất bại')
      fetchAll()
    }
  }

  // ── Toggle Status ──────────────────────────────────────────────
  const togglingSection = ref<number | null>(null)

  async function toggleSectionStatus(section: AdminSection) {
    togglingSection.value = section.id
    try {
      await sectionService.toggleStatus(section.id)
      section.status = section.status === 1 ? 0 : 1
    } catch {
      toast.error('Không thể cập nhật trạng thái chương')
    } finally {
      togglingSection.value = null
    }
  }

  // ── Section Form ───────────────────────────────────────────────
  const showSectionModal = ref(false)
  const editingSectionId = ref<number | null>(null)
  const sSubmitting = ref(false)
  const {
    errors: sErrors,
    submitError: sSubmitError,
    handleApiError: handleSectionError,
    clearErrors: clearSectionErrors,
  } = useFormErrors()

  const defaultSForm = (): SectionForm => ({
    title: '',
    description: '',
    order: 0,
    status: 0,
  })
  const sForm = ref(defaultSForm())

  function openCreateSection() {
    editingSectionId.value = null
    sForm.value = defaultSForm()
    sForm.value.order = sectionsList.value.length
    clearSectionErrors()
    showSectionModal.value = true
  }

  function openEditSection(section: AdminSection) {
    editingSectionId.value = section.id
    sForm.value = {
      title: section.title,
      description: section.description || '',
      order: section.order,
      status: section.status,
    }
    clearSectionErrors()
    showSectionModal.value = true
  }

  async function submitSection() {
    clearSectionErrors()
    if (!sForm.value.title) {
      sErrors.value.title = 'Vui lòng nhập tiêu đề'
      return
    }

    sSubmitting.value = true
    const payload = {
      title: sForm.value.title,
      description: sForm.value.description || null,
      order: sForm.value.order,
      status: sForm.value.status,
    }

    try {
      if (editingSectionId.value) {
        await sectionService.update(editingSectionId.value, payload)
        toast.success('Cập nhật chương thành công')
      } else {
        await sectionService.store(courseId, payload)
        toast.success('Tạo chương thành công')
      }
      showSectionModal.value = false
      fetchAll()
    } catch (err: any) {
      handleSectionError(err)
    } finally {
      sSubmitting.value = false
    }
  }

  // ── Delete ─────────────────────────────────────────────────────
  const deleteSection = useDeleteConfirm({
    async onConfirm(section: AdminSection) {
      await sectionService.destroy(section.id)
      toast.success('Xóa chương thành công')
      fetchAll()
    },
  })

  function confirmDeleteSection(section: AdminSection) {
    deleteSection.confirm(section)
  }

  // ── Bulk selection helpers (section-level) ─────────────────────
  function isSectionAllSelected(section: AdminSection, selectedLessons?: number[]): boolean {
    const sel = selectedLessons ?? []
    if (!section.lessons || section.lessons.length === 0) return false
    return section.lessons.every((l: AdminLesson) => sel.includes(l.id))
  }

  function getSectionLessonIds(section: AdminSection): number[] {
    return (section.lessons || []).map((l: AdminLesson) => l.id)
  }

  return {
    // state
    sectionsList,
    orphanLessons,
    loading,
    expandedSections,
    totalLessons,
    togglingSection,
    // section form
    showSectionModal,
    editingSectionId,
    sSubmitting,
    sErrors,
    sSubmitError,
    sForm,
    // delete confirm
    deleteSection,
    // methods
    fetchAll,
    toggleExpand,
    reorderSection,
    toggleSectionStatus,
    openCreateSection,
    openEditSection,
    submitSection,
    confirmDeleteSection,
    isSectionAllSelected,
    getSectionLessonIds,
  }
}
