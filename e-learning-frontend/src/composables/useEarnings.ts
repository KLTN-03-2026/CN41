import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { commissionService } from '@/services/commission.service'

export function useEarnings() {
  const toast = useToast()
  const balance = ref({ available: 0, total_earned: 0, total_paid: 0, pending_payout: 0 })
  const earnings = ref<any[]>([])
  const payouts = ref<any[]>([])
  const loading = ref(false)
  const payoutLoading = ref(false)
  const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

  async function loadEarnings(params = {}) {
    if (loading.value) return
    loading.value = true
    try {
      const res = await commissionService.getMyEarnings(params)
      balance.value = res.data.data.balance
      earnings.value = res.data.data.earnings
      pagination.value = res.data.data.pagination
    } finally {
      loading.value = false
    }
  }

  async function loadMyPayouts(params = {}) {
    const res = await commissionService.getMyPayouts(params)
    payouts.value = res.data.data
  }

  async function requestPayout(amount: number, teacherNote = ''): Promise<boolean> {
    if (payoutLoading.value) return false
    payoutLoading.value = true
    try {
      await commissionService.requestPayout({ amount, teacher_note: teacherNote })
      toast.success('Yêu cầu rút tiền đã được gửi thành công.')
      await loadEarnings()
      return true
    } catch (err: any) {
      toast.error(err.response?.data?.message || 'Đã có lỗi xảy ra.')
      return false
    } finally {
      payoutLoading.value = false
    }
  }

  return { balance, earnings, payouts, loading, payoutLoading, pagination, loadEarnings, loadMyPayouts, requestPayout }
}
