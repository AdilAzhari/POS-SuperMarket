<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Eye, EyeOff, Mail, Lock, Shield } from 'lucide-vue-next'
import { ref } from 'vue'

const props = defineProps({
  email: {
    type: String,
    required: true,
  },
  token: {
    type: String,
    required: true,
  },
})

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const submit = () => {
  form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <GuestLayout>
    <Head title="Set New Password - POS SuperMarket" />
    
    <div class="space-y-8">
      <!-- Header -->
      <div class="text-center">
        <div class="bg-blue-100 rounded-full p-3 w-16 h-16 mx-auto mb-4 flex items-center justify-center">
          <Shield class="w-8 h-8 text-blue-600" />
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">Set New Password</h2>
        <p class="text-gray-600">Enter your new password below</p>
      </div>

      <!-- Reset Form -->
      <form @submit.prevent="submit" class="space-y-6">
        <!-- Email Field (Read-only) -->
        <div class="space-y-2">
          <label for="email" class="block text-sm font-medium text-gray-700">
            Email Address
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Mail class="h-5 w-5 text-gray-400" />
            </div>
            <input
              id="email"
              v-model="form.email"
              type="email"
              readonly
              class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed"
            />
          </div>
          <p v-if="form.errors.email" class="text-red-600 text-sm">{{ form.errors.email }}</p>
        </div>

        <!-- New Password Field -->
        <div class="space-y-2">
          <label for="password" class="block text-sm font-medium text-gray-700">
            New Password
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Lock class="h-5 w-5 text-gray-400" />
            </div>
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              required
              autofocus
              class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
              :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': form.errors.password }"
              placeholder="Enter your new password"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
              <Eye v-if="!showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" />
              <EyeOff v-else class="h-5 w-5 text-gray-400 hover:text-gray-600" />
            </button>
          </div>
          <p v-if="form.errors.password" class="text-red-600 text-sm">{{ form.errors.password }}</p>
        </div>

        <!-- Confirm Password Field -->
        <div class="space-y-2">
          <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
            Confirm New Password
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Lock class="h-5 w-5 text-gray-400" />
            </div>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              :type="showPasswordConfirmation ? 'text' : 'password'"
              autocomplete="new-password"
              required
              class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
              :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': form.errors.password_confirmation }"
              placeholder="Confirm your new password"
            />
            <button
              type="button"
              @click="showPasswordConfirmation = !showPasswordConfirmation"
              class="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
              <Eye v-if="!showPasswordConfirmation" class="h-5 w-5 text-gray-400 hover:text-gray-600" />
              <EyeOff v-else class="h-5 w-5 text-gray-400 hover:text-gray-600" />
            </button>
          </div>
          <p v-if="form.errors.password_confirmation" class="text-red-600 text-sm">{{ form.errors.password_confirmation }}</p>
        </div>

        <!-- Password Requirements -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-2">Password Requirements:</h4>
          <ul class="text-sm text-gray-600 space-y-1">
            <li>• At least 8 characters long</li>
            <li>• Mix of uppercase and lowercase letters</li>
            <li>• At least one number</li>
            <li>• At least one special character</li>
          </ul>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="form.processing">Updating password...</span>
          <span v-else class="flex items-center space-x-2">
            <Shield class="h-4 w-4" />
            <span>Update Password</span>
          </span>
        </button>
      </form>
    </div>
  </GuestLayout>
</template>
