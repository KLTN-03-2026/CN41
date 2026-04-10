<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Khóa học</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Quản lý tất cả khóa học</p>
      </div>
      <router-link
        v-if="!isTrashed"
        to="/admin/courses/create"
        class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors"
      >
        <PlusIcon class="w-4 h-4" />
        Thêm khóa học
      </router-link>
    </div>

    <!-- Tabs: Đang hoạt động / Thùng rác -->
    <div class="flex items-center gap-1 mb-4 p-1 bg-gray-100 dark:bg-white/5 rounded-xl w-fit">
      <button
        @click="switchTab(false)"
        :class="!isTrashed
          ? 'bg-white dark:bg-gray-800 text-gray-800 dark:text-white shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        Đang hoạt động
      </button>
      <button
        @click="switchTab(true)"
        :class="isTrashed
          ? 'bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
      >
        <TrashIcon class="w-4 h-4" />
        Thùng rác
        <span
          v-if="trashedCount > 0"
          class="px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400"
        >
          {{ trashedCount }}
        </span>
      </button>
    </div>

    <!-- Filters -->
    <div v-if="!isTrashed" class="flex flex-wrap gap-3 mb-4">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Tìm kiếm khóa học..."
        class="input-field w-64"
        @input="debouncedFetch"
      />
      <select v-model="filters.status" class="input-field w-40" @change="fetchPage(1)">
        <option value="">Tất cả trạng thái</option>
        <option value="1">Đã đăng</option>
        <option value="0">Nháp</option>
      </select>
      <select v-model="filters.level" class="input-field w-40" @change="fetchPage(1)">
        <option value="">Tất cả trình độ</option>
        <option value="beginner">Cơ bản</option>
        <option value="intermediate">Trung cấp</option>
        <option value="advanced">Nâng cao</option>
      </select>
    </div>

    <!-- Trashed search -->
    <div v-if="isTrashed" class="flex flex-wrap gap-3 mb-4">
      <input
        v-model="trashedFilters.search"
        type="text"
        placeholder="Tìm trong thùng rác..."
        class="input-field w-64"
        @input="debouncedFetchTrashed"
      />
    </div>

    <!-- ═══════════ ACTIVE TABLE ═══════════ -->
    <div v-if="!isTrashed" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/5 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-white/5">
              <th class="w-10 px-4 py-3">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  :indeterminate="isIndeterminate"
                  @change="toggleSelectAll"
                  class="w-4 h-4 rounded border-gray-300 text-blue-500 focus:ring-blue-500"
                />
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Khóa học</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Giảng viên</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Giá</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Học viên</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Trạng thái</th>
              <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <tr v-if="loading">
              <td colspan="7" class="text-center py-10 text-gray-400">
                <svg class="animate-spin w-6 h-6 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
              </td>
            </tr>
            <tr v-else-if="!courses.length">
              <td colspan="7" class="text-center py-10 text-gray-400 text-sm">Chưa có khóa học nào</td>
            </tr>
            <tr
              v-for="course in courses"
              :key="course.id"
              :class="selectedIds.has(course.id) ? 'bg-blue-50 dark:bg-blue-500/5' : ''"
              class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
            >
              <td class="w-10 px-4 py-4">
                <input
                  type="checkbox"
                  :checked="selectedIds.has(course.id)"
                  @change="toggleSelect(course.id)"
                  class="w-4 h-4 rounded border-gray-300 text-blue-500 focus:ring-blue-500"
                />
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <img
                    v-if="course.thumbnail"
                    :src="course.thumbnail"
                    :alt="course.name"
                    class="w-12 h-8 object-cover rounded shrink-0"
                  />
                  <div
                    v-else
                    class="w-12 h-8 bg-gray-100 dark:bg-gray-800 rounded shrink-0 flex items-center justify-center"
                  >
                    <BoxCubeIcon class="w-4 h-4 text-gray-400" />
                  </div>
                  <div class="min-w-0">
                    <p class="font-medium text-gray-800 dark:text-gray-200 truncate max-w-[200px]">{{ course.name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ levelLabel(course.level) }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                {{ course.teacher?.name || '—' }}
              </td>
              <td class="px-6 py-4">
                <div>
                  <p v-if="course.sale_price" class="font-medium text-green-600 dark:text-green-400">
                    {{ formatCurrency(Number(course.sale_price)) }}
                  </p>
                  <p
                    class="text-gray-600 dark:text-gray-400"
                    :class="{ 'line-through text-xs text-gray-400': course.sale_price }"
                  >
                    {{ formatCurrency(Number(course.price)) }}
                  </p>
                </div>
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                {{ course.total_students || 0 }}
              </td>
              <td class="px-6 py-4">
                <button
                  @click="toggleStatus(course)"
                  :disabled="togglingId === course.id"
                  :class="course.status === 1
                    ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400 hover:bg-green-200'
                    : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400 hover:bg-yellow-200'"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium transition-colors disabled:opacity-50 cursor-pointer"
                >
                  {{ course.status === 1 ? 'Đã đăng' : 'Nháp' }}
                </button>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <router-link
                    :to="`/admin/courses/${course.id}/edit`"
                    class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10 transition-colors"
                    title="Chỉnh sửa"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                  </router-link>
                  <router-link
                    :to="`/admin/courses/${course.id}/edit?tab=lessons`"
                    class="p-1.5 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg dark:hover:bg-purple-500/10 transition-colors"
                    title="Nội dung (Chương & Bài giảng)"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                  </router-link>
                  <button
                    @click="confirmDelete(course)"
                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors"
                    title="Xóa"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination && pagination.last_page > 1"
        class="flex items-center justify-between px-6 py-3 border-t border-gray-100 dark:border-gray-700"
      >
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ pagination.from }}–{{ pagination.to }} / {{ pagination.total }} khóa học
        </p>
        <div class="flex gap-1">
          <button
            v-for="p in pagination.last_page"
            :key="p"
            @click="fetchPage(p)"
            :class="p === pagination.current_page
              ? 'bg-blue-500 text-white border-blue-500'
              : 'bg-white text-gray-600 dark:bg-white/5 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/10'"
            class="w-8 h-8 rounded-lg text-sm border border-gray-200 dark:border-gray-700 transition-colors"
          >
            {{ p }}
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════════ TRASHED TABLE ═══════════ -->
    <div v-if="isTrashed" class="rounded-2xl border border-red-200 bg-white dark:border-red-900/50 dark:bg-white/5 overflow-hidden">
      <!-- Warning banner -->
      <div class="px-6 py-3 bg-red-50 dark:bg-red-500/5 border-b border-red-100 dark:border-red-900/30">
        <div class="flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
          <span>Các khóa học trong thùng rác. Bạn có thể khôi phục hoặc xóa vĩnh viễn.</span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-white/5">
              <th class="w-10 px-4 py-3">
                <input
                  type="checkbox"
                  :checked="isTrashedAllSelected"
                  :indeterminate="isTrashedIndeterminate"
                  @change="toggleTrashedSelectAll"
                  class="w-4 h-4 rounded border-gray-300 text-red-500 focus:ring-red-500"
                />
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Khóa học</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Giảng viên</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Giá</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Đã xóa lúc</th>
              <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <tr v-if="trashedLoading">
              <td colspan="6" class="text-center py-10 text-gray-400">
                <svg class="animate-spin w-6 h-6 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
              </td>
            </tr>
            <tr v-else-if="!trashedCourses.length">
              <td colspan="6" class="text-center py-10">
                <div class="flex flex-col items-center gap-2">
                  <svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  <p class="text-sm text-gray-400">Thùng rác trống</p>
                </div>
              </td>
            </tr>
            <tr
              v-for="course in trashedCourses"
              :key="course.id"
              :class="trashedSelectedIds.has(course.id) ? 'bg-red-50 dark:bg-red-500/5' : ''"
              class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
            >
              <td class="w-10 px-4 py-4">
                <input
                  type="checkbox"
                  :checked="trashedSelectedIds.has(course.id)"
                  @change="toggleTrashedSelect(course.id)"
                  class="w-4 h-4 rounded border-gray-300 text-red-500 focus:ring-red-500"
                />
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <img
                    v-if="course.thumbnail"
                    :src="course.thumbnail"
                    :alt="course.name"
                    class="w-12 h-8 object-cover rounded shrink-0 opacity-60"
                  />
                  <div
                    v-else
                    class="w-12 h-8 bg-gray-100 dark:bg-gray-800 rounded shrink-0 flex items-center justify-center opacity-60"
                  >
                    <BoxCubeIcon class="w-4 h-4 text-gray-400" />
                  </div>
                  <div class="min-w-0">
                    <p class="font-medium text-gray-500 dark:text-gray-400 truncate max-w-[200px]">{{ course.name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ levelLabel(course.level) }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-gray-500 dark:text-gray-500">
                {{ course.teacher?.name || '—' }}
              </td>
              <td class="px-6 py-4 text-gray-500 dark:text-gray-500">
                {{ formatCurrency(Number(course.price)) }}
              </td>
              <td class="px-6 py-4 text-gray-500 dark:text-gray-500 text-xs">
                {{ formatDate(course.deleted_at) }}
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="doRestore(course)"
                    :disabled="restoringId === course.id"
                    class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg dark:hover:bg-green-500/10 transition-colors disabled:opacity-50"
                    title="Khôi phục"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                  </button>
                  <button
                    @click="confirmForceDelete(course)"
                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors"
                    title="Xóa vĩnh viễn"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Trashed Pagination -->
      <div
        v-if="trashedPagination && trashedPagination.last_page > 1"
        class="flex items-center justify-between px-6 py-3 border-t border-gray-100 dark:border-gray-700"
      >
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ trashedPagination.from }}–{{ trashedPagination.to }} / {{ trashedPagination.total }} khóa học
        </p>
        <div class="flex gap-1">
          <button
            v-for="p in trashedPagination.last_page"
            :key="p"
            @click="fetchTrashedPage(p)"
            :class="p === trashedPagination.current_page
              ? 'bg-red-500 text-white border-red-500'
              : 'bg-white text-gray-600 dark:bg-white/5 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/10'"
            class="w-8 h-8 rounded-lg text-sm border border-gray-200 dark:border-gray-700 transition-colors"
          >
            {{ p }}
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════ MODALS ═══════ -->

    <!-- Confirm Soft Delete -->
    <Teleport to="body">
      <div
        v-if="deleteTarget"
        class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4"
        @click.self="deleteTarget = null"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-sm p-6">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-2">Xác nhận xóa</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Bạn có chắc muốn xóa khóa học
            <strong class="text-gray-800 dark:text-white/90">{{ deleteTarget.name }}</strong>?
            <span class="block mt-1 text-xs text-gray-400">Khóa học sẽ được chuyển vào thùng rác.</span>
          </p>
          <div class="flex justify-end gap-3">
            <button
              @click="deleteTarget = null"
              class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400"
            >
              Hủy
            </button>
            <button
              @click="doDelete"
              :disabled="deleting"
              class="px-4 py-2 text-sm rounded-lg bg-red-500 text-white hover:bg-red-600 disabled:opacity-50"
            >
              {{ deleting ? 'Đang xóa...' : 'Xóa' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Confirm Force Delete -->
    <Teleport to="body">
      <div
        v-if="forceDeleteTarget"
        class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4"
        @click.self="forceDeleteTarget = null"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-sm p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-500/10 flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Xóa vĩnh viễn</h3>
              <p class="text-xs text-red-500">Hành động này không thể hoàn tác!</p>
            </div>
          </div>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Bạn có chắc muốn xóa vĩnh viễn khóa học
            <strong class="text-gray-800 dark:text-white/90">{{ forceDeleteTarget.name }}</strong>?
          </p>
          <div class="flex justify-end gap-3">
            <button
              @click="forceDeleteTarget = null"
              class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400"
            >
              Hủy
            </button>
            <button
              @click="doForceDelete"
              :disabled="forceDeleting"
              class="px-4 py-2 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700 disabled:opacity-50"
            >
              {{ forceDeleting ? 'Đang xóa...' : 'Xóa vĩnh viễn' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══════ REUSABLE BULK ACTIONS COMPONENT ═══════ -->
    <BulkActions
      ref="bulkActionsRef"
      :count="isTrashed ? trashedSelectedIds.size : selectedIds.size"
      itemName="khóa học"
      :is-trashed="isTrashed"
      :loading="bulkUpdating || bulkDeleting || bulkRestoring || bulkForceDeleting"
      @publish="bulkToggleStatus(1)"
      @draft="bulkToggleStatus(0)"
      @delete="doBulkDelete"
      @restore="doBulkRestore"
      @force-delete="doBulkForceDelete"
      @clear="isTrashed ? trashedSelectedIds.clear() : selectedIds.clear()"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { PlusIcon, TrashIcon, BoxCubeIcon } from '@/icons'
import BulkActions from '@/components/admin/BulkActions.vue'
import { courseService } from '@/services/course.service'
import { formatCurrency } from '@/utils/formatCurrency'

const toast = useToast()

interface Course {
  id: number
  name: string
  slug: string
  thumbnail?: string | null
  price: string
  sale_price?: string | null
  level: string
  status: number
  total_students: number
  teacher?: { id: number; name: string; slug: string } | null
  deleted_at?: string | null
}

// ── Tab state ─────────────────────────────────────────────────
const isTrashed = ref(false)

// ── Active state ──────────────────────────────────────────────
const courses     = ref<Course[]>([])
const pagination  = ref<any>(null)
const loading     = ref(true)
const currentPage = ref(1)
const togglingId  = ref<number | null>(null)
const deleteTarget = ref<Course | null>(null)
const deleting    = ref(false)

// ── Bulk selection (active) ───────────────────────────────────
const selectedIds = reactive(new Set<number>())
const bulkDeleting = ref(false)
const bulkUpdating = ref(false)

const isAllSelected = computed(() => courses.value.length > 0 && courses.value.every(c => selectedIds.has(c.id)))
const isIndeterminate = computed(() => selectedIds.size > 0 && !isAllSelected.value)

// ── Trashed state ─────────────────────────────────────────────
const trashedCourses    = ref<Course[]>([])
const trashedPagination = ref<any>(null)
const trashedLoading    = ref(false)
const trashedPage       = ref(1)
const trashedCount      = ref(0)
const restoringId       = ref<number | null>(null)
const forceDeleteTarget = ref<Course | null>(null)
const forceDeleting     = ref(false)

// ── Bulk selection (trashed) ──────────────────────────────────
const trashedSelectedIds = reactive(new Set<number>())
const bulkForceDeleting = ref(false)
const bulkRestoring     = ref(false)

const bulkActionsRef = ref<InstanceType<typeof BulkActions> | null>(null)

const isTrashedAllSelected = computed(() => trashedCourses.value.length > 0 && trashedCourses.value.every(c => trashedSelectedIds.has(c.id)))
const isTrashedIndeterminate = computed(() => trashedSelectedIds.size > 0 && !isTrashedAllSelected.value)

// ── Filters ───────────────────────────────────────────────────
const filters = reactive({ search: '', status: '', level: '' })
const trashedFilters = reactive({ search: '' })

let debounceTimer: ReturnType<typeof setTimeout> | null = null
function debouncedFetch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchPage(1), 400)
}

let trashedDebounceTimer: ReturnType<typeof setTimeout> | null = null
function debouncedFetchTrashed() {
  if (trashedDebounceTimer) clearTimeout(trashedDebounceTimer)
  trashedDebounceTimer = setTimeout(() => fetchTrashedPage(1), 400)
}

// ── Tab switching ─────────────────────────────────────────────
function switchTab(trashed: boolean) {
  if (isTrashed.value === trashed) return
  isTrashed.value = trashed
  if (trashed) {
    fetchTrashedPage(1)
  }
}

// ── Active: Select toggles ────────────────────────────────────
function toggleSelectAll() {
  if (isAllSelected.value) {
    courses.value.forEach(c => selectedIds.delete(c.id))
  } else {
    courses.value.forEach(c => selectedIds.add(c.id))
  }
}

function toggleSelect(id: number) {
  if (selectedIds.has(id)) selectedIds.delete(id)
  else selectedIds.add(id)
}

// ── Trashed: Select toggles ──────────────────────────────────
function toggleTrashedSelectAll() {
  if (isTrashedAllSelected.value) {
    trashedCourses.value.forEach(c => trashedSelectedIds.delete(c.id))
  } else {
    trashedCourses.value.forEach(c => trashedSelectedIds.add(c.id))
  }
}

function toggleTrashedSelect(id: number) {
  if (trashedSelectedIds.has(id)) trashedSelectedIds.delete(id)
  else trashedSelectedIds.add(id)
}

// ── Active: Fetch ─────────────────────────────────────────────
async function fetchPage(page = 1) {
  loading.value = true
  currentPage.value = page
  try {
    const params: Record<string, any> = { page, per_page: 15 }
    if (filters.search) params.search = filters.search
    if (filters.status !== '') params.status = filters.status
    if (filters.level) params.level = filters.level

    const res = await courseService.index(params)
    courses.value = res.data.data
    pagination.value = res.data.pagination
  } catch {
    toast.error('Không thể tải khóa học')
  } finally {
    loading.value = false
  }
}

// ── Trashed: Fetch ────────────────────────────────────────────
async function fetchTrashedPage(page = 1) {
  trashedLoading.value = true
  trashedPage.value = page
  try {
    const params: Record<string, any> = { page, per_page: 15 }
    if (trashedFilters.search) params.search = trashedFilters.search

    const res = await courseService.trashed(params)
    trashedCourses.value = res.data.data
    trashedPagination.value = res.data.pagination
    trashedCount.value = res.data.pagination?.total || res.data.data?.length || 0
  } catch {
    toast.error('Không thể tải thùng rác')
  } finally {
    trashedLoading.value = false
  }
}

// Lấy count thùng rác (gọi lúc mount để hiển thị badge)
async function fetchTrashedCount() {
  try {
    const res = await courseService.trashed({ per_page: 1 })
    trashedCount.value = res.data.pagination?.total || res.data.data?.length || 0
  } catch {
    // im lặng
  }
}

onMounted(() => {
  fetchPage()
  fetchTrashedCount()
})

// ── Helpers ───────────────────────────────────────────────────
function levelLabel(level: string) {
  return { beginner: 'Cơ bản', intermediate: 'Trung cấp', advanced: 'Nâng cao' }[level] || level
}

function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

// ── Active: Toggle status ─────────────────────────────────────
async function toggleStatus(course: Course) {
  togglingId.value = course.id
  try {
    await courseService.toggleStatus(course.id)
    course.status = course.status === 1 ? 0 : 1
    toast.success(`Đã ${course.status === 1 ? 'đăng' : 'chuyển về nháp'} khóa học`)
  } catch {
    toast.error('Không thể cập nhật trạng thái')
  } finally {
    togglingId.value = null
  }
}

// ── Active: Delete (soft) ─────────────────────────────────────
function confirmDelete(course: Course) {
  deleteTarget.value = course
}

async function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await courseService.destroy(deleteTarget.value.id)
    toast.success('Xóa khóa học thành công')
    deleteTarget.value = null
    fetchPage(currentPage.value)
    fetchTrashedCount()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Xóa thất bại')
  } finally {
    deleting.value = false
  }
}

