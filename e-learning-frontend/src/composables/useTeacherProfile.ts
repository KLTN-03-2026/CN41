import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { commissionService } from '@/services/commission.service'

interface TeacherProfile {
  id: number
  name: string
  email: string
  description: string | null
  image: string | null
  bank_name: string | null
  bank_account_number: string | null
  bank_account_name: string | null
}

export function useTeacherProfile() {
  const profile = ref<TeacherProfile | null>(null)
  const loading = ref(false)
  const saving = ref(false)
  const toast = useToast()

  async function loadProfile() {
    loading.value = true
    try {
      const res = await commissionService.getTeacherProfile()
      profile.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  async function saveProfile(data: {
    description?: string
    bank_name?: string
    bank_account_number?: string
    bank_account_name?: string
  }): Promise<boolean> {
    saving.value = true
    try {
      const res = await commissionService.updateTeacherProfile(data)
      profile.value = res.data.data
      toast.success('Cập nhật hồ sơ thành công!')
      return true
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string } } }
      toast.error(e.response?.data?.message || 'Có lỗi xảy ra.')
      return false
    } finally {
      saving.value = false
    }
  }

  return { profile, loading, saving, loadProfile, saveProfile }
}
