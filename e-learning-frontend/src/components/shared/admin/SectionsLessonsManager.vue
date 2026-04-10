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
          <button @click="currentTab = 'active'" :class="currentTab === 'active' ? 'bg-white dark:bg-gray-700 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'" class="px-3 py-1 text-sm font-medium rounded-md transition-all">
            Đang hoạt động
          </button>
          <button @click="currentTab = 'trashed'" :class="currentTab === 'trashed' ? 'bg-white dark:bg-gray-700 shadow-sm text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'" class="px-3 py-1 text-sm font-medium rounded-md transition-all">
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
      <!-- Bulk Actions Toolbar (Moved to floating dock at bottom) -->

      <!-- Sections Accordion -->
      <div v-if="currentTab === 'active'" class="space-y-3">
      <!-- Empty state -->
      <div
        v-if="!sectionsList.length && !orphanLessons.length"
        class="text-center py-10 text-gray-400 text-sm border border-dashed border-gray-200 dark:border-gray-700 rounded-2xl"
      >
        Chưa có nội dung. Hãy thêm chương hoặc bài giảng.
      </div>

      <!-- Section cards -->
      <div
        v-for="(section, sIdx) in sectionsList"
        :key="section.id"
        class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/5 overflow-hidden"
      >
        <!-- Section header -->
        <div
          class="flex items-center gap-3 px-5 py-3.5 cursor-pointer select-none hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
          @click="toggleExpand(section.id)"
        >
          <!-- Expand icon -->
          <svg
            class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0"
            :class="{ 'rotate-90': expandedSections.has(section.id) }"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>

          <!-- Checkbox chọn tất cả bài trong section -->
          <div @click.stop class="flex items-center justify-center mr-1">
            <input type="checkbox" :checked="isSectionAllSelected(section)" @change="e => handleSectionSelectAll(section, e)" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" title="Chọn tất cả bài giảng trong chương này" />
          </div>

          <!-- Section info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span class="text-xs font-mono text-gray-400">{{ sIdx + 1 }}.</span>
              <h4 class="font-medium text-gray-800 dark:text-gray-200 truncate">{{ section.title }}</h4>
              <span
                :class="section.status === 1
                  ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                  : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400'"
                class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium"
              >
                {{ section.status === 1 ? 'Đã đăng' : 'Nháp' }}
              </span>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">{{ (section.lessons || []).length }} bài giảng</p>
          </div>

          <!-- Section actions -->
          <div class="flex items-center gap-1" @click.stop>
            <!-- Reorder -->
            <button
              v-if="sIdx > 0"
              @click="reorderSection(sIdx, sIdx - 1)"
              class="p-1 text-gray-400 hover:text-gray-600 rounded transition-colors"
              title="Di chuyển lên"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
            </button>
            <button
              v-if="sIdx < sectionsList.length - 1"
              @click="reorderSection(sIdx, sIdx + 1)"
              class="p-1 text-gray-400 hover:text-gray-600 rounded transition-colors"
              title="Di chuyển xuống"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <!-- Add lesson to this section -->
            <button
              @click="openCreateLesson(section.id)"
              class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10 transition-colors"
              title="Thêm bài giảng vào chương này"
            >
              <PlusIcon class="w-4 h-4" />
            </button>

            <!-- Toggle status -->
            <button
              @click="toggleSectionStatus(section)"
              :disabled="togglingSection === section.id"
              class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg dark:hover:bg-green-500/10 transition-colors disabled:opacity-50"
              title="Toggle trạng thái"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </button>

            <!-- Edit section -->
            <button
              @click="openEditSection(section)"
              class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10 transition-colors"
              title="Sửa chương"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </button>

            <!-- Delete section -->
            <button
              @click="confirmDeleteSection(section)"
              class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors"
              title="Xóa chương"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Lessons list (expandable) -->
        <div v-if="expandedSections.has(section.id)" class="border-t border-gray-100 dark:border-gray-700">
          <div v-if="!(section.lessons || []).length" class="text-center py-6 text-gray-400 text-xs">
            Chưa có bài giảng trong chương này
          </div>
          <table v-else class="w-full text-sm">
            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
              <LessonItem
                v-for="(lesson, lIdx) in section.lessons"
                :key="lesson.id"
                :lesson="lesson"
                :index="lIdx"
                :is-selected="selectedLessons.includes(lesson.id)"
                :is-toggling="togglingLesson === lesson.id"
                @toggle-select="toggleLessonSelect"
                @toggle-status="toggleLessonStatus"
                @preview="handlePreviewLesson"
                @edit="openEditLesson"
                @delete="deleteLesson.confirm"
                @dragstart="draggedLessonIdx = lIdx"
                @drop="reorderLessonDrag(section, lIdx)"
              />
            </tbody>
          </table>
        </div>
      </div>

      <!-- Orphan lessons (chưa gán section) -->
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

          <!-- Checkbox chọn tất cả bài orphan -->
          <div @click.stop class="flex items-center justify-center mr-1">
            <input type="checkbox" :checked="isOrphanAllSelected" @change="handleOrphanSelectAll" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" title="Chọn tất cả bài chưa phân chương" />
          </div>
          <div class="flex-1">
            <h4 class="font-medium text-orange-600 dark:text-orange-400">Chưa phân chương</h4>
            <p class="text-xs text-orange-400 dark:text-orange-500 mt-0.5">{{ orphanLessons.length }} bài giảng chưa gán vào chương nào</p>
          </div>
        </div>

        <div v-if="expandedSections.has('orphan')" class="border-t border-orange-200 dark:border-orange-500/20">
          <table class="w-full text-sm">
            <tbody class="divide-y divide-orange-100 dark:divide-orange-500/10">
              <LessonItem
                v-for="(lesson, lIdx) in orphanLessons"
                :key="lesson.id"
                :lesson="lesson"
                :index="lIdx"
                :is-selected="selectedLessons.includes(lesson.id)"
                :is-toggling="togglingLesson === lesson.id"
                :is-orphan="true"
                @toggle-select="toggleLessonSelect"
                @toggle-status="toggleLessonStatus"
                @preview="handlePreviewLesson"
                @edit="openEditLesson"
                @delete="deleteLesson.confirm"
              />
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
      <!-- Trashed Lessons List -->
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
                <span :class="typeClass(lesson.type)" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium">{{ typeLabel(lesson.type) }}</span>
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
      @submit="submitSection"
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
      @submit="submitLesson"
    />

    <!-- ═══════ MODAL: Confirm Delete Section (via composable) ═══════ -->
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

    <!-- ═══════ MODAL: Confirm Delete Lesson (via composable) ═══════ -->
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

    <!-- ═══════ MODAL: Confirm Restore Lesson (trashed) ═══════ -->
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

    <!-- ═══════ MODAL: Confirm Force Delete Lesson (trashed) ═══════ -->
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

    <!-- ═══════ REUSABLE BULK ACTIONS COMPONENT ═══════ -->
    <BulkActions
      ref="bulkActionsRef"
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
      ref="previewModalRef"
      :lesson="previewLesson"
      :loading="previewLoading"
      @close="previewLesson = null"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { PlusIcon, TrashIcon } from '@/components/icons'
