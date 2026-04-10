<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-12">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <router-link to="/" class="inline-flex items-center gap-2 text-primary-600 hover:opacity-80">
          <BookOpen class="w-8 h-8" />
          <span class="text-2xl font-bold text-gray-800">E-Learning</span>
        </router-link>
        <h2 class="mt-4 text-2xl font-bold text-gray-800">Đặt lại mật khẩu</h2>
        <p class="mt-2 text-sm text-gray-500">
          Vui lòng nhập mật khẩu mới cho tài khoản của bạn.
        </p>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <!-- Alert Error -->
        <div v-if="apiError" class="mb-5 p-4 bg-red-50 text-red-600 rounded-lg text-sm flex items-start gap-2">
          <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
          <span>{{ apiError }}</span>
        </div>

        <Form @submit="onSubmit" :validation-schema="schema" v-slot="{ errors, isSubmitting }">
          <!-- Email (readonly, lấy từ query params) -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <Mail class="w-5 h-5" />
              </div>
              <input
                type="email"
                :value="emailParam"
                readonly
                class="input-field pl-10 bg-gray-50 text-gray-500 cursor-not-allowed"
              />
            </div>
          </div>

          <!-- Mật khẩu mới -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <Lock class="w-5 h-5" />
              </div>
              <Field
                name="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Tối thiểu 8 ký tự"
                class="input-field pl-10 pr-10"
                :class="{ 'input-error': errors.password }"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                @click="showPassword = !showPassword"
              >
                <Eye class="w-5 h-5" v-if="!showPassword" />
                <EyeOff class="w-5 h-5" v-else />
              </button>
            </div>
            <p class="error-msg" v-if="errors.password">{{ errors.password }}</p>
          </div>

          <!-- Xác nhận mật khẩu mới -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu mới</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <Lock class="w-5 h-5" />
              </div>
              <Field
                name="password_confirmation"
                :type="showConfirm ? 'text' : 'password'"
                placeholder="Nhập lại mật khẩu mới"
                class="input-field pl-10 pr-10"
                :class="{ 'input-error': errors.password_confirmation }"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                @click="showConfirm = !showConfirm"
              >
                <Eye class="w-5 h-5" v-if="!showConfirm" />
                <EyeOff class="w-5 h-5" v-else />
              </button>
            </div>
            <p class="error-msg" v-if="errors.password_confirmation">{{ errors.password_confirmation }}</p>
          </div>

          <!-- Nút Đổi mật khẩu -->
          <button
            type="submit"
            class="btn-primary w-full flex justify-center items-center py-2.5 h-[42px]"
            :disabled="isSubmitting"
          >
            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-if="!isSubmitting">Đổi mật khẩu</span>
            <span v-else>Đang xử lý...</span>
          </button>
        </Form>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Form, Field } from 'vee-validate'
import * as z from 'zod'
import { toTypedSchema } from '@vee-validate/zod'
import { useToast } from 'vue-toastification'
import { BookOpen, Mail, Lock, Eye, EyeOff, AlertCircle } from 'lucide-vue-next'
import { authService } from '@/services/auth.service'

export default {
  components: { Form, Field, BookOpen, Mail, Lock, Eye, EyeOff, AlertCircle },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const toast = useToast()

    const apiError = ref('')
    const showPassword = ref(false)
    const showConfirm = ref(false)
    
    // Lấy token và email từ URL (Ví dụ: /reset-password?token=abc&email=test@example.com)
    const tokenParam = ref('')
    const emailParam = ref('')

    onMounted(() => {
      tokenParam.value = route.query.token || ''
      emailParam.value = route.query.email || ''

      if (!tokenParam.value) {
        apiError.value = 'Link đặt lại mật khẩu không hợp lệ hoặc đã qua chỉnh sửa.'
      }
    })

    const schema = toTypedSchema(
      z.object({
        password: z
          .string({ required_error: 'Vui lòng nhập mật khẩu mới' })
          .min(1, 'Vui lòng nhập mật khẩu mới')
          .min(8, 'Mật khẩu tối thiểu 8 ký tự'),
        password_confirmation: z
          .string({ required_error: 'Vui lòng xác nhận mật khẩu mới' })
          .min(1, 'Vui lòng xác nhận mật khẩu mới'),
      }).superRefine((data, ctx) => {
        if (data.password_confirmation && data.password !== data.password_confirmation) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: 'Mật khẩu xác nhận không khớp',
            path: ['password_confirmation'],
          })
        }
      })
    )

    const onSubmit = async (values) => {
      if (!tokenParam.value || !emailParam.value) {
        apiError.value = 'Thông tin xác thực không đầy đủ.'
        return
      }

      apiError.value = ''
      
      try {
        const payload = {
          token: tokenParam.value,
          email: emailParam.value,
          password: values.password,
          password_confirmation: values.password_confirmation,
        }
        await authService.resetPassword(payload)
        toast.success('Đặt lại mật khẩu thành công. Vui lòng đăng nhập bằng mật khẩu mới.')
        router.push('/login')
      } catch (err) {
        apiError.value = err.response?.data?.message || 'Có lỗi xảy ra khi đặt lại mật khẩu. Vui lòng thử lại sau.'
      }
    }

    return { 
      schema, 
      onSubmit, 
      apiError, 
      showPassword, 
      showConfirm, 
      emailParam 
    }
  }
}
</script>
