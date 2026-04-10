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
            <CategoryTreeNode
              v-for="(cat, idx) in visibleCategories"
              :key="cat.id"
              :cat="cat"
              :is-first="idx === 0"
              :is-selected="selectedIds.has(cat.id)"
              :is-expanded="expandedIds.has(cat.id)"
              :is-last-child="isLastChild(cat, idx)"
              :has-children="hasChildren(cat.id)"
              :child-count="getChildCount(cat.id)"
              :search-query="searchQuery"
              @toggle-select="toggleSelect"
              @toggle-expand="toggleExpand"
              @edit="openEdit"
              @delete="softDelete.confirm"
            />
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
                    @click="forceDelete.confirm(cat)"
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

    <!-- Category Form Modal (extracted component) -->
    <CategoryForm
      :show="showModal"
      :editing-id="editingId"
      :form="form"
      :errors="formErrors"
      :submit-error="submitError"
      :submitting="submitting"
      :flat-tree="flatTree"
      @close="closeModal"
      @submit="submitForm"
      @update:form="form = $event"
      @auto-slug="autoSlug"
    />

    <!-- Confirm Soft Delete -->
    <ConfirmModal
      :show="softDelete.isOpen.value"
      title="Xác nhận xóa"
      :loading="softDelete.loading.value"
      confirm-text="Xóa"
      loading-text="Đang xóa..."
      @cancel="softDelete.cancel()"
      @confirm="softDelete.execute()"
    >
      <p>
        Bạn có chắc muốn xóa danh mục
        <strong class="text-gray-800 dark:text-white/90">{{ softDelete.target.value?.name }}</strong>?
        Các danh mục con cũng sẽ bị xóa.
        <span class="block mt-1 text-xs text-gray-400">Danh mục sẽ được chuyển vào thùng rác.</span>
      </p>
    </ConfirmModal>

    <!-- Confirm Force Delete -->
    <ConfirmModal
      :show="forceDelete.isOpen.value"
      title="Xóa vĩnh viễn"
      subtitle="Hành động này không thể hoàn tác!"
      icon="warning"
      :loading="forceDelete.loading.value"
      confirm-text="Xóa vĩnh viễn"
      loading-text="Đang xóa..."
      @cancel="forceDelete.cancel()"
      @confirm="forceDelete.execute()"
    >
      <p>
        Bạn có chắc muốn xóa vĩnh viễn danh mục
        <strong class="text-gray-800 dark:text-white/90">{{ forceDelete.target.value?.name }}</strong>?
      </p>
    </ConfirmModal>

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
      @clear="isTrashed ? clearTrashedSelection() : clearSelection()"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import { useToast } from 'vue-toastification'
import { PlusIcon, TrashIcon } from '@/icons'
import BulkActions from '@/components/admin/BulkActions.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import CategoryTreeNode from '@/components/admin/CategoryTreeNode.vue'
import CategoryForm from '@/components/admin/CategoryForm.vue'
import { categoryService } from '@/services/category.service'
import { useDebounceSearch } from '@/composables/useDebounceSearch'
import { useDeleteConfirm } from '@/composables/useDeleteConfirm'
import { useBulkSelect } from '@/composables/useBulkSelect'
import { useFormErrors } from '@/composables/useFormErrors'

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
const { errors: formErrors, submitError, clearErrors, handleApiError } = useFormErrors()

const defaultForm = () => ({
  name: '',
  slug: '',
  description: '',
  status: 1,
  parent_id: null as number | null,
})
const form = ref(defaultForm())

// ── Bulk selection (active) ───────────────────────────────────
const {
  selectedIds,
  isAllSelected,
  isIndeterminate,
  toggleSelectAll,
  toggleSelect,
  clear: clearSelection,
} = useBulkSelect({ items: () => visibleCategories.value })
const bulkDeleting = ref(false)
const bulkUpdating = ref(false)

