<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
              <FileText class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
            </div>
            Purchase Order Management
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            Create, track, and manage all purchase orders
          </p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="exportPOs"
            class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
          >
            <Download class="w-4 h-4" />
            Export
          </button>
          <button
            @click="showCreateModal = true"
            class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
          >
            <Plus class="w-4 h-4" />
            Create PO
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
            <Clock class="w-6 h-6 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Pending</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.pending || 0 }}</p>
            <p class="text-xs text-gray-500">Awaiting approval</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
            <Truck class="w-6 h-6 text-orange-600 dark:text-orange-400" />
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">In Transit</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ stats.in_transit || 0 }}</p>
            <p class="text-xs text-gray-500">Being delivered</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
            <CheckCircle class="w-6 h-6 text-green-600 dark:text-green-400" />
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Received</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.received || 0 }}</p>
            <p class="text-xs text-gray-500">This month</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
            <DollarSign class="w-6 h-6 text-purple-600 dark:text-purple-400" />
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Value</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">${{ formatCurrency(stats.total_value || 0) }}</p>
            <p class="text-xs text-gray-500">Active orders</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
      <div class="flex flex-col lg:flex-row gap-4">
        <!-- Search -->
        <div class="flex-1">
          <div class="relative">
            <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
            <input
              v-model="searchQuery"
              placeholder="Search by PO number, supplier name..."
              class="w-full pl-9 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
            />
          </div>
        </div>

        <!-- Filters -->
        <div class="flex gap-3">
          <select
            v-model="filters.status"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option value="">All Status</option>
            <option value="draft">Draft</option>
            <option value="sent">Sent</option>
            <option value="confirmed">Confirmed</option>
            <option value="partially_received">Partially Received</option>
            <option value="received">Received</option>
            <option value="cancelled">Cancelled</option>
          </select>

          <select
            v-model="filters.supplier"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option value="">All Suppliers</option>
            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
              {{ supplier.name }}
            </option>
          </select>

          <input
            type="date"
            v-model="filters.dateFrom"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          />

          <button
            @click="clearFilters"
            v-if="hasActiveFilters"
            class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
          >
            Clear
          </button>
        </div>
      </div>
    </div>

    <!-- Purchase Orders Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Purchase Orders</h3>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                PO Details
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
                Progress
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="po in filteredPOs" :key="po.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                  <div class="text-sm font-medium text-gray-900 dark:text-white">{{ po.po_number }}</div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(po.created_at) }}</div>
                  <div v-if="po.expected_delivery_date" class="text-xs text-blue-600 dark:text-blue-400">
                    Expected: {{ formatDate(po.expected_delivery_date) }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                      <Building class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ po.supplier?.name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ po.supplier?.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 dark:text-white">{{ po.items_count }} items</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ po.items?.slice(0, 2).map(item => item.product?.name).join(', ') }}
                  <span v-if="po.items_count > 2">...</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">${{ formatCurrency(po.total_amount) }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ po.items_count }} items</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(po.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                  {{ formatStatus(po.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div 
                      :class="getProgressBarClass(po.status)"
                      class="h-2 rounded-full transition-all duration-300"
                      :style="{ width: `${po.progress?.percentage || 0}%` }"
                    ></div>
                  </div>
                  <span class="text-xs text-gray-500 dark:text-gray-400">{{ po.progress?.percentage || 0 }}%</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  {{ po.progress?.received_items || 0 }}/{{ po.progress?.total_items || 0 }} received
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center gap-2">
                  <button
                    @click="viewPO(po)"
                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                  >
                    <Eye class="w-4 h-4" />
                  </button>
                  <button
                    @click="editPO(po)"
                    v-if="po.status === 'draft'"
                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                  >
                    <Edit class="w-4 h-4" />
                  </button>
                  <button
                    @click="duplicatePO(po)"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                  >
                    <Copy class="w-4 h-4" />
                  </button>
                  <div class="relative">
                    <button
                      @click="toggleDropdown(po.id)"
                      class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >
                      <MoreVertical class="w-4 h-4" />
                    </button>
                    <div
                      v-if="showDropdown === po.id"
                      class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg z-10 border border-gray-200 dark:border-gray-700"
                    >
                      <div class="py-1">
                        <button
                          @click="sendPO(po)"
                          v-if="po.status === 'draft'"
                          class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 w-full text-left"
                        >
                          <Send class="w-4 h-4" />
                          Send to Supplier
                        </button>
                        <button
                          @click="markAsReceived(po)"
                          v-if="['sent', 'confirmed'].includes(po.status)"
                          class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 w-full text-left"
                        >
                          <Package class="w-4 h-4" />
                          Mark as Received
                        </button>
                        <button
                          @click="printPO(po)"
                          class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 w-full text-left"
                        >
                          <Printer class="w-4 h-4" />
                          Print PO
                        </button>
                        <button
                          @click="cancelPO(po)"
                          v-if="!['received', 'cancelled'].includes(po.status)"
                          class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 w-full text-left"
                        >
                          <X class="w-4 h-4" />
                          Cancel Order
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredPOs.length) }} of {{ filteredPOs.length }} orders
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="currentPage = Math.max(1, currentPage - 1)"
              :disabled="currentPage === 1"
              class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
            >
              Previous
            </button>
            <span class="px-3 py-1">{{ currentPage }} of {{ totalPages }}</span>
            <button
              @click="currentPage = Math.min(totalPages, currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create PO Modal -->
    <CreatePurchaseOrderModal
      :show="showCreateModal"
      @close="showCreateModal = false"
      @created="onPOCreated"
    />

    <!-- View PO Modal -->
    <ViewPurchaseOrderModal
      :show="showViewModal"
      :purchase-order="selectedPO"
      @close="showViewModal = false"
      @updated="onPOUpdated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import {
  FileText, Download, Plus, Clock, Truck, CheckCircle, DollarSign, Search,
  Building, Eye, Edit, Copy, MoreVertical, Send, Package, Printer, X
} from 'lucide-vue-next'
import axios from 'axios'
import CreatePurchaseOrderModal from '@/Components/Reorder/CreatePurchaseOrderModal.vue'
import ViewPurchaseOrderModal from '@/Components/PurchaseOrder/ViewPurchaseOrderModal.vue'

