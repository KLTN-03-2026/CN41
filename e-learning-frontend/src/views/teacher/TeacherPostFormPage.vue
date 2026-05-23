<template>
  <div class="max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
      {{ isEdit ? 'Chỉnh sửa bài viết' : 'Viết bài mới' }}
    </h1>

    <div v-if="loading" class="text-center py-12 text-gray-400">Đang tải...</div>

    <template v-else>
      <div
        v-if="!isEdit"
        class="mb-5 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-sm text-blue-700 dark:text-blue-300"
      >
        Bài viết sẽ được Admin xét duyệt trước khi đăng công khai.
      </div>

      <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content (2/3 width) -->
        <div class="lg:col-span-2 space-y-5">
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 space-y-5">
            <!-- Title -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Tiêu đề <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.title"
                @input="onTitleChange(form.title)"
                type="text"
                placeholder="Nhập tiêu đề bài viết..."
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title[0] }}</p>
            </div>

            <!-- Slug (read-only) -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
              <input
                :value="form.slug"
                type="text"
                readonly
                class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 rounded-lg px-3 py-2 text-sm bg-gray-50 dark:bg-gray-900/50 cursor-not-allowed"
              />
              <p v-if="errors.slug" class="text-red-500 text-xs mt-1">{{ errors.slug[0] }}</p>
            </div>

            <!-- Content (Quill) -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Nội dung <span class="text-red-500">*</span>
              </label>
              <QuillEditor
                v-model:content="form.content"
                contentType="html"
                theme="snow"
                style="min-height: 350px"
                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600"
              />
              <p v-if="errors.content" class="text-red-500 text-xs mt-1">{{ errors.content[0] }}</p>
            </div>
          </div>
        </div>

        <!-- Sidebar (1/3 width) -->
        <div class="space-y-6">
          <!-- Phân loại (Category & Tags) -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 space-y-4">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 border-b dark:border-gray-700 pb-2">Phân loại</h3>
            
            <!-- Category -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Danh mục</label>
              <select
                v-model="form.post_category_id"
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option :value="null">-- Không có danh mục --</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>

            <!-- Tags -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tags</label>
              <div class="flex flex-wrap gap-2 max-h-[150px] overflow-y-auto pr-1">
                <label
                  v-for="tag in tags"
                  :key="tag.id"
                  class="flex items-center gap-1.5 cursor-pointer bg-gray-50 dark:bg-gray-700 px-2 py-1 rounded-md border border-gray-200 dark:border-gray-600"
                >
                  <input
                    type="checkbox"
                    :value="tag.id"
                    v-model="form.tag_ids"
                    class="rounded text-blue-600 focus:ring-blue-500"
                  />
                  <span class="text-xs text-gray-700 dark:text-gray-300">{{ tag.name }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Thumbnail -->
          <ThumbnailUpload v-model="form.thumbnail" />

          <!-- Actions -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <div class="flex items-center gap-3">
              <button
                type="submit"
                :disabled="saving"
                class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50 text-center shadow-lg shadow-blue-500/20"
              >
                {{ saving ? 'Đang lưu...' : isEdit ? 'Lưu thay đổi' : 'Gửi duyệt' }}
              </button>
              <router-link
                to="/teacher/posts"
                class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-center transition-colors"
              >
                Hủy
              </router-link>
            </div>
          </div>
        </div>
      </form>
    </template>
  </div>
</template>

<script setup lang="ts">
import { useTeacherPostForm } from '@/composables/useTeacherPostForm'
import ThumbnailUpload from '@/components/forms/ThumbnailUpload.vue'

const { form, categories, tags, loading, saving, errors, isEdit, onTitleChange, submit } =
  useTeacherPostForm()
</script>
