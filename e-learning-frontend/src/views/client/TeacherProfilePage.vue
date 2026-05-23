<template>
  <div class="max-w-5xl mx-auto px-4 py-8">
    <!-- Loading -->
    <div v-if="loading" class="space-y-6">
      <div class="bg-white rounded-2xl border border-gray-100 p-8 flex gap-6 animate-pulse">
        <div class="w-24 h-24 rounded-full bg-gray-200 flex-shrink-0" />
        <div class="flex-1 space-y-3 py-1">
          <div class="h-5 bg-gray-200 rounded w-48" />
          <div class="h-3 bg-gray-100 rounded w-32" />
          <div class="h-3 bg-gray-100 rounded w-56" />
          <div class="h-3 bg-gray-100 rounded w-40" />
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="i in 3"
          :key="i"
          class="bg-white rounded-2xl border border-gray-100 overflow-hidden animate-pulse"
        >
          <div class="h-36 bg-gray-100" />
          <div class="p-4 space-y-2">
            <div class="h-4 bg-gray-200 rounded w-3/4" />
            <div class="h-3 bg-gray-100 rounded w-1/2" />
          </div>
        </div>
      </div>
    </div>

    <!-- Not found -->
    <div v-else-if="!teacher" class="text-center py-20">
      <p class="text-gray-500">Không tìm thấy giảng viên này</p>
      <router-link to="/teachers" class="mt-4 inline-block text-blue-500 hover:underline">
        Xem tất cả giảng viên
      </router-link>
    </div>

    <template v-else>
      <!-- Breadcrumb -->
      <nav class="text-sm text-gray-500 flex items-center gap-1 mb-6">
        <router-link to="/teachers" class="hover:text-blue-500">Giảng viên</router-link>
        <span>/</span>
        <span class="text-gray-800 truncate">{{ teacher.name }}</span>
      </nav>

      <!-- Profile card -->
      <div class="bg-white rounded-2xl border border-gray-100 p-6 sm:p-8 mb-8">
        <div class="flex flex-col sm:flex-row gap-6">
          <!-- Avatar -->
          <div class="w-24 h-24 rounded-full overflow-hidden flex-shrink-0 mx-auto sm:mx-0">
            <img
              v-if="teacher.image"
              :src="teacher.image"
              :alt="teacher.name"
              class="w-full h-full object-cover"
            />
            <div
              v-else
              class="w-full h-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-2xl"
            >
              {{ avatarInitial(teacher.name) }}
            </div>
          </div>

          <!-- Info -->
          <div class="flex-1 text-center sm:text-left">
            <h1 class="text-2xl font-bold text-gray-900">{{ teacher.name }}</h1>
            <p v-if="teacher.email" class="text-sm text-gray-400 mt-1">{{ teacher.email }}</p>

            <!-- Stats row -->
            <div class="flex flex-wrap justify-center sm:justify-start gap-4 mt-4">
              <div class="flex items-center gap-1.5 text-sm text-gray-600">
                <svg
                  class="w-4 h-4 text-blue-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                  />
                </svg>
                {{ courses.length }} khóa học
              </div>
              <div v-if="teacher.exp" class="flex items-center gap-1.5 text-sm text-gray-600">
                <svg
                  class="w-4 h-4 text-green-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                {{ teacher.exp }} năm kinh nghiệm
              </div>
            </div>

            <!-- Description -->
            <p v-if="teacher.description" class="mt-4 text-gray-600 text-sm leading-relaxed">
              {{ teacher.description }}
            </p>
          </div>
        </div>
      </div>

      <!-- Courses section -->
      <div class="mb-12">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Khóa học của {{ teacher.name }}
          <span class="text-sm font-normal text-gray-400 ml-1">({{ courses.length }})</span>
        </h2>

        <div
          v-if="!courses.length"
          class="text-center py-12 text-gray-400 bg-white rounded-2xl border border-gray-100"
        >
          Giảng viên chưa có khóa học nào.
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <router-link
            v-for="course in courses"
            :key="course.id"
            :to="`/courses/${course.slug}`"
            class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-200 hover:-translate-y-0.5 group"
          >
            <div class="relative h-40 bg-gray-100 overflow-hidden">
              <img
                v-if="course.thumbnail"
                :src="course.thumbnail"
                :alt="course.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
            </div>
            <div class="p-4">
              <h3 class="font-medium text-gray-900 text-sm line-clamp-2 leading-snug">
                {{ course.name }}
              </h3>
              <div class="mt-3 flex items-center justify-between">
                <span v-if="Number(course.sale_price) > 0" class="text-blue-600 font-semibold text-sm">
                  {{ formatPrice(course.sale_price) }}
                  <span class="text-xs text-gray-400 line-through ml-1">{{ formatPrice(course.price) }}</span>
                </span>
                <span v-else-if="Number(course.price) > 0" class="text-blue-600 font-semibold text-sm">
                  {{ formatPrice(course.price) }}
                </span>
                <span v-else class="text-green-600 font-semibold text-sm">Miễn phí</span>
              </div>
            </div>
          </router-link>
        </div>
      </div>

      <!-- Posts section -->
      <div v-if="posts.length || postsLoading">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Bài viết của {{ teacher.name }}
          <span class="text-sm font-normal text-gray-400 ml-1">({{ posts.length }})</span>
        </h2>

        <div v-if="postsLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="i in 3" :key="i" class="bg-white rounded-2xl border border-gray-100 overflow-hidden animate-pulse">
            <div class="h-36 bg-gray-100" />
            <div class="p-4 space-y-2">
              <div class="h-4 bg-gray-200 rounded w-3/4" />
              <div class="h-3 bg-gray-100 rounded w-1/2" />
            </div>
          </div>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <router-link
            v-for="post in posts"
            :key="post.id"
            :to="`/posts/${post.slug}`"
            class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-200 hover:-translate-y-0.5 group"
          >
            <div class="relative h-40 bg-gray-100 overflow-hidden">
              <img
                v-if="post.thumbnail"
                :src="post.thumbnail"
                :alt="post.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
            </div>
            <div class="p-4">
              <h3 class="font-medium text-gray-900 text-sm line-clamp-2 leading-snug">
                {{ post.title }}
              </h3>
              <p class="text-xs text-gray-400 mt-2">{{ formatDate(post.created_at) }}</p>
            </div>
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { teacherService } from '@/services/teacher.service'
import PostService from '@/services/post.service'
import { formatDate } from '@/utils/formatDate'
import type { Teacher } from '@/types/course.types'
import type { Post } from '@/types/post.types'

