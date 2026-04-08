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
              <tr
                v-for="(lesson, lIdx) in section.lessons"
                :key="lesson.id"
                class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group cursor-grab active:cursor-grabbing"
                draggable="true"
                @dragstart="draggedLessonIdx = lIdx"
                @dragover.prevent
                @drop.prevent="reorderLessonDrag(section, lIdx)"
              >
                <td class="pl-4 pr-1 py-2.5 w-8">
                  <input type="checkbox" v-model="selectedLessons" :value="lesson.id" @click.stop class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                </td>
                <td class="pl-2 pr-2 py-2.5 text-gray-400 text-xs w-8">{{ lIdx + 1 }}</td>
                <td class="px-2 py-2.5 font-medium text-gray-800 dark:text-gray-200 max-w-[200px] truncate">
                  {{ lesson.title }}
                </td>
                <td class="px-2 py-2.5">
                  <span
                    :class="typeClass(lesson.type)"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium"
                  >
                    {{ typeLabel(lesson.type) }}
                  </span>
                </td>
                <td class="px-2 py-2.5">
                  <span
                    v-if="lesson.is_preview"
                    class="inline-flex items-center px-1.5 py-0.5 rounded border border-indigo-200 bg-indigo-50 text-indigo-600 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-400 text-[10px] font-medium whitespace-nowrap"
                  >
                    Học thử
                  </span>
                </td>
                <td class="px-2 py-2.5 text-gray-500 dark:text-gray-400 text-xs">
                  {{ lesson.duration ? formatSeconds(lesson.duration) : '—' }}
                </td>
                <td class="px-2 py-2.5">
                  <button
                    @click="toggleLessonStatus(lesson)"
                    :disabled="togglingLesson === lesson.id"
                    :class="lesson.status === 1
                      ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                      : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400'"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium cursor-pointer disabled:opacity-50"
                  >
                    {{ lesson.status === 1 ? 'Đã đăng' : 'Nháp' }}
                  </button>
                </td>
                <td class="px-2 py-2.5 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <!-- Drag Handle (replacing UI arrows since we drag the row) -->
                    <button class="p-1 text-gray-400 hover:text-gray-600 transition-colors opacity-0 group-hover:opacity-100" title="Kéo thả hàng để sắp xếp">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/></svg>
                    </button>
                    <button
                      @click="openEditLesson(lesson)"
                      class="p-1 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10 transition-colors"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </button>
                    <button
                      @click="confirmDeleteLesson(lesson)"
                      class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors"
                    >
                      <TrashIcon class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
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
              <tr
                v-for="(lesson, lIdx) in orphanLessons"
                :key="lesson.id"
                class="hover:bg-orange-50 dark:hover:bg-orange-500/5 transition-colors"
              >
                <td class="pl-4 pr-1 py-2.5 w-8">
                  <input type="checkbox" v-model="selectedLessons" :value="lesson.id" @click.stop class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                </td>
                <td class="pl-2 pr-2 py-2.5 text-gray-400 text-xs w-8">{{ lIdx + 1 }}</td>
                <td class="px-2 py-2.5 font-medium text-gray-800 dark:text-gray-200 max-w-[200px] truncate">
                  {{ lesson.title }}
                </td>
                <td class="px-2 py-2.5">
                  <span
                    :class="typeClass(lesson.type)"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium"
                  >
                    {{ typeLabel(lesson.type) }}
                  </span>
                </td>
                <td class="px-2 py-2.5">
                  <span
                    v-if="lesson.is_preview"
                    class="inline-flex items-center px-1.5 py-0.5 rounded border border-indigo-200 bg-indigo-50 text-indigo-600 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-400 text-[10px] font-medium whitespace-nowrap"
                  >
                    Học thử
                  </span>
                </td>
                <td class="px-2 py-2.5 text-gray-500 text-xs">{{ lesson.duration ? formatSeconds(lesson.duration) : '—' }}</td>
                <td class="px-2 py-2.5">
                  <button
                    @click="toggleLessonStatus(lesson)"
                    :disabled="togglingLesson === lesson.id"
                    :class="lesson.status === 1
                      ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                      : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400'"
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium cursor-pointer disabled:opacity-50"
                  >
                    {{ lesson.status === 1 ? 'Đã đăng' : 'Nháp' }}
                  </button>
                </td>
                <td class="px-2 py-2.5 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="openEditLesson(lesson)" class="p-1 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10 transition-colors">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </button>
                    <button @click="confirmDeleteLesson(lesson)" class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors">
                      <TrashIcon class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
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
    <Teleport to="body">
      <div
        v-if="showSectionModal"
        class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4"
        @click.self="showSectionModal = false"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md p-6">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-5">
            {{ editingSectionId ? 'Chỉnh sửa chương' : 'Thêm chương mới' }}
          </h3>
          <form @submit.prevent="submitSection" class="space-y-4">
            <div>
              <label class="label-form">Tiêu đề <span class="text-red-500">*</span></label>
              <input v-model="sForm.title" type="text" class="input-field" :class="{ 'input-error': sErrors.title }" placeholder="Chương 1: Giới thiệu" />
              <p v-if="sErrors.title" class="error-msg">{{ sErrors.title }}</p>
            </div>
            <div>
              <label class="label-form">Mô tả</label>
              <textarea v-model="sForm.description" rows="2" class="input-field resize-none" placeholder="Mô tả nội dung chương..." />
            </div>
            <div class="flex items-center gap-4">
              <div class="flex-1">
                <label class="label-form">Thứ tự</label>
                <input v-model.number="sForm.order" type="number" min="0" class="input-field" placeholder="0" />
              </div>
              <div>
                <label class="label-form">Trạng thái</label>
                <select v-model="sForm.status" class="input-field w-auto px-3">
                  <option :value="0">Nháp</option>
                  <option :value="1">Đã đăng</option>
                </select>
              </div>
            </div>

            <p v-if="sSubmitError" class="text-sm text-red-500">{{ sSubmitError }}</p>

            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="showSectionModal = false" class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                Hủy
              </button>
              <button
                type="submit"
                :disabled="sSubmitting"
                class="px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 disabled:opacity-50 flex items-center gap-2"
              >
                <svg v-if="sSubmitting" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ editingSectionId ? 'Cập nhật' : 'Tạo mới' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═══════ MODAL: Lesson Form ═══════ -->
    <Teleport to="body">
      <div
        v-if="showLessonModal"
        class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4"
        @click.self="showLessonModal = false"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-5">
            {{ editingLessonId ? 'Chỉnh sửa bài giảng' : 'Thêm bài giảng' }}
          </h3>

          <form @submit.prevent="submitLesson" class="space-y-4">
            <!-- Chương -->
            <div>
              <label class="label-form">Chương</label>
              <select v-model="lForm.section_id" class="input-field">
                <option :value="null">— Chưa phân chương —</option>
                <option v-for="s in sectionsList" :key="s.id" :value="s.id">{{ s.title }}</option>
              </select>
            </div>

            <div>
              <label class="label-form">Tiêu đề <span class="text-red-500">*</span></label>
              <input v-model="lForm.title" type="text" class="input-field" :class="{ 'input-error': lErrors.title }" placeholder="Giới thiệu khóa học" />
              <p v-if="lErrors.title" class="error-msg">{{ lErrors.title }}</p>
            </div>

            <div>
              <label class="label-form">Loại bài giảng <span class="text-red-500">*</span></label>
              <select v-model="lForm.type" class="input-field">
                <option value="video">Video</option>
                <option value="document">Tài liệu</option>
              </select>
            </div>

            <div>
              <label class="label-form">Nội dung tải lên (Video / Tài liệu) <span class="text-red-500">*</span></label>
              
              <div v-if="lForm.content" class="flex flex-col gap-2 relative">
                 <input v-model="lForm.content" type="text" class="input-field pr-10" />
                 <button
                  type="button"
                  @click="lForm.content = ''"
                  class="absolute right-2 top-2 w-6 h-6 flex items-center justify-center bg-gray-100 hover:bg-red-100 text-gray-500 hover:text-red-500 rounded-md transition-colors"
                 >
                   <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                 </button>
              </div>

              <div
                v-else
                @dragover.prevent=""
                @drop.prevent="handleLessonDrop"
                @click="!lUploading && lFileInput?.click()"
                :class="[lUploading ? 'opacity-70 cursor-not-allowed' : 'cursor-pointer hover:border-blue-400 dark:hover:border-blue-500', 'border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl px-4 py-8 text-center transition-all']"
              >
                 <div v-if="lUploading" class="max-w-xs mx-auto">
                    <div class="flex justify-between text-xs text-blue-600 dark:text-blue-400 font-medium mb-2">
                       <span>Đang tải lên...</span>
                       <span>{{ lUploadProgress }}%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                       <div class="h-full bg-blue-500 rounded-full transition-all duration-300" :style="{ width: lUploadProgress + '%' }" />
                    </div>
                 </div>
                 <div v-else class="flex flex-col items-center justify-center space-y-2">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                      <span class="text-blue-500">Nhấp để tải lên</span> hoặc kéo thả file
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ lForm.type === 'video' ? 'Hỗ trợ MP4, WebM (Max: 50MB)' : 'Hỗ trợ PDF, DOCX (Max: 10MB)' }}
                    </p>
                 </div>
              </div>
              <input ref="lFileInput" type="file" class="hidden" :accept="lForm.type === 'video' ? 'video/*' : '*/*'" @change="handleLessonFileSelect" />
              <p v-if="lUploadError" class="error-msg mt-2">{{ lUploadError }}</p>
              <p v-if="lErrors.content" class="error-msg mt-1">{{ lErrors.content }}</p>
              <p v-if="lErrors.video_id" class="error-msg mt-1">{{ lErrors.video_id }}</p>
              <p v-if="lErrors.document_id" class="error-msg mt-1">{{ lErrors.document_id }}</p>
            </div>

            <div v-if="lForm.type === 'video'">
              <label class="label-form">Thời lượng (giây)</label>
              <input v-model.number="lForm.duration" type="number" min="0" class="input-field cursor-not-allowed bg-gray-50 dark:bg-gray-800/50" readonly disabled placeholder="Tự động tính khi tải lên video" />
            </div>

            <div class="flex items-center gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="lForm.is_preview" type="checkbox" class="w-4 h-4 rounded border-gray-300" />
                <span class="text-sm text-gray-700 dark:text-gray-400">Cho xem thử (preview)</span>
              </label>
              <div>
                <select v-model="lForm.status" class="input-field w-auto px-3">
                  <option :value="0">Nháp</option>
                  <option :value="1">Đã đăng</option>
                </select>
              </div>
            </div>

            <p v-if="lSubmitError" class="text-sm text-red-500">{{ lSubmitError }}</p>

            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="showLessonModal = false" class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                Hủy
              </button>
              <button
                type="submit"
                :disabled="lSubmitting"
                class="px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 disabled:opacity-50 flex items-center gap-2"
              >
                <svg v-if="lSubmitting" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ editingLessonId ? 'Cập nhật' : 'Tạo mới' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ═══════ MODAL: Confirm Delete Section ═══════ -->
    <Teleport to="body">
      <div
        v-if="deleteSectionTarget"
        class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4"
        @click.self="deleteSectionTarget = null"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-sm p-6">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-2">Xác nhận xóa chương</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
            Bạn có chắc muốn xóa chương
            <strong class="text-gray-800 dark:text-white/90">{{ deleteSectionTarget.title }}</strong>?
          </p>
          <p class="text-xs text-orange-500 mb-5">
            ⚠️ Các bài giảng trong chương sẽ chuyển thành "Chưa phân chương".
          </p>
          <div class="flex justify-end gap-3">
            <button @click="deleteSectionTarget = null" class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">Hủy</button>
            <button @click="doDeleteSection" :disabled="deletingSection" class="px-4 py-2 text-sm rounded-lg bg-red-500 text-white hover:bg-red-600 disabled:opacity-50">
              {{ deletingSection ? 'Đang xóa...' : 'Xóa' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══════ MODAL: Confirm Delete Lesson ═══════ -->
    <Teleport to="body">
      <div
        v-if="deleteLessonTarget"
        class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 px-4"
        @click.self="deleteLessonTarget = null"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-sm p-6">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-2">Xác nhận xóa bài giảng</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Bạn có chắc muốn xóa bài giảng
            <strong class="text-gray-800 dark:text-white/90">{{ deleteLessonTarget.title }}</strong>?
          </p>
          <div class="flex justify-end gap-3">
            <button @click="deleteLessonTarget = null" class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">Hủy</button>
            <button @click="doDeleteLesson" :disabled="deletingLesson" class="px-4 py-2 text-sm rounded-lg bg-red-500 text-white hover:bg-red-600 disabled:opacity-50">
              {{ deletingLesson ? 'Đang xóa...' : 'Xóa' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══════ REUSABLE BULK ACTIONS COMPONENT ═══════ -->
    <BulkActions
      ref="bulkActionsRef"
      :count="selectedLessons.length"
      itemName="bài giảng"
      :is-trashed="currentTab === 'trashed'"
      :loading="bulkActionLoading"
      @publish="doBulkStatusLessons('activate')"
      @draft="doBulkStatusLessons('deactivate')"
      @delete="doBulkDeleteLessons"
      @restore="doBulkRestoreLessons"
      @force-delete="doBulkForceDeleteLessons"
      @clear="selectedLessons = []"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { PlusIcon, TrashIcon } from '@/icons'
import { sectionsApi } from '@/api/sectionsApi'
import { lessonsApi } from '@/api/lessonsApi'
import { uploadApi } from '@/api/uploadApi'
import { formatSeconds } from '@/utils/formatDuration'
import BulkActions from '@/components/admin/BulkActions.vue'

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
const sSubmitError = ref('')
const sErrors = ref<Record<string, string>>({})

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
    await lessonsApi.bulkAction({ ids: selectedLessons.value, action: statusVal })
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
    await lessonsApi.bulkDelete(selectedLessons.value)
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
    await lessonsApi.bulkRestore(selectedLessons.value)
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
    await lessonsApi.bulkForceDelete(selectedLessons.value)
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

async function handleRestoreLessonTr(lesson: Lesson) {
  if (!confirm(`Khôi phục bài giảng "${lesson.title}"?`)) return
  try {
    await lessonsApi.restore(lesson.id)
    toast.success('Khôi phục thành công')
    fetchTrashed()
  } catch {
    toast.error('Khôi phục thất bại')
  }
}

async function handleForceDeleteLessonTr(lesson: Lesson) {
  if (!confirm(`Xóa vĩnh viễn bài giảng "${lesson.title}"?`)) return
  try {
    await lessonsApi.forceDelete(lesson.id)
    toast.success('Đã xóa vĩnh viễn')
    fetchTrashed()
  } catch {
    toast.error('Xóa vĩnh viễn thất bại')
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
    const res = await lessonsApi.trashed({ course_id: props.courseId, per_page: 100 })
    trashedLessons.value = Array.isArray(res.data?.data) 
      ? res.data.data 
      : (res.data?.data?.data || [])
  } catch (err: any) {
    const data = err.response?.data
    console.error('Fetch trashed error:', JSON.stringify(data, null, 2))
    let msg = data?.message || 'Lỗi tải thùng rác bài giảng'
    if (data?.errors) {
       msg += ': ' + Object.values(data.errors).map(e => e[0]).join(', ')
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
const lSubmitError = ref('')
const lErrors = ref<Record<string, string>>({})

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

// ── Lesson Upload State ───────────────────────────────────────
const lUploading = ref(false)
const lUploadProgress = ref(0)
const lUploadError = ref('')
const lFileInput = ref<HTMLInputElement>()

async function handleLessonDrop(event: DragEvent) {
  const file = event.dataTransfer?.files?.[0]
  if (file) uploadLessonFile(file)
}
async function handleLessonFileSelect(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) await uploadLessonFile(file)
  if (input) input.value = ''
}
function extractVideoDuration(file: File): Promise<number | null> {
  return new Promise((resolve) => {
    const video = document.createElement('video')
    video.preload = 'metadata'
    video.onloadedmetadata = () => {
      window.URL.revokeObjectURL(video.src)
      resolve(Math.round(video.duration))
    }
    video.onerror = () => resolve(null)
    video.src = URL.createObjectURL(file)
  })
}

async function uploadLessonFile(file: File) {
  lUploadError.value = ''
  lUploadProgress.value = 0
  
  if (lForm.value.type === 'video' && !file.type.startsWith('video/')) {
    lUploadError.value = 'Vui lòng chọn file video.'
    return
  }

  if (lForm.value.type === 'video') {
    const duration = await extractVideoDuration(file)
    if (duration) lForm.value.duration = duration
  }
  
  lUploading.value = true
  try {
    const onProgress = (progressEvent: any) => {
      if (progressEvent.total) {
        lUploadProgress.value = Math.round((progressEvent.loaded / progressEvent.total) * 100)
      }
    }
    
    let res;
    if (lForm.value.type === 'video') {
       res = await uploadApi.video(file, onProgress)
    } else {
       res = await uploadApi.document(file, onProgress)
    }
    
    let url = res.data.data.url
    lForm.value.media_id = res.data.data.id
    try {
      const parsed = new URL(url)
      if (parsed.origin !== window.location.origin) {
        url = parsed.pathname
      }
    } catch {}
    
    lForm.value.content = url
    toast.success('Tải lên thành công')
  } catch (err: any) {
    lUploadError.value = err.response?.data?.message || 'Tải lên thất bại'
  } finally {
    lUploading.value = false
    setTimeout(() => { lUploadProgress.value = 0 }, 1000)
  }
}

// ── Toggle & Delete state ─────────────────────────────────────
const togglingSection = ref<number | null>(null)
const togglingLesson = ref<number | null>(null)
const deleteSectionTarget = ref<Section | null>(null)
const deletingSection = ref(false)
const deleteLessonTarget = ref<Lesson | null>(null)
const deletingLesson = ref(false)

// ── Fetch data ────────────────────────────────────────────────
async function fetchAll() {
  loading.value = true
  try {
    // Lấy danh sách sections (kèm lessons nested)
    const [sectionsRes, lessonsRes] = await Promise.all([
      sectionsApi.index(props.courseId, { per_page: 100 }),
      lessonsApi.index(props.courseId, { per_page: 100 }),
    ])

    const allSections: Section[] = (sectionsRes.data.data || []).map((s: any) => ({
      ...s,
      lessons: [],
    }))

    const allLessons: Lesson[] = lessonsRes.data.data || []

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

    // Auto-expand tất cả sections lần đầu
    if (expandedSections.size === 0) {
      for (const s of allSections) {
        expandedSections.add(s.id)
      }
      if (orphans.length) expandedSections.add('orphan')
    }
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

// ── Section CRUD ──────────────────────────────────────────────
function openCreateSection() {
  editingSectionId.value = null
  sForm.value = defaultSForm()
  sForm.value.order = sectionsList.value.length
  sErrors.value = {}
  sSubmitError.value = ''
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
  sErrors.value = {}
  sSubmitError.value = ''
  showSectionModal.value = true
}

async function submitSection() {
  sErrors.value = {}
  sSubmitError.value = ''
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
      await sectionsApi.update(editingSectionId.value, payload)
      toast.success('Cập nhật chương thành công')
    } else {
      await sectionsApi.store(props.courseId, payload)
      toast.success('Tạo chương thành công')
    }
    showSectionModal.value = false
    fetchAll()
  } catch (err: any) {
    const data = err.response?.data
    if (err.response?.status === 422 && data?.errors) {
      for (const [key, msgs] of Object.entries(data.errors as Record<string, string[]>)) {
        sErrors.value[key] = msgs[0]
      }
    } else {
      sSubmitError.value = data?.message || 'Có lỗi xảy ra'
    }
  } finally {
    sSubmitting.value = false
  }
}

async function toggleSectionStatus(section: Section) {
  togglingSection.value = section.id
  try {
    await sectionsApi.toggleStatus(section.id)
    section.status = section.status === 1 ? 0 : 1
  } catch {
    toast.error('Không thể cập nhật trạng thái chương')
  } finally {
    togglingSection.value = null
  }
}

function confirmDeleteSection(section: Section) {
  deleteSectionTarget.value = section
}

async function doDeleteSection() {
  if (!deleteSectionTarget.value) return
  deletingSection.value = true
  try {
    await sectionsApi.destroy(deleteSectionTarget.value.id)
    toast.success('Xóa chương thành công')
    deleteSectionTarget.value = null
    fetchAll()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Xóa chương thất bại')
  } finally {
    deletingSection.value = false
  }
}

async function reorderSection(fromIdx: number, toIdx: number) {
  const arr = [...sectionsList.value]
  const [item] = arr.splice(fromIdx, 1)
  arr.splice(toIdx, 0, item)
  sectionsList.value = arr

  const orders = arr.map((s, i) => ({ id: s.id, order: i }))
  try {
    await sectionsApi.reorder(orders)
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

  lErrors.value = {}
  lSubmitError.value = ''
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
  lErrors.value = {}
  lSubmitError.value = ''
  showLessonModal.value = true
}

async function submitLesson() {
  lErrors.value = {}
  lSubmitError.value = ''
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
      await lessonsApi.update(editingLessonId.value, payload)
      toast.success('Cập nhật bài giảng thành công')
    } else {
      await lessonsApi.store(props.courseId, payload)
      toast.success('Tạo bài giảng thành công')
    }
    showLessonModal.value = false
    fetchAll()
  } catch (err: any) {
    const data = err.response?.data
    if (err.response?.status === 422 && data?.errors) {
      const firstErrorMsg = Object.values(data.errors)[0]?.[0] as string | undefined
      if (firstErrorMsg) {
        toast.error(firstErrorMsg) // Trực tiếp show toast lên để dễ nhìn
      }
      for (const [key, msgs] of Object.entries(data.errors as Record<string, string[]>)) {
        lErrors.value[key] = msgs[0]
      }
    } else {
      const msg = data?.message || 'Có lỗi xảy ra'
      lSubmitError.value = msg
      toast.error(msg)
    }
  } finally {
    lSubmitting.value = false
  }
}

async function toggleLessonStatus(lesson: Lesson) {
  togglingLesson.value = lesson.id
  try {
    await lessonsApi.toggleStatus(lesson.id)
    lesson.status = lesson.status === 1 ? 0 : 1
  } catch {
    toast.error('Không thể cập nhật trạng thái bài giảng')
  } finally {
    togglingLesson.value = null
  }
}

function confirmDeleteLesson(lesson: Lesson) {
  deleteLessonTarget.value = lesson
}

async function doDeleteLesson() {
  if (!deleteLessonTarget.value) return
  deletingLesson.value = true
  try {
    await lessonsApi.destroy(deleteLessonTarget.value.id)
    toast.success('Xóa bài giảng thành công')
    deleteLessonTarget.value = null
    fetchAll()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Xóa bài giảng thất bại')
  } finally {
    deletingLesson.value = false
  }
}

async function reorderLesson(section: Section, fromIdx: number, toIdx: number) {
  const arr = [...(section.lessons || [])]
  const [item] = arr.splice(fromIdx, 1)
  arr.splice(toIdx, 0, item)
  section.lessons = arr

  const orders = arr.map((l, i) => ({ id: l.id, order: i }))
  try {
    await lessonsApi.reorder(orders)
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
