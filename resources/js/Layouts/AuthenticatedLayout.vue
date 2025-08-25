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
  Building2,
  Monitor,
  RotateCcw,
} from 'lucide-vue-next'

import { Link, usePage } from '@inertiajs/vue3'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import NotificationPanel from '@/Components/NotificationPanel.vue'
import NotificationDropdown from '@/Components/NotificationDropdown.vue'
import { useNotificationStore } from '@/stores/notifications'

const appStore = useAppStore()
const notificationStore = useNotificationStore()
const page = usePage()

// Get current user
const currentUser = computed(() => page.props.auth?.user)

// Sidebar state - start collapsed
const isSidebarOpen = ref(false)
const isMobile = ref(false)

// Check if mobile screen
const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024
  // Auto-close sidebar on mobile by default
  if (isMobile.value) {
    isSidebarOpen.value = false
  }
}

// Navigation items with better icons
const navigationItems = [
  { id: 'pos', name: 'POS', icon: ShoppingCart },
  { id: 'manager-dashboard', name: 'Manager Dashboard', icon: Monitor },
  { id: 'products', name: 'Products', icon: Package },
  { id: 'sales', name: 'Sales History', icon: History },
  { id: 'stock', name: 'Stock Management', icon: Warehouse },
  { id: 'inventory', name: 'Inventory', icon: Archive },
  { id: 'customers', name: 'Customers', icon: Users },
  { id: 'employees', name: 'Employees', icon: User },
  { id: 'categories', name: 'Categories', icon: Tag },
  { id: 'suppliers', name: 'Suppliers', icon: Truck },
  { id: 'stores', name: 'Store Management', icon: Building2 },
  { id: 'payments', name: 'Payments', icon: CreditCard },
  { id: 'returns', name: 'Product Returns', icon: RotateCcw },
  { id: 'reports', name: 'Reports', icon: BarChart3 },
  { id: 'settings', name: 'Settings', icon: Settings },
]

// Filter navigation items based on user role
const filteredNavigationItems = computed(() => {
  return navigationItems.filter(item => {
    if (!item.roles) return true // No role restrictions
    return item.roles.includes(currentUser.value?.role)
  })
})

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
  checkMobile()
}

