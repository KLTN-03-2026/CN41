<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Danh mục</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Quản lý danh mục khóa học</p>
      </div>
      <div class="flex items-center gap-3">
        <template v-if="!isTrashed">
          <button
            @click="toggleAll"
            class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path v-if="allExpanded" stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
              <path v-else stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            {{ allExpanded ? 'Thu gọn tất cả' : 'Mở rộng tất cả' }}
          </button>
          <button @click="openCreate" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors">
            <PlusIcon class="w-4 h-4" />
            Thêm danh mục
          </button>
        </template>
      </div>
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
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
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

    <!-- Search (active) -->
    <div v-if="!isTrashed" class="mb-4">
      <div class="relative max-w-sm">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm danh mục..."
          class="w-full h-10 pl-10 pr-8 rounded-lg border border-gray-200 bg-white text-sm text-gray-800 dark:border-gray-700 dark:bg-white/5 dark:text-white/90 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-colors"
        />
        <button
          v-if="searchQuery"
          @click="searchQuery = ''"
          class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Search (trashed) -->
    <div v-if="isTrashed" class="mb-4">
      <div class="relative max-w-sm">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input
          v-model="trashedSearchQuery"
          type="text"
          placeholder="Tìm trong thùng rác..."
          class="w-full h-10 pl-10 pr-8 rounded-lg border border-gray-200 bg-white text-sm text-gray-800 dark:border-gray-700 dark:bg-white/5 dark:text-white/90 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-400 transition-colors"
          @input="debouncedFetchTrashed"
        />
        <button
          v-if="trashedSearchQuery"
          @click="trashedSearchQuery = ''; fetchTrashedCategories()"
          class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- ═══════════ ACTIVE TREE TABLE ═══════════ -->
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
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Tên danh mục</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Slug</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Trạng thái</th>
              <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <tr v-if="loading">
              <td colspan="5" class="text-center py-10 text-gray-400">
                <svg class="animate-spin w-6 h-6 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
              </td>
            </tr>
            <tr v-else-if="isSearching && !visibleCategories.length">
              <td colspan="5" class="text-center py-10 text-gray-400 text-sm">
                Không tìm thấy danh mục nào cho "<strong class="text-gray-600 dark:text-gray-300">{{ searchQuery }}</strong>"
              </td>
            </tr>
            <tr v-else-if="!allCategories.length">
              <td colspan="5" class="text-center py-10 text-gray-400 text-sm">Chưa có danh mục nào</td>
            </tr>
            <template v-for="(cat, idx) in visibleCategories" :key="cat.id">
              <tr
                class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                :class="[
                  cat.depth === 0 && idx > 0 ? 'border-t-2 !border-gray-200 dark:!border-gray-600' : '',
                  selectedIds.has(cat.id) ? 'bg-blue-50 dark:bg-blue-500/5' : ''
                ]"
              >
                <td class="w-10 px-4 py-2.5">
                  <input
                    type="checkbox"
                    :checked="selectedIds.has(cat.id)"
                    @change="toggleSelect(cat.id)"
                    class="w-4 h-4 rounded border-gray-300 text-blue-500 focus:ring-blue-500"
                  />
                </td>
                <td class="px-6 py-2.5">
                  <div class="flex items-center" :style="{ paddingLeft: cat.depth * 24 + 'px' }">
                    <button
                      v-if="hasChildren(cat.id)"
                      @click="toggleExpand(cat.id)"
                      class="mr-1.5 p-0.5 rounded hover:bg-gray-100 dark:hover:bg-white/10 transition-colors text-gray-400 dark:text-gray-500"
                    >
                      <svg
                        class="w-4 h-4 transition-transform duration-200"
                        :class="expandedIds.has(cat.id) ? 'rotate-90' : ''"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                      </svg>
                    </button>
                    <span v-else class="mr-1.5 w-5 inline-block"></span>

                    <span v-if="cat.depth > 0" class="text-gray-300 dark:text-gray-600 mr-1.5 font-mono text-xs select-none">
                      {{ isLastChild(cat, idx) ? '└─' : '├─' }}
                    </span>

                    <span :class="cat.depth === 0 ? 'text-blue-500' : 'text-gray-400 dark:text-gray-500'" class="mr-2 flex-shrink-0">
                      <svg v-if="cat.depth === 0" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                      </svg>
                      <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                      </svg>
                    </span>

                    <span class="font-medium" :class="cat.depth === 0 ? 'text-gray-800 dark:text-white/90' : 'text-gray-600 dark:text-gray-300'">
                      <span v-if="isSearching" v-html="highlightName(cat.name)"></span>
                      <template v-else>{{ cat.name }}</template>
                    </span>
                    <span
                      v-if="hasChildren(cat.id) && cat.depth === 0"
                      class="ml-2 px-1.5 py-0.5 text-[10px] rounded-full bg-blue-50 text-blue-500 dark:bg-blue-500/10 dark:text-blue-400 font-medium"
                    >
                      {{ getChildCount(cat.id) }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-2.5 text-gray-500 dark:text-gray-400 font-mono text-xs">{{ cat.slug }}</td>
                <td class="px-6 py-2.5">
                  <span
                    :class="cat.status === 1
                      ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                      : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ cat.status === 1 ? 'Hoạt động' : 'Ẩn' }}
                  </span>
                </td>
                <td class="px-6 py-2.5 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="openEdit(cat)"
                      class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10 transition-colors"
                      title="Chỉnh sửa"
                    >
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </button>
                    <button
                      @click="confirmDelete(cat)"
                      class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors"
                      title="Xóa"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Footer info -->
      <div v-if="allCategories.length" class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">
          <template v-if="isSearching">Tìm thấy {{ matchCount }} kết quả / </template>
          Tổng {{ allCategories.length }} danh mục
        </p>
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
          <span>Các danh mục trong thùng rác. Bạn có thể khôi phục hoặc xóa vĩnh viễn.</span>
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
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Tên danh mục</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Slug</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Đã xóa lúc</th>
              <th class="text-right text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <tr v-if="trashedLoading">
              <td colspan="5" class="text-center py-10 text-gray-400">
                <svg class="animate-spin w-6 h-6 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
              </td>
            </tr>
            <tr v-else-if="!trashedCategories.length">
              <td colspan="5" class="text-center py-10">
                <div class="flex flex-col items-center gap-2">
                  <svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  <p class="text-sm text-gray-400">Thùng rác trống</p>
                </div>
              </td>
            </tr>
            <tr
              v-for="cat in trashedCategories"
              :key="cat.id"
              :class="trashedSelectedIds.has(cat.id) ? 'bg-red-50 dark:bg-red-500/5' : ''"
              class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
            >
              <td class="w-10 px-4 py-3">
                <input
                  type="checkbox"
                  :checked="trashedSelectedIds.has(cat.id)"
                  @change="toggleTrashedSelect(cat.id)"
                  class="w-4 h-4 rounded border-gray-300 text-red-500 focus:ring-red-500"
                />
              </td>
              <td class="px-6 py-3">
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                  </svg>
                  <span class="font-medium text-gray-500 dark:text-gray-400">{{ cat.name }}</span>
                </div>
              </td>
              <td class="px-6 py-3 text-gray-500 dark:text-gray-500 font-mono text-xs">{{ cat.slug }}</td>
              <td class="px-6 py-3 text-gray-500 dark:text-gray-500 text-xs">
                {{ formatDate(cat.deleted_at) }}
              </td>
              <td class="px-6 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="doRestoreCategory(cat)"
                    :disabled="restoringId === cat.id"
                    class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg dark:hover:bg-green-500/10 transition-colors disabled:opacity-50"
                    title="Khôi phục"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                  </button>
                  <button
                    @click="confirmForceDeleteCategory(cat)"
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
          {{ trashedPagination.from }}–{{ trashedPagination.to }} / {{ trashedPagination.total }} danh mục
        </p>
        <div class="flex gap-1">
          <button
            v-for="p in trashedPagination.last_page"
            :key="p"
            @click="fetchTrashedCategories(p)"
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

    <!-- Modal Create/Edit -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4"
        @click.self="closeModal"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md p-6">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-5">
            {{ editingId ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' }}
          </h3>

          <form @submit.prevent="submitForm" class="space-y-4">
            <div>
              <label class="label-form">Tên danh mục <span class="text-red-500">*</span></label>
              <input
                v-model="form.name"
                type="text"
                class="input-field"
                :class="{ 'input-error': formErrors.name }"
                placeholder="Lập trình"
                @input="autoSlug"
              />
              <p v-if="formErrors.name" class="error-msg">{{ formErrors.name }}</p>
            </div>

            <div>
              <label class="label-form">Slug <span class="text-red-500">*</span></label>
              <input
                v-model="form.slug"
                type="text"
                class="input-field font-mono text-sm"
                :class="{ 'input-error': formErrors.slug }"
                placeholder="lap-trinh"
              />
              <p v-if="formErrors.slug" class="error-msg">{{ formErrors.slug }}</p>
            </div>

            <div>
              <label class="label-form">Danh mục cha</label>
              <select v-model="form.parent_id" class="input-field">
                <option :value="null">— Không có (danh mục gốc) —</option>
                <option
                  v-for="item in flatTree"
                  :key="item.id"
                  :value="item.id"
                  :disabled="item.id === editingId"
                >
                  {{ '—'.repeat(item.depth) }} {{ item.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="label-form">Mô tả</label>
              <textarea
                v-model="form.description"
                rows="2"
                class="input-field resize-none"
                placeholder="Mô tả ngắn..."
              />
            </div>

            <div>
              <label class="label-form">Trạng thái</label>
              <select v-model="form.status" class="input-field">
                <option :value="1">Hoạt động</option>
                <option :value="0">Ẩn</option>
              </select>
            </div>

            <p v-if="submitError" class="text-sm text-red-500">{{ submitError }}</p>

            <div class="flex justify-end gap-3 pt-2">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5"
              >
                Hủy
              </button>
              <button
                type="submit"
                :disabled="submitting"
                class="px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 disabled:opacity-50 flex items-center gap-2"
              >
                <svg v-if="submitting" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ editingId ? 'Cập nhật' : 'Tạo mới' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

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
            Bạn có chắc muốn xóa danh mục
            <strong class="text-gray-800 dark:text-white/90">{{ deleteTarget.name }}</strong>?
            Các danh mục con cũng sẽ bị xóa.
            <span class="block mt-1 text-xs text-gray-400">Danh mục sẽ được chuyển vào thùng rác.</span>
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

    <!-- Confirm Force Delete Category -->
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
            Bạn có chắc muốn xóa vĩnh viễn danh mục
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
              @click="doForceDeleteCategory"
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
      itemName="danh mục"
      :is-trashed="isTrashed"
      :loading="bulkUpdating || bulkDeleting || bulkRestoring || bulkForceDeleting"
      publishLabel="Hoạt động"
      draftLabel="Ẩn"
      @publish="bulkToggleStatus(1)"
      @draft="bulkToggleStatus(0)"
      @delete="doBulkDelete"
      @restore="doBulkRestoreCategories"
      @force-delete="doBulkForceDeleteCategories"
      @clear="isTrashed ? trashedSelectedIds.clear() : selectedIds.clear()"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import { useToast } from 'vue-toastification'
import { PlusIcon, TrashIcon } from '@/icons'
import BulkActions from '@/components/admin/BulkActions.vue'
import { categoriesApi } from '@/api/categoriesApi'

const toast = useToast()

interface Category {
  id: number
  name: string
  slug: string
  description?: string | null
  status: number
  depth: number
  is_root: boolean
  parent_id?: number | null
  deleted_at?: string | null
}

// ── Tab state ─────────────────────────────────────────────────
const isTrashed = ref(false)

// Dữ liệu gốc từ API (flat-tree đã sắp xếp theo cây)
const allCategories = ref<Category[]>([])
const flatTree      = ref<{ id: number; name: string; depth: number }[]>([])
const loading       = ref(true)

// Expand/collapse state: chứa các id đang mở
const expandedIds = ref<Set<number>>(new Set())
const allExpanded = ref(true)

// Search
const searchQuery = ref('')
const isSearching = computed(() => searchQuery.value.trim().length > 0)

// Tìm các id match keyword (bao gồm cả chuỗi cha để giữ cấu trúc cây)
const matchedIds = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return new Set<number>()

  const matched = new Set<number>()
  // Tìm tất cả danh mục match tên
  for (const cat of allCategories.value) {
    if (cat.name.toLowerCase().includes(q) || cat.slug.toLowerCase().includes(q)) {
      matched.add(cat.id)
    }
  }

  // Thêm ancestor chain cho mỗi item match → giữ cấu trúc cây
  const withAncestors = new Set<number>(matched)
  for (const id of matched) {
    const idx = allCategories.value.findIndex(c => c.id === id)
    if (idx < 0) continue
    const targetDepth = allCategories.value[idx].depth
    // Đi ngược lên tìm tổ tiên
    for (let i = idx - 1; i >= 0; i--) {
      if (allCategories.value[i].depth < targetDepth) {
        withAncestors.add(allCategories.value[i].id)
        // Tiếp tục tìm lên cấp cao hơn
      }
    }
  }
  return withAncestors
})

// Đếm số kết quả match trực tiếp (không tính ancestor)
const matchCount = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return 0
  return allCategories.value.filter(c =>
    c.name.toLowerCase().includes(q) || c.slug.toLowerCase().includes(q)
  ).length
})

