export const formatDuration = (minutes) => {
  if (!minutes) return '0 phút'
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  if (h > 0) {
    return m > 0 ? `${h} giờ ${m} phút` : `${h} giờ`
  }
  return `${m} phút`
}

export const formatSeconds = (totalSeconds) => {
  if (!totalSeconds) return '00:00'
  const h = Math.floor(totalSeconds / 3600)
  const m = Math.floor((totalSeconds % 3600) / 60)
  const s = totalSeconds % 60
  const pad = (num) => num.toString().padStart(2, '0')
  
  if (h > 0) {
    return `${h}:${pad(m)}:${pad(s)}`
  }
  return `${pad(m)}:${pad(s)}`
}
