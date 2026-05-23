<template>
  <div class="min-h-screen bg-white pb-20">
    <div v-if="loading" class="max-w-4xl mx-auto px-4 py-20">
      <div class="animate-pulse space-y-8">
        <div class="h-10 w-3/4 bg-gray-100 rounded"></div>
        <div class="h-4 w-1/4 bg-gray-100 rounded"></div>
        <div class="aspect-video bg-gray-100 rounded-3xl"></div>
        <div class="space-y-4">
          <div class="h-4 w-full bg-gray-100 rounded"></div>
          <div class="h-4 w-full bg-gray-100 rounded"></div>
          <div class="h-4 w-2/3 bg-gray-100 rounded"></div>
        </div>
      </div>
    </div>

    <div v-else-if="post" class="relative">
      <!-- Breadcrumb & Title -->
      <div class="max-w-4xl mx-auto px-4 pt-12">
        <router-link
          to="/posts"
          class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-blue-600 transition-colors mb-8"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          Quay lại danh sách
        </router-link>

        <h1 class="text-3xl sm:text-5xl font-extrabold text-gray-900 leading-tight mb-6">
          {{ post.title }}
        </h1>

        <div class="flex flex-wrap items-center gap-6 pb-8 border-b border-gray-100 mb-12">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0">
              <img
                v-if="post.author?.avatar"
                :src="post.author.avatar"
                :alt="post.author.name"
                class="w-full h-full object-cover"
              />
              <div
                v-else
                class="w-full h-full bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-600 uppercase"
              >
                {{ post.author?.name?.charAt(0) || 'A' }}
              </div>
            </div>
            <div>
              <div class="text-sm font-bold text-gray-900">{{ post.author?.name }}</div>
              <div class="text-xs text-gray-500">Tác giả</div>
            </div>
          </div>

          <div class="flex items-center gap-2 text-gray-500">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
            <span class="text-sm font-medium">{{ formatDate(post.created_at) }}</span>
          </div>

          <div class="flex items-center gap-2 text-gray-500">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
            <span class="text-sm font-medium">{{ post.views || 0 }} lượt xem</span>
          </div>
        </div>
      </div>

      <!-- Feature Image -->
      <div v-if="post.thumbnail" class="max-w-6xl mx-auto px-4 mb-12">
        <img
          :src="post.thumbnail"
          class="w-full aspect-[21/9] object-cover rounded-3xl shadow-2xl shadow-blue-500/10"
        />
      </div>

      <!-- Content -->
      <div class="max-w-4xl mx-auto px-4">
        <div
          class="prose prose-lg prose-blue max-w-none text-gray-700 leading-relaxed quill-content"
          v-html="post.content"
        ></div>

        <!-- Tags -->
        <div
          v-if="post.tags?.length"
          class="mt-12 pt-8 border-t border-gray-100 flex flex-wrap gap-2"
        >
          <span
            v-for="tag in post.tags"
            :key="tag.id"
            class="px-4 py-2 bg-gray-50 text-gray-600 text-sm font-medium rounded-xl"
          >
            #{{ tag.name }}
          </span>
        </div>

        <!-- Comments Section -->
        <section class="mt-20">
          <h2 class="text-2xl font-bold text-gray-900 mb-8 flex items-center gap-3">
            Bình luận
            <span
              class="px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-500 text-sm font-medium"
              >{{ comments.length }}</span
            >
          </h2>

          <!-- Comment Form -->
          <div v-if="isLoggedIn" class="mb-12">
            <textarea
              v-model="commentContent"
              rows="4"
              placeholder="Chia sẻ suy nghĩ của bạn..."
              class="w-full px-5 py-4 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all resize-none text-gray-700 mb-4"
            ></textarea>
            <div class="flex justify-end">
              <button
                @click="submitComment"
                :disabled="!commentContent.trim() || submittingComment"
                class="px-8 py-3 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 disabled:opacity-50 transition-all shadow-lg shadow-blue-500/25"
              >
                {{ submittingComment ? 'Đang gửi...' : 'Gửi bình luận' }}
              </button>
            </div>
          </div>
          <div
            v-else
            class="mb-12 p-8 text-center bg-gray-50 rounded-3xl border border-dashed border-gray-200"
          >
            <p class="text-gray-500 mb-4">Bạn cần đăng nhập để gửi bình luận.</p>
            <router-link to="/login" class="text-blue-600 font-bold hover:underline"
              >Đăng nhập ngay</router-link
            >
          </div>

          <!-- Comments List -->
          <div class="space-y-8">
            <div
              v-for="comment in comments"
              :key="comment.id"
              class="flex gap-4 p-6 rounded-3xl hover:bg-gray-50 transition-colors"
            >
              <div
                class="w-12 h-12 rounded-full bg-gray-200 flex-shrink-0 flex items-center justify-center text-gray-500 font-bold uppercase overflow-hidden"
              >
                <img
                  v-if="comment.commenter?.avatar"
                  :src="comment.commenter.avatar"
                  class="w-full h-full object-cover"
                />
                <span v-else>{{ comment.commenter?.name?.charAt(0) || 'U' }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                  <h4 class="font-bold text-gray-900">{{ comment.commenter?.name }}</h4>
                  <span class="text-xs text-gray-400 font-medium">{{
                    formatDate(comment.created_at)
                  }}</span>
                </div>
                <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">
                  {{ comment.content }}
                </p>
              </div>
            </div>
            <p v-if="!comments.length" class="text-center text-gray-400 py-10">
              Chưa có bình luận nào. Hãy là người đầu tiên!
            </p>
          </div>
        </section>
      </div>
    </div>

    <div v-else class="text-center py-40">
      <h2 class="text-2xl font-bold text-gray-900 mb-4">Bài viết không tồn tại</h2>
      <router-link to="/posts" class="text-blue-600 hover:underline"
        >Quay lại trang tin tức</router-link
      >
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useStudentAuthStore } from '@/stores/studentAuth.store'
import PostService from '@/services/post.service'
import { formatDate } from '@/utils/formatDate'
import type { Post, PostComment } from '@/types/post.types'

