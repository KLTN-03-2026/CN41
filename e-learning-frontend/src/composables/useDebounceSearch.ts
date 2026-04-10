import { ref, onUnmounted } from 'vue'

export function useDebounceSearch(onSearch: () => void, delay = 400) {
  const query = ref('')
  let timer: ReturnType<typeof setTimeout> | null = null

  function debounce() {
    if (timer) clearTimeout(timer)
    timer = setTimeout(onSearch, delay)
  }

  onUnmounted(() => {
    if (timer) clearTimeout(timer)
  })

  return { query, debounce }
}
