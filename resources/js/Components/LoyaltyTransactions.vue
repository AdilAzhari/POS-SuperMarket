<template>
  <div class="loyalty-transactions">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-semibold text-gray-800">Loyalty Transactions</h2>
        <p class="text-gray-600">Track customer loyalty points activity</p>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
      <div class="space-y-4">
        <!-- Search Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="relative flex-1">
            <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
            <input
              v-model="searchQuery"
              placeholder="Search by customer name, email, or description"
              class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              @input="debounceSearch"
            />
          </div>
          <div class="text-sm text-gray-500">
            {{ transactions.total || 0 }} transactions
          </div>
        </div>

        <!-- Filters and Sorting -->
        <div class="flex flex-col lg:flex-row gap-4">
          <!-- Filters -->
          <div class="flex flex-wrap gap-4 items-center">
            <!-- Customer Filter -->
            <div>
              <select
                v-model="filters.customer_id"
                @change="fetchTransactions"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Customers</option>
                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                  {{ customer.name }}
                </option>
              </select>
            </div>
            
            <!-- Type Filter -->
            <div>
              <select
                v-model="filters.type"
                @change="fetchTransactions"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Types</option>
                <option value="earned">Earned</option>
                <option value="redeemed">Redeemed</option>
                <option value="adjustment">Adjustment</option>
                <option value="expired">Expired</option>
              </select>
            </div>
            
            <!-- Date Range Filter -->
            <div>
              <select
                v-model="filters.days"
                @change="fetchTransactions"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Time</option>
                <option value="7">Last 7 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
              </select>
            </div>

            <!-- Points Range -->
            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-600">Points:</span>
              <input
                v-model.number="filters.minPoints"
                type="number"
                placeholder="Min"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                @input="debounceSearch"
              />
              <span class="text-gray-400">-</span>
              <input
                v-model.number="filters.maxPoints"
                type="number"
                placeholder="Max"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                @input="debounceSearch"
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
              v-model="filters.sort"
              @change="fetchTransactions"
              class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
            >
              <option value="created_at">Date</option>
              <option value="customer_name">Customer</option>
              <option value="points">Points</option>
              <option value="type">Type</option>
            </select>
            <button
              @click="toggleSortDirection"
              class="p-2 border rounded-lg hover:bg-gray-50"
              :title="filters.direction === 'asc' ? 'Sort descending' : 'Sort ascending'"
            >
              <i class="fas fa-arrow-up" :class="filters.direction === 'desc' ? 'fa-rotate-180' : ''"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date & Time
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Customer
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Type
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Points
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Description
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Expires
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="transaction in transactions.data" :key="transaction.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDateTime(transaction.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center text-xs font-medium text-gray-600">
                      {{ getInitials(transaction.customer?.name || 'N/A') }}
                    </div>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">
                      {{ transaction.customer?.name || 'N/A' }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ transaction.customer?.email || '' }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="px-2 py-1 text-xs font-medium rounded-full"
                  :class="getTypeClass(transaction.type)"
                >
                  {{ formatType(transaction.type) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                  :class="transaction.points >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ transaction.points >= 0 ? '+' : '' }}{{ transaction.points }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                {{ transaction.description }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <span v-if="transaction.expires_at">
                  {{ formatDate(transaction.expires_at) }}
                </span>
                <span v-else class="text-gray-400">Never</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="transactions.data && transactions.data.length === 0" class="text-center py-12">
        <i class="fas fa-exchange-alt text-gray-400 text-6xl mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No transactions found</h3>
        <p class="text-gray-500">
          {{ hasActiveFilters ? 'Try adjusting your filters' : 'Transactions will appear here once customers start earning or redeeming points' }}
        </p>
      </div>

      <!-- Pagination -->
      <div v-if="transactions.data && transactions.data.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <p class="text-sm text-gray-700">
              Showing
              <span class="font-medium">{{ transactions.from || 0 }}</span>
              to
              <span class="font-medium">{{ transactions.to || 0 }}</span>
              of
              <span class="font-medium">{{ transactions.total || 0 }}</span>
              results
            </p>
          </div>
          <div class="flex space-x-2">
            <button
              @click="previousPage"
              :disabled="!transactions.prev_page_url"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm text-gray-500 hover:bg-gray-50 disabled:opacity-50"
            >
              Previous
            </button>
            <button
              @click="nextPage"
              :disabled="!transactions.next_page_url"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm text-gray-500 hover:bg-gray-50 disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Transaction Summary -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <i class="fas fa-plus-circle text-green-500 text-2xl"></i>
          </div>
          <div class="ml-3">
            <div class="text-sm font-medium text-gray-500">Points Earned</div>
            <div class="text-2xl font-semibold text-green-600">
              +{{ summary.earned || 0 }}
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <i class="fas fa-minus-circle text-red-500 text-2xl"></i>
          </div>
          <div class="ml-3">
            <div class="text-sm font-medium text-gray-500">Points Redeemed</div>
            <div class="text-2xl font-semibold text-red-600">
              {{ summary.redeemed || 0 }}
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <i class="fas fa-clock text-orange-500 text-2xl"></i>
          </div>
          <div class="ml-3">
            <div class="text-sm font-medium text-gray-500">Points Expired</div>
            <div class="text-2xl font-semibold text-orange-600">
              {{ summary.expired || 0 }}
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <i class="fas fa-balance-scale text-blue-500 text-2xl"></i>
          </div>
          <div class="ml-3">
            <div class="text-sm font-medium text-gray-500">Net Points</div>
            <div class="text-2xl font-semibold text-blue-600">
              {{ (summary.earned || 0) + (summary.redeemed || 0) + (summary.expired || 0) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LoyaltyTransactions',
  props: {
    customerId: {
      type: [Number, String],
      default: null
    }
  },
  data() {
    return {
      transactions: { data: [] },
      customers: [],
      summary: {},
      searchQuery: '',
      searchTimeout: null,
      filters: {
        customer_id: this.customerId || '',
        type: '',
        days: '',
        minPoints: null,
        maxPoints: null,
        sort: 'created_at',
        direction: 'desc',
        search: ''
      },
      loading: false
    }
  },
  computed: {
    hasActiveFilters() {
      return this.filters.customer_id || this.filters.type || this.filters.days || 
             this.filters.minPoints !== null || this.filters.maxPoints !== null || 
             this.searchQuery.trim()
    }
  },
  mounted() {
    this.fetchCustomers()
    this.fetchTransactions()
    this.fetchSummary()
  },
  methods: {
    async fetchCustomers() {
      try {
        const response = await fetch('/api/customers?per_page=100')
        const data = await response.json()
        if (response.ok) {
          this.customers = data.data || []
        }
      } catch (error) {
        console.error('Error fetching customers:', error)
      }
    },

    async fetchTransactions() {
      this.loading = true
      try {
        const params = new URLSearchParams()
        
        Object.keys(this.filters).forEach(key => {
          if (this.filters[key] !== null && this.filters[key] !== '') {
            params.append(key, this.filters[key])
          }
        })

        const url = this.customerId 
          ? `/api/loyalty/customers/${this.customerId}/transactions?${params}`
          : `/api/loyalty/transactions?${params}`

        const response = await fetch(url)
        const data = await response.json()
        
        if (response.ok) {
          this.transactions = data
        } else {
          this.$toast.error(data.error || 'Failed to fetch transactions')
        }
      } catch (error) {
        console.error('Error fetching transactions:', error)
        this.$toast.error('Failed to fetch transactions')
      } finally {
        this.loading = false
      }
    },

    async fetchSummary() {
      try {
        const params = new URLSearchParams(this.filters)
        const response = await fetch(`/api/loyalty/transactions/summary?${params}`)
        const data = await response.json()
        
        if (response.ok) {
          this.summary = data.data || {}
        }
      } catch (error) {
        console.error('Error fetching summary:', error)
      }
    },

    previousPage() {
      if (this.transactions.prev_page_url) {
        this.fetchPage(this.transactions.prev_page_url)
      }
    },

    nextPage() {
      if (this.transactions.next_page_url) {
        this.fetchPage(this.transactions.next_page_url)
      }
    },

    async fetchPage(url) {
      this.loading = true
      try {
        const response = await fetch(url)
        const data = await response.json()
        
        if (response.ok) {
          this.transactions = data
        }
      } catch (error) {
        console.error('Error fetching page:', error)
      } finally {
        this.loading = false
      }
    },

    formatDateTime(datetime) {
      return new Date(datetime).toLocaleString()
    },

    formatDate(date) {
      return new Date(date).toLocaleDateString()
    },

    formatType(type) {
      return type.charAt(0).toUpperCase() + type.slice(1)
    },

    getTypeClass(type) {
      const classes = {
        earned: 'bg-green-100 text-green-800',
        redeemed: 'bg-red-100 text-red-800',
        adjustment: 'bg-blue-100 text-blue-800',
        expired: 'bg-orange-100 text-orange-800'
      }
      return classes[type] || 'bg-gray-100 text-gray-800'
    },

    getInitials(name) {
      return name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2)
    },

    // New methods for enhanced filtering and search
    debounceSearch() {
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout)
      }
      this.searchTimeout = setTimeout(() => {
        this.filters.search = this.searchQuery
        this.fetchTransactions()
        this.fetchSummary()
      }, 500)
    },

    clearFilters() {
      this.searchQuery = ''
      this.filters = {
        customer_id: this.customerId || '',
        type: '',
        days: '',
        minPoints: null,
        maxPoints: null,
        sort: 'created_at',
        direction: 'desc',
        search: ''
      }
      this.fetchTransactions()
      this.fetchSummary()
    },

    toggleSortDirection() {
      this.filters.direction = this.filters.direction === 'asc' ? 'desc' : 'asc'
      this.fetchTransactions()
    }
  },
  watch: {
    customerId(newVal) {
      this.filters.customer_id = newVal || ''
      this.fetchTransactions()
      this.fetchSummary()
    }
  }
}
</script>