<script setup lang="ts">
import { onMounted } from 'vue'
import { useTeacherEarnings } from '@/composables/useTeacherEarnings'

const { summary, loading, loadSummary } = useTeacherEarnings()

onMounted(loadSummary)
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Hoa hồng giảng viên</h1>
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
  </div>
</template>
