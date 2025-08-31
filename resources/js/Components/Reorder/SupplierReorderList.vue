<template>
    <div>
        <!-- Supplier Groups -->
        <div class="space-y-6">
            <div 
                v-for="supplierGroup in suppliers" 
                :key="supplierGroup.supplier.id"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                
                <!-- Supplier Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-5 5-5-5"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ supplierGroup.supplier.name }}
                                </h3>
                                <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                    <span>{{ supplierGroup.total_items }} items</span>
                                    <span>•</span>
                                    <span>${{ formatCurrency(supplierGroup.total_cost) }}</span>
                                    <span v-if="supplierGroup.high_priority_items > 0" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        {{ supplierGroup.high_priority_items }} urgent
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="text-right text-sm text-gray-600 dark:text-gray-400">
                                <div>Lead: {{ supplierGroup.avg_lead_time }}d</div>
                                <div>Est. Total: ${{ formatCurrency(supplierGroup.total_cost) }}</div>
                            </div>
                            <button 
                                @click="createPOForSupplier(supplierGroup)"
                                :disabled="supplierGroup.items.length === 0"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50">
                                Create PO
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Suggested Qty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Est. Cost</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="item in supplierGroup.items" :key="item.product.id"
                                :class="getPriorityRowClass(item.priority)">
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
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ item.current_stock }} / {{ item.threshold }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ item.days_remaining }}d remaining
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getPriorityBadgeClass(item.priority)">
                                        {{ getPriorityText(item.priority) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input v-model.number="item.suggested_order_qty" 
                                           type="number" 
                                           min="1"
                                           @input="updateSupplierTotal(supplierGroup)"
                                           class="w-20 px-2 py-1 text-sm border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        ${{ formatCurrency(item.estimated_cost) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        ${{ formatCurrency(item.product.cost) }}/unit
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="suppliers.length === 0" class="text-center py-8">
            <div class="text-gray-400 dark:text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-5 5-5-5"></path>
                </svg>
                <p class="text-lg font-medium">No suppliers need reordering</p>
                <p class="text-sm">All suppliers are well stocked!</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    suppliers: {
        type: Array,
        required: true
    }
})

const emit = defineEmits(['create-po'])

const createPOForSupplier = (supplierGroup) => {
    const itemsForPO = supplierGroup.items.map(item => ({
        product_id: item.product.id,
        supplier_id: item.supplier.id,
        quantity: item.suggested_order_qty,
        notes: null
    }))
    
    emit('create-po', itemsForPO)
}

const updateSupplierTotal = (supplierGroup) => {
    // Recalculate total cost when quantity changes
    supplierGroup.total_cost = supplierGroup.items.reduce((total, item) => {
        return total + (item.suggested_order_qty * item.product.cost)
    }, 0)
}

const getPriorityRowClass = (priority) => {
    if (priority >= 5) return 'bg-red-50 dark:bg-red-900/10'
    if (priority >= 4) return 'bg-orange-50 dark:bg-orange-900/10'
    return ''
}

const getPriorityBadgeClass = (priority) => {
    const baseClasses = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'
    
    if (priority >= 5) return `${baseClasses} bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200`
    if (priority >= 4) return `${baseClasses} bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200`
    if (priority >= 3) return `${baseClasses} bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200`
    if (priority >= 2) return `${baseClasses} bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200`
    return `${baseClasses} bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200`
}

const getPriorityText = (priority) => {
    if (priority >= 5) return 'Critical'
    if (priority >= 4) return 'High'
    if (priority >= 3) return 'Medium'
    if (priority >= 2) return 'Low'
    return 'Normal'
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value)
}
</script>