<template>
  <div class="max-w-[720px] mx-auto px-4 lg:px-8 py-8">
    <!-- Profile header -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6 flex items-center gap-5">
      <!-- Avatar -->
      <div class="relative shrink-0">
        <div
          class="w-20 h-20 rounded-full overflow-hidden bg-primary-100 border-2 border-primary-200 flex items-center justify-center"
        >
          <img
            v-if="studentStore.student?.avatar"
            :src="studentStore.student.avatar"
            alt="Avatar"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-2xl font-bold text-primary-600">
            {{ studentStore.fullName?.charAt(0).toUpperCase() || 'U' }}
          </span>
        </div>

        <!-- Upload overlay -->
        <label
          class="absolute inset-0 rounded-full flex items-center justify-center bg-black/40 opacity-0 hover:opacity-100 transition-opacity cursor-pointer"
          :class="{ 'opacity-100 cursor-wait': uploadingAvatar }"
        >
          <input
            type="file"
            accept="image/jpg,image/jpeg,image/png,image/webp"
            class="hidden"
            @change="onAvatarFileChange"
            :disabled="uploadingAvatar"
          />
          <svg
            v-if="!uploadingAvatar"
            class="w-5 h-5 text-white"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"
            />
          </svg>
          <svg v-else class="w-5 h-5 text-white animate-spin" fill="none" viewBox="0 0 24 24">
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
            />
          </svg>
        </label>
      </div>

      <!-- Name & email -->
      <div class="min-w-0">
        <p class="text-lg font-semibold text-gray-900 truncate">
          {{ studentStore.fullName || 'Người dùng' }}
        </p>
        <p class="text-sm text-gray-500 truncate">{{ studentStore.student?.email }}</p>
        <p class="text-xs text-gray-400 mt-0.5">Bấm vào ảnh để thay đổi avatar</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 bg-gray-100 p-1 rounded-xl mb-6">
      <button
        @click="activeTab = 'info'"
        class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors"
        :class="
          activeTab === 'info'
            ? 'bg-white text-gray-900 shadow-sm'
            : 'text-gray-500 hover:text-gray-700'
        "
      >
        Thông tin cá nhân
      </button>
      <button
        @click="activeTab = 'security'"
        class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors"
        :class="
          activeTab === 'security'
            ? 'bg-white text-gray-900 shadow-sm'
            : 'text-gray-500 hover:text-gray-700'
        "
      >
        Bảo mật
      </button>
    </div>

    <!-- Tab: Thông tin cá nhân -->
    <div v-if="activeTab === 'info'" class="bg-white rounded-2xl border border-gray-100 p-6">
      <h2 class="text-base font-semibold text-gray-800 mb-5">Thông tin cá nhân</h2>

      <form @submit.prevent="saveProfile" class="space-y-4">
        <!-- Họ và tên -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Họ và tên</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="Nhập họ và tên"
            class="w-full px-4 py-2.5 rounded-xl border text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            :class="
              infoErrors.name
                ? 'border-red-400 bg-red-50'
                : 'border-gray-200 bg-gray-50 focus:border-primary-400'
            "
          />
          <p v-if="infoErrors.name" class="mt-1 text-xs text-red-500">{{ infoErrors.name[0] }}</p>
        </div>

        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="Nhập email"
            class="w-full px-4 py-2.5 rounded-xl border text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            :class="
              infoErrors.email
                ? 'border-red-400 bg-red-50'
                : 'border-gray-200 bg-gray-50 focus:border-primary-400'
            "
          />
          <p v-if="infoErrors.email" class="mt-1 text-xs text-red-500">{{ infoErrors.email[0] }}</p>
        </div>

        <!-- Ngày sinh -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Ngày sinh</label>
          <input
            v-model="form.date_of_birth"
            type="date"
            class="w-full px-4 py-2.5 rounded-xl border text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            :class="
              infoErrors.date_of_birth
                ? 'border-red-400 bg-red-50'
                : 'border-gray-200 bg-gray-50 focus:border-primary-400'
            "
          />
          <p v-if="infoErrors.date_of_birth" class="mt-1 text-xs text-red-500">
            {{ infoErrors.date_of_birth[0] }}
          </p>
        </div>

        <!-- Submit -->
        <div class="pt-2">
          <button
            type="submit"
            :disabled="saving"
            class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
          >
            <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
              />
            </svg>
            {{ saving ? 'Đang lưu...' : 'Lưu thay đổi' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Tab: Bảo mật -->
    <div v-if="activeTab === 'security'" class="bg-white rounded-2xl border border-gray-100 p-6">
      <h2 class="text-base font-semibold text-gray-800 mb-5">Đổi mật khẩu</h2>

      <!-- Thành công -->
      <div
        v-if="passwordEmailSent"
        class="mb-5 flex items-start gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl"
      >
        <svg
          class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
          />
        </svg>
        <div>
          <p class="text-sm font-medium text-emerald-800">Email xác nhận đã được gửi!</p>
          <p class="text-xs text-emerald-600 mt-0.5">
            Vui lòng kiểm tra hộp thư và bấm vào link để hoàn tất đổi mật khẩu.
          </p>
        </div>
      </div>

      <form @submit.prevent="submitChangePassword" class="space-y-4">
        <!-- Mật khẩu hiện tại -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Mật khẩu hiện tại</label>
          <input
            v-model="passwordForm.current_password"
            type="password"
            placeholder="Nhập mật khẩu hiện tại"
            class="w-full px-4 py-2.5 rounded-xl border text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            :class="
              passwordErrors.current_password
                ? 'border-red-400 bg-red-50'
                : 'border-gray-200 bg-gray-50 focus:border-primary-400'
            "
          />
          <p v-if="passwordErrors.current_password" class="mt-1 text-xs text-red-500">
            {{ passwordErrors.current_password[0] }}
          </p>
        </div>

        <!-- Mật khẩu mới -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Mật khẩu mới</label>
          <input
            v-model="passwordForm.new_password"
            type="password"
            placeholder="Tối thiểu 8 ký tự"
            class="w-full px-4 py-2.5 rounded-xl border text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            :class="
              passwordErrors.new_password
                ? 'border-red-400 bg-red-50'
                : 'border-gray-200 bg-gray-50 focus:border-primary-400'
            "
          />
          <p v-if="passwordErrors.new_password" class="mt-1 text-xs text-red-500">
            {{ passwordErrors.new_password[0] }}
          </p>
        </div>

        <!-- Xác nhận mật khẩu mới -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5"
            >Xác nhận mật khẩu mới</label
          >
          <input
            v-model="passwordForm.new_password_confirmation"
            type="password"
            placeholder="Nhập lại mật khẩu mới"
            class="w-full px-4 py-2.5 rounded-xl border text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            :class="
              passwordErrors.new_password_confirmation
                ? 'border-red-400 bg-red-50'
                : 'border-gray-200 bg-gray-50 focus:border-primary-400'
            "
          />
          <p v-if="passwordErrors.new_password_confirmation" class="mt-1 text-xs text-red-500">
            {{ passwordErrors.new_password_confirmation[0] }}
          </p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-2">
          <router-link to="/forgot-password" class="text-sm text-primary-600 hover:underline">
            Quên mật khẩu?
          </router-link>
          <button
            type="submit"
            :disabled="sendingPasswordEmail"
            class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
          >
            <svg
              v-if="sendingPasswordEmail"
              class="w-4 h-4 animate-spin"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
              />
            </svg>
            {{ sendingPasswordEmail ? 'Đang gửi...' : 'Gửi xác nhận qua email' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useStudentAuthStore } from '@/stores/studentAuth.store'
import { useProfile } from '@/composables/useProfile'

const studentStore = useStudentAuthStore()

const {
  activeTab,
  saving,
  uploadingAvatar,
  sendingPasswordEmail,
  infoErrors,
  passwordErrors,
  passwordEmailSent,
  form,
  passwordForm,
  saveProfile,
  handleAvatarChange,
  submitChangePassword,
} = useProfile()

function onAvatarFileChange(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (file) handleAvatarChange(file)
}
</script>
