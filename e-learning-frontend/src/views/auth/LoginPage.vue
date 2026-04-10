<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-12">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 text-primary-600">
          <BookOpen class="w-8 h-8" />
          <span class="text-2xl font-bold text-gray-800">E-Learning</span>
        </div>
        <h2 class="mt-4 text-2xl font-bold text-gray-800">Đăng nhập</h2>
        <p class="mt-1 text-sm text-gray-500">Chào mừng bạn trở lại!</p>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <!-- Alert: email chưa xác thực -->
        <div v-if="emailNotVerified" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
          <div class="flex items-start gap-3">
            <MailWarning class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" />
            <div class="flex-1">
              <p class="text-sm font-semibold text-amber-800 mb-1">Tài khoản chưa được xác thực</p>
              <p class="text-sm text-amber-700 mb-3">
                Vui lòng kiểm tra hộp thư <strong>{{ unverifiedEmail }}</strong> và nhấn link xác thực để kích hoạt tài khoản.
              </p>
              <button
                type="button"
                @click="resendVerification"
                :disabled="isResending || resendCooldown > 0"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-amber-800 bg-amber-100 hover:bg-amber-200 disabled:opacity-60 disabled:cursor-not-allowed px-3 py-1.5 rounded-lg transition-colors"
              >
                <svg v-if="isResending" class="animate-spin w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                <RefreshCw v-else class="w-3.5 h-3.5" />
                <span v-if="isResending">Đang gửi...</span>
                <span v-else-if="resendCooldown > 0">Gửi lại sau {{ resendCooldown }}s</span>
                <span v-else>Gửi lại email xác thực</span>
              </button>
              <p v-if="resendSuccess" class="mt-2 text-xs text-green-700 font-medium">✓ Email xác thực đã được gửi lại!</p>
            </div>
          </div>
        </div>

        <!-- Alert Error thông thường -->
        <div v-else-if="apiError" class="mb-5 p-4 bg-red-50 text-red-600 rounded-lg text-sm flex items-start gap-2">
          <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
          <span>{{ apiError }}</span>
        </div>

        <Form @submit="onSubmit" :validation-schema="schema" v-slot="{ errors, isSubmitting }">
          <!-- Email -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <Mail class="w-5 h-5" />
              </div>
              <Field
                name="email"
                type="email"
                placeholder="you@example.com"
                class="input-field pl-10"
                :class="{ 'input-error': errors.email }"
              />
            </div>
            <p class="error-msg" v-if="errors.email">{{ errors.email }}</p>
          </div>

          <!-- Mật khẩu -->
          <div class="mb-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <Lock class="w-5 h-5" />
              </div>
              <Field
                name="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
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

          <!-- Ghi nhớ đăng nhập + Quên mật khẩu -->
          <div class="flex items-center justify-between mb-6">
            <label for="keepLoggedIn" class="flex items-center text-sm text-gray-600 cursor-pointer select-none">
              <input
                v-model="keepLoggedIn"
                type="checkbox"
                id="keepLoggedIn"
                class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2"
              />
              Ghi nhớ đăng nhập
            </label>
            <router-link to="/forgot-password" class="text-sm text-primary-600 hover:underline">
              Quên mật khẩu?
            </router-link>
          </div>

          <!-- Nút đăng nhập -->
          <button
            type="submit"
            class="btn-primary w-full flex justify-center items-center py-2.5 h-[42px]"
            :disabled="isSubmitting"
          >
            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-if="!isSubmitting">Đăng nhập</span>
            <span v-else>Đang xử lý...</span>
          </button>
        </Form>

        <p class="mt-6 text-center text-sm text-gray-500">
          Chưa có tài khoản?
          <router-link to="/register" class="text-primary-600 font-medium hover:underline">Đăng ký ngay</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Form, Field } from 'vee-validate'
import * as z from 'zod'
import { toTypedSchema } from '@vee-validate/zod'
import { useToast } from 'vue-toastification'
import { BookOpen, Mail, Lock, Eye, EyeOff, AlertCircle, MailWarning, RefreshCw } from 'lucide-vue-next'
import { useStudentAuthStore } from '@/stores/studentAuth.store'
import { authService } from '@/services/auth.service'

export default {
  components: { Form, Field, BookOpen, Mail, Lock, Eye, EyeOff, AlertCircle, MailWarning, RefreshCw },
  setup() {
    const router = useRouter()
    const route  = useRoute()
    const toast  = useToast()
    const studentStore = useStudentAuthStore()

    const showPassword    = ref(false)
    const keepLoggedIn    = ref(false)
    const apiError        = ref('')
    const emailNotVerified = ref(false)
    const unverifiedEmail  = ref('')
    const isResending      = ref(false)
    const resendSuccess    = ref(false)
    const resendCooldown   = ref(0)

    const schema = toTypedSchema(
      z.object({
        email:    z.string().min(1, 'Vui lòng nhập email').email('Email không đúng định dạng'),
        password: z.string().min(1, 'Vui lòng nhập mật khẩu').min(6, 'Mật khẩu phải có ít nhất 6 ký tự'),
      })
    )

    const startCooldown = () => {
      resendCooldown.value = 60
      const timer = setInterval(() => {
        resendCooldown.value--
        if (resendCooldown.value <= 0) clearInterval(timer)
      }, 1000)
    }

    const resendVerification = async () => {
      if (!unverifiedEmail.value || isResending.value || resendCooldown.value > 0) return
      isResending.value = true
      resendSuccess.value = false
      try {
        await authService.studentResendVerification({ email: unverifiedEmail.value })
        resendSuccess.value = true
        startCooldown()
      } catch (err) {
        toast.error(err?.response?.data?.message || 'Không thể gửi lại email. Vui lòng thử lại sau.')
      } finally {
        isResending.value = false
      }
    }

    const onSubmit = async (values) => {
      apiError.value = ''
      emailNotVerified.value = false
      resendSuccess.value = false

      const result = await studentStore.login(values.email, values.password, keepLoggedIn.value)

      if (result.success) {
        toast.success('Đăng nhập thành công!')
        const redirect = route.query.redirect || '/'
        router.push(redirect)
        return
      }

      // Trường hợp đặc biệt: email chưa xác thực
      if (result.errors?.email_not_verified) {
        emailNotVerified.value = true
        unverifiedEmail.value = result.errors?.email || values.email
        return
      }

      apiError.value = result.message || 'Email hoặc mật khẩu không chính xác.'
    }

    return {
      schema, onSubmit,
      showPassword, keepLoggedIn, apiError,
      emailNotVerified, unverifiedEmail,
      isResending, resendSuccess, resendCooldown,
      resendVerification,
    }
  }
}
</script>
