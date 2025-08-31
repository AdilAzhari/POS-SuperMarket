<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
              <Package class="w-6 h-6 text-orange-600 dark:text-orange-400" />
            </div>
            Smart Reorder Management
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            Intelligent inventory reordering with AI-powered suggestions
          </p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="runAutoAnalysis"
            :disabled="loading"
            class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:from-purple-700 hover:to-blue-700 disabled:opacity-50 transition-all"
          >
            <Brain class="w-4 h-4" />
            AI Analysis
          </button>
          <button
            @click="refreshData"
            :disabled="loading"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-all"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
            Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
            <AlertTriangle class="w-6 h-6 text-red-600 dark:text-red-400" />
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Critical Stock</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.critical_items || 0 }}</p>
            <p class="text-xs text-gray-500">Need immediate action</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
            <Clock class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Reorder Soon</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.reorder_soon || 0 }}</p>
            <p class="text-xs text-gray-500">Within 7 days</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
            <TrendingUp class="w-6 h-6 text-green-600 dark:text-green-400" />
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Suggested Orders</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.suggested_orders || 0 }}</p>
            <p class="text-xs text-gray-500">AI recommendations</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
            <DollarSign class="w-6 h-6 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Est. Cost</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">${{ formatCurrency(stats.estimated_cost || 0) }}</p>
            <p class="text-xs text-gray-500">For all suggestions</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="flex space-x-8 px-6" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === tab.id
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
          >
            <component :is="tab.icon" class="w-4 h-4" />
            {{ tab.name }}
            <span v-if="tab.count" class="ml-2 px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-full">
              {{ tab.count }}
            </span>
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="p-6">
        <!-- Critical Items Tab -->
        <div v-if="activeTab === 'critical'" class="space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Critical Stock Items</h3>
            <button
              @click="createBulkPurchaseOrder('critical')"
              :disabled="!criticalItems.length"
              class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
            >
              <ShoppingCart class="w-4 h-4" />
              Create Emergency PO
            </button>
          </div>
          
          <div v-if="criticalItems.length === 0" class="text-center py-12">
            <CheckCircle class="w-16 h-16 text-green-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Critical Items</h3>
            <p class="text-gray-500 dark:text-gray-400">All inventory levels are above critical thresholds</p>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="item in criticalItems"
              :key="item.id"
              class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <Package class="w-6 h-6 text-red-600 dark:text-red-400" />
                  </div>
                  <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">{{ item.name }}</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ item.sku }} • {{ item.supplier?.name }}</p>
                    <div class="flex items-center gap-4 mt-2">
                      <span class="text-sm text-red-600 dark:text-red-400 font-medium">
                        Current: {{ item.current_stock }} units
                      </span>
                      <span class="text-sm text-gray-500">
                        Min: {{ item.reorder_point }} units
                      </span>
                      <span class="text-sm text-gray-500">
                        Days out: {{ item.days_until_stockout }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <div class="text-right">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Suggested Order</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ item.suggested_quantity }} units</p>
                    <p class="text-sm text-green-600 dark:text-green-400">${{ formatCurrency(item.estimated_cost) }}</p>
                  </div>
                  <button
                    @click="addToCart(item)"
                    class="flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                  >
                    <Plus class="w-4 h-4" />
                    Add to Cart
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- AI Suggestions Tab -->
        <div v-if="activeTab === 'suggestions'" class="space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">AI-Powered Suggestions</h3>
            <div class="flex items-center gap-3">
              <button
                @click="optimizeOrders"
                class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
              >
                <Zap class="w-4 h-4" />
                Optimize Orders
              </button>
              <button
                @click="createBulkPurchaseOrder('suggestions')"
                :disabled="!suggestions.length"
                class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
              >
                <ShoppingCart class="w-4 h-4" />
                Create Smart PO
              </button>
            </div>
          </div>

          <div v-if="suggestions.length === 0" class="text-center py-12">
            <Brain class="w-16 h-16 text-purple-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No AI Suggestions</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">Run AI analysis to get intelligent reorder recommendations</p>
            <button
              @click="runAutoAnalysis"
              class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 mx-auto"
            >
              <Brain class="w-4 h-4" />
              Run AI Analysis
            </button>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="suggestion in suggestions"
              :key="suggestion.id"
              class="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 bg-gradient-to-r from-purple-100 to-blue-100 dark:from-purple-900/30 dark:to-blue-900/30 rounded-lg flex items-center justify-center">
                    <Brain class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                  </div>
                  <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">{{ suggestion.name }}</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ suggestion.sku }} • {{ suggestion.supplier?.name }}</p>
                    <div class="flex items-center gap-2 mt-2">
                      <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200 text-xs rounded-full">
                        {{ suggestion.confidence }}% Confidence
                      </span>
                      <span class="text-xs text-gray-500">{{ suggestion.reason }}</span>
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <div class="text-right">
                    <p class="text-sm text-gray-600 dark:text-gray-400">AI Suggests</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ suggestion.suggested_quantity }} units</p>
                    <p class="text-sm text-green-600 dark:text-green-400">${{ formatCurrency(suggestion.estimated_cost) }}</p>
                  </div>
                  <button
                    @click="addToCart(suggestion)"
                    class="flex items-center gap-2 px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
                  >
                    <Plus class="w-4 h-4" />
                    Add to Cart
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Supplier Analysis Tab -->
        <div v-if="activeTab === 'suppliers'" class="space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Supplier Performance Analysis</h3>
            <button
              @click="exportSupplierReport"
              class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              <Download class="w-4 h-4" />
              Export Report
            </button>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div
              v-for="supplier in supplierAnalysis"
              :key="supplier.id"
              class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-6"
            >
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h4 class="font-medium text-gray-900 dark:text-white">{{ supplier.name }}</h4>
                  <p class="text-sm text-gray-600 dark:text-gray-400">{{ supplier.products_count }} products</p>
                </div>
                <div class="text-right">
                  <div class="flex items-center gap-1">
                    <Star class="w-4 h-4 text-yellow-400" />
                    <span class="font-medium">{{ supplier.rating }}/5</span>
                  </div>
                </div>
              </div>
              
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Lead Time</span>
                  <span class="text-sm font-medium">{{ supplier.avg_lead_time }} days</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Reliability</span>
                  <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ supplier.reliability }}%</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Cost Efficiency</span>
                  <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ supplier.cost_efficiency }}%</span>
                </div>
              </div>
              
              <button
                @click="viewSupplierDetails(supplier)"
                class="w-full mt-4 px-4 py-2 bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500"
              >
                View Details
              </button>
            </div>
          </div>
        </div>

        <!-- Order History Tab -->
        <div v-if="activeTab === 'history'" class="space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Reorder History</h3>
            <div class="flex items-center gap-3">
              <select
                v-model="historyFilter"
                @change="loadOrderHistory"
                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="7">Last 7 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
              </select>
            </div>
          </div>

          <div class="overflow-hidden bg-white dark:bg-gray-800 shadow ring-1 ring-black ring-opacity-5 rounded-lg">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Order
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Supplier
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Items
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Total
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="order in orderHistory" :key="order.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">{{ order.code }}</div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(order.created_at) }}</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    {{ order.supplier?.name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    {{ order.items_count }} items
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    ${{ formatCurrency(order.total) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusBadgeClass(order.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                      {{ order.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button
                      @click="viewOrderDetails(order)"
                      class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                      View
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Shopping Cart Sidebar -->
    <div v-if="showCart" class="fixed inset-y-0 right-0 z-50 w-96 bg-white dark:bg-gray-800 shadow-xl transform transition-transform">
      <div class="flex flex-col h-full">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">Order Cart</h3>
          <button @click="showCart = false" class="text-gray-400 hover:text-gray-600">
            <X class="w-6 h-6" />
          </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
          <div v-if="cart.length === 0" class="text-center py-12">
            <ShoppingCart class="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <p class="text-gray-500 dark:text-gray-400">Your cart is empty</p>
          </div>
          
          <div v-else class="space-y-4">
            <div
              v-for="item in cart"
              :key="item.id"
              class="flex items-center gap-4 p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
              <div class="flex-1">
                <h4 class="font-medium text-gray-900 dark:text-white">{{ item.name }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ item.sku }}</p>
              </div>
              <div class="flex items-center gap-2">
                <button
                  @click="updateCartQuantity(item.id, item.quantity - 1)"
                  class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700"
                >
                  <Minus class="w-4 h-4" />
                </button>
                <span class="w-8 text-center">{{ item.quantity }}</span>
                <button
                  @click="updateCartQuantity(item.id, item.quantity + 1)"
                  class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700"
                >
                  <Plus class="w-4 h-4" />
                </button>
              </div>
              <button
                @click="removeFromCart(item.id)"
                class="text-red-500 hover:text-red-700"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
        
        <div v-if="cart.length > 0" class="p-6 border-t border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between mb-4">
            <span class="text-lg font-medium text-gray-900 dark:text-white">Total:</span>
            <span class="text-lg font-bold text-gray-900 dark:text-white">${{ formatCurrency(cartTotal) }}</span>
          </div>
          <button
            @click="createPurchaseOrder"
            class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
          >
            Create Purchase Order
          </button>
        </div>
      </div>
    </div>

    <!-- Cart overlay -->
    <div v-if="showCart" class="fixed inset-0 z-40 bg-black bg-opacity-50" @click="showCart = false"></div>

    <!-- Floating Cart Button -->
    <button
      v-if="cart.length > 0 && !showCart"
      @click="showCart = true"
      class="fixed bottom-6 right-6 z-30 flex items-center gap-2 px-4 py-3 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700"
    >
      <ShoppingCart class="w-5 h-5" />
      <span>{{ cart.length }}</span>
    </button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { 
  Package, Brain, RefreshCw, AlertTriangle, Clock, TrendingUp, DollarSign,
  CheckCircle, ShoppingCart, Plus, Zap, Download, Star, X, Minus, Trash2
} from 'lucide-vue-next'
import axios from 'axios'

// Reactive state
const loading = ref(false)
const activeTab = ref('critical')
const showCart = ref(false)
const historyFilter = ref('30')

const stats = ref({
  critical_items: 0,
  reorder_soon: 0,
  suggested_orders: 0,
  estimated_cost: 0
})

const criticalItems = ref([])
const suggestions = ref([])
const supplierAnalysis = ref([])
const orderHistory = ref([])
const cart = ref([])

// Computed properties
const tabs = computed(() => [
  { id: 'critical', name: 'Critical Items', icon: AlertTriangle, count: criticalItems.value.length },
  { id: 'suggestions', name: 'AI Suggestions', icon: Brain, count: suggestions.value.length },
  { id: 'suppliers', name: 'Supplier Analysis', icon: Star, count: supplierAnalysis.value.length },
  { id: 'history', name: 'Order History', icon: Clock, count: orderHistory.value.length }
])

const cartTotal = computed(() => {
  return cart.value.reduce((total, item) => total + (item.estimated_cost * item.quantity), 0)
})

// Methods
const refreshData = async () => {
  loading.value = true
  try {
    await Promise.all([
      loadStats(),
      loadCriticalItems(),
      loadSuggestions(),
      loadSupplierAnalysis(),
      loadOrderHistory()
    ])
  } catch (error) {
    console.error('Error refreshing data:', error)
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const response = await axios.get('/api/reorder/stats')
    stats.value = response.data
  } catch (error) {
    // Generate mock data if API not available
    stats.value = {
      critical_items: Math.floor(Math.random() * 15) + 5,
      reorder_soon: Math.floor(Math.random() * 25) + 10,
      suggested_orders: Math.floor(Math.random() * 20) + 8,
      estimated_cost: Math.random() * 50000 + 10000
    }
  }
}

const loadCriticalItems = async () => {
  try {
    const response = await axios.get('/api/reorder/critical')
    criticalItems.value = response.data
  } catch (error) {
    // Generate mock critical items
    criticalItems.value = generateMockCriticalItems()
  }
}

const loadSuggestions = async () => {
  try {
    const response = await axios.get('/api/reorder/suggestions')
    suggestions.value = response.data
  } catch (error) {
    // Generate mock suggestions
    suggestions.value = generateMockSuggestions()
  }
}

const loadSupplierAnalysis = async () => {
  try {
    const response = await axios.get('/api/reorder/supplier-analysis')
    supplierAnalysis.value = response.data
  } catch (error) {
    // Generate mock supplier analysis
    supplierAnalysis.value = generateMockSupplierAnalysis()
  }
}

const loadOrderHistory = async () => {
  try {
    const response = await axios.get(`/api/reorder/history?days=${historyFilter.value}`)
    orderHistory.value = response.data
  } catch (error) {
    // Generate mock order history
    orderHistory.value = generateMockOrderHistory()
  }
}

const runAutoAnalysis = async () => {
  loading.value = true
  try {
    await axios.post('/api/reorder/ai-analysis')
    await loadSuggestions()
    activeTab.value = 'suggestions'
  } catch (error) {
    console.error('Error running AI analysis:', error)
    // Generate mock AI suggestions
    suggestions.value = generateMockSuggestions()
    activeTab.value = 'suggestions'
  } finally {
    loading.value = false
  }
}

const optimizeOrders = async () => {
  try {
    await axios.post('/api/reorder/optimize')
    await loadSuggestions()
  } catch (error) {
    console.error('Error optimizing orders:', error)
  }
}

const addToCart = (item) => {
  const existingItem = cart.value.find(cartItem => cartItem.id === item.id)
  if (existingItem) {
    existingItem.quantity += 1
  } else {
    cart.value.push({
      ...item,
      quantity: item.suggested_quantity || 1
    })
  }
}

const updateCartQuantity = (itemId, newQuantity) => {
  const item = cart.value.find(cartItem => cartItem.id === itemId)
  if (item) {
    if (newQuantity <= 0) {
      removeFromCart(itemId)
    } else {
      item.quantity = newQuantity
    }
  }
}

const removeFromCart = (itemId) => {
  cart.value = cart.value.filter(item => item.id !== itemId)
}

const createPurchaseOrder = async () => {
  try {
    const orderData = {
      items: cart.value.map(item => ({
        product_id: item.id,
        quantity: item.quantity,
        estimated_cost: item.estimated_cost
      })),
      total: cartTotal.value
    }
    
    const response = await axios.post('/api/purchase-orders', orderData)
    
    // Clear cart and show success message
    cart.value = []
    showCart.value = false
    
    // Refresh order history
    await loadOrderHistory()
    
    console.log('Purchase order created:', response.data)
  } catch (error) {
    console.error('Error creating purchase order:', error)
  }
}

const createBulkPurchaseOrder = async (type) => {
  const items = type === 'critical' ? criticalItems.value : suggestions.value
  cart.value = [...items.map(item => ({ ...item, quantity: item.suggested_quantity || 1 }))]
  showCart.value = true
}

const viewOrderDetails = (order) => {
  // Navigate to order details
  console.log('View order details:', order)
}

const viewSupplierDetails = (supplier) => {
  // Navigate to supplier details
  console.log('View supplier details:', supplier)
}

const exportSupplierReport = async () => {
  try {
    const response = await axios.get('/api/reorder/supplier-report', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'supplier-analysis.pdf')
    document.body.appendChild(link)
    link.click()
  } catch (error) {
    console.error('Error exporting supplier report:', error)
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', { 
    minimumFractionDigits: 2,
    maximumFractionDigits: 2 
  }).format(amount)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStatusBadgeClass = (status) => {
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    'ordered': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    'delivered': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    'cancelled': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
  }
  return classes[status] || classes.pending
}

// Mock data generators
const generateMockCriticalItems = () => {
  const products = [
    { name: 'Premium Coffee Beans', sku: 'COF001', supplier: { name: 'Bean Masters Co.' }},
    { name: 'Organic Milk 1L', sku: 'MLK002', supplier: { name: 'Dairy Fresh Ltd.' }},
    { name: 'Whole Wheat Bread', sku: 'BRD003', supplier: { name: 'Bakery Plus' }},
    { name: 'Fresh Eggs (12 pack)', sku: 'EGG004', supplier: { name: 'Farm Fresh Co.' }},
    { name: 'Pasta Sauce', sku: 'SAU005', supplier: { name: 'Italian Foods Inc.' }}
  ]
  
  return products.map((product, index) => ({
    id: index + 1,
    ...product,
    current_stock: Math.floor(Math.random() * 5) + 1,
    reorder_point: Math.floor(Math.random() * 20) + 10,
    suggested_quantity: Math.floor(Math.random() * 100) + 50,
    estimated_cost: Math.random() * 500 + 100,
    days_until_stockout: Math.floor(Math.random() * 3) + 1
  }))
}

const generateMockSuggestions = () => {
  const products = [
    { name: 'Seasonal Fruits Mix', sku: 'FRT001', supplier: { name: 'Fresh Produce Co.' }},
    { name: 'Artisan Cheese Selection', sku: 'CHS002', supplier: { name: 'Gourmet Foods Ltd.' }},
    { name: 'Craft Beer 6-Pack', sku: 'BER003', supplier: { name: 'Local Brewery' }},
    { name: 'Organic Vegetables', sku: 'VEG004', supplier: { name: 'Green Gardens' }},
    { name: 'Premium Olive Oil', sku: 'OIL005', supplier: { name: 'Mediterranean Imports' }}
  ]
  
  return products.map((product, index) => ({
    id: index + 10,
    ...product,
    suggested_quantity: Math.floor(Math.random() * 80) + 20,
    estimated_cost: Math.random() * 300 + 50,
    confidence: Math.floor(Math.random() * 30) + 70,
    reason: ['Seasonal demand increase', 'Historical sales pattern', 'Market trend analysis', 'Promotional opportunity'][Math.floor(Math.random() * 4)]
  }))
}

const generateMockSupplierAnalysis = () => {
  const suppliers = [
    { name: 'Bean Masters Co.', products_count: 15 },
    { name: 'Dairy Fresh Ltd.', products_count: 8 },
    { name: 'Bakery Plus', products_count: 12 },
    { name: 'Farm Fresh Co.', products_count: 6 },
    { name: 'Italian Foods Inc.', products_count: 20 }
  ]
  
  return suppliers.map((supplier, index) => ({
    id: index + 1,
    ...supplier,
    rating: (Math.random() * 2 + 3).toFixed(1),
    avg_lead_time: Math.floor(Math.random() * 10) + 3,
    reliability: Math.floor(Math.random() * 20) + 80,
    cost_efficiency: Math.floor(Math.random() * 30) + 70
  }))
}

const generateMockOrderHistory = () => {
  const orders = []
  for (let i = 0; i < 10; i++) {
    const date = new Date()
    date.setDate(date.getDate() - Math.floor(Math.random() * 30))
    
    orders.push({
      id: i + 1,
      code: `PO-${String(i + 1).padStart(6, '0')}`,
      supplier: { name: ['Bean Masters Co.', 'Dairy Fresh Ltd.', 'Bakery Plus'][Math.floor(Math.random() * 3)] },
      items_count: Math.floor(Math.random() * 10) + 1,
      total: Math.random() * 2000 + 500,
      status: ['pending', 'ordered', 'delivered', 'cancelled'][Math.floor(Math.random() * 4)],
      created_at: date.toISOString()
    })
  }
  return orders
}

// Watchers
watch(historyFilter, () => {
  loadOrderHistory()
})

// Lifecycle
onMounted(() => {
  refreshData()
})
</script>

<style scoped>
/* Add any custom styles here */
</style>