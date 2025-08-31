<template>
  <!-- Modal Overlay -->
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
      <!-- Background overlay -->
      <div 
        class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-80" 
        @click="$emit('close')"
      ></div>

      <!-- Modal panel -->
      <div class="relative inline-block w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 my-8 text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <Bell class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notification Settings</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">Manage how and when you receive notifications</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span :class="[
              'px-2 py-1 text-xs rounded-full',
              hasUnsavedChanges 
                ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' 
                : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
            ]">
              {{ hasUnsavedChanges ? 'Unsaved changes' : 'All saved' }}
            </span>
            <button 
              @click="$emit('close')" 
              class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              <X class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Modal Content -->
        <div class="mt-4 space-y-8">
          <!-- Quick Actions -->
          <div class="flex items-center gap-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
            <Volume2 class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            <div class="flex-1">
              <h4 class="font-medium text-gray-900 dark:text-white">Master Sound Control</h4>
              <p class="text-sm text-gray-600 dark:text-gray-400">Enable or disable all notification sounds</p>
            </div>
            <Switch
              v-model="notificationStore.soundEnabled"
              @update:modelValue="toggleSound"
              class="flex-shrink-0"
            />
          </div>

          <!-- Notification Categories -->
          <div class="space-y-6">
            <div>
              <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <Settings class="w-4 h-4" />
                Notification Categories
              </h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Sales Notifications -->
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                      <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <ShoppingBag class="w-4 h-4 text-green-600 dark:text-green-400" />
                      </div>
                      <div>
                        <h5 class="font-medium text-gray-900 dark:text-white">Sales</h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Transaction completions, payments</p>
                      </div>
                    </div>
                    <Switch v-model="settings.categories.sales" @update:modelValue="saveSettings" />
                  </div>
                  <div class="ml-11 space-y-2">
                    <label class="flex items-center">
                      <input
                        type="checkbox"
                        v-model="settings.salesOptions.largeTransactions"
                        @change="saveSettings"
                        :disabled="!settings.categories.sales"
                        class="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
                      />
                      <span class="ml-2 text-xs text-gray-600 dark:text-gray-400">Large transactions (>RM 1000)</span>
                    </label>
                    <label class="flex items-center">
                      <input
                        type="checkbox"
                        v-model="settings.salesOptions.paymentFailures"
                        @change="saveSettings"
                        :disabled="!settings.categories.sales"
                        class="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
                      />
                      <span class="ml-2 text-xs text-gray-600 dark:text-gray-400">Payment failures</span>
                    </label>
                  </div>
                </div>

                <!-- Inventory Notifications -->
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                      <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                        <Package class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                      </div>
                      <div>
                        <h5 class="font-medium text-gray-900 dark:text-white">Inventory</h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Stock levels, reorder alerts</p>
                      </div>
                    </div>
                    <Switch v-model="settings.categories.inventory" @update:modelValue="saveSettings" />
                  </div>
                  <div class="ml-11 space-y-2">
                    <label class="flex items-center">
                      <input
                        type="checkbox"
                        v-model="settings.inventoryOptions.lowStock"
                        @change="saveSettings"
                        :disabled="!settings.categories.inventory"
                        class="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
                      />
                      <span class="ml-2 text-xs text-gray-600 dark:text-gray-400">Low stock warnings</span>
                    </label>
                    <label class="flex items-center">
                      <input
                        type="checkbox"
                        v-model="settings.inventoryOptions.outOfStock"
                        @change="saveSettings"
                        :disabled="!settings.categories.inventory"
                        class="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
                      />
                      <span class="ml-2 text-xs text-gray-600 dark:text-gray-400">Out of stock alerts</span>
                    </label>
                  </div>
                </div>

                <!-- System Notifications -->
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                      <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <Monitor class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                      </div>
                      <div>
                        <h5 class="font-medium text-gray-900 dark:text-white">System</h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Updates, maintenance, errors</p>
                      </div>
                    </div>
                    <Switch v-model="settings.categories.system" @update:modelValue="saveSettings" />
                  </div>
                  <div class="ml-11 space-y-2">
                    <label class="flex items-center">
                      <input
                        type="checkbox"
                        v-model="settings.systemOptions.updates"
                        @change="saveSettings"
                        :disabled="!settings.categories.system"
                        class="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
                      />
                      <span class="ml-2 text-xs text-gray-600 dark:text-gray-400">System updates</span>
                    </label>
                    <label class="flex items-center">
                      <input
                        type="checkbox"
                        v-model="settings.systemOptions.errors"
                        @change="saveSettings"
                        :disabled="!settings.categories.system"
                        class="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
                      />
                      <span class="ml-2 text-xs text-gray-600 dark:text-gray-400">Critical errors</span>
                    </label>
                  </div>
                </div>

                <!-- Staff Notifications -->
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                      <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <Users class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                      </div>
                      <div>
                        <h5 class="font-medium text-gray-900 dark:text-white">Staff</h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Employee activities, shifts</p>
                      </div>
                    </div>
                    <Switch v-model="settings.categories.staff" @update:modelValue="saveSettings" />
                  </div>
                  <div class="ml-11 space-y-2">
                    <label class="flex items-center">
                      <input
                        type="checkbox"
                        v-model="settings.staffOptions.clockInOut"
                        @change="saveSettings"
                        :disabled="!settings.categories.staff"
                        class="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
                      />
                      <span class="ml-2 text-xs text-gray-600 dark:text-gray-400">Clock in/out events</span>
                    </label>
                    <label class="flex items-center">
                      <input
                        type="checkbox"
                        v-model="settings.staffOptions.permissions"
                        @change="saveSettings"
                        :disabled="!settings.categories.staff"
                        class="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50"
                      />
                      <span class="ml-2 text-xs text-gray-600 dark:text-gray-400">Permission requests</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Delivery Preferences -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <Smartphone class="w-4 h-4" />
                Delivery Preferences
              </h4>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg text-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <div class="flex items-center justify-center mb-2">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                      <Monitor class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                  </div>
                  <h5 class="font-medium text-gray-900 dark:text-white mb-1">In-App</h5>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Show notifications within the app</p>
                  <Switch
                    v-model="settings.delivery.inApp"
                    @update:modelValue="saveSettings"
                    class="mx-auto"
                  />
                </div>

                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg text-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <div class="flex items-center justify-center mb-2">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                      <Smartphone class="w-5 h-5 text-green-600 dark:text-green-400" />
                    </div>
                  </div>
                  <h5 class="font-medium text-gray-900 dark:text-white mb-1">Browser</h5>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Desktop browser notifications</p>
                  <Switch
                    v-model="settings.delivery.browser"
                    @update:modelValue="handleBrowserNotificationToggle"
                    class="mx-auto"
                  />
                </div>

                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg text-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <div class="flex items-center justify-center mb-2">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                      <Mail class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    </div>
                  </div>
                  <h5 class="font-medium text-gray-900 dark:text-white mb-1">Email</h5>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Email summaries for important alerts</p>
                  <Switch
                    v-model="settings.delivery.email"
                    @update:modelValue="saveSettings"
                    class="mx-auto"
                  />
                </div>
              </div>
            </div>

            <!-- Test Notifications -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <TestTube class="w-4 h-4" />
                Test Notifications
              </h4>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <button
                  @click="testNotification('success')"
                  class="flex items-center gap-2 px-4 py-3 bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-900/50 text-green-800 dark:text-green-200 rounded-lg text-sm font-medium transition-colors"
                >
                  <CheckCircle class="w-4 h-4" />
                  Success
                </button>
                <button
                  @click="testNotification('error')"
                  class="flex items-center gap-2 px-4 py-3 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-800 dark:text-red-200 rounded-lg text-sm font-medium transition-colors"
                >
                  <AlertCircle class="w-4 h-4" />
                  Error
                </button>
                <button
                  @click="testNotification('warning')"
                  class="flex items-center gap-2 px-4 py-3 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:hover:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200 rounded-lg text-sm font-medium transition-colors"
                >
                  <AlertTriangle class="w-4 h-4" />
                  Warning
                </button>
                <button
                  @click="testNotification('info')"
                  class="flex items-center gap-2 px-4 py-3 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-800 dark:text-blue-200 rounded-lg text-sm font-medium transition-colors"
                >
                  <Info class="w-4 h-4" />
                  Info
                </button>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
            <button
              @click="resetToDefaults"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
            >
              Reset to Defaults
            </button>
            <div class="flex items-center gap-3">
              <button
                @click="exportSettings"
                class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium"
              >
                <Download class="w-4 h-4" />
                Export
              </button>
              <button
                @click="saveSettings"
                :disabled="!hasUnsavedChanges"
                class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
              >
                <Save class="w-4 h-4" />
                Save Changes
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useNotificationStore } from '@/stores/notifications'
import {
  Bell, Settings, Volume2, Package, ShoppingBag, Monitor, Users, Smartphone, Mail,
  TestTube, CheckCircle, AlertCircle, AlertTriangle, Info, Download, Save, X
} from 'lucide-vue-next'

