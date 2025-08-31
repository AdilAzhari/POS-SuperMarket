<template>
    <div>
        <!-- Header -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Supplier Performance Comparison</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Compare suppliers based on reorder needs, reliability, and performance metrics.
            </p>
        </div>

        <!-- Supplier Comparison Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div 
                v-for="supplier in suppliers" 
                :key="supplier.supplier.id"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                
                <!-- Card Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ supplier.supplier.name }}
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ supplier.supplier.contact_email }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="text-right">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Reliability</div>
                                <div class="font-semibold" :class="getReliabilityColor(supplier.reliability_score)">
                                    {{ supplier.reliability_score }}%
                                </div>
                            </div>
                            <div :class="[
                                'w-3 h-3 rounded-full',
                                supplier.reliability_score >= 80 ? 'bg-green-500' :
                                supplier.reliability_score >= 60 ? 'bg-yellow-500' : 'bg-red-500'
                            ]"></div>
                        </div>
                    </div>
                </div>

                <!-- Card Content -->
                <div class="px-6 py-4">
                    <!-- Key Metrics -->
                    <div class="space-y-4">
                        <!-- Items & Cost -->
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ supplier.items_count }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Items to reorder</div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    ${{ formatCurrency(supplier.total_cost) }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Total cost</div>
                            </div>
                        </div>

                        <!-- Priority Items -->
                        <div v-if="supplier.high_priority_items > 0" 
                             class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <span class="text-sm font-medium text-red-800 dark:text-red-200">High Priority</span>
                            </div>
                            <span class="text-lg font-bold text-red-800 dark:text-red-200">{{ supplier.high_priority_items }}</span>
                        </div>

                        <!-- Lead Time -->
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Average Lead Time</span>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ supplier.avg_lead_time }} days</span>
                                <div :class="[
                                    'w-2 h-2 rounded-full',
                                    supplier.avg_lead_time <= 3 ? 'bg-green-500' :
                                    supplier.avg_lead_time <= 7 ? 'bg-yellow-500' : 'bg-red-500'
                                ]"></div>
                            </div>
                        </div>

                        <!-- Last Order -->
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Last Order</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ supplier.last_order_date ? formatDate(supplier.last_order_date) : 'Never' }}
                            </span>
                        </div>

                        <!-- Priority Score -->
                        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Priority Score</span>
                                <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                    {{ Math.round(supplier.priority_score) }}/100
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div 
                                    class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-300"
                                    :style="{ width: Math.min(supplier.priority_score, 100) + '%' }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 rounded-b-lg">
                    <div class="flex justify-between items-center">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Recommendation: 
                            <span :class="getRecommendationClass(supplier)">
                                {{ getRecommendation(supplier) }}
                            </span>
                        </div>
                        <button 
                            @click="viewSupplierDetails(supplier)"
                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 text-sm font-medium">
                            View Details
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="suppliers.length === 0" class="text-center py-8">
            <div class="text-gray-400 dark:text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-5 5-5-5"></path>
                </svg>
                <p class="text-lg font-medium">No supplier data available</p>
                <p class="text-sm">Add some products to see supplier comparisons.</p>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div v-if="suppliers.length > 0" class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Summary Statistics</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ suppliers.length }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Active Suppliers</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        ${{ formatCurrency(totalReorderCost) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Reorder Cost</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ totalHighPriorityItems }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">High Priority Items</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ averageLeadTime }}d</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Avg Lead Time</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    suppliers: {
        type: Array,
        required: true
    }
})

const totalReorderCost = computed(() => {
    return props.suppliers.reduce((total, supplier) => total + supplier.total_cost, 0)
})

const totalHighPriorityItems = computed(() => {
    return props.suppliers.reduce((total, supplier) => total + supplier.high_priority_items, 0)
})

const averageLeadTime = computed(() => {
    if (props.suppliers.length === 0) return 0
    const totalLeadTime = props.suppliers.reduce((total, supplier) => total + supplier.avg_lead_time, 0)
    return Math.round(totalLeadTime / props.suppliers.length)
})

const getReliabilityColor = (score) => {
    if (score >= 80) return 'text-green-600 dark:text-green-400'
    if (score >= 60) return 'text-yellow-600 dark:text-yellow-400'
    return 'text-red-600 dark:text-red-400'
}

const getRecommendation = (supplier) => {
    if (supplier.high_priority_items > 0 && supplier.reliability_score >= 80) return 'Order Now'
    if (supplier.high_priority_items > 0) return 'Order with Caution'
    if (supplier.reliability_score >= 80) return 'Preferred Supplier'
    if (supplier.reliability_score >= 60) return 'Monitor Performance'
    return 'Review Supplier'
}

const getRecommendationClass = (supplier) => {
    const recommendation = getRecommendation(supplier)
    if (recommendation === 'Order Now') return 'text-green-600 dark:text-green-400 font-medium'
    if (recommendation === 'Order with Caution') return 'text-orange-600 dark:text-orange-400 font-medium'
    if (recommendation === 'Preferred Supplier') return 'text-blue-600 dark:text-blue-400 font-medium'
    if (recommendation === 'Monitor Performance') return 'text-yellow-600 dark:text-yellow-400 font-medium'
    return 'text-red-600 dark:text-red-400 font-medium'
}

const viewSupplierDetails = (supplier) => {
    // This could emit an event or navigate to supplier details
    console.log('View supplier details:', supplier)
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