import { sectionService } from '@/services/section.service'
import { lessonService } from '@/services/lesson.service'
import BulkActions from '@/components/table/BulkActions.vue'
import LessonPreviewModal from '@/components/shared/admin/LessonPreviewModal.vue'
import LessonItem from '@/components/shared/admin/LessonItem.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useDeleteConfirm } from '@/composables/useDeleteConfirm'
import SectionFormModal from '@/components/forms/SectionFormModal.vue'
import LessonFormModal from '@/components/forms/LessonFormModal.vue'
import { useFormErrors } from '@/composables/useFormErrors'

const props = defineProps<{ courseId: number }>()
const toast = useToast()

// ── Interfaces ────────────────────────────────────────────────
interface Lesson {
  id: number
  title: string
  type: string
  content?: string | null
  section_id?: number | null
  order: number
  is_preview: boolean
  duration?: number | null
  status: number
}

interface Section {
  id: number
  course_id: number
  title: string
  description?: string | null
  order: number
  status: number
  lessons: Lesson[]
}

// ── State ─────────────────────────────────────────────────────
const sectionsList = ref<Section[]>([])
const orphanLessons = ref<Lesson[]>([])
const loading = ref(true)
const expandedSections = reactive(new Set<number | string>())
const previewModalRef = ref()
const previewLesson = ref<any>(null)
const previewLoading = ref(false)

const totalLessons = computed(() => {
  let total = orphanLessons.value.length
  for (const s of sectionsList.value) {
    total += (s.lessons || []).length
  }
  return total
})

