<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useAppStore } from '@/stores/app'
import {
  Store,
  User,
  ShoppingCart,
  Package,
  History,
  Warehouse,
  BarChart3,
  Users,
  Archive,
  Settings,
  Menu,
  Bell,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  Tag,
  Truck,
  CreditCard,
} from 'lucide-vue-next'

import { Link } from '@inertiajs/vue3'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'

const appStore = useAppStore()

// Sidebar state - responsive default
const isSidebarOpen = ref(window.innerWidth >= 1024)

// Navigation items with better icons
const navigationItems = [
  { id: 'pos', name: 'POS', icon: ShoppingCart },
  { id: 'products', name: 'Products', icon: Package },
  { id: 'sales', name: 'Sales History', icon: History },
  { id: 'stock', name: 'Stock Management', icon: Warehouse },
  { id: 'inventory', name: 'Inventory', icon: Archive },
  { id: 'customers', name: 'Customers', icon: Users },
  { id: 'categories', name: 'Categories', icon: Tag },
  { id: 'suppliers', name: 'Suppliers', icon: Truck },
  { id: 'payments', name: 'Payments', icon: CreditCard },
  { id: 'reports', name: 'Reports', icon: BarChart3 },
  { id: 'settings', name: 'Settings', icon: Settings },
]


// Sidebar toggle function
const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

// Keyboard shortcut for sidebar toggle
const handleKeydown = (event) => {
  if (event.ctrlKey && event.key === 'b') {
    event.preventDefault()
    toggleSidebar()
  }
}

// Handle responsive behavior
const handleResize = () => {
  if (window.innerWidth < 1024) {
    isSidebarOpen.value = false
  }
}

// Add event listeners
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('resize', handleResize)
})

// Get current view title
const getCurrentViewTitle = () => {
  const currentItem = navigationItems.find(item => item.id === appStore.currentView)
  return currentItem ? currentItem.name : 'POS'
}
</script>