interface TeacherCourse {
  id: number
  name: string
  slug: string
  thumbnail?: string | null
  price: string | number
  sale_price?: string | number | null
}

interface TeacherDetail extends Teacher {
  user_id?: number
  courses?: TeacherCourse[]
}

const route = useRoute()
const teacher = ref<TeacherDetail | null>(null)
const courses = ref<TeacherCourse[]>([])
const posts = ref<Post[]>([])
const loading = ref(true)
const postsLoading = ref(false)

function avatarInitial(name: string): string {
  return name
    .split(' ')
    .map((w) => w[0])
    .slice(-2)
    .join('')
    .toUpperCase()
}

function formatPrice(value: string | number | null | undefined): string {
  const num = Number(value)
  if (!num) return '0₫'
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(num)
}

async function loadPosts(userId: number) {
  postsLoading.value = true
  try {
    const res = await PostService.getClientPosts({ author_id: userId, per_page: 6 })
    posts.value = res.data.data ?? []
  } catch {
    posts.value = []
  } finally {
    postsLoading.value = false
  }
}

async function load() {
  loading.value = true
  try {
    const slug = route.params.slug as string
    const res = await teacherService.publicShow(slug)
    teacher.value = res.data.data as TeacherDetail
    courses.value = teacher.value?.courses ?? []

    if (teacher.value?.user_id) {
      loadPosts(teacher.value.user_id)
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => load())
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
