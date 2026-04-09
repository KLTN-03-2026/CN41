export const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(date)
}

export const formatDatetime = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('vi-VN', { 
    hour: '2-digit', minute: '2-digit', 
    day: '2-digit', month: '2-digit', year: 'numeric' 
  }).format(date)
}

export const timeAgo = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const now = new Date()
  const seconds = Math.floor((now - date) / 1000)
  
  let interval = seconds / 31536000
  if (interval > 1) return Math.floor(interval) + " năm trước"
  interval = seconds / 2592000
  if (interval > 1) return Math.floor(interval) + " tháng trước"
  interval = seconds / 86400
  if (interval > 1) return Math.floor(interval) + " ngày trước"
  interval = seconds / 3600
  if (interval > 1) return Math.floor(interval) + " giờ trước"
  interval = seconds / 60
  if (interval > 1) return Math.floor(interval) + " phút trước"
  
  return "Vài giây trước"
}
