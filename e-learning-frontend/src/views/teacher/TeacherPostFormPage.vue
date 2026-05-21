<template>
  <div class="max-w-4xl">
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

      <div class="space-y-5">
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
            class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 rounded-lg px-3 py-2 text-sm bg-gray-50 cursor-not-allowed"
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
            style="min-height: 300px"
            class="bg-white dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600"
          />
          <p v-if="errors.content" class="text-red-500 text-xs mt-1">{{ errors.content[0] }}</p>
        </div>

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
          <div class="flex flex-wrap gap-3">
            <label
              v-for="tag in tags"
              :key="tag.id"
              class="flex items-center gap-1.5 cursor-pointer"
            >
              <input
                type="checkbox"
                :value="tag.id"
                v-model="form.tag_ids"
                class="rounded text-blue-600 focus:ring-blue-500"
              />
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ tag.name }}</span>
            </label>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3 pt-2">
          <button
            @click="submit"
            :disabled="saving"
            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
          >
            {{ saving ? 'Đang lưu...' : isEdit ? 'Lưu thay đổi' : 'Gửi duyệt' }}
          </button>
          <router-link
            to="/teacher/posts"
            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
          >
            Hủy
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { useTeacherPostForm } from '@/composables/useTeacherPostForm'

const { form, categories, tags, loading, saving, errors, isEdit, onTitleChange, submit } =
  useTeacherPostForm()
</script>
