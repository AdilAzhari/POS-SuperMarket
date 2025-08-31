<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Eye, EyeOff, Mail, Lock, User, UserPlus } from 'lucide-vue-next'
import { ref } from 'vue'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <GuestLayout>
    <Head title="Create Account - POS SuperMarket" />
    
    <div class="space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-2">Create Account</h2>
        <p class="text-gray-600">Join POS SuperMarket and start managing your business</p>
      </div>

      <!-- Register Form -->
      <form @submit.prevent="submit" class="space-y-6">
        <!-- Name Field -->
        <div class="space-y-2">
          <label for="name" class="block text-sm font-medium text-gray-700">
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
              class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
              :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': form.errors.name }"
              placeholder="Enter your full name"
            />
          </div>
          <p v-if="form.errors.name" class="text-red-600 text-sm">{{ form.errors.name }}</p>
        </div>

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
              autocomplete="new-password"
              required
              class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
              :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': form.errors.password }"
              placeholder="Create a password"
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
              class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
              :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': form.errors.password_confirmation }"
              placeholder="Confirm your password"
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

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="form.processing">Creating account...</span>
          <span v-else class="flex items-center space-x-2">
            <UserPlus class="h-4 w-4" />
            <span>Create Account</span>
          </span>
        </button>

        <!-- Login Link -->
        <div class="text-center pt-4 border-t border-gray-200">
          <p class="text-sm text-gray-600">
            Already have an account?
            <Link
              :href="route('login')"
              class="text-blue-600 hover:text-blue-500 font-medium transition duration-200"
            >
              Sign In
            </Link>
          </p>
        </div>
      </form>
    </div>
  </GuestLayout>
</template>
