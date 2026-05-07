<template>
  <section class="py-14 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-end justify-between mb-8">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Bài viết mới nhất</h2>
          <p class="text-gray-500 mt-1 text-sm">Kiến thức và xu hướng công nghệ mới nhất</p>
        </div>
        <router-link
          to="/posts"
          class="text-primary-600 hover:text-primary-700 text-sm font-medium hidden sm:block"
        >
          Xem tất cả →
        </router-link>
      </div>

      <!-- Skeleton -->
      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div
          v-for="i in 3"
          :key="i"
          class="animate-pulse rounded-2xl overflow-hidden border border-gray-100"
        >
          <div class="h-48 bg-gray-100"></div>
          <div class="p-5 space-y-2">
            <div class="h-3 bg-gray-100 rounded w-1/3"></div>
            <div class="h-4 bg-gray-100 rounded w-full"></div>
            <div class="h-4 bg-gray-100 rounded w-3/4"></div>
            <div class="h-3 bg-gray-100 rounded w-1/2 mt-3"></div>
          </div>
        </div>
      </div>

      <!-- Cards -->
      <div v-else-if="posts.length" class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <router-link
          v-for="post in posts"
          :key="post.id"
          :to="`/posts/${post.slug}`"
          class="group rounded-2xl overflow-hidden border border-gray-100 hover:shadow-lg transition-shadow flex flex-col bg-white"
        >
          <!-- Thumbnail -->
          <div class="h-48 overflow-hidden shrink-0 bg-gradient-to-br from-gray-100 to-gray-200">
            <img
              v-if="post.thumbnail"
              :src="post.thumbnail"
              :alt="post.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
              <svg
                class="w-10 h-10 text-gray-300"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"
                />
              </svg>
            </div>
          </div>

          <div class="p-5 flex flex-col flex-1">
            <!-- Category -->
            <span v-if="post.category" class="text-xs font-medium text-primary-600 mb-2">
              {{ post.category.name }}
            </span>

            <h3
              class="font-semibold text-gray-900 leading-snug line-clamp-2 mb-2 group-hover:text-primary-600 transition-colors"
            >
              {{ post.title }}
            </h3>

            <div
              class="mt-auto flex items-center justify-between text-xs text-gray-400 pt-3 border-t border-gray-50"
            >
              <span>{{ post.author?.name || 'Tác giả' }}</span>
              <span>{{ formatDate(post.published_at || post.created_at) }}</span>
            </div>
          </div>
        </router-link>
      </div>

      <div v-else class="text-center py-12 text-gray-400 text-sm">Chưa có bài viết nào.</div>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { Post } from '@/services/post.service'

defineProps<{
  posts: Post[]
  loading: boolean
}>()

function formatDate(dateStr: string): string {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
</script>
