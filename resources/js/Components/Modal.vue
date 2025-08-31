<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 overflow-y-auto"
      @click.self="closeOnBackdrop && $emit('close')"
    >
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
      
      <!-- Modal container -->
      <div class="flex min-h-full items-center justify-center p-4">
        <div
          class="relative w-full transform rounded-lg bg-white shadow-xl transition-all"
          :class="modalClass"
        >
          <!-- Header -->
          <div v-if="title || $slots.header" class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <slot name="header">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                  <span v-if="icon" class="text-xl">{{ icon }}</span>
                  {{ title }}
                </h3>
              </slot>
              <button
                v-if="showCloseButton"
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          
          <!-- Body -->
          <div class="px-6 py-4">
            <slot>
              <div class="flex items-start gap-3">
                <div v-if="icon && !title" class="flex-shrink-0">
                  <span class="text-2xl">{{ icon }}</span>
                </div>
                <div class="flex-1">
                  <p class="text-gray-700 whitespace-pre-line">{{ message }}</p>
                </div>
              </div>
            </slot>
          </div>
          
          <!-- Footer -->
          <div v-if="$slots.footer || showDefaultButtons" class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <slot name="footer">
              <button
                v-if="showCancelButton"
                @click="$emit('cancel')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 transition-colors"
              >
                {{ cancelText }}
              </button>
              <button
                @click="$emit('confirm')"
                class="px-4 py-2 text-sm font-medium text-white rounded-md transition-colors"
                :class="confirmButtonClass"
              >
                {{ confirmText }}
              </button>
            </slot>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  message: {
    type: String,
    default: ''
  },
  type: {
    type: String,
    default: 'info', // info, success, warning, error
    validator: (value) => ['info', 'success', 'warning', 'error'].includes(value)
  },
  size: {
    type: String,
    default: 'md', // sm, md, lg, xl
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true
  },
  showCloseButton: {
    type: Boolean,
    default: true
  },
  showDefaultButtons: {
    type: Boolean,
    default: true
  },
  showCancelButton: {
    type: Boolean,
    default: false
  },
  confirmText: {
    type: String,
    default: 'OK'
  },
  cancelText: {
    type: String,
    default: 'Cancel'
  }
})

defineEmits(['close', 'confirm', 'cancel'])

const modalClass = computed(() => {
  const classes = []
  
  // Size classes
  switch (props.size) {
    case 'sm':
      classes.push('max-w-sm')
      break
    case 'md':
      classes.push('max-w-md')
      break
    case 'lg':
      classes.push('max-w-lg')
      break
    case 'xl':
      classes.push('max-w-xl')
      break
  }
  
  return classes.join(' ')
})

const confirmButtonClass = computed(() => {
  switch (props.type) {
    case 'success':
      return 'bg-green-600 hover:bg-green-700'
    case 'warning':
      return 'bg-yellow-600 hover:bg-yellow-700'
    case 'error':
      return 'bg-red-600 hover:bg-red-700'
    default:
      return 'bg-blue-600 hover:bg-blue-700'
  }
})

const icon = computed(() => {
  switch (props.type) {
    case 'success':
      return '✅'
    case 'warning':
      return '⚠️'
    case 'error':
      return '❌'
    case 'info':
    default:
      return 'ℹ️'
  }
})
</script>