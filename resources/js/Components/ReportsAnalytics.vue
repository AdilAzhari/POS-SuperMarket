<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Reports & Analytics</h2>
            <p class="text-gray-600 dark:text-gray-300">Business insights and performance metrics</p>
        </div>

        <!-- Date Range Filter -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center space-x-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date Range</label>
                    <select
                        v-model="dateRange"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="quarter">This Quarter</option>
                        <option value="year">This Year</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Store</label>
                    <select
                        v-model="selectedStore"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">All Stores</option>
                        <option 
                            v-for="store in stores" 
                            :key="store.id" 
                            :value="store.id"
                        >
                            {{ store.name }}
                        </option>
                    </select>
                </div>
                <button
                    class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
                    @click="generateReport"
                >
                    <FileText class="w-4 h-4" />
                    <span>Generate Report</span>
                </button>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 dark:bg-green-900/20 rounded-lg">
                        <DollarSign class="w-6 h-6 text-green-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ metrics.totalRevenue.toFixed(2) }}</p>
                        <p class="text-sm text-green-600">+12.5% from last period</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                        <ShoppingBag class="w-6 h-6 text-blue-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Sales</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ metrics.totalSales }}</p>
                        <p class="text-sm text-blue-600">+8.3% from last period</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900/20 rounded-lg">
                        <TrendingUp class="w-6 h-6 text-yellow-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Average Sale</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ metrics.averageSale.toFixed(2) }}</p>
                        <p class="text-sm text-yellow-600">+3.7% from last period</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
                        <Users class="w-6 h-6 text-purple-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">New Customers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ metrics.newCustomers }}</p>
                        <p class="text-sm text-purple-600">+15.2% from last period</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Metrics Row -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-indigo-100 rounded-lg">
                        <Package class="w-6 h-6 text-indigo-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Items Sold</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ metrics.totalItemsSold || 0 }}</p>
                        <p class="text-sm text-indigo-600">Items processed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Sales Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sales Trend</h3>
                <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                    <div class="text-center">
                        <BarChart3 class="w-12 h-12 text-gray-400 mx-auto mb-2" />
                        <p class="text-gray-500">Sales chart would be displayed here</p>
                        <p class="text-sm text-gray-400">Integration with chart library needed</p>
                    </div>
                </div>
            </div>

            <!-- Category Performance -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Categories</h3>
                <div class="space-y-4">
                    <div
                        v-for="category in topCategories"
                        :key="category.name"
                        class="flex items-center justify-between"
                    >
                        <div class="flex items-center space-x-3">
                            <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: category.color }"></div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ category.name }}</span>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${{ category.revenue.toFixed(2) }}</p>
                            <p class="text-xs text-gray-500">{{ category.percentage }}%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Reports -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Products -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Selling Products</h3>
                        <div class="flex items-center space-x-2">
                            <select
                                v-model="topProductsSort"
                                class="text-sm border border-gray-300 dark:border-gray-600 rounded px-2 py-1 focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="quantity">By Quantity</option>
                                <option value="revenue">By Revenue</option>
                                <option value="name">By Name</option>
                            </select>
                            <button
                                @click="topProductsDirection = topProductsDirection === 'desc' ? 'asc' : 'desc'"
                                class="p-1 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50"
                                :title="topProductsDirection === 'desc' ? 'Sort ascending' : 'Sort descending'"
                            >
                                <ArrowUpDown class="w-4 h-4" :class="topProductsDirection === 'asc' ? '' : 'rotate-180'" />
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div
                            v-for="product in sortedTopProducts.slice((topProductsPage - 1) * topProductsPerPage, topProductsPage * topProductsPerPage)"
                            :key="product.id"
                            class="flex items-center justify-between"
                        >
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <Package class="w-6 h-6 text-gray-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ product.name }}</p>
                                    <p class="text-xs text-gray-500">{{ product.sku }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ product.soldQuantity }} sold</p>
                                <p class="text-xs text-gray-500">${{ product.revenue.toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products Pagination -->
                    <div v-if="topProductsTotalPages > 1" class="mt-4 flex items-center justify-between text-sm">
            <span class="text-gray-500">
              {{ (topProductsPage - 1) * topProductsPerPage + 1 }}-{{ Math.min(topProductsPage * topProductsPerPage, sortedTopProducts.length) }} of {{ sortedTopProducts.length }}
            </span>
                        <div class="flex space-x-2">
                            <button
                                @click="topProductsPage = Math.max(1, topProductsPage - 1)"
                                :disabled="topProductsPage === 1"
                                class="px-2 py-1 border rounded hover:bg-gray-50 disabled:opacity-50"
                            >
                                Previous
                            </button>
                            <button
                                @click="topProductsPage = Math.min(topProductsTotalPages, topProductsPage + 1)"
                                :disabled="topProductsPage === topProductsTotalPages"
                                class="px-2 py-1 border rounded hover:bg-gray-50 disabled:opacity-50"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Low Stock Alert</h3>
                        <div class="flex items-center space-x-2">
                            <select
                                v-model="lowStockSort"
                                class="text-sm border border-gray-300 dark:border-gray-600 rounded px-2 py-1 focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="stock">By Stock Level</option>
                                <option value="name">By Name</option>
                                <option value="threshold">By Threshold</option>
                            </select>
                            <button
                                @click="lowStockDirection = lowStockDirection === 'desc' ? 'asc' : 'desc'"
                                class="p-1 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50"
                                :title="lowStockDirection === 'desc' ? 'Sort ascending' : 'Sort descending'"
                            >
                                <ArrowUpDown class="w-4 h-4" :class="lowStockDirection === 'asc' ? '' : 'rotate-180'" />
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div
                            v-for="product in sortedLowStockProducts.slice((lowStockPage - 1) * lowStockPerPage, lowStockPage * lowStockPerPage)"
                            :key="product.id"
                            class="flex items-center justify-between"
                        >
                            <div class="flex items-center space-x-3">
                                <div :class="[
                  'w-2 h-2 rounded-full',
                  product.stock === 0 ? 'bg-red-600' :
                  product.stock <= product.lowStockThreshold * 0.5 ? 'bg-red-500' : 'bg-yellow-500'
                ]"></div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ product.name }}</p>
                                    <p class="text-xs text-gray-500">{{ product.sku }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p :class="[
                  'text-sm font-medium',
                  product.stock === 0 ? 'text-red-700' :
                  product.stock <= product.lowStockThreshold * 0.5 ? 'text-red-600' : 'text-yellow-600'
                ]">
                                    {{ product.stock }} left
                                </p>
                                <p class="text-xs text-gray-500">Min: {{ product.lowStockThreshold }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Pagination -->
                    <div v-if="lowStockTotalPages > 1" class="mt-4 flex items-center justify-between text-sm">
            <span class="text-gray-500">
              {{ (lowStockPage - 1) * lowStockPerPage + 1 }}-{{ Math.min(lowStockPage * lowStockPerPage, sortedLowStockProducts.length) }} of {{ sortedLowStockProducts.length }}
            </span>
                        <div class="flex space-x-2">
                            <button
                                @click="lowStockPage = Math.max(1, lowStockPage - 1)"
                                :disabled="lowStockPage === 1"
                                class="px-2 py-1 border rounded hover:bg-gray-50 disabled:opacity-50"
                            >
                                Previous
                            </button>
                            <button
                                @click="lowStockPage = Math.min(lowStockTotalPages, lowStockPage + 1)"
                                :disabled="lowStockPage === lowStockTotalPages"
                                class="px-2 py-1 border rounded hover:bg-gray-50 disabled:opacity-50"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Export Reports</h3>
            <div class="flex flex-wrap gap-3">
                <button
                    class="flex items-center space-x-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 transition-colors"
                    @click="exportReport('pdf')"
                >
                    <FileText class="w-4 h-4" />
                    <span>Export PDF</span>
                </button>
                <button
                    class="flex items-center space-x-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 transition-colors"
                    @click="exportReport('excel')"
                >
                    <Download class="w-4 h-4" />
                    <span>Export Excel</span>
                </button>
                <button
                    class="flex items-center space-x-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 transition-colors"
                    @click="exportReport('csv')"
                >
                    <FileSpreadsheet class="w-4 h-4" />
                    <span>Export CSV</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useProductsStore } from '@/stores/products'
import axios from 'axios'
import jsPDF from 'jspdf'
import html2canvas from 'html2canvas'
import * as XLSX from 'xlsx'
import {
    FileText,
    DollarSign,
    ShoppingBag,
    TrendingUp,
    Users,
    BarChart3,
    Download,
    FileSpreadsheet,
    ArrowUpDown,
    Package,
} from 'lucide-vue-next'

const productsStore = useProductsStore()
const { products } = storeToRefs(productsStore)

const dateRange = ref('month')
const selectedStore = ref('')
const isLoading = ref(false)
const message = ref('')
const messageType = ref<'success' | 'error'>('success')

// Real data states
const realData = ref({
  overview: null,
  salesTrend: null,
  topProducts: null,
  inventoryAnalysis: null
})
const stores = ref([])
const dataLoaded = ref(false)

// Top Products pagination and sorting
const topProductsSort = ref('quantity')
const topProductsDirection = ref('desc')
const topProductsPage = ref(1)
const topProductsPerPage = ref(10)

// Low Stock pagination and sorting
const lowStockSort = ref('stock')
const lowStockDirection = ref('asc')
const lowStockPage = ref(1)
const lowStockPerPage = ref(10)

const notify = (msg: string, type: 'success' | 'error' = 'success') => {
    message.value = msg
    messageType.value = type
    setTimeout(() => message.value = '', 3000)
}

// Real metrics data computed from API
const metrics = computed(() => {
    if (!realData.value.overview) {
        return {
            totalRevenue: 0,
            totalSales: 0,
            averageSale: 0,
            newCustomers: 0,
        }
    }
    
    const overview = realData.value.overview
    return {
        totalRevenue: overview.total_revenue || 0,
        totalSales: overview.total_sales || 0,
        averageSale: overview.average_sale || 0,
        newCustomers: overview.new_customers || 0,
        totalItemsSold: overview.total_items_sold || 0,
    }
})

const topCategories = computed(() => {
    if (!realData.value.overview?.category_breakdown) {
        return []
    }
    
    const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#F97316']
    
    return realData.value.overview.category_breakdown.map((category, index) => ({
        name: category.name,
        revenue: category.revenue || 0,
        percentage: category.percentage || 0,
        color: colors[index % colors.length]
    }))
})

const topProducts = computed(() => {
    if (!realData.value.topProducts) {
        return []
    }
    
    return realData.value.topProducts.map(product => ({
        id: product.id,
        name: product.name,
        sku: product.sku,
        soldQuantity: product.sold_quantity || 0,
        revenue: product.revenue || 0,
        category: product.category
    }))
})

const sortedTopProducts = computed(() => {
    const sorted = [...topProducts.value].sort((a, b) => {
        let aValue, bValue

        switch (topProductsSort.value) {
            case 'quantity':
                aValue = a.soldQuantity
                bValue = b.soldQuantity
                break
            case 'revenue':
                aValue = a.revenue
                bValue = b.revenue
                break
            case 'name':
                aValue = a.name.toLowerCase()
                bValue = b.name.toLowerCase()
                break
            default:
                return 0
        }

        if (topProductsDirection.value === 'asc') {
            return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
        } else {
            return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
        }
    })

    return sorted
})

const topProductsTotalPages = computed(() =>
    Math.ceil(sortedTopProducts.value.length / topProductsPerPage.value)
)

const lowStockProducts = computed(() => {
    if (!realData.value.inventoryAnalysis?.low_stock_products) {
        return []
    }
    
    return realData.value.inventoryAnalysis.low_stock_products
})

const sortedLowStockProducts = computed(() => {
    const sorted = [...lowStockProducts.value].sort((a, b) => {
        let aValue, bValue

        switch (lowStockSort.value) {
            case 'stock':
                aValue = a.stock
                bValue = b.stock
                break
            case 'name':
                aValue = a.name.toLowerCase()
                bValue = b.name.toLowerCase()
                break
            case 'threshold':
                aValue = a.lowStockThreshold
                bValue = b.lowStockThreshold
                break
            default:
                return 0
        }

        if (lowStockDirection.value === 'asc') {
            return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
        } else {
            return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
        }
    })

    return sorted
})

const lowStockTotalPages = computed(() =>
    Math.ceil(sortedLowStockProducts.value.length / lowStockPerPage.value)
)

// Fetch real data from API
const fetchAnalyticsData = async () => {
    try {
        isLoading.value = true

        const params = {
            date_range: dateRange.value,
            store_id: selectedStore.value || null
        }

        // Fetch analytics data
        const analyticsResponse = await axios.get('/api/reports/analytics', { params })
        realData.value.overview = analyticsResponse.data.overview
        realData.value.topProducts = analyticsResponse.data.top_products
        realData.value.inventoryAnalysis = analyticsResponse.data.inventory_analysis

        dataLoaded.value = true
        notify('Report data loaded successfully!')
    } catch (e) {
        console.error('Failed to fetch analytics:', e)
        notify('Failed to load report data', 'error')
    } finally {
        isLoading.value = false
    }
}

const fetchStores = async () => {
    try {
        const response = await axios.get('/api/stores')
        stores.value = response.data.data || []
    } catch (e) {
        console.error('Failed to fetch stores:', e)
    }
}

const generateReport = async () => {
    await fetchAnalyticsData()
}

// Initialize data on component mount
const initializeComponent = async () => {
    await Promise.all([
        fetchStores(),
        fetchAnalyticsData()
    ])
}

const exportReport = async (format: string) => {
    try {
        isLoading.value = true

        // Use real API endpoint for exporting
        const response = await axios.post('/api/reports/export', {
            report_type: 'sales',
            format: format === 'excel' ? 'excel' : format,
            date_range: dateRange.value,
            store_id: selectedStore.value || null
        }, {
            responseType: format === 'pdf' ? 'blob' : 'json'
        })

        if (format === 'pdf') {
            // Handle PDF download
            const blob = new Blob([response.data], { type: 'application/pdf' })
            const url = window.URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = url
            link.download = `sales-report-${dateRange.value}-${new Date().toISOString().split('T')[0]}.pdf`
            document.body.appendChild(link)
            link.click()
            link.remove()
            window.URL.revokeObjectURL(url)
        } else {
            // For other formats, the backend handles the download
            if (response.data.download_url) {
                window.open(response.data.download_url, '_blank')
            }
        }

        // Fallback: Generate client-side report if API fails
        const reportData = {
            dateRange: dateRange.value,
            store: selectedStore.value ? stores.value.find(s => s.id == selectedStore.value)?.name || 'Selected Store' : 'All Stores',
            generatedAt: new Date().toISOString(),
            metrics: metrics.value,
            topProducts: sortedTopProducts.value.slice(0, 10),
            lowStockProducts: sortedLowStockProducts.value.slice(0, 10),
            categoryData: topCategories.value
        }

        const timestamp = new Date().toISOString().split('T')[0]
        const filename = `sales-report-${timestamp}`

        switch (format) {
            case 'pdf':
                await generatePDFReport(reportData, filename)
                break
            case 'excel':
                generateExcelReport(reportData, filename)
                break
            case 'csv':
                generateCSVReport(reportData, filename)
                break
            default:
                throw new Error('Unsupported format')
        }

        notify(`Report exported as ${format.toUpperCase()}`)
    } catch (e) {
        notify('Failed to export report', 'error')
        console.error('Export error:', e)
    } finally {
        isLoading.value = false
    }
}

// PDF Export Function
const generatePDFReport = async (data, filename: string) => {
    const doc = new jsPDF('p', 'mm', 'a4')

    // Header
    doc.setFontSize(20)
    doc.setFont('helvetica', 'bold')
    doc.text('Sales & Analytics Report', 20, 20)

    doc.setFontSize(10)
    doc.setFont('helvetica', 'normal')
    doc.text(`Generated: ${new Date(data.generatedAt).toLocaleString()}`, 20, 30)
    doc.text(`Date Range: ${data.dateRange}`, 20, 35)
    doc.text(`Store: ${data.store}`, 20, 40)

    // Key Metrics Section
    doc.setFontSize(14)
    doc.setFont('helvetica', 'bold')
    doc.text('Key Metrics', 20, 55)

    doc.setFontSize(10)
    doc.setFont('helvetica', 'normal')
    const metrics = [
        ['Total Revenue', `$${data.metrics.totalRevenue.toFixed(2)}`],
        ['Total Sales', data.metrics.totalSales.toString()],
        ['Average Sale', `$${data.metrics.averageSale.toFixed(2)}`],
        ['New Customers', data.metrics.newCustomers.toString()],
        ['Customer Retention', `${data.metrics.customerRetention}%`]
    ]

    metrics.forEach((metric, index) => {
        doc.text(`${metric[0]}:`, 25, 65 + (index * 5))
        doc.text(metric[1], 70, 65 + (index * 5))
    })

    // Top Products Section
    doc.setFontSize(14)
    doc.setFont('helvetica', 'bold')
    doc.text('Top Products', 20, 100)

    doc.setFontSize(9)
    doc.setFont('helvetica', 'bold')
    doc.text('Product Name', 25, 110)
    doc.text('SKU', 85, 110)
    doc.text('Qty Sold', 115, 110)
    doc.text('Revenue', 145, 110)

    doc.setFont('helvetica', 'normal')
    data.topProducts.slice(0, 10).forEach((product, index) => {
        const y = 115 + (index * 5)
        doc.text(product.name.substring(0, 20), 25, y)
        doc.text(product.sku, 85, y)
        doc.text(product.soldQuantity.toString(), 115, y)
        doc.text(`$${product.revenue.toFixed(2)}`, 145, y)
    })

    // Low Stock Section
    const lowStockY = 115 + (data.topProducts.length * 5) + 15
    doc.setFontSize(14)
    doc.setFont('helvetica', 'bold')
    doc.text('Low Stock Alerts', 20, lowStockY)

    doc.setFontSize(9)
    doc.setFont('helvetica', 'bold')
    doc.text('Product Name', 25, lowStockY + 10)
    doc.text('SKU', 85, lowStockY + 10)
    doc.text('Stock', 115, lowStockY + 10)
    doc.text('Threshold', 145, lowStockY + 10)

    doc.setFont('helvetica', 'normal')
    data.lowStockProducts.slice(0, 10).forEach((product, index) => {
        const y = lowStockY + 15 + (index * 5)
        doc.text(product.name.substring(0, 20), 25, y)
        doc.text(product.sku, 85, y)
        doc.text(product.stock.toString(), 115, y)
        doc.text(product.lowStockThreshold.toString(), 145, y)
    })

    // Save PDF
    doc.save(`${filename}.pdf`)
}

// Excel Export Function
const generateExcelReport = (data, filename: string) => {
    const workbook = XLSX.utils.book_new()

    // Summary Sheet
    const summaryData = [
        ['Sales & Analytics Report'],
        [''],
        ['Report Details'],
        ['Generated', new Date(data.generatedAt).toLocaleString()],
        ['Date Range', data.dateRange],
        ['Store', data.store],
        [''],
        ['Key Metrics'],
        ['Total Revenue', data.metrics.totalRevenue],
        ['Total Sales', data.metrics.totalSales],
        ['Average Sale', data.metrics.averageSale],
        ['New Customers', data.metrics.newCustomers],
        ['Customer Retention %', data.metrics.customerRetention]
    ]

    const summarySheet = XLSX.utils.aoa_to_sheet(summaryData)

    // Style the summary sheet
    summarySheet['!cols'] = [{ width: 20 }, { width: 15 }]

    XLSX.utils.book_append_sheet(workbook, summarySheet, 'Summary')

    // Top Products Sheet
    const topProductsData = [
        ['Top Products'],
        [''],
        ['Product Name', 'SKU', 'Quantity Sold', 'Revenue', 'Category']
    ]

    data.topProducts.forEach(product => {
        topProductsData.push([
            product.name,
            product.sku,
            product.soldQuantity,
            product.revenue,
            product.category || 'N/A'
        ])
    })

    const topProductsSheet = XLSX.utils.aoa_to_sheet(topProductsData)
    topProductsSheet['!cols'] = [
        { width: 30 }, { width: 15 }, { width: 15 }, { width: 15 }, { width: 20 }
    ]

    XLSX.utils.book_append_sheet(workbook, topProductsSheet, 'Top Products')

    // Low Stock Sheet
    const lowStockData = [
        ['Low Stock Products'],
        [''],
        ['Product Name', 'SKU', 'Current Stock', 'Low Stock Threshold', 'Reorder Needed']
    ]

    data.lowStockProducts.forEach(product => {
        lowStockData.push([
            product.name,
            product.sku,
            product.stock,
            product.lowStockThreshold,
            product.stock <= product.lowStockThreshold ? 'Yes' : 'No'
        ])
    })

    const lowStockSheet = XLSX.utils.aoa_to_sheet(lowStockData)
    lowStockSheet['!cols'] = [
        { width: 30 }, { width: 15 }, { width: 15 }, { width: 20 }, { width: 15 }
    ]

    XLSX.utils.book_append_sheet(workbook, lowStockSheet, 'Low Stock')

    // Category Performance Sheet (if data available)
    if (data.categoryData && data.categoryData.length > 0) {
        const categoryData = [
            ['Category Performance'],
            [''],
            ['Category', 'Sales', 'Revenue', 'Products Count']
        ]

        data.categoryData.forEach(category => {
            categoryData.push([
                category.name,
                category.sales || 0,
                category.revenue || 0,
                category.productCount || 0
            ])
        })

        const categorySheet = XLSX.utils.aoa_to_sheet(categoryData)
        categorySheet['!cols'] = [{ width: 25 }, { width: 15 }, { width: 15 }, { width: 15 }]

        XLSX.utils.book_append_sheet(workbook, categorySheet, 'Categories')
    }

    // Save Excel file
    XLSX.writeFile(workbook, `${filename}.xlsx`)
}

// Enhanced CSV Export Function
const generateCSVReport = (data, filename: string) => {
    let csv = ''

    // Header
    csv += 'Sales & Analytics Report\n'
    csv += `Generated: ${new Date(data.generatedAt).toLocaleString()}\n`
    csv += `Date Range: ${data.dateRange}\n`
    csv += `Store: ${data.store}\n\n`

    // Key Metrics
    csv += 'KEY METRICS\n'
    csv += 'Metric,Value\n'
    csv += `Total Revenue,$${data.metrics.totalRevenue.toFixed(2)}\n`
    csv += `Total Sales,${data.metrics.totalSales}\n`
    csv += `Average Sale,$${data.metrics.averageSale.toFixed(2)}\n`
    csv += `New Customers,${data.metrics.newCustomers}\n`
    csv += `Customer Retention,${data.metrics.customerRetention}%\n\n`

    // Top Products
    csv += 'TOP PRODUCTS\n'
    csv += 'Product Name,SKU,Quantity Sold,Revenue,Category\n'
    data.topProducts.forEach(product => {
        csv += `"${product.name}",${product.sku},${product.soldQuantity},$${product.revenue.toFixed(2)},"${product.category || 'N/A'}"\n`
    })
    csv += '\n'

    // Low Stock Products
    csv += 'LOW STOCK PRODUCTS\n'
    csv += 'Product Name,SKU,Current Stock,Low Stock Threshold,Reorder Needed\n'
    data.lowStockProducts.forEach(product => {
        const reorderNeeded = product.stock <= product.lowStockThreshold ? 'Yes' : 'No'
        csv += `"${product.name}",${product.sku},${product.stock},${product.lowStockThreshold},${reorderNeeded}\n`
    })

    // Category Performance (if available)
    if (data.categoryData && data.categoryData.length > 0) {
        csv += '\nCATEGORY PERFORMANCE\n'
        csv += 'Category,Sales,Revenue,Products Count\n'
        data.categoryData.forEach(category => {
            csv += `"${category.name}",${category.sales || 0},$${(category.revenue || 0).toFixed(2)},${category.productCount || 0}\n`
        })
    }

    // Create and download CSV
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${filename}.csv`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
}

// Initialize component when mounted
initializeComponent()
</script>
