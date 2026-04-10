import { ref, provide, inject, onMounted, watch, computed } from 'vue'
import type { ComputedRef } from 'vue'

interface ThemeContextType {
  isDarkMode: ComputedRef<boolean>
  toggleTheme: () => void
}

const ThemeSymbol = Symbol('theme')

type Theme = 'light' | 'dark'

export function useThemeProvider() {
  const theme = ref<Theme>('light')
  const isInitialized = ref(false)

  const isDarkMode = computed(() => theme.value === 'dark')

  const toggleTheme = () => {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
  }

  onMounted(() => {
    const savedTheme = localStorage.getItem('theme') as Theme | null
    theme.value = savedTheme || 'light'
    isInitialized.value = true
  })

  watch([theme, isInitialized], ([newTheme, initialized]) => {
    if (initialized) {
      localStorage.setItem('theme', newTheme)
      if (newTheme === 'dark') {
        document.documentElement.classList.add('dark')
      } else {
        document.documentElement.classList.remove('dark')
      }
    }
  })

  const context: ThemeContextType = {
    isDarkMode,
    toggleTheme,
  }

  provide(ThemeSymbol, context)

  return context
}

export function useTheme(): ThemeContextType {
  const context = inject<ThemeContextType>(ThemeSymbol)
  if (!context) {
    throw new Error('useTheme must be used within a ThemeProvider')
  }
  return context
}
