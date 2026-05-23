<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useTeacherEarnings } from '@/composables/useTeacherEarnings'
import ExportExcelModal from '@/components/common/ExportExcelModal.vue'

const { summary, loading, loadSummary } = useTeacherEarnings()
const showExportModal = ref(false)

onMounted(loadSummary)
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">Hoa hồng giảng viên</h1>
      <button
        v-permission="'teacher_earnings.export'"
        @click="showExportModal = true"
        class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        Xuất Excel
      </button>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-4 py-3 text-left">Giảng viên</th>
            <th class="px-4 py-3 text-right">Tổng đã kiếm</th>
            <th class="px-4 py-3 text-right">Đã thanh toán</th>
            <th class="px-4 py-3 text-right">Đang chờ duyệt</th>
            <th class="px-4 py-3 text-right">Số dư khả dụng</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="px-4 py-8 text-center text-gray-400">Đang tải...</td>
          </tr>
          <tr v-else-if="!summary.length">
            <td colspan="5" class="px-4 py-8 text-center text-gray-400">Chưa có dữ liệu.</td>
          </tr>
          <tr v-for="row in summary" :key="row.id" class="border-t hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">{{ row.name }}</td>
            <td class="px-4 py-3 text-right">{{ Number(row.total_earned).toLocaleString('vi-VN') }} ₫</td>
            <td class="px-4 py-3 text-right text-gray-500">{{ Number(row.total_paid ?? 0).toLocaleString('vi-VN') }} ₫</td>
            <td class="px-4 py-3 text-right text-yellow-700">{{ Number(row.pending_payout).toLocaleString('vi-VN') }} ₫</td>
            <td class="px-4 py-3 text-right font-semibold text-green-700">{{ Number(row.available_balance).toLocaleString('vi-VN') }} ₫</td>
          </tr>
        </tbody>
      </table>
    </div>

    <ExportExcelModal
      :show="showExportModal"
      title="Xuất hoa hồng giảng viên"
      endpoint="/admin/teacher-earnings/export"
      @close="showExportModal = false"
    />
  </div>
</template>
