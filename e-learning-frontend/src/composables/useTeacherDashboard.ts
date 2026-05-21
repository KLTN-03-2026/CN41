import { ref } from 'vue'
import { commissionService } from '@/services/commission.service'

interface TeacherDashboardData {
  total_courses: number
  total_students: number
  total_earned: number
  available_balance: number
}

export function useTeacherDashboard() {
  const stats = ref<TeacherDashboardData>({
    total_courses: 0,
    total_students: 0,
    total_earned: 0,
    available_balance: 0,
  })
  const loading = ref(false)

  async function loadDashboard() {
    loading.value = true
    try {
      const res = await commissionService.getTeacherDashboard()
      stats.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  return { stats, loading, loadDashboard }
}
