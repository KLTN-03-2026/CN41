<template>
  <div v-if="url" ref="wrapperEl" class="video-wrapper" @contextmenu.prevent>
    <video
      ref="videoEl"
      :src="isHls ? undefined : url"
      controls
      controlsList="nodownload nofullscreen noremoteplayback"
      disablePictureInPicture
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

    <img
      v-if="logoUrl"
      :src="logoUrl"
      aria-hidden="true"
      class="logo-watermark"
    />

    <button class="fullscreen-btn" @click="toggleFullscreen" :aria-label="isFullscreen ? 'Thoát toàn màn hình' : 'Toàn màn hình'">
      <svg v-if="!isFullscreen" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
        <path d="M3 3h6v2H5v4H3V3zm12 0h6v6h-2V5h-4V3zM3 15h2v4h4v2H3v-6zm14 4h-4v2h6v-6h-2v4z"/>
      </svg>
      <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
        <path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/>
      </svg>
    </button>
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
  logoUrl?: string
}>()

const emit = defineEmits<{
  timeupdate: [currentTime: number]
  ended: []
}>()

const videoEl = ref<HTMLVideoElement | null>(null)
const wrapperEl = ref<HTMLDivElement | null>(null)
const isFullscreen = ref(false)
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

function onFullscreenChange() {
  isFullscreen.value = !!(document.fullscreenElement ?? (document as any).webkitFullscreenElement)
}

function toggleFullscreen() {
  if (!isFullscreen.value) {
    if (wrapperEl.value?.requestFullscreen) {
      wrapperEl.value.requestFullscreen()
    } else {
      ;(wrapperEl.value as any)?.webkitRequestFullscreen?.()
    }
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen()
    } else {
      ;(document as any).webkitExitFullscreen?.()
    }
  }
}

onMounted(() => {
  if (props.watermarkText) {
    wmTimer = setInterval(moveWatermark, 30_000)
  }
  document.addEventListener('fullscreenchange', onFullscreenChange)
  document.addEventListener('webkitfullscreenchange', onFullscreenChange)
})

onUnmounted(() => {
  if (wmTimer) clearInterval(wmTimer)
  if (hlsInstance) hlsInstance.destroy()
  document.removeEventListener('fullscreenchange', onFullscreenChange)
  document.removeEventListener('webkitfullscreenchange', onFullscreenChange)
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

<style scoped>
.video-wrapper {
  position: relative;
  width: 100%;
  background: #000;
  overflow: hidden;
}

.video-player {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: contain;
}

.logo-watermark {
  position: absolute;
  bottom: 12px;
  right: 14px;
  width: 72px;
  opacity: 0.22;
  pointer-events: none;
  user-select: none;
  z-index: 10;
  filter: brightness(10);
}

.fullscreen-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 20;
  background: rgba(0, 0, 0, 0.45);
  border: none;
  border-radius: 4px;
  padding: 5px 6px;
  cursor: pointer;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s;
}

.video-wrapper:hover .fullscreen-btn {
  opacity: 1;
}

/* Tách thành 2 rule riêng — trình duyệt bỏ cả rule nếu gặp selector không hiểu */
.video-wrapper:fullscreen {
  width: 100vw;
  height: 100vh;
  background: #000;
}

.video-wrapper:-webkit-full-screen {
  width: 100vw;
  height: 100vh;
  background: #000;
}

.video-wrapper:fullscreen .video-player {
  width: 100%;
  height: 100vh;
  object-fit: contain;
}

.video-wrapper:-webkit-full-screen .video-player {
  width: 100%;
  height: 100vh;
  object-fit: contain;
}
</style>
