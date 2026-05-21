<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { usePayouts } from '@/composables/usePayouts'

const { payouts, loading, filters, loadPayouts, approvePayout, rejectPayout, markPaid } = usePayouts()

const confirmModal = ref({ show: false, id: 0, action: '', note: '' })

function openModal(id: number, action: string) {
  confirmModal.value = { show: true, id, action, note: '' }
}

async function confirmAction() {
  const { id, action, note } = confirmModal.value
  if (action === 'approve') await approvePayout(id, note)
  else if (action === 'reject') await rejectPayout(id, note)
  else if (action === 'mark-paid') await markPaid(id)
  confirmModal.value.show = false
}

const statusLabel: Record<string, string> = {
  pending: 'Chờ duyệt', approved: 'Đã duyệt', rejected: 'Từ chối', paid: 'Đã thanh toán',
}
const statusClass: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-800',
  approved: 'bg-blue-100 text-blue-800',
  rejected: 'bg-red-100 text-red-800',
  paid: 'bg-green-100 text-green-800',
}

onMounted(loadPayouts)
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Quản lý rút tiền</h1>

    <div class="flex gap-4 mb-4">
      <select v-model="filters.status" @change="loadPayouts" class="border rounded px-3 py-2 text-sm">
        <option value="">Tất cả</option>
        <option value="pending">Chờ duyệt</option>
        <option value="approved">Đã duyệt</option>
        <option value="rejected">Từ chối</option>
        <option value="paid">Đã thanh toán</option>
      </select>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-4 py-3 text-left">Giảng viên</th>
            <th class="px-4 py-3 text-right">Số tiền</th>
            <th class="px-4 py-3 text-left">Ngân hàng</th>
            <th class="px-4 py-3 text-left">Trạng thái</th>
            <th class="px-4 py-3 text-left">Ngày yêu cầu</th>
            <th class="px-4 py-3 text-left">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="px-4 py-8 text-center text-gray-400">Đang tải...</td>
          </tr>
          <tr v-else-if="!payouts.length">
            <td colspan="6" class="px-4 py-8 text-center text-gray-400">Chưa có yêu cầu nào.</td>
          </tr>
          <tr v-for="p in payouts" :key="p.id" class="border-t hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">{{ p.teacher_name }}</td>
            <td class="px-4 py-3 text-right font-semibold text-green-700">
              {{ Number(p.amount).toLocaleString('vi-VN') }} ₫
            </td>
            <td class="px-4 py-3 text-xs text-gray-500">
              <span v-if="p.bank_name">{{ p.bank_name }} – {{ p.bank_account_number }}</span>
              <span v-else class="text-red-400">Chưa có TK ngân hàng</span>
            </td>
            <td class="px-4 py-3">
              <span :class="['px-2 py-1 rounded-full text-xs font-medium', statusClass[p.status]]">
                {{ statusLabel[p.status] }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-500 text-xs">
              {{ new Date(p.created_at).toLocaleDateString('vi-VN') }}
            </td>
            <td class="px-4 py-3 flex gap-2">
              <button v-if="p.status === 'pending'" @click="openModal(p.id, 'approve')"
                class="px-3 py-1 bg-green-100 text-green-700 rounded text-xs hover:bg-green-200">Duyệt</button>
              <button v-if="p.status === 'pending'" @click="openModal(p.id, 'reject')"
                class="px-3 py-1 bg-red-100 text-red-700 rounded text-xs hover:bg-red-200">Từ chối</button>
              <button v-if="p.status === 'approved'" @click="openModal(p.id, 'mark-paid')"
                class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200">Đã TT</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="confirmModal.show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="font-semibold mb-3">
          {{ confirmModal.action === 'approve' ? 'Duyệt yêu cầu' : confirmModal.action === 'reject' ? 'Từ chối yêu cầu' : 'Xác nhận đã thanh toán' }}
        </h3>
        <textarea v-if="confirmModal.action !== 'mark-paid'" v-model="confirmModal.note"
          class="w-full border rounded px-3 py-2 text-sm mb-4" rows="3" placeholder="Ghi chú (tùy chọn)" />
        <div class="flex justify-end gap-2">
          <button @click="confirmModal.show = false" class="px-4 py-2 border rounded text-sm">Hủy</button>
          <button @click="confirmAction" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Xác nhận</button>
        </div>
      </div>
    </div>
  </div>
</template>
