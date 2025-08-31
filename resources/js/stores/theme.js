import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  // Theme state
  const isDark = ref(false)
  const theme = ref('light')
  const systemTheme = ref('light')

  // Available themes
  const themes = {
    light: {
      name: 'Light',
      description: 'Clean and bright interface',
      primary: 'emerald',
      bg: 'white',
      text: 'gray-900',
      border: 'gray-200'
    },
    dark: {
      name: 'Dark',  
      description: 'Easy on the eyes in low light',
      primary: 'emerald',
      bg: 'gray-900',
      text: 'gray-100',
      border: 'gray-700'
    },
    system: {
      name: 'System',
      description: 'Follow system preference',
      primary: 'emerald',
      bg: 'auto',
      text: 'auto', 
      border: 'auto'
    }
  }

  // Detect system theme
  const detectSystemTheme = () => {
    if (typeof window !== 'undefined' && window.matchMedia) {
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }
    return 'light'
  }

  // Apply theme to HTML element
  const applyTheme = (themeMode) => {
    if (typeof document === 'undefined') return

    const html = document.documentElement
    
    // Remove existing theme classes
    html.classList.remove('light', 'dark')
    
    if (themeMode === 'system') {
      const systemPreference = detectSystemTheme()
      html.classList.add(systemPreference)
      isDark.value = systemPreference === 'dark'
    } else {
      html.classList.add(themeMode)
      isDark.value = themeMode === 'dark'
    }

    // Set CSS variables for dynamic theming
    const root = document.documentElement
    const currentTheme = themeMode === 'system' ? detectSystemTheme() : themeMode
    
    if (currentTheme === 'dark') {
      root.style.setProperty('--color-bg-primary', '#111827')
      root.style.setProperty('--color-bg-secondary', '#1f2937')
      root.style.setProperty('--color-bg-tertiary', '#374151')
      root.style.setProperty('--color-text-primary', '#f9fafb')
      root.style.setProperty('--color-text-secondary', '#d1d5db')
      root.style.setProperty('--color-text-tertiary', '#9ca3af')
      root.style.setProperty('--color-border-primary', '#4b5563')
      root.style.setProperty('--color-border-secondary', '#6b7280')
    } else {
      root.style.setProperty('--color-bg-primary', '#ffffff')
      root.style.setProperty('--color-bg-secondary', '#f9fafb')
      root.style.setProperty('--color-bg-tertiary', '#f3f4f6')
      root.style.setProperty('--color-text-primary', '#111827')
      root.style.setProperty('--color-text-secondary', '#374151')
      root.style.setProperty('--color-text-tertiary', '#6b7280')
      root.style.setProperty('--color-border-primary', '#e5e7eb')
      root.style.setProperty('--color-border-secondary', '#d1d5db')
    }
  }

  // Set theme
  const setTheme = (newTheme) => {
    theme.value = newTheme
    applyTheme(newTheme)
    
    // Save to localStorage
    localStorage.setItem('theme-preference', newTheme)
    
    // Dispatch custom event for components that need to react
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('theme-changed', { 
        detail: { 
          theme: newTheme, 
          isDark: isDark.value,
          actualTheme: newTheme === 'system' ? detectSystemTheme() : newTheme
        } 
      }))
    }
  }

  // Toggle between light and dark (skips system)
  const toggleTheme = () => {
    const newTheme = theme.value === 'dark' ? 'light' : 'dark'
    setTheme(newTheme)
  }

  // Load saved theme preference
  const loadTheme = () => {
    const saved = localStorage.getItem('theme-preference')
    if (saved && themes[saved]) {
      setTheme(saved)
    } else {
      setTheme('system') // Default to system preference
    }
  }

  // Initialize theme system
  const initialize = () => {
    // Detect system theme
    systemTheme.value = detectSystemTheme()
    
    // Listen for system theme changes
    if (typeof window !== 'undefined' && window.matchMedia) {
      const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
      mediaQuery.addEventListener('change', (e) => {
        systemTheme.value = e.matches ? 'dark' : 'light'
        if (theme.value === 'system') {
          applyTheme('system')
        }
      })
    }

    // Load saved theme or use system default
    loadTheme()
  }

  // Get current effective theme (resolves 'system' to actual theme)
  const getEffectiveTheme = () => {
    return theme.value === 'system' ? systemTheme.value : theme.value
  }

  // Theme-aware CSS classes helper
  const getThemeClasses = (lightClasses, darkClasses) => {
    return isDark.value ? darkClasses : lightClasses
  }

  // Get theme colors
  const getThemeColors = () => {
    const effectiveTheme = getEffectiveTheme()
    return {
      bg: {
        primary: isDark.value ? 'bg-gray-900' : 'bg-white',
        secondary: isDark.value ? 'bg-gray-800' : 'bg-gray-50',
        tertiary: isDark.value ? 'bg-gray-700' : 'bg-gray-100',
        card: isDark.value ? 'bg-gray-800' : 'bg-white',
        hover: isDark.value ? 'hover:bg-gray-700' : 'hover:bg-gray-50'
      },
      text: {
        primary: isDark.value ? 'text-gray-100' : 'text-gray-900',
        secondary: isDark.value ? 'text-gray-300' : 'text-gray-600', 
        tertiary: isDark.value ? 'text-gray-400' : 'text-gray-500',
        inverse: isDark.value ? 'text-gray-900' : 'text-gray-100'
      },
      border: {
        primary: isDark.value ? 'border-gray-700' : 'border-gray-200',
        secondary: isDark.value ? 'border-gray-600' : 'border-gray-300',
        light: isDark.value ? 'border-gray-800' : 'border-gray-100'
      },
      ring: {
        primary: isDark.value ? 'ring-gray-700' : 'ring-gray-200',
        focus: isDark.value ? 'focus:ring-emerald-400' : 'focus:ring-emerald-500'
      }
    }
  }

  return {
    // State
    isDark,
    theme,
    systemTheme,
    themes,
    
    // Actions
    setTheme,
    toggleTheme,
    loadTheme,
    initialize,
    
    // Getters
    getEffectiveTheme,
    getThemeClasses,
    getThemeColors
  }
})