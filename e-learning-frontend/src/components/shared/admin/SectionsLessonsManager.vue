<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
      <div class="flex items-center gap-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ sectionsList.length }} chương · {{ totalLessons }} bài giảng
        </p>
        <!-- Tabs -->
        <div class="flex bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
          <button
            @click="currentTab = 'active'"
            :class="currentTab === 'active'
              ? 'bg-white dark:bg-gray-700 shadow-sm text-blue-600 dark:text-blue-400'
              : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
            class="px-3 py-1 text-sm font-medium rounded-md transition-all"
          >
            Đang hoạt động
          </button>
          <button
            @click="currentTab = 'trashed'"
            :class="currentTab === 'trashed'
              ? 'bg-white dark:bg-gray-700 shadow-sm text-red-600 dark:text-red-400'
              : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
            class="px-3 py-1 text-sm font-medium rounded-md transition-all"
          >
            Thùng rác
          </button>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="openCreateLesson(null)"
          class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
        >
          <PlusIcon class="w-4 h-4" />
          Thêm bài giảng
        </button>
        <button
          @click="openCreateSection"
          class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors"
        >
          <PlusIcon class="w-4 h-4" />
          Thêm chương
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-10">
      <svg class="animate-spin w-6 h-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
    </div>

    <template v-else>
      <!-- Active tab -->
      <div v-if="currentTab === 'active'" class="space-y-3">
        <!-- Empty state -->
        <div
          v-if="!sectionsList.length && !orphanLessons.length"
          class="text-center py-10 text-gray-400 text-sm border border-dashed border-gray-200 dark:border-gray-700 rounded-2xl"
        >
          Chưa có nội dung. Hãy thêm chương hoặc bài giảng.
        </div>

        <!-- Section cards -->
        <SectionItem
          v-for="(section, sIdx) in sectionsList"
          :key="section.id"
          :section="section"
          :index="sIdx"
          :is-last="sIdx === sectionsList.length - 1"
          :is-expanded="expandedSections.has(section.id)"
          :is-all-selected="isSectionAllSelected(section)"
          :is-toggling="togglingSection === section.id"
          @toggle-expand="toggleExpand"
          @select-all="handleSectionSelectAll"
          @reorder="reorderSection"
          @add-lesson="openCreateLesson"
          @toggle-status="toggleSectionStatus"
          @edit="openEditSection"
          @delete="confirmDeleteSection"
        >
          <LessonList
            :lessons="section.lessons || []"
            :selected-lessons="selectedLessons"
            :toggling-lesson="togglingLesson"
            @toggle-select="toggleLessonSelect"
            @toggle-status="toggleLessonStatus"
            @preview="handlePreviewLesson"
            @edit="openEditLesson"
            @delete="deleteLesson.confirm"
            @dragstart="draggedLessonIdx = $event"
            @drop="reorderLessonDrag(section, $event)"
          />
        </SectionItem>

        <!-- Orphan lessons -->
        <div
          v-if="orphanLessons.length"
          class="rounded-2xl border border-dashed border-orange-300 bg-orange-50/50 dark:border-orange-500/30 dark:bg-orange-500/5 overflow-hidden"
        >
          <div
            class="flex items-center gap-3 px-5 py-3.5 cursor-pointer select-none"
            @click="toggleExpand('orphan')"
          >
            <svg
              class="w-4 h-4 text-orange-400 transition-transform duration-200 flex-shrink-0"
              :class="{ 'rotate-90': expandedSections.has('orphan') }"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <div @click.stop class="flex items-center justify-center mr-1">
              <input
                type="checkbox"
                :checked="isOrphanAllSelected"
                @change="handleOrphanSelectAll"
                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                title="Chọn tất cả bài chưa phân chương"
              />
            </div>
            <div class="flex-1">
              <h4 class="font-medium text-orange-600 dark:text-orange-400">Chưa phân chương</h4>
              <p class="text-xs text-orange-400 dark:text-orange-500 mt-0.5">
                {{ orphanLessons.length }} bài giảng chưa gán vào chương nào
              </p>
            </div>
          </div>

          <div v-if="expandedSections.has('orphan')" class="border-t border-orange-200 dark:border-orange-500/20">
            <LessonList
              :lessons="orphanLessons"
              :selected-lessons="selectedLessons"
              :toggling-lesson="togglingLesson"
              :is-orphan="true"
              @toggle-select="toggleLessonSelect"
              @toggle-status="toggleLessonStatus"
              @preview="handlePreviewLesson"
              @edit="openEditLesson"
              @delete="deleteLesson.confirm"
            />
          </div>
        </div>
      </div>

      <!-- Trashed tab -->
      <div v-if="currentTab === 'trashed'" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden mt-4">
        <div v-if="loadingTrashed" class="py-10 text-center text-gray-500">Đang tải thùng rác...</div>
        <div v-else-if="!trashedLessons.length" class="py-10 text-center text-gray-500 border border-dashed border-gray-200 rounded-2xl">Thùng rác trống</div>
        <table v-else class="w-full text-sm text-left">
          <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
            <tr>
              <th class="pl-4 pr-1 py-3 w-8">
                <input type="checkbox" :checked="isAllSelected" @change="handleSelectAllChange" class="w-4 h-4 rounded border-gray-300 focus:ring-blue-500 cursor-pointer" />
              </th>
              <th class="px-4 py-3 font-medium">Tiêu đề bài giảng</th>
              <th class="px-4 py-3 font-medium">Loại</th>
              <th class="px-4 py-3 font-medium text-right">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr v-for="lesson in trashedLessons" :key="lesson.id" class="hover:bg-gray-50 dark:hover:bg-white/5">
              <td class="pl-4 pr-1 py-3 w-8">
                <input type="checkbox" v-model="selectedLessons" :value="lesson.id" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
              </td>
              <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">{{ lesson.title }}</td>
              <td class="px-4 py-3">
                <span :class="typeClass(lesson.type)" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium">
                  {{ typeLabel(lesson.type) }}
                </span>
              </td>
              <td class="px-4 py-3 text-right space-x-2">
                <button @click="handleRestoreLessonTr(lesson)" class="px-2 py-1 text-xs text-green-600 hover:bg-green-50 rounded border border-green-200 transition">Khôi phục</button>
                <button @click="handleForceDeleteLessonTr(lesson)" class="px-2 py-1 text-xs text-red-600 hover:bg-red-50 rounded border border-red-200 transition">Xóa vĩnh viễn</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- ═══════ MODAL: Section Form ═══════ -->
    <SectionFormModal
      v-model:show="showSectionModal"
      :is-edit="!!editingSectionId"
      :submitting="sSubmitting"
      :errors="sErrors"
      :submit-error="sSubmitError"
      :form="sForm"
      @submit="(data) => { sForm = data; submitSection() }"
    />

    <!-- ═══════ MODAL: Lesson Form ═══════ -->
    <LessonFormModal
      v-model:show="showLessonModal"
      :is-edit="!!editingLessonId"
      :submitting="lSubmitting"
      :errors="lErrors"
      :submit-error="lSubmitError"
      :form="lForm"
      :sections-list="sectionsList"
      @submit="(data) => { lForm = data; submitLesson() }"
    />

    <!-- ═══════ MODAL: Confirm Delete Section ═══════ -->
    <ConfirmModal
      :show="deleteSection.isOpen.value"
      title="Xác nhận xóa chương"
      :loading="deleteSection.loading.value"
      confirm-text="Xóa"
      loading-text="Đang xóa..."
      @cancel="deleteSection.cancel()"
      @confirm="deleteSection.execute()"
    >
      <p>
        Bạn có chắc muốn xóa chương
        <strong class="text-gray-800 dark:text-white/90">{{ deleteSection.target.value?.title }}</strong>?
      </p>
      <p class="text-xs text-orange-500 mt-1">
        ⚠️ Các bài giảng trong chương sẽ chuyển thành "Chưa phân chương".
      </p>
    </ConfirmModal>

    <!-- ═══════ MODAL: Confirm Delete Lesson ═══════ -->
    <ConfirmModal
      :show="deleteLesson.isOpen.value"
      title="Xác nhận xóa bài giảng"
      :loading="deleteLesson.loading.value"
      confirm-text="Xóa"
      loading-text="Đang xóa..."
      @cancel="deleteLesson.cancel()"
      @confirm="deleteLesson.execute()"
    >
      <p>
        Bạn có chắc muốn xóa bài giảng
        <strong class="text-gray-800 dark:text-white/90">{{ deleteLesson.target.value?.title }}</strong>?
      </p>
    </ConfirmModal>

    <!-- ═══════ MODAL: Confirm Restore Lesson ═══════ -->
    <ConfirmModal
      :show="restoreLessonConfirm.isOpen.value"
      title="Khôi phục bài giảng"
      variant="info"
      :loading="restoreLessonConfirm.loading.value"
      confirm-text="Khôi phục"
      loading-text="Đang khôi phục..."
      @cancel="restoreLessonConfirm.cancel()"
      @confirm="restoreLessonConfirm.execute()"
    >
      <p>
        Khôi phục bài giảng
        <strong class="text-gray-800 dark:text-white/90">{{ restoreLessonConfirm.target.value?.title }}</strong>?
      </p>
    </ConfirmModal>

    <!-- ═══════ MODAL: Confirm Force Delete Lesson ═══════ -->
    <ConfirmModal
      :show="forceDeleteLessonConfirm.isOpen.value"
      title="Xóa vĩnh viễn"
      subtitle="Hành động này không thể hoàn tác!"
      icon="warning"
      :loading="forceDeleteLessonConfirm.loading.value"
      confirm-text="Xóa vĩnh viễn"
      loading-text="Đang xóa..."
      @cancel="forceDeleteLessonConfirm.cancel()"
      @confirm="forceDeleteLessonConfirm.execute()"
    >
      <p>
        Xóa vĩnh viễn bài giảng
        <strong class="text-gray-800 dark:text-white/90">{{ forceDeleteLessonConfirm.target.value?.title }}</strong>?
      </p>
    </ConfirmModal>

    <!-- ═══════ BULK ACTIONS ═══════ -->
    <BulkActions
      :ref="setBulkActionsRef"
      :count="selectedLessons.length"
      itemName="bài giảng"
      :is-trashed="currentTab === 'trashed'"
      :loading="bulkActionLoading"
      :sections="sectionsList"
      @publish="doBulkStatusLessons('activate')"
      @draft="doBulkStatusLessons('deactivate')"
      @delete="doBulkDeleteLessons"
      @restore="doBulkRestoreLessons"
      @force-delete="doBulkForceDeleteLessons"
      @assign-section="doBulkAssignSection"
      @clear="selectedLessons = []"
    />

    <LessonPreviewModal
      :ref="setPreviewModalRef"
      :lesson="previewLesson"
      :loading="previewLoading"
      @close="previewLesson = null"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { PlusIcon } from '@/components/icons'
