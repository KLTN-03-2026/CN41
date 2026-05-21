import { ref, reactive } from 'vue'
import { commissionService } from '@/services/commission.service'

interface TeacherCourse {
  id: number
  name: string
  slug: string
  price: number
  sale_price: number | null
  status: number
  total_students: number
}

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export function useTeacherCourses() {
  const courses = ref<TeacherCourse[]>([])
  const pagination = ref<Pagination>({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
  const loading = ref(false)
  const filters = reactive({ page: 1, per_page: 15 })

  async function loadCourses() {
    loading.value = true
    try {
      const res = await commissionService.getTeacherCourses(filters)
      courses.value = res.data.data
      pagination.value = res.data.pagination
    } finally {
      loading.value = false
    }
  }

  function changePage(page: number) {
    filters.page = page
    loadCourses()
  }

  return { courses, pagination, loading, loadCourses, changePage }
}
