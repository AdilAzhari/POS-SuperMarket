<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center">
            <Store class="h-8 w-8 text-blue-600 mr-3" />
            <h1 class="text-xl font-semibold text-gray-900">POS System</h1>
          </div>

          <div class="flex items-center space-x-4">
            <Link
              :href="route('dashboard')"
              class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"
            >
              ← Back to Dashboard
            </Link>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <CategoryManagement />
    </main>
  </div>
</template>

<script setup lang="ts">
import { Store } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
import CategoryManagement from '@/Components/CategoryManagement.vue'
</script>
