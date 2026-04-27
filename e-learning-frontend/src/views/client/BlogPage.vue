<template>
  <div class="min-h-screen bg-gray-50/50 pb-20">
    <!-- Hero Section -->
    <div class="bg-white border-b border-gray-100 mb-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
        <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight sm:text-5xl">
          Tin tức & Kiến thức
        </h1>
        <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto">
          Cập nhật những thông tin mới nhất về công nghệ, lập trình và các khóa học tại E-Learning.
        </p>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col lg:flex-row gap-12">
        <!-- Main Content: Blog List -->
        <div class="flex-1">
          <!-- Filters & Search -->
          <div class="flex flex-col sm:flex-row gap-4 mb-8">
            <div class="relative flex-1">
              <input
                v-model="search"
                @input="debouncedFetch"
                type="text"
                placeholder="Tìm kiếm bài viết..."
                class="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
              />
              <svg
                class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
              </svg>
            </div>
            <select
              v-model="categoryFilter"
              @change="fetchPosts(1)"
              class="px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all bg-white text-gray-700 min-w-[200px]"
            >
              <option value="">Tất cả danh mục</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <!-- List -->
          <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div
              v-for="i in 4"
              :key="i"
              class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm animate-pulse"
            >
              <div class="aspect-video bg-gray-100"></div>
              <div class="p-6 space-y-4">
                <div class="h-4 w-24 bg-gray-100 rounded"></div>
                <div class="h-6 w-full bg-gray-100 rounded"></div>
                <div class="h-4 w-2/3 bg-gray-100 rounded"></div>
              </div>
            </div>
          </div>

          <div v-else-if="posts.length" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <article
              v-for="post in posts"
              :key="post.id"
              class="group bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
            >
              <router-link :to="`/posts/${post.slug}`" class="block">
                <div class="aspect-video overflow-hidden">
                  <img
                    :src="post.thumbnail || '/images/placeholder.webp'"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                </div>
                <div class="p-6">
                  <div class="flex items-center gap-3 mb-3">
                    <span
                      class="px-3 py-1 bg-blue-50 text-blue-600 text-xs font-semibold rounded-full uppercase tracking-wider"
                    >
                      {{ post.category?.name || 'Chung' }}
                    </span>
                    <span class="text-xs text-gray-400 font-medium">{{
                      formatDate(post.created_at)
                    }}</span>
                  </div>
                  <h3
                    class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-2 leading-tight mb-3"
                  >
                    {{ post.title }}
                  </h3>
                  <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-50">
                    <div class="flex items-center gap-2">
                      <div
                        class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500 uppercase"
                      >
                        {{ post.author?.name?.charAt(0) || 'A' }}
                      </div>
                      <span class="text-sm font-medium text-gray-600">{{ post.author?.name }}</span>
                    </div>
                    <div class="flex items-center gap-1 text-gray-400">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        />
                      </svg>
                      <span class="text-xs">{{ post.views || 0 }}</span>
                    </div>
                  </div>
                </div>
              </router-link>
            </article>
          </div>

          <div
            v-else
            class="text-center py-20 bg-white rounded-3xl border border-dashed border-gray-200"
          >
            <p class="text-gray-400">Không tìm thấy bài viết nào.</p>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="mt-12 flex justify-center">
            <PaginationBar
              :current-page="pagination.current_page"
              :last-page="pagination.last_page"
              @change="fetchPosts"
            />
          </div>
        </div>

        <!-- Sidebar -->
        <aside class="w-full lg:w-80 space-y-8">
          <!-- Categories -->
          <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-6">Danh mục</h3>
            <ul class="space-y-3">
              <li v-for="cat in categories" :key="cat.id">
                <button
                  @click="
                    categoryFilter = categoryFilter === cat.id ? '' : cat.id
                    fetchPosts(1)
                  "
                  class="w-full text-left px-4 py-2 rounded-xl text-sm transition-all flex items-center justify-between"
                  :class="
                    categoryFilter === cat.id
                      ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/20'
                      : 'text-gray-600 hover:bg-gray-50'
                  "
                >
                  {{ cat.name }}
                  <span
                    class="text-xs"
                    :class="categoryFilter === cat.id ? 'text-blue-100' : 'text-gray-400'"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"
                      />
                    </svg>
                  </span>
                </button>
              </li>
            </ul>
          </div>

          <!-- Top Tags -->
          <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-6">Thẻ phổ biến</h3>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="tag in tags"
                :key="tag.id"
                class="px-3 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 text-xs font-medium rounded-lg cursor-pointer transition-colors"
              >
                #{{ tag.name }}
              </span>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import PostService from '@/services/post.service'
import { formatDate } from '@/utils/formatDate'
import PaginationBar from '@/components/common/PaginationBar.vue'
import type { Post, PostCategory, Tag } from '@/types/post.types'

const posts = ref<Post[]>([])
const categories = ref<PostCategory[]>([])
const tags = ref<Tag[]>([])
const loading = ref(true)
const search = ref('')
const categoryFilter = ref('')
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
})

async function fetchPosts(page = 1) {
  loading.value = true
  try {
    const res = await PostService.getClientPosts({
      page,
      per_page: pagination.per_page,
      search: search.value || undefined,
      category_id: categoryFilter.value || undefined,
    })
    posts.value = res.data.data
    Object.assign(pagination, res.data.pagination)
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

async function fetchSidebarData() {
  try {
    const [catRes, tagRes] = await Promise.all([
      PostService.getClientCategories(),
      PostService.getClientTags(),
    ])
    categories.value = catRes.data.data
    tags.value = tagRes.data.data
  } catch (err) {
    console.error(err)
  }
}

let debounceTimer: any
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchPosts(1)
  }, 400)
}

onMounted(() => {
  fetchPosts()
  fetchSidebarData()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
