import { ref } from 'vue'

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
