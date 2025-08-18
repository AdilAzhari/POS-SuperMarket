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
          <h3 class="font-semibold text-gray-900">Recent Adjustments</h3>
          <span class="text-xs text-gray-500">Total: {{ inventory.stockAdjustments.length }}</span>
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
              <tr v-for="a in inventory.stockAdjustments" :key="a.id" class="border-t">
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
              <tr v-if="inventory.stockAdjustments.length === 0">
                <td colspan="9" class="px-4 py-8 text-center text-gray-500">No adjustments yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useInventoryStore } from '@/stores/inventory'
import { useAppStore } from '@/stores/app'
import { useProductsStore } from '@/stores/products'
// import type { StockAdjustment } from '@/types'
import axios from 'axios'

const inventory = useInventoryStore()
const appStore = useAppStore()
const productsStore = useProductsStore()

// ensure products available for SKU->ID mapping
productsStore.fetchProducts().catch(() => {})

// Load initial data
inventory.fetchStockMovements().catch(() => {})

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
      notify('Please select a product', 'error')
      return
    }

    if (!type.value) {
      notify('Please select an adjustment type', 'error')
      return
    }

    if (!quantity.value || quantity.value <= 0) {
      notify('Please enter a valid quantity', 'error')
      return
    }

    if (!reason.value) {
      notify('Please select a reason for the adjustment', 'error')
      return
    }

    const product = productsStore.products.find(p => p.id === parseInt(selectedProductId.value))
    if (!product) {
      notify('Selected product not found', 'error')
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
      product_id: Number(product.id),
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
    const item = inventory.inventoryItems.find(i => i.id === parseInt(selectedProductId.value))
    if (item) {
      if (type.value === 'addition' || type.value === 'transfer_in') {
        item.currentStock += quantity.value
      } else {
        item.currentStock = Math.max(0, item.currentStock - quantity.value)
      }
    }

    notify('Stock movement recorded successfully')

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
    notify(errorMessage, 'error')
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