// Add event listeners
onMounted(() => {
  checkMobile() // Initial check
  document.addEventListener('keydown', handleKeydown)
  window.addEventListener('resize', handleResize)
  appStore.fetchStores() // Load stores data
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

// Get current view icon
const getCurrentViewIcon = () => {
  const currentItem = navigationItems.find(item => item.id === appStore.currentView)
  return currentItem ? currentItem.icon : ShoppingCart
}

// Get current view description
const getCurrentViewDescription = () => {
  const descriptions = {
    pos: 'Point of Sale Terminal',
    'manager-dashboard': 'Real-time business insights',
    products: 'Manage your inventory',
    sales: 'Transaction history',
    stock: 'Inventory adjustments',
    inventory: 'Stock overview',
    customers: 'Customer database',
    employees: 'Staff management',
    categories: 'Product categories',
    suppliers: 'Vendor management',
    stores: 'Location settings',
    payments: 'Financial transactions',
    returns: 'Product returns',
    reports: 'Business analytics',
    settings: 'System configuration'
  }
  return descriptions[appStore.currentView] || 'Point of Sale System'
}

</script>

<template>
  <div id="app" class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <div :class="[
      'bg-white shadow-lg border-r border-gray-200 transition-all duration-300 flex flex-col z-30 fixed h-full',
      isSidebarOpen ? 'w-64' : 'w-16',
      !isSidebarOpen && 'items-center',
      // Mobile responsiveness
      'lg:translate-x-0',
      isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
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
            class="p-2 rounded-md bg-white border border-gray-200 hover:bg-gray-50 shadow-sm transition-all duration-200"
            :title="(isSidebarOpen ? 'Collapse sidebar' : 'Expand sidebar') + ' (Ctrl+B)'"
          >
            <component
              :is="isSidebarOpen ? 'ChevronLeft' : 'ChevronRight'"
              class="h-5 w-5 text-gray-700 hover:text-blue-600 transition-colors"
            />
          </button>
        </div>

        <!-- Collapsed view -->
        <div v-else class="flex flex-col items-center space-y-2">
          <Store class="h-8 w-8 text-blue-600" />
          <button
            @click="toggleSidebar"
            class="p-2 rounded-md bg-white border border-gray-200 hover:bg-gray-50 shadow-sm transition-all duration-200"
            :title="'Expand sidebar (Ctrl+B)'"
          >
            <ChevronRight class="h-5 w-5 text-gray-700 hover:text-blue-600 transition-colors" />
          </button>
        </div>
      </div>

      <!-- Navigation Items -->
      <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
        <button
          v-for="item in filteredNavigationItems"
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
            <option
              v-for="store in appStore.stores"
              :key="store.id"
              :value="store.id"
            >
              {{ store.name }}
            </option>
          </select>

          <div class="flex items-center justify-center">
            <Dropdown align="right" width="48">
              <template #trigger>
                <button class="flex items-center space-x-2 p-2 rounded-md hover:bg-gray-100 transition-colors w-full justify-center">
                  <User class="h-4 w-4 text-gray-600" />
                  <span class="text-sm text-gray-600">Menu</span>
                  <ChevronDown class="h-3 w-3 text-gray-400" />
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

    <!-- Mobile Overlay -->
    <div
      v-if="isSidebarOpen && isMobile"
      class="lg:hidden fixed inset-0 z-20 bg-black bg-opacity-50 transition-opacity duration-300"
      @click="toggleSidebar"
    ></div>

    <!-- Main Content Area -->
    <div :class="[
      'flex-1 flex flex-col min-w-0 relative transition-all duration-300',
      isSidebarOpen ? 'lg:ml-64' : 'lg:ml-16',
      'ml-0'
    ]">
      <!-- Top Header -->
      <header class="bg-gradient-to-r from-white to-gray-50 shadow-md border-b border-gray-200 h-20 flex items-center justify-between px-6">
        <div class="flex items-center space-x-6">
          <!-- Mobile Toggle Button -->
          <button
            @click="toggleSidebar"
            class="p-2 rounded-lg hover:bg-white hover:shadow-sm transition-all duration-200 lg:hidden"
            :title="(isSidebarOpen ? 'Collapse sidebar' : 'Expand sidebar') + ' (Ctrl+B)'"
          >
            <Menu class="h-5 w-5 text-gray-600" />
          </button>

          <!-- Page Title with Icon -->
          <div class="flex items-center space-x-3">
            <div class="p-2 bg-blue-100 rounded-lg">
              <component
                :is="getCurrentViewIcon()"
                class="h-5 w-5 text-blue-600"
              />
            </div>
            <div>
              <h1 class="text-xl font-bold text-gray-900 capitalize">
                {{ getCurrentViewTitle() }}
              </h1>
              <p class="text-sm text-gray-500">{{ getCurrentViewDescription() }}</p>
            </div>
          </div>
        </div>

        <div class="flex items-center space-x-6">
          <!-- Quick Actions -->
          <div class="hidden md:flex items-center space-x-2">
            <button
              v-if="appStore.currentView === 'pos'"
              class="flex items-center space-x-2 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
            >
              <ShoppingCart class="h-4 w-4" />
              <span>New Sale</span>
            </button>
            <button
              v-if="appStore.currentView === 'products'"
              class="flex items-center space-x-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
            >
              <Package class="h-4 w-4" />
              <span>Add Product</span>
            </button>
          </div>

          <!-- Store Selector (visible on larger screens) -->
          <div class="hidden lg:block">
            <select
              v-model="appStore.selectedStore"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
            >
              <option
                v-for="store in appStore.stores"
                :key="store.id"
                :value="store.id"
              >
                {{ store.name }}
              </option>
            </select>
          </div>

          <!-- Notifications -->
          <NotificationDropdown />

          <!-- User Menu -->
          <div class="flex items-center">
            <Dropdown align="right" width="64">
              <template #trigger>
                <button class="flex items-center space-x-3 px-4 py-2 rounded-xl hover:bg-white hover:shadow-sm transition-all duration-200 border border-gray-200" title="User Menu">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center">
                      <span class="text-white text-sm font-semibold">
                        {{ ($page.props.auth?.user?.name || 'G').charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div class="hidden sm:block text-left">
                      <div class="text-sm font-semibold text-gray-900">{{ $page.props.auth?.user?.name || 'Guest User' }}</div>
                      <div class="text-xs text-gray-500 capitalize">{{ $page.props.auth?.user?.role || 'Guest' }}</div>
                    </div>
                  </div>
                  <ChevronDown class="h-4 w-4 text-gray-400" />
                </button>
              </template>
              <template #content>
                <div class="px-4 py-3 border-b border-gray-100">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center">
                      <span class="text-white font-semibold">
                        {{ ($page.props.auth?.user?.name || 'G').charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div>
                      <div class="font-semibold text-gray-900">{{ $page.props.auth?.user?.name || 'Guest User' }}</div>
                      <div class="text-sm text-gray-500">{{ $page.props.auth?.user?.email || 'No email' }}</div>
                      <div class="text-xs text-blue-600 capitalize">{{ $page.props.auth?.user?.role || 'Guest' }}</div>
                    </div>
                  </div>
                </div>
                <DropdownLink :href="route('profile.edit')">
                  <div class="flex items-center space-x-2">
                    <User class="h-4 w-4" />
                    <span>Profile Settings</span>
                  </div>
                </DropdownLink>
                <DropdownLink :href="route('logout')" method="post" as="button">
                  <div class="flex items-center space-x-2 text-red-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Sign Out</span>
                  </div>
                </DropdownLink>
              </template>
            </Dropdown>
          </div>
        </div>
      </header>

      <!-- Main Content -->
      <main class="flex-1 p-6 overflow-auto">
        <slot />
      </main>
    </div>

    <!-- Notification Panel -->
    <NotificationPanel />
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
