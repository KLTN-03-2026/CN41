<template>
  <section class="bg-gray-50 py-16">
    <div class="max-w-6xl mx-auto px-4">
      <div class="mb-10">
        <h2 class="text-2xl font-bold text-gray-900">Tại sao chọn chúng tôi?</h2>
        <p class="mt-2 text-gray-500">Nền tảng học trực tuyến được thiết kế vì người học.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div
          v-for="(reason, index) in REASONS"
          :key="index"
          class="bg-white rounded-xl p-6 flex gap-4 shadow-sm hover:shadow-md transition-shadow duration-200"
        >
          <div
            :class="[
              'w-12 h-12 rounded-full flex-shrink-0 flex items-center justify-center',
              reason.bg,
            ]"
          >
            <component :is="reason.icon" :class="['w-6 h-6', reason.color]" />
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 mb-1">{{ reason.title }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{{ reason.desc }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { Target, GraduationCap, BadgeCheck, Zap } from 'lucide-vue-next'

const REASONS = [
  {
    icon: Target,
    title: 'Lộ trình học cá nhân hoá',
    desc: 'Hệ thống gợi ý khóa học phù hợp với mục tiêu và trình độ của từng học viên.',
    bg: 'bg-blue-50',
    color: 'text-blue-500',
  },
  {
    icon: GraduationCap,
    title: 'Giảng viên chuyên nghiệp',
    desc: 'Đội ngũ giảng viên giàu kinh nghiệm thực tế, được kiểm duyệt chặt chẽ.',
    bg: 'bg-green-50',
    color: 'text-green-500',
  },
  {
    icon: BadgeCheck,
    title: 'Chứng chỉ được công nhận',
    desc: 'Nhận chứng chỉ hoàn thành có giá trị sau mỗi khóa học.',
    bg: 'bg-purple-50',
    color: 'text-purple-500',
  },
  {
    icon: Zap,
    title: 'Học mọi lúc, mọi nơi',
    desc: 'Truy cập toàn bộ nội dung trên mọi thiết bị, không giới hạn thời gian.',
    bg: 'bg-yellow-50',
    color: 'text-yellow-500',
  },
]
</script>
