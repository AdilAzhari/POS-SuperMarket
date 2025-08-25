<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Eye, EyeOff, Mail, Lock, ArrowRight } from 'lucide-vue-next'
import { ref } from 'vue'

defineProps({
  canResetPassword: {
    type: Boolean,
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
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <GuestLayout>
    <Head title="Sign In - POS SuperMarket" />
    
    <div class="space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h2>
        <p class="text-gray-600">Sign in to your POS SuperMarket account</p>
      </div>

      <!-- Status Message -->
      <div v-if="status" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
        {{ status }}
      </div>

      <!-- Login Form -->
      <form @submit.prevent="submit" class="space-y-6">
        <!-- Email Field -->
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
              autocomplete="email"
              required
              autofocus
              class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
              :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': form.errors.email }"
              placeholder="Enter your email"
            />
          </div>
          <p v-if="form.errors.email" class="text-red-600 text-sm">{{ form.errors.email }}</p>
        </div>

        <!-- Password Field -->
        <div class="space-y-2">
          <label for="password" class="block text-sm font-medium text-gray-700">
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
              class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
              :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': form.errors.password }"
              placeholder="Enter your password"
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

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
          <label class="flex items-center">
            <input
              v-model="form.remember"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">Remember me</span>
          </label>
          
          <Link
            v-if="canResetPassword"
            :href="route('password.request')"
            class="text-sm text-blue-600 hover:text-blue-500 font-medium transition duration-200"
          >
            Forgot password?
          </Link>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="form.processing">Signing in...</span>
          <span v-else class="flex items-center space-x-2">
            <span>Sign In</span>
            <ArrowRight class="h-4 w-4" />
          </span>
        </button>

        <!-- Register Link -->
        <div class="text-center pt-4 border-t border-gray-200">
          <p class="text-sm text-gray-600">
            Don't have an account?
            <Link
              :href="route('register')"
              class="text-blue-600 hover:text-blue-500 font-medium transition duration-200"
            >
              Create Account
            </Link>
          </p>
        </div>
      </form>
    </div>
  </GuestLayout>
</template>
