<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-12">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 text-primary-600">
          <BookOpen class="w-8 h-8" />
          <span class="text-2xl font-bold text-gray-800">E-Learning</span>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
        <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
          <Mail class="w-8 h-8" />
        </div>
        
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Xác thực Email</h2>
        
        <!-- Trạng thái success (nếu báo đã gửi lại) -->
        <div v-if="successMessage" class="mb-6 p-4 bg-green-50 text-green-700 rounded-lg text-sm flex items-start gap-2 text-left">
          <CheckCircle2 class="w-5 h-5 shrink-0 mt-0.5 text-green-500" />
          <span>{{ successMessage }}</span>
        </div>

        <!-- Cảnh báo chưa xác thực -->
        <p class="text-gray-600 mb-6" v-else>
          Tài khoản <strong>{{ studentEmail }}</strong> của bạn hiện chưa được xác thực. <br/>
          Vui lòng kiểm tra hộp thư đến (và thư rác) để hoàn tất đăng ký.
        </p>

        <!-- Lỗi api nếu có -->
         <div v-if="apiError" class="mb-4 p-3 bg-red-50 text-red-600 rounded-lg text-sm text-left flex items-start gap-2">
          <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
          <span>{{ apiError }}</span>
        </div>

        <button
          @click="resendVerification"
          class="btn-primary w-full flex justify-center items-center py-2.5 h-[42px] mb-4"
          :disabled="isResending"
        >
          <svg v-if="isResending" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span v-if="!isResending">Gửi lại email xác thực</span>
          <span v-else>Đang gửi...</span>
        </button>

        <button
          @click="handleLogout"
          class="w-full text-sm font-medium text-gray-500 hover:text-gray-700 underline"
        >
          Đăng xuất và sử dụng tài khoản khác
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { BookOpen, Mail, CheckCircle2, AlertCircle } from 'lucide-vue-next'
import { useStudentAuthStore } from '@/stores/studentAuth.store'
import { authService } from '@/services/auth.service'

export default {
  components: { BookOpen, Mail, CheckCircle2, AlertCircle },
  setup() {
    const router = useRouter()
    const toast = useToast()
    const studentStore = useStudentAuthStore()

    const isResending = ref(false)
    const successMessage = ref('')
    const apiError = ref('')

    const studentEmail = computed(() => studentStore.student?.email || '')

    const resendVerification = async () => {
      if (!studentEmail.value) {
        apiError.value = 'Không tìm thấy email người dùng. Vui lòng đăng nhập lại.'
        return
      }

      isResending.value = true
      apiError.value = ''
      successMessage.value = ''

      try {
        const res = await authService.studentResendVerification({ email: studentEmail.value })
        successMessage.value = res.data?.message || 'Đã gửi lại email xác thực. Vui lòng kiểm tra hòm thư.'
        toast.success('Gửi email thành công!')
      } catch (err) {
        apiError.value = err.response?.data?.message || 'Không thể gửi lại email xác thực.'
      } finally {
        isResending.value = false
      }
    }

    const handleLogout = async () => {
      await studentStore.logout()
      router.push('/login')
    }

    return {
      studentEmail,
      isResending,
      successMessage,
      apiError,
      resendVerification,
      handleLogout
    }
  }
}
</script>