const showModal   = ref(false)
const editingId   = ref<number | null>(null)
const submitting  = ref(false)
const submitError = ref('')
const formErrors  = ref<Record<string, string>>({})

const defaultForm = () => ({
  name: '',
  slug: '',
  description: '',
  status: 1,
  parent_id: null as number | null,
})
const form = ref(defaultForm())

const deleteTarget = ref<Category | null>(null)
const deleting     = ref(false)

// ── Bulk selection (active) ───────────────────────────────────
const selectedIds = reactive(new Set<number>())
const bulkDeleting = ref(false)
const bulkUpdating = ref(false)

const isAllSelected = computed(() => visibleCategories.value.length > 0 && visibleCategories.value.every(c => selectedIds.has(c.id)))
const isIndeterminate = computed(() => selectedIds.size > 0 && !isAllSelected.value)

// ── Trashed state ─────────────────────────────────────────────
const trashedCategories  = ref<Category[]>([])
const trashedPagination  = ref<any>(null)
const trashedLoading     = ref(false)
const trashedCount       = ref(0)
const trashedSearchQuery = ref('')
const restoringId        = ref<number | null>(null)
const forceDeleteTarget  = ref<Category | null>(null)
const forceDeleting      = ref(false)

// ── Bulk selection (trashed) ──────────────────────────────────
const trashedSelectedIds = reactive(new Set<number>())
const bulkRestoring      = ref(false)
const bulkForceDeleting  = ref(false)

