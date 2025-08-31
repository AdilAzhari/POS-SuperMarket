<template>
  <div class="relative" ref="dropdown">
    <!-- Modern Notification Bell -->
    <button 
      @click="toggleDropdown" 
      class="relative p-2.5 rounded-xl hover:bg-gray-50 transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1" 
      :title="hasNotifications ? `${unreadCount} unread notifications` : 'No notifications'"
    >
      <Bell class="h-5 w-5 text-gray-500 group-hover:text-gray-700 transition-colors" />
      
      <!-- Unread Count Badge -->
      <span 
        v-if="unreadCount > 0" 
        class="absolute -top-1 -right-1 min-w-[20px] h-5 bg-gradient-to-r from-red-500 to-red-600 text-white text-xs font-bold rounded-full flex items-center justify-center shadow-lg animate-pulse border-2 border-white"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
      
      <!-- Notification Dot -->
      <span 
        v-else-if="hasNotifications" 
        class="absolute -top-0.5 -right-0.5 h-3 w-3 bg-blue-500 rounded-full shadow-sm border-2 border-white"
      ></span>
    </button>

    <!-- Enhanced Dropdown Menu -->
    <div
      v-if="isOpen"
      class="absolute right-0 mt-3 w-96 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden backdrop-blur-sm"
      style="filter: drop-shadow(0 25px 25px rgb(0 0 0 / 0.15))"
    >
      <!-- Modern Header -->
      <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <Bell class="h-5 w-5 text-gray-600" />
            <h3 class="text-lg font-semibold text-gray-900">
              Notifications
            </h3>
            <span v-if="unreadCount > 0" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
              {{ unreadCount }} new
            </span>
          </div>
          <div class="flex items-center space-x-1">
            <button
              v-if="unreadCount > 0"
              @click="markAllAsRead"
              class="text-xs font-medium text-blue-600 hover:text-blue-700 px-3 py-1 rounded-lg hover:bg-blue-50 transition-colors"
            >
              Mark all read
            </button>
            <button
              @click="openSettings"
              class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
              title="Notification settings"
            >
              <Settings class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="max-h-80 overflow-y-auto">
        <div v-if="notifications.length > 0">
          <div
            v-for="notification in notifications.slice(0, 10)"
            :key="notification.id"
            @click="markAsRead(notification)"
            :class="[
              'px-6 py-4 hover:bg-gray-50/80 cursor-pointer transition-all duration-200 border-l-4',
              !notification.read 
                ? 'bg-blue-50/50 border-blue-400' 
                : 'bg-white border-transparent hover:border-gray-200'
            ]"
          >
            <div class="flex items-start space-x-4">
              <!-- Enhanced Icon -->
              <div class="flex-shrink-0 mt-0.5">
                <div :class="[
                  'p-2.5 rounded-full',
                  getNotificationBgClass(notification.type)
                ]">
                  <component 
                    :is="getNotificationIcon(notification.type)" 
                    :class="getNotificationIconClass(notification.type)"
                    class="w-4 h-4"
                  />
                </div>
              </div>
              
              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <div class="flex-1 pr-2">
                    <h4 :class="['text-sm font-semibold leading-5', !notification.read ? 'text-gray-900' : 'text-gray-700']">
                      {{ notification.title }}
                    </h4>
                    <p :class="['text-sm mt-1 leading-5', !notification.read ? 'text-gray-600' : 'text-gray-500']">
                      {{ notification.message }}
                    </p>
                    <div class="flex items-center mt-2 space-x-4">
                      <p class="text-xs text-gray-400 flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                        {{ formatTime(notification.timestamp) }}
                      </p>
                    </div>
                  </div>
                  
                  <!-- Priority Badge -->
                  <div v-if="notification.priority === 'urgent'" class="flex-shrink-0">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 ring-1 ring-red-200">
                      <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                      </svg>
                      Urgent
                    </span>
                  </div>
                </div>
                
                <!-- Action Buttons -->
                <div v-if="notification.actions && notification.actions.length > 0" class="mt-3 flex flex-wrap gap-2">
                  <button
                    v-for="action in notification.actions"
                    :key="action.id"
                    @click.stop="handleAction(action, notification)"
                    :class="[
                      'text-xs px-3 py-1.5 rounded-lg font-medium transition-colors',
                      action.type === 'primary' 
                        ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                  >
                    {{ action.label }}
                  </button>
                </div>
              </div>
              
              <!-- Dismiss Button -->
              <div class="flex-shrink-0">
                <button
                  @click.stop="removeNotification(notification.id)"
                  class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
                  title="Dismiss"
                >
                  <X class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Empty State -->
        <div v-else class="px-6 py-12 text-center">
          <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <Bell class="w-8 h-8 text-gray-400" />
          </div>
          <h3 class="text-sm font-medium text-gray-900 mb-2">No notifications yet</h3>
          <p class="text-sm text-gray-500">We'll notify you when something important happens.</p>
        </div>
      </div>

      <!-- Footer -->
      <div v-if="notifications.length > 10" class="px-6 py-4 border-t border-gray-100 bg-gradient-to-r from-gray-50 to-white">
        <button
          @click="viewAllNotifications"
          class="w-full text-center text-sm font-medium text-blue-600 hover:text-blue-700 py-2 px-4 rounded-lg hover:bg-blue-50 transition-colors"
        >
          View all {{ notifications.length }} notifications
        </button>
      </div>
    </div>

    <!-- Notification Settings Modal -->
    <NotificationSettings 
      :show="showSettingsModal" 
      @close="showSettingsModal = false" 
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useNotificationStore } from '@/stores/notifications'
import { Bell, X, Settings, CheckCircle, XCircle, AlertTriangle, Info } from 'lucide-vue-next'
import NotificationSettings from './NotificationSettings.vue'