<template>
  <div id="app" class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <div :class="[
      'bg-white shadow-lg border-r border-gray-200 transition-all duration-300 flex flex-col z-10',
      isSidebarOpen ? 'w-64' : 'w-16',
      !isSidebarOpen && 'items-center'
    ]">
      <!-- Sidebar Header -->
      <div :class="[
        'flex items-center h-16 border-b border-gray-200',
        isSidebarOpen ? 'justify-between px-4' : 'flex-col justify-center px-2 space-y-2'
      ]">
        <div v-if="isSidebarOpen" class="flex items-center">
          <Store class="h-8 w-8 text-blue-600 mr-3" />
          <h1 class="text-xl font-semibold text-gray-900">POS System</h1>
        </div>
        
        <div v-if="isSidebarOpen">
          <button
            @click="toggleSidebar"
            class="p-2 rounded-md hover:bg-gray-100 transition-all duration-200 hover:shadow-md"
            :title="(isSidebarOpen ? 'Collapse sidebar' : 'Expand sidebar') + ' (Ctrl+B)'"
          >
            <component 
              :is="isSidebarOpen ? 'ChevronLeft' : 'ChevronRight'" 
              class="h-5 w-5 text-gray-500 hover:text-gray-700 transition-colors" 
            />
          </button>
        </div>
        
        <!-- Collapsed view -->
        <div v-else class="flex flex-col items-center space-y-2">
          <Store class="h-8 w-8 text-blue-600" />
          <button
            @click="toggleSidebar"
            class="p-1 rounded-md hover:bg-gray-100 transition-all duration-200 hover:shadow-md animate-pulse hover:animate-none"
            :title="'Expand sidebar (Ctrl+B)'"
          >
            <ChevronRight class="h-4 w-4 text-gray-500 hover:text-gray-700 transition-colors" />
          </button>
        </div>
      </div>

      <!-- Navigation Items -->
      <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
        <button
          v-for="item in navigationItems"
          :key="item.id"
          :class="[
            'group flex items-center w-full text-sm font-medium rounded-lg transition-all duration-200 relative',
            isSidebarOpen ? 'px-3 py-3' : 'px-2 py-3 justify-center',
            appStore.currentView === item.id
              ? 'bg-blue-50 text-blue-700'
              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
          ]"
          @click="appStore.setCurrentView(item.id)"
          :title="!isSidebarOpen ? item.name : ''"
        >
          <!-- Active indicator -->
          <div 
            v-if="appStore.currentView === item.id && isSidebarOpen" 
            class="absolute left-0 top-1/2 transform -translate-y-1/2 w-1 h-6 bg-blue-600 rounded-r-lg transition-all duration-200"
          ></div>
          
          <!-- Collapsed active indicator -->
          <div 
            v-if="appStore.currentView === item.id && !isSidebarOpen" 
            class="absolute inset-0 bg-blue-600 rounded-lg opacity-10"
          ></div>
          
          <component 
            :is="item.icon" 
            :class="[
              'h-5 w-5 transition-colors',
              appStore.currentView === item.id ? 'text-blue-700' : 'text-gray-400 group-hover:text-gray-500',
              isSidebarOpen ? 'mr-3' : 'mx-auto'
            ]" 
          />
          <span v-if="isSidebarOpen" class="truncate">{{ item.name }}</span>
        </button>
      </nav>

      <!-- Store Selector & User Info -->
      <div class="border-t border-gray-200 p-4">
        <div v-if="isSidebarOpen" class="space-y-3">
          <select
            v-model="appStore.selectedStore"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="main">Main Store</option>
            <option value="branch1">Branch 1</option>
            <option value="branch2">Branch 2</option>
          </select>
          
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2 text-sm text-gray-600">
              <User class="h-4 w-4" />
              <span class="truncate">{{ $page.props.auth?.user?.name || 'Guest User' }}</span>
            </div>
            <Dropdown align="right" width="48">
              <template #trigger>
                <button class="p-1 rounded-md hover:bg-gray-100">
                  <ChevronDown class="h-4 w-4 text-gray-400" />
                </button>
              </template>
              <template #content>
                <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                <DropdownLink :href="route('logout')" method="post" as="button">
                  Log Out
                </DropdownLink>
              </template>
            </Dropdown>
          </div>
        </div>
        <div v-else class="flex flex-col items-center space-y-2">
          <button 
            class="p-2 rounded-md hover:bg-gray-100 transition-colors" 
            title="Store: Main Store"
          >
            <Store class="h-5 w-5 text-gray-400 hover:text-gray-600" />
          </button>
          <Dropdown align="right" width="48">
            <template #trigger>
              <button 
                class="p-2 rounded-md hover:bg-gray-100 transition-colors" 
                :title="$page.props.auth?.user?.name || 'User Menu'"
              >
                <User class="h-5 w-5 text-gray-400 hover:text-gray-600" />
              </button>
            </template>
            <template #content>
              <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
              <DropdownLink :href="route('logout')" method="post" as="button">
                Log Out
              </DropdownLink>
            </template>
          </Dropdown>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 relative">
      <!-- Top Header -->
      <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-6">
        <div class="flex items-center space-x-4">
          <!-- Mobile Toggle Button -->
          <button
            @click="toggleSidebar"
            class="p-2 rounded-md hover:bg-gray-100 transition-colors lg:hidden"
            :title="(isSidebarOpen ? 'Collapse sidebar' : 'Expand sidebar') + ' (Ctrl+B)'"
          >
            <Menu class="h-5 w-5 text-gray-500" />
          </button>
          
          <h2 class="text-lg font-semibold text-gray-900 capitalize">
            {{ getCurrentViewTitle() }}
          </h2>
        </div>
        
        <div class="flex items-center space-x-4">
          <button class="p-2 rounded-md hover:bg-gray-100 relative" title="Notifications">
            <Bell class="h-5 w-5 text-gray-500" />
            <span class="absolute -top-1 -right-1 h-3 w-3 bg-red-500 rounded-full"></span>
          </button>
          
          <Dropdown align="right" width="48">
            <template #trigger>
              <button class="flex items-center space-x-2 px-3 py-2 rounded-md hover:bg-gray-100" title="User Menu">
                <User class="h-5 w-5 text-gray-500" />
                <span class="text-sm text-gray-700">{{ $page.props.auth?.user?.name || 'Guest' }}</span>
                <ChevronDown class="h-4 w-4 text-gray-400" />
              </button>
            </template>
            <template #content>
              <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
              <DropdownLink :href="route('logout')" method="post" as="button">
                Log Out
              </DropdownLink>
            </template>
          </Dropdown>
        </div>
      </header>

      <!-- Main Content -->
      <main class="flex-1 p-6 overflow-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
#app {
  font-family:
    'Inter',
    -apple-system,
    BlinkMacSystemFont,
    'Segoe UI',
    Roboto,
    sans-serif;
}
</style>
