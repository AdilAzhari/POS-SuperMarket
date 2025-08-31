<template>
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" v-if="show">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white dark:bg-gray-800 dark:border-gray-700">
            <!-- Header -->
            <div class="flex justify-between items-center pb-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Create Purchase Order</h3>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="mt-4">
                <form @submit.prevent="createPO" class="space-y-6">
                    <!-- Items List -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Items ({{ items.length }})</h4>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Supplier</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Unit Cost</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(item, index) in formItems" :key="index">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ item.product?.name || 'Unknown Product' }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                SKU: {{ item.product?.sku || 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                {{ item.supplier?.name || 'Unknown Supplier' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input 
                                                v-model.number="item.quantity" 
                                                type="number" 
                                                min="1"
                                                class="w-20 px-2 py-1 text-sm border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                ${{ formatCurrency(item.product?.cost || 0) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                ${{ formatCurrency((item.product?.cost || 0) * item.quantity) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <button 
                                                @click="removeItem(index)"
                                                type="button"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Total Items: {{ formItems.length }}</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Total Amount: ${{ formatCurrency(totalAmount) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes (Optional)</label>
                        <textarea 
                            id="notes"
                            v-model="notes"
                            rows="3" 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            placeholder="Add any additional notes for this purchase order..."></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button 
                            @click="$emit('close')"
                            type="button" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="formItems.length === 0 || creating"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ creating ? 'Creating...' : 'Create Purchase Order' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    items: {
        type: Array,
        default: () => []
    },
    storeId: {
        type: Number,
        required: true
    }
})

const emit = defineEmits(['close', 'created'])

// State
const formItems = ref([])
const notes = ref('')
const creating = ref(false)

// Computed
const totalAmount = computed(() => {
    return formItems.value.reduce((total, item) => {
        return total + (item.product?.cost || 0) * item.quantity
    }, 0)
})

// Watch for props changes
watch(() => props.items, (newItems) => {
    formItems.value = newItems.map(item => ({
        ...item,
        quantity: item.quantity || item.suggested_order_qty || 1
    }))
}, { immediate: true })

// Methods
const removeItem = (index) => {
    formItems.value.splice(index, 1)
}

const createPO = async () => {
    if (formItems.value.length === 0) return

    creating.value = true
    try {
        const response = await axios.post('/api/reorder/create-po', {
            store_id: props.storeId,
            notes: notes.value || null,
            items: formItems.value.map(item => ({
                product_id: item.product_id || item.product?.id,
                supplier_id: item.supplier_id || item.supplier?.id,
                quantity: item.quantity,
                notes: item.notes || null
            }))
        })

        emit('created', response.data.data)
        emit('close')
        
        // Reset form
        formItems.value = []
        notes.value = ''
    } catch (error) {
        console.error('Failed to create purchase order:', error)
        // Handle error - could show toast/notification here
    } finally {
        creating.value = false
    }
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value)
}
</script>