// ── Trashed state ─────────────────────────────────────────────
const trashedCategories  = ref<Category[]>([])
const trashedPagination  = ref<any>(null)
const trashedLoading     = ref(false)
const trashedCount       = ref(0)
const trashedSearchQuery = ref('')
const restoringId        = ref<number | null>(null)

// ── Bulk selection (trashed) ──────────────────────────────────
const {
  selectedIds: trashedSelectedIds,
  isAllSelected: isTrashedAllSelected,
  isIndeterminate: isTrashedIndeterminate,
  toggleSelectAll: toggleTrashedSelectAll,
  toggleSelect: toggleTrashedSelect,
  clear: clearTrashedSelection,
} = useBulkSelect({ items: () => trashedCategories.value })
const bulkRestoring      = ref(false)
const bulkForceDeleting  = ref(false)

const bulkActionsRef = ref<InstanceType<typeof BulkActions> | null>(null)

// ── Debounce search (via composable) ──────────────────────────
const { debounce: debouncedFetchTrashed } = useDebounceSearch(() => fetchTrashedCategories())

// ── Delete confirmations (via composable) ─────────────────────
const softDelete = useDeleteConfirm({
  async onConfirm(cat: Category) {
    await categoryService.destroy(cat.id)
    toast.success('Xóa danh mục thành công')
    fetchCategories()
    fetchFlatTree()
    fetchTrashedCount()
  },
})

const forceDelete = useDeleteConfirm({
  async onConfirm(cat: Category) {
    await categoryService.forceDelete(cat.id)
    toast.success('Đã xóa vĩnh viễn danh mục')
    fetchTrashedCategories()
    fetchTrashedCount()
  },
})

// ── Tab switching ─────────────────────────────────────────────
function switchTab(trashed: boolean) {
  if (isTrashed.value === trashed) return
  isTrashed.value = trashed
  if (trashed) {
    fetchTrashedCategories()
  }
}

// Select toggles are now provided by useBulkSelect composable

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
    const res = await categoryService.flatTree()
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
    const res = await categoryService.flatTree()
    flatTree.value = res.data.data
  } catch {}
}

// ── Trashed: Fetch ────────────────────────────────────────────
async function fetchTrashedCategories(page = 1) {
  trashedLoading.value = true
  try {
    const params: Record<string, any> = { page, per_page: 20 }
    if (trashedSearchQuery.value) params.search = trashedSearchQuery.value

    const res = await categoryService.trashed(params)
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
    const res = await categoryService.trashed({ per_page: 1 })
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
  clearErrors()
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
  clearErrors()
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

async function submitForm() {
  clearErrors()
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
      await categoryService.update(editingId.value, payload)
      toast.success('Cập nhật danh mục thành công')
    } else {
      await categoryService.store(payload)
      toast.success('Tạo danh mục thành công')
    }
    closeModal()
    fetchCategories()
    fetchFlatTree()
  } catch (err: any) {
    handleApiError(err)
  } finally {
    submitting.value = false
  }
}

// ── Trashed: Restore ──────────────────────────────────────────
async function doRestoreCategory(cat: Category) {
  restoringId.value = cat.id
  try {
    await categoryService.restore(cat.id)
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

// ── Active: Bulk delete ───────────────────────────────────────
async function doBulkDelete() {
  bulkDeleting.value = true
  try {
    const ids = [...selectedIds]
    await Promise.all(ids.map(id => categoryService.destroy(id)))
    toast.success(`Đã xóa ${ids.length} danh mục`)
    clearSelection()
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
    await Promise.all(ids.map(id => categoryService.update(id, { status })))
    toast.success(`Đã cập nhật ${ids.length} danh mục`)
    clearSelection()
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
    await Promise.all(ids.map(id => categoryService.restore(id)))
    toast.success(`Đã khôi phục ${ids.length} danh mục`)
    clearTrashedSelection()
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
    await Promise.all(ids.map(id => categoryService.forceDelete(id)))
    toast.success(`Đã xóa vĩnh viễn ${ids.length} danh mục`)
    clearTrashedSelection()
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
