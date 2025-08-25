<template>
  <div class="reward-redemptions">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-semibold text-gray-800">Reward Redemptions</h2>
        <p class="text-gray-600">Track and manage customer reward redemptions</p>
      </div>
      <button
        @click="openRedeemModal"
        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors"
      >
        <i class="fas fa-gift mr-2"></i>Manual Redeem
      </button>
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
              placeholder="Search by customer name, email, or reward name"
              class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              @input="debounceSearch"
            />
          </div>
          <div class="text-sm text-gray-500">
            {{ redemptions.total || 0 }} redemptions
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
                @change="fetchRedemptions"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Customers</option>
                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                  {{ customer.name }}
                </option>
              </select>
            </div>
            
            <!-- Reward Filter -->
            <div>
              <select
                v-model="filters.reward_id"
                @change="fetchRedemptions"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Rewards</option>
                <option v-for="reward in rewards" :key="reward.id" :value="reward.id">
                  {{ reward.name }}
                </option>
              </select>
            </div>
            
            <!-- Date Range Filter -->
            <div>
              <select
                v-model="filters.days"
                @change="fetchRedemptions"
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
              @change="fetchRedemptions"
              class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
            >
              <option value="created_at">Date</option>
              <option value="customer_name">Customer</option>
              <option value="points_used">Points Used</option>
              <option value="discount_amount">Discount Amount</option>
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

    <!-- Redemptions Table -->
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
                Reward
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Points Used
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Discount Amount
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Sale
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="redemption in redemptions.data" :key="redemption.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDateTime(redemption.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center text-xs font-medium text-gray-600">
                      {{ getInitials(redemption.customer?.name || 'N/A') }}
                    </div>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">
                      {{ redemption.customer?.name || 'N/A' }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ redemption.customer?.email || '' }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">
                  {{ redemption.loyalty_reward?.name || 'N/A' }}
                </div>
                <div class="text-sm text-gray-500">
                  {{ formatRewardType(redemption.loyalty_reward?.type) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600">
                -{{ redemption.points_used }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                ${{ Number(redemption.discount_amount).toFixed(2) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <span v-if="redemption.sale" class="text-blue-600 hover:text-blue-800 cursor-pointer">
                  #{{ redemption.sale.sale_code || redemption.sale.id }}
                </span>
                <span v-else class="text-gray-400">Manual</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="viewRedemption(redemption)"
                  class="text-blue-600 hover:text-blue-900 mr-3"
                  title="View Details"
                >
                  <i class="fas fa-eye"></i>
                </button>
                <button
                  v-if="canRefund(redemption)"
                  @click="refundRedemption(redemption)"
                  class="text-red-600 hover:text-red-900"
                  title="Refund"
                >
                  <i class="fas fa-undo"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="redemptions.data && redemptions.data.length === 0" class="text-center py-12">
        <i class="fas fa-gift text-gray-400 text-6xl mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No redemptions found</h3>
        <p class="text-gray-500">
          {{ hasActiveFilters ? 'Try adjusting your filters' : 'Reward redemptions will appear here once customers start redeeming rewards' }}
        </p>
      </div>

      <!-- Pagination -->
      <div v-if="redemptions.data && redemptions.data.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <p class="text-sm text-gray-700">
              Showing
              <span class="font-medium">{{ redemptions.from || 0 }}</span>
              to
              <span class="font-medium">{{ redemptions.to || 0 }}</span>
              of
              <span class="font-medium">{{ redemptions.total || 0 }}</span>
              results
            </p>
          </div>
          <div class="flex space-x-2">
            <button
              @click="previousPage"
              :disabled="!redemptions.prev_page_url"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm text-gray-500 hover:bg-gray-50 disabled:opacity-50"
            >
              Previous
            </button>
            <button
              @click="nextPage"
              :disabled="!redemptions.next_page_url"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm text-gray-500 hover:bg-gray-50 disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Manual Redeem Modal -->
    <div v-if="showRedeemModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Manual Reward Redemption</h3>
            <button @click="closeRedeemModal" class="text-gray-500 hover:text-gray-700">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <form @submit.prevent="processRedemption">
            <div class="space-y-4">
              <!-- Customer Selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer *</label>
                <select
                  v-model="redeemForm.customer_id"
                  @change="updateCustomerInfo"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                  <option value="">Select Customer</option>
                  <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                    {{ customer.name }} ({{ customer.loyalty_points || 0 }} points)
                  </option>
                </select>
              </div>

              <!-- Customer Points Display -->
              <div v-if="selectedCustomer" class="bg-blue-50 p-3 rounded-md">
                <div class="text-sm text-blue-800">
                  <strong>{{ selectedCustomer.name }}</strong> has 
                  <strong>{{ selectedCustomer.loyalty_points || 0 }}</strong> loyalty points
                </div>
              </div>

              <!-- Reward Selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reward *</label>
                <select
                  v-model="redeemForm.reward_id"
                  @change="updateRewardInfo"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                  <option value="">Select Reward</option>
                  <option 
                    v-for="reward in availableRewards" 
                    :key="reward.id" 
                    :value="reward.id"
                    :disabled="!reward.is_available || (selectedCustomer && selectedCustomer.loyalty_points < reward.points_required)"
                  >
                    {{ reward.name }} ({{ reward.points_required }} points)
                  </option>
                </select>
              </div>

              <!-- Reward Info Display -->
              <div v-if="selectedReward" class="bg-green-50 p-3 rounded-md">
                <div class="text-sm text-green-800">
                  <div><strong>{{ selectedReward.name }}</strong></div>
                  <div>Requires: {{ selectedReward.points_required }} points</div>
                  <div>Value: {{ formatRewardValue(selectedReward) }}</div>
                </div>
              </div>

              <!-- Insufficient Points Warning -->
              <div v-if="selectedCustomer && selectedReward && selectedCustomer.loyalty_points < selectedReward.points_required" 
                   class="bg-red-50 border border-red-200 rounded-md p-3">
                <div class="text-sm text-red-800">
                  <i class="fas fa-exclamation-triangle mr-2"></i>
                  Customer has insufficient points for this reward.
                </div>
              </div>
            </div>

            <div class="flex items-center justify-end space-x-3 mt-6 pt-6 border-t">
              <button
                type="button"
                @click="closeRedeemModal"
                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading || !canProcessRedemption"
                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors disabled:opacity-50"
              >
                {{ loading ? 'Processing...' : 'Redeem' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- View Details Modal -->
    <div v-if="viewingRedemption" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Redemption Details</h3>
            <button @click="viewingRedemption = null" class="text-gray-500 hover:text-gray-700">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="space-y-4">
            <div>
              <label class="text-sm font-medium text-gray-500">Date & Time</label>
              <div class="text-sm text-gray-900">{{ formatDateTime(viewingRedemption.created_at) }}</div>
            </div>
            
            <div>
              <label class="text-sm font-medium text-gray-500">Customer</label>
              <div class="text-sm text-gray-900">{{ viewingRedemption.customer?.name || 'N/A' }}</div>
            </div>
            
            <div>
              <label class="text-sm font-medium text-gray-500">Reward</label>
              <div class="text-sm text-gray-900">{{ viewingRedemption.loyalty_reward?.name || 'N/A' }}</div>
            </div>
            
            <div>
              <label class="text-sm font-medium text-gray-500">Points Used</label>
              <div class="text-sm font-medium text-red-600">-{{ viewingRedemption.points_used }}</div>
            </div>
            
            <div>
              <label class="text-sm font-medium text-gray-500">Discount Amount</label>
              <div class="text-sm font-medium text-green-600">${{ Number(viewingRedemption.discount_amount).toFixed(2) }}</div>
            </div>
            
            <div v-if="viewingRedemption.sale">
              <label class="text-sm font-medium text-gray-500">Associated Sale</label>
              <div class="text-sm text-blue-600">#{{ viewingRedemption.sale.sale_code || viewingRedemption.sale.id }}</div>
            </div>
          </div>

          <div class="flex items-center justify-end mt-6 pt-6 border-t">
            <button
              @click="viewingRedemption = null"
              class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'RewardRedemptions',
  data() {
    return {
      redemptions: { data: [] },
      customers: [],
      rewards: [],
      showRedeemModal: false,
      viewingRedemption: null,
      loading: false,
      searchQuery: '',
      searchTimeout: null,
      filters: {
        customer_id: '',
        reward_id: '',
        days: '',
        minPoints: null,
        maxPoints: null,
        sort: 'created_at',
        direction: 'desc',
        search: ''
      },
      redeemForm: {
        customer_id: '',
        reward_id: ''
      }
    }
  },
  computed: {
    hasActiveFilters() {
      return this.filters.customer_id || this.filters.reward_id || this.filters.days ||
             this.filters.minPoints !== null || this.filters.maxPoints !== null ||
             this.searchQuery.trim()
    },
    
    selectedCustomer() {
      return this.customers.find(c => c.id == this.redeemForm.customer_id)
    },
    
    selectedReward() {
      return this.rewards.find(r => r.id == this.redeemForm.reward_id)
    },
    
    availableRewards() {
      return this.rewards.filter(reward => reward.is_active)
    },
    
    canProcessRedemption() {
      return this.redeemForm.customer_id && 
             this.redeemForm.reward_id && 
             this.selectedCustomer && 
             this.selectedReward &&
             this.selectedCustomer.loyalty_points >= this.selectedReward.points_required
    }
  },
  mounted() {
    this.fetchRedemptions()
    this.fetchCustomers()
    this.fetchRewards()
  },
  methods: {
    async fetchRedemptions() {
      this.loading = true
      try {
        const params = new URLSearchParams()
        
        Object.keys(this.filters).forEach(key => {
          if (this.filters[key] !== null && this.filters[key] !== '') {
            params.append(key, this.filters[key])
          }
        })

        const response = await fetch(`/api/loyalty/redemptions?${params}`)
        const data = await response.json()
        
        if (response.ok) {
          this.redemptions = data
        } else {
          this.$toast.error(data.error || 'Failed to fetch redemptions')
        }
      } catch (error) {
        console.error('Error fetching redemptions:', error)
        this.$toast.error('Failed to fetch redemptions')
      } finally {
        this.loading = false
      }
    },

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

    async fetchRewards() {
      try {
        const response = await fetch('/api/loyalty/rewards')
        const data = await response.json()
        if (response.ok) {
          this.rewards = data.data || []
        }
      } catch (error) {
        console.error('Error fetching rewards:', error)
      }
    },

    openRedeemModal() {
      this.showRedeemModal = true
      this.redeemForm = { customer_id: '', reward_id: '' }
    },

    closeRedeemModal() {
      this.showRedeemModal = false
      this.redeemForm = { customer_id: '', reward_id: '' }
    },

    updateCustomerInfo() {
      // Trigger reactivity update
      this.$forceUpdate()
    },

    updateRewardInfo() {
      // Trigger reactivity update
      this.$forceUpdate()
    },

    async processRedemption() {
      this.loading = true
      try {
        const response = await fetch('/api/loyalty/redeem', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify(this.redeemForm)
        })
        
        const data = await response.json()
        
        if (response.ok) {
          this.$toast.success(data.message)
          this.closeRedeemModal()
          this.fetchRedemptions()
          this.fetchCustomers() // Refresh to update point balances
        } else {
          this.$toast.error(data.error || 'Failed to process redemption')
        }
      } catch (error) {
        console.error('Error processing redemption:', error)
        this.$toast.error('Failed to process redemption')
      } finally {
        this.loading = false
      }
    },

    viewRedemption(redemption) {
      this.viewingRedemption = redemption
    },

    async refundRedemption(redemption) {
      if (!confirm(`Are you sure you want to refund this redemption? This will restore ${redemption.points_used} points to ${redemption.customer?.name}.`)) {
        return
      }

      try {
        const response = await fetch(`/api/loyalty/redemptions/${redemption.id}/refund`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })
        
        const data = await response.json()
        
        if (response.ok) {
          this.$toast.success(data.message)
          this.fetchRedemptions()
          this.fetchCustomers()
        } else {
          this.$toast.error(data.error || 'Failed to refund redemption')
        }
      } catch (error) {
        console.error('Error refunding redemption:', error)
        this.$toast.error('Failed to refund redemption')
      }
    },

    canRefund(redemption) {
      // Allow refund within 24 hours
      const redemptionDate = new Date(redemption.created_at)
      const now = new Date()
      const hoursSince = (now - redemptionDate) / (1000 * 60 * 60)
      return hoursSince <= 24
    },

    previousPage() {
      if (this.redemptions.prev_page_url) {
        this.fetchPage(this.redemptions.prev_page_url)
      }
    },

    nextPage() {
      if (this.redemptions.next_page_url) {
        this.fetchPage(this.redemptions.next_page_url)
      }
    },

    async fetchPage(url) {
      this.loading = true
      try {
        const response = await fetch(url)
        const data = await response.json()
        
        if (response.ok) {
          this.redemptions = data
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

    formatRewardType(type) {
      if (!type) return ''
      return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
    },

    formatRewardValue(reward) {
      switch (reward.type) {
        case 'percentage_discount':
          return `${reward.discount_value}% off`
        case 'fixed_discount':
          return `$${reward.discount_value} off`
        case 'free_product':
          return 'Free product'
        case 'free_shipping':
          return 'Free shipping'
        default:
          return reward.type.replace('_', ' ')
      }
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
        this.fetchRedemptions()
      }, 500)
    },

    clearFilters() {
      this.searchQuery = ''
      this.filters = {
        customer_id: '',
        reward_id: '',
        days: '',
        minPoints: null,
        maxPoints: null,
        sort: 'created_at',
        direction: 'desc',
        search: ''
      }
      this.fetchRedemptions()
    },

    toggleSortDirection() {
      this.filters.direction = this.filters.direction === 'asc' ? 'desc' : 'asc'
      this.fetchRedemptions()
    }
  }
}
</script>