const bulkActionsRef = ref<InstanceType<typeof BulkActions> | null>(null)

const isTrashedAllSelected = computed(() => trashedCategories.value.length > 0 && trashedCategories.value.every(c => trashedSelectedIds.has(c.id)))
const isTrashedIndeterminate = computed(() => trashedSelectedIds.size > 0 && !isTrashedAllSelected.value)

// ── Tab switching ─────────────────────────────────────────────
function switchTab(trashed: boolean) {
  if (isTrashed.value === trashed) return
  isTrashed.value = trashed
  if (trashed) {
    fetchTrashedCategories()
  }
}

// ── Active: Select toggles ────────────────────────────────────
function toggleSelectAll() {
  if (isAllSelected.value) {
    visibleCategories.value.forEach(c => selectedIds.delete(c.id))
  } else {
    visibleCategories.value.forEach(c => selectedIds.add(c.id))
  }
}

function toggleSelect(id: number) {
  if (selectedIds.has(id)) selectedIds.delete(id)
  else selectedIds.add(id)
}

// ── Trashed: Select toggles ──────────────────────────────────
function toggleTrashedSelectAll() {
  if (isTrashedAllSelected.value) {
    trashedCategories.value.forEach(c => trashedSelectedIds.delete(c.id))
  } else {
    trashedCategories.value.forEach(c => trashedSelectedIds.add(c.id))
  }
}

