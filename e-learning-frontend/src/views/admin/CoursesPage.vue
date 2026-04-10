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

    <!-- Filters (Tabs + Search + Status + Level) -->
    <CourseFilters
      :is-trashed="isTrashed"
      :trashed-count="trashedCount"
      :search="filters.search"
      :status="filters.status"
      :level="filters.level"
      :trashed-search="trashedFilters.search"
      @switch-tab="switchTab"
      @update:search="filters.search = $event"
      @update:status="filters.status = $event"
      @update:level="filters.level = $event"
      @update:trashedSearch="trashedFilters.search = $event"
      @search-input="debouncedFetch()"
      @filter-change="activeSetPage(1)"
      @trashed-search-input="debouncedFetchTrashed()"
    />

    <!-- Table (active + trashed) -->
    <CourseTable
      :is-trashed="isTrashed"
      :courses="courses"
      :loading="loading"
      :selected-ids="selectedIds"
      :is-all-selected="isAllSelected"
      :is-indeterminate="isIndeterminate"
      :toggling-id="togglingId"
      :pagination="activePagination"
      :trashed-courses="trashedCourses"
      :trashed-loading="trashedLoading"
      :trashed-selected-ids="trashedSelectedIds"
      :is-trashed-all-selected="isTrashedAllSelected"
      :is-trashed-indeterminate="isTrashedIndeterminate"
      :restoring-id="restoringId"
      :trashed-pagination="trashedPagination"
      @toggle-select-all="toggleSelectAll"
      @toggle-select="toggleSelect"
      @toggle-status="toggleStatus"
      @delete="softDelete.confirm"
      @page-change="loadActivePage"
      @toggle-trashed-select-all="toggleTrashedSelectAll"
      @toggle-trashed-select="toggleTrashedSelect"
      @restore="doRestore"
      @force-delete="forceDelete.confirm"
      @trashed-page-change="loadTrashedPage"
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
        Bạn có chắc muốn xóa khóa học
        <strong class="text-gray-800 dark:text-white/90">{{ softDelete.target.value?.name }}</strong>?
        <span class="block mt-1 text-xs text-gray-400">Khóa học sẽ được chuyển vào thùng rác.</span>
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
        Bạn có chắc muốn xóa vĩnh viễn khóa học
        <strong class="text-gray-800 dark:text-white/90">{{ forceDelete.target.value?.name }}</strong>?
      </p>
    </ConfirmModal>

    <!-- Bulk Actions -->
    <BulkActions
      :ref="(el) => { bulkActionsRef.value = el as any }"
      :count="isTrashed ? trashedSelectedIds.size : selectedIds.size"
      itemName="khóa học"
      :is-trashed="isTrashed"
      :loading="bulkUpdating || bulkDeleting || bulkRestoring || bulkForceDeleting"
      publishLabel="Đã đăng"
      draftLabel="Nháp"
      @publish="bulkToggleStatus(1)"
      @draft="bulkToggleStatus(0)"
      @delete="doBulkDelete"
      @restore="doBulkRestore"
      @force-delete="doBulkForceDelete"
      @clear="isTrashed ? clearTrashedSelection() : clearSelection()"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { PlusIcon } from '@/components/icons'
import { useCourses } from '@/composables/useCourses'
import CourseFilters from '@/components/admin/courses/CourseFilters.vue'
import CourseTable from '@/components/admin/courses/CourseTable.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import BulkActions from '@/components/table/BulkActions.vue'

const {
  isTrashed, switchTab,
  courses, loading, togglingId, filters,
  loadActivePage, activePagination, activeSetPage, debouncedFetch,
  toggleStatus,
  selectedIds, isAllSelected, isIndeterminate, toggleSelectAll, toggleSelect, clearSelection,
  bulkDeleting, bulkUpdating, doBulkDelete, bulkToggleStatus,
  trashedCourses, trashedLoading, trashedCount, restoringId, trashedFilters,
  loadTrashedPage, trashedPagination, debouncedFetchTrashed, fetchTrashedCount, doRestore,
  trashedSelectedIds, isTrashedAllSelected, isTrashedIndeterminate,
  toggleTrashedSelectAll, toggleTrashedSelect, clearTrashedSelection,
  bulkForceDeleting, bulkRestoring, doBulkRestore, doBulkForceDelete,
  bulkActionsRef,
  softDelete, forceDelete,
} = useCourses()

onMounted(() => {
  loadActivePage()
  fetchTrashedCount()
})
</script>
