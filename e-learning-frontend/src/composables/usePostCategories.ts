import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import PostService from '@/services/post.service'
import { useFormErrors } from '@/composables/useFormErrors'
import type { PostCategory } from '@/types/post.types'

export function usePostCategories() {
  const toast = useToast()
  const categories = ref<PostCategory[]>([])
  const loading = ref(false)
  const showModal = ref(false)
  const editingId = ref<number | null>(null)
  const submitting = ref(false)
  const { errors: formErrors, submitError, clearErrors, handleApiError } = useFormErrors()

  const defaultForm = () => ({
    name: '',
    slug: '',
    description: '',
  })
  const form = ref(defaultForm())

  async function fetchCategories() {
    loading.value = true
    try {
      const res = await PostService.getCategories()
      categories.value = res.data.data
    } catch {
      toast.error('Không thể tải danh mục bài viết')
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

  function openEdit(cat: PostCategory) {
    editingId.value = cat.id
    form.value = {
      name: cat.name,
      slug: cat.slug,
      description: cat.description || '',
    }
    clearErrors()
    showModal.value = true
  }

  async function submitForm() {
    clearErrors()
    submitting.value = true
    try {
      if (editingId.value) {
        await PostService.updateCategory(editingId.value, form.value)
        toast.success('Cập nhật thành công')
      } else {
        await PostService.createCategory(form.value)
        toast.success('Thêm mới thành công')
      }
      showModal.value = false
      fetchCategories()
    } catch (err) {
      handleApiError(err)
    } finally {
      submitting.value = false
    }
  }

  async function deleteCategory(id: number) {
    if (!confirm('Bạn có chắc chắn muốn xóa danh mục này?')) return
    try {
      await PostService.deleteCategory(id)
      toast.success('Đã xóa thành công')
      fetchCategories()
    } catch {
      toast.error('Xóa thất bại')
    }
  }

  return {
    categories,
    loading,
    showModal,
    editingId,
    submitting,
    formErrors,
    submitError,
    form,
    fetchCategories,
    autoSlug,
    openCreate,
    openEdit,
    submitForm,
    deleteCategory,
  }
}
