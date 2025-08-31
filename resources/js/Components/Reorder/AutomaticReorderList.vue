<template>
    <div>
        <!-- Header -->
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-red-800 dark:text-red-200">Immediate Action Required</h3>
                    <p class="text-sm text-red-700 dark:text-red-300">
                        These products need urgent reordering based on current stock levels and sales velocity.
                    </p>
                </div>
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="mb-6 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <button @click="selectAll" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                    Select All
                </button>
                <button @click="clearSelection" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                    Clear Selection
                </button>
                <span class="text-sm text-gray-600">{{ selectedItems.length }} items selected</span>
            </div>
            <button @click="createPOFromSelected" 
                    :disabled="selectedItems.length === 0"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50">
                Create Emergency PO ({{ selectedItems.length }})
            </button>
        </div>

        <!-- Items List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <input type="checkbox" 
                                       @change="toggleSelectAll"
                                       :checked="isAllSelected"
                                       class="rounded border-gray-300">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Supplier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Days Left</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Suggested Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Est. Cost</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="item in items" :key="item.product.id"
                            class="hover:bg-red-50 dark:hover:bg-red-900/10">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" 
                                       :value="item.product.id"
                                       v-model="selectedItems"
                                       class="rounded border-gray-300">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ item.product.name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            SKU: {{ item.product.sku }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ item.supplier?.name || 'No Supplier' }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Lead: {{ item.avg_lead_time }}d</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col space-y-1">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ item.current_stock }} / {{ item.threshold }}
                                    </div>
                                    <div v-if="item.current_stock === 0" 
                                         class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        OUT OF STOCK
                                    </div>
                                    <div v-else-if="item.priority >= 4" 
                                         class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                        CRITICAL LOW
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <div :class="[
                                        'text-sm font-medium',
                                        item.days_remaining <= 1 ? 'text-red-600 dark:text-red-400' : 
                                        item.days_remaining <= 3 ? 'text-orange-600 dark:text-orange-400' : 
                                        'text-gray-900 dark:text-white'
                                    ]">
                                        {{ item.days_remaining }}d
                                    </div>
                                    <div v-if="item.days_remaining <= 1" class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                                    <div v-else-if="item.days_remaining <= 3" class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input v-model.number="item.suggested_order_qty" 
                                       type="number" 
                                       min="1"
                                       class="w-20 px-2 py-1 text-sm border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    ${{ formatCurrency(item.estimated_cost) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    ${{ formatCurrency(item.product.cost) }}/unit
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button @click="createSinglePO(item)"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 mr-3">
                                    Order Now
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="items.length === 0" class="text-center py-8">
            <div class="text-green-400 dark:text-green-500">
                <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-lg font-medium text-gray-900 dark:text-white">No immediate reorders needed</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">All products are stocked appropriately!</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    items: {
        type: Array,
        required: true
    }
})

const emit = defineEmits(['create-po'])

const selectedItems = ref([])

const isAllSelected = computed(() => {
    return props.items.length > 0 && selectedItems.value.length === props.items.length
})

const selectAll = () => {
    selectedItems.value = props.items.map(item => item.product.id)
}

const clearSelection = () => {
    selectedItems.value = []
}

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        clearSelection()
    } else {
        selectAll()
    }
}

const createPOFromSelected = () => {
    const itemsForPO = props.items.filter(item => selectedItems.value.includes(item.product.id))
        .map(item => ({
            product_id: item.product.id,
            supplier_id: item.supplier.id,
            quantity: item.suggested_order_qty,
            notes: 'Emergency reorder - automatic suggestion'
        }))
    
    emit('create-po', itemsForPO)
}

const createSinglePO = (item) => {
    const poItem = {
        product_id: item.product.id,
        supplier_id: item.supplier.id,
        quantity: item.suggested_order_qty,
        notes: 'Emergency reorder - critical stock level'
    }
    
    emit('create-po', [poItem])
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value)
}
</script>