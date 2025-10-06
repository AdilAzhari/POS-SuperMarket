<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Eye, EyeOff, Mail, Lock, ArrowRight, AlertCircle } from 'lucide-vue-next'
import { ref, computed } from 'vue'

defineProps({
  canResetPassword: {
    type: Boolean,
    default: true
  },
  status: {
    type: String,
  },
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}

// Check if user is already authenticated
const checkAuth = () => {
  if (window.location.pathname === '/dashboard') {
    return
  }
}

const hasErrors = computed(() => {
  return Object.keys(form.errors).length > 0
})
</script>

<template>
  <GuestLayout>
    <Head title="Sign In - POS SuperMarket" />

    <div class="space-y-8">
      <!-- Header -->
      <div class="text-center space-y-3">
        <div class="flex justify-center">
          <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-4 rounded-2xl">
            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
        </div>
        <h2 class="text-3xl font-bold text-gray-900">Welcome Back</h2>
        <p class="text-gray-600">Sign in to access your POS dashboard</p>
      </div>

      <!-- Status Message -->
      <div v-if="status" class="bg-green-50 border-l-4 border-green-400 p-4 rounded-lg">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-green-800">{{ status }}</p>
          </div>
        </div>
      </div>

      <!-- Error Summary -->
      <div v-if="hasErrors" class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
        <div class="flex">
          <div class="flex-shrink-0">
            <AlertCircle class="h-5 w-5 text-red-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
            <div class="mt-2 text-sm text-red-700">
              <ul class="list-disc list-inside space-y-1">
                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="submit" class="space-y-6">
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
              autofocus
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
              autocomplete="current-password"
              required
              class="block w-full pl-10 pr-12 py-3.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm"
              :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500 bg-red-50': form.errors.password }"
              placeholder="Enter your password"
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
          <p v-if="form.errors.password" class="text-red-600 text-sm flex items-center gap-1">
            <AlertCircle class="h-4 w-4" />
            {{ form.errors.password }}
          </p>
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
          <label class="flex items-center cursor-pointer">
            <input
              v-model="form.remember"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer"
            />
            <span class="ml-2 text-sm text-gray-700 select-none">Remember me for 30 days</span>
          </label>

          <Link
            v-if="canResetPassword"
            href="/forgot-password"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium transition duration-200 hover:underline"
          >
            Forgot password?
          </Link>
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
            <span>Signing in...</span>
          </span>
          <span v-else class="flex items-center space-x-2">
            <span>Sign In</span>
            <ArrowRight class="h-4 w-4" />
          </span>
        </button>

        <!-- Demo Credentials -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <p class="text-sm text-blue-800 font-medium mb-1">Demo Credentials:</p>
          <p class="text-xs text-blue-700">Email: admin@supermarket.com</p>
          <p class="text-xs text-blue-700">Password: supermarket</p>
        </div>

        <!-- Register Link -->
        <div class="text-center pt-4 border-t border-gray-200">
          <p class="text-sm text-gray-600">
            Don't have an account?
            <Link
              href="/register"
              class="text-blue-600 hover:text-blue-800 font-semibold transition duration-200 hover:underline"
            >
              Create Account
            </Link>
          </p>
        </div>
      </form>
    </div>
  </GuestLayout>
</template>
