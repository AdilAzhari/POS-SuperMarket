<template>
  <div class="space-y-6">
    <!-- Header with Role-based Welcome -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-700 dark:from-blue-700 dark:to-purple-800 rounded-lg shadow-lg p-6 text-white">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold">Operations Dashboard</h1>
          <p class="text-blue-100 mt-1">Real-time store management for {{ currentUser?.name }}</p>
          <p class="text-blue-200 text-sm">{{ currentUser?.role === 'admin' ? 'System Administrator' : 'Store Manager' }} • Live Operations</p>
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
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-green-500">
        <div class="flex items-center">
          <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-full">
            <TrendingUp class="h-6 w-6 text-green-600" />
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-300">Today's Sales</h3>
            <div class="flex items-baseline">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ dashboardData.today_metrics?.revenue?.toFixed(2) || '0.00' }}</p>
              <span class="ml-2 text-sm text-green-600">{{ dashboardData.today_metrics?.sales_count || 0 }} transactions</span>
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Pace: ${{ dashboardData.today_metrics?.hourly_pace?.toFixed(0) || '0' }}/hr
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
        <div class="flex items-center">
          <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-full">
            <Users class="h-6 w-6 text-blue-600" />
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-300">Active Staff</h3>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ dashboardData.live_stats?.active_transactions || 0 }}</p>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              {{ dashboardData.current_shift?.active_cashiers?.length || 0 }} cashiers on duty
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
        <div class="flex items-center">
          <div class="p-3 bg-yellow-100 dark:bg-yellow-900/20 rounded-full">
            <Clock class="h-6 w-6 text-yellow-600" />
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-300">System Status</h3>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ dashboardData.live_stats?.system_load?.cpu || 0 }}%</p>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              CPU Load • {{ dashboardData.live_stats?.register_status ? Object.keys(dashboardData.live_stats.register_status).length : 0 }} registers
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-red-500">
        <div class="flex items-center">
          <div class="p-3 bg-red-100 dark:bg-red-900/20 rounded-full">
            <AlertTriangle class="h-6 w-6 text-red-600" />
          </div>
          <div class="ml-4">
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-300">Urgent Alerts</h3>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ dashboardData.urgent_alerts?.length || 0 }}</p>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              {{ dashboardData.alerts_count?.critical || 0 }} critical, {{ dashboardData.alerts_count?.warnings || 0 }} warnings
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Controls -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
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
            :class="autoRefresh ? 'bg-green-100 dark:bg-green-900/20 text-green-700' : 'bg-gray-100 text-gray-600 dark:text-gray-300'"
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

    <!-- Urgent Alerts & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Urgent Alerts -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Urgent Alerts</h3>
            <span v-if="dashboardData.urgent_alerts?.length" class="px-3 py-1 bg-red-100 dark:bg-red-900/20 text-red-800 rounded-full text-sm font-medium">
              {{ dashboardData.urgent_alerts.length }}
            </span>
          </div>
        </div>
        <div class="p-6">
          <div v-if="dashboardData.urgent_alerts?.length" class="space-y-3">
            <div
              v-for="alert in dashboardData.urgent_alerts.slice(0, 5)"
              :key="alert.type"
              :class="[
                'flex items-center justify-between p-3 rounded-lg border',
                alert.severity === 'critical' ? 'bg-red-50 border-red-200' :
                alert.severity === 'warning' ? 'bg-yellow-50 border-yellow-200' :
                'bg-blue-50 border-blue-200'
              ]"
            >
              <div class="flex items-center space-x-3">
                <div :class="[
                  'w-3 h-3 rounded-full',
                  alert.severity === 'critical' ? 'bg-red-500' :
                  alert.severity === 'warning' ? 'bg-yellow-500' :
                  'bg-blue-500'
                ]"></div>
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">{{ alert.message }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ alert.type.replace('_', ' ').toUpperCase() }}</p>
                </div>
              </div>
              <button
                @click="acknowledgeAlert(alert)"
                class="text-sm px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50"
              >
                Acknowledge
              </button>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <CheckCircle class="w-12 h-12 text-green-400 mx-auto mb-2" />
            <p class="text-gray-500 dark:text-gray-400">No urgent alerts at this time</p>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h3>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-2 gap-3">
            <button
              v-for="action in dashboardData.quick_actions"
              :key="action.id"
              @click="executeQuickAction(action)"
              :class="[
                'p-4 rounded-lg border-2 border-dashed transition-colors text-left',
                action.type === 'primary' ? 'border-blue-300 hover:border-blue-400 hover:bg-blue-50' :
                action.type === 'secondary' ? 'border-gray-300 hover:border-gray-400 hover:bg-gray-50' :
                'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
              ]"
            >
              <div class="flex items-center space-x-3">
                <div :class="[
                  'p-2 rounded',
                  action.type === 'primary' ? 'bg-blue-100 dark:bg-blue-900/20' :
                  action.type === 'secondary' ? 'bg-gray-100' :
                  'bg-gray-50'
                ]">
                  <!-- Icon would be dynamically rendered based on action.icon -->
                  <div class="w-5 h-5 bg-gray-400 rounded"></div>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ action.label }}</span>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Real-time Activity & System Status -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent Activity Feed -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
        </div>
        <div class="p-6">
          <div class="space-y-4 max-h-80 overflow-y-auto">
            <div
              v-for="activity in dashboardData.recent_activity"
              :key="activity.id || activity.description"
              class="flex items-start space-x-3"
            >
              <div :class="[
                'w-2 h-2 rounded-full mt-2 flex-shrink-0',
                activity.severity === 'success' ? 'bg-green-500' :
                activity.severity === 'warning' ? 'bg-yellow-500' :
                activity.severity === 'info' ? 'bg-blue-500' : 'bg-gray-500'
              ]"></div>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-900 dark:text-white">{{ activity.description }}</p>
                <div class="flex items-center space-x-2 mt-1">
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ activity.time }}</p>
                  <span class="text-xs text-gray-400">•</span>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ activity.user }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- System Status -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">System Status</h3>
        </div>
        <div class="p-6">
          <div class="space-y-4">
            <!-- Payment Systems -->
            <div>
              <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Payment Systems</h4>
              <div class="grid grid-cols-2 gap-2">
                <div
                  v-for="(status, method) in dashboardData.live_stats?.payment_methods_status"
                  :key="method"
                  class="flex items-center justify-between p-2 bg-gray-50 rounded"
                >
                  <span class="text-sm text-gray-700">{{ method.replace('_', ' ') }}</span>
                  <div :class="[
                    'w-2 h-2 rounded-full',
                    status === 'online' ? 'bg-green-500' : 'bg-red-500'
                  ]"></div>
                </div>
              </div>
            </div>

            <!-- Registers -->
            <div>
              <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">POS Registers</h4>
              <div class="grid grid-cols-2 gap-2">
                <div
                  v-for="(status, register) in dashboardData.live_stats?.register_status"
                  :key="register"
                  class="flex items-center justify-between p-2 bg-gray-50 rounded"
                >
                  <span class="text-sm text-gray-700">{{ register.replace('_', ' ') }}</span>
                  <div :class="[
                    'w-2 h-2 rounded-full',
                    status === 'open' ? 'bg-green-500' :
                    status === 'closed' ? 'bg-gray-500' : 'bg-yellow-500'
                  ]"></div>
                </div>
              </div>
            </div>

            <!-- System Load -->
            <div v-if="dashboardData.live_stats?.system_load">
              <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">System Performance</h4>
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-700">CPU</span>
                  <div class="flex items-center space-x-2">
                    <div class="w-16 bg-gray-200 rounded-full h-2">
                      <div
                        class="bg-blue-500 h-2 rounded-full"
                        :style="{ width: `${dashboardData.live_stats.system_load.cpu}%` }"
                      ></div>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ dashboardData.live_stats.system_load.cpu }}%</span>
                  </div>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-700">Memory</span>
                  <div class="flex items-center space-x-2">
                    <div class="w-16 bg-gray-200 rounded-full h-2">
                      <div
                        class="bg-green-500 h-2 rounded-full"
                        :style="{ width: `${dashboardData.live_stats.system_load.memory}%` }"
                      ></div>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ dashboardData.live_stats.system_load.memory }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Staff Performance (if user can manage users) -->
    <div v-if="currentUser?.role === 'admin' || currentUser?.role === 'manager'" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Active Staff Performance</h3>
      </div>
      <div class="p-6">
        <div v-if="dashboardData.current_shift?.shift_performance?.length" class="space-y-3">
          <div
            v-for="staff in dashboardData.current_shift.shift_performance.slice(0, 5)"
            :key="staff.name"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
          >
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center">
                <span class="text-sm font-medium text-blue-600">{{ staff.name.charAt(0) }}</span>
              </div>
              <div>
                <p class="font-medium text-gray-900 dark:text-white">{{ staff.name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ staff.transactions }} transactions today</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-medium text-gray-900 dark:text-white">${{ parseFloat(staff.revenue).toFixed(0) }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Avg: ${{ parseFloat(staff.avg_sale).toFixed(0) }}</p>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8">
          <Users class="w-12 h-12 text-gray-400 mx-auto mb-2" />
          <p class="text-gray-500 dark:text-gray-400">No active staff performance data</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useNotificationStore } from '@/stores/notifications'
