<template>
  <div class="space-y-6">
    <div v-if="flashMessage" class="rounded-md p-3" :class="flashClass">
      {{ flashMessage }}
    </div>

    <div>
      <h2 class="text-2xl font-bold text-gray-900">Stock Management</h2>
      <p class="text-gray-600">Record stock movements and adjustments</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="bg-white rounded-lg shadow-sm p-4 lg:col-span-1">
        <h3 class="font-semibold text-gray-900 mb-3">New Adjustment</h3>
        <form class="space-y-3" @submit.prevent="submit">
          <div>
            <label class="text-xs text-gray-500">Product *</label>
            <select 
              v-model="selectedProductId" 
              required 
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Select product</option>
              <option 
                v-for="product in productsStore.products" 
                :key="product.id" 
                :value="product.id"
              >
                {{ product.name }} (SKU: {{ product.sku }}) - Stock: {{ getProductStock(product.id) }}
              </option>
            </select>
          </div>
          <div>
            <label class="text-xs text-gray-500">Type *</label>
            <select 
              v-model="type" 
              required 
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              @change="resetReasonOnTypeChange"
            >
              <option value="">Select type</option>
              <option
                v-for="typeOption in adjustmentTypes"
                :key="typeOption.value"
                :value="typeOption.value"
                :class="`text-${typeOption.color}-600`"
              >
                {{ typeOption.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="text-xs text-gray-500">Quantity</label>
            <input
              v-model.number="quantity"
              type="number"
              min="1"
              required
              class="w-full px-3 py-2 border rounded-lg"
            />
          </div>
          <div>
            <label class="text-xs text-gray-500">Reason *</label>
            <select 
              v-model="reason" 
              required 
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Select reason</option>
              <optgroup 
                v-for="(reasons, category) in getFilteredReasons()" 
                :key="category"
                :label="category.charAt(0).toUpperCase() + category.slice(1)"
              >
                <option
                  v-for="reasonOption in reasons"
                  :key="reasonOption.value"
                  :value="reasonOption.value"
                >
                  {{ reasonOption.label }}
                </option>
              </optgroup>
            </select>
          </div>
          <div v-if="isTransfer" class="grid grid-cols-2 gap-2">
            <div>
              <label class="text-xs text-gray-500">From Store</label>
              <select v-model="fromStore" class="w-full px-3 py-2 border rounded-lg">
                <option value="">—</option>
                <option v-for="s in appStore.stores" :key="s.id" :value="s.id">
                  {{ s.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="text-xs text-gray-500">To Store</label>
              <select v-model="toStore" class="w-full px-3 py-2 border rounded-lg">
                <option value="">—</option>
                <option v-for="s in appStore.stores" :key="s.id" :value="s.id">
                  {{ s.name }}
                </option>
              </select>
            </div>
          </div>
          <div>
            <label class="text-xs text-gray-500">Notes</label>
            <textarea
              v-model="notes"
              rows="3"
              class="w-full px-3 py-2 border rounded-lg"
            ></textarea>
          </div>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed"
          >
            <span v-if="isSubmitting">Recording...</span>
            <span v-else>Record Adjustment</span>
          </button>
        </form>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-4 lg:col-span-2">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-gray-900">Stock Movements</h3>
          <span class="text-xs text-gray-500">Total: {{ filteredMovements.length }}</span>
        </div>

        <!-- Search and Filters -->
        <div class="space-y-4 mb-4">
          <!-- Search Bar -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="relative flex-1">
              <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
              <input
                v-model="searchQuery"
                placeholder="Search by product name, SKU, or reason"
                class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div class="text-sm text-gray-500">
              {{ paginatedMovements.length }} of {{ filteredMovements.length }} movements
            </div>
          </div>

          <!-- Filters and Sorting -->
          <div class="flex flex-col lg:flex-row gap-4">
            <!-- Filters -->
            <div class="flex flex-wrap gap-4 items-center">
              <!-- Type Filter -->
              <div>
                <select
                  v-model="filters.type"
                  class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
                >
                  <option value="">All Types</option>
                  <option value="addition">Addition</option>
                  <option value="reduction">Reduction</option>
                  <option value="transfer_in">Transfer In</option>
                  <option value="transfer_out">Transfer Out</option>
                  <option value="adjustment">Adjustment</option>
                </select>
              </div>

              <!-- Reason Filter -->
              <div>
                <select
                  v-model="filters.reason"
                  class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
                >
                  <option value="">All Reasons</option>
                  <option v-for="reason in uniqueReasons" :key="reason" :value="reason">
                    {{ reason }}
                  </option>
                </select>
              </div>

              <!-- Quantity Range -->
              <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600">Qty:</span>
                <input
                  v-model.number="filters.minQuantity"
                  type="number"
                  placeholder="Min"
                  class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                  min="0"
                />
                <span class="text-gray-400">-</span>
                <input
                  v-model.number="filters.maxQuantity"
                  type="number"
                  placeholder="Max"
                  class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                  min="0"
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
            <div class="flex items-center gap-2 lg:ml-auto">
              <span class="text-sm text-gray-600">Sort by:</span>
              <select
                v-model="sortBy"
                class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="id">ID</option>
                <option value="product_name">Product</option>
                <option value="type">Type</option>
                <option value="quantity">Quantity</option>
                <option value="createdAt">Date</option>
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
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th class="text-left px-4 py-2 font-medium">ID</th>
                <th class="text-left px-4 py-2 font-medium">Product</th>
                <th class="text-left px-4 py-2 font-medium">SKU</th>
                <th class="text-left px-4 py-2 font-medium">Type</th>
                <th class="text-right px-4 py-2 font-medium">Qty</th>
                <th class="text-left px-4 py-2 font-medium">Reason</th>
                <th class="text-left px-4 py-2 font-medium">Notes</th>
                <th class="text-left px-4 py-2 font-medium">Date/Time</th>
                <th class="text-left px-4 py-2 font-medium">User</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in paginatedMovements" :key="a.id" class="border-t">
                <td class="px-4 py-2 font-medium text-gray-900">
                  {{ a.id }}
                </td>
                <td class="px-4 py-2">{{ a.product?.name }}</td>
                <td class="px-4 py-2">{{ a.product?.sku }}</td>
                <td class="px-4 py-2">
                  <span :class="typePill(a.type)">{{ typeText(a.type) }}</span>
                </td>
                <td class="px-4 py-2 text-right">
                  {{ a.quantity }}
                </td>
                <td class="px-4 py-2">{{ a.reason }}</td>
                <td class="px-4 py-2 truncate max-w-[200px]">
                  {{ a.notes }}
                </td>
                <td class="px-4 py-2">{{ a.createdAt }}</td>
                <td class="px-4 py-2">{{ a.user?.name || 'System' }}</td>
              </tr>
              <tr v-if="filteredMovements.length === 0">
                <td colspan="9" class="px-4 py-8 text-center text-gray-500">No stock movements found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="mt-4 flex items-center justify-between">
          <div class="text-sm text-gray-500">
            Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredMovements.length) }} of {{ filteredMovements.length }} movements
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
import { computed, reactive, ref } from 'vue'
import { useInventoryStore } from '@/stores/inventory'
import { useAppStore } from '@/stores/app'
import { useProductsStore } from '@/stores/products'
import { useMessageModal } from '@/composables/useModal.js'
import Modal from '@/Components/Modal.vue'
// import type { StockAdjustment } from '@/types'
import axios from 'axios'
import { useNotificationStore } from '@/stores/notifications'
import { Search, ArrowUpDown } from 'lucide-vue-next'

const inventory = useInventoryStore()
const appStore = useAppStore()
const productsStore = useProductsStore()
const modal = useMessageModal()
const notificationStore = useNotificationStore()

// ensure products available for SKU->ID mapping
productsStore.fetchProducts().catch(() => {})

// Load initial data
inventory.fetchInventoryData().catch(() => {})
inventory.fetchStockMovements().catch(() => {})
appStore.fetchStores().catch(() => {})

const selectedProductId = ref('')
const type = ref('addition')
const quantity = ref(1)
const reason = ref('')
const notes = ref('')
const fromStore = ref('')
const toStore = ref('')

// Add state for adjustment types and reasons
const adjustmentTypes = ref([])
const adjustmentReasons = ref([])
const reasonsByCategory = ref({})

// Add loading state
const isSubmitting = ref(false)

// Search and filter state
const searchQuery = ref('')
const filters = reactive({
  type: '',
  reason: '',
  minQuantity: null,
  maxQuantity: null
})

// Sorting state
const sortBy = ref('id')
const sortDirection = ref('desc')

// Pagination state
const currentPage = ref(1)
const itemsPerPage = ref(20)

const flashMessage = ref('')
const flashType = ref('success')
const flashClass = computed(() =>
  flashType.value === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
)
let flashTimer
const notify = (msg, type = 'success') => {
  flashMessage.value = msg
  flashType.value = type
  if (flashTimer) window.clearTimeout(flashTimer)
  flashTimer = window.setTimeout(() => (flashMessage.value = ''), 3000)
}

// Computed properties for search, filter, sort, and pagination
const uniqueReasons = computed(() => {
  const reasons = new Set()
  inventory.stockAdjustments.forEach(movement => {
    if (movement.reason) {
      reasons.add(movement.reason)
    }
  })
  return Array.from(reasons).sort()
})

const hasActiveFilters = computed(() => {
  return filters.type || filters.reason || filters.minQuantity !== null || filters.maxQuantity !== null
})

const filteredMovements = computed(() => {
  let movements = inventory.stockAdjustments || []

  // Apply search
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    movements = movements.filter(movement => 
      movement.product?.name?.toLowerCase().includes(query) ||
      movement.product?.sku?.toLowerCase().includes(query) ||
      movement.reason?.toLowerCase().includes(query) ||
      movement.notes?.toLowerCase().includes(query)
    )
  }

  // Apply filters
  if (filters.type) {
    movements = movements.filter(movement => movement.type === filters.type)
  }

  if (filters.reason) {
    movements = movements.filter(movement => movement.reason === filters.reason)
  }

  if (filters.minQuantity !== null && filters.minQuantity !== '') {
    movements = movements.filter(movement => movement.quantity >= Number(filters.minQuantity))
  }

  if (filters.maxQuantity !== null && filters.maxQuantity !== '') {
    movements = movements.filter(movement => movement.quantity <= Number(filters.maxQuantity))
  }

  // Apply sorting
  movements = [...movements].sort((a, b) => {
    let aValue, bValue

    switch (sortBy.value) {
      case 'id':
        aValue = Number(a.id)
        bValue = Number(b.id)
        break
      case 'product_name':
        aValue = (a.product?.name || '').toLowerCase()
        bValue = (b.product?.name || '').toLowerCase()
        break
      case 'type':
        aValue = a.type || ''
        bValue = b.type || ''
        break
      case 'quantity':
        aValue = Number(a.quantity)
        bValue = Number(b.quantity)
        break
      case 'createdAt':
        aValue = new Date(a.createdAt || 0)
        bValue = new Date(b.createdAt || 0)
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

  return movements
})

const totalPages = computed(() => Math.ceil(filteredMovements.value.length / itemsPerPage.value))

const paginatedMovements = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredMovements.value.slice(start, end)
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

// Filter and sorting functions
const clearFilters = () => {
  filters.type = ''
  filters.reason = ''
  filters.minQuantity = null
  filters.maxQuantity = null
  currentPage.value = 1
}

const toggleSortDirection = () => {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
}

// Fetch adjustment types and reasons from backend
const fetchAdjustmentTypes = async () => {
  try {
    const response = await axios.get('/api/stock-movement-types')
    adjustmentTypes.value = response.data.types
    adjustmentReasons.value = response.data.reasons
    reasonsByCategory.value = response.data.reasonsByCategory
  } catch (error) {
    console.error('Failed to fetch adjustment types:', error)
    // Fallback to hardcoded types
    adjustmentTypes.value = [
      { value: 'addition', label: 'Addition', color: 'green' },
      { value: 'reduction', label: 'Reduction', color: 'red' },
      { value: 'transfer_out', label: 'Transfer Out', color: 'orange' },
      { value: 'transfer_in', label: 'Transfer In', color: 'blue' },
      { value: 'adjustment', label: 'Stock Adjustment', color: 'purple' },
    ]
    adjustmentReasons.value = [
      { value: 'purchase', label: 'Purchase from Supplier', category: 'inbound' },
      { value: 'sale', label: 'Sold to Customer', category: 'outbound' },
      { value: 'return', label: 'Customer Return', category: 'inbound' },
      { value: 'damaged', label: 'Damaged Goods', category: 'loss' },
      { value: 'expired', label: 'Expired Items', category: 'loss' },
      { value: 'recount', label: 'Inventory Recount', category: 'adjustment' },
    ]
  }
}

// Fetch adjustment types on component mount
fetchAdjustmentTypes().catch(() => {})

const isTransfer = computed(() => type.value === 'transfer_out' || type.value === 'transfer_in')

// Helper function to get product stock
const getProductStock = (productId) => {
  const item = inventory.inventoryItems.find(i => i.id === productId)
  return item ? item.currentStock : 0
}

// Helper function to get filtered reasons based on movement type
const getFilteredReasons = () => {
  if (!reasonsByCategory.value || Object.keys(reasonsByCategory.value).length === 0) {
    return { all: adjustmentReasons.value }
  }
  
  // Filter reasons based on movement type
  switch (type.value) {
    case 'addition':
      return {
        inbound: reasonsByCategory.value.inbound || [],
        adjustment: reasonsByCategory.value.adjustment || []
      }
    case 'reduction':
      return {
        outbound: reasonsByCategory.value.outbound || [],
        loss: reasonsByCategory.value.loss || [],
        marketing: reasonsByCategory.value.marketing || []
      }
    case 'transfer_out':
    case 'transfer_in':
      return {
        transfer: reasonsByCategory.value.outbound?.filter(r => r.value === 'transfer') || []
      }
    case 'adjustment':
      return {
        adjustment: reasonsByCategory.value.adjustment || []
      }
    default:
      return reasonsByCategory.value
  }
}

// Reset reason when type changes
const resetReasonOnTypeChange = () => {
  reason.value = ''
}

const toNumericStoreId = (val) => {
  const n = parseInt(String(val).replace(/\D+/g, ''), 10)
  return Number.isFinite(n) && n > 0 ? n : 1
}

const submit = async () => {
  if (isSubmitting.value) return // Prevent multiple submissions

  try {
    isSubmitting.value = true

    // Validate required fields
    if (!selectedProductId.value) {
      notificationStore.error('Validation Error', 'Please select a product')
      return
    }

    if (!type.value) {
      notificationStore.error('Validation Error', 'Please select an adjustment type')
      return
    }

    if (!quantity.value || quantity.value <= 0) {
      notificationStore.error('Validation Error', 'Please enter a valid quantity')
      return
    }

    if (!reason.value) {
      notificationStore.error('Validation Error', 'Please select a reason for the adjustment')
      return
    }

    const product = productsStore.products.find(p => p.id === selectedProductId.value || p.id === parseInt(selectedProductId.value))
    if (!product) {
      notificationStore.error('Validation Error', 'Selected product not found')
      return
    }

    // Decide which store_id affects stock for movement
    let storeId = 1
    if (type.value === 'transfer_out') {
      storeId = toNumericStoreId(fromStore.value)
    } else if (type.value === 'transfer_in') {
      storeId = toNumericStoreId(toStore.value)
    } else {
      storeId = toNumericStoreId(appStore.selectedStore)
    }

    const response = await axios.post('/api/stock-movements', {
      product_id: Number(selectedProductId.value),
      store_id: storeId,
      type: type.value,
      quantity: quantity.value,
      reason: reason.value,
      notes: notes.value || null,
      from_store_id: fromStore.value ? toNumericStoreId(fromStore.value) : null,
      to_store_id: toStore.value ? toNumericStoreId(toStore.value) : null,
      user_id: window?.App?.userId ?? 1,
      occurred_at: new Date().toISOString(),
    })

    // Optimistic local update for overview
    const item = inventory.inventoryItems.find(i => i.id === selectedProductId.value || i.id === parseInt(selectedProductId.value))
    if (item) {
      if (type.value === 'addition' || type.value === 'transfer_in') {
        item.currentStock += quantity.value
      } else {
        item.currentStock = Math.max(0, item.currentStock - quantity.value)
      }
    }

    // Show success notification
    notificationStore.stockMovementSuccess(product?.name || 'Product', type.value, quantity.value)
    
    // Check for low stock after movement
    if (item && item.currentStock <= (item.lowStockThreshold || 5)) {
      notificationStore.lowStockWarning(product?.name || 'Product', item.currentStock, item.lowStockThreshold || 5)
    }

    // Refresh stock movements to show the new one
    inventory.fetchStockMovements().catch(() => {})

    // reset form
    selectedProductId.value = ''
    type.value = 'addition'
    quantity.value = 1
    reason.value = ''
    notes.value = ''
    fromStore.value = ''
    toStore.value = ''
  } catch (e) {
    const errorMessage =
      e?.response?.data?.message ||
      e?.response?.data?.errors?.product_id?.[0] ||
      e?.response?.data?.errors?.store_id?.[0] ||
      e?.response?.data?.errors?.type?.[0] ||
      e?.response?.data?.errors?.quantity?.[0] ||
      e?.response?.data?.errors?.reason?.[0] ||
      'Failed to record stock movement'
    
    notificationStore.stockMovementError(errorMessage)
  } finally {
    isSubmitting.value = false
  }
}

const typeText = (t) => {
  switch (t) {
    case 'addition':
      return 'Addition'
    case 'reduction':
      return 'Reduction'
    case 'transfer_in':
      return 'Transfer In'
    case 'transfer_out':
      return 'Transfer Out'
  }
}

const typePill = (t) => {
  if (t === 'addition' || t === 'transfer_in')
    return 'inline-flex px-2 py-1 text-xs rounded-full bg-green-100 text-green-800'
  if (t === 'reduction' || t === 'transfer_out')
    return 'inline-flex px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800'
  return 'inline-flex px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800'
}
</script>
