<script setup>
import { computed } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Mail, Send, LogOut, CheckCircle } from 'lucide-vue-next'

const props = defineProps({
  status: {
    type: String,
  },
})

const form = useForm({})

const submit = () => {
  form.post(route('verification.send'))
}

const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
</script>

<template>
  <GuestLayout>
    <Head title="Verify Email - POS SuperMarket" />
    
    <div class="space-y-8">
      <!-- Header -->
      <div class="text-center">
        <div class="bg-blue-100 rounded-full p-3 w-16 h-16 mx-auto mb-4 flex items-center justify-center">
          <Mail class="w-8 h-8 text-blue-600" />
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">Verify Your Email</h2>
        <p class="text-gray-600">We've sent a verification link to your email address</p>
      </div>

      <!-- Main Message -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <p class="text-blue-800 text-center leading-relaxed">
          Thanks for signing up for POS SuperMarket! Before getting started, please verify your email address by clicking
          on the link we just emailed to you. If you didn't receive the email, we'll gladly send you another one.
        </p>
      </div>

      <!-- Success Message -->
      <div v-if="verificationLinkSent" class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-center space-x-3">
          <CheckCircle class="h-5 w-5 text-green-600 flex-shrink-0" />
          <p class="text-green-800 font-medium">
            A new verification link has been sent to your email address.
          </p>
        </div>
      </div>

      <!-- Actions -->
      <form @submit.prevent="submit" class="space-y-6">
        <!-- Resend Button -->
        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="form.processing">Sending verification email...</span>
          <span v-else class="flex items-center space-x-2">
            <Send class="h-4 w-4" />
            <span>Resend Verification Email</span>
          </span>
        </button>

        <!-- Logout Link -->
        <div class="text-center pt-4 border-t border-gray-200">
          <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="inline-flex items-center space-x-2 text-gray-600 hover:text-gray-500 font-medium transition duration-200"
          >
            <LogOut class="h-4 w-4" />
            <span>Log Out</span>
          </Link>
        </div>
      </form>

      <!-- Additional Help -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-2">Didn't receive the email?</h4>
        <ul class="text-sm text-gray-600 space-y-1">
          <li>• Check your spam or junk folder</li>
          <li>• Make sure the email address is correct</li>
          <li>• Try resending the verification email</li>
          <li>• Contact support if you continue having issues</li>
        </ul>
      </div>
    </div>
  </GuestLayout>
</template>
