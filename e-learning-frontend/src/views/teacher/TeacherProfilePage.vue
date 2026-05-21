<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Hồ sơ cá nhân</h1>

    <div v-if="loading" class="text-center py-12 text-gray-400">Đang tải...</div>

    <div v-else-if="profile" class="max-w-2xl space-y-6">
      <!-- Basic info (read-only) -->
      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Thông tin cơ bản</h2>
        <div class="flex items-center gap-4 mb-4">
          <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-2xl font-bold text-blue-700 dark:text-blue-300">
            {{ profile.name.charAt(0).toUpperCase() }}
          </div>
          <div>
            <p class="font-semibold text-gray-900 dark:text-white text-lg">{{ profile.name }}</p>
            <p class="text-sm text-gray-500">Giảng viên</p>
          </div>
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Giới thiệu bản thân</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Mô tả ngắn về bản thân, kinh nghiệm giảng dạy..."
            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <!-- Bank info -->
      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-1">Thông tin ngân hàng</h2>
        <p class="text-xs text-gray-500 mb-4">Dùng để Admin chuyển khoản khi duyệt yêu cầu rút tiền</p>
        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tên ngân hàng</label>
            <input
              v-model="form.bank_name"
              type="text"
              placeholder="VD: Vietcombank, Techcombank..."
              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Số tài khoản</label>
            <input
              v-model="form.bank_account_number"
              type="text"
              placeholder="Số tài khoản ngân hàng"
              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tên chủ tài khoản</label>
            <input
              v-model="form.bank_account_name"
              type="text"
              placeholder="VD: NGUYEN VAN A (viết hoa, không dấu)"
              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
      </div>

      <div class="flex justify-end">
        <button
          @click="submit"
          :disabled="saving"
          class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
        >
          {{ saving ? 'Đang lưu...' : 'Lưu thay đổi' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, onMounted, watch } from 'vue'
import { useTeacherProfile } from '@/composables/useTeacherProfile'

const { profile, loading, saving, loadProfile, saveProfile } = useTeacherProfile()

const form = reactive({
  description: '',
  bank_name: '',
  bank_account_number: '',
  bank_account_name: '',
})

watch(profile, (p) => {
  if (p) {
    form.description = p.description || ''
    form.bank_name = p.bank_name || ''
    form.bank_account_number = p.bank_account_number || ''
    form.bank_account_name = p.bank_account_name || ''
  }
}, { immediate: true })

async function submit() {
  await saveProfile({ ...form })
}

onMounted(() => loadProfile())
</script>
