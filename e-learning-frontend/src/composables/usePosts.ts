import { ref, reactive } from 'vue'
import { useToast } from 'vue-toastification'
import PostService from '@/services/post.service'
import type { Post } from '@/types/post.types'

export function usePosts() {
  const toast = useToast()
  const posts = ref<Post[]>([])
  const loading = ref(false)
  const search = ref('')
  const categoryFilter = ref('')
  const statusFilter = ref('')
  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  })

  async function fetchPosts(page = 1) {
    loading.value = true
    try {
      const params: any = {
        page,
        per_page: pagination.per_page,
        search: search.value || undefined,
        category_id: categoryFilter.value || undefined,
        is_published: statusFilter.value !== '' ? statusFilter.value : undefined,
      }
      const res = await PostService.getPosts(params)
      posts.value = res.data.data
      Object.assign(pagination, res.data.pagination)
    } catch {
      toast.error('Không thể tải danh sách bài viết')
    } finally {
      loading.value = false
    }
  }

  async function deletePost(id: number) {
    if (!confirm('Bạn có chắc chắn muốn xóa bài viết này?')) return
    try {
      await PostService.deletePost(id)
      toast.success('Đã xóa thành công')
      fetchPosts(pagination.current_page)
    } catch {
      toast.error('Xóa thất bại')
    }
  }

  return {
    posts,
    loading,
    search,
    categoryFilter,
    statusFilter,
    pagination,
    fetchPosts,
    deletePost,
  }
}
