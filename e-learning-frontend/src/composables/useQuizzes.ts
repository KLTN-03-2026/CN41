import { ref, reactive } from 'vue'
import { useToast } from 'vue-toastification'
import { quizService } from '@/services/quiz.service'
import { useFormErrors } from '@/composables/useFormErrors'
import { useDeleteConfirm } from '@/composables/useDeleteConfirm'
import type { Quiz } from '@/services/quiz.service'
import type { Pagination } from '@/types/common.types'

export function useQuizzes(lessonId?: number) {
  const toast = useToast()

  const quizzes = ref<Quiz[]>([])
  const pagination = ref<Pagination | null>(null)
  const loading = ref(false)
  const filters = reactive({ search: '', status: '', lesson_id: lessonId ?? '' })

  async function fetchQuizzes(page = 1) {
    loading.value = true
    try {
      const params: Record<string, unknown> = { page, per_page: 15, ...filters }
      const res = await quizService.index(params)
      quizzes.value = res.data.data
      pagination.value = res.data.pagination
    } catch {
      toast.error('Không thể tải danh sách quiz')
    } finally {
      loading.value = false
    }
  }

  // ── Form ───────────────────────────────────────────────────
  const showModal = ref(false)
  const editingId = ref<number | null>(null)
  const submitting = ref(false)
  const { errors: formErrors, clearErrors, handleApiError } = useFormErrors()

  const defaultForm = () => ({
    lesson_id: lessonId ?? (null as number | null),
    title: '',
    description: '',
    max_attempts: 3,
    time_limit: null as number | null,
    status: 1,
  })
  const form = ref(defaultForm())

  function openCreate() {
    editingId.value = null
    form.value = defaultForm()
    clearErrors()
    showModal.value = true
  }

  function openEdit(quiz: Quiz) {
    editingId.value = quiz.id
    form.value = {
      lesson_id: quiz.lesson_id,
      title: quiz.title,
      description: quiz.description ?? '',
      max_attempts: quiz.max_attempts,
      time_limit: quiz.time_limit,
      status: quiz.status,
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
    try {
      const payload: Record<string, unknown> = {
        lesson_id: form.value.lesson_id,
        title: form.value.title,
        description: form.value.description || null,
        max_attempts: form.value.max_attempts,
        time_limit: form.value.time_limit || null,
        status: form.value.status,
      }
      if (editingId.value) {
        await quizService.update(editingId.value, payload)
        toast.success('Cập nhật quiz thành công')
      } else {
        await quizService.store(payload)
        toast.success('Tạo quiz thành công')
      }
      closeModal()
      fetchQuizzes(pagination.value?.current_page || 1)
    } catch (err) {
      handleApiError(err)
    } finally {
      submitting.value = false
    }
  }

  const softDelete = useDeleteConfirm({
    async onConfirm(quiz: Quiz) {
      try {
        await quizService.destroy(quiz.id)
        toast.success('Xóa quiz thành công')
        fetchQuizzes(pagination.value?.current_page || 1)
      } catch (err: unknown) {
        const axiosError = err as { response?: { data?: { message?: string } } }
        toast.error(axiosError.response?.data?.message || 'Xóa quiz thất bại')
      }
    },
  })

  async function toggleStatus(quiz: Quiz) {
    try {
      await quizService.toggleStatus(quiz.id)
      quiz.status = quiz.status === 1 ? 0 : 1
    } catch {
      toast.error('Cập nhật trạng thái thất bại')
    }
  }

  return {
    quizzes,
    pagination,
    loading,
    filters,
    fetchQuizzes,
    showModal,
    editingId,
    submitting,
    formErrors,
    form,
    openCreate,
    openEdit,
    closeModal,
    submitForm,
    softDelete,
    toggleStatus,
  }
}
