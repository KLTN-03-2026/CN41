<template>
  <div>
    <!-- Page Breadcrumb -->
    <div class="mb-6">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">
        Dashboard
      </h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Tổng quan hệ thống E-Learning
      </p>
    </div>

    <div class="grid grid-cols-12 gap-4 md:gap-6">
      <!-- Metrics -->
      <div class="col-span-12 space-y-6 xl:col-span-7">
        <!-- Stat Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6">
          <div
            v-for="stat in stats"
            :key="stat.label"
            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-white/5 md:p-6"
          >
            <div
              class="flex items-center justify-center w-12 h-12 rounded-xl"
              :class="stat.iconBg"
            >
              <component
                :is="stat.icon"
                class="w-6 h-6"
                :class="stat.iconColor"
              />
            </div>

            <div class="flex items-end justify-between mt-5">
              <div>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ stat.label }}</span>
                <h4
                  v-if="!loading"
                  class="mt-2 font-bold text-gray-800 text-2xl dark:text-white/90"
                >
                  {{ stat.value }}
                </h4>
                <div
                  v-else
                  class="h-8 w-24 bg-gray-100 dark:bg-gray-800 rounded animate-pulse mt-2"
                ></div>
              </div>

              <span
                :class="stat.changePositive
                  ? 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500'
                  : 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500'"
                class="flex items-center gap-1 rounded-full py-0.5 pl-2 pr-2.5 text-sm font-medium"
              >
                <svg
                  class="fill-current"
                  width="12"
                  height="12"
                  viewBox="0 0 12 12"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    v-if="stat.changePositive"
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"
                    fill=""
                  />
                  <path
                    v-else
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M5.31462 10.3761C5.45194 10.5293 5.65136 10.6257 5.87329 10.6257C5.8736 10.6257 5.8739 10.6257 5.87421 10.6257C6.0663 10.6259 6.25845 10.5527 6.40505 10.4062L9.40514 7.4082C9.69814 7.11541 9.69831 6.64054 9.40552 6.34754C9.11273 6.05454 8.63785 6.05438 8.34486 6.34717L6.62329 8.06753L6.62329 1.875C6.62329 1.46079 6.28751 1.125 5.87329 1.125C5.45908 1.125 5.12329 1.46079 5.12329 1.875L5.12329 8.06422L3.40516 6.34719C3.11218 6.05439 2.6373 6.05454 2.3445 6.34752C2.0517 6.64051 2.05185 7.11538 2.34484 7.40818L5.31462 10.3761Z"
                    fill=""
                  />
                </svg>
                {{ stat.change }}
              </span>
            </div>
          </div>
        </div>

        <!-- Revenue Chart -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-white/5 md:p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800 dark:text-white/90">Doanh thu theo tháng</h3>
            <span class="text-xs text-gray-400">{{ currentYear }}</span>
          </div>
          <div v-if="!loading" class="flex items-end gap-2 h-40">
            <div
              v-for="(item, i) in revenueData"
              :key="i"
              class="flex-1 flex flex-col items-center gap-1 group"
            >
              <span class="text-xs text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap dark:text-gray-400">
                {{ formatCurrency(item.value) }}
              </span>
              <div
                class="w-full bg-blue-500 rounded-t-md transition-all duration-500 hover:bg-blue-600"
                :style="{ height: barHeight(item.value) }"
              ></div>
              <span class="text-xs text-gray-400">{{ item.month }}</span>
            </div>
          </div>
          <div v-else class="flex items-end gap-2 h-40">
            <div
              v-for="(h, i) in SKELETON_HEIGHTS"
              :key="i"
              class="flex-1 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"
              :style="{ height: h + 'px' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Right Column -->
      <div class="col-span-12 xl:col-span-5 space-y-6">
        <!-- Top Courses -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-white/5 md:p-6">
          <h3 class="font-semibold text-gray-800 dark:text-white/90 mb-4">Top khóa học</h3>
          <div v-if="!loading" class="space-y-3">
            <div
              v-for="(course, i) in topCourses"
              :key="course.id"
              class="flex items-center gap-3"
            >
              <span class="w-7 h-7 rounded-full bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center shrink-0">
                {{ i + 1 }}
              </span>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-700 truncate dark:text-gray-300">{{ course.title }}</p>
                <p class="text-xs text-gray-400">{{ course.enrolled }} học viên</p>
              </div>
              <span class="text-sm font-semibold text-gray-700 shrink-0 dark:text-gray-300">{{ formatCurrency(course.revenue) }}</span>
            </div>
            <p v-if="topCourses.length === 0" class="text-sm text-gray-400 text-center py-4">Chưa có dữ liệu</p>
          </div>
          <div v-else class="space-y-3">
            <div v-for="i in 5" :key="i" class="flex items-center gap-3">
              <div class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-800 animate-pulse shrink-0"></div>
              <div class="flex-1 space-y-1">
                <div class="h-4 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                <div class="h-3 bg-gray-100 dark:bg-gray-800 rounded animate-pulse w-1/2"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Orders - Full Width -->
      <div class="col-span-12">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-white/5 md:p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800 dark:text-white/90">Đơn hàng gần đây</h3>
            <router-link
              to="/admin/orders"
              class="text-sm text-blue-500 hover:text-blue-600 dark:text-blue-400 flex items-center gap-1"
            >
              Xem tất cả
              <ChevronRightIcon class="w-4 h-4" />
            </router-link>
          </div>

          <div v-if="!loading" class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-100 dark:border-gray-700">
                  <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 pb-3 pr-4">Học viên</th>
                  <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 pb-3 pr-4">Khóa học</th>
                  <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 pb-3 pr-4">Số tiền</th>
                  <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 pb-3">Trạng thái</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                <tr
                  v-for="order in recentOrders"
                  :key="order.id"
                  class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                >
                  <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ order.student_name }}</td>
                  <td class="py-3 pr-4 text-gray-600 dark:text-gray-400 max-w-[180px] truncate">{{ order.course_title }}</td>
                  <td class="py-3 pr-4 font-medium text-gray-700 dark:text-gray-300">{{ formatCurrency(order.amount) }}</td>
                  <td class="py-3">
                    <span
                      :class="statusClass(order.status)"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    >
                      {{ statusLabel(order.status) }}
                    </span>
                  </td>
                </tr>
                <tr v-if="recentOrders.length === 0">
                  <td colspan="4" class="py-8 text-center text-gray-400 text-sm">Chưa có đơn hàng nào</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Skeleton table -->
          <div v-else class="space-y-3">
            <div v-for="i in 5" :key="i" class="flex gap-4">
              <div class="h-4 bg-gray-100 dark:bg-gray-800 rounded animate-pulse w-1/4"></div>
              <div class="h-4 bg-gray-100 dark:bg-gray-800 rounded animate-pulse w-1/3"></div>
              <div class="h-4 bg-gray-100 dark:bg-gray-800 rounded animate-pulse w-1/6"></div>
              <div class="h-4 bg-gray-100 dark:bg-gray-800 rounded animate-pulse w-1/6"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, markRaw, type Component } from 'vue'
