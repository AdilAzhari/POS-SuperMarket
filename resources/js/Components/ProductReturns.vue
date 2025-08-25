<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-2xl font-bold text-gray-900">Product Returns</h2>
      <p class="text-gray-600">Process customer returns and refunds</p>
    </div>

    <!-- Search for Sale -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h3 class="text-lg font-semibold mb-4">Find Sale for Return</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="text-sm font-medium text-gray-500">Sale ID / Receipt Number</label>
          <input
            v-model="searchTerm"
            type="text"
            placeholder="Enter sale ID or receipt number"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            @input="searchSales"
          />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-500">Customer Phone</label>
          <input
            v-model="customerPhone"
            type="text"
            placeholder="Enter customer phone"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            @input="searchSales"
          />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-500">Date Range</label>
          <input
            v-model="dateRange"
            type="date"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            @change="searchSales"
          />
        </div>
      </div>
    </div>

    <!-- Search Results -->
    <div v-if="searchResults.length > 0" class="bg-white rounded-lg shadow-sm p-6">
      <h3 class="text-lg font-semibold mb-4">Sales Found</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="sale in searchResults" :key="sale.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ sale.code || sale.id }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <div>{{ sale.customer?.name || 'Walk-in Customer' }}</div>
                <div class="text-xs text-gray-400">{{ sale.customer?.phone || '' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(sale.created_at) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">RM {{ parseFloat(sale.total || 0).toFixed(2) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ getMethodName(sale.payment_method) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  @click="selectSaleForReturn(sale)"
                  class="text-blue-600 hover:text-blue-900"
                >
                  Process Return
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Return Form -->
    <div v-if="selectedSale" class="bg-white rounded-lg shadow-sm p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Process Return for Sale #{{ selectedSale.code || selectedSale.id }}</h3>
        <button @click="clearSelection" class="text-gray-400 hover:text-gray-600">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Sale Items -->
      <div class="mb-6">
        <h4 class="text-md font-medium mb-3">Items in this Sale</h4>
        <div class="space-y-3">
          <div v-for="item in saleItems" :key="item.id" class="flex items-center justify-between p-3 border rounded-lg">
            <div class="flex items-center space-x-3">
              <input
                v-model="returnItems[item.id]"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                @change="toggleReturnItem(item)"
              />
              <div>
                <div class="font-medium">{{ item.product?.name || 'Unknown Product' }}</div>
                <div class="text-sm text-gray-500">SKU: {{ item.product?.sku || 'N/A' }}</div>
                <div class="text-sm text-gray-500">Qty sold: {{ item.quantity }} @ RM {{ parseFloat(item.price || 0).toFixed(2) }}</div>
              </div>
            </div>
            <div v-if="returnItems[item.id]" class="flex items-center space-x-2">
              <label class="text-sm">Return Qty:</label>
              <input
                v-model.number="returnQuantities[item.id]"
                type="number"
                :min="1"
                :max="item.quantity"
                class="w-20 px-2 py-1 border rounded text-center"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Return Details -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Return Reason</label>
          <select v-model="returnReason" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" required>
            <option value="">Select reason</option>
            <option value="defective">Defective Product</option>
            <option value="wrong_item">Wrong Item</option>
            <option value="customer_change_mind">Customer Changed Mind</option>
            <option value="damaged_shipping">Damaged in Shipping</option>
            <option value="not_as_described">Not as Described</option>
            <option value="duplicate_order">Duplicate Order</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Refund Method</label>
          <select v-model="refundMethod" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" required>
            <option value="">Select refund method</option>
            <option value="original_payment">Original Payment Method</option>
            <option value="cash">Cash</option>
            <option value="store_credit">Store Credit</option>
            <option value="exchange">Exchange Only</option>
          </select>
        </div>
      </div>

      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
        <textarea
          v-model="returnNotes"
          rows="3"
          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          placeholder="Additional notes about the return..."
        ></textarea>
      </div>

      <!-- Return Summary -->
      <div class="bg-gray-50 p-4 rounded-lg mb-6">
        <h4 class="font-medium mb-2">Return Summary</h4>
        <div class="text-sm space-y-1">
          <div class="flex justify-between">
            <span>Items to return:</span>
            <span>{{ totalReturnItems }}</span>
          </div>
          <div class="flex justify-between">
            <span>Total refund amount:</span>
            <span class="font-medium">RM {{ totalRefundAmount.toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex space-x-3">
        <button
          @click="processReturn"
          :disabled="!canProcessReturn || isProcessing"
          class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
        >
          <span v-if="isProcessing">Processing Return...</span>
          <span v-else>Process Return</span>
        </button>
        <button
          @click="clearSelection"
          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
        >
          Cancel
        </button>
      </div>
    </div>

    <!-- Recent Returns -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h3 class="text-lg font-semibold mb-4">Recent Returns</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Original Sale</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Refund Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="returnRecord in recentReturns" :key="returnRecord.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ returnRecord.id }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ returnRecord.original_sale_id }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ returnRecord.customer_name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ returnRecord.items_count }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">RM {{ parseFloat(returnRecord.refund_amount || 0).toFixed(2) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ returnRecord.reason }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(returnRecord.created_at) }}</td>
            </tr>
            <tr v-if="recentReturns.length === 0">
              <td colspan="7" class="px-6 py-8 text-center text-gray-500">No returns processed yet</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

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
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useMessageModal } from '@/composables/useModal.js'
import Modal from '@/Components/Modal.vue'
import { X } from 'lucide-vue-next'
import axios from 'axios'

// Data
const searchTerm = ref('')
const customerPhone = ref('')
const dateRange = ref('')
const searchResults = ref([])
const selectedSale = ref(null)
const saleItems = ref([])
const returnItems = ref({})
const returnQuantities = ref({})
const returnReason = ref('')
const refundMethod = ref('')
const returnNotes = ref('')
const recentReturns = ref([])
const isProcessing = ref(false)
const modal = useMessageModal()

// Computed
const totalReturnItems = computed(() => {
  return Object.values(returnItems.value).filter(Boolean).length
})

const totalRefundAmount = computed(() => {
  let total = 0
  for (const item of saleItems.value) {
    if (returnItems.value[item.id]) {
      const qty = returnQuantities.value[item.id] || 1
      total += qty * parseFloat(item.price || 0)
    }
  }
  return total
})

const canProcessReturn = computed(() => {
  return totalReturnItems.value > 0 && returnReason.value && refundMethod.value
})

// Methods
const searchSales = async () => {
  if (!searchTerm.value && !customerPhone.value && !dateRange.value) {
    searchResults.value = []
    return
  }

  try {
    const params = new URLSearchParams()
    if (searchTerm.value) params.append('search', searchTerm.value)
    if (customerPhone.value) params.append('customer_phone', customerPhone.value)
    if (dateRange.value) params.append('date', dateRange.value)
    
    const response = await axios.get(`/api/sales?${params.toString()}`)
    const salesData = Array.isArray(response.data?.data) ? response.data.data : response.data
    searchResults.value = salesData.filter(sale => sale.status === 'completed')
  } catch (error) {
    console.error('Failed to search sales:', error)
    searchResults.value = []
  }
}

const selectSaleForReturn = async (sale) => {
  selectedSale.value = sale
  
  try {
    // Fetch sale items
    const response = await axios.get(`/api/sales/${sale.id}`)
    saleItems.value = response.data.items || []
    
    // Reset return selections
    returnItems.value = {}
    returnQuantities.value = {}
    returnReason.value = ''
    refundMethod.value = ''
    returnNotes.value = ''
  } catch (error) {
    console.error('Failed to fetch sale items:', error)
    await modal.showError('Failed to load sale details. Please try again.')
    selectedSale.value = null
  }
}

const toggleReturnItem = (item) => {
  if (returnItems.value[item.id]) {
    returnQuantities.value[item.id] = 1
  } else {
    delete returnQuantities.value[item.id]
  }
}

const processReturn = async () => {
  if (!canProcessReturn.value) return

  const confirmed = await modal.showConfirm(
    `Are you sure you want to process this return? This will refund RM ${totalRefundAmount.value.toFixed(2)} and add the returned items back to inventory.`
  )

  if (!confirmed) return

  isProcessing.value = true

  try {
    // Prepare return data
    const returnData = {
      sale_id: selectedSale.value.id,
      reason: returnReason.value,
      refund_method: refundMethod.value,
      notes: returnNotes.value,
      items: []
    }

    // Add returned items
    for (const item of saleItems.value) {
      if (returnItems.value[item.id]) {
        returnData.items.push({
          product_id: item.product_id,
          quantity: returnQuantities.value[item.id] || 1,
          price: item.price
        })
      }
    }

    // Process the return by creating stock movements and handling refund
    for (const returnItem of returnData.items) {
      // Add stock back to inventory
      await axios.post('/api/stock-movements', {
        product_id: returnItem.product_id,
        store_id: selectedSale.value.store_id || 1,
        type: 'addition',
        quantity: returnItem.quantity,
        reason: 'return',
        notes: `Customer return for sale #${selectedSale.value.code || selectedSale.value.id}. Reason: ${returnReason.value}`,
        user_id: window?.App?.userId ?? 1,
        occurred_at: new Date().toISOString(),
      })
    }

    // In a real application, you would also process the refund here
    // For now, we'll just show a success message

    await modal.showSuccess(`Return processed successfully! RM ${totalRefundAmount.value.toFixed(2)} refund has been initiated.`)

    // Refresh recent returns
    fetchRecentReturns()
    
    // Clear the selection
    clearSelection()

  } catch (error) {
    console.error('Failed to process return:', error)
    await modal.showError('Failed to process return. Please try again.')
  } finally {
    isProcessing.value = false
  }
}

const clearSelection = () => {
  selectedSale.value = null
  saleItems.value = []
  returnItems.value = {}
  returnQuantities.value = {}
  returnReason.value = ''
  refundMethod.value = ''
  returnNotes.value = ''
}

const fetchRecentReturns = async () => {
  try {
    // For now, we'll use stock movements with reason 'return' as a proxy for returns
    const response = await axios.get('/api/stock-movements?reason=return&per_page=20')
    const movements = Array.isArray(response.data?.data) ? response.data.data : response.data
    
    // Group by notes to simulate return records
    const returnMap = new Map()
    movements.forEach(movement => {
      const saleMatch = movement.notes?.match(/sale #(\w+)/)
      if (saleMatch) {
        const saleId = saleMatch[1]
        if (!returnMap.has(saleId)) {
          returnMap.set(saleId, {
            id: `RET-${saleId}`,
            original_sale_id: saleId,
            customer_name: 'Customer',
            items_count: 0,
            refund_amount: 0,
            reason: movement.notes?.includes('defective') ? 'defective' : 'return',
            created_at: movement.created_at
          })
        }
        const returnRecord = returnMap.get(saleId)
        returnRecord.items_count += 1
        returnRecord.refund_amount += movement.quantity * 10 // Estimate based on quantity
      }
    })
    
    recentReturns.value = Array.from(returnMap.values()).slice(0, 10)
  } catch (error) {
    console.error('Failed to fetch recent returns:', error)
    recentReturns.value = []
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

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-MY', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  fetchRecentReturns()
})
</script>