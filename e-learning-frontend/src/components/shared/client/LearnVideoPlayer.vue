<template>
  <div v-if="url" class="video-wrapper" style="position: relative; overflow: hidden">
    <video
      ref="videoEl"
      :src="isHls ? undefined : url"
      controls
      class="video-player"
      @loadedmetadata="onLoadedMetadata"
      @timeupdate="onTimeUpdate"
      @ended="onVideoEnded"
    ></video>

    <div
      v-if="watermarkText"
      :style="watermarkStyle"
      aria-hidden="true"
    >
      {{ watermarkText }}
    </div>
  </div>
  <div v-else class="video-placeholder">
    <svg class="w-16 h-16 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
    </svg>
    <p>Video không khả dụng</p>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted, nextTick } from 'vue'
import Hls from 'hls.js'
import { useStudentAuthStore } from '@/stores/studentAuth.store'

const props = defineProps<{
  url?: string
  watchedSeconds?: number
  watermarkText?: string
}>()

const emit = defineEmits<{
  timeupdate: [currentTime: number]
  ended: []
}>()

const videoEl = ref<HTMLVideoElement | null>(null)
const watermarkX = ref(10)
const watermarkY = ref(10)
let wmTimer: ReturnType<typeof setInterval> | null = null
let hlsInstance: Hls | null = null
let positionRestored = false

const authStore = useStudentAuthStore()

const isHls = computed(() => !!props.url?.endsWith('.m3u8'))

const watermarkStyle = computed(() => ({
  position: 'absolute' as const,
  left: `${watermarkX.value}%`,
  top: `${watermarkY.value}%`,
  color: 'rgba(255, 255, 255, 0.28)',
  fontSize: '13px',
  fontFamily: 'monospace',
  pointerEvents: 'none' as const,
  userSelect: 'none' as const,
  textShadow: '0 1px 3px rgba(0,0,0,0.7)',
  zIndex: 10,
  whiteSpace: 'nowrap' as const,
  transition: 'left 1s ease, top 1s ease',
}))

function moveWatermark() {
  watermarkX.value = Math.floor(Math.random() * 65) + 5   // 5–70 %
  watermarkY.value = Math.floor(Math.random() * 75) + 5   // 5–80 %
}

onMounted(() => {
  if (props.watermarkText) {
    wmTimer = setInterval(moveWatermark, 30_000)
  }
})

onUnmounted(() => {
  if (wmTimer) clearInterval(wmTimer)
  if (hlsInstance) hlsInstance.destroy()
})

function initHls() {
  if (!props.url || !videoEl.value) return

  if (hlsInstance) {
    hlsInstance.destroy()
    hlsInstance = null
  }

  if (isHls.value && Hls.isSupported()) {
    hlsInstance = new Hls({
      xhrSetup: (xhr, url) => {
        if (url.includes('hls-key') && authStore.token) {
          const sep = url.includes('?') ? '&' : '?'
          xhr.open('GET', url + sep + 'token=' + authStore.token)
        }
      }
    })
    hlsInstance.once(Hls.Events.MANIFEST_PARSED, () => {
      if (!positionRestored && props.watchedSeconds && props.watchedSeconds > 0 && videoEl.value) {
        videoEl.value.currentTime = props.watchedSeconds
        positionRestored = true
      }
    })
    hlsInstance.loadSource(props.url)
    hlsInstance.attachMedia(videoEl.value)
  } else if (videoEl.value.canPlayType('application/vnd.apple.mpegurl')) {
    // Native Safari support
    videoEl.value.src = props.url
  }
}

watch(() => props.url, async () => {
  positionRestored = false
  await nextTick()
  initHls()
}, { immediate: true })

function onLoadedMetadata() {
  if (!positionRestored && props.watchedSeconds && props.watchedSeconds > 0 && videoEl.value) {
    videoEl.value.currentTime = props.watchedSeconds
    positionRestored = true
  }
}

function onTimeUpdate() {
  if (!videoEl.value) return
  emit('timeupdate', Math.floor(videoEl.value.currentTime))
}

function onVideoEnded() {
  emit('ended')
}

defineExpose({ videoElement: videoEl })
</script>

