<template>
  <div class="fixed top-4 right-4 z-50 space-y-2 max-w-sm">
    <!-- Notification Settings Toggle -->
    <div v-if="showSettings" class="bg-white rounded-lg shadow-lg p-4 border">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-medium text-gray-900">Notification Settings</h3>
        <button
          @click="showSettings = false"
          class="text-gray-400 hover:text-gray-600"
        >
          <X class="w-4 h-4" />
        </button>
      </div>
      <div class="space-y-3">
        <label class="flex items-center space-x-2">
          <input
            type="checkbox"
            :checked="notificationStore.soundEnabled"
            @change="notificationStore.toggleSound()"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
          <span class="text-sm text-gray-700">Enable notification sounds</span>
        </label>
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-700">History:</span>
          <div class="flex space-x-2">
            <button
              @click="showHistory = !showHistory"
              class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
            >
              {{ showHistory ? 'Hide' : 'Show' }} ({{ notificationStore.historyCount }})
            </button>
            <button
              @click="notificationStore.clearHistory()"
              class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200"
            >
              Clear
            </button>
          </div>
        </div>
        <button
          @click="notificationStore.markAllAsRead()"
          class="w-full text-xs py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
        >
          Mark All as Read
        </button>
      </div>
    </div>

    <!-- Control Panel -->
    <div class="flex items-center justify-end space-x-2 mb-2">
      <button
        v-if="notificationStore.hasNotifications"
        @click="notificationStore.clearAll()"
        class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200"
        title="Clear all notifications"
      >
        Clear All
      </button>
      <button
        @click="showSettings = !showSettings"
        class="p-1 text-gray-500 hover:text-gray-700 rounded"
        title="Notification settings"
      >
        <Settings class="w-4 h-4" />
      </button>
    </div>

    <!-- Notification History -->
    <div
      v-if="showHistory"
      class="bg-white rounded-lg shadow-lg border max-h-60 overflow-y-auto"
    >
      <div class="p-3 border-b">
        <h3 class="text-sm font-medium text-gray-900">Notification History</h3>
      </div>
      <div class="p-2 space-y-1">
        <div
          v-for="notification in notificationStore.notificationHistory.slice(0, 10)"
          :key="'history-' + notification.id"
          class="text-xs p-2 rounded border-l-2"
          :class="getHistoryItemClass(notification)"
        >
          <div class="flex items-center justify-between">
            <span class="font-medium">{{ notification.title }}</span>
            <span class="text-gray-500">{{ formatTime(notification.timestamp) }}</span>
          </div>
          <p class="text-gray-600 mt-1">{{ notification.message }}</p>
        </div>
        <div v-if="notificationStore.historyCount === 0" class="text-center text-gray-500 py-4">
          No notifications yet
        </div>
      </div>
    </div>

    <!-- Active Notifications -->
    <TransitionGroup name="notification" tag="div">
      <div
        v-for="notification in notificationStore.notifications"
        :key="notification.id"
        :class="[
          'rounded-lg p-4 shadow-lg border-l-4 transform transition-all duration-300 cursor-pointer',
          notificationClasses(notification.type),
          notification.priority === 'urgent' ? 'ring-2 ring-red-400 ring-opacity-50' : '',
          !notification.read ? 'bg-opacity-100' : 'bg-opacity-75'
        ]"
        @click="markAsRead(notification)"
      >
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <component 
              :is="getIcon(notification.type)" 
              :class="['h-5 w-5', getIconColor(notification.type)]"
            />
          </div>
          <div class="ml-3 flex-1">
            <h4 v-if="notification.title" class="text-sm font-medium mb-1">
              {{ notification.title }}
            </h4>
            <p class="text-sm">
              {{ notification.message }}
            </p>
            <div v-if="notification.actions?.length" class="mt-2 flex space-x-2">
              <button
                v-for="action in notification.actions"
                :key="action.label"
                @click="action.handler"
                class="text-xs px-2 py-1 rounded bg-white bg-opacity-20 hover:bg-opacity-30 transition-colors"
              >
                {{ action.label }}
              </button>
            </div>
          </div>
          <div class="ml-4 flex-shrink-0">
            <button
              @click="notificationStore.removeNotification(notification.id)"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <X class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useNotificationStore } from '@/stores/notifications'
import { CheckCircle, XCircle, AlertTriangle, Info, X, Settings } from 'lucide-vue-next'

const notificationStore = useNotificationStore()
const showSettings = ref(false)
const showHistory = ref(false)

const getIcon = (type) => {
  switch (type) {
    case 'success': return CheckCircle
    case 'error': return XCircle
    case 'warning': return AlertTriangle
    case 'info': return Info
    default: return Info
  }
}

const getIconColor = (type) => {
  switch (type) {
    case 'success': return 'text-green-600'
    case 'error': return 'text-red-600'
    case 'warning': return 'text-yellow-600'
    case 'info': return 'text-blue-600'
    default: return 'text-gray-600'
  }
}

const notificationClasses = (type) => {
  switch (type) {
    case 'success':
      return 'bg-green-50 border-green-400 text-green-800'
    case 'error':
      return 'bg-red-50 border-red-400 text-red-800'
    case 'warning':
      return 'bg-yellow-50 border-yellow-400 text-yellow-800'
    case 'info':
      return 'bg-blue-50 border-blue-400 text-blue-800'
    default:
      return 'bg-gray-50 border-gray-400 text-gray-800'
  }
}

const getHistoryItemClass = (notification) => {
  const baseClass = 'bg-gray-50'
  const borderColors = {
    success: 'border-green-400',
    error: 'border-red-400',
    warning: 'border-yellow-400',
    info: 'border-blue-400'
  }
  return `${baseClass} ${borderColors[notification.type] || 'border-gray-400'}`
}

const markAsRead = (notification) => {
  notificationStore.markAsRead(notification.id)
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
</script>

<style scoped>
.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.notification-move {
  transition: transform 0.3s ease;
}
</style>