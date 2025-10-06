<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Eye, EyeOff, Mail, Lock, User, UserPlus, AlertCircle, CheckCircle2 } from 'lucide-vue-next'
import { ref, computed } from 'vue'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const submit = () => {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}

const hasErrors = computed(() => {
  return Object.keys(form.errors).length > 0
})

const passwordStrength = computed(() => {
  const password = form.password
  if (!password) return { strength: 0, label: '', color: '' }

  let strength = 0
  if (password.length >= 8) strength++
  if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++
  if (password.match(/\d/)) strength++
  if (password.match(/[^a-zA-Z\d]/)) strength++

  const labels = ['Weak', 'Fair', 'Good', 'Strong']
  const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500']

  return {
    strength,
    label: labels[strength - 1] || '',
    color: colors[strength - 1] || ''
  }
})

const passwordsMatch = computed(() => {
  if (!form.password || !form.password_confirmation) return null
  return form.password === form.password_confirmation
})
</script>

<template>
  <GuestLayout>
    <Head title="Create Account - POS SuperMarket" />

    <div class="space-y-8">
      <!-- Header -->
      <div class="text-center space-y-3">
        <div class="flex justify-center">
          <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-4 rounded-2xl">
            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
          </div>
        </div>
        <h2 class="text-3xl font-bold text-gray-900">Create Account</h2>
        <p class="text-gray-600">Join POS SuperMarket and manage your business</p>
      </div>

      <!-- Error Summary -->
      <div v-if="hasErrors" class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
        <div class="flex">
          <div class="flex-shrink-0">
            <AlertCircle class="h-5 w-5 text-red-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
            <div class="mt-2 text-sm text-red-700">
              <ul class="list-disc list-inside space-y-1">
                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Register Form -->
      <form @submit.prevent="submit" class="space-y-6">
        <!-- Name Field -->
        <div class="space-y-2">
          <label for="name" class="block text-sm font-semibold text-gray-700">
            Full Name
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <User class="h-5 w-5 text-gray-400" />
            </div>
            <input
              id="name"
              v-model="form.name"
              type="text"
              autocomplete="name"
              required
              autofocus
              class="block w-full pl-10 pr-3 py-3.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm"
              :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500 bg-red-50': form.errors.name }"
              placeholder="John Doe"
            />
          </div>
          <p v-if="form.errors.name" class="text-red-600 text-sm flex items-center gap-1">
            <AlertCircle class="h-4 w-4" />
            {{ form.errors.name }}
          </p>
        </div>

        <!-- Email Field -->
        <div class="space-y-2">
          <label for="email" class="block text-sm font-semibold text-gray-700">
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
              autocomplete="username"
              required
              class="block w-full pl-10 pr-3 py-3.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm"
              :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500 bg-red-50': form.errors.email }"
              placeholder="you@example.com"
            />
          </div>
          <p v-if="form.errors.email" class="text-red-600 text-sm flex items-center gap-1">
            <AlertCircle class="h-4 w-4" />
            {{ form.errors.email }}
          </p>
        </div>

        <!-- Password Field -->
        <div class="space-y-2">
          <label for="password" class="block text-sm font-semibold text-gray-700">
            Password
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
              class="block w-full pl-10 pr-12 py-3.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm"
              :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500 bg-red-50': form.errors.password }"
              placeholder="Create a strong password"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute inset-y-0 right-0 pr-3 flex items-center hover:text-gray-700 transition-colors"
              tabindex="-1"
            >
              <Eye v-if="!showPassword" class="h-5 w-5 text-gray-400" />
              <EyeOff v-else class="h-5 w-5 text-gray-600" />
            </button>
          </div>

          <!-- Password Strength Indicator -->
          <div v-if="form.password" class="space-y-1">
            <div class="flex items-center gap-2">
              <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div
                  class="h-full transition-all duration-300"
                  :class="passwordStrength.color"
                  :style="{ width: `${(passwordStrength.strength / 4) * 100}%` }"
                ></div>
              </div>
              <span class="text-xs font-medium text-gray-600">{{ passwordStrength.label }}</span>
            </div>
            <p class="text-xs text-gray-500">Use 8+ characters with a mix of letters, numbers & symbols</p>
          </div>

          <p v-if="form.errors.password" class="text-red-600 text-sm flex items-center gap-1">
            <AlertCircle class="h-4 w-4" />
            {{ form.errors.password }}
          </p>
        </div>

        <!-- Confirm Password Field -->
        <div class="space-y-2">
          <label for="password_confirmation" class="block text-sm font-semibold text-gray-700">
            Confirm Password
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
              class="block w-full pl-10 pr-12 py-3.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm"
              :class="{
                'border-red-300 focus:ring-red-500 focus:border-red-500 bg-red-50': form.errors.password_confirmation || (passwordsMatch === false),
                'border-green-300 focus:ring-green-500 focus:border-green-500 bg-green-50': passwordsMatch === true
              }"
              placeholder="Re-enter your password"
            />
            <button
              type="button"
              @click="showPasswordConfirmation = !showPasswordConfirmation"
              class="absolute inset-y-0 right-0 pr-3 flex items-center hover:text-gray-700 transition-colors"
              tabindex="-1"
            >
              <Eye v-if="!showPasswordConfirmation" class="h-5 w-5 text-gray-400" />
              <EyeOff v-else class="h-5 w-5 text-gray-600" />
            </button>
          </div>

          <!-- Password Match Indicator -->
          <p v-if="passwordsMatch === true" class="text-green-600 text-sm flex items-center gap-1">
            <CheckCircle2 class="h-4 w-4" />
            Passwords match
          </p>
          <p v-else-if="passwordsMatch === false" class="text-red-600 text-sm flex items-center gap-1">
            <AlertCircle class="h-4 w-4" />
            Passwords do not match
          </p>

          <p v-if="form.errors.password_confirmation" class="text-red-600 text-sm flex items-center gap-1">
            <AlertCircle class="h-4 w-4" />
            {{ form.errors.password_confirmation }}
          </p>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3.5 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
        >
          <span v-if="form.processing" class="flex items-center space-x-2">
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Creating account...</span>
          </span>
          <span v-else class="flex items-center space-x-2">
            <UserPlus class="h-4 w-4" />
            <span>Create Account</span>
          </span>
        </button>

        <!-- Terms Notice -->
        <p class="text-xs text-center text-gray-500">
          By creating an account, you agree to our Terms of Service and Privacy Policy
        </p>

        <!-- Login Link -->
        <div class="text-center pt-4 border-t border-gray-200">
          <p class="text-sm text-gray-600">
            Already have an account?
            <Link
              href="/login"
              class="text-blue-600 hover:text-blue-800 font-semibold transition duration-200 hover:underline"
            >
              Sign In
            </Link>
          </p>
        </div>
      </form>
    </div>
  </GuestLayout>
</template>