function toggleTrashedSelect(id: number) {
  if (trashedSelectedIds.has(id)) trashedSelectedIds.delete(id)
  else trashedSelectedIds.add(id)
}

let trashedDebounceTimer: ReturnType<typeof setTimeout> | null = null
function debouncedFetchTrashed() {
  if (trashedDebounceTimer) clearTimeout(trashedDebounceTimer)
  trashedDebounceTimer = setTimeout(() => fetchTrashedCategories(), 400)
}

// ── Computed: danh mục hiển thị (lọc theo expand state + search) ──
const visibleCategories = computed(() => {
  // Search mode: hiện tất cả danh mục match + ancestor, bỏ qua expand state
  if (isSearching.value) {
    return allCategories.value.filter(c => matchedIds.value.has(c.id))
  }

  // Normal mode: theo expand/collapse
  const result: Category[] = []
  let skipBelow = -1

  for (const cat of allCategories.value) {
    if (skipBelow >= 0 && cat.depth > skipBelow) {
      continue
    }
    skipBelow = -1

    result.push(cat)

    if (hasChildren(cat.id) && !expandedIds.value.has(cat.id)) {
      skipBelow = cat.depth
    }
  }

  return result
})

// ── Helpers ──
// Kiểm tra danh mục có con không
function hasChildren(parentId: number): boolean {
  const idx = allCategories.value.findIndex(c => c.id === parentId)
  if (idx < 0) return false
  const next = allCategories.value[idx + 1]
  if (!next) return false
  return next.depth > allCategories.value[idx].depth
}

