import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { featureFlagService, type FeatureFlag } from '@/services/featureFlag.service'

export function useFeatureFlags() {
  const toast = useToast()
  const flags = ref<FeatureFlag[]>([])
  const loading = ref(false)
  const toggling = ref<string | null>(null)

  async function loadFlags() {
    if (loading.value) return
    loading.value = true
    try {
      const res = await featureFlagService.index()
      flags.value = res.data.data
    } catch {
      toast.error('Không thể tải danh sách tính năng.')
    } finally {
      loading.value = false
    }
  }

  async function toggleFlag(key: string, active: boolean) {
    if (toggling.value) return
    toggling.value = key

    // Optimistic update
    const flag = flags.value.find((f) => f.key === key)
    if (!flag) return
    flag.active = active

    try {
      await featureFlagService.update(key, active)
      toast.success(`Đã ${active ? 'bật' : 'tắt'} ${flag.label}.`)
    } catch {
      // Revert
      flag.active = !active
      toast.error('Cập nhật thất bại. Vui lòng thử lại.')
    } finally {
      toggling.value = null
    }
  }

  return { flags, loading, toggling, loadFlags, toggleFlag }
}