import axios from 'axios'
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
const selectedStore = ref('')
const selectedPeriod = ref('today')
const autoRefresh = ref(false)
const lastUpdated = ref(new Date())
const refreshInterval = ref(null)

const dashboardData = ref({
  today_metrics: {},
  live_stats: {},
  urgent_alerts: [],
  quick_actions: [],
  recent_activity: [],
  current_shift: {},
  alerts_count: {},
})

const realtimeStats = ref({
  live_sales: {},
  current_shift: {},
  system_status: {},
  alerts_count: {},
})

const stores = ref([
  { id: 1, name: 'Downtown Branch' },
  { id: 2, name: 'Mall Location' },
  { id: 3, name: 'Suburban Store' },
])

// Computed
const totalAlerts = computed(() => {
  const alerts = dashboardData.value.alerts_count || {}
  return (alerts.critical || 0) + (alerts.warnings || 0) + (alerts.info || 0)
})

// Methods
const loadDashboardOverview = async () => {
  // Check if user is authenticated before making API call
  if (!currentUser.value) {
    console.log('User not authenticated, skipping dashboard overview load')
    return
  }

  try {
    isLoading.value = true
    const params = {}

    if (selectedStore.value) {
      params.store_id = selectedStore.value
    }
    if (selectedPeriod.value) {
      params.period = selectedPeriod.value
    }

    const response = await axios.get('/api/manager-dashboard/overview', { params })
    dashboardData.value = response.data
    lastUpdated.value = new Date()

  } catch (error) {
    if (error.response?.status === 403) {
      notificationStore.error('Access Denied', 'You do not have permission to view dashboard data')
    } else {
      notificationStore.error('Error', 'Failed to load dashboard data')
    }
    console.error('Dashboard error:', error)
  } finally {
    isLoading.value = false
  }
}

