<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-12">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <router-link to="/" class="inline-flex items-center gap-2 text-primary-600 hover:opacity-80">
          <BookOpen class="w-8 h-8" />
          <span class="text-2xl font-bold text-gray-800">E-Learning</span>
        </router-link>
        <h2 class="mt-4 text-2xl font-bold text-gray-800">Quên mật khẩu</h2>
        <p class="mt-2 text-sm text-gray-500">
          Nhập email của bạn và chúng tôi sẽ gửi link đặt lại mật khẩu.
        </p>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <!-- Alert Success -->
        <div v-if="successMessage" class="mb-5 p-4 bg-green-50 text-green-700 rounded-lg text-sm flex items-start gap-2">
          <CheckCircle2 class="w-5 h-5 shrink-0 mt-0.5 text-green-500" />
          <span>{{ successMessage }}</span>
        </div>

        <!-- Alert Error -->
        <div v-else-if="apiError" class="mb-5 p-4 bg-red-50 text-red-600 rounded-lg text-sm flex items-start gap-2">
          <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
          <span>{{ apiError }}</span>
        </div>

        <Form @submit="onSubmit" :validation-schema="schema" v-slot="{ errors, isSubmitting }">
          <!-- Email -->
          <div class="mb-6" v-if="!successMessage">
            <label class="block text-sm font-medium text-gray-700 mb-1">Email của bạn</label>
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

          <!-- Nút gửi link -->
          <button
            v-if="!successMessage"
            type="submit"
            class="btn-primary w-full flex justify-center items-center py-2.5 h-[42px]"
            :disabled="isSubmitting"
          >
            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-if="!isSubmitting">Gửi link đặt lại mật khẩu</span>
            <span v-else>Đang xử lý...</span>
          </button>
        </Form>

        <div class="mt-6 text-center">
          <router-link to="/login" class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline inline-flex items-center gap-1">
            <ArrowLeft class="w-4 h-4" />
            Quay lại đăng nhập
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { Form, Field } from 'vee-validate'
import * as z from 'zod'
import { toTypedSchema } from '@vee-validate/zod'
import { BookOpen, Mail, AlertCircle, CheckCircle2, ArrowLeft } from 'lucide-vue-next'
import { authService } from '@/services/auth.service'

export default {
  components: { Form, Field, BookOpen, Mail, AlertCircle, CheckCircle2, ArrowLeft },
  setup() {
    const apiError = ref('')
    const successMessage = ref('')

    const schema = toTypedSchema(
      z.object({
        email: z.string().min(1, 'Vui lòng nhập email').email('Email không đúng định dạng'),
      })
    )

    const onSubmit = async (values) => {
      apiError.value = ''
      successMessage.value = ''
      
      try {
        const res = await authService.forgotPassword(values.email)
        successMessage.value = res.data?.message || 'Link đặt lại mật khẩu đã được gửi.'
      } catch (err) {
        apiError.value = err.response?.data?.message || 'Không thể tạo yêu cầu quên mật khẩu. Vui lòng thử lại.'
      }
    }

    return { schema, onSubmit, apiError, successMessage }
  }
}
</script>
