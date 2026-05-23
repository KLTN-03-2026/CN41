<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useEarnings } from '@/composables/useEarnings'
import ExportExcelModal from '@/components/common/ExportExcelModal.vue'

const { balance, earnings, payouts, loading, payoutLoading, loadEarnings, loadMyPayouts, requestPayout } = useEarnings()

const showPayoutModal = ref(false)
const showExportModal = ref(false)
const payoutAmount = ref<number>(0)
const payoutNote = ref('')

async function submitPayout() {
  const ok = await requestPayout(payoutAmount.value, payoutNote.value)
  if (ok) {
    showPayoutModal.value = false
    payoutAmount.value = 0
    payoutNote.value = ''
    await loadMyPayouts()
  }
}

const statusLabel: Record<string, string> = {
  pending: 'Chờ duyệt', approved: 'Đã duyệt', rejected: 'Từ chối', paid: 'Đã thanh toán',
}
const statusClass: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-800', approved: 'bg-blue-100 text-blue-800',
  rejected: 'bg-red-100 text-red-800', paid: 'bg-green-100 text-green-800',
}

onMounted(async () => {
  await loadEarnings()
  await loadMyPayouts()
})
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">Thu nhập của tôi</h1>
      <button
        @click="showExportModal = true"
        class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        Xuất Excel
      </button>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="text-xs text-gray-500 mb-1">Số dư khả dụng</div>
        <div class="text-2xl font-bold text-green-700">{{ Number(balance.available).toLocaleString('vi-VN') }} ₫</div>
      </div>
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="text-xs text-gray-500 mb-1">Tổng đã kiếm</div>
        <div class="text-2xl font-bold text-blue-700">{{ Number(balance.total_earned).toLocaleString('vi-VN') }} ₫</div>
      </div>
      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="text-xs text-gray-500 mb-1">Đang chờ duyệt</div>
        <div class="text-2xl font-bold text-yellow-700">{{ Number(balance.pending_payout).toLocaleString('vi-VN') }} ₫</div>
      </div>
    </div>

    <button @click="showPayoutModal = true"
      class="mb-6 px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
      + Yêu cầu rút tiền
    </button>

    <!-- Earnings History -->
    <h2 class="text-lg font-semibold mb-3">Lịch sử hoa hồng</h2>
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-4 py-3 text-left">Mô tả</th>
            <th class="px-4 py-3 text-left">Loại</th>
            <th class="px-4 py-3 text-right">Số tiền</th>
            <th class="px-4 py-3 text-left">Ngày</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="4" class="px-4 py-8 text-center text-gray-400">Đang tải...</td>
          </tr>
          <tr v-else-if="!earnings.length">
            <td colspan="4" class="px-4 py-8 text-center text-gray-400">Chưa có giao dịch nào.</td>
          </tr>
          <tr v-for="e in earnings" :key="e.id" class="border-t hover:bg-gray-50">
            <td class="px-4 py-3">{{ e.description }}</td>
            <td class="px-4 py-3">
              <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', e.type === 'credit' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                {{ e.type === 'credit' ? 'Thu' : 'Trừ (hoàn tiền)' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-medium" :class="e.type === 'credit' ? 'text-green-700' : 'text-red-700'">
              {{ e.type === 'credit' ? '+' : '−' }}{{ Number(e.amount).toLocaleString('vi-VN') }} ₫
            </td>
            <td class="px-4 py-3 text-gray-500 text-xs">{{ new Date(e.created_at).toLocaleDateString('vi-VN') }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Payout History -->
    <h2 class="text-lg font-semibold mb-3">Lịch sử rút tiền</h2>
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-4 py-3 text-right">Số tiền</th>
            <th class="px-4 py-3 text-left">Trạng thái</th>
            <th class="px-4 py-3 text-left">Ghi chú Admin</th>
            <th class="px-4 py-3 text-left">Ngày yêu cầu</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!payouts.length">
            <td colspan="4" class="px-4 py-8 text-center text-gray-400">Chưa có yêu cầu rút nào.</td>
          </tr>
          <tr v-for="p in payouts" :key="p.id" class="border-t hover:bg-gray-50">
            <td class="px-4 py-3 text-right font-semibold">{{ Number(p.amount).toLocaleString('vi-VN') }} ₫</td>
            <td class="px-4 py-3">
              <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', statusClass[p.status]]">{{ statusLabel[p.status] }}</span>
            </td>
            <td class="px-4 py-3 text-gray-500 text-xs">{{ p.admin_note || '—' }}</td>
            <td class="px-4 py-3 text-gray-500 text-xs">{{ new Date(p.created_at).toLocaleDateString('vi-VN') }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Payout Request Modal -->
    <div v-if="showPayoutModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="font-semibold mb-4">Yêu cầu rút tiền</h3>
        <p class="text-sm text-gray-500 mb-4">
          Số dư khả dụng: <strong class="text-green-700">{{ Number(balance.available).toLocaleString('vi-VN') }} ₫</strong>
        </p>
        <div class="mb-3">
          <label class="block text-sm font-medium mb-1">Số tiền (VNĐ)</label>
          <input v-model.number="payoutAmount" type="number" min="1000" :max="balance.available"
            class="w-full border rounded px-3 py-2 text-sm" />
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Ghi chú (tùy chọn)</label>
          <textarea v-model="payoutNote" rows="2" class="w-full border rounded px-3 py-2 text-sm" />
        </div>
        <div class="flex justify-end gap-2">
          <button @click="showPayoutModal = false" class="px-4 py-2 border rounded text-sm">Hủy</button>
          <button @click="submitPayout" :disabled="payoutLoading || payoutAmount <= 0"
            class="px-4 py-2 bg-blue-600 text-white rounded text-sm disabled:opacity-50">
            {{ payoutLoading ? 'Đang gửi...' : 'Gửi yêu cầu' }}
          </button>
        </div>
      </div>
    </div>

    <ExportExcelModal
      :show="showExportModal"
      title="Xuất thu nhập của tôi"
      endpoint="/teacher/earnings/export"
      @close="showExportModal = false"
    />
  </div>
</template>
