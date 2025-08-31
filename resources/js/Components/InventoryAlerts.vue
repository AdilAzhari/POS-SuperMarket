<template>
  <div class="inventory-alerts">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
          <div class="p-2 bg-yellow-100 rounded-lg">
            <AlertTriangle class="h-6 w-6 text-yellow-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Low Stock Items</p>
            <p class="text-2xl font-bold text-gray-900">{{ summary.total_low_stock_products || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
          <div class="p-2 bg-red-100 rounded-lg">
            <AlertCircle class="h-6 w-6 text-red-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Critical Items</p>
            <p class="text-2xl font-bold text-red-600">{{ summary.critical_products || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <Building class="h-6 w-6 text-blue-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Stores Affected</p>
            <p class="text-2xl font-bold text-gray-900">{{ summary.stores_affected || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <RefreshCw class="h-6 w-6 text-green-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Last Updated</p>
              <p class="text-sm text-gray-900">{{ lastUpdated }}</p>
            </div>
          </div>
          <button
            @click="refreshAlerts"
            :disabled="isLoading"
            class="p-2 text-gray-400 hover:text-gray-600 disabled:opacity-50"
          >
            <RefreshCw :class="['h-4 w-4', { 'animate-spin': isLoading }]" />
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
      </div>
      <div class="p-4">
        <div class="flex flex-wrap gap-3">
          <button
            @click="sendAlerts"
            :disabled="isLoading"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
          >
            <Send class="h-4 w-4 mr-2" />
            Send Alerts
          </button>
          
          <button
            @click="updateThresholds"
            :disabled="isLoading"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            <Settings class="h-4 w-4 mr-2" />
            Update Thresholds
          </button>
          
          <button
            @click="viewReorderSuggestions"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            <ShoppingCart class="h-4 w-4 mr-2" />
            Reorder Suggestions
          </button>
          
          <button
            @click="exportReport"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            <Download class="h-4 w-4 mr-2" />
            Export Report
          </button>
        </div>
      </div>
    </div>

    <!-- Critical Items Alert -->
    <div v-if="criticalItems.length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <div class="flex">
        <AlertCircle class="h-5 w-5 text-red-400" />
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">
            Critical Stock Alert
          </h3>
          <div class="mt-2 text-sm text-red-700">
            <p>{{ criticalItems.length }} products need immediate attention.</p>
            <div class="mt-2">
              <button
                @click="showCriticalModal = true"
                class="text-red-800 font-medium hover:text-red-900"
              >
                View Critical Items →
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Store Selector -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900">Inventory by Store</h3>
          <select
            v-model="selectedStoreId"
            @change="fetchStoreData"
            class="block w-40 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
          >
            <option value="">All Stores</option>
            <option v-for="store in stores" :key="store.id" :value="store.id">
              {{ store.name }}
            </option>
          </select>
        </div>
      </div>
      
      <!-- Store Data -->
      <div v-if="selectedStoreId" class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <div class="text-center">
            <p class="text-2xl font-bold text-gray-900">{{ storeData.low_stock_count || 0 }}</p>
            <p class="text-sm text-gray-500">Low Stock Items</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-red-600">{{ storeData.critical_count || 0 }}</p>
            <p class="text-sm text-gray-500">Critical Items</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-green-600">${{ storeData.reorder_cost || 0 }}</p>
            <p class="text-sm text-gray-500">Est. Reorder Cost</p>
          </div>
        </div>
        
        <!-- Low Stock Products Table -->
        <div v-if="storeData.products && storeData.products.length > 0">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-md font-medium text-gray-900">Low Stock Products</h4>
            <div class="flex items-center space-x-2">
              <!-- Search -->
              <div class="relative">
                <Search class="w-4 h-4 text-gray-400 absolute left-2 top-2" />
                <input
                  v-model="searchQuery"
                  placeholder="Search products..."
                  class="pl-8 pr-3 py-1 text-sm border rounded focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <!-- Sort -->
              <select
                v-model="sortBy"
                class="text-sm border rounded px-2 py-1 focus:ring-2 focus:ring-blue-500"
              >
                <option value="severity">By Severity</option>
                <option value="name">By Name</option>
                <option value="stock">By Stock</option>
                <option value="threshold">By Threshold</option>
              </select>
              <button
                @click="toggleSortDirection"
                class="p-1 border rounded hover:bg-gray-50"
                :title="sortDirection === 'desc' ? 'Sort ascending' : 'Sort descending'"
              >
                <ArrowUpDown class="w-4 h-4" :class="sortDirection === 'asc' ? '' : 'rotate-180'" />
              </button>
            </div>
          </div>
          
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Product
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    SKU
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Current Stock
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Threshold
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in paginatedProducts" :key="item.product.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ item.product.name }}</div>
                      <div class="text-sm text-gray-500">{{ item.product.category?.name }}</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ item.product.sku }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStockColorClass(item.current_stock, item.threshold)">
                      {{ item.current_stock }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ item.threshold }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getSeverityBadgeClass(item.severity)">
                      {{ getSeverityText(item.severity) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button
                      @click="createReorderSuggestion(item)"
                      class="text-blue-600 hover:text-blue-900 mr-3"
                    >
                      Reorder
                    </button>
                    <button
                      @click="viewProductHistory(item.product.id)"
                      class="text-gray-600 hover:text-gray-900"
                    >
                      History
                    </button>
                  </td>
                </tr>
                <tr v-if="filteredProducts.length === 0">
                  <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                    No products match your search criteria.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <div v-if="totalPages > 1" class="mt-4 flex items-center justify-between">
            <div class="text-sm text-gray-500">
              Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredProducts.length) }} of {{ filteredProducts.length }} products
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="currentPage = Math.max(1, currentPage - 1)"
                :disabled="currentPage === 1"
                class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-50"
              >
                Previous
              </button>
              <div class="flex space-x-1">
                <button
                  v-for="page in visiblePages"
                  :key="page"
                  @click="currentPage = page"
                  :class="[
                    'px-3 py-1 border rounded',
                    page === currentPage ? 'bg-blue-600 text-white' : 'hover:bg-gray-50'
                  ]"
                >
                  {{ page }}
                </button>
              </div>
              <button
                @click="currentPage = Math.min(totalPages, currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-50"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- All Stores Summary -->
      <div v-else class="p-4">
        <div v-if="lowStockByStore.length > 0">
          <h4 class="text-md font-medium text-gray-900 mb-3">Stores Summary</h4>
          <div class="space-y-3">
            <div
              v-for="storeData in lowStockByStore"
              :key="storeData.store.id"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
            >
              <div>
                <p class="font-medium text-gray-900">{{ storeData.store.name }}</p>
                <p class="text-sm text-gray-500">
                  {{ storeData.products.length }} low stock items
                  <span v-if="getStoresCriticalCount(storeData.products) > 0" class="text-red-600">
                    ({{ getStoresCriticalCount(storeData.products) }} critical)
                  </span>
                </p>
              </div>
              <button
                @click="selectStore(storeData.store.id)"
                class="text-blue-600 hover:text-blue-900 font-medium"
              >
                View Details →
              </button>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8 text-gray-500">
          <Package class="mx-auto h-12 w-12 text-gray-400 mb-4" />
          <p>No low stock alerts at this time</p>
        </div>
      </div>
    </div>

    <!-- Critical Items Modal -->
    <Modal v-if="showCriticalModal" @close="showCriticalModal = false">
      <div class="bg-white px-6 py-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Critical Stock Items</h3>
          <button
            @click="showCriticalModal = false"
            class="text-gray-400 hover:text-gray-600"
          >
            <X class="h-6 w-6" />
          </button>
        </div>
        
        <div class="max-h-96 overflow-y-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Store</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="item in criticalItems" :key="`${item.store.id}-${item.product.id}`">
                <td class="px-4 py-3 text-sm text-gray-900">{{ item.store.name }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ item.product.name }}</td>
                <td class="px-4 py-3 text-sm">
                  <span :class="item.is_out_of_stock ? 'text-red-600 font-bold' : 'text-orange-600'">
                    {{ item.current_stock }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm">
                  <span :class="item.is_out_of_stock ? 'text-red-600' : 'text-orange-600'">
                    {{ item.is_out_of_stock ? 'OUT OF STOCK' : 'CRITICAL' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { 
  AlertTriangle, AlertCircle, Building, RefreshCw, Send, Settings, 
  ShoppingCart, Download, Package, X, Search, ArrowUpDown
} from 'lucide-vue-next'
import Modal from './Modal.vue'
import axios from 'axios'

// Reactive data
const isLoading = ref(false)
const selectedStoreId = ref('')
const showCriticalModal = ref(false)
const lastUpdated = ref('')

// Search, filter, sort, and pagination state
const searchQuery = ref('')
const sortBy = ref('severity')
const sortDirection = ref('desc')
const currentPage = ref(1)
const itemsPerPage = ref(10)

const summary = ref({
  total_low_stock_products: 0,
  critical_products: 0,
  stores_affected: 0
})

const lowStockByStore = ref([])
const criticalItems = ref([])
const stores = ref([])
const storeData = ref({})

// Computed
const lastUpdated = computed(() => {
  return new Date().toLocaleString()
})

const filteredProducts = computed(() => {
  if (!storeData.value.products) return []
  
  let products = storeData.value.products
  
  // Apply search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    products = products.filter(item =>
      item.product.name.toLowerCase().includes(query) ||
      item.product.sku.toLowerCase().includes(query) ||
      (item.product.category?.name || '').toLowerCase().includes(query)
    )
  }
  
  // Apply sorting
  products = [...products].sort((a, b) => {
    let aValue, bValue
    
    switch (sortBy.value) {
      case 'severity':
        aValue = a.severity
        bValue = b.severity
        break
      case 'name':
        aValue = a.product.name.toLowerCase()
        bValue = b.product.name.toLowerCase()
        break
      case 'stock':
        aValue = a.current_stock
        bValue = b.current_stock
        break
      case 'threshold':
        aValue = a.threshold
        bValue = b.threshold
        break
      default:
        return 0
    }
    
    if (sortDirection.value === 'asc') {
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
    } else {
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
    }
  })
  
  return products
})

const totalPages = computed(() => Math.ceil(filteredProducts.value.length / itemsPerPage.value))

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredProducts.value.slice(start, end)
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const delta = 2
  
  let start = Math.max(1, current - delta)
  let end = Math.min(total, current + delta)
  
  if (end - start < 2 * delta) {
    start = Math.max(1, end - 2 * delta)
    end = Math.min(total, start + 2 * delta)
  }
  
  const pages = []
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

// Methods
const fetchAlerts = async () => {
  try {
    isLoading.value = true
    const response = await axios.get('/api/inventory-alerts')
    
    if (response.data.success) {
      lowStockByStore.value = response.data.data.low_stock_by_store || []
      criticalItems.value = response.data.data.critical_stock || []
      summary.value = response.data.data.summary || {}
    }
  } catch (error) {
    console.error('Failed to fetch inventory alerts:', error)
  } finally {
    isLoading.value = false
  }
}

const fetchStoreData = async () => {
  if (!selectedStoreId.value) {
    storeData.value = {}
    return
  }

  try {
    const response = await axios.get(`/api/inventory-alerts/store/${selectedStoreId.value}`)
    const reorderResponse = await axios.get(`/api/inventory-alerts/store/${selectedStoreId.value}/reorder-suggestions`)
    
    if (response.data.success) {
      storeData.value = {
        products: response.data.data.products || [],
        low_stock_count: response.data.data.count || 0,
        critical_count: (response.data.data.products || []).filter(p => p.severity >= 4).length,
        reorder_cost: reorderResponse.data.success ? reorderResponse.data.data.total_estimated_cost || 0 : 0
      }
    }
  } catch (error) {
    console.error('Failed to fetch store data:', error)
  }
}

const refreshAlerts = async () => {
  await fetchAlerts()
  if (selectedStoreId.value) {
    await fetchStoreData()
  }
}

const sendAlerts = async () => {
  try {
    isLoading.value = true
    const response = await axios.post('/api/inventory-alerts/send-alerts')
    
    if (response.data.success) {
      alert(`Alerts sent to ${response.data.alerts_sent} managers`)
    }
  } catch (error) {
    console.error('Failed to send alerts:', error)
    alert('Failed to send alerts')
  } finally {
    isLoading.value = false
  }
}

const updateThresholds = async () => {
  if (!selectedStoreId.value) {
    alert('Please select a store first')
    return
  }

  try {
    isLoading.value = true
    const response = await axios.post(`/api/inventory-alerts/store/${selectedStoreId.value}/update-thresholds`)
    
    if (response.data.success) {
      alert(`Updated ${response.data.products_updated} product thresholds`)
      await fetchStoreData()
    }
  } catch (error) {
    console.error('Failed to update thresholds:', error)
    alert('Failed to update thresholds')
  } finally {
    isLoading.value = false
  }
}

const selectStore = (storeId) => {
  selectedStoreId.value = storeId
  fetchStoreData()
}

const getStockColorClass = (current, threshold) => {
  if (current === 0) return 'text-red-600 font-bold'
  if (current <= threshold * 0.5) return 'text-orange-600 font-medium'
  if (current <= threshold) return 'text-yellow-600'
  return 'text-gray-900'
}

const getSeverityBadgeClass = (severity) => {
  const baseClass = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'
  
  switch (severity) {
    case 5: return `${baseClass} bg-red-100 text-red-800`
    case 4: return `${baseClass} bg-orange-100 text-orange-800`
    case 3: return `${baseClass} bg-yellow-100 text-yellow-800`
    case 2: return `${baseClass} bg-blue-100 text-blue-800`
    default: return `${baseClass} bg-gray-100 text-gray-800`
  }
}

const getSeverityText = (severity) => {
  switch (severity) {
    case 5: return 'OUT OF STOCK'
    case 4: return 'CRITICAL'
    case 3: return 'LOW'
    case 2: return 'WARNING'
    case 1: return 'NOTICE'
    default: return 'OK'
  }
}

const getStoresCriticalCount = (products) => {
  return products.filter(p => p.severity >= 4).length
}

const toggleSortDirection = () => {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
}

// Placeholder methods for future implementation
const viewReorderSuggestions = () => {
  console.log('View reorder suggestions')
}

const exportReport = () => {
  console.log('Export report')
}

const createReorderSuggestion = (item) => {
  console.log('Create reorder suggestion for:', item.product.name)
}

const viewProductHistory = (productId) => {
  console.log('View product history:', productId)
}

// Lifecycle
onMounted(async () => {
  // Fetch initial data
  await fetchAlerts()
  
  // Fetch stores list (assuming you have this endpoint)
  try {
    const storesResponse = await axios.get('/api/stores')
    if (storesResponse.data) {
      stores.value = storesResponse.data.data || storesResponse.data
    }
  } catch (error) {
    console.error('Failed to fetch stores:', error)
  }
})
</script>

<style scoped>
.inventory-alerts {
  /* Custom styles if needed */
}
</style>