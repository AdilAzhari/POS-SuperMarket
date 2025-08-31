<template>
  <div class="fixed top-20 right-4 z-50 space-y-3 max-w-md">
    <!-- Modern Notification Settings Panel -->
    <div v-if="showSettings" class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <Settings class="h-5 w-5 text-gray-600" />
            <h3 class="text-lg font-semibold text-gray-900">Settings</h3>
          </div>
          <button
            @click="showSettings = false"
            class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
      </div>
      
      <div class="px-6 py-4 space-y-4">
        <label class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="p-2 bg-blue-100 rounded-lg">
              <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M18 3a1 1 0 00-1.196-.98L10 2.89A3.001 3.001 0 00.804 2.02a1 1 0 101.196.98L2 3a3 3 0 106 0L10 4.91l5.196-.98L16 4a3.001 3.001 0 102-1z"></path>
              </svg>
            </div>
            <span class="text-sm font-medium text-gray-900">Notification sounds</span>
          </div>
          <input
            type="checkbox"
            :checked="notificationStore.soundEnabled"
            @change="notificationStore.toggleSound()"
            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
          />
        </label>
        
        <div class="border-t border-gray-100 pt-4">
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-medium text-gray-900">History Management</span>
            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">{{ notificationStore.historyCount }} items</span>
          </div>
          <div class="flex space-x-2">
            <button
              @click="showHistory = !showHistory"
              class="flex-1 text-xs font-medium px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors"
            >
              {{ showHistory ? 'Hide History' : 'Show History' }}
            </button>
            <button
              @click="notificationStore.clearHistory()"
              class="flex-1 text-xs font-medium px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors"
            >
              Clear History
            </button>
          </div>
        </div>
        
        <button
          @click="notificationStore.markAllAsRead()"
          class="w-full text-sm font-medium py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
        >
          Mark All as Read
        </button>
      </div>
    </div>

    <!-- Modern Control Panel -->
    <div class="flex items-center justify-end space-x-2">
      <button
        v-if="notificationStore.hasNotifications"
        @click="notificationStore.clearAll()"
        class="px-3 py-2 text-xs font-medium bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors shadow-sm"
        title="Clear all notifications"
      >
        Clear All
      </button>
      <button
        @click="showSettings = !showSettings"
        class="p-2.5 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-white hover:shadow-sm transition-all duration-200"
        title="Notification settings"
      >
        <Settings class="w-4 h-4" />
      </button>
    </div>

    <!-- Enhanced Notification History -->
    <div
      v-if="showHistory"
      class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-100 max-h-80 overflow-hidden"
    >
      <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
        <div class="flex items-center space-x-2">
          <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
          </svg>
          <h3 class="text-lg font-semibold text-gray-900">History</h3>
        </div>
      </div>
      <div class="overflow-y-auto max-h-64">
        <div class="p-4 space-y-3">
          <div
            v-for="notification in notificationStore.notificationHistory.slice(0, 10)"
            :key="'history-' + notification.id"
            class="p-3 rounded-xl border border-gray-100 hover:border-gray-200 transition-colors"
            :class="getHistoryItemClass(notification)"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <h4 class="text-sm font-medium text-gray-900">{{ notification.title }}</h4>
                <p class="text-sm text-gray-600 mt-1">{{ notification.message }}</p>
              </div>
              <span class="text-xs text-gray-400 ml-2 whitespace-nowrap">{{ formatTime(notification.timestamp) }}</span>
            </div>
          </div>
          <div v-if="notificationStore.historyCount === 0" class="text-center text-gray-500 py-8">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
            </svg>
            <p class="text-sm">No notification history</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modern Toast Notifications -->
    <TransitionGroup name="notification" tag="div" class="space-y-3">
      <div
        v-for="notification in notificationStore.notifications"
        :key="notification.id"
        :class="[
          'rounded-2xl p-5 shadow-2xl border-l-4 transform transition-all duration-300 cursor-pointer backdrop-blur-sm',
          notificationClasses(notification.type),
          notification.priority === 'urgent' ? 'ring-2 ring-red-400/50 animate-pulse' : '',
          !notification.read ? 'bg-opacity-95' : 'bg-opacity-80'
        ]"
        @click="markAsRead(notification)"
        style="filter: drop-shadow(0 10px 15px rgb(0 0 0 / 0.1))"
      >
        <div class="flex items-start space-x-4">
          <div class="flex-shrink-0">
            <div :class="[
              'p-2.5 rounded-full',
              getIconBgClass(notification.type)
            ]">
              <component 
                :is="getIcon(notification.type)" 
                :class="['h-5 w-5', getIconColor(notification.type)]"
              />
            </div>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <h4 v-if="notification.title" class="text-sm font-semibold mb-1 text-gray-900">
                  {{ notification.title }}
                </h4>
                <p class="text-sm text-gray-700 leading-5">
                  {{ notification.message }}
                </p>
              </div>
              <div v-if="notification.priority === 'urgent'" class="ml-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 ring-1 ring-red-200">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                  Urgent
                </span>
              </div>
            </div>
            
            <div v-if="notification.actions?.length" class="mt-3 flex flex-wrap gap-2">
              <button
                v-for="action in notification.actions"
                :key="action.label"
                @click.stop="action.handler"
                class="text-xs font-medium px-3 py-1.5 rounded-lg bg-white/60 hover:bg-white/80 backdrop-blur-sm border border-white/20 transition-all duration-200 hover:shadow-sm"
              >
                {{ action.label }}
              </button>
            </div>
          </div>
          <div class="flex-shrink-0">
            <button
              @click.stop="notificationStore.removeNotification(notification.id)"
              class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-white/30 transition-colors"
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

const getIconBgClass = (type) => {
  switch (type) {
    case 'success': return 'bg-green-100'
    case 'error': return 'bg-red-100'
    case 'warning': return 'bg-yellow-100'
    case 'info': return 'bg-blue-100'
    default: return 'bg-gray-100'
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