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
        <!-- Alert Error -->
        <div v-if="apiError" class="mb-5 p-4 bg-red-50 text-red-600 rounded-lg text-sm flex items-start gap-2">
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

<script>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Form, Field } from 'vee-validate'
import * as z from 'zod'
import { toTypedSchema } from '@vee-validate/zod'
import { useToast } from 'vue-toastification'
import { BookOpen, Mail, Lock, Eye, EyeOff, AlertCircle } from 'lucide-vue-next'
import { useStudentAuthStore } from '@/stores/studentAuth.store'

export default {
  components: { Form, Field, BookOpen, Mail, Lock, Eye, EyeOff, AlertCircle },
  setup() {
    const router = useRouter()
    const route  = useRoute()
    const toast  = useToast()
    const studentStore = useStudentAuthStore()

    const showPassword = ref(false)
    const keepLoggedIn = ref(false)
    const apiError     = ref('')

    const schema = toTypedSchema(
      z.object({
        email:    z.string().min(1, 'Vui lòng nhập email').email('Email không đúng định dạng'),
        password: z.string().min(1, 'Vui lòng nhập mật khẩu').min(6, 'Mật khẩu phải có ít nhất 6 ký tự'),
      })
    )

    const onSubmit = async (values) => {
      apiError.value = ''

      // Chỉ gọi student API — không fallback sang admin
      // (fallback gây 2 request / lần submit → đốt throttle:5,1 rất nhanh → 429)
      const result = await studentStore.login(values.email, values.password, keepLoggedIn.value)

      if (result.success) {
        toast.success('Đăng nhập thành công!')
        const redirect = route.query.redirect || '/'
        router.push(redirect)
        return
      }

      // Hiện message từ server (401, 403 email_not_verified, v.v.)
      apiError.value = result.message || 'Email hoặc mật khẩu không chính xác.'
    }

    return { schema, onSubmit, showPassword, keepLoggedIn, apiError }
  }
}
</script>
