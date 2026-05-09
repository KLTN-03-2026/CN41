<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Lịch sử hoạt động</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Theo dõi các thay đổi và hoạt động của hệ thống
        </p>
      </div>
      <button
        @click="confirmClearLogs"
        class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-red-50 dark:bg-red-500/10 text-red-600 hover:bg-red-100 dark:hover:bg-red-500/20 transition-colors"
      >
        <TrashIcon class="w-4 h-4" /> Dọn dẹp log
      </button>
    </div>

    <!-- Table -->
    <div
      class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/5 overflow-hidden shadow-sm"
    >
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[1000px]">
          <thead>
            <tr
              class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-white/[0.02]"
            >
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-4">
                Người thực hiện
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-4">
                Hành động
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-4">
                Đối tượng
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-4">
                Thời gian
              </th>
              <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-4">
                Chi tiết
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <!-- Loading -->
            <template v-if="loading">
              <tr v-for="i in 5" :key="i">
                <td class="px-6 py-4">
                  <div class="h-4 w-32 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                </td>
                <td class="px-6 py-4">
                  <div class="h-4 w-40 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                </td>
                <td class="px-6 py-4">
                  <div class="h-4 w-24 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                </td>
                <td class="px-6 py-4">
                  <div class="h-4 w-20 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                </td>
                <td class="px-6 py-4">
                  <div
                    class="h-6 w-10 bg-gray-100 dark:bg-gray-800 rounded animate-pulse ml-auto"
                  ></div>
                </td>
              </tr>
            </template>

            <!-- Data rows -->
            <template v-else-if="logs.length">
              <tr
                v-for="log in logs"
                :key="log.id"
                class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <div
                      class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-xs"
                    >
                      {{ log.causer_name.charAt(0) }}
                    </div>
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{
                      log.causer_name
                    }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span
                    class="px-2 py-1 rounded text-xs font-medium"
                    :class="getActionClass(log.description)"
                  >
                    {{ getActionLabel(log.description) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="text-gray-700 dark:text-gray-300 font-medium">
                    {{ log.subject_type }}
                  </div>
                  <div class="text-xs text-gray-400">ID: {{ log.subject_id }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-gray-700 dark:text-gray-300">{{ log.human_time }}</div>
                  <div class="text-[10px] text-gray-400">{{ log.created_at }}</div>
                </td>
                <td class="px-6 py-4 text-right">
                  <button
                    @click="viewDetail(log)"
                    class="text-blue-500 hover:text-blue-600 text-xs font-medium flex items-center gap-1 ml-auto"
                  >
                    <EyeIcon class="w-4 h-4" /> Xem
                  </button>
                </td>
              </tr>
            </template>

            <tr v-else>
              <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">
                Chưa có lịch sử hoạt động nào.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination.last_page > 1"
        class="p-4 border-t border-gray-50 dark:border-gray-800 flex justify-end"
      >
        <PaginationBar
          :current-page="pagination.current_page"
          :last-page="pagination.last_page"
          @change="loadPage"
        />
      </div>
    </div>

    <!-- Detail Modal -->
    <div
      v-if="showDetail"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4"
    >
      <div
        class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-2xl border border-gray-100 dark:border-gray-800 overflow-hidden"
      >
        <div
          class="p-4 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center"
        >
          <h3 class="font-bold text-gray-800 dark:text-white">Chi tiết thay đổi</h3>
          <button
            @click="showDetail = false"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <CloseIcon class="w-5 h-5" />
          </button>
        </div>
        <div class="p-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div
                class="p-3 rounded-lg bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-gray-800"
              >
                <span class="text-xs text-gray-500 block mb-1">Mô tả</span>
                <span class="text-sm font-medium text-gray-800 dark:text-white">{{
                  selectedLog?.description
                }}</span>
              </div>
              <div
                class="p-3 rounded-lg bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-gray-800"
              >
                <span class="text-xs text-gray-500 block mb-1">Đối tượng</span>
                <span class="text-sm font-medium text-gray-800 dark:text-white"
                  >{{ selectedLog?.subject_type }} #{{ selectedLog?.subject_id }}</span
                >
              </div>
            </div>

            <div
              class="p-4 rounded-xl bg-gray-900 text-green-400 font-mono text-xs overflow-x-auto"
            >
              <pre>{{ JSON.stringify(selectedLog?.properties, null, 2) }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm Clear Modal -->
    <ConfirmModal
      :show="showClearModal"
      title="Dọn dẹp lịch sử"
      :loading="clearLoading"
      confirm-text="Xác nhận dọn dẹp"
      confirm-class="bg-red-500 hover:bg-red-600"
      @cancel="showClearModal = false"
      @confirm="doClearLogs"
    >
      <p class="text-gray-600 dark:text-gray-400 text-sm">
        Bạn có chắc chắn muốn dọn dẹp toàn bộ lịch sử hoạt động không? Hành động này sẽ xoá vĩnh
        viễn dữ liệu log và không thể khôi phục.
      </p>
    </ConfirmModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { systemService } from '@/services/system.service'
import type { ActivityLog } from '@/services/system.service'
import { TrashIcon, EyeIcon, CloseIcon } from '@/components/icons'
import PaginationBar from '@/components/common/PaginationBar.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

const logs = ref<ActivityLog[]>([])
const loading = ref(false)
const pagination = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

async function loadPage(page = 1) {
  loading.value = true
  try {
    const res = await systemService.getLogs({ page, per_page: pagination.per_page })
    logs.value = res.data.data
    Object.assign(pagination, res.data.pagination)
  } catch {
    toast.error('Không thể tải lịch sử hoạt động.')
  } finally {
    loading.value = false
  }
}

// ── Detail ──
const showDetail = ref(false)
const selectedLog = ref<ActivityLog | null>(null)

function viewDetail(log: ActivityLog) {
  selectedLog.value = log
  showDetail.value = true
}

// ── Clear ──
const showClearModal = ref(false)
const clearLoading = ref(false)

function confirmClearLogs() {
  showClearModal.value = true
}

async function doClearLogs() {
  clearLoading.value = true
  try {
    await systemService.clearLogs()
    toast.success('Đã dọn dẹp lịch sử hoạt động.')
    showClearModal.value = false
    loadPage(1)
  } catch {
    toast.error('Dọn dẹp thất bại.')
  } finally {
    clearLoading.value = false
  }
}

// ── Helpers ──
function getActionLabel(desc: string) {
  const map: Record<string, string> = {
    created: 'Thêm mới',
    updated: 'Cập nhật',
    deleted: 'Xoá',
    restored: 'Khôi phục',
  }
  return map[desc] || desc
}

function getActionClass(desc: string) {
  const map: Record<string, string> = {
    created: 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
    updated: 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
    deleted: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    restored: 'bg-purple-100 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
  }
  return map[desc] || 'bg-gray-100 text-gray-700'
}

onMounted(() => {
  loadPage()
})
</script>