// ── Section form state ────────────────────────────────────────
const showSectionModal = ref(false)
const editingSectionId = ref<number | null>(null)
const sSubmitting = ref(false)
const { errors: sErrors, apiError: sSubmitError, handleApiError: handleSectionError, clearErrors: clearSectionErrors } = useFormErrors()

const defaultSForm = () => ({
  title: '',
  description: '',
  order: 0 as number,
  status: 0 as number,
})
const sForm = ref(defaultSForm())

// ── Bulk & Trashed State ───────────────────────────────────────
const currentTab = ref<'active' | 'trashed'>('active')
const trashedLessons = ref<Lesson[]>([])
const loadingTrashed = ref(false)

const selectedLessons = ref<number[]>([])
const isAllSelected = computed(() => {
  if (currentTab.value === 'active') {
    const total = totalLessons.value
    return total > 0 && selectedLessons.value.length === total
  } else {
    return trashedLessons.value.length > 0 && selectedLessons.value.length === trashedLessons.value.length
  }
})

function toggleSelectAll(checked: boolean) {
  if (checked) {
    if (currentTab.value === 'active') {
      const ids: number[] = orphanLessons.value.map(l => l.id)
      sectionsList.value.forEach(s => {
        ids.push(...(s.lessons || []).map(l => l.id))
      })
      selectedLessons.value = ids
    } else {
      selectedLessons.value = trashedLessons.value.map(l => l.id)
    }
  } else {
    selectedLessons.value = []
  }
}

function handleSelectAllChange(e: Event) {
  const checked = (e.target as HTMLInputElement).checked
  toggleSelectAll(checked)
}

function isSectionAllSelected(section: Section) {
  if (!section.lessons || section.lessons.length === 0) return false
  return section.lessons.every(l => selectedLessons.value.includes(l.id))
}

function handleSectionSelectAll(section: Section, e: Event) {
  const checked = (e.target as HTMLInputElement).checked
  if (!section.lessons) return
  const sIds = section.lessons.map(l => l.id)
  if (checked) {
    const newSelected = new Set([...selectedLessons.value, ...sIds])
    selectedLessons.value = Array.from(newSelected)
  } else {
    selectedLessons.value = selectedLessons.value.filter(id => !sIds.includes(id))
  }
}

const isOrphanAllSelected = computed(() => {
  if (orphanLessons.value.length === 0) return false
  return orphanLessons.value.every(l => selectedLessons.value.includes(l.id))
})

function handleOrphanSelectAll(e: Event) {
  const checked = (e.target as HTMLInputElement).checked
  const sIds = orphanLessons.value.map(l => l.id)
  if (checked) {
    const newSelected = new Set([...selectedLessons.value, ...sIds])
    selectedLessons.value = Array.from(newSelected)
  } else {
    selectedLessons.value = selectedLessons.value.filter(id => !sIds.includes(id))
  }
}

// ── Bulk Actions via Reusable Component ────────────────────────

const bulkActionsRef = ref<InstanceType<typeof BulkActions> | null>(null)
const bulkActionLoading = ref(false)

async function doBulkStatusLessons(statusVal: 'activate' | 'deactivate') {
  bulkActionLoading.value = true
  try {
    await lessonService.bulkAction({ ids: selectedLessons.value, action: statusVal })
    toast.success('Cập nhật trạng thái thành công')
    selectedLessons.value = []
    bulkActionsRef.value?.closeModal()
    fetchAll()
  } catch {
    toast.error('Chưa thể cập nhật trạng thái hàng loạt')
  } finally {
    bulkActionLoading.value = false
  }
}

async function doBulkDeleteLessons() {
  bulkActionLoading.value = true
  try {
    await lessonService.bulkDelete(selectedLessons.value)
    toast.success('Xóa bài giảng thành công')
    selectedLessons.value = []
    bulkActionsRef.value?.closeModal()
    fetchAll()
  } catch {
    toast.error('Chưa thể xóa hàng loạt')
  } finally {
    bulkActionLoading.value = false
  }
}

async function doBulkRestoreLessons() {
  bulkActionLoading.value = true
  try {
    await lessonService.bulkRestore(selectedLessons.value)
    toast.success('Khôi phục thành công')
    selectedLessons.value = []
    bulkActionsRef.value?.closeModal()
    fetchTrashed()
  } catch {
    toast.error('Chưa thể khôi phục hàng loạt')
  } finally {
    bulkActionLoading.value = false
  }
}

