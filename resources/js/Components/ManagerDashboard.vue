<template>
  <div class="space-y-6">
    <!-- Header with Role-based Welcome -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-700 rounded-lg shadow-lg p-6 text-white">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold">Manager Dashboard</h1>
          <p class="text-blue-100 mt-1">Welcome back, {{ currentUser?.name }}</p>
          <p class="text-blue-200 text-sm">{{ currentUser?.role === 'admin' ? 'System Administrator' : 'Store Manager' }}</p>
        </div>
        <div class="flex items-center space-x-4">
          <div class="text-right">
            <p class="text-sm text-blue-100">Last Updated</p>
            <p class="text-lg font-semibold">{{ formatTime(lastUpdated) }}</p>
          </div>
          <button
            @click="refreshData"
            :disabled="isLoading"
            class="bg-white bg-opacity-20 hover:bg-opacity-30 p-3 rounded-lg transition-colors"
          >
            <RefreshCw :class="{ 'animate-spin': isLoading }" class="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Real-time Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
        <div class="flex items-center">
          <div class="p-3 bg-green-100 rounded-full">
            <TrendingUp class="h-6 w-6 text-green-600" />
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-600">Today's Sales</h3>
            <div class="flex items-baseline">
              <p class="text-2xl font-bold text-gray-900">${{ realtimeStats.today_sales?.revenue?.toFixed(2) || '0.00' }}</p>
              <span class="ml-2 text-sm text-green-600">{{ realtimeStats.today_sales?.count || 0 }} orders</span>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
        <div class="flex items-center">
          <div class="p-3 bg-blue-100 rounded-full">
            <Users class="h-6 w-6 text-blue-600" />
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-600">Active Employees</h3>
            <p class="text-2xl font-bold text-gray-900">{{ realtimeStats.active_employees || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
        <div class="flex items-center">
          <div class="p-3 bg-yellow-100 rounded-full">
            <Clock class="h-6 w-6 text-yellow-600" />
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-600">Pending Orders</h3>
            <p class="text-2xl font-bold text-gray-900">{{ realtimeStats.pending_orders || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
        <div class="flex items-center">
          <div class="p-3 bg-red-100 rounded-full">
            <AlertTriangle class="h-6 w-6 text-red-600" />
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-600">Low Stock Items</h3>
            <p class="text-2xl font-bold text-gray-900">{{ realtimeStats.low_stock_count || 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Controls -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
            <select
              v-model="selectedPeriod"
              @change="loadAnalytics"
              class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="today">Today</option>
              <option value="week">This Week</option>
              <option value="month">This Month</option>
              <option value="quarter">This Quarter</option>
              <option value="year">This Year</option>
            </select>
          </div>
          <div v-if="currentUser?.role === 'admin'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Store</label>
            <select
              v-model="selectedStore"
              @change="loadAnalytics"
              class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">All Stores</option>
              <option v-for="store in stores" :key="store.id" :value="store.id">
                {{ store.name }}
              </option>
            </select>
          </div>
        </div>
        <div class="flex items-center space-x-2">
          <button
            @click="toggleAutoRefresh"
            :class="autoRefresh ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          >
            <div class="flex items-center space-x-2">
              <div :class="autoRefresh ? 'bg-green-500' : 'bg-gray-400'" class="w-2 h-2 rounded-full"></div>
              <span>Auto-refresh {{ autoRefresh ? 'ON' : 'OFF' }}</span>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Analytics Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Revenue</h3>
          <DollarSign class="h-5 w-5 text-green-500" />
        </div>
        <div class="space-y-2">
          <p class="text-3xl font-bold text-gray-900">${{ analytics.overview?.total_revenue?.toFixed(2) || '0.00' }}</p>
          <div class="flex items-center">
            <TrendingUp v-if="analytics.overview?.revenue_growth >= 0" class="h-4 w-4 text-green-500 mr-1" />
            <TrendingDown v-else class="h-4 w-4 text-red-500 mr-1" />
            <span :class="analytics.overview?.revenue_growth >= 0 ? 'text-green-600' : 'text-red-600'" class="text-sm">
              {{ Math.abs(analytics.overview?.revenue_growth || 0) }}% vs last period
            </span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Sales Count</h3>
          <ShoppingBag class="h-5 w-5 text-blue-500" />
        </div>
        <div class="space-y-2">
          <p class="text-3xl font-bold text-gray-900">{{ analytics.overview?.total_sales || 0 }}</p>
          <div class="flex items-center">
            <TrendingUp v-if="analytics.overview?.sales_growth >= 0" class="h-4 w-4 text-green-500 mr-1" />
            <TrendingDown v-else class="h-4 w-4 text-red-500 mr-1" />
            <span :class="analytics.overview?.sales_growth >= 0 ? 'text-green-600' : 'text-red-600'" class="text-sm">
              {{ Math.abs(analytics.overview?.sales_growth || 0) }}% vs last period
            </span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Avg. Sale</h3>
          <Calculator class="h-5 w-5 text-purple-500" />
        </div>
        <div class="space-y-2">
          <p class="text-3xl font-bold text-gray-900">${{ analytics.overview?.average_sale?.toFixed(2) || '0.00' }}</p>
          <p class="text-sm text-gray-600">Per transaction</p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">New Customers</h3>
          <UserPlus class="h-5 w-5 text-indigo-500" />
        </div>
        <div class="space-y-2">
          <p class="text-3xl font-bold text-gray-900">{{ analytics.overview?.new_customers || 0 }}</p>
          <p class="text-sm text-gray-600">This period</p>
        </div>
      </div>
    </div>

    <!-- Charts and Detailed Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Sales Trend Chart -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Sales Trend</h3>
        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
          <div v-if="analytics.sales_trend?.length" class="w-full h-full p-4">
            <!-- Simple visualization - in production, use a chart library -->
            <div class="grid grid-cols-7 gap-2 h-full">
              <div 
                v-for="(day, index) in analytics.sales_trend" 
                :key="index"
                class="flex flex-col items-center justify-end"
              >
                <div 
                  class="bg-blue-500 w-full rounded-t"
                  :style="{ height: `${(day.revenue / maxDayRevenue) * 100}%` }"
                ></div>
                <span class="text-xs text-gray-500 mt-1">{{ formatDate(day.date) }}</span>
              </div>
            </div>
          </div>
          <div v-else class="text-center">
            <BarChart3 class="w-12 h-12 text-gray-400 mx-auto mb-2" />
            <p class="text-gray-500">Sales trend data</p>
          </div>
        </div>
      </div>

      <!-- Top Products -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Performing Products</h3>
        <div class="space-y-3">
          <div 
            v-for="product in analytics.top_products?.slice(0, 5)" 
            :key="product.id"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
          >
            <div>
              <p class="font-medium text-gray-900">{{ product.name }}</p>
              <p class="text-sm text-gray-500">{{ product.sku }}</p>
            </div>
            <div class="text-right">
              <p class="font-medium text-gray-900">{{ product.total_quantity }} sold</p>
              <p class="text-sm text-gray-500">${{ parseFloat(product.total_revenue).toFixed(2) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Employee Performance (if user can manage users) -->
    <div v-if="currentUser?.role === 'admin' || currentUser?.role === 'manager'" class="bg-white rounded-lg shadow-sm">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Employee Performance</h3>
      </div>
      <div class="p-6">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sales Count</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Sale</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="employee in analytics.employee_performance?.slice(0, 10)" :key="employee.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="font-medium text-gray-900">{{ employee.name }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                    {{ employee.role }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ employee.sales_count }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900">${{ parseFloat(employee.total_revenue).toFixed(2) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900">${{ parseFloat(employee.avg_sale).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Inventory Alerts (if user can manage inventory) -->
    <div v-if="currentUser?.role === 'admin' || currentUser?.role === 'manager'" class="bg-white rounded-lg shadow-sm">
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Inventory Alerts</h3>
          <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
            {{ analytics.inventory_alerts?.length || 0 }} items
          </span>
        </div>
      </div>
      <div class="p-6">
        <div v-if="analytics.inventory_alerts?.length" class="space-y-3">
          <div 
            v-for="item in analytics.inventory_alerts.slice(0, 8)" 
            :key="item.id"
            class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg"
          >
            <div>
              <p class="font-medium text-gray-900">{{ item.name }}</p>
              <p class="text-sm text-gray-500">SKU: {{ item.sku }}</p>
            </div>
            <div class="text-right">
              <p class="font-medium text-red-600">{{ item.stock }} left</p>
              <p class="text-xs text-gray-500">Min: {{ item.low_stock_threshold }}</p>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8">
          <CheckCircle class="w-12 h-12 text-green-400 mx-auto mb-2" />
          <p class="text-gray-500">All inventory levels are healthy</p>
        </div>
      </div>
    </div>

    <!-- Store Comparison (Admin only) -->
    <div v-if="currentUser?.role === 'admin' && analytics.store_comparison?.length" class="bg-white rounded-lg shadow-sm">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Store Performance Comparison</h3>
      </div>
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-for="store in analytics.store_comparison" 
            :key="store.id"
            class="p-4 border border-gray-200 rounded-lg"
          >
            <h4 class="font-medium text-gray-900 mb-2">{{ store.name }}</h4>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Sales:</span>
                <span class="font-medium">{{ store.sales_count }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Revenue:</span>
                <span class="font-medium">${{ parseFloat(store.revenue).toFixed(2) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Avg Sale:</span>
                <span class="font-medium">${{ parseFloat(store.avg_sale).toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useNotificationStore } from '@/stores/notifications'
import {
  TrendingUp,
  TrendingDown,
  Users,
  Clock,
  AlertTriangle,
  RefreshCw,
  DollarSign,
  ShoppingBag,
  Calculator,
  UserPlus,
  BarChart3,
  CheckCircle,
} from 'lucide-vue-next'

const notificationStore = useNotificationStore()
const page = usePage()
const currentUser = computed(() => page.props.auth?.user)

// State
const isLoading = ref(false)
const selectedPeriod = ref('month')
const selectedStore = ref('')
const autoRefresh = ref(false)
const lastUpdated = ref(new Date())
const refreshInterval = ref(null)

const analytics = ref({
  overview: {},
  sales_trend: [],
  top_products: [],
  employee_performance: [],
  inventory_alerts: [],
  customer_insights: {},
  store_comparison: [],
})

const realtimeStats = ref({
  today_sales: {},
  active_employees: 0,
  pending_orders: 0,
  low_stock_count: 0,
  hourly_sales: [],
})

const stores = ref([
  { id: 1, name: 'Downtown Branch' },
  { id: 2, name: 'Mall Location' },
  { id: 3, name: 'Suburban Store' },
])

// Computed
const maxDayRevenue = computed(() => {
  if (!analytics.value.sales_trend?.length) return 1
  return Math.max(...analytics.value.sales_trend.map(day => parseFloat(day.revenue)))
})

// Methods
const loadAnalytics = async () => {
  try {
    isLoading.value = true
    const params = new URLSearchParams({
      date_range: selectedPeriod.value,
    })
    
    if (selectedStore.value) {
      params.append('store_id', selectedStore.value)
    }

    const response = await fetch(`/api/manager-dashboard/analytics?${params}`)
    if (!response.ok) throw new Error('Failed to load analytics')
    
    analytics.value = await response.json()
    lastUpdated.value = new Date()
    
  } catch (error) {
    notificationStore.error('Error', 'Failed to load analytics data')
    console.error('Analytics error:', error)
  } finally {
    isLoading.value = false
  }
}

const loadRealtimeStats = async () => {
  try {
    const params = new URLSearchParams()
    if (selectedStore.value) {
      params.append('store_id', selectedStore.value)
    }

    const response = await fetch(`/api/manager-dashboard/realtime?${params}`)
    if (!response.ok) throw new Error('Failed to load realtime stats')
    
    realtimeStats.value = await response.json()
    
  } catch (error) {
    console.error('Realtime stats error:', error)
  }
}

const refreshData = async () => {
  await Promise.all([loadAnalytics(), loadRealtimeStats()])
}

const toggleAutoRefresh = () => {
  autoRefresh.value = !autoRefresh.value
  
  if (autoRefresh.value) {
    refreshInterval.value = setInterval(() => {
      loadRealtimeStats()
    }, 30000) // Refresh every 30 seconds
    notificationStore.info('Auto-refresh enabled', 'Data will update every 30 seconds')
  } else {
    if (refreshInterval.value) {
      clearInterval(refreshInterval.value)
      refreshInterval.value = null
    }
    notificationStore.info('Auto-refresh disabled')
  }
}

const formatTime = (date) => {
  return date.toLocaleTimeString('en-US', {
    hour12: true,
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatDate = (dateStr) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

// Lifecycle
onMounted(async () => {
  await refreshData()
})

onUnmounted(() => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value)
  }
})
</script>