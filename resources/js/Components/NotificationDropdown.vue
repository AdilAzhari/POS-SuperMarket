<template>
  <div class="relative" ref="dropdown">
    <button 
      @click="toggleDropdown" 
      class="p-2 rounded-md hover:bg-gray-100 relative transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500" 
      :title="hasNotifications ? `${unreadCount} unread notifications` : 'No notifications'"
    >
      <Bell class="h-5 w-5 text-gray-500 hover:text-gray-700" />
      <span 
        v-if="unreadCount > 0" 
        class="absolute -top-1 -right-1 h-5 w-5 bg-red-500 text-white text-xs font-medium rounded-full flex items-center justify-center shadow-lg animate-pulse z-10"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
      <!-- Alternative indicator for when count is 0 but notifications exist -->
      <span 
        v-else-if="hasNotifications" 
        class="absolute -top-1 -right-1 h-3 w-3 bg-blue-500 rounded-full shadow-lg z-10"
      ></span>
    </button>

    <!-- Dropdown Menu -->
    <div
      v-if="isOpen"
      class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50 max-h-96 overflow-hidden"
    >
      <!-- Header -->
      <div class="p-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-medium text-gray-900">
            Notifications
            <span v-if="unreadCount > 0" class="ml-1 text-xs text-gray-500">({{ unreadCount }} new)</span>
          </h3>
          <div class="flex items-center space-x-2">
            <button
              v-if="unreadCount > 0"
              @click="markAllAsRead"
              class="text-xs text-blue-600 hover:text-blue-800"
            >
              Mark all read
            </button>
            <button
              @click="openSettings"
              class="p-1 text-gray-400 hover:text-gray-600 rounded"
              title="Notification settings"
            >
              <Settings class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="max-h-64 overflow-y-auto">
        <div v-if="notifications.length > 0" class="divide-y divide-gray-100">
          <div
            v-for="notification in notifications.slice(0, 10)"
            :key="notification.id"
            @click="markAsRead(notification)"
            :class="[
              'p-4 hover:bg-gray-50 cursor-pointer transition-colors',
              !notification.read ? 'bg-blue-50 border-l-4 border-blue-400' : ''
            ]"
          >
            <div class="flex items-start space-x-3">
              <div class="flex-shrink-0 mt-1">
                <component 
                  :is="getNotificationIcon(notification.type)" 
                  :class="getNotificationIconClass(notification.type)"
                  class="w-5 h-5"
                />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <p :class="['text-sm font-medium', !notification.read ? 'text-gray-900' : 'text-gray-700']">
                      {{ notification.title }}
                    </p>
                    <p :class="['text-sm mt-1', !notification.read ? 'text-gray-700' : 'text-gray-500']">
                      {{ notification.message }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                      {{ formatTime(notification.timestamp) }}
                    </p>
                  </div>
                  <div v-if="notification.priority === 'urgent'" class="ml-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                      Urgent
                    </span>
                  </div>
                </div>
                <!-- Actions if any -->
                <div v-if="notification.actions && notification.actions.length > 0" class="mt-2 flex space-x-2">
                  <button
                    v-for="action in notification.actions"
                    :key="action.id"
                    @click.stop="handleAction(action, notification)"
                    :class="[
                      'text-xs px-2 py-1 rounded border',
                      action.type === 'primary' 
                        ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700'
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                    ]"
                  >
                    {{ action.label }}
                  </button>
                </div>
              </div>
              <div class="flex-shrink-0">
                <button
                  @click.stop="removeNotification(notification.id)"
                  class="text-gray-400 hover:text-gray-600"
                  title="Dismiss"
                >
                  <X class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="p-8 text-center">
          <Bell class="w-12 h-12 text-gray-300 mx-auto mb-4" />
          <p class="text-gray-500 text-sm">No notifications yet</p>
        </div>
      </div>

      <!-- Footer -->
      <div v-if="notifications.length > 10" class="p-3 border-t border-gray-200 bg-gray-50">
        <button
          @click="viewAllNotifications"
          class="w-full text-center text-sm text-blue-600 hover:text-blue-800"
        >
          View all notifications ({{ notifications.length }})
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useNotificationStore } from '@/stores/notifications'
import { Bell, X, Settings, CheckCircle, XCircle, AlertTriangle, Info } from 'lucide-vue-next'

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

const openSettings = () => {
  // You can implement settings modal here
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
    case 'success': return 'text-green-500'
    case 'error': return 'text-red-500'
    case 'warning': return 'text-yellow-500'
    case 'info': return 'text-blue-500'
    default: return 'text-gray-500'
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