import { useSectionsManager } from '@/composables/useSectionsManager'
import { useLessonsManager } from '@/composables/useLessonsManager'
import SectionItem from '@/components/admin/sections/SectionItem.vue'
import LessonList from '@/components/admin/lessons/LessonList.vue'
import BulkActions from '@/components/table/BulkActions.vue'
import LessonPreviewModal from '@/components/shared/admin/LessonPreviewModal.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import SectionFormModal from '@/components/forms/SectionFormModal.vue'
import LessonFormModal from '@/components/forms/LessonFormModal.vue'

const props = defineProps<{ courseId: number }>()

// ── Sections composable ────────────────────────────────────────
const {
  sectionsList,
  orphanLessons,
  loading,
  expandedSections,
  totalLessons,
  togglingSection,
  showSectionModal,
  editingSectionId,
  sSubmitting,
  sErrors,
  sSubmitError,
  sForm,
  deleteSection,
  fetchAll,
  toggleExpand,
  reorderSection,
  toggleSectionStatus,
  openCreateSection,
  openEditSection,
  submitSection,
  confirmDeleteSection,
  isSectionAllSelected,
} = useSectionsManager(props.courseId)

// ── Lessons composable ─────────────────────────────────────────
const {
  currentTab,
  trashedLessons,
  loadingTrashed,
  selectedLessons,
  isAllSelected,
  isOrphanAllSelected,
  toggleLessonSelect,
  handleSelectAllChange,
  handleSectionSelectAll,
  handleOrphanSelectAll,
  togglingLesson,
  toggleLessonStatus,
  draggedLessonIdx,
  reorderLessonDrag,
  showLessonModal,
  editingLessonId,
  lSubmitting,
  lErrors,
  lSubmitError,
  lForm,
  openCreateLesson,
  openEditLesson,
  submitLesson,
  deleteLesson,
  restoreLessonConfirm,
  forceDeleteLessonConfirm,
  handleRestoreLessonTr,
  handleForceDeleteLessonTr,
  previewLesson,
  previewLoading,
  previewModalRef,
  handlePreviewLesson,
  bulkActionLoading,
  bulkActionsRef,
  doBulkStatusLessons,
  doBulkDeleteLessons,
  doBulkRestoreLessons,
  doBulkForceDeleteLessons,
  doBulkAssignSection,
} = useLessonsManager(props.courseId, sectionsList, orphanLessons, totalLessons, fetchAll)

function setBulkActionsRef(el: unknown) { bulkActionsRef.value = el }
function setPreviewModalRef(el: unknown) { previewModalRef.value = el }

// ── Helpers (type labels for trashed table) ────────────────────
const typeLabelMap: Record<string, string> = { video: 'Video', document: 'Tài liệu' }
const typeClassMap: Record<string, string> = {
  video:    'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
  document: 'bg-orange-100 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400',
}
function typeLabel(type: string) { return typeLabelMap[type] || type }
function typeClass(type: string) { return typeClassMap[type] || 'bg-gray-100 text-gray-600' }

onMounted(fetchAll)
</script>
