<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4"
        @click.self="$emit('cancel')"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-sm p-6">
          <!-- Icon + Title -->
          <div v-if="icon" class="flex items-center gap-3 mb-4">
            <div
              class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
              :class="style.iconBg"
            >
              <!-- Warning icon -->
              <svg
                v-if="icon === 'warning'"
                class="w-5 h-5"
                :class="style.iconColor"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
              <!-- Trash icon -->
              <svg
                v-else-if="icon === 'trash'"
                class="w-5 h-5"
                :class="style.iconColor"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </div>
            <div>
              <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ title }}</h3>
              <p v-if="subtitle" class="text-xs" :class="style.subtitle">{{ subtitle }}</p>
            </div>
          </div>
          <h3
            v-else
            class="text-base font-semibold text-gray-800 dark:text-white/90 mb-2"
          >
            {{ title }}
          </h3>

          <!-- Message -->
          <div class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            <slot>
              <p>{{ message }}</p>
            </slot>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3">
            <button
              @click="$emit('cancel')"
              class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            >
              {{ cancelText }}
            </button>
            <button
              @click="$emit('confirm')"
              :disabled="loading"
              class="px-4 py-2 text-sm rounded-lg text-white disabled:opacity-50 transition-colors"
              :class="style.btn"
            >
              {{ loading ? loadingText : confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type Variant = 'danger' | 'warning' | 'info'

const props = withDefaults(defineProps<{
  show: boolean
  title: string
  message?: string
  icon?: 'warning' | 'trash' | null
  subtitle?: string
  cancelText?: string
  confirmText?: string
  loadingText?: string
  loading?: boolean
  variant?: Variant
}>(), {
  message: '',
  icon: null,
  subtitle: '',
  cancelText: 'Hủy',
  confirmText: 'Xác nhận',
  loadingText: 'Đang xử lý...',
  loading: false,
  variant: 'danger',
})

defineEmits<{
  cancel: []
  confirm: []
}>()

const variantStyles: Record<Variant, { iconBg: string; iconColor: string; subtitle: string; btn: string }> = {
  danger: {
    iconBg: 'bg-red-100 dark:bg-red-500/10',
    iconColor: 'text-red-600 dark:text-red-400',
    subtitle: 'text-red-500',
    btn: 'bg-red-500 hover:bg-red-600',
  },
  warning: {
    iconBg: 'bg-yellow-100 dark:bg-yellow-500/10',
    iconColor: 'text-yellow-600 dark:text-yellow-400',
    subtitle: 'text-yellow-500',
    btn: 'bg-yellow-500 hover:bg-yellow-600',
  },
  info: {
    iconBg: 'bg-blue-100 dark:bg-blue-500/10',
    iconColor: 'text-blue-600 dark:text-blue-400',
    subtitle: 'text-blue-500',
    btn: 'bg-blue-500 hover:bg-blue-600',
  },
}

const style = computed(() => variantStyles[props.variant])
</script>
