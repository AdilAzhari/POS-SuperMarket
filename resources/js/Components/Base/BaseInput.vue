<template>
  <div class="space-y-1">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-gray-700">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <div class="relative">
      <component
        :is="leftIcon"
        v-if="leftIcon"
        class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"
      />

      <input
        :id="inputId"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :class="inputClasses"
        v-bind="$attrs"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />

      <component
        :is="rightIcon"
        v-if="rightIcon"
        class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"
      />
    </div>

    <div v-if="error || hint" class="text-sm">
      <p v-if="error" class="text-red-600">{{ error }}</p>
      <p v-else-if="hint" class="text-gray-500">{{ hint }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  type: {
    type: String,
    default: 'text',
    validator: value => ['text', 'email', 'password', 'number', 'tel', 'url'].includes(value),
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  leftIcon: {
    type: Object,
    default: null,
  },
  rightIcon: {
    type: Object,
    default: null,
  },
  size: {
    type: String,
    default: 'md',
    validator: value => ['sm', 'md', 'lg'].includes(value),
  },
})

const emit = defineEmits(['update:modelValue', 'focus', 'blur'])

const inputId = ref(`input-${Math.random().toString(36).substring(2, 9)}`)

const inputClasses = computed(() => {
  const base = 'block w-full rounded-lg border transition-colors focus:outline-none focus:ring-2'

  const states = props.error
    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'

  const sizes = {
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-3 py-2 text-base',
    lg: 'px-4 py-3 text-lg',
  }

  const padding = props.leftIcon
    ? sizes[props.size].replace('px-3', 'pl-10 pr-3')
    : props.rightIcon
      ? sizes[props.size].replace('px-3', 'pl-3 pr-10')
      : sizes[props.size]

  const disabled = props.disabled
    ? 'bg-gray-50 text-gray-500 cursor-not-allowed'
    : 'bg-white text-gray-900'

  return [base, states, padding, disabled].join(' ')
})

const handleInput = event => {
  const target = event.target
  let value = target.value

  if (props.type === 'number') {
    value = target.valueAsNumber || 0
  }

  emit('update:modelValue', value)
}

const handleFocus = event => {
  emit('focus', event)
}

const handleBlur = event => {
  emit('blur', event)
}
</script>
