import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { profileService } from '@/services/profile.service'
import { useStudentAuthStore } from '@/stores/studentAuth.store'

export function useProfile() {
  const toast = useToast()
  const studentStore = useStudentAuthStore()

  const activeTab = ref<'info' | 'security'>('info')
  const saving = ref(false)
  const uploadingAvatar = ref(false)
  const sendingPasswordEmail = ref(false)

  const infoErrors = ref<Record<string, string[]>>({})
  const passwordErrors = ref<Record<string, string[]>>({})
  const passwordEmailSent = ref(false)

  const form = reactive({
    name: '',
    email: '',
    date_of_birth: '',
  })

  const passwordForm = reactive({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
  })

  function loadFromStore() {
    const s = studentStore.student
    if (!s) return
    form.name = s.name ?? ''
    form.email = s.email ?? ''
    form.date_of_birth = s.date_of_birth ?? ''
  }

  async function saveProfile() {
    infoErrors.value = {}
    saving.value = true
    try {
      const payload: Record<string, unknown> = {
        name: form.name,
        email: form.email,
        date_of_birth: form.date_of_birth || null,
      }
      const res = await profileService.update(payload)
      studentStore.student = res.data.data
      toast.success('Cập nhật thông tin thành công.')
    } catch (err: unknown) {
      const e = err as {
        response?: { data?: { errors?: Record<string, string[]>; message?: string } }
      }
      infoErrors.value = e.response?.data?.errors ?? {}
      toast.error(e.response?.data?.message || 'Cập nhật thất bại.')
    } finally {
      saving.value = false
    }
  }

  async function handleAvatarChange(file: File) {
    uploadingAvatar.value = true
    try {
      const res = await profileService.uploadAvatar(file)
      if (studentStore.student) {
        studentStore.student = { ...studentStore.student, avatar: res.data.data.avatar }
      }
      toast.success('Cập nhật avatar thành công.')
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string } } }
      toast.error(e.response?.data?.message || 'Upload avatar thất bại.')
    } finally {
      uploadingAvatar.value = false
    }
  }

  async function submitChangePassword() {
    passwordErrors.value = {}
    passwordEmailSent.value = false
    sendingPasswordEmail.value = true
    try {
      await profileService.changePassword({ ...passwordForm })
      passwordEmailSent.value = true
      passwordForm.current_password = ''
      passwordForm.new_password = ''
      passwordForm.new_password_confirmation = ''
      toast.success('Vui lòng kiểm tra email để xác nhận đổi mật khẩu.')
    } catch (err: unknown) {
      const e = err as {
        response?: { data?: { errors?: Record<string, string[]>; message?: string } }
      }
      passwordErrors.value = e.response?.data?.errors ?? {}
      toast.error(e.response?.data?.message || 'Không thể gửi email xác nhận.')
    } finally {
      sendingPasswordEmail.value = false
    }
  }

  onMounted(() => loadFromStore())

  return {
    activeTab,
    saving,
    uploadingAvatar,
    sendingPasswordEmail,
    infoErrors,
    passwordErrors,
    passwordEmailSent,
    form,
    passwordForm,
    saveProfile,
    handleAvatarChange,
    submitChangePassword,
  }
}
