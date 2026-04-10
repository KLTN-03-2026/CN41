<template>
  <div
    class="doc-viewer-main"
    :class="{ 'doc-fullscreen': docFullscreen }"
  >
    <div class="doc-embed-wrapper">
      <!-- Toolbar -->
      <div class="doc-toolbar">
        <div class="doc-toolbar-left">
          <div class="doc-file-badge">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>{{ fileExtension }}</span>
          </div>
          <span class="doc-toolbar-filename">{{ title }}</span>
        </div>
        <div class="doc-toolbar-right">
          <a
            :href="url"
            download
            class="doc-tool-btn"
            title="Tải xuống"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
          </a>
          <a
            :href="url"
            target="_blank"
            class="doc-tool-btn"
            title="Mở trong tab mới"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
          </a>
          <button
            @click="docFullscreen = !docFullscreen"
            class="doc-tool-btn"
            :title="docFullscreen ? 'Thoát toàn màn hình' : 'Toàn màn hình'"
          >
            <svg v-if="!docFullscreen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/>
            </svg>
          </button>
        </div>
      </div>
      <!-- Embed frame -->
      <div class="doc-embed-frame">
        <!-- PDF -->
        <iframe
          v-if="isPdf"
          :src="url"
          class="doc-iframe"
          frameborder="0"
          allowfullscreen
        ></iframe>
        <!-- Ảnh -->
        <div v-else-if="isImage" class="doc-image-viewer">
          <img
            :src="url"
            :alt="title"
            class="doc-image"
          />
        </div>
        <!-- File khác -->
        <div v-else class="doc-file-card">
          <div class="doc-file-icon-large">
            <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="doc-ext-label">{{ fileExtension }}</span>
          </div>
          <p class="doc-file-name">{{ fileName }}</p>
          <p class="doc-file-hint">Định dạng này chưa hỗ trợ xem trực tiếp</p>
          <div class="doc-file-actions">
            <a :href="url" target="_blank" class="doc-action-btn primary">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
              </svg>
              Mở tài liệu
            </a>
            <a :href="url" download class="doc-action-btn secondary">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
              Tải xuống
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

const props = defineProps<{
  url: string
  title: string
}>()

const docFullscreen = ref(false)

const isPdf = computed(() => {
  if (!props.url) return false
  const lower = props.url.toLowerCase()
  return lower.endsWith('.pdf') || lower.includes('.pdf?')
})

const isImage = computed(() => {
  if (!props.url) return false
  const lower = props.url.toLowerCase()
  return /\.(jpg|jpeg|png|gif|webp|svg|bmp)(\?|$)/.test(lower)
})

const fileName = computed(() => {
  if (!props.url) return 'Tài liệu'
  try {
    const pathname = new URL(props.url, window.location.origin).pathname
    const name = pathname.split('/').pop() || 'Tài liệu'
    return decodeURIComponent(name)
  } catch {
    return 'Tài liệu'
  }
})

const fileExtension = computed(() => {
  if (!props.url) return 'FILE'
  try {
    const name = new URL(props.url, window.location.origin).pathname.split('/').pop() || ''
    const ext = name.split('.').pop()?.toUpperCase() || 'FILE'
    return ext
  } catch {
    return 'FILE'
  }
})
</script>

<style scoped>
/* NOTE: The CSS for these classes (doc-viewer-main, doc-toolbar, doc-embed-frame, etc.)
   will be moved here from the parent LearnPage.vue */
</style>