// Components
import Switch from '@/Components/ui/Switch.vue'

// Props and emits
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const notificationStore = useNotificationStore()

// Local settings state
const settings = reactive({
  categories: {
    sales: true,
    inventory: true,
    system: true,
    staff: false
  },
  salesOptions: {
    largeTransactions: true,
    paymentFailures: true
  },
  inventoryOptions: {
    lowStock: true,
    outOfStock: true
  },
  systemOptions: {
    updates: true,
    errors: true
  },
  staffOptions: {
    clockInOut: false,
    permissions: true
  },
  delivery: {
    inApp: true,
    browser: false,
    email: false
  },
  volume: 75,
  soundTheme: 'modern'
})

const originalSettings = ref({})
const browserPermission = ref('default')

// Computed
const hasUnsavedChanges = computed(() => {
  return JSON.stringify(settings) !== JSON.stringify(originalSettings.value)
})

// Methods
const toggleSound = () => {
  notificationStore.toggleSound()
  saveSettings()
}

const handleBrowserNotificationToggle = async (enabled) => {
  if (enabled) {
    if ('Notification' in window) {
      const permission = await Notification.requestPermission()
      browserPermission.value = permission
      if (permission === 'denied') {
        settings.delivery.browser = false
        alert('Browser notifications are blocked. Please enable them in your browser settings.')
        return
      }
    } else {
      settings.delivery.browser = false
      alert('This browser does not support notifications.')
      return
    }
  }
  settings.delivery.browser = enabled
  saveSettings()
}

