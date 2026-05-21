import { ref } from 'vue'
import { commissionService } from '@/services/commission.service'

export function useTeacherEarnings() {
  const summary = ref<any[]>([])
  const loading = ref(false)

  async function loadSummary() {
    if (loading.value) return
    loading.value = true
    try {
      const res = await commissionService.getTeacherEarningsSummary()
      summary.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  return { summary, loading, loadSummary }
}
