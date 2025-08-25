<script setup>
import { ShoppingCart, Store, BarChart3 } from 'lucide-vue-next'
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 flex flex-col lg:flex-row">
    <!-- Left Panel - Branding -->
    <div class="lg:w-1/2 flex flex-col justify-center items-center p-8 lg:p-12 text-white relative overflow-hidden">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-20 left-20 w-32 h-32 border border-white/20 rounded-full"></div>
        <div class="absolute top-40 right-20 w-24 h-24 border border-white/20 rounded-full"></div>
        <div class="absolute bottom-32 left-1/4 w-16 h-16 border border-white/20 rounded-full"></div>
        <div class="absolute bottom-20 right-1/3 w-20 h-20 border border-white/20 rounded-full"></div>
      </div>
      
      <!-- Content -->
      <div class="text-center z-10 max-w-lg">
        <!-- Logo/Brand -->
        <div class="mb-8">
          <div class="bg-white/10 backdrop-blur-sm rounded-full p-6 w-24 h-24 mx-auto mb-4 flex items-center justify-center">
            <ShoppingCart class="w-12 h-12 text-white" />
          </div>
          <h1 class="text-4xl lg:text-5xl font-bold mb-2">POS SuperMarket</h1>
          <p class="text-blue-200 text-lg">Modern Point of Sale System</p>
        </div>

        <!-- Features -->
        <div class="space-y-6">
          <div class="flex items-center space-x-4 bg-white/5 backdrop-blur-sm rounded-lg p-4">
            <div class="bg-white/10 rounded-lg p-2">
              <Store class="w-6 h-6 text-blue-200" />
            </div>
            <div class="text-left">
              <h3 class="font-semibold">Inventory Management</h3>
              <p class="text-sm text-blue-200">Real-time stock tracking and alerts</p>
            </div>
          </div>
          
          <div class="flex items-center space-x-4 bg-white/5 backdrop-blur-sm rounded-lg p-4">
            <div class="bg-white/10 rounded-lg p-2">
              <BarChart3 class="w-6 h-6 text-blue-200" />
            </div>
            <div class="text-left">
              <h3 class="font-semibold">Sales Analytics</h3>
              <p class="text-sm text-blue-200">Comprehensive reports and insights</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Panel - Auth Form -->
    <div class="lg:w-1/2 flex items-center justify-center p-6 lg:p-12 bg-white relative">
      <!-- Decorative Elements -->
      <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-blue-100 to-transparent rounded-bl-full"></div>
      <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-indigo-100 to-transparent rounded-tr-full"></div>
      
      <div class="w-full max-w-md z-10">
        <slot />
      </div>
    </div>
  </div>
</template>