async function doBulkForceDeleteLessons() {
  bulkActionLoading.value = true
  try {
    await lessonService.bulkForceDelete(selectedLessons.value)
    toast.success('Xóa vĩnh viễn thành công')
    selectedLessons.value = []
    bulkActionsRef.value?.closeModal()
    fetchTrashed()
  } catch {
    toast.error('Chưa thể xóa vĩnh viễn hàng loạt')
  } finally {
    bulkActionLoading.value = false
  }
}

async function doBulkAssignSection(sectionId: number | null) {
  bulkActionLoading.value = true
  try {
    await lessonService.bulkAction({
      ids: selectedLessons.value,
      action: 'assign-section',
      section_id: sectionId,
    })
    const sectionName = sectionId
      ? sectionsList.value.find(s => s.id === sectionId)?.title || 'chương đã chọn'
      : 'Chưa phân chương'
    toast.success(`Đã gán ${selectedLessons.value.length} bài giảng vào "${sectionName}"`)
    selectedLessons.value = []
    bulkActionsRef.value?.closeModal()
    fetchAll()
  } catch {
    toast.error('Chưa thể phân chương hàng loạt')
  } finally {
    bulkActionLoading.value = false
  }
}

function handleRestoreLessonTr(lesson: Lesson) {
  restoreLessonConfirm.confirm(lesson)
}

function handleForceDeleteLessonTr(lesson: Lesson) {
  forceDeleteLessonConfirm.confirm(lesson)
}

async function handlePreviewLesson(lessonId: number) {
  previewLesson.value = null
  previewLoading.value = true
  previewModalRef.value?.open()
  try {
    const res = await lessonService.show(lessonId)
    previewLesson.value = res.data.data
  } catch {
    toast.error('Không thể tải nội dung xem trước')
    previewModalRef.value?.close()
  } finally {
    previewLoading.value = false
  }
}

watch(currentTab, (val) => {
  selectedLessons.value = []
  if (val === 'trashed') {
    fetchTrashed()
  } else {
    fetchAll()
  }
})

async function fetchTrashed() {
  loadingTrashed.value = true
  try {
    const res = await lessonService.trashed({ course_id: props.courseId, per_page: 100 })
    const resData = res.data as any
    trashedLessons.value = Array.isArray(resData?.data) 
      ? resData.data 
      : (resData?.data?.data || [])
  } catch (err: unknown) {
    const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const data = axiosErr.response?.data
    console.error('Fetch trashed error:', JSON.stringify(data, null, 2))
    let msg = data?.message || 'Lỗi tải thùng rác bài giảng'
    if (data?.errors) {
       msg += ': ' + Object.values(data.errors).map((e: string[]) => e[0]).join(', ')
    }
    toast.error(msg)
  } finally {
    loadingTrashed.value = false
  }
}

// ── Lesson form state ─────────────────────────────────────────
const showLessonModal = ref(false)
const editingLessonId = ref<number | null>(null)
const draggedLessonIdx = ref<number | null>(null)
const lSubmitting = ref(false)
const { errors: lErrors, apiError: lSubmitError, handleApiError: handleLessonError, clearErrors: clearLessonErrors } = useFormErrors()

const defaultLForm = () => ({
  section_id: null as number | null,
  title: '',
  type: 'video' as string,
  content: '',
  media_id: null as number | null,
  order: 0 as number,
  duration: null as number | null,
  is_preview: false,
  status: 0 as number,
})
const lForm = ref(defaultLForm())


// ── Toggle state ──────────────────────────────────────────────
const togglingSection = ref<number | null>(null)
const togglingLesson = ref<number | null>(null)

// ── Delete confirmations (via composable) ─────────────────────
const deleteSection = useDeleteConfirm({
  async onConfirm(section: Section) {
    await sectionService.destroy(section.id)
    toast.success('Xóa chương thành công')
    fetchAll()
  },
})

const deleteLesson = useDeleteConfirm({
  async onConfirm(lesson: Lesson) {
    await lessonService.destroy(lesson.id)
    toast.success('Xóa bài giảng thành công')
    fetchAll()
  },
})

