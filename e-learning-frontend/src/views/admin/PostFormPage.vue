<template>
  <div class="p-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <router-link
        to="/admin/posts"
        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
      </router-link>
      <div>
        <h2 class="text-lg font-semibold text-gray-800">
          {{ isEdit ? 'Chỉnh sửa bài viết' : 'Viết bài mới' }}
        </h2>
        <p class="text-sm text-gray-500 mt-0.5">
          {{ isEdit ? form.title : 'Chia sẻ kiến thức và tin tức mới nhất' }}
        </p>
      </div>
    </div>

    <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <div class="card-box">
          <div class="space-y-4">
            <div>
              <label class="label-form">Tiêu đề bài viết <span class="text-red-500">*</span></label>
              <input
                v-model="form.title"
                @input="autoSlug"
                type="text"
                required
                class="input-field text-lg font-medium"
                :class="{ 'border-red-400': formErrors.title }"
                placeholder="Nhập tiêu đề hấp dẫn..."
              />
              <p v-if="formErrors.title" class="text-xs text-red-500 mt-1">
                {{ formErrors.title }}
              </p>
            </div>

            <div>
              <label class="label-form">Slug <span class="text-red-500">*</span></label>
              <input
                v-model="form.slug"
                type="text"
                required
                class="input-field font-mono text-xs bg-gray-50"
                :class="{ 'border-red-400': formErrors.slug }"
                placeholder="tieu-de-bai-viet"
              />
              <p v-if="formErrors.slug" class="text-xs text-red-500 mt-1">{{ formErrors.slug }}</p>
            </div>

            <div>
              <label class="label-form">Nội dung <span class="text-red-500">*</span></label>
              <div class="quill-wrapper">
                <QuillEditor
                  v-model:content="form.content"
                  content-type="html"
                  theme="snow"
                  toolbar="full"
                  class="min-h-[400px]"
                />
              </div>
              <p v-if="formErrors.content" class="text-xs text-red-500 mt-1">
                {{ formErrors.content }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Publishing -->
        <div class="card-box">
          <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Xuất bản</h3>
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Trạng thái:</span>
              <span
                :class="
                  form.is_published ? 'text-green-600 font-bold' : 'text-yellow-600 font-bold'
                "
                class="text-xs uppercase"
              >
                {{ form.is_published ? 'Công khai' : 'Bản nháp' }}
              </span>
            </div>

            <div class="flex items-center gap-2">
              <input
                type="checkbox"
                v-model="form.is_published"
                id="is_published"
                class="rounded text-blue-500 focus:ring-blue-500"
              />
              <label for="is_published" class="text-sm text-gray-700 cursor-pointer"
                >Công khai ngay bài viết này</label
              >
            </div>

            <p v-if="submitError" class="text-xs text-red-500 bg-red-50 p-2 rounded">
              {{ submitError }}
            </p>

            <button
              type="submit"
              :disabled="submitting"
              class="w-full py-2.5 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 transition-colors shadow-lg shadow-blue-500/20"
            >
              {{ submitting ? 'Đang lưu...' : isEdit ? 'Cập nhật bài viết' : 'Đăng bài viết' }}
            </button>
          </div>
        </div>

        <!-- Category & Tags -->
        <div class="card-box">
          <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Phân loại</h3>
          <div class="space-y-4">
            <div>
              <label class="label-form">Danh mục <span class="text-red-500">*</span></label>
              <select v-model="form.post_category_id" required class="input-field">
                <option :value="null">-- Chọn danh mục --</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="label-form">Thẻ (Tags)</label>
              <div class="flex flex-wrap gap-2 mb-2">
                <span
                  v-for="tagId in form.tag_ids"
                  :key="tagId"
                  class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-600 text-xs rounded-md"
                >
                  {{ getTagName(tagId) }}
                  <button type="button" @click="removeTag(tagId)" class="hover:text-red-500">
                    &times;
                  </button>
                </span>
              </div>
              <select @change="addTag" class="input-field">
                <option value="">-- Chọn thẻ --</option>
                <option
                  v-for="tag in tags"
                  :key="tag.id"
                  :value="tag.id"
                  :disabled="form.tag_ids.includes(tag.id)"
                >
                  {{ tag.name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Thumbnail -->
        <div class="card-box">
          <h3 class="text-sm font-semibold text-gray-700 mb-4 border-b pb-2">Ảnh đại diện</h3>
          <ThumbnailUpload v-model="form.thumbnail" label="Chọn ảnh thumbnail" />
        </div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import PostService from '@/services/post.service'
import ThumbnailUpload from '@/components/forms/ThumbnailUpload.vue'
import type { PostCategory, Tag } from '@/types/post.types'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const postId = computed(() => (route.params.id ? Number(route.params.id) : null))
const isEdit = computed(() => !!postId.value)

const categories = ref<PostCategory[]>([])
const tags = ref<Tag[]>([])
const submitting = ref(false)
const submitError = ref('')
const formErrors = ref<Record<string, string>>({})

const form = reactive({
  title: '',
  slug: '',
  content: '',
  post_category_id: null as number | null,
  tag_ids: [] as number[],
  is_published: false,
  thumbnail: '' as string | File,
})

function autoSlug() {
  if (isEdit.value) return
  form.slug = form.title
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[đĐ]/g, 'd')
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
}

async function fetchInitialData() {
  try {
    const [catRes, tagRes] = await Promise.all([PostService.getCategories(), PostService.getTags()])
    categories.value = catRes.data.data
    tags.value = tagRes.data.data

    if (isEdit.value) {
      const postRes = await PostService.getPost(postId.value!)
      const p = postRes.data.data
      form.title = p.title
      form.slug = p.slug
      form.content = p.content
      form.post_category_id = p.post_category_id
      form.tag_ids = p.tags?.map((t: Tag) => t.id) || []
      form.is_published = !!p.is_published
      form.thumbnail = p.thumbnail || ''
    }
  } catch {
    toast.error('Không thể tải dữ liệu')
  }
}

function getTagName(id: number) {
  return tags.value.find((t) => t.id === id)?.name || id
}

function addTag(e: Event) {
  const id = Number((e.target as HTMLSelectElement).value)
  if (id && !form.tag_ids.includes(id)) {
    form.tag_ids.push(id)
  }
  ;(e.target as HTMLSelectElement).value = ''
}

function removeTag(id: number) {
  form.tag_ids = form.tag_ids.filter((t) => t !== id)
}

async function submitForm() {
  submitting.value = true
  submitError.value = ''
  formErrors.value = {}

  const formData = new FormData()
  formData.append('title', form.title)
  formData.append('slug', form.slug)
  formData.append('content', form.content)
  if (form.post_category_id) formData.append('post_category_id', String(form.post_category_id))
  formData.append('is_published', form.is_published ? '1' : '0')

  form.tag_ids.forEach((id) => {
    formData.append('tag_ids[]', String(id))
  })

  if (form.thumbnail instanceof File) {
    formData.append('thumbnail', form.thumbnail)
  }

  try {
    if (isEdit.value) {
      await PostService.updatePost(postId.value!, formData)
      toast.success('Cập nhật bài viết thành công')
    } else {
      await PostService.createPost(formData)
      toast.success('Đăng bài viết thành công')
    }
    router.push('/admin/posts')
  } catch (err) {
    const e = err as {
      response?: { status?: number; data?: { errors?: Record<string, string[]>; message?: string } }
    }
    if (e.response?.status === 422) {
      formErrors.value = e.response.data?.errors ?? {}
    } else {
      submitError.value = err.response?.data?.message || 'Có lỗi xảy ra'
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchInitialData()
})
</script>

<style scoped>
.card-box {
  @apply bg-white border border-gray-200 rounded-2xl p-5;
}
.label-form {
  @apply block text-sm font-medium text-gray-700 mb-1;
}
.input-field {
  @apply w-full h-10 px-3 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-800
         focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all;
}
.quill-wrapper :deep(.ql-container) {
  @apply border-gray-200 rounded-b-lg;
}
.quill-wrapper :deep(.ql-toolbar) {
  @apply border-gray-200 rounded-t-lg bg-gray-50;
}
</style>
