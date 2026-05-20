import { ref, reactive } from 'vue'
import { useToast } from 'vue-toastification'
import PostService from '@/services/post.service'

interface TeacherPost {
  id: number
  title: string
  slug: string
  approval_status: 'pending' | 'approved' | 'rejected'
  rejection_reason: string | null
  is_published: boolean
  category: { id: number; name: string; slug: string } | null
  created_at: string
}

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export function useTeacherPosts() {
  const posts = ref<TeacherPost[]>([])
  const pagination = ref<Pagination>({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
  const loading = ref(false)
  const filters = reactive({ page: 1, per_page: 15, approval_status: '' })
  const toast = useToast()

  async function fetchPosts() {
    loading.value = true
    try {
      const params: Record<string, unknown> = { page: filters.page, per_page: filters.per_page }
      if (filters.approval_status) params.approval_status = filters.approval_status
      const res = await PostService.getTeacherPosts(params)
      posts.value = res.data.data
      pagination.value = res.data.pagination
    } finally {
      loading.value = false
    }
  }

  async function deletePost(id: number) {
    if (!confirm('Bạn có chắc muốn xóa bài viết này?')) return
    try {
      await PostService.deleteTeacherPost(id)
      toast.success('Đã xóa bài viết.')
      await fetchPosts()
    } catch {
      toast.error('Không thể xóa bài viết.')
    }
  }

  function changePage(page: number) {
    filters.page = page
    fetchPosts()
  }

  return { posts, pagination, loading, filters, fetchPosts, deletePost, changePage }
}
