import { reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { commissionService } from '@/services/commission.service'

export function usePayouts() {
  const toast = useToast()
  const payouts = ref<any[]>([])
  const loading = ref(false)
  const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
  const filters = reactive({ status: '', teacher_id: '', page: 1, per_page: 15 })

  async function loadPayouts() {
    if (loading.value) return
    loading.value = true
    try {
      const res = await commissionService.getAdminPayouts(filters)
      payouts.value = res.data.data
      pagination.value = res.data.pagination
    } finally {
      loading.value = false
    }
  }

  async function approvePayout(id: number, adminNote = '') {
    await commissionService.approvePayout(id, { admin_note: adminNote })
    toast.success('Đã duyệt yêu cầu rút tiền.')
    await loadPayouts()
  }

  async function rejectPayout(id: number, adminNote = '') {
    await commissionService.rejectPayout(id, { admin_note: adminNote })
    toast.success('Đã từ chối yêu cầu.')
    await loadPayouts()
  }

  async function markPaid(id: number) {
    await commissionService.markPaid(id)
    toast.success('Đã đánh dấu đã thanh toán.')
    await loadPayouts()
  }

  return { payouts, loading, pagination, filters, loadPayouts, approvePayout, rejectPayout, markPaid }
}
