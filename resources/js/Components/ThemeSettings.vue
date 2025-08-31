<template>
  <div class="space-y-6">
    <!-- Theme Selection -->
    <div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
        <Palette class="w-5 h-5 mr-2 text-emerald-600" />
        Display Theme
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
        Choose how the interface looks and feels. Your preference will be remembered across sessions.
      </p>

      <!-- Theme Options -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div 
          v-for="(themeInfo, key) in themeStore.themes" 
          :key="key"
          @click="setTheme(key)"
          :class="[
            'relative rounded-lg border-2 p-4 cursor-pointer transition-all duration-200',
            themeStore.theme === key
              ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
              : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-800'
          ]"
        >
          <!-- Theme Preview -->
          <div class="mb-3">
            <div class="flex space-x-1 mb-2">
              <div 
                :class="[
                  'w-8 h-5 rounded-sm',
                  key === 'light' ? 'bg-white border border-gray-200' :
                  key === 'dark' ? 'bg-gray-900' :
                  'bg-gradient-to-r from-white to-gray-900'
                ]"
              ></div>
              <div 
                :class="[
                  'w-4 h-5 rounded-sm',
                  key === 'light' ? 'bg-gray-100' :
                  key === 'dark' ? 'bg-gray-700' :
                  'bg-gradient-to-r from-gray-100 to-gray-700'
                ]"
              ></div>
            </div>
          </div>

          <!-- Theme Info -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ themeInfo.name }}
              </h4>
              <div v-if="themeStore.theme === key" class="flex-shrink-0">
                <CheckCircle class="w-5 h-5 text-emerald-600" />
              </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ themeInfo.description }}
            </p>
          </div>

          <!-- System Theme Indicator -->
          <div v-if="key === 'system'" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Currently: <span class="capitalize font-medium">{{ themeStore.systemTheme }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Advanced Options -->
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
      <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
        <Settings class="w-4 h-4 mr-2 text-gray-600" />
        Advanced Options
      </h4>

      <div class="space-y-4">
        <!-- Auto Theme Toggle -->
        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-900 dark:text-gray-100">
              Quick Theme Toggle
            </label>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Use Ctrl+Shift+D to quickly toggle between light and dark themes
            </p>
          </div>
          <button
            @click="toggleQuickSwitch"
            :class="[
              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2',
              quickSwitchEnabled ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-600'
            ]"
          >
            <span
              :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                quickSwitchEnabled ? 'translate-x-6' : 'translate-x-1'
              ]"
            />
          </button>
        </div>

        <!-- Theme Sync Across Devices -->
        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-900 dark:text-gray-100">
              Sync Theme Preference
            </label>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Remember theme preference when switching between devices
            </p>
          </div>
          <button
            @click="toggleSync"
            :class="[
              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2',
              syncEnabled ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-600'
            ]"
          >
            <span
              :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                syncEnabled ? 'translate-x-6' : 'translate-x-1'
              ]"
            />
          </button>
        </div>

        <!-- Reduce Motion -->
        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-900 dark:text-gray-100">
              Reduce Animations
            </label>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Minimize motion effects for better accessibility
            </p>
          </div>
          <button
            @click="toggleReduceMotion"
            :class="[
              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2',
              reduceMotion ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-600'
            ]"
          >
            <span
              :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                reduceMotion ? 'translate-x-6' : 'translate-x-1'
              ]"
            />
          </button>
        </div>
      </div>
    </div>

    <!-- Current Theme Info -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
      <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
        Current Configuration
      </h4>
      <div class="text-xs space-y-1">
        <div class="flex justify-between">
          <span class="text-gray-600 dark:text-gray-400">Selected Theme:</span>
          <span class="font-medium text-gray-900 dark:text-gray-100 capitalize">{{ themeStore.theme }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600 dark:text-gray-400">Active Mode:</span>
          <span class="font-medium text-gray-900 dark:text-gray-100 capitalize">{{ themeStore.getEffectiveTheme() }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600 dark:text-gray-400">System Preference:</span>
          <span class="font-medium text-gray-900 dark:text-gray-100 capitalize">{{ themeStore.systemTheme }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600 dark:text-gray-400">Browser Support:</span>
          <span class="font-medium text-emerald-600">{{ browserSupport }}</span>
        </div>
      </div>
    </div>

    <!-- Reset Button -->
    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
      <button
        @click="resetToDefault"
        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        Reset to System Default
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useThemeStore } from '@/stores/theme'
import { Palette, CheckCircle, Settings } from 'lucide-vue-next'

const themeStore = useThemeStore()

// Advanced settings
const quickSwitchEnabled = ref(true)
const syncEnabled = ref(true)
const reduceMotion = ref(false)

// Methods
const setTheme = (themeKey) => {
  themeStore.setTheme(themeKey)
}

const toggleQuickSwitch = () => {
  quickSwitchEnabled.value = !quickSwitchEnabled.value
  localStorage.setItem('theme-quick-switch', quickSwitchEnabled.value.toString())
  
  if (quickSwitchEnabled.value) {
    setupKeyboardShortcut()
  } else {
    removeKeyboardShortcut()
  }
}

const toggleSync = () => {
  syncEnabled.value = !syncEnabled.value
  localStorage.setItem('theme-sync', syncEnabled.value.toString())
}

const toggleReduceMotion = () => {
  reduceMotion.value = !reduceMotion.value
  localStorage.setItem('reduce-motion', reduceMotion.value.toString())
  
  // Apply to CSS
  document.documentElement.style.setProperty(
    '--animation-duration', 
    reduceMotion.value ? '0.01ms' : '200ms'
  )
}

const resetToDefault = () => {
  themeStore.setTheme('system')
  quickSwitchEnabled.value = true
  syncEnabled.value = true
  reduceMotion.value = false
  
  // Clear localStorage
  localStorage.removeItem('theme-preference')
  localStorage.removeItem('theme-quick-switch')
  localStorage.removeItem('theme-sync')
  localStorage.removeItem('reduce-motion')
}

// Computed
const browserSupport = computed(() => {
  if (typeof window === 'undefined') return 'Unknown'
  return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches !== undefined 
    ? 'Full' 
    : 'Limited'
})

// Keyboard shortcut handling
const handleKeyboardShortcut = (event) => {
  if (event.ctrlKey && event.shiftKey && event.key.toLowerCase() === 'd') {
    event.preventDefault()
    themeStore.toggleTheme()
  }
}

const setupKeyboardShortcut = () => {
  document.addEventListener('keydown', handleKeyboardShortcut)
}

const removeKeyboardShortcut = () => {
  document.removeEventListener('keydown', handleKeyboardShortcut)
}

// Load settings
const loadSettings = () => {
  const quickSwitch = localStorage.getItem('theme-quick-switch')
  if (quickSwitch !== null) {
    quickSwitchEnabled.value = quickSwitch === 'true'
  }
  
  const sync = localStorage.getItem('theme-sync')
  if (sync !== null) {
    syncEnabled.value = sync === 'true'
  }
  
  const motion = localStorage.getItem('reduce-motion')
  if (motion !== null) {
    reduceMotion.value = motion === 'true'
  }
  
  // Apply reduce motion setting
  if (reduceMotion.value) {
    document.documentElement.style.setProperty('--animation-duration', '0.01ms')
  }
}

// Initialize
onMounted(() => {
  loadSettings()
  
  if (quickSwitchEnabled.value) {
    setupKeyboardShortcut()
  }
})

onUnmounted(() => {
  removeKeyboardShortcut()
})
</script>