const saveSettings = () => {
  localStorage.setItem('notificationSettings', JSON.stringify(settings))
  originalSettings.value = JSON.parse(JSON.stringify(settings))
  console.log('Notification settings saved:', settings)
}

const resetToDefaults = () => {
  Object.assign(settings, {
    categories: {
      sales: true,
      inventory: true,
      system: true,
      staff: false
    },
    salesOptions: {
      largeTransactions: true,
      paymentFailures: true
    },
    inventoryOptions: {
      lowStock: true,
      outOfStock: true
    },
    systemOptions: {
      updates: true,
      errors: true
    },
    staffOptions: {
      clockInOut: false,
      permissions: true
    },
    delivery: {
      inApp: true,
      browser: false,
      email: false
    },
    volume: 75,
    soundTheme: 'modern'
  })
  saveSettings()
}

const exportSettings = () => {
  const settingsData = {
    ...settings,
    exportedAt: new Date().toISOString(),
    version: '1.0'
  }
  
  const blob = new Blob([JSON.stringify(settingsData, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'notification-settings.json'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

const testNotification = (type) => {
  const messages = {
    success: { title: 'Test Success', message: 'This is a success notification test' },
    error: { title: 'Test Error', message: 'This is an error notification test' },
    warning: { title: 'Test Warning', message: 'This is a warning notification test' },
    info: { title: 'Test Info', message: 'This is an info notification test' }
  }
  
  const notification = messages[type]
  notificationStore[type](notification.title, notification.message)
  
  if (settings.delivery.browser && 'Notification' in window && Notification.permission === 'granted') {
    new Notification(notification.title, {
      body: notification.message,
      icon: '/favicon.ico'
    })
  }
}

const loadSettings = () => {
  try {
    const saved = localStorage.getItem('notificationSettings')
    if (saved) {
      const parsedSettings = JSON.parse(saved)
      Object.assign(settings, parsedSettings)
    }
    originalSettings.value = JSON.parse(JSON.stringify(settings))
  } catch (error) {
    console.error('Failed to load notification settings:', error)
  }
}

const checkBrowserPermission = () => {
  if ('Notification' in window) {
    browserPermission.value = Notification.permission
    if (Notification.permission === 'denied') {
      settings.delivery.browser = false
    }
  }
}

// Handle ESC key to close modal
const handleKeydown = (event) => {
  if (event.key === 'Escape' && props.show) {
    emit('close')
  }
}

// Lifecycle
onMounted(() => {
  loadSettings()
  checkBrowserPermission()
})

// Watch for show prop changes to add/remove event listener
watch(() => props.show, (newVal) => {
  if (newVal) {
    document.addEventListener('keydown', handleKeydown)
  } else {
    document.removeEventListener('keydown', handleKeydown)
  }
})

// Watch for unsaved changes
watch(() => settings, () => {
  clearTimeout(window.notificationSettingsTimeout)
  window.notificationSettingsTimeout = setTimeout(() => {
    if (hasUnsavedChanges.value) {
      saveSettings()
    }
  }, 2000)
}, { deep: true })
</script>