// ── Active: Bulk delete ───────────────────────────────────────
async function doBulkDelete() {
  bulkDeleting.value = true
  try {
    await courseService.bulkDelete([...selectedIds])
    toast.success(`Đã xóa ${selectedIds.size} khóa học`)
    selectedIds.clear()
    bulkActionsRef.value?.closeModal()
    fetchPage(currentPage.value)
    fetchTrashedCount()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Xóa nhiều thất bại')
  } finally {
    bulkDeleting.value = false
  }
}

// ── Active: Bulk toggle status ────────────────────────────────
async function bulkToggleStatus(status: number) {
  bulkUpdating.value = true
  try {
    const ids = [...selectedIds]
    await Promise.all(ids.map(id => courseService.update(id, { status })))
    toast.success(`Đã cập nhật ${ids.length} khóa học`)
    selectedIds.clear()
    bulkActionsRef.value?.closeModal()
    fetchPage(currentPage.value)
  } catch {
    toast.error('Cập nhật trạng thái thất bại')
  } finally {
    bulkUpdating.value = false
  }
}

// ── Trashed: Restore ──────────────────────────────────────────
async function doRestore(course: Course) {
  restoringId.value = course.id
  try {
    await courseService.restore(course.id)
    toast.success(`Đã khôi phục "${course.name}"`)
    fetchTrashedPage(trashedPage.value)
    fetchTrashedCount()
    // Refresh active list nếu cần
    fetchPage(currentPage.value)
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Khôi phục thất bại')
  } finally {
    restoringId.value = null
  }
}