const restoreLessonConfirm = useDeleteConfirm({
  async onConfirm(lesson: Lesson) {
    await lessonService.restore(lesson.id)
    toast.success('Khôi phục thành công')
    fetchTrashed()
  },
})

const forceDeleteLessonConfirm = useDeleteConfirm({
  async onConfirm(lesson: Lesson) {
    await lessonService.forceDelete(lesson.id)
    toast.success('Đã xóa vĩnh viễn')
    fetchTrashed()
  },
})

// ── Lesson select helper ──────────────────────────────────────
function toggleLessonSelect(id: number) {
  const idx = selectedLessons.value.indexOf(id)
  if (idx >= 0) {
    selectedLessons.value.splice(idx, 1)
  } else {
    selectedLessons.value.push(id)
  }
}

// ── Fetch data ────────────────────────────────────────────────
async function fetchAll() {
  loading.value = true
  try {
    // Lấy danh sách sections (kèm lessons nested)
    const [sectionsRes, lessonsRes] = await Promise.all([
      sectionService.index(props.courseId, { per_page: 100 }),
      lessonService.index(props.courseId, { per_page: 100 }),
    ])

    const allSections: Section[] = ((sectionsRes.data as any).data || []).map((s: any) => ({
      ...s,
      lessons: [],
    }))

    const allLessons: Lesson[] = (lessonsRes.data as any).data || []

    // Phân bổ lessons vào sections
    const sectionMap = new Map<number, Section>()
    for (const s of allSections) {
      sectionMap.set(s.id, s)
    }

    const orphans: Lesson[] = []
    for (const lesson of allLessons) {
      if (lesson.section_id && sectionMap.has(lesson.section_id)) {
        sectionMap.get(lesson.section_id)!.lessons.push(lesson)
      } else {
        orphans.push(lesson)
      }
    }

    // Sắp xếp sections theo order
    allSections.sort((a, b) => a.order - b.order)
    for (const s of allSections) {
      s.lessons.sort((a, b) => a.order - b.order)
    }

    sectionsList.value = allSections
    orphanLessons.value = orphans

    // Mặc định không auto-expand các chương (để collapsed)
    // Nhưng nếu có bài chưa phân chương thì có thể giữ nguyên tính năng để mở riêng phần này ra
    // if (expandedSections.size === 0) {
    //   if (orphans.length) expandedSections.add('orphan')
    // }
  } catch {
    toast.error('Không thể tải nội dung khóa học')
  } finally {
    loading.value = false
  }
}

onMounted(fetchAll)

// ── Expand/Collapse ───────────────────────────────────────────
function toggleExpand(id: number | string) {
  if (expandedSections.has(id)) expandedSections.delete(id)
  else expandedSections.add(id)
}

// ── Helpers ───────────────────────────────────────────────────
function typeLabel(type: string) {
  return { video: 'Video', document: 'Tài liệu' }[type] || type
}
function typeClass(type: string) {
  return {
    video:    'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
    document: 'bg-orange-100 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400',
  }[type] || 'bg-gray-100 text-gray-600'
}

function openCreateSection() {
  editingSectionId.value = null
  sForm.value = defaultSForm()
  sForm.value.order = sectionsList.value.length
  clearSectionErrors()
  showSectionModal.value = true
}

function openEditSection(section: Section) {
  editingSectionId.value = section.id
  sForm.value = {
    title: section.title,
    description: section.description || '',
    order: section.order,
    status: section.status,
  }
  clearSectionErrors()
  showSectionModal.value = true
}

async function submitSection() {
  clearSectionErrors()
  if (!sForm.value.title) { sErrors.value.title = 'Vui lòng nhập tiêu đề'; return }

  sSubmitting.value = true
  const payload = {
    title: sForm.value.title,
    description: sForm.value.description || null,
    order: sForm.value.order,
    status: sForm.value.status,
  }

  try {
    if (editingSectionId.value) {
      await sectionService.update(editingSectionId.value, payload)
      toast.success('Cập nhật chương thành công')
    } else {
      await sectionService.store(props.courseId, payload)
      toast.success('Tạo chương thành công')
    }
    showSectionModal.value = false
    fetchAll()
  } catch (err: any) {
    handleSectionError(err)
  } finally {
    sSubmitting.value = false
  }
}

async function toggleSectionStatus(section: Section) {
  togglingSection.value = section.id
  try {
    await sectionService.toggleStatus(section.id)
    section.status = section.status === 1 ? 0 : 1
  } catch {
    toast.error('Không thể cập nhật trạng thái chương')
  } finally {
    togglingSection.value = null
  }
}

