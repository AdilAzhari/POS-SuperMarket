<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold text-gray-900">Payment History</h2>
      <button
        @click="refresh"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
      >
        Refresh
      </button>
    </div>

    <!-- Enhanced Filters and Search -->
    <div class="mb-6 space-y-4">
      <div class="flex flex-col lg:flex-row gap-4">
        <!-- Search -->
        <div class="flex-1">
          <div class="relative">
            <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
            <input
              v-model="searchQuery"
              placeholder="Search by payment code, sale ID, or customer"
              class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
        
        <!-- Results count -->
        <div class="text-sm text-gray-500 flex items-center">
          {{ paginatedPayments.length }} of {{ filteredPayments.length }} payments
        </div>
      </div>

      <!-- Filters Row -->
      <div class="flex flex-wrap gap-4 items-center">
        <!-- Payment Method Filter -->
        <div>
          <select
            v-model="selectedMethod"
            class="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Methods</option>
            <option v-for="method in paymentMethods" :key="method.code" :value="method.code">
              {{ method.name }}
            </option>
          </select>
        </div>

        <!-- Status Filter -->
        <div>
          <select
            v-model="statusFilter"
            class="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Status</option>
            <option value="completed">Completed</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>
        </div>

        <!-- Date Range -->
        <div class="flex items-center gap-2">
          <input
            v-model="dateRange.start"
            type="date"
            class="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
          />
          <span class="text-gray-400">to</span>
          <input
            v-model="dateRange.end"
            type="date"
            class="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Amount Range -->
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">Amount:</span>
          <input
            v-model.number="amountRange.min"
            type="number"
            placeholder="Min"
            class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
            min="0"
            step="0.01"
          />
          <span class="text-gray-400">-</span>
          <input
            v-model.number="amountRange.max"
            type="number"
            placeholder="Max"
            class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
            min="0"
            step="0.01"
          />
        </div>

        <!-- Clear Filters -->
        <button
          v-if="hasActiveFilters"
          @click="clearFilters"
          class="text-sm text-blue-600 hover:text-blue-800"
        >
          Clear filters
        </button>
      </div>

      <!-- Sorting -->
      <div class="flex items-center gap-2">
        <span class="text-sm text-gray-600">Sort by:</span>
        <select
          v-model="sortBy"
          class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
        >
          <option value="created_at">Date</option>
          <option value="amount">Amount</option>
          <option value="payment_method">Payment Method</option>
          <option value="payment_code">Payment Code</option>
          <option value="status">Status</option>
        </select>
        <button
          @click="toggleSortDirection"
          class="p-2 border rounded-lg hover:bg-gray-50"
          :title="sortDirection === 'asc' ? 'Sort descending' : 'Sort ascending'"
        >
          <ArrowUpDown class="w-4 h-4" :class="sortDirection === 'desc' ? 'rotate-180' : ''" />
        </button>
      </div>
    </div>

    <!-- Payment Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-green-50 p-4 rounded-lg">
        <div class="text-2xl font-bold text-green-600">{{ stats.total_payments }}</div>
        <div class="text-sm text-green-700">Total Payments</div>
      </div>
      <div class="bg-blue-50 p-4 rounded-lg">
        <div class="text-2xl font-bold text-blue-600">RM {{ stats.total_amount?.toFixed(2) || '0.00' }}</div>
        <div class="text-sm text-blue-700">Total Amount</div>
      </div>
      <div class="bg-orange-50 p-4 rounded-lg">
        <div class="text-2xl font-bold text-orange-600">RM {{ stats.total_fees?.toFixed(2) || '0.00' }}</div>
        <div class="text-sm text-orange-700">Total Fees</div>
      </div>
      <div class="bg-purple-50 p-4 rounded-lg">
        <div class="text-2xl font-bold text-purple-600">RM {{ stats.net_amount?.toFixed(2) || '0.00' }}</div>
        <div class="text-sm text-purple-700">Net Amount</div>
      </div>
    </div>

    <!-- Payment Methods Breakdown -->
    <div class="mb-6">
      <h3 class="text-lg font-medium text-gray-900 mb-3">Payment Methods Breakdown</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div 
          v-for="(methodStats, method) in stats.by_method" 
          :key="method"
          class="bg-gray-50 p-4 rounded-lg"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="font-medium">{{ getMethodName(method) }}</span>
            <span class="text-sm text-gray-500">{{ methodStats.count }} transactions</span>
          </div>
          <div class="text-lg font-bold text-gray-900">RM {{ methodStats.amount?.toFixed(2) || '0.00' }}</div>
          <div class="text-sm text-gray-600">Fees: RM {{ methodStats.fees?.toFixed(2) || '0.00' }}</div>
        </div>
      </div>
    </div>

    <!-- Payment History Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Payment Code
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Sale ID
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Method
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Amount
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Fee
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Date
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="payment in paginatedPayments" :key="payment.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ payment.payment_code }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ payment.sale?.code || payment.sale_id }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getMethodBadgeClass(payment.payment_method)" class="px-2 py-1 text-xs rounded-full">
                {{ getMethodName(payment.payment_method) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              RM {{ payment.amount }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              RM {{ payment.fee || '0.00' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStatusBadgeClass(payment.status)" class="px-2 py-1 text-xs rounded-full">
                {{ payment.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(payment.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <button
                v-if="payment.status === 'completed'"
                @click="showRefundModal(payment)"
                class="text-red-600 hover:text-red-900"
              >
                Refund
              </button>
              <button
                @click="showPaymentDetails(payment)"
                class="text-blue-600 hover:text-blue-900 ml-3"
              >
                Details
              </button>
            </td>
          </tr>
          <tr v-if="payments.length === 0">
            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
              No payments found
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="mt-6 flex items-center justify-between">
      <div class="text-sm text-gray-500">
        Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredPayments.length) }} of {{ filteredPayments.length }} payments
      </div>
      <div class="flex items-center space-x-2">
        <button
          @click="currentPage = Math.max(1, currentPage - 1)"
          :disabled="currentPage === 1"
          class="px-3 py-1 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Previous
        </button>
        <div class="flex space-x-1">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="currentPage = page"
            :class="[
              'px-3 py-1 border rounded-lg',
              page === currentPage
                ? 'bg-blue-600 text-white border-blue-600'
                : 'hover:bg-gray-50'
            ]"
          >
            {{ page }}
          </button>
        </div>
        <button
          @click="currentPage = Math.min(totalPages, currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="px-3 py-1 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
    </div>

    <!-- Payment Details Modal -->
    <div v-if="showDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Payment Details</h3>
          <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-600">
            <X class="w-5 h-5" />
          </button>
        </div>
        <div v-if="selectedPayment" class="space-y-3">
          <div>
            <label class="text-sm font-medium text-gray-500">Payment Code:</label>
            <div class="text-sm text-gray-900">{{ selectedPayment.payment_code }}</div>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Method:</label>
            <div class="text-sm text-gray-900">{{ getMethodName(selectedPayment.payment_method) }}</div>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Amount:</label>
            <div class="text-sm text-gray-900">RM {{ selectedPayment.amount }}</div>
          </div>
          <div v-if="selectedPayment.fee > 0">
            <label class="text-sm font-medium text-gray-500">Fee:</label>
            <div class="text-sm text-gray-900">RM {{ selectedPayment.fee }}</div>
          </div>
          <div v-if="selectedPayment.card_last_four">
            <label class="text-sm font-medium text-gray-500">Card:</label>
            <div class="text-sm text-gray-900">**** {{ selectedPayment.card_last_four }}</div>
          </div>
          <div v-if="selectedPayment.tng_reference">
            <label class="text-sm font-medium text-gray-500">TNG Reference:</label>
            <div class="text-sm text-gray-900">{{ selectedPayment.tng_reference }}</div>
          </div>
          <div v-if="selectedPayment.gateway_reference">
            <label class="text-sm font-medium text-gray-500">Gateway Reference:</label>
            <div class="text-sm text-gray-900">{{ selectedPayment.gateway_reference }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useMessageModal } from '@/composables/useModal.js'
import Modal from '@/Components/Modal.vue'
import { X, Search, ArrowUpDown } from 'lucide-vue-next'
import axios from 'axios'

// Data
const payments = ref([])
const paymentMethods = ref([])
const stats = ref({})
const selectedMethod = ref('')
const statusFilter = ref('')
const searchQuery = ref('')
const showDetailsModal = ref(false)
const selectedPayment = ref(null)
const isLoading = ref(false)
const modal = useMessageModal()

// Filters and sorting
const dateRange = ref({
  start: '',
  end: ''
})
const amountRange = ref({
  min: null,
  max: null
})
const sortBy = ref('created_at')
const sortDirection = ref('desc')

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(20)

// Computed
const hasActiveFilters = computed(() => {
  return selectedMethod.value || statusFilter.value || searchQuery.value || 
         dateRange.value.start || dateRange.value.end || 
         amountRange.value.min !== null || amountRange.value.max !== null
})

const filteredPayments = computed(() => {
  let filtered = payments.value
  
  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(p => 
      p.payment_code?.toLowerCase().includes(query) ||
      p.sale_id?.toString().includes(query) ||
      p.sale?.code?.toLowerCase().includes(query)
    )
  }
  
  // Payment method filter
  if (selectedMethod.value) {
    filtered = filtered.filter(p => p.payment_method === selectedMethod.value)
  }
  
  // Status filter
  if (statusFilter.value) {
    filtered = filtered.filter(p => p.status === statusFilter.value)
  }
  
  // Date range filter
  if (dateRange.value.start) {
    filtered = filtered.filter(p => new Date(p.created_at) >= new Date(dateRange.value.start))
  }
  if (dateRange.value.end) {
    filtered = filtered.filter(p => new Date(p.created_at) <= new Date(dateRange.value.end + ' 23:59:59'))
  }
  
  // Amount range filter
  if (amountRange.value.min !== null) {
    filtered = filtered.filter(p => parseFloat(p.amount) >= amountRange.value.min)
  }
  if (amountRange.value.max !== null) {
    filtered = filtered.filter(p => parseFloat(p.amount) <= amountRange.value.max)
  }
  
  // Apply sorting
  filtered.sort((a, b) => {
    let aValue = a[sortBy.value]
    let bValue = b[sortBy.value]
    
    // Handle specific field types
    if (sortBy.value === 'amount') {
      aValue = parseFloat(aValue || 0)
      bValue = parseFloat(bValue || 0)
    } else if (sortBy.value === 'created_at') {
      aValue = new Date(aValue || 0)
      bValue = new Date(bValue || 0)
    } else {
      // String comparison
      aValue = (aValue || '').toString().toLowerCase()
      bValue = (bValue || '').toString().toLowerCase()
    }
    
    if (sortDirection.value === 'asc') {
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
    } else {
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
    }
  })
  
  return filtered
})

const totalPages = computed(() => Math.ceil(filteredPayments.value.length / itemsPerPage.value))

const paginatedPayments = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredPayments.value.slice(start, end)
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
const fetchPayments = async () => {
  isLoading.value = true
  try {
    // Fetch payment statistics
    const paymentsResponse = await axios.get('/api/payments/stats')
    stats.value = paymentsResponse.data

    // Fetch payment methods
    const methodsResponse = await axios.get('/api/payment-methods')
    paymentMethods.value = methodsResponse.data.methods

    // Fetch actual payment data
    const paymentsListResponse = await axios.get('/api/payments/list?per_page=100')
    const paymentsData = Array.isArray(paymentsListResponse.data?.data) 
      ? paymentsListResponse.data.data 
      : paymentsListResponse.data

    payments.value = paymentsData.map(payment => ({
      id: payment.id,
      payment_code: payment.payment_code,
      sale_id: payment.sale_id,
      sale: payment.sale || { code: payment.sale?.code || `TXN-${String(payment.sale_id).padStart(6, '0')}` },
      payment_method: payment.payment_method,
      amount: parseFloat(payment.amount || 0).toFixed(2),
      fee: parseFloat(payment.fee || 0).toFixed(2),
      status: payment.status,
      created_at: payment.created_at,
      processed_at: payment.processed_at,
      card_last_four: payment.card_last_four,
      tng_reference: payment.tng_reference,
      gateway_reference: payment.gateway_reference
    }))
  } catch (error) {
    console.error('Failed to fetch payments:', error)
    // Fallback to sales data if payments API fails
    try {
      const salesResponse = await axios.get('/api/sales?per_page=100')
      const salesData = Array.isArray(salesResponse.data?.data) ? salesResponse.data.data : []
      
      payments.value = salesData
        .filter(sale => sale.id && sale.payment_method)
        .map(sale => ({
          id: sale.id,
          payment_code: `PAY-${String(sale.id).padStart(6, '0')}`,
          sale_id: sale.id,
          sale: { code: sale.code || `TXN-${String(sale.id).padStart(6, '0')}` },
          payment_method: sale.payment_method,
          amount: parseFloat(sale.total || 0).toFixed(2),
          fee: '0.00',
          status: 'completed',
          created_at: sale.created_at,
          processed_at: sale.paid_at,
          card_last_four: null,
          tng_reference: null,
          gateway_reference: null
        }))
    } catch (fallbackError) {
      console.error('Failed to fetch sales as fallback:', fallbackError)
      payments.value = []
    }
    
    // Set empty defaults if all APIs fail
    stats.value = {
      total_payments: 0,
      total_amount: 0,
      total_fees: 0,
      net_amount: 0,
      by_method: {}
    }
  } finally {
    isLoading.value = false
  }
}

const refresh = () => {
  fetchPayments()
}

const showPaymentDetails = (payment) => {
  selectedPayment.value = payment
  showDetailsModal.value = true
}

const showRefundModal = async (payment) => {
  const confirmed = await modal.showConfirm(`Are you sure you want to refund payment ${payment.payment_code}? This action cannot be undone.`)
  if (confirmed) {
    try {
      // Handle refund logic here
      // await axios.post(`/api/payments/${payment.id}/refund`, { reason: 'Customer request' })
      await modal.showInfo('Refund functionality would be implemented here. The API call would process the actual refund.')
    } catch (error) {
      await modal.showError('Failed to process refund. Please try again.')
    }
  }
}

const getMethodName = (method) => {
  const methodMap = {
    'cash': 'Cash',
    'stripe': 'Stripe',
    'visa': 'Visa',
    'mastercard': 'Mastercard',
    'amex': 'American Express',
    'tng': 'Touch n Go'
  }
  return methodMap[method] || method
}

const getMethodBadgeClass = (method) => {
  const classMap = {
    'cash': 'bg-green-100 text-green-800',
    'stripe': 'bg-purple-100 text-purple-800',
    'visa': 'bg-blue-100 text-blue-800',
    'mastercard': 'bg-red-100 text-red-800',
    'amex': 'bg-indigo-100 text-indigo-800',
    'tng': 'bg-yellow-100 text-yellow-800'
  }
  return classMap[method] || 'bg-gray-100 text-gray-800'
}

const getStatusBadgeClass = (status) => {
  const classMap = {
    'completed': 'bg-green-100 text-green-800',
    'pending': 'bg-yellow-100 text-yellow-800',
    'failed': 'bg-red-100 text-red-800',
    'refunded': 'bg-gray-100 text-gray-800'
  }
  return classMap[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-MY', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Filter and sorting functions
const clearFilters = () => {
  selectedMethod.value = ''
  statusFilter.value = ''
  searchQuery.value = ''
  dateRange.value.start = ''
  dateRange.value.end = ''
  amountRange.value.min = null
  amountRange.value.max = null
  currentPage.value = 1
}

const toggleSortDirection = () => {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
}

// Lifecycle
onMounted(() => {
  fetchPayments()
})
</script>

<!-- Modal Component -->
<Modal
  :show="modal.isVisible.value"
  :title="modal.modalData.title"
  :message="modal.modalData.message"
  :type="modal.modalData.type"
  :size="modal.modalData.size"
  :show-cancel-button="modal.modalData.showCancelButton"
  :confirm-text="modal.modalData.confirmText"
  :cancel-text="modal.modalData.cancelText"
  @close="modal.hide"
  @confirm="modal.confirm"
  @cancel="modal.cancel"
/>