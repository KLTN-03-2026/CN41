import { ref, onMounted } from 'vue'
import { courseService } from '@/services/course.service'
import { categoryService } from '@/services/category.service'
import { teacherService } from '@/services/teacher.service'
import PostService from '@/services/post.service'
import type { Course, Category, Teacher } from '@/types/course.types'
import type { Post } from '@/services/post.service'

export function useHomePage() {
  const featuredCourses = ref<Course[]>([])
  const categories = ref<Category[]>([])
  const latestPosts = ref<Post[]>([])
  const featuredTeachers = ref<Teacher[]>([])
  const loading = ref(true)

  async function loadAll() {
    loading.value = true
    try {
      const [coursesRes, catsRes, postsRes, teachersRes] = await Promise.all([
        courseService.featured(8),
        categoryService.publicList(),
        PostService.getClientPosts({ per_page: 3 }),
        teacherService.featured(4),
      ])
      featuredCourses.value = coursesRes.data.data ?? []
      categories.value = (catsRes.data.data ?? []).slice(0, 8)
      latestPosts.value = postsRes.data.data ?? []
      featuredTeachers.value = (teachersRes.data.data as unknown as Teacher[]) ?? []
    } catch {
      // silent — sections sẽ hiển thị trạng thái empty
    } finally {
      loading.value = false
    }
  }

  onMounted(loadAll)

  return { featuredCourses, categories, latestPosts, featuredTeachers, loading }
}
