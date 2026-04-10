<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-blue-50 px-4 py-12">
    <div class="w-full max-w-md">

      <!-- Logo -->
      <div class="text-center mb-8">
        <router-link to="/" class="inline-flex items-center gap-2 text-primary-600">
          <BookOpen class="w-8 h-8" />
          <span class="text-2xl font-bold text-gray-800">E-Learning</span>
        </router-link>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">

        <!-- SUCCESS -->
        <template v-if="status === 'success'">
          <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <CheckCircle2 class="w-10 h-10 text-green-500" />
          </div>
          <h2 class="text-2xl font-bold text-gray-800 mb-3">Xác thực thành công!</h2>
          <p class="text-gray-500 mb-8">
            Email của bạn đã được xác thực. Tài khoản đã được kích hoạt, bạn có thể bắt đầu học ngay.
          </p>
          <button @click="goToLogin" class="btn-primary w-full flex justify-center items-center py-3 rounded-xl font-semibold">
            Đăng nhập ngay
          </button>
        </template>

        <!-- ALREADY VERIFIED -->
        <template v-else-if="status === 'already'">
          <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <ShieldCheck class="w-10 h-10 text-blue-500" />
          </div>
          <h2 class="text-2xl font-bold text-gray-800 mb-3">Đã xác thực trước đó</h2>
          <p class="text-gray-500 mb-8">
            Email này đã được xác thực rồi. Bạn có thể đăng nhập bình thường.
          </p>
          <button @click="goToLogin" class="btn-primary w-full flex justify-center items-center py-3 rounded-xl font-semibold">
            Đăng nhập
          </button>
        </template>

        <!-- EXPIRED -->
        <template v-else-if="status === 'expired'">
          <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <Clock class="w-10 h-10 text-amber-500" />
          </div>
          <h2 class="text-2xl font-bold text-gray-800 mb-3">Link đã hết hạn</h2>
          <p class="text-gray-500 mb-8">
            Link xác thực chỉ có hiệu lực trong <strong>24 giờ</strong> và đã hết hạn.
            Vui lòng đăng nhập để yêu cầu gửi lại link mới.
          </p>
          <router-link to="/login" class="btn-primary w-full flex justify-center items-center py-3 rounded-xl font-semibold">
            Đăng nhập để gửi lại
          </router-link>
        </template>

        <!-- INVALID / DEFAULT -->
        <template v-else>
          <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <XCircle class="w-10 h-10 text-red-500" />
          </div>
          <h2 class="text-2xl font-bold text-gray-800 mb-3">Link không hợp lệ</h2>
          <p class="text-gray-500 mb-8">
            Link xác thực không hợp lệ hoặc đã được sử dụng.
            Vui lòng đăng nhập để yêu cầu gửi lại link mới.
          </p>
          <router-link to="/login" class="btn-primary w-full flex justify-center items-center py-3 rounded-xl font-semibold">
            Về trang đăng nhập
          </router-link>
        </template>

      </div>

      <!-- Footer -->
      <p class="text-center text-sm text-gray-400 mt-6">
        Cần hỗ trợ?
        <a href="mailto:support@elearning.com" class="text-primary-600 hover:underline">Liên hệ chúng tôi</a>
      </p>

    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { BookOpen, CheckCircle2, ShieldCheck, Clock, XCircle } from 'lucide-vue-next'
import { useStudentAuthStore } from '@/stores/studentAuth.store'

const route = useRoute()
const router = useRouter()
const studentStore = useStudentAuthStore()

const status = computed(() => route.query.status as string || 'invalid')

// Khi xác thực thành công, logout token cũ (chưa verified) để user đăng nhập lại sạch
onMounted(async () => {
  if (status.value === 'success' && studentStore.isLoggedIn) {
    await studentStore.logout()
  }
})

const goToLogin = () => router.push('/login')
</script>
