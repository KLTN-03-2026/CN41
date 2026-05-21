import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import PostService from '@/services/post.service'

interface PostCategory { id: number; name: string; slug: string }
interface Tag { id: number; name: string; slug: string }

export function useTeacherPostForm() {
  const route = useRoute()
  const router = useRouter()
  const toast = useToast()

  const postId = computed(() => (route.params.id ? Number(route.params.id) : null))
  const isEdit = computed(() => !!postId.value)

  const categories = ref<PostCategory[]>([])
  const tags = ref<Tag[]>([])
  const loading = ref(false)
  const saving = ref(false)
  const errors = ref<Record<string, string[]>>({})

  const form = reactive({
    title: '',
    slug: '',
    content: '',
    post_category_id: null as number | null,
    tag_ids: [] as number[],
  })

  function autoSlug(title: string): string {
    return title
      .toLowerCase()
      .replace(/[àáâãäåạảấầẩẫậắằẳẵặ]/g, 'a')
      .replace(/[èéêëẹẻẽếềểễệ]/g, 'e')
      .replace(/[ìíîïịỉĩ]/g, 'i')
      .replace(/[òóôõöọỏốồổỗộớờởỡợ]/g, 'o')
      .replace(/[ùúûüụủũứừửữự]/g, 'u')
      .replace(/[ýỳỵỷỹ]/g, 'y')
      .replace(/[đ]/g, 'd')
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim()
  }

  function onTitleChange(title: string) {
    if (!isEdit.value) {
      form.slug = autoSlug(title)
    }
  }

  async function loadForm() {
    loading.value = true
    try {
      const [catRes, tagRes] = await Promise.all([
        PostService.getClientCategories(),
        PostService.getClientTags(),
      ])
      categories.value = catRes.data.data
      tags.value = tagRes.data.data

      if (isEdit.value) {
        const res = await PostService.getTeacherPost(postId.value!)
        const post = res.data.data
        form.title = post.title
        form.slug = post.slug
        form.content = post.content ?? ''
        form.post_category_id = post.category?.id ?? null
        form.tag_ids = post.tags?.map((t: Tag) => t.id) ?? []
      }
    } catch {
      toast.error('Không thể tải thông tin bài viết.')
    } finally {
      loading.value = false
    }
  }

  async function submit() {
    saving.value = true
    errors.value = {}
    try {
      if (isEdit.value) {
        await PostService.updateTeacherPost(postId.value!, { ...form })
        toast.success('Đã cập nhật bài viết.')
      } else {
        await PostService.createTeacherPost({ ...form })
        toast.success('Bài viết đã được gửi chờ duyệt.')
      }
      router.push('/teacher/posts')
    } catch (err: unknown) {
      const e = err as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } }
      errors.value = e.response?.data?.errors ?? {}
      toast.error(e.response?.data?.message ?? 'Có lỗi xảy ra.')
    } finally {
      saving.value = false
    }
  }

  onMounted(() => loadForm())

  return { form, categories, tags, loading, saving, errors, isEdit, onTitleChange, submit }
}
