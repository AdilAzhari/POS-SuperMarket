<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Mail, ArrowLeft, Send } from 'lucide-vue-next'

defineProps({
  status: {
    type: String,
  },
})

const form = useForm({
  email: '',
})

const submit = () => {
  form.post(route('password.email'))
}
</script>

<template>
  <GuestLayout>
    <Head title="Reset Password - POS SuperMarket" />
    
    <div class="space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-2">Reset Password</h2>
        <p class="text-gray-600">Enter your email to receive a password reset link</p>
      </div>

      <!-- Info Message -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-sm text-blue-800">
          Forgot your password? No problem. Just let us know your email address and we will send you a
          password reset link that will allow you to choose a new one.
        </p>
      </div>

      <!-- Status Message -->
      <div v-if="status" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
        {{ status }}
      </div>

      <!-- Reset Form -->
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
              placeholder="Enter your email address"
            />
          </div>
          <p v-if="form.errors.email" class="text-red-600 text-sm">{{ form.errors.email }}</p>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="form.processing">Sending reset link...</span>
          <span v-else class="flex items-center space-x-2">
            <Send class="h-4 w-4" />
            <span>Send Reset Link</span>
          </span>
        </button>

        <!-- Back to Login -->
        <div class="text-center pt-4 border-t border-gray-200">
          <Link
            :href="route('login')"
            class="inline-flex items-center space-x-2 text-blue-600 hover:text-blue-500 font-medium transition duration-200"
          >
            <ArrowLeft class="h-4 w-4" />
            <span>Back to Sign In</span>
          </Link>
        </div>
      </form>
    </div>
  </GuestLayout>
</template>