// Reactive state
const loading = ref(false)
const showCreateModal = ref(false)
const showViewModal = ref(false)
const showDropdown = ref(null)
const selectedPO = ref(null)
const currentPage = ref(1)
const itemsPerPage = ref(10)

const searchQuery = ref('')
const filters = ref({
  status: '',
  supplier: '',
  dateFrom: ''
})

const stats = ref({
  pending: 0,
  in_transit: 0,
  received: 0,
  total_value: 0
})

const purchaseOrders = ref([])
const suppliers = ref([])

// Computed properties
const hasActiveFilters = computed(() => {
  return searchQuery.value || filters.value.status || filters.value.supplier || filters.value.dateFrom
})

const filteredPOs = computed(() => {
  let filtered = purchaseOrders.value

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(po =>
      po.po_number.toLowerCase().includes(query) ||
      po.supplier?.name.toLowerCase().includes(query)
    )
  }

  // Apply status filter
  if (filters.value.status) {
    filtered = filtered.filter(po => po.status === filters.value.status)
  }

  // Apply supplier filter
  if (filters.value.supplier) {
    filtered = filtered.filter(po => po.supplier_id === filters.value.supplier)
  }

  // Apply date filter
  if (filters.value.dateFrom) {
    filtered = filtered.filter(po => po.created_at >= filters.value.dateFrom)
  }

  return filtered
})

const paginatedPOs = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredPOs.value.slice(start, end)
})

const totalPages = computed(() => Math.ceil(filteredPOs.value.length / itemsPerPage.value))

// Methods
const loadData = async () => {
  loading.value = true
  try {
    await Promise.all([
      loadPurchaseOrders(),
      loadSuppliers(),
      loadStats()
    ])
  } catch (error) {
    console.error('Error loading data:', error)
  } finally {
    loading.value = false
  }
}

const loadPurchaseOrders = async () => {
  try {
    const response = await axios.get('/api/purchase-orders')
    purchaseOrders.value = response.data.data || generateMockPOs()
  } catch (error) {
    // Generate mock data if API not available
    purchaseOrders.value = generateMockPOs()
  }
}

const loadSuppliers = async () => {
  try {
    const response = await axios.get('/api/suppliers')
    suppliers.value = response.data.data || generateMockSuppliers()
  } catch (error) {
    suppliers.value = generateMockSuppliers()
  }
}

const loadStats = async () => {
  try {
    const response = await axios.get('/api/purchase-orders/stats')
    stats.value = response.data
  } catch (error) {
    // Generate mock stats
    stats.value = {
      pending: Math.floor(Math.random() * 20) + 5,
      in_transit: Math.floor(Math.random() * 15) + 3,
      received: Math.floor(Math.random() * 50) + 20,
      total_value: Math.random() * 100000 + 25000
    }
  }
}

const clearFilters = () => {
  searchQuery.value = ''
  filters.value = {
    status: '',
    supplier: '',
    dateFrom: ''
  }
  currentPage.value = 1
}

const toggleDropdown = (poId) => {
  showDropdown.value = showDropdown.value === poId ? null : poId
}

const viewPO = (po) => {
  selectedPO.value = po
  showViewModal.value = true
  showDropdown.value = null
}

