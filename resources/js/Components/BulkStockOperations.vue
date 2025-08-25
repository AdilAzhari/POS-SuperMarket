<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h4 class="text-lg font-medium">Bulk Stock Operations</h4>
      <span class="text-sm text-gray-500">{{ movements.length }} operations queued</span>
    </div>

    <!-- Add Movement Form -->
    <div class="bg-gray-50 rounded-lg p-4 space-y-4">
      <h5 class="font-medium text-gray-900">Add Operation</h5>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <label class="text-xs text-gray-500">Product</label>
          <select v-model="currentMovement.product_id" class="w-full px-3 py-2 border rounded-lg text-sm">
            <option value="">Select product</option>
            <option v-for="product in products" :key="product.id" :value="product.id">
              {{ product.name }} ({{ product.sku }})
            </option>
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-500">Store</label>
          <select v-model="currentMovement.store_id" class="w-full px-3 py-2 border rounded-lg text-sm">
            <option value="">Select store</option>
            <option v-for="store in stores" :key="store.id" :value="store.id">
              {{ store.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-500">Type</label>
          <select v-model="currentMovement.type" class="w-full px-3 py-2 border rounded-lg text-sm">
            <option value="">Select type</option>
            <option value="addition">Addition</option>
            <option value="reduction">Reduction</option>
            <option value="adjustment">Adjustment</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-500">Quantity</label>
          <input 
            v-model.number="currentMovement.quantity" 
            type="number" 
            min="1" 
            class="w-full px-3 py-2 border rounded-lg text-sm"
            placeholder="0"
          />
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-xs text-gray-500">Reason</label>
          <select v-model="currentMovement.reason" class="w-full px-3 py-2 border rounded-lg text-sm">
            <option value="">Select reason</option>
            <option value="purchase">Purchase</option>
            <option value="recount">Inventory Recount</option>
            <option value="damaged">Damaged Goods</option>
            <option value="expired">Expired Items</option>
            <option value="return">Customer Return</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-500">Notes</label>
          <input 
            v-model="currentMovement.notes" 
            class="w-full px-3 py-2 border rounded-lg text-sm"
            placeholder="Optional notes"
          />
        </div>
      </div>
      <div class="flex items-center justify-between">
        <button 
          @click="addMovement"
          :disabled="!isCurrentMovementValid"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-sm"
        >
          Add to Queue
        </button>
      </div>
    </div>

    <!-- Movements Queue -->
    <div v-if="movements.length > 0" class="space-y-4">
      <h5 class="font-medium text-gray-900">Operations Queue</h5>
      <div class="max-h-64 overflow-y-auto border rounded-lg">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left p-2 font-medium">Product</th>
              <th class="text-left p-2 font-medium">Store</th>
              <th class="text-left p-2 font-medium">Type</th>
              <th class="text-right p-2 font-medium">Qty</th>
              <th class="text-left p-2 font-medium">Reason</th>
              <th class="text-center p-2 font-medium">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(movement, index) in movements" :key="index" class="border-b hover:bg-gray-50">
              <td class="p-2">{{ getProductName(movement.product_id) }}</td>
              <td class="p-2">{{ getStoreName(movement.store_id) }}</td>
              <td class="p-2">
                <span :class="getTypeClass(movement.type)">{{ movement.type }}</span>
              </td>
              <td class="p-2 text-right">{{ movement.quantity }}</td>
              <td class="p-2">{{ movement.reason }}</td>
              <td class="p-2 text-center">
                <button 
                  @click="removeMovement(index)"
                  class="text-red-600 hover:text-red-800 text-xs px-2 py-1 rounded hover:bg-red-50"
                >
                  Remove
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between pt-4 border-t">
      <div class="text-sm text-gray-600">
        <span v-if="movements.length === 0">No operations queued</span>
        <span v-else>{{ movements.length }} operations ready to execute</span>
      </div>
      <div class="flex gap-3">
        <button 
          @click="$emit('close')"
          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
        >
          Cancel
        </button>
        <button 
          @click="clearAll"
          v-if="movements.length > 0"
          class="px-4 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50"
        >
          Clear All
        </button>
        <button 
          @click="executeAll"
          :disabled="movements.length === 0 || isExecuting"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
        >
          <span v-if="isExecuting">Executing...</span>
          <span v-else>Execute All ({{ movements.length }})</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
  products: {
    type: Array,
    default: () => []
  },
  stores: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['bulk-created', 'close'])

const movements = ref([])
const isExecuting = ref(false)

const currentMovement = ref({
  product_id: '',
  store_id: '',
  type: '',
  quantity: 1,
  reason: '',
  notes: ''
})

const isCurrentMovementValid = computed(() => {
  return currentMovement.value.product_id &&
         currentMovement.value.store_id &&
         currentMovement.value.type &&
         currentMovement.value.quantity > 0 &&
         currentMovement.value.reason
})

const getProductName = (productId) => {
  const product = props.products.find(p => p.id === productId)
  return product ? `${product.name} (${product.sku})` : 'Unknown Product'
}

const getStoreName = (storeId) => {
  const store = props.stores.find(s => s.id === storeId)
  return store ? store.name : 'Unknown Store'
}

const getTypeClass = (type) => {
  const baseClass = 'px-2 py-1 text-xs rounded-full'
  switch (type) {
    case 'addition':
      return `${baseClass} bg-green-100 text-green-800`
    case 'reduction':
      return `${baseClass} bg-red-100 text-red-800`
    case 'adjustment':
      return `${baseClass} bg-blue-100 text-blue-800`
    default:
      return `${baseClass} bg-gray-100 text-gray-800`
  }
}

const addMovement = () => {
  if (!isCurrentMovementValid.value) return
  
  movements.value.push({ ...currentMovement.value })
  
  // Reset form
  currentMovement.value = {
    product_id: '',
    store_id: '',
    type: '',
    quantity: 1,
    reason: '',
    notes: ''
  }
}

const removeMovement = (index) => {
  movements.value.splice(index, 1)
}

const clearAll = () => {
  movements.value = []
}

const executeAll = async () => {
  if (movements.value.length === 0) return
  
  isExecuting.value = true
  try {
    const response = await axios.post('/api/stock-movements/bulk', {
      movements: movements.value
    })
    
    emit('bulk-created', response.data.movements)
    movements.value = []
  } catch (error) {
    console.error('Bulk operation failed:', error)
    // Handle error - maybe show notification
  } finally {
    isExecuting.value = false
  }
}
</script>