import { UserGroupIcon, BoxCubeIcon, BoxIcon, BarChartIcon, ChevronRightIcon } from '@/icons'
import { formatCurrency } from '@/utils/formatCurrency'
import { orderService } from '@/services/order.service'

const SKELETON_HEIGHTS = [55, 72, 48, 85, 63, 90, 78, 42, 68, 80, 58, 45]

interface StatItem {
  label: string
  value: string
  icon: Component
  iconBg: string
  iconColor: string
  change: string
  changePositive: boolean
}

interface RevenueItem {
  month: string
  value: number
}

interface CourseItem {
  id: number
  title: string
  enrolled: number
  revenue: number
}

interface OrderItem {
  id: number
  student_name: string
  course_title: string
  amount: number
  status: string
}

const MONTHS = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12']

const loading = ref(true)
const statValues = ref({ students: 0, courses: 0, orders: 0, revenue: 0 })
const revenueData = ref<RevenueItem[]>(MONTHS.map((month) => ({ month, value: 0 })))
const topCourses = ref<CourseItem[]>([])
const recentOrders = ref<OrderItem[]>([])

const currentYear = new Date().getFullYear()

const stats = computed<StatItem[]>(() => [
  {
    label: 'Học viên',
    value: statValues.value.students.toLocaleString('vi'),
    icon: markRaw(UserGroupIcon),
    iconBg: 'bg-blue-100 dark:bg-blue-500/10',
    iconColor: 'text-blue-600 dark:text-blue-400',
    change: '11.01%',
    changePositive: true,
  },
  {
    label: 'Khóa học',
    value: statValues.value.courses.toLocaleString('vi'),
    icon: markRaw(BoxCubeIcon),
    iconBg: 'bg-green-100 dark:bg-green-500/10',
    iconColor: 'text-green-600 dark:text-green-400',
    change: '5.2%',
    changePositive: true,
  },
  {
    label: 'Đơn hàng',
    value: statValues.value.orders.toLocaleString('vi'),
    icon: markRaw(BoxIcon),
    iconBg: 'bg-orange-100 dark:bg-orange-500/10',
    iconColor: 'text-orange-600 dark:text-orange-400',
    change: '9.05%',
    changePositive: false,
  },
  {
    label: 'Doanh thu',
    value: formatCurrency(statValues.value.revenue),
    icon: markRaw(BarChartIcon),
    iconBg: 'bg-purple-100 dark:bg-purple-500/10',
    iconColor: 'text-purple-600 dark:text-purple-400',
    change: '12.3%',
    changePositive: true,
  },
])

