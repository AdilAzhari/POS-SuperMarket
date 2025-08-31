<template>
    <div>
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Purchase Order History</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    View and analyze past purchase orders and delivery performance.
                </p>
            </div>
            <div v-if="meta" class="text-right">
                <div class="text-sm text-gray-600 dark:text-gray-400">Last {{ meta.period_days }} days</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ meta.total_orders }} orders • ${{ formatCurrency(meta.total_value) }}
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div v-if="meta" class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ meta.total_orders }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Orders</div>
                    </div>
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">${{ formatCurrency(meta.total_value) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Value</div>
                    </div>
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ averageDeliveryTime }}d</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Avg Delivery Time</div>
                    </div>
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">PO Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Supplier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Timeline</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Delivery</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="order in orders" :key="order.po_number" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                    {{ order.po_number }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDate(order.created_at) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ order.supplier }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getStatusBadgeClass(order.status)">
                                    {{ formatStatus(order.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ order.items_count }} items</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    ${{ formatCurrency(order.total_amount) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="space-y-1">
                                    <div v-if="order.ordered_at" class="text-xs text-gray-600 dark:text-gray-400">
                                        Ordered: {{ formatDate(order.ordered_at) }}
                                    </div>
                                    <div v-if="order.received_at" class="text-xs text-gray-600 dark:text-gray-400">
                                        Received: {{ formatDate(order.received_at) }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="order.delivery_days" class="flex items-center space-x-2">
                                    <span :class="[
                                        'text-sm font-medium',
                                        order.delivery_days <= 3 ? 'text-green-600 dark:text-green-400' :
                                        order.delivery_days <= 7 ? 'text-yellow-600 dark:text-yellow-400' :
                                        'text-red-600 dark:text-red-400'
                                    ]">
                                        {{ order.delivery_days }}d
                                    </span>
                                    <div :class="[
                                        'w-2 h-2 rounded-full',
                                        order.delivery_days <= 3 ? 'bg-green-500' :
                                        order.delivery_days <= 7 ? 'bg-yellow-500' : 'bg-red-500'
                                    ]"></div>
                                </div>
                                <div v-else-if="order.status === 'received'" class="text-xs text-gray-400">
                                    No delivery time
                                </div>
                                <div v-else class="text-xs text-gray-400">
                                    Pending
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="orders.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 py-12">
            <div class="text-center">
                <div class="text-gray-400 dark:text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-lg font-medium text-gray-900 dark:text-white">No purchase orders found</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        No purchase orders have been created in the selected time period.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    orders: {
        type: Array,
        required: true
    },
    meta: {
        type: Object,
        default: null
    }
})

const averageDeliveryTime = computed(() => {
    const ordersWithDelivery = props.orders.filter(order => order.delivery_days)
    if (ordersWithDelivery.length === 0) return 0
    
    const total = ordersWithDelivery.reduce((sum, order) => sum + order.delivery_days, 0)
    return Math.round(total / ordersWithDelivery.length)
})

const getStatusBadgeClass = (status) => {
    const baseClasses = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'
    
    switch (status) {
        case 'received':
            return `${baseClasses} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`
        case 'ordered':
            return `${baseClasses} bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200`
        case 'partial':
            return `${baseClasses} bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200`
        case 'pending':
            return `${baseClasses} bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200`
        case 'cancelled':
            return `${baseClasses} bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200`
        default:
            return `${baseClasses} bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200`
    }
}

const formatStatus = (status) => {
    return status.charAt(0).toUpperCase() + status.slice(1)
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value)
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    })
}
</script>