<template>
  <div v-if="url" class="video-wrapper">
    <video
      ref="videoEl"
      :src="url"
      controls
      class="video-player"
      @timeupdate="onTimeUpdate"
      @ended="onVideoEnded"
    ></video>
  </div>
  <div v-else class="video-placeholder">
    <svg class="w-16 h-16 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
    </svg>
    <p>Video không khả dụng</p>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
  url?: string;
  watchedSeconds?: number;
}>()

const emit = defineEmits<{
  (e: 'timeupdate', currentTime: number): void
  (e: 'ended'): void
}>()

const videoEl = ref<HTMLVideoElement | null>(null)

watch(() => props.watchedSeconds, (val) => {
  if (videoEl.value && val !== undefined) {
    if (Math.abs(videoEl.value.currentTime - val) > 1) {
      videoEl.value.currentTime = val
    }
  }
}, { immediate: true })

function onTimeUpdate() {
  if (!videoEl.value) return
  emit('timeupdate', Math.floor(videoEl.value.currentTime))
}

function onVideoEnded() {
  emit('ended')
}

defineExpose({
  videoElement: videoEl
})
</script>

<style scoped>
/* NOTE: The CSS for video-wrapper, video-player, video-placeholder
   will be moved here from LearnPage.vue */
</style>
