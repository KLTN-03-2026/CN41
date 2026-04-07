<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Danh mục</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Quản lý danh mục khóa học</p>
      </div>
      <button @click="openCreate" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors">
        <PlusIcon class="w-4 h-4" />
        Thêm danh mục
      </button>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-white/5 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-white/5">
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Tên</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Slug</th>
              <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 px-6 py-3">Cấp</th>
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
            <tr v-else-if="!categories.length">
              <td colspan="5" class="text-center py-10 text-gray-400 text-sm">Chưa có danh mục nào</td>
            </tr>
            <tr
              v-for="cat in categories"
              :key="cat.id"
              class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
            >
              <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-200">
                <span class="flex items-center" :style="{ paddingLeft: cat.depth * 20 + 'px' }">
                  <span v-if="cat.depth > 0" class="text-gray-300 dark:text-gray-600 mr-1 text-base leading-none">└</span>
                  {{ cat.name }}
                </span>
              </td>
              <td class="px-6 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">{{ cat.slug }}</td>
              <td class="px-6 py-3 text-gray-500 dark:text-gray-400">
                {{ cat.is_root ? 'Gốc' : `Cấp ${cat.depth}` }}
              </td>
              <td class="px-6 py-3">
                <span
                  :class="cat.status === 1
                    ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                    : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ cat.status === 1 ? 'Hoạt động' : 'Ẩn' }}
                </span>
              </td>
              <td class="px-6 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="openEdit(cat)"
                    class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10 transition-colors"
                    title="Chỉnh sửa"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                  </button>
                  <button
                    @click="confirmDelete(cat)"
                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10 transition-colors"
                    title="Xóa"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination && pagination.last_page > 1"
        class="flex items-center justify-between px-6 py-3 border-t border-gray-100 dark:border-gray-700"
      >
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ pagination.from }}–{{ pagination.to }} / {{ pagination.total }} danh mục
        </p>
        <div class="flex gap-1">
          <button
            v-for="p in pagination.last_page"
            :key="p"
            @click="fetchPage(p)"
            :class="p === pagination.current_page
              ? 'bg-blue-500 text-white border-blue-500'
              : 'bg-white text-gray-600 dark:bg-white/5 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/10'"
            class="w-8 h-8 rounded-lg text-sm border border-gray-200 dark:border-gray-700 transition-colors"
          >
            {{ p }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Create/Edit -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        @click.self="closeModal"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md p-6">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-5">
            {{ editingId ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' }}
          </h3>

          <form @submit.prevent="submitForm" class="space-y-4">
            <div>
              <label class="label-form">Tên danh mục <span class="text-red-500">*</span></label>
              <input
                v-model="form.name"
                type="text"
                class="input-field"
                :class="{ 'input-error': formErrors.name }"
                placeholder="Lập trình"
                @input="autoSlug"
              />
              <p v-if="formErrors.name" class="error-msg">{{ formErrors.name }}</p>
            </div>

            <div>
              <label class="label-form">Slug <span class="text-red-500">*</span></label>
              <input
                v-model="form.slug"
                type="text"
                class="input-field font-mono text-sm"
                :class="{ 'input-error': formErrors.slug }"
                placeholder="lap-trinh"
              />
              <p v-if="formErrors.slug" class="error-msg">{{ formErrors.slug }}</p>
            </div>

            <div>
              <label class="label-form">Danh mục cha</label>
              <select v-model="form.parent_id" class="input-field">
                <option :value="null">— Không có (danh mục gốc) —</option>
                <option
                  v-for="item in flatTree"
                  :key="item.id"
                  :value="item.id"
                  :disabled="item.id === editingId"
                >
                  {{ '—'.repeat(item.depth) }} {{ item.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="label-form">Mô tả</label>
              <textarea
                v-model="form.description"
                rows="2"
                class="input-field resize-none"
                placeholder="Mô tả ngắn..."
              />
            </div>

            <div>
              <label class="label-form">Trạng thái</label>
              <select v-model="form.status" class="input-field">
                <option :value="1">Hoạt động</option>
                <option :value="0">Ẩn</option>
              </select>
            </div>

            <p v-if="submitError" class="text-sm text-red-500">{{ submitError }}</p>

            <div class="flex justify-end gap-3 pt-2">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5"
              >
                Hủy
              </button>
              <button
                type="submit"
                :disabled="submitting"
                class="px-4 py-2 text-sm rounded-lg bg-blue-500 text-white hover:bg-blue-600 disabled:opacity-50 flex items-center gap-2"
              >
                <svg v-if="submitting" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ editingId ? 'Cập nhật' : 'Tạo mới' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Confirm Delete -->
    <Teleport to="body">
      <div
        v-if="deleteTarget"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        @click.self="deleteTarget = null"
      >
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-sm p-6">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 mb-2">Xác nhận xóa</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Bạn có chắc muốn xóa danh mục
            <strong class="text-gray-800 dark:text-white/90">{{ deleteTarget.name }}</strong>?
            Các danh mục con cũng sẽ bị xóa.
          </p>
          <div class="flex justify-end gap-3">
            <button
              @click="deleteTarget = null"
              class="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400"
            >
              Hủy
            </button>
            <button
              @click="doDelete"
              :disabled="deleting"
              class="px-4 py-2 text-sm rounded-lg bg-red-500 text-white hover:bg-red-600 disabled:opacity-50"
            >
              {{ deleting ? 'Đang xóa...' : 'Xóa' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { PlusIcon, TrashIcon } from '@/icons'
import { categoriesApi } from '@/api/categoriesApi'

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
}

const categories  = ref<Category[]>([])
const flatTree    = ref<{ id: number; name: string; depth: number }[]>([])
const pagination  = ref<any>(null)
const loading     = ref(true)
const currentPage = ref(1)

const showModal   = ref(false)
const editingId   = ref<number | null>(null)
const submitting  = ref(false)
const submitError = ref('')
const formErrors  = ref<Record<string, string>>({})

const defaultForm = () => ({
  name: '',
  slug: '',
  description: '',
  status: 1,
  parent_id: null as number | null,
})
const form = ref(defaultForm())

const deleteTarget = ref<Category | null>(null)
const deleting     = ref(false)

async function fetchPage(page = 1) {
  loading.value = true
  currentPage.value = page
  try {
    const res = await categoriesApi.index({ page, per_page: 20 })
    categories.value = res.data.data
    pagination.value = res.data.pagination
  } catch {
    toast.error('Không thể tải danh mục')
  } finally {
    loading.value = false
  }
}

async function fetchFlatTree() {
  try {
    const res = await categoriesApi.flatTree()
    flatTree.value = res.data.data
  } catch {}
}

onMounted(() => {
  fetchPage()
  fetchFlatTree()
})

function autoSlug() {
  if (editingId.value) return
  form.value.slug = form.value.name
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[đĐ]/g, 'd')
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-')
}

function openCreate() {
  editingId.value = null
  form.value = defaultForm()
  formErrors.value = {}
  submitError.value = ''
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
  formErrors.value = {}
  submitError.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

async function submitForm() {
  formErrors.value = {}
  submitError.value = ''
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
      await categoriesApi.update(editingId.value, payload)
      toast.success('Cập nhật danh mục thành công')
    } else {
      await categoriesApi.store(payload)
      toast.success('Tạo danh mục thành công')
    }
    closeModal()
    fetchPage(currentPage.value)
    fetchFlatTree()
  } catch (err: any) {
    const data = err.response?.data
    if (err.response?.status === 422 && data?.errors) {
      for (const [key, msgs] of Object.entries(data.errors as Record<string, string[]>)) {
        formErrors.value[key] = msgs[0]
      }
    } else {
      submitError.value = data?.message || 'Có lỗi xảy ra, vui lòng thử lại'
    }
  } finally {
    submitting.value = false
  }
}

function confirmDelete(cat: Category) {
  deleteTarget.value = cat
}

async function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await categoriesApi.destroy(deleteTarget.value.id)
    toast.success('Xóa danh mục thành công')
    deleteTarget.value = null
    fetchPage(currentPage.value)
    fetchFlatTree()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Xóa thất bại')
  } finally {
    deleting.value = false
  }
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
