import { ref } from 'vue'
import { createEcho, getEcho } from '@/plugins/echo'

export function useHlsChannel() {
  const hlsStatus = ref<'idle' | 'processing' | 'ready' | 'failed'>('idle')

  function subscribeHls(mediaId: number): void {
    hlsStatus.value = 'processing'
    const token = localStorage.getItem('adminToken') ?? ''
    const echo = getEcho() ?? createEcho(token)

    echo.private(`hls.${mediaId}`).listen('.HlsProgress', (event: { status: string }) => {
      hlsStatus.value = event.status as typeof hlsStatus.value
      if (event.status === 'ready' || event.status === 'failed') {
        echo.leave(`hls.${mediaId}`)
      }
    })
  }

  function unsubscribeHls(mediaId: number): void {
    getEcho()?.leave(`hls.${mediaId}`)
    hlsStatus.value = 'idle'
  }

  return { hlsStatus, subscribeHls, unsubscribeHls }
}
