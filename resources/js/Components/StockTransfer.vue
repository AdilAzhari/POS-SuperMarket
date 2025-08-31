<template>
  <div class="space-y-6">
    <div>
      <h4 class="text-lg font-medium">Store to Store Transfer</h4>
      <p class="text-sm text-gray-600 mt-1">Transfer stock between different store locations</p>
    </div>

    <form @submit.prevent="executeTransfer" class="space-y-4">
      <!-- Product Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Product *</label>
        <select 
          v-model="transferData.product_id" 
          required 
          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          @change="checkSourceStock"
        >
          <option value="">Select product to transfer</option>
          <option v-for="product in products" :key="product.id" :value="product.id">
            {{ product.name }} ({{ product.sku }})
          </option>
        </select>
      </div>

      <!-- Store Selection -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">From Store *</label>
          <select 
            v-model="transferData.from_store_id" 
            required 
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            @change="checkSourceStock"
          >
            <option value="">Select source store</option>
            <option 
              v-for="store in stores" 
              :key="store.id" 
              :value="store.id"
              :disabled="store.id === transferData.to_store_id"
            >
              {{ store.name }}
            </option>
          </select>
          <div v-if="sourceStock !== null" class="mt-1 text-xs text-gray-600">
            Available stock: {{ sourceStock }} units
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">To Store *</label>
          <select 
            v-model="transferData.to_store_id" 
            required 
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select destination store</option>
            <option 
              v-for="store in stores" 
              :key="store.id" 
              :value="store.id"
              :disabled="store.id === transferData.from_store_id"
            >
              {{ store.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- Quantity -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity *</label>
        <div class="relative">
          <input 
            v-model.number="transferData.quantity" 
            type="number" 
            min="1" 
            :max="sourceStock || undefined"
            required
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Enter quantity to transfer"
          />
          <div v-if="sourceStock !== null && transferData.quantity > sourceStock" 
               class="absolute right-3 top-2.5 text-red-500 text-sm">
            ⚠️ Exceeds available stock
          </div>
        </div>
        <div v-if="sourceStock !== null" class="mt-1 flex items-center justify-between text-xs text-gray-600">
          <span>Available: {{ sourceStock }} units</span>
          <button 
            type="button"
            @click="transferData.quantity = sourceStock"
            v-if="sourceStock > 0"
            class="text-blue-600 hover:text-blue-800"
          >
            Transfer all
          </button>
        </div>
      </div>

      <!-- Reason and Notes -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
          <input 
            v-model="transferData.reason" 
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="e.g., Store restocking"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
          <input 
            v-model="transferData.notes" 
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Optional notes"
          />
        </div>
      </div>

      <!-- Transfer Summary -->
      <div v-if="isFormValid" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h5 class="font-medium text-blue-900 mb-2">Transfer Summary</h5>
        <div class="space-y-1 text-sm text-blue-800">
          <div>Product: {{ getProductName(transferData.product_id) }}</div>
          <div>From: {{ getStoreName(transferData.from_store_id) }}</div>
          <div>To: {{ getStoreName(transferData.to_store_id) }}</div>
          <div>Quantity: {{ transferData.quantity }} units</div>
          <div v-if="transferData.reason">Reason: {{ transferData.reason }}</div>
        </div>
      </div>

      <!-- Validation Errors -->
      <div v-if="validationErrors.length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <h5 class="font-medium text-red-900 mb-2">Please fix the following issues:</h5>
        <ul class="space-y-1 text-sm text-red-800">
          <li v-for="error in validationErrors" :key="error">• {{ error }}</li>
        </ul>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-4 border-t">
        <button 
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
        >
          Cancel
        </button>
        <button 
          type="submit"
          :disabled="!isFormValid || isExecuting || validationErrors.length > 0"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
        >
          <span v-if="isExecuting">Executing Transfer...</span>
          <span v-else>Execute Transfer</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
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

const emit = defineEmits(['transfer-completed', 'close'])

const transferData = ref({
  product_id: '',
  from_store_id: '',
  to_store_id: '',
  quantity: 1,
  reason: 'Store transfer',
  notes: ''
})

const sourceStock = ref(null)
const isExecuting = ref(false)

const isFormValid = computed(() => {
  return transferData.value.product_id &&
         transferData.value.from_store_id &&
         transferData.value.to_store_id &&
         transferData.value.quantity > 0 &&
         transferData.value.from_store_id !== transferData.value.to_store_id
})

const validationErrors = computed(() => {
  const errors = []
  
  if (transferData.value.from_store_id === transferData.value.to_store_id && transferData.value.from_store_id) {
    errors.push('Source and destination stores cannot be the same')
  }
  
  if (sourceStock.value !== null && transferData.value.quantity > sourceStock.value) {
    errors.push(`Quantity exceeds available stock (${sourceStock.value} units available)`)
  }
  
  if (transferData.value.quantity <= 0) {
    errors.push('Quantity must be greater than 0')
  }
  
  return errors
})

const getProductName = (productId) => {
  const product = props.products.find(p => p.id === productId)
  return product ? `${product.name} (${product.sku})` : 'Unknown Product'
}

const getStoreName = (storeId) => {
  const store = props.stores.find(s => s.id === storeId)
  return store ? store.name : 'Unknown Store'
}

const checkSourceStock = async () => {
  if (!transferData.value.product_id || !transferData.value.from_store_id) {
    sourceStock.value = null
    return
  }
  
  try {
    // In a real implementation, you'd fetch the actual stock from your inventory API
    // For now, we'll simulate it
    const response = await axios.get('/api/inventory', {
      params: {
        product_id: transferData.value.product_id,
        store_id: transferData.value.from_store_id
      }
    })
    
    // Assuming the API returns stock information
    sourceStock.value = response.data?.stock || 0
  } catch (error) {
    console.error('Failed to check source stock:', error)
    sourceStock.value = 0
  }
}

const executeTransfer = async () => {
  if (!isFormValid.value || validationErrors.value.length > 0) return
  
  isExecuting.value = true
  try {
    const response = await axios.post('/api/stock-movements/transfer', transferData.value)
    
    emit('transfer-completed', response.data)
    
    // Reset form
    transferData.value = {
      product_id: '',
      from_store_id: '',
      to_store_id: '',
      quantity: 1,
      reason: 'Store transfer',
      notes: ''
    }
    sourceStock.value = null
  } catch (error) {
    console.error('Transfer failed:', error)
    // Handle error - maybe show notification
  } finally {
    isExecuting.value = false
  }
}

// Watch for changes that should trigger stock check
watch([() => transferData.value.product_id, () => transferData.value.from_store_id], () => {
  checkSourceStock()
})
</script>