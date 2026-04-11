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
            <svg
              class="w-4 h-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                v-if="allExpanded"
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M19 9l-7 7-7-7"
              />
              <path v-else stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            {{ allExpanded ? 'Thu gọn tất cả' : 'Mở rộng tất cả' }}
          </button>
          <button
            @click="openCreate"
            class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors"
          >
            <PlusIcon class="w-4 h-4" />
            Thêm danh mục
          </button>
        </template>
      </div>
    </div>

    <!-- Filters (Tabs + Search) -->
    <CategoryFilters
      :is-trashed="isTrashed"
      :trashed-count="trashedCount"
      :search-query="searchQuery"
      :trashed-search-query="trashedSearchQuery"
      @switch-tab="switchTab"
      @update:searchQuery="searchQuery = $event"
      @clear-search="searchQuery = ''"
      @trashedSearchInput="onTrashedSearchInput"
      @clear-trashed-search="onClearTrashedSearch"
    />

    <!-- Active Table -->
    <CategoryTable
      v-if="!isTrashed"
      :all-categories="allCategories"
      :visible-categories="visibleCategories"
      :loading="loading"
      :is-searching="isSearching"
      :search-query="searchQuery"
      :match-count="matchCount"
      :selected-ids="selectedIds"
      :expanded-ids="expandedIds"
      :is-all-selected="isAllSelected"
      :is-indeterminate="isIndeterminate"
      :has-children="hasChildren"
      :get-child-count="getChildCount"
      :is-last-child="(cat, idx) => isLastChild(cat, idx, visibleCategories)"
      :pagination="pagination"
      @toggle-select-all="toggleSelectAll"
      @toggle-select="toggleSelect"
      @toggle-expand="toggleExpand"
      @edit="openEdit"
      @delete="softDelete.confirm"
      @page-change="fetchCategories"
    />

    <!-- Trashed Table -->
    <CategoryTrashedTable
      v-if="isTrashed"
      :categories="trashedCategories"
      :loading="trashedLoading"
      :selected-ids="trashedSelectedIds"
      :is-all-selected="isTrashedAllSelected"
      :is-indeterminate="isTrashedIndeterminate"
      :restoring-id="restoringId"
      :pagination="trashedPagination"
      @toggle-select-all="toggleTrashedSelectAll"
      @toggle-select="toggleTrashedSelect"
      @restore="doRestoreCategory"
      @force-delete="forceDelete.confirm"
      @page-change="fetchTrashedCategories"
    />

    <!-- Category Form Modal -->
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
        <strong class="text-gray-800 dark:text-white/90">{{ softDelete.target.value?.name }}</strong
        >? Các danh mục con cũng sẽ bị xóa.
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
        <strong class="text-gray-800 dark:text-white/90">{{
          forceDelete.target.value?.name
        }}</strong
        >?
      </p>
    </ConfirmModal>

    <!-- Bulk Actions -->
    <BulkActions
      :ref="setBulkActionsRef"
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
import { onMounted } from 'vue'
import { PlusIcon } from '@/components/icons'
import { useCategories } from '@/composables/useCategories'
import { useCategoryTree } from '@/composables/useCategoryTree'
import CategoryFilters from '@/components/admin/categories/CategoryFilters.vue'
import CategoryTable from '@/components/admin/categories/CategoryTable.vue'
import CategoryTrashedTable from '@/components/admin/categories/CategoryTrashedTable.vue'
import CategoryForm from '@/components/forms/CategoryForm.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import BulkActions from '@/components/table/BulkActions.vue'

const {
  isTrashed,
  switchTab,
  allCategories,
  pagination,
  flatTree,
  loading,
  activeItems,
  fetchCategories,
  fetchFlatTree,
  selectedIds,
  isAllSelected,
  isIndeterminate,
  toggleSelectAll,
  toggleSelect,
  clearSelection,
  bulkDeleting,
  bulkUpdating,
  doBulkDelete,
  bulkToggleStatus,
  trashedCategories,
  trashedPagination,
  trashedLoading,
  trashedCount,
  trashedSearchQuery,
  restoringId,
  fetchTrashedCategories,
  fetchTrashedCount,
  debouncedFetchTrashed,
  doRestoreCategory,
  trashedSelectedIds,
  isTrashedAllSelected,
  isTrashedIndeterminate,
  toggleTrashedSelectAll,
  toggleTrashedSelect,
  clearTrashedSelection,
  bulkRestoring,
  bulkForceDeleting,
  doBulkRestoreCategories,
  doBulkForceDeleteCategories,
  bulkActionsRef,
  showModal,
  editingId,
  submitting,
  formErrors,
  submitError,
  form,
  autoSlug,
  openCreate,
  openEdit,
  closeModal,
  submitForm,
  softDelete,
  forceDelete,
} = useCategories()

const onTrashedSearchInput = (val: string) => {
  trashedSearchQuery.value = val
  debouncedFetchTrashed()
}

const onClearTrashedSearch = () => {
  trashedSearchQuery.value = ''
  fetchTrashedCategories()
}

const setBulkActionsRef = (el: { closeModal: () => void } | null) => {
  bulkActionsRef.value = el
}

const {
  expandedIds,
  allExpanded,
  searchQuery,
  isSearching,
  matchCount,
  visibleCategories,
  hasChildren,
  getChildCount,
  isLastChild,
  toggleExpand,
  toggleAll,
} = useCategoryTree(allCategories)

// Sync activeItems → useBulkSelect vẫn nhìn visibleCategories
import { watch } from 'vue'
watch(
  visibleCategories,
  (v) => {
    activeItems.value = v
  },
  { immediate: true },
)

onMounted(() => {
  fetchCategories()
  fetchFlatTree()
  fetchTrashedCount()
})
</script>
