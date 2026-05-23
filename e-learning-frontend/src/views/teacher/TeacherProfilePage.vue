<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Hồ sơ cá nhân</h1>

    <div v-if="loading" class="text-center py-12 text-gray-400">Đang tải...</div>

    <div v-else-if="profile" class="max-w-2xl space-y-6">
      <!-- Basic info (read-only) -->
      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Thông tin cơ bản</h2>
        <div class="flex items-center gap-4 mb-4">
          <div class="relative w-24 h-24 group">
            <img v-if="form.image" :src="form.image" alt="Avatar" class="w-24 h-24 rounded-full object-cover border border-gray-200 dark:border-gray-700" />
            <div v-else class="w-24 h-24 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-3xl font-bold text-blue-700 dark:text-blue-300">
              {{ profile.name.charAt(0).toUpperCase() }}
            </div>
            
            <button
              @click="avatarInput?.click()"
              class="absolute bottom-0 right-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-blue-700 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
            <input type="file" ref="avatarInput" accept="image/*" class="hidden" @change="uploadAvatar" />
          </div>
          <div>
            <p class="font-semibold text-gray-900 dark:text-white text-lg">{{ profile.name }}</p>
            <p class="text-sm text-gray-500">{{ profile.email }}</p>
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

      <!-- Change password -->
      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-1">Đổi mật khẩu</h2>
        <p class="text-xs text-gray-500 mb-4">
          Mã xác minh sẽ được gửi đến: <strong class="text-gray-700 dark:text-gray-300">{{ profile.email }}</strong>
        </p>

        <div v-if="!pwOtpSent">
          <button
            @click="sendPasswordOtp"
            :disabled="pwSending"
            class="px-4 py-2 bg-gray-800 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
          >
            {{ pwSending ? 'Đang gửi...' : 'Gửi mã xác minh' }}
          </button>
        </div>

        <div v-else class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mã xác minh</label>
            <div class="flex gap-2">
              <input
                v-model="pwForm.otp"
                type="text"
                maxlength="6"
                placeholder="6 chữ số"
                class="flex-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="{ 'border-red-500': pwErrors.otp }"
              />
              <button
                @click="sendPasswordOtp"
                :disabled="pwSending"
                class="px-3 py-2 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 border border-blue-300 rounded-lg disabled:opacity-50 whitespace-nowrap"
              >
                {{ pwSending ? '...' : 'Gửi lại' }}
              </button>
            </div>
            <p v-if="pwErrors.otp" class="mt-1 text-xs text-red-500">{{ pwErrors.otp[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mật khẩu mới</label>
            <input
              v-model="pwForm.password"
              type="password"
              placeholder="Ít nhất 8 ký tự"
              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': pwErrors.password }"
            />
            <p v-if="pwErrors.password" class="mt-1 text-xs text-red-500">{{ pwErrors.password[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Xác nhận mật khẩu mới</label>
            <input
              v-model="pwForm.password_confirmation"
              type="password"
              placeholder="Nhập lại mật khẩu mới"
              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div class="flex gap-2 pt-1">
            <button
              @click="resetPasswordForm(); clearPwForm()"
              class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            >
              Hủy
            </button>
            <button
              @click="submitPasswordChange"
              :disabled="pwConfirming"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
            >
              {{ pwConfirming ? 'Đang xác nhận...' : 'Xác nhận đổi mật khẩu' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Change email -->
      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-1">Đổi email</h2>
        <p class="text-xs text-gray-500 mb-4">Mã xác minh sẽ được gửi đến địa chỉ email mới bạn nhập</p>

        <div v-if="!emailOtpSent" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email mới</label>
            <input
              v-model="emailForm.new_email"
              type="email"
              placeholder="example@email.com"
              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': emailErrors.new_email }"
            />
            <p v-if="emailErrors.new_email" class="mt-1 text-xs text-red-500">{{ emailErrors.new_email[0] }}</p>
          </div>
          <button
            @click="submitSendEmailOtp"
            :disabled="emailSending || !emailForm.new_email"
            class="px-4 py-2 bg-gray-800 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
          >
            {{ emailSending ? 'Đang gửi...' : 'Gửi mã xác minh đến email mới' }}
          </button>
        </div>

        <div v-else class="space-y-3">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Đã gửi mã xác minh đến <strong class="text-gray-900 dark:text-white">{{ pendingNewEmail }}</strong>
          </p>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mã xác minh</label>
            <div class="flex gap-2">
              <input
                v-model="emailForm.otp"
                type="text"
                maxlength="6"
                placeholder="6 chữ số"
                class="flex-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="{ 'border-red-500': emailErrors.otp }"
              />
              <button
                @click="submitSendEmailOtp"
                :disabled="emailSending"
                class="px-3 py-2 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 border border-blue-300 rounded-lg disabled:opacity-50 whitespace-nowrap"
              >
                {{ emailSending ? '...' : 'Gửi lại' }}
              </button>
            </div>
            <p v-if="emailErrors.otp" class="mt-1 text-xs text-red-500">{{ emailErrors.otp[0] }}</p>
          </div>

          <div class="flex gap-2 pt-1">
            <button
              @click="resetEmailForm(); clearEmailForm()"
              class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            >
              Hủy
            </button>
            <button
              @click="submitEmailChange"
              :disabled="emailConfirming"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
            >
              {{ emailConfirming ? 'Đang xác nhận...' : 'Xác nhận đổi email' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, onMounted, watch, ref } from 'vue'
import { useTeacherProfile } from '@/composables/useTeacherProfile'
import { useTeacherSecurity } from '@/composables/useTeacherSecurity'
import { uploadService } from '@/services/upload.service'
import { useToast } from 'vue-toastification'

const { profile, loading, saving, loadProfile, saveProfile } = useTeacherProfile()
const {
  pwOtpSent, pwSending, pwConfirming, pwErrors,
  sendPasswordOtp, confirmPasswordChange, resetPasswordForm,
  emailOtpSent, emailSending, emailConfirming, emailErrors, pendingNewEmail,
  sendEmailChangeOtp, confirmEmailChange, resetEmailForm,
} = useTeacherSecurity()

const avatarInput = ref<HTMLInputElement | null>(null)
const isUploading = ref(false)
const toast = useToast()

const form = reactive({
  image: '',
  description: '',
  bank_name: '',
  bank_account_number: '',
  bank_account_name: '',
})

const pwForm = reactive({
  otp: '',
  password: '',
  password_confirmation: '',
})

const emailForm = reactive({
  new_email: '',
  otp: '',
})

watch(profile, (p) => {
  if (p) {
    form.image = p.image || ''
    form.description = p.description || ''
    form.bank_name = p.bank_name || ''
    form.bank_account_number = p.bank_account_number || ''
    form.bank_account_name = p.bank_account_name || ''
  }
}, { immediate: true })

async function submit() {
  await saveProfile({ ...form })
}

function clearPwForm() {
  pwForm.otp = ''
  pwForm.password = ''
  pwForm.password_confirmation = ''
}

function clearEmailForm() {
  emailForm.new_email = ''
  emailForm.otp = ''
}

async function submitPasswordChange() {
  const ok = await confirmPasswordChange(pwForm.otp, pwForm.password, pwForm.password_confirmation)
  if (ok) clearPwForm()
}

async function submitSendEmailOtp() {
  await sendEmailChangeOtp(emailForm.new_email)
  emailForm.otp = ''
}

async function submitEmailChange() {
  const newEmail = await confirmEmailChange(emailForm.otp)
  if (newEmail && profile.value) {
    profile.value.email = newEmail
    clearEmailForm()
  }
}

async function uploadAvatar(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  try {
    isUploading.value = true
    const res = await uploadService.image(file, 'avatars')
    // Get the image path from response and prefix with correct base path if not present.
    // The response could be just a relative path.
    const imagePath = res.data.data.path || res.data.data.url
    form.image = imagePath
    toast.success('Tải ảnh thành công, vui lòng "Lưu thay đổi" để áp dụng.')
  } catch (error) {
    toast.error('Lỗi khi tải ảnh lên.')
  } finally {
    isUploading.value = false
    if (avatarInput.value) avatarInput.value.value = ''
  }
}

onMounted(() => loadProfile())
</script>