// ── Trashed: Force delete ─────────────────────────────────────
function confirmForceDelete(course: Course) {
  forceDeleteTarget.value = course
}

async function doForceDelete() {
  if (!forceDeleteTarget.value) return
  forceDeleting.value = true
  try {
    await courseService.forceDelete(forceDeleteTarget.value.id)
    toast.success('Đã xóa vĩnh viễn khóa học')
    forceDeleteTarget.value = null
    fetchTrashedPage(trashedPage.value)
    fetchTrashedCount()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Xóa vĩnh viễn thất bại')
  } finally {
    forceDeleting.value = false
  }
}

// ── Trashed: Bulk restore ─────────────────────────────────────
async function doBulkRestore() {
  bulkRestoring.value = true
  try {
    await courseService.bulkRestore([...trashedSelectedIds])
    toast.success(`Đã khôi phục ${trashedSelectedIds.size} khóa học`)
    trashedSelectedIds.clear()
    bulkActionsRef.value?.closeModal()
    fetchTrashedPage(trashedPage.value)
    fetchTrashedCount()
    fetchPage(currentPage.value)
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Khôi phục nhiều thất bại')
  } finally {
    bulkRestoring.value = false
  }
}

// ── Trashed: Bulk force delete ────────────────────────────────
async function doBulkForceDelete() {
  bulkForceDeleting.value = true
  try {
    await courseService.bulkForceDelete([...trashedSelectedIds])
    toast.success(`Đã xóa vĩnh viễn ${trashedSelectedIds.size} khóa học`)
    trashedSelectedIds.clear()
    bulkActionsRef.value?.closeModal()
    fetchTrashedPage(trashedPage.value)
    fetchTrashedCount()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Xóa vĩnh viễn nhiều thất bại')
  } finally {
    bulkForceDeleting.value = false
  }
}
</script>

<style scoped>
.input-field {
  @apply h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm text-gray-800
         dark:border-gray-700 dark:text-white/90 dark:bg-gray-900
         focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400;
}
</style>
