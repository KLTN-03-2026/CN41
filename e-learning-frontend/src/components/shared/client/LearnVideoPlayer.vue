<template>
  <div v-if="url" ref="wrapperEl" class="video-wrapper" :class="{ 'is-fullscreen': isFullscreen }" @contextmenu.prevent>
    <video
      ref="videoEl"
      :src="isHls ? undefined : url"
      controls
      controlsList="nodownload noremoteplayback"
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
  color: 'rgba(255, 255, 255, 0.5)',
  fontSize: '15px',
  fontWeight: '600',
  fontFamily: 'monospace',
  pointerEvents: 'none' as const,
  userSelect: 'none' as const,
  textShadow: '0 1px 4px rgba(0,0,0,0.9), 0 0 2px rgba(0,0,0,0.8)',
  zIndex: 10,
  whiteSpace: 'nowrap' as const,
  transition: 'left 1s ease, top 1s ease',
}))

function moveWatermark() {
  watermarkX.value = Math.floor(Math.random() * 65) + 5   // 5–70 %
  watermarkY.value = Math.floor(Math.random() * 75) + 5   // 5–80 %
}

function onFullscreenChange() {
  const fsEl = document.fullscreenElement ?? (document as any).webkitFullscreenElement
  isFullscreen.value = !!fsEl

  if (fsEl && wrapperEl.value) {
    wrapperEl.value.style.width = '100vw'
    wrapperEl.value.style.height = '100vh'
    wrapperEl.value.style.display = 'flex'
    wrapperEl.value.style.alignItems = 'center'
    wrapperEl.value.style.justifyContent = 'center'
    wrapperEl.value.style.background = '#000'
    if (videoEl.value) {
      videoEl.value.style.width = '100vw'
      videoEl.value.style.height = '100vh'
      videoEl.value.style.maxHeight = '100vh'
      videoEl.value.style.objectFit = 'contain'
    }
  } else if (wrapperEl.value) {
    wrapperEl.value.style.width = ''
    wrapperEl.value.style.height = ''
    wrapperEl.value.style.display = ''
    wrapperEl.value.style.alignItems = ''
    wrapperEl.value.style.justifyContent = ''
    wrapperEl.value.style.background = ''
    if (videoEl.value) {
      videoEl.value.style.width = ''
      videoEl.value.style.height = ''
      videoEl.value.style.maxHeight = ''
      videoEl.value.style.objectFit = ''
    }
  }
}

function toggleFullscreen() {
  if (!isFullscreen.value) {
    if (wrapperEl.value?.requestFullscreen) {
      wrapperEl.value.requestFullscreen().catch(() => {
        ;(wrapperEl.value as any)?.webkitRequestFullscreen?.()
      })
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

.video-player::-webkit-media-controls-fullscreen-button {
  display: none !important;
}

.logo-watermark {
  position: absolute;
  top: 20px;
  left: 20px;
  width: 120px;
  opacity: 0.65;
  pointer-events: none;
  user-select: none;
  z-index: 10;
  filter: brightness(10) drop-shadow(0 2px 4px rgba(0,0,0,0.5));
}

.fullscreen-btn {
  position: absolute;
  bottom: 60px; /* Nằm ngay trên thanh control mặc định */
  right: 16px;
  z-index: 20;
  background: rgba(0, 0, 0, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  padding: 8px;
  cursor: pointer;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: all 0.2s ease-in-out;
  backdrop-filter: blur(4px);
}

.fullscreen-btn:hover {
  background: rgba(0, 0, 0, 0.8);
  transform: scale(1.05);
}

.video-wrapper:hover .fullscreen-btn {
  opacity: 1;
}

/* :global() bắt buộc vì Vue scoped thêm [data-v-xxx] vào selector,
   nhưng browser không cho attribute selector kết hợp với :fullscreen */
:global(.video-wrapper:fullscreen) {
  width: 100vw !important;
  height: 100vh !important;
  background: #000;
  display: flex !important;
  align-items: center;
  justify-content: center;
}

:global(.video-wrapper:fullscreen) .video-player {
  width: 100vw !important;
  height: 100vh !important;
  object-fit: contain;
}

:global(.video-wrapper:-webkit-full-screen) {
  width: 100vw !important;
  height: 100vh !important;
  background: #000;
  display: flex !important;
  align-items: center;
  justify-content: center;
}

:global(.video-wrapper:-webkit-full-screen) .video-player {
  width: 100vw !important;
  height: 100vh !important;
  object-fit: contain;
}
</style>