const loadRealtimeStats = async () => {
  // Check if user is authenticated before making API call
  if (!currentUser.value) {
    console.log('User not authenticated, skipping realtime stats load')
    return
  }

  try {
    const params = {}
    if (selectedStore.value) {
      params.store_id = selectedStore.value
    }
    if (selectedPeriod.value) {
      params.period = selectedPeriod.value
    }

    const response = await axios.get('/api/manager-dashboard/realtime-stats', { params })
    realtimeStats.value = response.data

  } catch (error) {
    if (error.response?.status === 403) {
      notificationStore.error('Access Denied', 'You do not have permission to view realtime stats')
    } else {
      console.error('Realtime stats error:', error)
    }
  }
}

const refreshData = async () => {
  await Promise.all([loadDashboardOverview(), loadRealtimeStats()])
}

const loadAnalytics = async () => {
  // Called when period or store changes
  await refreshData()
}

// New methods for real-time actions
const acknowledgeAlert = async (alert) => {
  try {
    await axios.post('/api/manager-dashboard/quick-action', {
      action: 'acknowledge_alert',
      params: { alert_id: alert.id || alert.type }
    })

    // Remove the alert from the list
    const index = dashboardData.value.urgent_alerts.findIndex(a => a.type === alert.type)
    if (index > -1) {
      dashboardData.value.urgent_alerts.splice(index, 1)
    }
    notificationStore.success('Alert acknowledged')
  } catch (error) {
    console.error('Failed to acknowledge alert:', error)
    notificationStore.error('Failed to acknowledge alert')
  }
}

const executeQuickAction = async (action) => {
  try {
    const response = await axios.post('/api/manager-dashboard/quick-action', {
      action: action.id,
      params: {}
    })

    notificationStore.success(response.data.message || `${action.label} executed successfully`)
  } catch (error) {
    console.error('Failed to execute action:', error)
    notificationStore.error(`Failed to execute ${action.label}`)
  }
}

const toggleAutoRefresh = () => {
  autoRefresh.value = !autoRefresh.value

  if (autoRefresh.value) {
    refreshInterval.value = setInterval(() => {
      loadRealtimeStats()
      // Refresh full data every 5 minutes
      if (Date.now() - lastUpdated.value.getTime() > 300000) {
        loadDashboardOverview()
      }
    }, 30000) // Refresh every 30 seconds
    notificationStore.info('Auto-refresh enabled', 'Real-time data will update every 30 seconds')
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