// Đếm số con trực tiếp
function getChildCount(parentId: number): number {
  const idx = allCategories.value.findIndex(c => c.id === parentId)
  if (idx < 0) return 0
  const parentDepth = allCategories.value[idx].depth
  let count = 0
  for (let i = idx + 1; i < allCategories.value.length; i++) {
    if (allCategories.value[i].depth <= parentDepth) break
    if (allCategories.value[i].depth === parentDepth + 1) count++
  }
  return count
}

// Kiểm tra phần tử có phải con cuối cùng trong visible list không (cho tree connector ├─ / └─)
function isLastChild(cat: Category, visibleIdx: number): boolean {
  const next = visibleCategories.value[visibleIdx + 1]
  if (!next) return true
  return next.depth <= cat.depth
}

// Highlight text match trong tên danh mục
function highlightName(name: string): string {
  const q = searchQuery.value.trim()
  if (!q) return name
  const regex = new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
  return name.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-500/30 rounded px-0.5">$1</mark>')
}

function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

// Toggle expand 1 danh mục
function toggleExpand(id: number) {
  const s = new Set(expandedIds.value)
  if (s.has(id)) {
    // Thu gọn: xóa id này và tất cả con cháu
    s.delete(id)
    collapseDescendants(id, s)
  } else {
    s.add(id)
  }
  expandedIds.value = s
  allExpanded.value = checkAllExpanded()
}

// Xóa tất cả con cháu khỏi expanded set khi thu gọn cha
function collapseDescendants(parentId: number, s: Set<number>) {
  const idx = allCategories.value.findIndex(c => c.id === parentId)
  if (idx < 0) return
  const parentDepth = allCategories.value[idx].depth
  for (let i = idx + 1; i < allCategories.value.length; i++) {
    if (allCategories.value[i].depth <= parentDepth) break
    s.delete(allCategories.value[i].id)
  }
}

// Toggle mở/đóng tất cả
function toggleAll() {
  if (allExpanded.value) {
    expandedIds.value = new Set()
    allExpanded.value = false
  } else {
    expandAll()
  }
}

