<template>
  <div>
    <!-- Bulk Action Bar -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-full opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-full opacity-0"
    >
      <div
        v-if="count > 0"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[90] flex items-center gap-4 px-6 py-3 rounded-2xl bg-gray-900 dark:bg-gray-800 text-white shadow-2xl border border-gray-700"
      >
        <span class="text-sm font-medium">
          Đã chọn <strong :class="isTrashed ? 'text-red-400' : 'text-blue-400'">{{ count }}</strong> {{ itemName }}
        </span>
        <div class="w-px h-5 bg-gray-600"></div>
        <template v-if="!isTrashed">
          <button
            @click="confirmAction('publish')"
            :disabled="loading"
            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-green-500/20 text-green-400 hover:bg-green-500/30 transition-colors disabled:opacity-50"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ publishLabel }}
          </button>
          <button
            @click="confirmAction('draft')"
            :disabled="loading"
            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-yellow-500/20 text-yellow-400 hover:bg-yellow-500/30 transition-colors disabled:opacity-50"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ draftLabel }}
          </button>
          <button
            @click="confirmAction('delete')"
            :disabled="loading"
            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500/30 transition-colors disabled:opacity-50"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Xóa
          </button>
        </template>
        <template v-else>
          <button
            @click="confirmAction('restore')"
            :disabled="loading"
            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-green-500/20 text-green-400 hover:bg-green-500/30 transition-colors disabled:opacity-50"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Khôi phục
          </button>
          <button
            @click="confirmAction('force-delete')"
            :disabled="loading"
            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500/30 transition-colors disabled:opacity-50"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Xóa vĩnh viễn
          </button>
        </template>
        <button
          @click="$emit('clear')"
          class="ml-2 p-1.5 text-gray-400 hover:text-white rounded-lg transition-colors"
          title="Bỏ chọn"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
    </Transition>

    <!-- Modals -->
    <!-- Publish/Draft Modal -->
    <Teleport to="body">
      <div v-if="actionType === 'publish' || actionType === 'draft'" class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4" @click.self="actionType = null">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-sm p-6">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-2">Đổi trạng thái hàng loạt</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Bạn có chắc muốn <span class="font-medium text-gray-800 dark:text-gray-200">{{ actionType === 'publish' ? publishLabel.toLowerCase() : draftLabel.toLowerCase() }}</span>
            <strong class="text-blue-500 mx-1">{{ count }}</strong> {{ itemName }} đã chọn?
          </p>
          <div class="flex justify-end gap-3">
            <button @click="actionType = null" class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">Hủy</button>
            <button @click="executeAction" :disabled="loading" class="px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 disabled:opacity-50">
              {{ loading ? 'Đang cập nhật...' : 'Xác nhận' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Delete Modal -->
    <Teleport to="body">
      <div v-if="actionType === 'delete'" class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4" @click.self="actionType = null">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-sm p-6">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-2">Xóa nhiều {{ itemName }}</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Bạn có chắc muốn xóa <strong class="text-gray-800 dark:text-white/90">{{ count }}</strong> {{ itemName }} đã chọn?
            <span class="block mt-1 text-xs text-red-500">Chúng sẽ được chuyển vào thùng rác.</span>
          </p>
          <div class="flex justify-end gap-3">
            <button @click="actionType = null" class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">Hủy</button>
            <button @click="executeAction" :disabled="loading" class="px-4 py-2 text-sm rounded-lg bg-red-500 text-white hover:bg-red-600 disabled:opacity-50">
              {{ loading ? 'Đang xóa...' : 'Xóa tất cả' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Restore Modal -->
    <Teleport to="body">
      <div v-if="actionType === 'restore'" class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4" @click.self="actionType = null">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-sm p-6">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-2">Khôi phục nhiều {{ itemName }}</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Khôi phục <strong class="text-gray-800 dark:text-white/90">{{ count }}</strong> {{ itemName }}?
          </p>
          <div class="flex justify-end gap-3">
            <button @click="actionType = null" class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">Hủy</button>
            <button @click="executeAction" :disabled="loading" class="px-4 py-2 text-sm rounded-lg bg-green-500 text-white hover:bg-green-600 disabled:opacity-50">
              {{ loading ? 'Đang khôi phục...' : 'Khôi phục' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Force Delete Modal -->
    <Teleport to="body">
      <div v-if="actionType === 'force-delete'" class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4" @click.self="actionType = null">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-sm p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-500/10 flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Xóa vĩnh viễn</h3>
              <p class="text-xs text-red-500">Không thể hoàn tác!</p>
            </div>
          </div>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Bạn có chắc muốn xóa VĨNH VIỄN <strong class="text-gray-800 dark:text-white/90">{{ count }}</strong> {{ itemName }}?
          </p>
          <div class="flex justify-end gap-3">
            <button @click="actionType = null" class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">Hủy</button>
            <button @click="executeAction" :disabled="loading" class="px-4 py-2 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700 disabled:opacity-50">
              {{ loading ? 'Đang xóa...' : 'Xóa vĩnh viễn' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps({
  count: {
    type: Number,
    required: true
  },
  itemName: {
    type: String,
    default: 'mục'
  },
  isTrashed: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  publishLabel: {
    type: String,
    default: 'Đăng'
  },
  draftLabel: {
    type: String,
    default: 'Nháp'
  }
})

const emit = defineEmits(['publish', 'draft', 'delete', 'restore', 'force-delete', 'clear'])

const actionType = ref<'publish'|'draft'|'delete'|'restore'|'force-delete'|null>(null)

function confirmAction(type: 'publish'|'draft'|'delete'|'restore'|'force-delete') {
  actionType.value = type
}

function executeAction() {
  if (!actionType.value) return
  emit(actionType.value)
}

defineExpose({
  closeModal: () => { actionType.value = null }
})
</script>
