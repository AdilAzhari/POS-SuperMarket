<template>
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Cache Management</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Manage application cache for optimal performance</p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Cache Driver:</span>
                    <span class="px-2 py-1 text-sm bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded">
                        {{ stats?.current_driver || 'Loading...' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Clear All Cache -->
                <button @click="clearAllCache" 
                        :disabled="loading.clearAll"
                        class="flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg v-if="loading.clearAll" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Clear All
                </button>

                <!-- Warm Up Cache -->
                <button @click="warmupCache" 
                        :disabled="loading.warmup"
                        class="flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg v-if="loading.warmup" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Warm Up
                </button>

                <!-- Clear Reorder Cache -->
                <button @click="clearReorderCache" 
                        :disabled="loading.reorder"
                        class="flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg v-if="loading.reorder" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Clear Reorder
                </button>

                <!-- Refresh Stats -->
                <button @click="loadStats" 
                        :disabled="loading.stats"
                        class="flex items-center justify-center px-4 py-3 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg v-if="loading.stats" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh Stats
                </button>
            </div>
        </div>

        <!-- Specific Cache Actions -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Specific Cache Controls</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Clear Products Cache -->
                <div class="p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
                    <h4 class="font-medium text-gray-900 dark:text-white">Products Cache</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Product data, search results, barcodes</p>
                    <button @click="clearProductCache" 
                            :disabled="loading.products"
                            class="w-full px-3 py-2 text-sm font-medium text-white bg-purple-600 rounded hover:bg-purple-700 disabled:opacity-50">
                        {{ loading.products ? 'Clearing...' : 'Clear Products' }}
                    </button>
                </div>

                <!-- Clear Inventory Cache -->
                <div class="p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
                    <h4 class="font-medium text-gray-900 dark:text-white">Inventory Cache</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Stock levels, alerts, movements</p>
                    <button @click="clearInventoryCache" 
                            :disabled="loading.inventory"
                            class="w-full px-3 py-2 text-sm font-medium text-white bg-orange-600 rounded hover:bg-orange-700 disabled:opacity-50">
                        {{ loading.inventory ? 'Clearing...' : 'Clear Inventory' }}
                    </button>
                </div>

                <!-- Clear by Tags -->
                <div class="p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
                    <h4 class="font-medium text-gray-900 dark:text-white">Custom Tags</h4>
                    <div class="mt-2 space-y-2">
                        <select v-model="selectedTags" multiple 
                                class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option v-for="(description, tag) in commonTags" :key="tag" :value="tag">
                                {{ tag }} - {{ description }}
                            </option>
                        </select>
                        <button @click="clearByTags" 
                                :disabled="loading.tags || selectedTags.length === 0"
                                class="w-full px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700 disabled:opacity-50">
                            {{ loading.tags ? 'Clearing...' : 'Clear Selected' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cache Statistics -->
        <div v-if="stats" class="px-6 py-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Cache Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">System Info</h4>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Current Driver:</dt>
                            <dd class="font-medium">{{ stats.current_driver }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Tagged Cache Support:</dt>
                            <dd class="font-medium">
                                <span :class="stats.tagged_cache_supported ? 'text-green-600' : 'text-red-600'">
                                    {{ stats.tagged_cache_supported ? 'Yes' : 'No' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Available Cache Tags</h4>
                    <div class="space-y-1 text-sm">
                        <div v-for="(description, tag) in stats.common_cache_tags" :key="tag" 
                             class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                            <span class="font-medium text-blue-600 dark:text-blue-400">{{ tag }}</span>
                            <span class="text-gray-600 dark:text-gray-400 text-xs">{{ description }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Actions -->
        <div v-if="recentActions.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Recent Actions</h3>
            <div class="space-y-2">
                <div v-for="action in recentActions" :key="action.id" 
                     class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded text-sm">
                    <span class="text-gray-900 dark:text-white">{{ action.message }}</span>
                    <span class="text-gray-500 dark:text-gray-400">{{ formatTime(action.timestamp) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAppStore } from '@/stores/app'
import axios from 'axios'

const appStore = useAppStore()

// State
const stats = ref(null)
const selectedTags = ref([])
const recentActions = ref([])
const loading = ref({
    stats: false,
    clearAll: false,
    warmup: false,
    reorder: false,
    products: false,
    inventory: false,
    tags: false,
})

// Computed
const commonTags = computed(() => stats.value?.common_cache_tags || {})
const currentStoreId = computed(() => appStore.currentStore?.id || 1)

// Methods
const loadStats = async () => {
    loading.value.stats = true
    try {
        const response = await axios.get('/api/cache/stats')
        if (response.data.success) {
            stats.value = response.data.data
        }
    } catch (error) {
        console.error('Failed to load cache stats:', error)
        appStore.addNotification({
            type: 'error',
            title: 'Error',
            message: 'Failed to load cache statistics'
        })
    } finally {
        loading.value.stats = false
    }
}

const addRecentAction = (message) => {
    recentActions.value.unshift({
        id: Date.now(),
        message,
        timestamp: new Date()
    })
    
    // Keep only the last 5 actions
    if (recentActions.value.length > 5) {
        recentActions.value = recentActions.value.slice(0, 5)
    }
}

const clearAllCache = async () => {
    loading.value.clearAll = true
    try {
        const response = await axios.post('/api/cache/clear-all')
        if (response.data.success) {
            appStore.addNotification({
                type: 'success',
                title: 'Cache Cleared',
                message: response.data.message
            })
            addRecentAction('All cache cleared')
        }
    } catch (error) {
        console.error('Failed to clear cache:', error)
        appStore.addNotification({
            type: 'error',
            title: 'Error',
            message: error.response?.data?.message || 'Failed to clear cache'
        })
    } finally {
        loading.value.clearAll = false
    }
}

const warmupCache = async () => {
    loading.value.warmup = true
    try {
        const response = await axios.post('/api/cache/warmup', {
            store_id: currentStoreId.value
        })
        if (response.data.success) {
            appStore.addNotification({
                type: 'success',
                title: 'Cache Warmed Up',
                message: `${response.data.warmed_items.length} cache items warmed up`
            })
            addRecentAction(`Cache warmed up for store ${currentStoreId.value}`)
        }
    } catch (error) {
        console.error('Failed to warm up cache:', error)
        appStore.addNotification({
            type: 'error',
            title: 'Error',
            message: error.response?.data?.message || 'Failed to warm up cache'
        })
    } finally {
        loading.value.warmup = false
    }
}

const clearReorderCache = async () => {
    loading.value.reorder = true
    try {
        const response = await axios.post('/api/cache/clear-reorder', {
            store_id: currentStoreId.value
        })
        if (response.data.success) {
            appStore.addNotification({
                type: 'success',
                title: 'Cache Cleared',
                message: response.data.message
            })
            addRecentAction('Reorder cache cleared')
        }
    } catch (error) {
        console.error('Failed to clear reorder cache:', error)
        appStore.addNotification({
            type: 'error',
            title: 'Error',
            message: error.response?.data?.message || 'Failed to clear reorder cache'
        })
    } finally {
        loading.value.reorder = false
    }
}

const clearProductCache = async () => {
    loading.value.products = true
    try {
        const response = await axios.post('/api/cache/clear-products')
        if (response.data.success) {
            appStore.addNotification({
                type: 'success',
                title: 'Cache Cleared',
                message: response.data.message
            })
            addRecentAction('Product cache cleared')
        }
    } catch (error) {
        console.error('Failed to clear product cache:', error)
        appStore.addNotification({
            type: 'error',
            title: 'Error',
            message: error.response?.data?.message || 'Failed to clear product cache'
        })
    } finally {
        loading.value.products = false
    }
}

const clearInventoryCache = async () => {
    loading.value.inventory = true
    try {
        const response = await axios.post('/api/cache/clear-inventory')
        if (response.data.success) {
            appStore.addNotification({
                type: 'success',
                title: 'Cache Cleared',
                message: response.data.message
            })
            addRecentAction('Inventory cache cleared')
        }
    } catch (error) {
        console.error('Failed to clear inventory cache:', error)
        appStore.addNotification({
            type: 'error',
            title: 'Error',
            message: error.response?.data?.message || 'Failed to clear inventory cache'
        })
    } finally {
        loading.value.inventory = false
    }
}

const clearByTags = async () => {
    if (selectedTags.value.length === 0) return
    
    loading.value.tags = true
    try {
        const response = await axios.post('/api/cache/clear-tags', {
            tags: selectedTags.value
        })
        if (response.data.success) {
            appStore.addNotification({
                type: 'success',
                title: 'Cache Cleared',
                message: response.data.message
            })
            addRecentAction(`Cache cleared for tags: ${selectedTags.value.join(', ')}`)
            selectedTags.value = []
        }
    } catch (error) {
        console.error('Failed to clear cache by tags:', error)
        appStore.addNotification({
            type: 'error',
            title: 'Error',
            message: error.response?.data?.message || 'Failed to clear cache by tags'
        })
    } finally {
        loading.value.tags = false
    }
}

const formatTime = (timestamp) => {
    const now = new Date()
    const diff = now - timestamp
    const minutes = Math.floor(diff / 60000)
    
    if (minutes === 0) {
        return 'Just now'
    } else if (minutes === 1) {
        return '1 minute ago'
    } else if (minutes < 60) {
        return `${minutes} minutes ago`
    } else {
        const hours = Math.floor(minutes / 60)
        return hours === 1 ? '1 hour ago' : `${hours} hours ago`
    }
}

// Lifecycle
onMounted(() => {
    loadStats()
})
</script>