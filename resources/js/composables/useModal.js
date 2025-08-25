import { ref, reactive } from 'vue'

export function useModal(initialState = false) {
  const isOpen = ref(initialState)

  const open = () => {
    isOpen.value = true
  }

  const close = () => {
    isOpen.value = false
  }

  const toggle = () => {
    isOpen.value = !isOpen.value
  }

  return {
    isOpen,
    open,
    close,
    toggle,
  }
}

export function useMessageModal() {
  const isVisible = ref(false)
  const modalData = reactive({
    title: '',
    message: '',
    type: 'info',
    size: 'md',
    showCancelButton: false,
    confirmText: 'OK',
    cancelText: 'Cancel'
  })
  
  const resolvePromise = ref(null)
  
  const show = (options = {}) => {
    return new Promise((resolve) => {
      Object.assign(modalData, {
        title: '',
        message: '',
        type: 'info',
        size: 'md',
        showCancelButton: false,
        confirmText: 'OK',
        cancelText: 'Cancel',
        ...options
      })
      
      resolvePromise.value = resolve
      isVisible.value = true
    })
  }
  
  const hide = () => {
    isVisible.value = false
    resolvePromise.value = null
  }
  
  const confirm = () => {
    if (resolvePromise.value) {
      resolvePromise.value(true)
    }
    hide()
  }
  
  const cancel = () => {
    if (resolvePromise.value) {
      resolvePromise.value(false)
    }
    hide()
  }
  
  // Convenience methods
  const showSuccess = (message, title = 'Success') => {
    return show({
      title,
      message,
      type: 'success',
      showCancelButton: false
    })
  }
  
  const showError = (message, title = 'Error') => {
    return show({
      title,
      message,
      type: 'error',
      showCancelButton: false
    })
  }
  
  const showWarning = (message, title = 'Warning') => {
    return show({
      title,
      message,
      type: 'warning',
      showCancelButton: false
    })
  }
  
  const showInfo = (message, title = 'Information') => {
    return show({
      title,
      message,
      type: 'info',
      showCancelButton: false
    })
  }
  
  const showConfirm = (message, title = 'Confirm Action') => {
    return show({
      title,
      message,
      type: 'warning',
      showCancelButton: true,
      confirmText: 'Yes',
      cancelText: 'No'
    })
  }
  
  return {
    isVisible,
    modalData,
    show,
    hide,
    confirm,
    cancel,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    showConfirm
  }
}

export function useConfirmModal() {
  const isOpen = ref(false)
  const title = ref('')
  const message = ref('')
  const confirmText = ref('Confirm')
  const cancelText = ref('Cancel')
  const resolve = ref(null)

  const confirm = (
    titleText,
    messageText,
    confirmButtonText = 'Confirm',
    cancelButtonText = 'Cancel'
  ) => {
    title.value = titleText
    message.value = messageText
    confirmText.value = confirmButtonText
    cancelText.value = cancelButtonText
    isOpen.value = true

    return new Promise(res => {
      resolve.value = res
    })
  }

  const handleConfirm = () => {
    resolve.value?.(true)
    isOpen.value = false
    resolve.value = null
  }

  const handleCancel = () => {
    resolve.value?.(false)
    isOpen.value = false
    resolve.value = null
  }

  return {
    isOpen,
    title,
    message,
    confirmText,
    cancelText,
    confirm,
    handleConfirm,
    handleCancel,
  }
}
