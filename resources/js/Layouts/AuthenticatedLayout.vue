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
  RefreshCcw,
  ShoppingBag,
} from 'lucide-vue-next'

import { Link, usePage } from '@inertiajs/vue3'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import NotificationPanel from '@/Components/NotificationPanel.vue'
import NotificationDropdown from '@/Components/NotificationDropdown.vue'
import { useNotificationStore } from '@/stores/notifications'
import { useThemeStore } from '@/stores/theme'

const appStore = useAppStore()
const notificationStore = useNotificationStore()
const themeStore = useThemeStore()
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
  { id: 'reorder', name: 'Reorder Management', icon: ShoppingBag },
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
    reorder: 'Purchase order management',
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

// Format current time
const formatCurrentTime = () => {
  return new Date().toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

// Format current date
const formatCurrentDate = () => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric'
  })
}

</script>

<template>
  <div id="app" class="min-h-screen bg-gray-50 dark:bg-gray-900 flex transition-colors">
    <!-- Sidebar -->
    <div :class="[
      'bg-white dark:bg-gray-800 shadow-lg border-r border-gray-200 dark:border-gray-700 transition-all duration-300 flex flex-col z-30 fixed h-full',
      isSidebarOpen ? 'w-64' : 'w-16',
      !isSidebarOpen && 'items-center',
      // Mobile responsiveness
      'lg:translate-x-0',
      isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]">
      <!-- Sidebar Header -->
      <div :class="[
        'flex items-center h-16 border-b border-gray-200 dark:border-gray-700',
        isSidebarOpen ? 'justify-between px-4' : 'flex-col justify-center px-2 space-y-2'
      ]">
        <div v-if="isSidebarOpen" class="flex items-center">
          <Store class="h-8 w-8 text-blue-600 mr-3" />
          <h1 class="text-xl font-semibold text-gray-900 dark:text-white">POS System</h1>
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
              ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
              : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white',
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
              appStore.currentView === item.id ? 'text-blue-700 dark:text-blue-300' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-400',
              isSidebarOpen ? 'mr-3' : 'mx-auto'
            ]"
          />
          <span v-if="isSidebarOpen" class="truncate">{{ item.name }}</span>
        </button>
      </nav>

      <!-- Store Selector & User Info -->
      <div class="border-t border-gray-200 dark:border-gray-700 p-4">
        <div v-if="isSidebarOpen" class="space-y-3">
          <select
            v-model="appStore.selectedStore"
            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
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
                <button class="flex items-center space-x-2 p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors w-full justify-center">
                  <User class="h-4 w-4 text-gray-600 dark:text-gray-300" />
                  <span class="text-sm text-gray-600 dark:text-gray-300">Menu</span>
                  <ChevronDown class="h-3 w-3 text-gray-400 dark:text-gray-500" />
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
          <Dropdown align="right" width="48">
            <template #trigger>
              <button
                class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                :title="$page.props.auth?.user?.name || 'User Menu'"
              >
                <User class="h-5 w-5 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300" />
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
      class="lg:hidden fixed inset-0 z-20 bg-black dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-70 transition-opacity duration-300"
      @click="toggleSidebar"
    ></div>

    <!-- Main Content Area -->
    <div :class="[
      'flex-1 flex flex-col min-w-0 relative transition-all duration-300',
      isSidebarOpen ? 'lg:ml-64' : 'lg:ml-16',
      'ml-0'
    ]">
      <!-- Modern Header -->
      <header class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm border-b border-gray-200/60 dark:border-gray-700/60 shadow-sm sticky top-0 z-40">
        <div class="h-16 px-4 lg:px-6 flex items-center justify-between">
          <!-- Left Section -->
          <div class="flex items-center space-x-4">
            <!-- Mobile Toggle Button -->
            <button
              @click="toggleSidebar"
              class="p-2 -ml-2 rounded-xl hover:bg-gray-100 transition-all duration-200 lg:hidden group"
              :title="(isSidebarOpen ? 'Collapse sidebar' : 'Expand sidebar') + ' (Ctrl+B)'"
            >
              <Menu class="h-5 w-5 text-gray-600 group-hover:text-gray-800" />
            </button>

            <!-- Enhanced Page Header -->
            <div class="flex items-center space-x-4">
              <!-- Current View Badge -->
              <div class="flex items-center space-x-3 px-4 py-2 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-100">
                <div class="p-1.5 bg-white rounded-lg shadow-sm">
                  <component
                    :is="getCurrentViewIcon()"
                    class="h-4 w-4 text-blue-600"
                  />
                </div>
                <div class="hidden sm:block">
                  <h1 class="font-semibold text-gray-900 text-sm">{{ getCurrentViewTitle() }}</h1>
                  <p class="text-xs text-gray-600">{{ getCurrentViewDescription() }}</p>
                </div>
                <div class="sm:hidden">
                  <h1 class="font-semibold text-gray-900 text-sm">{{ getCurrentViewTitle() }}</h1>
                </div>
              </div>

              <!-- Live Status Indicator -->
              <div class="hidden lg:flex items-center space-x-2 px-3 py-2 bg-green-50 rounded-lg border border-green-200">
                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-xs font-medium text-green-700">System Online</span>
              </div>

              <!-- Time & Date -->
              <div class="hidden xl:block text-right">
                <div class="text-sm font-medium text-gray-900">{{ formatCurrentTime() }}</div>
                <div class="text-xs text-gray-500">{{ formatCurrentDate() }}</div>
              </div>
            </div>
          </div>

          <!-- Right Section -->
          <div class="flex items-center space-x-3">
            <!-- Contextual Quick Actions -->
            <div class="hidden lg:flex items-center space-x-2">
              <!-- POS Actions -->
              <button
                v-if="appStore.currentView === 'pos'"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white rounded-xl text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105"
              >
                <ShoppingCart class="h-4 w-4" />
                <span>New Sale</span>
              </button>

              <!-- Product Actions -->
              <button
                v-if="appStore.currentView === 'products'"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105"
              >
                <Package class="h-4 w-4" />
                <span>Add Product</span>
              </button>

              <!-- Manager Dashboard Actions -->
              <button
                v-if="appStore.currentView === 'manager-dashboard'"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-700 hover:to-violet-700 text-white rounded-xl text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105"
              >
                <BarChart3 class="h-4 w-4" />
                <span>Export Report</span>
              </button>

              <!-- Stock Management Actions -->
              <button
                v-if="appStore.currentView === 'stock'"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white rounded-xl text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105"
              >
                <Warehouse class="h-4 w-4" />
                <span>Bulk Adjust</span>
              </button>

              <!-- Reorder Management Actions -->
              <button
                v-if="appStore.currentView === 'reorder'"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 text-white rounded-xl text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105"
              >
                <ShoppingBag class="h-4 w-4" />
                <span>Create PO</span>
              </button>

              <!-- General refresh action for other views -->
              <button
                v-if="!['pos', 'products', 'manager-dashboard', 'stock', 'reorder'].includes(appStore.currentView)"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-gray-600 to-slate-600 hover:from-gray-700 hover:to-slate-700 text-white rounded-xl text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105"
              >
                <RefreshCcw class="h-4 w-4" />
                <span>Refresh</span>
              </button>
            </div>

            <!-- Store Selector -->
            <div class="hidden sm:block">
              <select
                v-model="appStore.selectedStore"
                class="bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-300 transition-colors shadow-sm"
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

            <!-- Action Buttons -->
            <div class="flex items-center space-x-1">
              <!-- Notifications -->
              <div class="relative">
                <NotificationDropdown />
              </div>

              <!-- User Profile -->
              <Dropdown align="right" width="72">
                <template #trigger>
                  <button class="flex items-center space-x-2 p-2 rounded-xl hover:bg-gray-50 transition-all duration-200 group" title="User Menu">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center ring-2 ring-white shadow-sm">
                      <span class="text-white text-sm font-semibold">
                        {{ ($page.props.auth?.user?.name || 'G').charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div class="hidden lg:block text-left">
                      <div class="text-sm font-medium text-gray-900 group-hover:text-gray-700">
                        {{ $page.props.auth?.user?.name || 'Guest User' }}
                      </div>
                      <div class="text-xs text-gray-500 capitalize">{{ $page.props.auth?.user?.role || 'Guest' }}</div>
                    </div>
                    <ChevronDown class="h-4 w-4 text-gray-400 group-hover:text-gray-600 transition-colors" />
                  </button>
                </template>
                <template #content>
                  <!-- User Info Header -->
                  <div class="px-4 py-4 border-b border-gray-100 bg-gradient-to-br from-gray-50 to-white">
                    <div class="flex items-center space-x-3">
                      <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold text-lg">
                          {{ ($page.props.auth?.user?.name || 'G').charAt(0).toUpperCase() }}
                        </span>
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900 truncate">{{ $page.props.auth?.user?.name || 'Guest User' }}</div>
                        <div class="text-sm text-gray-500 truncate">{{ $page.props.auth?.user?.email || 'No email' }}</div>
                        <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                          {{ ($page.props.auth?.user?.role || 'Guest').charAt(0).toUpperCase() + ($page.props.auth?.user?.role || 'Guest').slice(1) }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Menu Items -->
                  <div class="py-2">
                    <DropdownLink :href="route('profile.edit')" class="group">
                      <div class="flex items-center space-x-3 px-4 py-2">
                        <User class="h-4 w-4 text-gray-400 group-hover:text-blue-500" />
                        <span class="text-gray-700 group-hover:text-gray-900">Profile Settings</span>
                      </div>
                    </DropdownLink>

                    <div class="border-t border-gray-100 my-1"></div>

                    <DropdownLink :href="route('logout')" method="post" as="button" class="group w-full">
                      <div class="flex items-center space-x-3 px-4 py-2 text-red-600 group-hover:text-red-700 group-hover:bg-red-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Sign Out</span>
                      </div>
                    </DropdownLink>
                  </div>
                </template>
              </Dropdown>
            </div>
          </div>
        </div>
      </header>

      <!-- Main Content -->
      <main class="flex-1 p-6 overflow-auto bg-gray-50 dark:bg-gray-900">
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