function expandAll() {
  const s = new Set<number>()
  for (const cat of allCategories.value) {
    if (hasChildren(cat.id)) s.add(cat.id)
  }
  expandedIds.value = s
  allExpanded.value = true
}

function checkAllExpanded(): boolean {
  for (const cat of allCategories.value) {
    if (hasChildren(cat.id) && !expandedIds.value.has(cat.id)) return false
  }
  return true
}

// ── API ──
async function fetchCategories() {
  loading.value = true
  try {
    const res = await categoriesApi.flatTree()
    allCategories.value = res.data.data
    // Mặc định thu gọn tất cả, chỉ hiện danh mục cha
    expandedIds.value = new Set()
    allExpanded.value = false
  } catch {
    toast.error('Không thể tải danh mục')
  } finally {
    loading.value = false
  }
}

async function fetchFlatTree() {
  try {
    const res = await categoriesApi.flatTree()
    flatTree.value = res.data.data
  } catch {}
}

// ── Trashed: Fetch ────────────────────────────────────────────
async function fetchTrashedCategories(page = 1) {
  trashedLoading.value = true
  try {
    const params: Record<string, any> = { page, per_page: 20 }
    if (trashedSearchQuery.value) params.search = trashedSearchQuery.value

    const res = await categoriesApi.trashed(params)
    trashedCategories.value = res.data.data
    trashedPagination.value = res.data.pagination
    trashedCount.value = res.data.pagination?.total || res.data.data?.length || 0
  } catch {
    toast.error('Không thể tải thùng rác')
  } finally {
    trashedLoading.value = false
  }
}

async function fetchTrashedCount() {
  try {
    const res = await categoriesApi.trashed({ per_page: 1 })
    trashedCount.value = res.data.pagination?.total || res.data.data?.length || 0
  } catch {
    // im lặng
  }
}

onMounted(() => {
  fetchCategories()
  fetchFlatTree()
  fetchTrashedCount()
})

// ── Form ──
function autoSlug() {
  if (editingId.value) return
  form.value.slug = form.value.name
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[đĐ]/g, 'd')
    .toLowerCase()
    // Chuyển ký tự đặc biệt phổ biến thành từ đọc được
    .replace(/\+\+/g, '-plus-plus')
    .replace(/\+/g, '-plus')
    .replace(/#/g, '-sharp')
    .replace(/&/g, '-and')
    .replace(/\./g, '-')
    // Xóa ký tự đặc biệt còn lại
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')       // gộp nhiều dấu - liên tiếp
    .replace(/^-|-$/g, '')     // xóa - ở đầu/cuối
}

function openCreate() {
  editingId.value = null
  form.value = defaultForm()
  formErrors.value = {}
  submitError.value = ''
  showModal.value = true
}

function openEdit(cat: Category) {
  editingId.value = cat.id
  form.value = {
    name: cat.name,
    slug: cat.slug,
    description: cat.description || '',
    status: cat.status,
    parent_id: cat.parent_id ?? null,
  }
  formErrors.value = {}
  submitError.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

async function submitForm() {
  formErrors.value = {}
  submitError.value = ''
  submitting.value = true

  const payload = {
    name: form.value.name,
    slug: form.value.slug,
    description: form.value.description || null,
    status: form.value.status,
    parent_id: form.value.parent_id,
  }

  try {
    if (editingId.value) {
      await categoriesApi.update(editingId.value, payload)
      toast.success('Cập nhật danh mục thành công')
    } else {
      await categoriesApi.store(payload)
      toast.success('Tạo danh mục thành công')
    }
    closeModal()
    fetchCategories()
    fetchFlatTree()
  } catch (err: any) {
    const data = err.response?.data
    if (err.response?.status === 422 && data?.errors) {
      for (const [key, msgs] of Object.entries(data.errors as Record<string, string[]>)) {
        formErrors.value[key] = msgs[0]
      }
    } else {
      submitError.value = data?.message || 'Có lỗi xảy ra, vui lòng thử lại'
    }
  } finally {
    submitting.value = false
  }
}

function confirmDelete(cat: Category) {
  deleteTarget.value = cat
}

async function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await categoriesApi.destroy(deleteTarget.value.id)
    toast.success('Xóa danh mục thành công')
    deleteTarget.value = null
    fetchCategories()
    fetchFlatTree()
    fetchTrashedCount()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Xóa thất bại')
  } finally {
    deleting.value = false
  }
}