const route = useRoute()
const toast = useToast()
const authStore = useStudentAuthStore()

const loading = ref(true)
const post = ref<Post | null>(null)
const comments = ref<PostComment[]>([])
const commentContent = ref('')
const submittingComment = ref(false)

const isLoggedIn = computed(() => authStore.isLoggedIn)

async function fetchPost() {
  loading.value = true
  try {
    const slug = route.params.slug as string
    const res = await PostService.getClientPost(slug)
    post.value = res.data.data
    comments.value = res.data.data.comments || []

    // Increment view
    if (post.value) {
      PostService.incrementViews(post.value.id).catch(() => {})
    }
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

async function submitComment() {
  if (!post.value || !commentContent.value.trim()) return

  submittingComment.value = true
  try {
    await PostService.storeComment(post.value.id, commentContent.value)
    toast.success('Bình luận đã được đăng thành công!')
    commentContent.value = ''
    // Reload bài viết để hiển thị bình luận mới
    await fetchPost()
  } catch (err) {
    const msg =
      (err as { response?: { data?: { message?: string } } }).response?.data?.message ||
      'Gửi bình luận thất bại'
    toast.error(msg)
  } finally {
    submittingComment.value = false
  }
}

onMounted(() => {
  fetchPost()
})
</script>

<style scoped>
.quill-content :deep(img) {
  @apply rounded-2xl max-w-full h-auto my-8 shadow-lg;
}
.quill-content :deep(h2) {
  @apply text-2xl font-bold text-gray-900 mt-12 mb-6;
}
.quill-content :deep(h3) {
  @apply text-xl font-bold text-gray-900 mt-8 mb-4;
}
.quill-content :deep(p) {
  @apply mb-6;
}
.quill-content :deep(ul),
.quill-content :deep(ol) {
  @apply ml-6 mb-6 space-y-2;
}
.quill-content :deep(li) {
  @apply list-disc;
}
.quill-content :deep(pre) {
  @apply bg-gray-900 text-gray-100 p-6 rounded-2xl overflow-x-auto my-8 font-mono text-sm;
}
.quill-content :deep(blockquote) {
  @apply border-l-4 border-blue-500 pl-6 italic text-gray-600 my-8;
}
</style>
