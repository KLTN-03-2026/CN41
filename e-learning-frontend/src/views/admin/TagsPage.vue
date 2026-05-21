<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">Thẻ (Tags)</h2>
        <p class="text-sm text-gray-500 mt-0.5">Quản lý các từ khóa gắn cho bài viết</p>
      </div>
      <button
        v-permission="'tags.create'"
        @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors"
      >
        <PlusIcon class="w-4 h-4" /> Thêm thẻ
      </button>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50/50">
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Tên thẻ</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Slug</th>
              <th class="text-left text-xs font-medium text-gray-500 px-4 py-3">Ngày tạo</th>
              <th class="text-right text-xs font-medium text-gray-500 px-4 py-3">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <template v-if="loading">
              <tr v-for="i in 5" :key="i">
                <td class="px-4 py-3">
                  <div class="h-4 w-32 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-24 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-20 bg-gray-100 rounded animate-pulse"></div>
                </td>
                <td class="px-4 py-3">
                  <div class="h-4 w-16 bg-gray-100 rounded animate-pulse ml-auto"></div>
                </td>
              </tr>
            </template>
            <template v-else-if="tags.length">
              <tr v-for="tag in tags" :key="tag.id" class="hover:bg-gray-50/50 transition-colors">
                <td class="px-4 py-3">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                  >
                    # {{ tag.name }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ tag.slug }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(tag.created_at) }}</td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button
                      v-permission="'tags.edit'"
                      @click="openEdit(tag)"
                      class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors"
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
                      v-permission="'tags.delete'"
                      @click="deleteTag(tag.id)"
                      class="p-1.5 rounded-lg hover:bg-red-50 text-gray-500 hover:text-red-500 transition-colors"
                      title="Xoá"
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
            <tr v-else>
              <td colspan="4" class="px-4 py-12 text-center text-gray-400 text-sm">
                Chưa có thẻ nào.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showModal"
          class="fixed inset-0 z-[999999] flex items-center justify-center bg-black/50 px-4"
          @click.self="showModal = false"
        >
          <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-5">
              {{ editingId ? 'Chỉnh sửa thẻ' : 'Thêm thẻ mới' }}
            </h3>

            <form @submit.prevent="submitForm" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"
                  >Tên thẻ <span class="text-red-500">*</span></label
                >
                <input
                  v-model="form.name"
                  @input="autoSlug"
                  type="text"
                  required
                  class="input-field"
                  :class="{ 'border-red-400': formErrors.name }"
                  placeholder="VD: Laravel"
                />
                <p v-if="formErrors.name" class="text-xs text-red-500 mt-1">
                  {{ formErrors.name }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"
                  >Slug <span class="text-red-500">*</span></label
                >
                <input
                  v-model="form.slug"
                  type="text"
                  required
                  class="input-field font-mono text-xs"
                  :class="{ 'border-red-400': formErrors.slug }"
                  placeholder="laravel"
                />
                <p v-if="formErrors.slug" class="text-xs text-red-500 mt-1">
                  {{ formErrors.slug }}
                </p>
              </div>

              <p v-if="submitError" class="text-sm text-red-500">{{ submitError }}</p>

              <div class="flex justify-end gap-3 pt-2">
                <button
                  type="button"
                  @click="showModal = false"
                  class="px-4 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50"
                >
                  Hủy
                </button>
                <button
                  type="submit"
                  :disabled="submitting"
                  class="px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 disabled:opacity-50"
                >
                  {{ submitting ? 'Đang lưu...' : editingId ? 'Cập nhật' : 'Tạo mới' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { PlusIcon } from '@/components/icons'
import { useTags } from '@/composables/useTags'
import { formatDate } from '@/utils/formatDate'

const {
  tags,
  loading,
  showModal,
  editingId,
  submitting,
  formErrors,
  submitError,
  form,
  fetchTags,
  autoSlug,
  openCreate,
  openEdit,
  submitForm,
  deleteTag,
} = useTags()

onMounted(() => {
  fetchTags()
})
</script>

<style scoped>
.input-field {
  @apply w-full h-10 px-3 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-800
         focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all;
}
</style>