const notificationStore = useNotificationStore()
const dropdown = ref(null)
const isOpen = ref(false)

// Computed properties
const notifications = computed(() => notificationStore.notifications)
const unreadCount = computed(() => notificationStore.unreadCount)
const hasNotifications = computed(() => notificationStore.hasNotifications)

// Methods
const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

const closeDropdown = () => {
  isOpen.value = false
}

const markAsRead = (notification) => {
  notificationStore.markAsRead(notification.id)
}

const markAllAsRead = () => {
  notificationStore.markAllAsRead()
}

const removeNotification = (id) => {
  notificationStore.removeNotification(id)
}

const showSettingsModal = ref(false)

const openSettings = () => {
  showSettingsModal.value = true
  closeDropdown()
}

const viewAllNotifications = () => {
  // Navigate to full notifications page
  closeDropdown()
}

const handleAction = (action, notification) => {
  // Handle notification actions
  console.log('Action clicked:', action, notification)
  if (action.action) {
    action.action()
  }
  closeDropdown()
}

const getNotificationIcon = (type) => {
  switch (type) {
    case 'success': return CheckCircle
    case 'error': return XCircle
    case 'warning': return AlertTriangle
    case 'info': return Info
    default: return Info
  }
}

const getNotificationIconClass = (type) => {
  switch (type) {
    case 'success': return 'text-green-600'
    case 'error': return 'text-red-600'
    case 'warning': return 'text-yellow-600'
    case 'info': return 'text-blue-600'
    default: return 'text-gray-600'
  }
}

const getNotificationBgClass = (type) => {
  switch (type) {
    case 'success': return 'bg-green-100'
    case 'error': return 'bg-red-100'
    case 'warning': return 'bg-yellow-100'
    case 'info': return 'bg-blue-100'
    default: return 'bg-gray-100'
  }
}

const formatTime = (timestamp) => {
  const now = new Date()
  const time = new Date(timestamp)
  const diffMs = now - time
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)

  if (diffMins < 1) return 'now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  return time.toLocaleDateString()
}

// Handle click outside to close dropdown
const handleClickOutside = (event) => {
  if (dropdown.value && !dropdown.value.contains(event.target)) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>