<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-2xl font-bold text-gray-900">Sales History</h2>
      <p class="text-gray-600">View and manage past transactions</p>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
      <div class="space-y-4">
        <!-- Search Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="relative flex-1">
            <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
            <input
              v-model="searchQuery"
              placeholder="Search by transaction ID, customer name, or phone"
              class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="text-sm text-gray-500">
            {{ paginatedSales.length }} of {{ filteredSales.length }} sales
          </div>
        </div>

        <!-- Filters and Date Range -->
        <div class="flex flex-col lg:flex-row gap-4">
          <!-- Filters -->
          <div class="flex flex-wrap gap-4 items-center">
            <!-- Date Range -->
            <div class="flex items-center gap-2">
              <label class="text-sm text-gray-600">From:</label>
              <input v-model="startDate" type="date" class="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
              <span class="text-gray-400">to</span>
              <input v-model="endDate" type="date" class="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
            </div>

            <!-- Payment Method Filter -->
            <div>
              <select
                v-model="filters.paymentMethod"
                class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Payment Methods</option>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="tng">Touch n Go</option>
                <option value="stripe">Stripe</option>
              </select>
            </div>

            <!-- Status Filter -->
            <div>
              <select
                v-model="filters.status"
                class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Status</option>
                <option value="completed">Completed</option>
                <option value="refunded">Refunded</option>
                <option value="voided">Voided</option>
              </select>
            </div>

            <!-- Amount Range -->
            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-600">Amount:</span>
              <input
                v-model.number="filters.minAmount"
                type="number"
                placeholder="Min"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                min="0"
                step="0.01"
              />
              <span class="text-gray-400">-</span>
              <input
                v-model.number="filters.maxAmount"
                type="number"
                placeholder="Max"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                min="0"
                step="0.01"
              />
            </div>

            <!-- Customer Phone -->
            <div>
              <input
                v-model="customerPhone"
                placeholder="Customer phone"
                class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
              />
            </div>

            <!-- Clear Filters -->
            <button
              v-if="hasActiveFilters"
              @click="clearFilters"
              class="text-sm text-blue-600 hover:text-blue-800"
            >
              Clear filters
            </button>
          </div>

          <!-- Sorting -->
          <div class="flex items-center gap-2 lg:ml-auto">
            <span class="text-sm text-gray-600">Sort by:</span>
            <select
              v-model="sortBy"
              class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
            >
              <option value="id">Transaction ID</option>
              <option value="customerName">Customer</option>
              <option value="total">Total Amount</option>
              <option value="date">Date</option>
              <option value="paymentMethod">Payment Method</option>
              <option value="status">Status</option>
            </select>
            <button
              @click="toggleSortDirection"
              class="p-2 border rounded-lg hover:bg-gray-50"
              :title="sortDirection === 'asc' ? 'Sort descending' : 'Sort ascending'"
            >
              <ArrowUpDown class="w-4 h-4" :class="sortDirection === 'desc' ? 'rotate-180' : ''" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Sales Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="text-2xl font-bold text-blue-600">{{ filteredSales.length }}</div>
        <div class="text-sm text-gray-600">Total Sales</div>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="text-2xl font-bold text-green-600">${{ totalRevenue.toFixed(2) }}</div>
        <div class="text-sm text-gray-600">Total Revenue</div>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="text-2xl font-bold text-purple-600">${{ averageOrderValue.toFixed(2) }}</div>
        <div class="text-sm text-gray-600">Avg Order Value</div>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="text-2xl font-bold text-orange-600">{{ totalItems }}</div>
        <div class="text-sm text-gray-600">Total Items Sold</div>
      </div>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-600">
            <tr>
              <th class="text-left px-4 py-2 font-medium">Txn ID</th>
              <th class="text-left px-4 py-2 font-medium">Customer</th>
              <th class="text-left px-4 py-2 font-medium">Items</th>
              <th class="text-right px-4 py-2 font-medium">Subtotal</th>
              <th class="text-right px-4 py-2 font-medium">Discount</th>
              <th class="text-right px-4 py-2 font-medium">Tax</th>
              <th class="text-right px-4 py-2 font-medium">Total</th>
              <th class="text-left px-4 py-2 font-medium">Payment</th>
              <th class="text-left px-4 py-2 font-medium">Date</th>
              <th class="text-left px-4 py-2 font-medium">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in paginatedSales" :key="s.id" class="border-t">
              <td class="px-4 py-2 font-medium text-gray-900">
                {{ s.id }}
              </td>
              <td class="px-4 py-2">
                <div class="font-medium">
                  {{ s.customerName }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ s.customerPhone }}
                </div>
              </td>
              <td class="px-4 py-2">{{ s.items }}</td>
              <td class="px-4 py-2 text-right">${{ s.subtotal.toFixed(2) }}</td>
              <td class="px-4 py-2 text-right">-${{ s.discount.toFixed(2) }}</td>
              <td class="px-4 py-2 text-right">${{ s.tax.toFixed(2) }}</td>
              <td class="px-4 py-2 text-right font-semibold">${{ s.total.toFixed(2) }}</td>
              <td class="px-4 py-2">{{ s.paymentMethod }}</td>
              <td class="px-4 py-2">{{ s.date }} {{ s.time }}</td>
              <td class="px-4 py-2">
                <select
                  v-model="statusMap[s.id]"
                  class="text-xs border rounded px-2 py-1"
                  @change="updateStatus(s.id, statusMap[s.id])"
                >
                  <option value="completed">Completed</option>
                  <option value="refunded">Refunded</option>
                  <option value="voided">Voided</option>
                </select>
              </td>
            </tr>
            <tr v-if="filteredSales.length === 0">
              <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                {{ hasActiveFilters ? 'No sales match your search criteria.' : 'No sales found.' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
        <div class="text-sm text-gray-500">
          Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredSales.length) }} of {{ filteredSales.length }} sales
        </div>
        <div class="flex items-center space-x-2">
          <button
            @click="currentPage = Math.max(1, currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-3 py-1 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <div class="flex space-x-1">
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="currentPage = page"
              :class="[
                'px-3 py-1 border rounded-lg',
                page === currentPage
                  ? 'bg-blue-600 text-white border-blue-600'
                  : 'hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
          </div>
          <button
            @click="currentPage = Math.min(totalPages, currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="px-3 py-1 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watchEffect } from 'vue'
