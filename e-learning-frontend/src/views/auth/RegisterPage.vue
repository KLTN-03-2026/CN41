<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-12">
    <div class="w-full max-w-md">

      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 text-primary-600">
          <BookOpen class="w-8 h-8" />
          <span class="text-2xl font-bold text-gray-800">E-Learning</span>
        </div>
        <template v-if="!registered">
          <h2 class="mt-4 text-2xl font-bold text-gray-800">Tạo tài khoản</h2>
          <p class="mt-1 text-sm text-gray-500">Đăng ký để bắt đầu học tập ngay hôm nay</p>
        </template>
      </div>

      <!-- ── FORM ĐĂNG KÝ ── -->
      <div v-if="!registered" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <!-- Alert Error -->
        <div v-if="apiError" class="mb-5 p-4 bg-red-50 text-red-600 rounded-lg text-sm flex items-start gap-2">
          <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
          <span>{{ apiError }}</span>
        </div>

        <Form @submit="onSubmit" :validation-schema="schema" v-slot="{ errors, isSubmitting }">
          <!-- Họ tên -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Họ và tên</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <User class="w-5 h-5" />
              </div>
              <Field
                name="name"
                type="text"
                placeholder="Nguyễn Văn A"
                class="input-field pl-10"
                :class="{ 'input-error': errors.name }"
              />
            </div>
            <p class="error-msg" v-if="errors.name">{{ errors.name }}</p>
          </div>

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
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
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

          <!-- Xác nhận mật khẩu -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <Lock class="w-5 h-5" />
              </div>
              <Field
                name="password_confirmation"
                :type="showConfirm ? 'text' : 'password'"
                placeholder="Nhập lại mật khẩu"
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

          <!-- Nút đăng ký -->
          <button
            type="submit"
            class="btn-primary w-full flex justify-center items-center py-2.5 h-[42px]"
            :disabled="isSubmitting"
          >
            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-if="!isSubmitting">Đăng ký</span>
            <span v-else>Đang xử lý...</span>
          </button>
        </Form>

        <p class="mt-6 text-center text-sm text-gray-500">
          Đã có tài khoản?
          <router-link to="/login" class="text-primary-600 font-medium hover:underline">Đăng nhập</router-link>
        </p>
      </div>

      <!-- ── THÔNG BÁO CHECK MAIL ── -->
      <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
        <!-- Icon animated -->
        <div class="relative w-24 h-24 mx-auto mb-6">
          <div class="w-24 h-24 bg-primary-50 rounded-full flex items-center justify-center">
            <Mail class="w-12 h-12 text-primary-500" />
          </div>
          <!-- Badge check -->
          <div class="absolute -bottom-1 -right-1 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center border-2 border-white">
            <CheckCircle2 class="w-4 h-4 text-white" />
          </div>
        </div>

        <h2 class="text-2xl font-bold text-gray-800 mb-2">Kiểm tra hộp thư!</h2>
        <p class="text-gray-500 mb-1">Chúng tôi đã gửi email xác thực đến</p>
        <p class="font-semibold text-gray-800 mb-6">{{ registeredEmail }}</p>

        <!-- Hướng dẫn -->
        <div class="bg-blue-50 rounded-xl p-4 text-left mb-6 space-y-2">
          <p class="text-sm font-medium text-blue-800 mb-2">Làm theo các bước sau:</p>
          <div class="flex items-start gap-2 text-sm text-blue-700">
            <span class="w-5 h-5 bg-blue-200 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">1</span>
            <span>Mở email từ <strong>E-Learning</strong> trong hộp thư đến</span>
          </div>
          <div class="flex items-start gap-2 text-sm text-blue-700">
            <span class="w-5 h-5 bg-blue-200 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">2</span>
            <span>Nhấn nút <strong>"Xác thực email"</strong> trong email</span>
          </div>
          <div class="flex items-start gap-2 text-sm text-blue-700">
            <span class="w-5 h-5 bg-blue-200 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">3</span>
            <span>Quay lại đây và <strong>đăng nhập</strong> để bắt đầu học</span>
          </div>
        </div>

        <!-- Chú ý spam -->
        <p class="text-xs text-gray-400 mb-6">
          Không thấy email? Hãy kiểm tra thư mục <strong>Spam</strong> hoặc <strong>Junk Mail</strong>.
        </p>

        <router-link
          to="/login"
          class="btn-primary w-full flex justify-center items-center py-2.5 h-[42px] mb-3"
        >
          Đăng nhập
        </router-link>

        <router-link
          to="/"
          class="w-full flex justify-center items-center text-sm text-gray-500 hover:text-gray-700"
        >
          Về trang chủ
        </router-link>
      </div>

    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { Form, Field } from 'vee-validate'
import * as z from 'zod'
import { toTypedSchema } from '@vee-validate/zod'
import { BookOpen, User, Mail, Lock, Eye, EyeOff, AlertCircle, CheckCircle2 } from 'lucide-vue-next'
import { useStudentAuthStore } from '@/stores/studentAuth.store'

export default {
  components: { Form, Field, BookOpen, User, Mail, Lock, Eye, EyeOff, AlertCircle, CheckCircle2 },
  setup() {
    const studentStore = useStudentAuthStore()

    const showPassword    = ref(false)
    const showConfirm     = ref(false)
    const apiError        = ref('')
    const registered      = ref(false)
    const registeredEmail = ref('')

    const schema = toTypedSchema(
      z.object({
        name: z
          .string({ required_error: 'Vui lòng nhập họ tên' })
          .trim()
          .min(1, 'Vui lòng nhập họ tên')
          .min(2, 'Họ tên tối thiểu 2 ký tự'),
        email: z
          .string({ required_error: 'Vui lòng nhập email' })
          .min(1, 'Vui lòng nhập email')
          .email('Email không đúng định dạng'),
        password: z
          .string({ required_error: 'Vui lòng nhập mật khẩu' })
          .min(1, 'Vui lòng nhập mật khẩu')
          .min(8, 'Mật khẩu tối thiểu 8 ký tự'),
        password_confirmation: z
          .string({ required_error: 'Vui lòng xác nhận mật khẩu' })
          .min(1, 'Vui lòng xác nhận mật khẩu'),
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
      apiError.value = ''
      const result = await studentStore.register(values)

      if (result.success) {
        // Hiện màn hình "check mail" thay vì redirect
        registeredEmail.value = values.email
        registered.value = true
      } else {
        if (result.errors) {
          const firstError = Object.values(result.errors)[0]
          apiError.value = Array.isArray(firstError) ? firstError[0] : firstError
        } else {
          apiError.value = result.message || 'Đăng ký thất bại. Vui lòng thử lại.'
        }
      }
    }

    return { schema, onSubmit, showPassword, showConfirm, apiError, registered, registeredEmail }
  }
}
</script>
