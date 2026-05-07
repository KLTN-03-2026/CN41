<template>
  <section v-if="!isLoggedIn" class="py-16 bg-gradient-to-br from-primary-600 to-blue-500">
    <div class="max-w-3xl mx-auto px-4 text-center text-white">
      <h2 class="text-2xl sm:text-3xl font-extrabold mb-3">Bắt đầu học ngay hôm nay</h2>
      <p class="text-primary-100 mb-8 text-sm sm:text-base">
        Tham gia cùng hàng nghìn học viên đang nâng cao kỹ năng và phát triển sự nghiệp trên
        E-Learning.
      </p>
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <router-link
          to="/register"
          class="px-8 py-3 bg-yellow-400 hover:bg-yellow-300 text-gray-900 font-semibold rounded-xl transition-colors text-sm"
        >
          Đăng ký miễn phí
        </router-link>
        <router-link
          to="/courses"
          class="px-8 py-3 bg-white/20 hover:bg-white/30 text-white font-semibold rounded-xl transition-colors text-sm border border-white/30"
        >
          Khám phá khóa học
        </router-link>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { useStudentAuthStore } from '@/stores/studentAuth.store'

const studentStore = useStudentAuthStore()
const isLoggedIn = studentStore.isLoggedIn
</script>
