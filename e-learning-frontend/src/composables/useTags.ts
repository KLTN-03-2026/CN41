import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import PostService from '@/services/post.service'
import { useFormErrors } from '@/composables/useFormErrors'
import type { Tag } from '@/types/post.types'

export function useTags() {
  const toast = useToast()
  const tags = ref<Tag[]>([])
  const loading = ref(false)
  const showModal = ref(false)
  const editingId = ref<number | null>(null)
  const submitting = ref(false)
  const { errors: formErrors, submitError, clearErrors, handleApiError } = useFormErrors()

  const defaultForm = () => ({
    name: '',
    slug: '',
  })
  const form = ref(defaultForm())

  async function fetchTags() {
    loading.value = true
    try {
      const res = await PostService.getTags()
      tags.value = res.data.data
    } catch {
      toast.error('Không thể tải danh sách thẻ')
    } finally {
      loading.value = false
    }
  }

  function autoSlug() {
    if (editingId.value) return
    form.value.slug = form.value.name
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[đĐ]/g, 'd')
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .trim()
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
  }

  function openCreate() {
    editingId.value = null
    form.value = defaultForm()
    clearErrors()
    showModal.value = true
  }

  function openEdit(tag: Tag) {
    editingId.value = tag.id
    form.value = {
      name: tag.name,
      slug: tag.slug,
    }
    clearErrors()
    showModal.value = true
  }

  async function submitForm() {
    clearErrors()
    submitting.value = true
    try {
      if (editingId.value) {
        await PostService.updateTag(editingId.value, form.value)
        toast.success('Cập nhật thành công')
      } else {
        await PostService.createTag(form.value)
        toast.success('Thêm mới thành công')
      }
      showModal.value = false
      fetchTags()
    } catch (err) {
      handleApiError(err)
    } finally {
      submitting.value = false
    }
  }

  async function deleteTag(id: number) {
    if (!confirm('Bạn có chắc chắn muốn xóa thẻ này?')) return
    try {
      await PostService.deleteTag(id)
      toast.success('Đã xóa thành công')
      fetchTags()
    } catch {
      toast.error('Xóa thất bại')
    }
  }

  return {
    tags,
    loading,
    showModal,
    editingId,
    submitting,
    formErrors,
    submitError,
    form,
    fetchTags,
    autoSlug,
    openCreate,
    openEdit,
    submitForm,
    deleteTag,
  }
}