const maxRevenue = computed(() => Math.max(...revenueData.value.map((d) => d.value), 1))

const barHeight = (value: number) => {
  const pct = (value / maxRevenue.value) * 100
  return `${Math.max(pct, 4)}%`
}

const statusClass = (status: string) =>
  ({
    paid: 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
    pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400',
    failed: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    refunded: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
  })[status] || 'bg-gray-100 text-gray-600'

const statusLabel = (status: string) =>
  ({
    paid: 'Đã thanh toán',
    pending: 'Chờ thanh toán',
    failed: 'Thất bại',
    refunded: 'Hoàn tiền',
  })[status] || status

onMounted(async () => {
  try {
    // Fetch revenue stats từ API
    const [revenueRes, ordersRes] = await Promise.allSettled([
      orderService.revenueStats({ period: 'monthly', from: `${currentYear}-01-01`, to: `${currentYear}-12-31` }),
      orderService.adminList({ per_page: 5 }),
    ])

    // Revenue stats
    if (revenueRes.status === 'fulfilled') {
      const data = revenueRes.value.data.data
      statValues.value.revenue = data.total_revenue || 0
      statValues.value.orders  = data.total_orders || 0

      // Map monthly data vào chart
      if (data.data?.length) {
        const monthMap = new Map<number, number>()
        data.data.forEach((item: any) => {
          monthMap.set(item.month, Number(item.total_revenue))
        })
        revenueData.value = MONTHS.map((month, i) => ({
          month,
          value: monthMap.get(i + 1) || 0,
        }))
      }
    }

    // Recent orders
    if (ordersRes.status === 'fulfilled') {
      const ordersData = ordersRes.value.data.data || []
      recentOrders.value = ordersData.map((o: any) => ({
        id: o.id,
        student_name: o.student?.name || '—',
        course_title: o.items?.[0]?.course?.name || '—',
        amount: Number(o.total_amount),
        status: o.status,
      }))
    }

    // Fallback: fetch counts nếu chưa có API riêng
    if (!statValues.value.students) {
      statValues.value.students = 0
    }
    if (!statValues.value.courses) {
      statValues.value.courses = 0
    }
  } catch {
    // Fallback mock data nếu API chưa sẵn sàng
    statValues.value = { students: 0, courses: 0, orders: 0, revenue: 0 }
  } finally {
    loading.value = false
  }
})
</script>