// ── Trashed: Restore ──────────────────────────────────────────
async function doRestoreCategory(cat: Category) {
  restoringId.value = cat.id
  try {
    await categoriesApi.restore(cat.id)
    toast.success(`Đã khôi phục "${cat.name}"`)
    fetchTrashedCategories()
    fetchTrashedCount()
    fetchCategories()
    fetchFlatTree()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Khôi phục thất bại')
  } finally {
    restoringId.value = null
  }
}

// ── Trashed: Force delete ─────────────────────────────────────
function confirmForceDeleteCategory(cat: Category) {
  forceDeleteTarget.value = cat
}

async function doForceDeleteCategory() {
  if (!forceDeleteTarget.value) return
  forceDeleting.value = true
  try {
    await categoriesApi.forceDelete(forceDeleteTarget.value.id)
    toast.success('Đã xóa vĩnh viễn danh mục')
    forceDeleteTarget.value = null
    fetchTrashedCategories()
    fetchTrashedCount()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Xóa vĩnh viễn thất bại')
  } finally {
    forceDeleting.value = false
  }
}

// ── Active: Bulk delete ───────────────────────────────────────
async function doBulkDelete() {
  bulkDeleting.value = true
  try {
    const ids = [...selectedIds]
    await Promise.all(ids.map(id => categoriesApi.destroy(id)))
    toast.success(`Đã xóa ${ids.length} danh mục`)
    selectedIds.clear()
    bulkActionsRef.value?.closeModal()
    fetchCategories()
    fetchFlatTree()
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
    await Promise.all(ids.map(id => categoriesApi.update(id, { status })))
    toast.success(`Đã cập nhật ${ids.length} danh mục`)
    selectedIds.clear()
    bulkActionsRef.value?.closeModal()
    fetchCategories()
    fetchFlatTree()
  } catch {
    toast.error('Cập nhật trạng thái thất bại')
  } finally {
    bulkUpdating.value = false
  }
}

// ── Trashed: Bulk restore ─────────────────────────────────────
async function doBulkRestoreCategories() {
  bulkRestoring.value = true
  try {
    const ids = [...trashedSelectedIds]
    await Promise.all(ids.map(id => categoriesApi.restore(id)))
    toast.success(`Đã khôi phục ${ids.length} danh mục`)
    trashedSelectedIds.clear()
    bulkActionsRef.value?.closeModal()
    fetchTrashedCategories()
    fetchTrashedCount()
    fetchCategories()
    fetchFlatTree()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Khôi phục nhiều thất bại')
  } finally {
    bulkRestoring.value = false
  }
}

// ── Trashed: Bulk force delete ────────────────────────────────
async function doBulkForceDeleteCategories() {
  bulkForceDeleting.value = true
  try {
    const ids = [...trashedSelectedIds]
    await Promise.all(ids.map(id => categoriesApi.forceDelete(id)))
    toast.success(`Đã xóa vĩnh viễn ${ids.length} danh mục`)
    trashedSelectedIds.clear()
    bulkActionsRef.value?.closeModal()
    fetchTrashedCategories()
    fetchTrashedCount()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Xóa vĩnh viễn nhiều thất bại')
  } finally {
    bulkForceDeleting.value = false
  }
}
</script>

<style scoped>
.label-form {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1;
}
.input-field {
  @apply w-full h-10 px-3 rounded-lg border border-gray-300 bg-transparent text-sm text-gray-800
         dark:border-gray-700 dark:text-white/90 dark:bg-gray-900
         focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400;
}
textarea.input-field {
  @apply h-auto py-2;
}
.input-error {
  @apply border-red-400 focus:ring-red-400/20;
}
.error-msg {
  @apply text-xs text-red-500 mt-1;
}
</style>
