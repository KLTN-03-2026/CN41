<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Người dùng</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Quản trị viên, Nhân viên và Giáo viên
        </p>
      </div>
      <button
        v-if="!isTrashed"
        @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors"
      >
        <PlusIcon class="w-4 h-4" /> Thêm người dùng
      </button>
    </div>

    <!-- Tabs + Search & Filters -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
      <div class="flex gap-1 p-1 bg-gray-100 dark:bg-white/5 rounded-lg shrink-0">
        <button
          @click="switchTab(false)"
          :class="!isTrashed ? 'bg-white dark:bg-white/10 shadow-sm font-medium' : ''"
          class="px-3 py-1.5 text-sm rounded-md text-gray-600 dark:text-gray-300 transition-colors"
        >
          Tất cả
        </button>
        <button
          @click="switchTab(true)"
          :class="isTrashed ? 'bg-white dark:bg-white/10 shadow-sm font-medium' : ''"
          class="px-3 py-1.5 text-sm rounded-md text-gray-600 dark:text-gray-300 transition-colors"
        >
          Thùng rác
          <span v-if="trashedCount" class="ml-1 text-xs text-red-500">({{ trashedCount }})</span>
        </button>
      </div>

      <div class="flex flex-1 gap-2 flex-wrap sm:flex-nowrap w-full">
        <!-- Search -->
        <div class="relative flex-1 min-w-[200px]">
          <input
            v-model="search"
            @input="debouncedFetch"
            type="text"
            placeholder="Tìm tên, email..."
            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
          />
          <svg
            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
        </div>

        <!-- Role Filter -->
        <select
          v-model="roleFilter"
          @change="loadPage(1)"
          class="px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none w-[140px]"
        >
          <option value="">Tất cả vai trò</option>
          <option v-for="r in roles" :key="r.id" :value="r.name">{{ formatRole(r.name) }}</option>
        </select>

        <!-- Status Filter -->
        <select
          v-model="statusFilter"
          @change="loadPage(1)"
          class="px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none w-[140px]"
        >
          <option value="">Tất cả trạng thái</option>
          <option value="1">Đang hoạt động</option>
          <option value="0">Bị khóa</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div
      class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/5 overflow-hidden"
    >
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[800px]">
          <thead>
            <tr
              class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-white/[0.02]"
            >
              <th class="w-10 px-4 py-3">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  :indeterminate="isIndeterminate"
                  @change="toggleSelectAll"
                  class="rounded border-gray-300 dark:border-gray-600 text-blue-500 focus:ring-blue-500/20"
                />
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">
                Người dùng
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">
                Vai trò
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">
                Trạng thái
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">
                Xác minh
              </th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">
                Ngày tạo
              </th>
              <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 px-4 py-3">
                Thao tác
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <!-- Loading skeleton -->
            <template v-if="loading">
              <tr v-for="i in 5" :key="i">
                <td class="px-4 py-3">
                  <div class="w-4 h-4 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 animate-pulse"
                    ></div>
                    <div class="space-y-1.5">
                      <div
                        class="h-4 w-24 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"
                      ></div>
                      <div
                        class="h-3 w-32 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"
                      ></div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div
                    class="h-5 w-16 bg-gray-100 dark:bg-gray-800 rounded-full animate-pulse"
                  ></div>
                </td>
                <td class="px-4 py-3">
                  <div
                    class="h-5 w-20 bg-gray-100 dark:bg-gray-800 rounded-full animate-pulse"
                  ></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-16 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-24 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div
                    class="h-6 w-20 bg-gray-100 dark:bg-gray-800 rounded animate-pulse ml-auto"
                  ></div>
                </td>
              </tr>
            </template>

            <!-- Data rows -->
            <template v-else-if="users.length">
              <tr
                v-for="u in users"
                :key="u.id"
                class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors"
              >
                <td class="px-4 py-3">
                  <input
                    type="checkbox"
                    :checked="selectedIds.has(u.id)"
                    @change="toggleSelect(u.id)"
                    class="rounded border-gray-300 dark:border-gray-600 text-blue-500 focus:ring-blue-500/20"
                  />
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div
                      :class="getAvatarColor(u)"
                      class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm shrink-0 shadow-sm border border-white/50"
                    >
                      {{ u.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div>
                      <p class="font-medium text-gray-800 dark:text-white/90 leading-tight">
                        {{ u.name }}
                      </p>
                      <p class="text-xs text-gray-500 mt-0.5">{{ u.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <span
                    v-if="u.roles && u.roles.length"
                    :class="getRoleBadge(u.roles[0]?.name)"
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                  >
                    {{ formatRole(u.roles[0]?.name) }}
                  </span>
                  <span v-else class="text-xs text-gray-400">Không có</span>
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="
                      u.status === 1
                        ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                        : 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400'
                    "
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                  >
                    <span
                      class="w-1.5 h-1.5 rounded-full mr-1.5"
                      :class="u.status === 1 ? 'bg-green-500' : 'bg-red-500'"
                    ></span>
                    {{ u.status === 1 ? 'Hoạt động' : 'Đã khóa' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span
                    v-if="u.email_verified_at"
                    class="inline-flex items-center text-green-500"
                    title="Đã xác minh email"
                  >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"
                      />
                    </svg>
                  </span>
                  <span v-else class="text-xs text-gray-400">Chưa</span>
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                  {{ formatDate(u.created_at || '') }}
                </td>
                <td class="px-4 py-3 text-right">
                  <div v-if="!isTrashed" class="flex items-center justify-end gap-1">
                    <button
                      @click="openResetPassword(u)"
                      class="p-1.5 rounded-lg hover:bg-orange-50 dark:hover:bg-orange-500/10 text-gray-500 hover:text-orange-500 transition-colors"
                      title="Đổi mật khẩu"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                        />
                      </svg>
                    </button>
                    <button
                      @click="openEdit(u)"
                      class="p-1.5 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-500/10 text-gray-500 hover:text-blue-500 transition-colors"
                      title="Sửa"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                      </svg>
                    </button>
                    <button
                      @click="confirmDelete(u)"
                      class="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 text-gray-500 hover:text-red-500 transition-colors"
                      title="Xóa"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                      </svg>
                    </button>
                  </div>
                  <div v-else class="flex items-center justify-end gap-1">
                    <button
                      @click="doRestore(u.id)"
                      :disabled="restoringId === u.id"
                      class="p-1.5 rounded-lg hover:bg-green-50 dark:hover:bg-green-500/10 text-gray-500 hover:text-green-600 transition-colors disabled:opacity-50"
                      title="Khôi phục"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                      </svg>
                    </button>
                    <button
                      @click="confirmForceDelete(u)"
                      class="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 text-gray-500 hover:text-red-500 transition-colors"
                      title="Xóa vĩnh viễn"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </template>

            <!-- Empty -->
            <tr v-else>
              <td colspan="7" class="px-4 py-16 text-center">
                <div class="flex flex-col items-center justify-center text-gray-400">
                  <svg
                    class="w-12 h-12 mb-3 text-gray-300 dark:text-gray-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1"
                  >
                    <path
                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                    />
                  </svg>
                  <p class="text-sm font-medium">
                    {{ isTrashed ? 'Thùng rác trống.' : 'Không tìm thấy người dùng nào.' }}
                  </p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination.last_page > 1"
        class="flex justify-end px-4 py-3 border-t border-gray-100 dark:border-gray-700"
      >
        <PaginationBar
          :current-page="pagination.current_page"
          :last-page="pagination.last_page"
          @change="loadPage"
        />
      </div>
    </div>

    <!-- Bulk Actions Bar -->
    <div
      v-if="selectedIds.size > 0"
      class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[90] flex items-center gap-3 px-5 py-3 bg-gray-900/95 backdrop-blur shadow-2xl dark:bg-white text-white dark:text-gray-900 rounded-2xl border border-white/10 dark:border-gray-200"
    >
      <div class="flex items-center gap-2 pr-4 border-r border-white/20 dark:border-gray-200">
        <div
          class="w-6 h-6 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center text-xs font-bold"
        >
          {{ selectedIds.size }}
        </div>
        <span class="text-sm font-medium">đã chọn</span>
      </div>

      <div class="flex items-center gap-2" v-if="!isTrashed">
        <button
          @click="doBulkAction('activate')"
          :disabled="bulkLoading"
          class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-green-400 dark:text-green-600 hover:bg-green-400/10 dark:hover:bg-green-50 rounded-lg transition-colors disabled:opacity-50"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          Mở khóa
        </button>
        <button
          @click="doBulkAction('deactivate')"
          :disabled="bulkLoading"
          class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-yellow-400 dark:text-yellow-600 hover:bg-yellow-400/10 dark:hover:bg-yellow-50 rounded-lg transition-colors disabled:opacity-50"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
            />
          </svg>
          Khóa
        </button>

        <!-- Bulk Role Dropdown -->
        <div class="relative group">
          <button
            class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-blue-400 dark:text-blue-600 hover:bg-blue-400/10 dark:hover:bg-blue-50 rounded-lg transition-colors disabled:opacity-50"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"
              />
            </svg>
            Gán vai trò
          </button>
          <div
            class="absolute bottom-full left-0 mb-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-1 hidden group-hover:block z-50"
          >
            <button
              v-for="r in roles"
              :key="r.id"
              @click="doBulkAssignRole(r.name)"
              class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
            >
              {{ formatRole(r.name) }}
            </button>
          </div>
        </div>

        <div class="w-px h-5 bg-white/20 dark:bg-gray-200 mx-1"></div>
        <button
          @click="doBulkDelete"
          :disabled="bulkLoading"
          class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-red-400 dark:text-red-600 hover:bg-red-400/10 dark:hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
            />
          </svg>
          Xóa
        </button>
      </div>

      <div class="flex items-center gap-2" v-else>
        <button
          @click="doBulkRestore"
          :disabled="bulkLoading"
          class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-green-400 dark:text-green-600 hover:bg-green-400/10 dark:hover:bg-green-50 rounded-lg transition-colors disabled:opacity-50"
        >
          Khôi phục
        </button>
        <button
          @click="doBulkForceDelete"
          :disabled="bulkLoading"
          class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-red-400 dark:text-red-600 hover:bg-red-400/10 dark:hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50"
        >
          Xóa vĩnh viễn
        </button>
      </div>

      <button
        @click="selectedIds.clear()"
        class="p-1.5 text-gray-400 hover:text-white dark:hover:text-gray-900 transition-colors rounded-lg hover:bg-white/10 dark:hover:bg-gray-100 ml-2"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Create/Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm"
      @click.self="showModal = false"
    >
      <div
        class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 border border-gray-100 dark:border-gray-800"
      >
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-5">
          {{ editingUser ? 'Cập nhật người dùng' : 'Thêm người dùng mới' }}
        </h3>
        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
              >Họ và tên *</label
            >
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-white/5 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
            />
            <p v-if="formErrors.name" class="text-xs text-red-500 mt-1">{{ formErrors.name[0] }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
              >Email *</label
            >
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-white/5 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
            />
            <p v-if="formErrors.email" class="text-xs text-red-500 mt-1">
              {{ formErrors.email[0] }}
            </p>
          </div>
          <div v-if="!editingUser">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
              >Mật khẩu *</label
            >
            <input
              v-model="form.password"
              type="password"
              required
              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-white/5 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
            />
            <p v-if="formErrors.password" class="text-xs text-red-500 mt-1">
              {{ formErrors.password[0] }}
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
              >Vai trò</label
            >
            <select
              v-model="form.role"
              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-white/5 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
            >
              <option value="">Không gán</option>
              <option v-for="r in roles" :key="r.id" :value="r.name">
                {{ formatRole(r.name) }}
              </option>
            </select>
            <p v-if="formErrors.role" class="text-xs text-red-500 mt-1">{{ formErrors.role[0] }}</p>
          </div>
          <p v-if="formError" class="text-sm text-red-500 font-medium">{{ formError }}</p>

          <div
            class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-800"
          >
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 text-sm font-medium rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 transition-colors"
            >
              Huỷ
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-5 py-2 text-sm font-medium rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2"
            >
              <svg
                v-if="submitting"
                class="animate-spin h-4 w-4 text-white"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              {{ submitting ? 'Đang lưu...' : 'Lưu người dùng' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Reset Password Modal -->
    <div
      v-if="showResetModal"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm"
      @click.self="showResetModal = false"
    >
      <div
        class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 border border-gray-100 dark:border-gray-800"
      >
        <div class="flex items-center gap-3 mb-4 text-orange-500">
          <div class="p-2 bg-orange-100 dark:bg-orange-500/10 rounded-full">
            <svg
              class="w-6 h-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
              />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-800 dark:text-white">Đổi mật khẩu</h3>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
          Nhập mật khẩu mới cho tài khoản
          <strong class="text-gray-800 dark:text-white">{{ resetTarget?.email }}</strong
          >.
        </p>

        <form @submit.prevent="submitResetPassword" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
              >Mật khẩu mới *</label
            >
            <input
              v-model="resetPasswordForm.password"
              type="password"
              required
              minlength="8"
              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-white/5 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
              >Xác nhận mật khẩu *</label
            >
            <input
              v-model="resetPasswordForm.confirm"
              type="password"
              required
              minlength="8"
              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-white/5 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all"
            />
          </div>
          <p v-if="resetPasswordError" class="text-sm text-red-500 font-medium">
            {{ resetPasswordError }}
          </p>

          <div class="flex justify-end gap-3 pt-2 mt-4">
            <button
              type="button"
              @click="showResetModal = false"
              class="px-4 py-2 text-sm font-medium rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 transition-colors"
            >
              Huỷ
            </button>
            <button
              type="submit"
              :disabled="resetting"
              class="px-5 py-2 text-sm font-medium rounded-lg bg-orange-500 text-white hover:bg-orange-600 transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2"
            >
              <svg
                v-if="resetting"
                class="animate-spin h-4 w-4 text-white"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              {{ resetting ? 'Đang đổi...' : 'Xác nhận đổi' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete Modal -->
    <ConfirmModal
      :show="showDeleteModal"
      :title="isTrashed ? 'Xóa vĩnh viễn' : 'Xác nhận xóa'"
      :loading="deleteLoading"
      :confirm-text="isTrashed ? 'Xóa vĩnh viễn' : 'Xóa'"
      loading-text="Đang xử lý..."
      @cancel="showDeleteModal = false"
      @confirm="doDelete"
    >
      <p>
        Bạn có chắc muốn xoá người dùng
        <strong class="text-gray-800 dark:text-white/90">{{ deletingUser?.name }}</strong
        >?
      </p>
      <div
        v-if="!isTrashed"
        class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 rounded-lg text-sm flex gap-2"
      >
        <svg
          class="w-5 h-5 shrink-0"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span
          >Tài khoản sẽ được chuyển vào <strong>Thùng rác</strong> và không thể đăng nhập. Bạn có
          thể khôi phục lại sau.</span
        >
      </div>
      <div
        v-else
        class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 rounded-lg text-sm flex gap-2"
      >
        <svg
          class="w-5 h-5 shrink-0"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        <span>Hành động này sẽ xóa vĩnh viễn dữ liệu khỏi hệ thống và không thể hoàn tác!</span>
      </div>
    </ConfirmModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { userService } from '@/services/user.service'
import type { AdminUser } from '@/types'
import { formatDate } from '@/utils/formatDate'
import { PlusIcon } from '@/components/icons'
import PaginationBar from '@/components/common/PaginationBar.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useToast } from 'vue-toastification'
import { useAdminAuthStore } from '@/stores/adminAuth.store'

const toast = useToast()
const authStore = useAdminAuthStore()

// Kiểm tra xem người dùng hiện tại có phải super-admin không
const isSuperAdmin = computed(() => {
  return authStore.user?.roles?.includes('super-admin') || false
})

// Các role được phép quản lý (cho non-super-admin)
const ALLOWED_ROLES = ['student', 'teacher']

// ── State ──
const users = ref<AdminUser[]>([])
const roles = ref<{ id: number; name: string }[]>([])
const loading = ref(false)
const search = ref('')
const roleFilter = ref('')
const statusFilter = ref('')
const isTrashed = ref(false)
const trashedCount = ref(0)
const pagination = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

// Selection
const selectedIds = ref<Set<number>>(new Set())
const isAllSelected = computed(
  () => users.value.length > 0 && selectedIds.value.size === users.value.length,
)
const isIndeterminate = computed(() => selectedIds.value.size > 0 && !isAllSelected.value)
const toggleSelectAll = () => {
  if (isAllSelected.value) selectedIds.value.clear()
  else users.value.forEach((u) => selectedIds.value.add(u.id))
}
const toggleSelect = (id: number) => {
  if (selectedIds.value.has(id)) selectedIds.value.delete(id)
  else selectedIds.value.add(id)
}

// ── Helpers ──
function getAvatarColor(user: AdminUser) {
  const role = user.roles?.[0]?.name || ''
  if (role === 'super-admin') return 'bg-gradient-to-br from-red-400 to-rose-600 text-white'
  if (role === 'admin') return 'bg-gradient-to-br from-purple-400 to-indigo-600 text-white'
  if (role === 'teacher') return 'bg-gradient-to-br from-cyan-400 to-blue-600 text-white'
  return 'bg-gradient-to-br from-gray-300 to-gray-500 text-white dark:from-gray-600 dark:to-gray-800'
}

function getRoleBadge(roleName?: string) {
  if (roleName === 'super-admin')
    return 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400 border border-red-200 dark:border-red-500/30'
  if (roleName === 'admin')
    return 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-400 border border-purple-200 dark:border-purple-500/30'
  if (roleName === 'teacher')
    return 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30'
  return 'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400 border border-gray-200 dark:border-gray-500/30'
}

function formatRole(roleName?: string) {
  if (!roleName) return 'Không có'
  const map: Record<string, string> = {
    'super-admin': 'Super Admin',
    admin: 'Admin',
    teacher: 'Giáo viên',
    manager: 'Quản lý',
    staff: 'Nhân viên',
  }
  return map[roleName] || roleName.charAt(0).toUpperCase() + roleName.slice(1)
}

// ── Fetch ──
let debounceTimer: ReturnType<typeof setTimeout>
const debouncedFetch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => loadPage(1), 300)
}

async function fetchRoles() {
  try {
    const res = await userService.getRoles()
    const allRoles = res.data.data as { id: number; name: string }[]

    // Nếu không phải super-admin, chỉ hiển thị role student và teacher
    if (isSuperAdmin.value) {
      roles.value = allRoles
    } else {
      roles.value = allRoles.filter((r) => ALLOWED_ROLES.includes(r.name))
    }
  } catch (err) {
    console.error('Failed to load roles', err)
  }
}

async function loadPage(page = 1) {
  loading.value = true
  selectedIds.value.clear()
  try {
    const fn = isTrashed.value ? userService.trashed : userService.index
    const params: Record<string, unknown> = { page, per_page: pagination.per_page }
    if (search.value) params.search = search.value
    if (roleFilter.value) params.role = roleFilter.value
    if (statusFilter.value) params.status = statusFilter.value

    const res = await fn(params)
    users.value = res.data.data
    Object.assign(pagination, res.data.pagination)
  } catch {
    users.value = []
  } finally {
    loading.value = false
  }
}

async function fetchTrashedCount() {
  try {
    const res = await userService.trashed({ per_page: 1 })
    trashedCount.value = res.data.pagination?.total ?? 0
  } catch {
    trashedCount.value = 0
  }
}

function switchTab(trashed: boolean) {
  isTrashed.value = trashed
  search.value = ''
  roleFilter.value = ''
  statusFilter.value = ''
  loadPage(1)
  if (!trashed) fetchTrashedCount()
}

// ── Create / Edit Modal ──
const showModal = ref(false)
const editingUser = ref<AdminUser | null>(null)
const submitting = ref(false)
const formError = ref('')
const formErrors = ref<Record<string, string[]>>({})
const form = reactive({ name: '', email: '', password: '', role: '' })

function openCreate() {
  editingUser.value = null
  Object.assign(form, { name: '', email: '', password: '', role: '' })
  formError.value = ''
  formErrors.value = {}
  showModal.value = true
}

function openEdit(u: AdminUser) {
  editingUser.value = u
  Object.assign(form, {
    name: u.name,
    email: u.email,
    password: '',
    role: u.roles?.[0]?.name || '',
  })
  formError.value = ''
  formErrors.value = {}
  showModal.value = true
}

async function submitForm() {
  submitting.value = true
  formError.value = ''
  formErrors.value = {}
  try {
    const data: Record<string, unknown> = { name: form.name, email: form.email }
    if (form.role) data.role = form.role
    if (!editingUser.value && form.password) data.password = form.password

    if (editingUser.value) {
      await userService.update(editingUser.value.id, data)
      toast.success('Cập nhật người dùng thành công!')
    } else {
      await userService.store(data)
      toast.success('Thêm người dùng mới thành công!')
    }
    showModal.value = false
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch (err) {
    const error = err as { response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } }
    if (error.response?.status === 422 && error.response.data?.errors) {
      formErrors.value = error.response.data.errors
    } else {
      formError.value = error.response?.data?.message || 'Có lỗi xảy ra.'
    }
  } finally {
    submitting.value = false
  }
}

// ── Reset Password Modal ──
const showResetModal = ref(false)
const resetting = ref(false)
const resetPasswordError = ref('')
const resetTarget = ref<AdminUser | null>(null)
const resetPasswordForm = reactive({ password: '', confirm: '' })

function openResetPassword(u: AdminUser) {
  resetTarget.value = u
  resetPasswordForm.password = ''
  resetPasswordForm.confirm = ''
  resetPasswordError.value = ''
  showResetModal.value = true
}

async function submitResetPassword() {
  if (resetPasswordForm.password !== resetPasswordForm.confirm) {
    resetPasswordError.value = 'Mật khẩu xác nhận không khớp.'
    return
  }

  resetting.value = true
  resetPasswordError.value = ''

  try {
    await userService.update(resetTarget.value!.id, { password: resetPasswordForm.password })
    toast.success('Đã đổi mật khẩu thành công!')
    showResetModal.value = false
  } catch (err) {
    const error = err as { response?: { data?: { message?: string } } }
    resetPasswordError.value = error.response?.data?.message || 'Đổi mật khẩu thất bại.'
  } finally {
    resetting.value = false
  }
}

// ── Delete & Restore ──
const showDeleteModal = ref(false)
const deletingUser = ref<AdminUser | null>(null)
const deleteLoading = ref(false)
const restoringId = ref<number | null>(null)

function confirmDelete(u: AdminUser) {
  deletingUser.value = u
  showDeleteModal.value = true
}
function confirmForceDelete(u: AdminUser) {
  deletingUser.value = u
  showDeleteModal.value = true
}

async function doDelete() {
  if (!deletingUser.value) return
  deleteLoading.value = true
  try {
    if (isTrashed.value) await userService.forceDelete(deletingUser.value.id)
    else await userService.destroy(deletingUser.value.id)
    toast.success(isTrashed.value ? 'Đã xóa vĩnh viễn!' : 'Đã chuyển vào thùng rác!')
    showDeleteModal.value = false
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch (err) {
    const error = err as { response?: { data?: { message?: string } } }
    toast.error(error.response?.data?.message || 'Xoá thất bại.')
  } finally {
    deleteLoading.value = false
  }
}

async function doRestore(id: number) {
  restoringId.value = id
  try {
    await userService.restore(id)
    toast.success('Khôi phục thành công!')
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch {
    toast.error('Khôi phục thất bại.')
  } finally {
    restoringId.value = null
  }
}

// ── Bulk Actions ──
const bulkLoading = ref(false)

async function doBulkAction(action: string) {
  bulkLoading.value = true
  try {
    await userService.bulkAction([...selectedIds.value], action)
    toast.success(`Đã ${action === 'activate' ? 'mở khóa' : 'khóa'} hàng loạt thành công!`)
    loadPage(pagination.current_page)
  } catch {
    toast.error('Thực hiện thất bại.')
  } finally {
    bulkLoading.value = false
  }
}

async function doBulkAssignRole(roleName: string) {
  bulkLoading.value = true
  try {
    await userService.bulkAssignRole([...selectedIds.value], roleName)
    toast.success('Gán vai trò hàng loạt thành công!')
    loadPage(pagination.current_page)
  } catch {
    toast.error('Gán vai trò thất bại.')
  } finally {
    bulkLoading.value = false
  }
}

async function doBulkDelete() {
  bulkLoading.value = true
  try {
    await userService.bulkDelete([...selectedIds.value])
    toast.success('Xoá hàng loạt thành công!')
    selectedIds.value.clear()
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch {
    toast.error('Xoá hàng loạt thất bại.')
  } finally {
    bulkLoading.value = false
  }
}

async function doBulkRestore() {
  bulkLoading.value = true
  try {
    await userService.bulkRestore([...selectedIds.value])
    toast.success('Khôi phục hàng loạt thành công!')
    selectedIds.value.clear()
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch {
    toast.error('Khôi phục hàng loạt thất bại.')
  } finally {
    bulkLoading.value = false
  }
}

async function doBulkForceDelete() {
  if (
    !confirm(
      'Bạn có chắc chắn muốn XÓA VĨNH VIỄN các người dùng đã chọn? Hành động này không thể hoàn tác!',
    )
  )
    return

  bulkLoading.value = true
  try {
    await userService.bulkForceDelete([...selectedIds.value])
    toast.success('Đã xóa vĩnh viễn hàng loạt thành công!')
    selectedIds.value.clear()
    loadPage(pagination.current_page)
    fetchTrashedCount()
  } catch {
    toast.error('Xóa vĩnh viễn thất bại.')
  } finally {
    bulkLoading.value = false
  }
}

// ── Init ──
onMounted(() => {
  fetchRoles()
  loadPage()
  fetchTrashedCount()
})
</script>
