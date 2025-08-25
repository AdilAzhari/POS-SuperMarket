import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useNotificationStore = defineStore('notifications', () => {
  const notifications = ref([])
  const maxNotifications = 5
  const soundEnabled = ref(true)
  const notificationHistory = ref([])
  const maxHistory = 50

  // Generate unique ID for notifications
  const generateId = () => Date.now().toString(36) + Math.random().toString(36).substr(2)

  // Play notification sound
  const playSound = (type) => {
    if (!soundEnabled.value) return
    
    try {
      // Create audio context for different notification types
      const audioContext = new (window.AudioContext || window.webkitAudioContext)()
      const oscillator = audioContext.createOscillator()
      const gainNode = audioContext.createGain()
      
      oscillator.connect(gainNode)
      gainNode.connect(audioContext.destination)
      
      // Different tones for different notification types
      const frequencies = {
        success: 800,
        error: 400,
        warning: 600,
        info: 700
      }
      
      oscillator.frequency.setValueAtTime(frequencies[type] || 700, audioContext.currentTime)
      oscillator.type = 'sine'
      
      gainNode.gain.setValueAtTime(0.1, audioContext.currentTime)
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3)
      
      oscillator.start(audioContext.currentTime)
      oscillator.stop(audioContext.currentTime + 0.3)
    } catch (error) {
      console.warn('Could not play notification sound:', error)
    }
  }

  // Add notification
  const addNotification = (notification) => {
    const id = generateId()
    const newNotification = {
      id,
      type: notification.type || 'info', // info, success, warning, error
      title: notification.title || '',
      message: notification.message || '',
      duration: notification.duration || 5000,
      persistent: notification.persistent || false,
      actions: notification.actions || [],
      timestamp: new Date(),
      read: false,
      priority: notification.priority || 'normal', // low, normal, high, urgent
      category: notification.category || 'general', // general, system, sales, inventory, etc.
      showInHistory: notification.showInHistory !== false
    }

    // Play notification sound
    playSound(newNotification.type)

    notifications.value.unshift(newNotification)

    // Add to history if enabled
    if (newNotification.showInHistory) {
      notificationHistory.value.unshift({...newNotification})
      // Limit history size
      if (notificationHistory.value.length > maxHistory) {
        notificationHistory.value = notificationHistory.value.slice(0, maxHistory)
      }
    }

    // Limit max notifications
    if (notifications.value.length > maxNotifications) {
      notifications.value = notifications.value.slice(0, maxNotifications)
    }

    // Auto remove if not persistent
    if (!newNotification.persistent && newNotification.duration > 0) {
      setTimeout(() => {
        removeNotification(id)
      }, newNotification.duration)
    }

    return id
  }

  // Remove notification
  const removeNotification = (id) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }

  // Clear all notifications
  const clearAll = () => {
    notifications.value = []
  }

  // Mark notification as read
  const markAsRead = (id) => {
    const notification = notifications.value.find(n => n.id === id)
    if (notification) {
      notification.read = true
    }
    // Also mark in history
    const historyNotification = notificationHistory.value.find(n => n.id === id)
    if (historyNotification) {
      historyNotification.read = true
    }
  }

  // Mark all as read
  const markAllAsRead = () => {
    notifications.value.forEach(n => n.read = true)
  }

  // Clear history
  const clearHistory = () => {
    notificationHistory.value = []
  }

  // Toggle sound
  const toggleSound = () => {
    soundEnabled.value = !soundEnabled.value
    // Save preference to localStorage
    localStorage.setItem('notification-sound-enabled', soundEnabled.value.toString())
  }

  // Load sound preference from localStorage
  const loadSoundPreference = () => {
    const saved = localStorage.getItem('notification-sound-enabled')
    if (saved !== null) {
      soundEnabled.value = saved === 'true'
    }
  }

  // Filter notifications by category
  const getNotificationsByCategory = (category) => {
    return notificationHistory.value.filter(n => n.category === category)
  }

  // Get notifications by priority
  const getNotificationsByPriority = (priority) => {
    return notifications.value.filter(n => n.priority === priority)
  }

  // Convenience methods
  const success = (title, message, options = {}) => {
    return addNotification({
      type: 'success',
      title,
      message,
      ...options
    })
  }

  const error = (title, message, options = {}) => {
    return addNotification({
      type: 'error',
      title,
      message,
      persistent: true, // Errors should be persistent by default
      ...options
    })
  }

  const warning = (title, message, options = {}) => {
    return addNotification({
      type: 'warning',
      title,
      message,
      ...options
    })
  }

  const info = (title, message, options = {}) => {
    return addNotification({
      type: 'info',
      title,
      message,
      ...options
    })
  }

  // Stock movement specific notifications
  const stockMovementSuccess = (productName, type, quantity) => {
    const typeLabels = {
      'addition': 'added to',
      'reduction': 'removed from',
      'transfer_in': 'transferred to',
      'transfer_out': 'transferred from',
      'adjustment': 'adjusted in'
    }
    
    return success(
      'Stock Updated',
      `${quantity} units of ${productName} ${typeLabels[type] || 'updated in'} inventory`,
      { duration: 4000 }
    )
  }

  const stockMovementError = (error) => {
    return error(
      'Stock Movement Failed',
      error || 'Failed to update stock. Please try again.',
      { persistent: true }
    )
  }

  // Low stock notifications
  const lowStockWarning = (productName, currentStock, threshold) => {
    return warning(
      'Low Stock Alert',
      `${productName} has only ${currentStock} units left (threshold: ${threshold})`,
      { 
        persistent: true,
        actions: [
          {
            label: 'Reorder',
            handler: () => {
              // Navigate to product reorder
              console.log('Navigate to reorder for', productName)
            }
          }
        ]
      }
    )
  }

  // POS-specific notifications
  const saleCompleted = (saleCode, total, paymentMethod) => {
    return success(
      'Sale Completed',
      `Sale ${saleCode} for RM ${total} completed via ${paymentMethod}`,
      { duration: 3000 }
    )
  }

  const paymentProcessed = (amount, method) => {
    return success(
      'Payment Processed',
      `Payment of RM ${amount} via ${method} was successful`,
      { duration: 2000 }
    )
  }

  const paymentFailed = (reason) => {
    return error(
      'Payment Failed',
      reason || 'Payment could not be processed. Please try again.',
      { persistent: true }
    )
  }

  const customerAdded = (customerName) => {
    return success(
      'Customer Added',
      `${customerName} has been added to the system`,
      { duration: 3000 }
    )
  }

  const productOutOfStock = (productName) => {
    return error(
      'Out of Stock',
      `${productName} is out of stock and cannot be sold`,
      { 
        persistent: true,
        actions: [
          {
            label: 'Remove from Cart',
            handler: () => {
              // Remove item from cart
              console.log('Remove', productName, 'from cart')
            }
          },
          {
            label: 'Check Alternatives',
            handler: () => {
              // Show similar products
              console.log('Show alternatives for', productName)
            }
          }
        ]
      }
    )
  }

  const barcodeNotFound = (barcode) => {
    return warning(
      'Product Not Found',
      `No product found with barcode: ${barcode}`,
      { 
        duration: 4000,
        actions: [
          {
            label: 'Add New Product',
            handler: () => {
              // Navigate to add product with barcode
              console.log('Add new product with barcode', barcode)
            }
          }
        ]
      }
    )
  }

  const refundProcessed = (amount, reason) => {
    return info(
      'Refund Processed',
      `Refund of RM ${amount} processed. Reason: ${reason}`,
      { duration: 4000 }
    )
  }

  const inventoryAlert = (message, level = 'warning') => {
    const method = level === 'error' ? error : warning
    return method(
      'Inventory Alert',
      message,
      { persistent: level === 'error' }
    )
  }

  const systemMaintenance = (message, startTime) => {
    return warning(
      'System Maintenance',
      `${message} Scheduled for ${startTime}`,
      { 
        persistent: true,
        duration: 0 // No auto-dismiss
      }
    )
  }

  // Initialize sound preference
  loadSoundPreference()

  // Computed
  const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)
  const hasNotifications = computed(() => notifications.value.length > 0)
  const hasUnreadNotifications = computed(() => unreadCount.value > 0)
  const urgentNotifications = computed(() => getNotificationsByPriority('urgent'))
  const historyCount = computed(() => notificationHistory.value.length)

  return {
    notifications,
    notificationHistory,
    soundEnabled,
    unreadCount,
    hasNotifications,
    hasUnreadNotifications,
    urgentNotifications,
    historyCount,
    addNotification,
    removeNotification,
    clearAll,
    markAsRead,
    markAllAsRead,
    clearHistory,
    toggleSound,
    getNotificationsByCategory,
    getNotificationsByPriority,
    success,
    error,
    warning,
    info,
    stockMovementSuccess,
    stockMovementError,
    lowStockWarning,
    saleCompleted,
    paymentProcessed,
    paymentFailed,
    customerAdded,
    productOutOfStock,
    barcodeNotFound,
    refundProcessed,
    inventoryAlert,
    systemMaintenance
  }
})