const editPO = (po) => {
  selectedPO.value = po
  showCreateModal.value = true
  showDropdown.value = null
}

const duplicatePO = async (po) => {
  try {
    const response = await axios.post(`/api/purchase-orders/${po.id}/duplicate`)
    await loadPurchaseOrders()
    showDropdown.value = null
  } catch (error) {
    console.error('Error duplicating PO:', error)
  }
}

const sendPO = async (po) => {
  try {
    await axios.post(`/api/purchase-orders/${po.id}/send`)
    await loadPurchaseOrders()
    showDropdown.value = null
  } catch (error) {
    console.error('Error sending PO:', error)
  }
}

const markAsReceived = async (po) => {
  try {
    await axios.post(`/api/purchase-orders/${po.id}/receive`)
    await loadPurchaseOrders()
    showDropdown.value = null
  } catch (error) {
    console.error('Error marking PO as received:', error)
  }
}

const printPO = (po) => {
  window.open(`/api/purchase-orders/${po.id}/print`, '_blank')
  showDropdown.value = null
}

const cancelPO = async (po) => {
  if (confirm('Are you sure you want to cancel this purchase order?')) {
    try {
      await axios.post(`/api/purchase-orders/${po.id}/cancel`)
      await loadPurchaseOrders()
      showDropdown.value = null
    } catch (error) {
      console.error('Error cancelling PO:', error)
    }
  }
}

const exportPOs = async () => {
  try {
    const response = await axios.get('/api/purchase-orders/export', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'purchase-orders.xlsx')
    document.body.appendChild(link)
    link.click()
  } catch (error) {
    console.error('Error exporting POs:', error)
  }
}

const onPOCreated = () => {
  loadPurchaseOrders()
  showCreateModal.value = false
}

const onPOUpdated = () => {
  loadPurchaseOrders()
  showViewModal.value = false
}

// Utility methods
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

const formatStatus = (status) => {
  return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')
}

const getStatusBadgeClass = (status) => {
  const classes = {
    'draft': 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
    'sent': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    'confirmed': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    'partially_received': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    'received': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    'cancelled': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
  }
  return classes[status] || classes.draft
}

const getProgressBarClass = (status) => {
  const classes = {
    'draft': 'bg-gray-400',
    'sent': 'bg-blue-500',
    'confirmed': 'bg-yellow-500',
    'partially_received': 'bg-orange-500',
    'received': 'bg-green-500',
    'cancelled': 'bg-red-500'
  }
  return classes[status] || classes.draft
}

// Mock data generators
const generateMockPOs = () => {
  const statuses = ['draft', 'sent', 'confirmed', 'partially_received', 'received', 'cancelled']
  const pos = []

  for (let i = 1; i <= 25; i++) {
    const status = statuses[Math.floor(Math.random() * statuses.length)]
    const itemsCount = Math.floor(Math.random() * 15) + 1
    const totalAmount = Math.random() * 10000 + 1000

    pos.push({
      id: i,
      po_number: `PO-${String(i).padStart(6, '0')}`,
      supplier_id: Math.floor(Math.random() * 5) + 1,
      supplier: {
        id: Math.floor(Math.random() * 5) + 1,
        name: ['Supply Chain Co.', 'Global Distributors', 'Fresh Foods Ltd.', 'Tech Components Inc.', 'Office Supplies Pro'][Math.floor(Math.random() * 5)],
        email: 'contact@supplier.com'
      },
      items_count: itemsCount,
      total_amount: totalAmount,
      status: status,
      progress: {
        percentage: status === 'received' ? 100 : Math.floor(Math.random() * 100),
        received_items: status === 'received' ? itemsCount : Math.floor(Math.random() * itemsCount),
        total_items: itemsCount
      },
      created_at: new Date(Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000).toISOString(),
      expected_delivery_date: Math.random() > 0.5 ? new Date(Date.now() + Math.random() * 14 * 24 * 60 * 60 * 1000).toISOString() : null,
      items: Array.from({ length: Math.min(3, itemsCount) }, (_, idx) => ({
        product: { name: `Product ${idx + 1}` }
      }))
    })
  }

  return pos.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
}

const generateMockSuppliers = () => {
  return [
    { id: 1, name: 'Supply Chain Co.' },
    { id: 2, name: 'Global Distributors' },
    { id: 3, name: 'Fresh Foods Ltd.' },
    { id: 4, name: 'Tech Components Inc.' },
    { id: 5, name: 'Office Supplies Pro' }
  ]
}

// Watchers
watch(searchQuery, () => {
  currentPage.value = 1
})

watch(() => filters.value, () => {
  currentPage.value = 1
}, { deep: true })

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    showDropdown.value = null
  }
}

// Lifecycle
onMounted(() => {
  loadData()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Add any custom styles here */
</style>