function confirmDeleteSection(section: Section) {
  deleteSection.confirm(section)
}

async function reorderSection(fromIdx: number, toIdx: number) {
  const arr = [...sectionsList.value]
  const [item] = arr.splice(fromIdx, 1)
  arr.splice(toIdx, 0, item)
  sectionsList.value = arr

  const orders = arr.map((s, i) => ({ id: s.id, order: i }))
  try {
    await sectionService.reorder(orders)
  } catch {
    toast.error('Sắp xếp chương thất bại')
    fetchAll()
  }
}

// ── Lesson CRUD ───────────────────────────────────────────────
function openCreateLesson(sectionId: number | null) {
  editingLessonId.value = null
  lForm.value = defaultLForm()
  lForm.value.section_id = sectionId

  let nextOrder = 0
  if (sectionId) {
    const section = sectionsList.value.find(s => s.id === sectionId)
    if (section && section.lessons) {
      nextOrder = section.lessons.length
    }
  } else {
    nextOrder = orphanLessons.value.length
  }
  lForm.value.order = nextOrder

  clearLessonErrors()
  showLessonModal.value = true
}

function openEditLesson(lesson: Lesson) {
  editingLessonId.value = lesson.id
  lForm.value = {
    section_id: lesson.section_id ?? null,
    title: lesson.title,
    type: lesson.type,
    content: lesson.content || '',
    media_id: null,
    order: lesson.order,
    duration: lesson.duration ?? null,
    is_preview: lesson.is_preview,
    status: lesson.status,
  }
  clearLessonErrors()
  showLessonModal.value = true
}

async function submitLesson() {
  clearLessonErrors()
  if (!lForm.value.title) { lErrors.value.title = 'Vui lòng nhập tiêu đề'; return }

  lSubmitting.value = true
  const payload: any = {
    section_id: lForm.value.section_id || null,
    title: lForm.value.title,
    type: lForm.value.type,
    content: lForm.value.content || null,
    order: lForm.value.order,
    duration: lForm.value.type === 'video' ? (lForm.value.duration ?? null) : null,
    is_preview: lForm.value.is_preview ? 1 : 0,
    status: lForm.value.status,
  }

  if (lForm.value.media_id) {
    if (lForm.value.type === 'video') {
      payload.video_id = lForm.value.media_id
    } else if (lForm.value.type === 'document') {
      payload.document_id = lForm.value.media_id
    }
  }

  try {
    if (editingLessonId.value) {
      await lessonService.update(editingLessonId.value, payload)
      toast.success('Cập nhật bài giảng thành công')
    } else {
      await lessonService.store(props.courseId, payload)
      toast.success('Tạo bài giảng thành công')
    }
    showLessonModal.value = false
    fetchAll()
  } catch (err: any) {
    handleLessonError(err)
    if (lSubmitError.value) {
      toast.error(lSubmitError.value)
    }
  } finally {
    lSubmitting.value = false
  }
}

async function toggleLessonStatus(lesson: Lesson) {
  togglingLesson.value = lesson.id
  try {
    await lessonService.toggleStatus(lesson.id)
    lesson.status = lesson.status === 1 ? 0 : 1
  } catch {
    toast.error('Không thể cập nhật trạng thái bài giảng')
  } finally {
    togglingLesson.value = null
  }
}

function confirmDeleteLesson(lesson: Lesson) {
  deleteLesson.confirm(lesson)
}

async function reorderLesson(section: Section, fromIdx: number, toIdx: number) {
  const arr = [...(section.lessons || [])]
  const [item] = arr.splice(fromIdx, 1)
  arr.splice(toIdx, 0, item)
  section.lessons = arr

  const orders = arr.map((l, i) => ({ id: l.id, order: i }))
  try {
    await lessonService.reorder(orders)
  } catch {
    toast.error('Sắp xếp bài giảng thất bại')
    fetchAll()
  }
}

async function reorderLessonDrag(section: Section, toIdx: number) {
  if (draggedLessonIdx.value === null || draggedLessonIdx.value === toIdx) {
    draggedLessonIdx.value = null
    return
  }
  const fromIdx = draggedLessonIdx.value
  await reorderLesson(section, fromIdx, toIdx)
  draggedLessonIdx.value = null
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
