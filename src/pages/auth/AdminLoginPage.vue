<template>
  <div class="min-h-screen flex text-gray-800">
    <!-- Cột trái (Desktop) -->
    <div class="hidden lg:flex w-[40%] bg-primary-600 text-white flex-col justify-center items-center p-12 relative overflow-hidden">
      <!-- Decor circle -->
      <div class="absolute -top-20 -left-20 w-80 h-80 rounded-full bg-white opacity-5"></div>
      <div class="absolute -bottom-20 -right-20 w-96 h-96 rounded-full bg-white opacity-5"></div>
      
      <div class="z-10 text-center">
        <BookOpen class="w-20 h-20 mx-auto border-2 border-white rounded-2xl p-4 mb-6" />
        <h1 class="text-4xl font-bold mb-4">E-Learning Marketplace</h1>
        <p class="text-primary-100 text-lg">
          Hệ thống quản trị giáo dục trực tuyến toàn diện, hiệu quả.
        </p>
      </div>
    </div>

    <!-- Cột phải (Form) -->
    <div class="w-full lg:w-[60%] flex flex-col justify-center px-6 py-12 lg:px-24 bg-gray-50">
      <div class="max-w-md w-full mx-auto bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
        <!-- Logo on mobile -->
        <div class="flex lg:hidden items-center justify-center gap-2 mb-8 text-primary-600">
          <BookOpen class="w-8 h-8" />
          <span class="text-2xl font-bold text-gray-800">E-Learning Admin</span>
        </div>
        
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Đăng nhập Quản trị</h2>
        <p class="text-gray-500 text-sm mb-8">Vui lòng nhập email và mật khẩu để tiếp tục</p>

        <!-- Alert Error -->
        <div v-if="apiError" class="mb-6 p-4 bg-red-50 text-red-600 rounded-lg text-sm flex items-start gap-2">
          <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
          <span>{{ apiError }}</span>
        </div>

        <Form @submit="onSubmit" :validation-schema="schema" v-slot="{ errors, isSubmitting }">
          <!-- Email -->
          <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <Mail class="w-5 h-5" />
              </div>
              <Field 
                name="email" 
                type="email" 
                placeholder="admin@example.com"
                class="input-field pl-10"
                :class="{ 'input-error': errors.email }"
              />
            </div>
            <p class="error-msg" v-if="errors.email">{{ errors.email }}</p>
          </div>

          <!-- Mật khẩu -->
          <div class="mb-8">
            <div class="flex items-center justify-between mb-1">
              <label class="block text-sm font-medium text-gray-700">Mật khẩu</label>
            </div>
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
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Form, Field } from 'vee-validate'
import * as z from 'zod'
import { toTypedSchema } from '@vee-validate/zod'
import { useToast } from 'vue-toastification'
import { Mail, Lock, Eye, EyeOff, BookOpen } from 'lucide-vue-next'
import { useAdminAuthStore } from '@/stores/adminAuth'

export default {
  components: {
    Form, Field,
    Mail, Lock, Eye, EyeOff, BookOpen
  },
  setup() {
    const router = useRouter()
    const toast = useToast()
    const adminStore = useAdminAuthStore()
    
    const showPassword = ref(false)
    const apiError = ref('')

    // Schema validation
    const schema = toTypedSchema(
      z.object({
        email: z.string().min(1, 'Vui lòng nhập email').email('Email không đúng định dạng'),
        password: z.string().min(1, 'Vui lòng nhập mật khẩu')
      })
    )

    // Chuyển hướng nếu đã có token
    onMounted(() => {
      if (adminStore.token) {
        router.push('/admin/dashboard')
      }
    })

    const onSubmit = async (values) => {
      apiError.value = ''
      const result = await adminStore.login(values.email, values.password)
      
      if (result.success) {
        toast.success('Đăng nhập quản trị thành công!')
        router.push('/admin/dashboard')
      } else {
        apiError.value = result.message || 'Email hoặc mật khẩu không chính xác.'
      }
    }

    return {
      schema,
      onSubmit,
      showPassword,
      apiError
    }
  }
}
</script>
