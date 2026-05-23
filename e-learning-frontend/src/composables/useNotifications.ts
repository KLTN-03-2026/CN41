import { onUnmounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { createEcho, setEcho } from '@/plugins/echo'
import { notificationService } from '@/services/notification.service'

export interface AppNotification {
  id: number
  type: string
  title: string
  body: string
  data: Record<string, unknown> | null
  read_at: string | null
  created_at: string
}

const NOTIFICATION_ICONS: Record<string, string> = {
  enrollment: '🎓',
  payout_request: '💰',
  payout_decision: '✅',
  course_pending: '📚',
  new_comment: '💬',
}

export function useNotifications(guard: 'admin' | 'teacher') {
  const toast = useToast()
  const notifications = ref<AppNotification[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)

  async function fetchNotifications() {
    loading.value = true
    try {
      const res =
        guard === 'admin'
          ? await notificationService.getAdminNotifications()
          : await notificationService.getTeacherNotifications()
      notifications.value = res.data.data?.notifications ?? []
      unreadCount.value = res.data.data?.unread_count ?? 0
    } catch {
      // silently fail — notifications are non-critical
    } finally {
      loading.value = false
    }
  }

  async function markRead(id: number) {
    try {
      if (guard === 'admin') {
        await notificationService.markAdminRead(id)
      } else {
        await notificationService.markTeacherRead(id)
      }
      const n = notifications.value.find((n) => n.id === id)
      if (n && !n.read_at) {
        n.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (e) {
      void e
    }
  }

  async function markAllRead() {
    try {
      if (guard === 'admin') {
        await notificationService.markAdminAllRead()
      } else {
        await notificationService.markTeacherAllRead()
      }
      notifications.value.forEach((n) => {
        if (!n.read_at) n.read_at = new Date().toISOString()
      })
      unreadCount.value = 0
    } catch (e) {
      void e
    }
  }

  function connectEcho(token: string, recipientId: number) {
    const echo = createEcho(token)
    setEcho(echo)

    const channelName = guard === 'admin' ? `admin.${recipientId}` : `teacher.${recipientId}`

    echo.private(channelName).listen('.NewNotification', (event: AppNotification) => {
      notifications.value.unshift(event)
      unreadCount.value += 1

      const icon = NOTIFICATION_ICONS[event.type] ?? '🔔'
      toast.info(`${icon} ${event.title}: ${event.body}`, { timeout: 5000 })
    })

    onUnmounted(() => {
      echo.leave(channelName)
      setEcho(null)
    })
  }

  return {
    notifications,
    unreadCount,
    loading,
    fetchNotifications,
    markRead,
    markAllRead,
    connectEcho,
  }
}