import { useSalesStore } from '@/stores/sales'
import { Search, ArrowUpDown } from 'lucide-vue-next'

const salesStore = useSalesStore()

// Search and filter state
const searchQuery = ref('')
const startDate = ref('')
const endDate = ref('')
const customerPhone = ref('')
const filters = reactive({
  paymentMethod: '',
  status: '',
  minAmount: null as number | null,
  maxAmount: null as number | null
})

// Sorting state
const sortBy = ref('id')
const sortDirection = ref('desc')

// Pagination state
const currentPage = ref(1)
const itemsPerPage = ref(20)

// Computed properties
const hasActiveFilters = computed(() => {
  return searchQuery.value.trim() || startDate.value || endDate.value || customerPhone.value ||
         filters.paymentMethod || filters.status || filters.minAmount !== null || filters.maxAmount !== null
})

const filteredSales = computed(() => {
  let list = salesStore.sales
  
  // Apply search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    list = list.filter(s => 
      s.id.toString().includes(query) ||
      s.customerName?.toLowerCase().includes(query) ||
      s.customerPhone?.includes(query)
    )
  }
  
  // Apply date filtering
  if (startDate.value && endDate.value) {
    list = salesStore.getSalesByDateRange(startDate.value, endDate.value)
  } else if (startDate.value) {
    list = list.filter(s => s.date >= startDate.value)
  } else if (endDate.value) {
    list = list.filter(s => s.date <= endDate.value)
  }
  
  // Apply customer phone filter
  if (customerPhone.value) {
    list = list.filter(s => s.customerPhone === customerPhone.value)
  }

  // Apply payment method filter
  if (filters.paymentMethod) {
    list = list.filter(s => s.paymentMethod === filters.paymentMethod)
  }

  // Apply status filter
  if (filters.status) {
    list = list.filter(s => s.status === filters.status)
  }

  // Apply amount range filters
  if (filters.minAmount !== null && filters.minAmount !== '') {
    list = list.filter(s => s.total >= filters.minAmount!)
  }

  if (filters.maxAmount !== null && filters.maxAmount !== '') {
    list = list.filter(s => s.total <= filters.maxAmount!)
  }

  // Apply sorting
  list = [...list].sort((a, b) => {
    let aValue: any, bValue: any

    switch (sortBy.value) {
      case 'id':
        aValue = Number(a.id)
        bValue = Number(b.id)
        break
      case 'customerName':
        aValue = (a.customerName || '').toLowerCase()
        bValue = (b.customerName || '').toLowerCase()
        break
      case 'total':
        aValue = Number(a.total)
        bValue = Number(b.total)
        break
      case 'date':
        aValue = new Date(a.date + ' ' + a.time)
        bValue = new Date(b.date + ' ' + b.time)
        break
      case 'paymentMethod':
        aValue = (a.paymentMethod || '').toLowerCase()
        bValue = (b.paymentMethod || '').toLowerCase()
        break
      case 'status':
        aValue = (a.status || '').toLowerCase()
        bValue = (b.status || '').toLowerCase()
        break
      default:
        return 0
    }

    if (sortDirection.value === 'asc') {
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
    } else {
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
    }
  })
  
  return list
})

// Summary computed properties
const totalRevenue = computed(() => {
  return filteredSales.value.reduce((sum, sale) => sum + sale.total, 0)
})

const averageOrderValue = computed(() => {
  const sales = filteredSales.value
  return sales.length > 0 ? totalRevenue.value / sales.length : 0
})

const totalItems = computed(() => {
  return filteredSales.value.reduce((sum, sale) => sum + sale.items, 0)
})

// Pagination computed properties
const totalPages = computed(() => Math.ceil(filteredSales.value.length / itemsPerPage.value))

const paginatedSales = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredSales.value.slice(start, end)
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const delta = 2
  
  let start = Math.max(1, current - delta)
  let end = Math.min(total, current + delta)
  
  if (end - start < 2 * delta) {
    start = Math.max(1, end - 2 * delta)
    end = Math.min(total, start + 2 * delta)
  }
  
  const pages = []
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

// Filter and sorting functions
const clearFilters = () => {
  searchQuery.value = ''
  startDate.value = ''
  endDate.value = ''
  customerPhone.value = ''
  filters.paymentMethod = ''
  filters.status = ''
  filters.minAmount = null
  filters.maxAmount = null
  currentPage.value = 1
}

const toggleSortDirection = () => {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
}

const statusMap = reactive<Record<string, 'completed' | 'refunded' | 'voided'>>({})
watchEffect(() => {
  for (const s of salesStore.sales) {
    statusMap[s.id] = s.status
  }
})

const updateStatus = async (id: string, status: 'completed' | 'refunded' | 'voided') => {
  await salesStore.updateSaleStatus(id, status)
}

// initial load
salesStore.fetchSales().catch(() => {})
</script>
