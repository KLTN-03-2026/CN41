import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { commissionService } from '@/services/commission.service'

type ApiError = { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }

export function useTeacherSecurity() {
  const toast = useToast()

  // ── Password change ──────────────────────────────────────────────────────────
  const pwOtpSent = ref(false)
  const pwSending = ref(false)
  const pwConfirming = ref(false)
  const pwErrors = ref<Record<string, string[]>>({})

  async function sendPasswordOtp(): Promise<boolean> {
    pwSending.value = true
    pwErrors.value = {}
    try {
      await commissionService.sendPasswordOtp()
      pwOtpSent.value = true
      toast.success('Mã xác minh đã được gửi đến email của bạn.')
      return true
    } catch (err: unknown) {
      const e = err as ApiError
      toast.error(e.response?.data?.message || 'Không thể gửi mã xác minh.')
      return false
    } finally {
      pwSending.value = false
    }
  }

  async function confirmPasswordChange(otp: string, password: string, passwordConfirmation: string): Promise<boolean> {
    pwConfirming.value = true
    pwErrors.value = {}
    try {
      await commissionService.confirmPasswordChange({ otp, password, password_confirmation: passwordConfirmation })
      pwOtpSent.value = false
      toast.success('Đổi mật khẩu thành công!')
      return true
    } catch (err: unknown) {
      const e = err as ApiError
      const msg = e.response?.data?.message || 'Xác minh thất bại.'
      const errors = e.response?.data?.errors
      if (errors) {
        pwErrors.value = errors
      } else {
        toast.error(msg)
      }
      return false
    } finally {
      pwConfirming.value = false
    }
  }

  function resetPasswordForm() {
    pwOtpSent.value = false
    pwErrors.value = {}
  }

  // ── Email change ─────────────────────────────────────────────────────────────
  const emailOtpSent = ref(false)
  const emailSending = ref(false)
  const emailConfirming = ref(false)
  const emailErrors = ref<Record<string, string[]>>({})
  const pendingNewEmail = ref('')

  async function sendEmailChangeOtp(newEmail: string): Promise<boolean> {
    emailSending.value = true
    emailErrors.value = {}
    try {
      await commissionService.sendEmailChangeOtp({ new_email: newEmail })
      pendingNewEmail.value = newEmail
      emailOtpSent.value = true
      toast.success('Mã xác minh đã được gửi đến email mới của bạn.')
      return true
    } catch (err: unknown) {
      const e = err as ApiError
      const msg = e.response?.data?.message || 'Không thể gửi mã xác minh.'
      const errors = e.response?.data?.errors
      if (errors) {
        emailErrors.value = errors
      } else {
        toast.error(msg)
      }
      return false
    } finally {
      emailSending.value = false
    }
  }

  async function confirmEmailChange(otp: string): Promise<string | null> {
    emailConfirming.value = true
    emailErrors.value = {}
    try {
      const res = await commissionService.confirmEmailChange({ otp })
      emailOtpSent.value = false
      pendingNewEmail.value = ''
      toast.success('Đổi email thành công!')
      return (res.data.data as { email: string }).email
    } catch (err: unknown) {
      const e = err as ApiError
      const msg = e.response?.data?.message || 'Xác minh thất bại.'
      const errors = e.response?.data?.errors
      if (errors) {
        emailErrors.value = errors
      } else {
        toast.error(msg)
      }
      return null
    } finally {
      emailConfirming.value = false
    }
  }

  function resetEmailForm() {
    emailOtpSent.value = false
    emailErrors.value = {}
    pendingNewEmail.value = ''
  }

  return {
    // password
    pwOtpSent,
    pwSending,
    pwConfirming,
    pwErrors,
    sendPasswordOtp,
    confirmPasswordChange,
    resetPasswordForm,
    // email
    emailOtpSent,
    emailSending,
    emailConfirming,
    emailErrors,
    pendingNewEmail,
    sendEmailChangeOtp,
    confirmEmailChange,
    resetEmailForm,
  }
}
