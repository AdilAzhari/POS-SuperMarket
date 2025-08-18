<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h2 class="text-2xl font-bold text-gray-900">Reports & Analytics</h2>
      <p class="text-gray-600">Business insights and performance metrics</p>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <div class="flex items-center space-x-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
          <select
            v-model="dateRange"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="quarter">This Quarter</option>
            <option value="year">This Year</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Store</label>
          <select
            v-model="selectedStore"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">All Stores</option>
            <option value="store-1">Downtown Branch</option>
            <option value="store-2">Mall Location</option>
            <option value="store-3">Suburban Store</option>
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
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
          <div class="p-2 bg-green-100 rounded-lg">
            <DollarSign class="w-6 h-6 text-green-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Total Revenue</p>
            <p class="text-2xl font-bold text-gray-900">${{ metrics.totalRevenue.toFixed(2) }}</p>
            <p class="text-sm text-green-600">+12.5% from last period</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <ShoppingBag class="w-6 h-6 text-blue-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Total Sales</p>
            <p class="text-2xl font-bold text-gray-900">{{ metrics.totalSales }}</p>
            <p class="text-sm text-blue-600">+8.3% from last period</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
          <div class="p-2 bg-yellow-100 rounded-lg">
            <TrendingUp class="w-6 h-6 text-yellow-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Average Sale</p>
            <p class="text-2xl font-bold text-gray-900">${{ metrics.averageSale.toFixed(2) }}</p>
            <p class="text-sm text-yellow-600">+3.7% from last period</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
          <div class="p-2 bg-purple-100 rounded-lg">
            <Users class="w-6 h-6 text-purple-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">New Customers</p>
            <p class="text-2xl font-bold text-gray-900">{{ metrics.newCustomers }}</p>
            <p class="text-sm text-purple-600">+15.2% from last period</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Sales Chart -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Sales Trend</h3>
        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
          <div class="text-center">
            <BarChart3 class="w-12 h-12 text-gray-400 mx-auto mb-2" />
            <p class="text-gray-500">Sales chart would be displayed here</p>
            <p class="text-sm text-gray-400">Integration with chart library needed</p>
          </div>
        </div>
      </div>

      <!-- Category Performance -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Categories</h3>
        <div class="space-y-4">
          <div
            v-for="category in topCategories"
            :key="category.name"
            class="flex items-center justify-between"
          >
            <div class="flex items-center space-x-3">
              <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: category.color }"></div>
              <span class="text-sm font-medium text-gray-900">{{ category.name }}</span>
            </div>
            <div class="text-right">
              <p class="text-sm font-medium text-gray-900">${{ category.revenue.toFixed(2) }}</p>
              <p class="text-xs text-gray-500">{{ category.percentage }}%</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Reports -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Top Products -->
      <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Top Selling Products</h3>
        </div>
        <div class="p-6">
          <div class="space-y-4">
            <div
              v-for="product in topProducts"
              :key="product.id"
              class="flex items-center justify-between"
            >
              <div class="flex items-center space-x-3">
                <img
                  :src="product.image"
                  :alt="product.name"
                  class="w-10 h-10 rounded-lg object-cover"
                />
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ product.name }}</p>
                  <p class="text-xs text-gray-500">{{ product.sku }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-medium text-gray-900">{{ product.soldQuantity }} sold</p>
                <p class="text-xs text-gray-500">${{ product.revenue.toFixed(2) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Low Stock Alert -->
      <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Low Stock Alert</h3>
        </div>
        <div class="p-6">
          <div class="space-y-4">
            <div
              v-for="product in lowStockProducts"
              :key="product.id"
              class="flex items-center justify-between"
            >
              <div class="flex items-center space-x-3">
                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ product.name }}</p>
                  <p class="text-xs text-gray-500">{{ product.sku }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-medium text-red-600">{{ product.stock }} left</p>
                <p class="text-xs text-gray-500">Min: {{ product.lowStockThreshold }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Options -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Export Reports</h3>
      <div class="flex flex-wrap gap-3">
        <button
          class="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          @click="exportReport('pdf')"
        >
          <FileText class="w-4 h-4" />
          <span>Export PDF</span>
        </button>
        <button
          class="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          @click="exportReport('excel')"
        >
          <Download class="w-4 h-4" />
          <span>Export Excel</span>
        </button>
        <button
          class="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
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
import {
  FileText,
  DollarSign,
  ShoppingBag,
  TrendingUp,
  Users,
  BarChart3,
  Download,
  FileSpreadsheet,
} from 'lucide-vue-next'

const productsStore = useProductsStore()
const { products } = storeToRefs(productsStore)

const dateRange = ref('month')
const selectedStore = ref('')
const isLoading = ref(false)
const message = ref('')
const messageType = ref<'success' | 'error'>('success')

const notify = (msg: string, type: 'success' | 'error' = 'success') => {
  message.value = msg
  messageType.value = type
  setTimeout(() => message.value = '', 3000)
}

// Mock metrics data
const metrics = ref({
  totalRevenue: 45678.9,
  totalSales: 1234,
  averageSale: 37.02,
  newCustomers: 89,
})

const topCategories = ref([
  { name: 'Beverages', revenue: 12500.0, percentage: 27.4, color: '#3B82F6' },
  { name: 'Dairy', revenue: 9800.0, percentage: 21.5, color: '#10B981' },
  { name: 'Bakery', revenue: 8200.0, percentage: 18.0, color: '#F59E0B' },
  { name: 'Fruits', revenue: 7100.0, percentage: 15.5, color: '#EF4444' },
  { name: 'Meat', revenue: 8078.9, percentage: 17.6, color: '#8B5CF6' },
])

const topProducts = computed(() => {
  return products.value.slice(0, 5).map(product => ({
    ...product,
    soldQuantity: Math.floor(Math.random() * 100) + 20,
    revenue: (Math.floor(Math.random() * 100) + 20) * product.price,
  }))
})

const lowStockProducts = computed(() => {
  return products.value.filter(product => product.stock <= product.lowStockThreshold).slice(0, 5)
})

const generateReport = async () => {
  try {
    isLoading.value = true
    const response = await axios.post('/api/reports/generate', {
      dateRange: dateRange.value,
      storeId: selectedStore.value
    })
    // Process and display the report data
    notify('Report generated successfully!')
  } catch (e) {
    notify('Failed to generate report', 'error')
  } finally {
    isLoading.value = false
  }
}

const exportReport = async (format: string) => {
  try {
    isLoading.value = true
    const response = await axios.post('/api/reports/export', {
      format,
      dateRange: dateRange.value,
      storeId: selectedStore.value
    }, { responseType: 'blob' })
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `report.${format}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    notify(`Report exported as ${format.toUpperCase()}`)
  } catch (e) {
    notify('Failed to export report', 'error')
  } finally {
    isLoading.value = false
  }
}
</script>
