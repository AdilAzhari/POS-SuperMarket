import { ref } from 'vue'

const notifications = ref([])

export function useNotification() {
  const add = (notification) => {
    const id = Math.random().toString(36).substring(2, 9)
    const newNotification = {
      ...notification,
      id,
      duration: notification.duration ?? 5000,
    }

    notifications.value.push(newNotification)

    if (newNotification.duration && newNotification.duration > 0) {
      setTimeout(() => {
        remove(id)
      }, newNotification.duration)
    }

    return id
  }

  const remove = (id) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }

  const clear = () => {
    notifications.value = []
  }

  const success = (title, message, duration) => {
    return add({ type: 'success', title, message, duration })
  }

  const error = (title, message, duration) => {
    return add({ type: 'error', title, message, duration: duration ?? 0 })
  }

  const warning = (title, message, duration) => {
    return add({ type: 'warning', title, message, duration })
  }

  const info = (title, message, duration) => {
    return add({ type: 'info', title, message, duration })
  }

  return {
    notifications,
    add,
    remove,
    clear,
    success,
    error,
    warning,
    info,
  }
}
