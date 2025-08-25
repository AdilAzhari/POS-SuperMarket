<template>
  <div class="loyalty-rewards">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-semibold text-gray-800">Loyalty Rewards</h2>
        <p class="text-gray-600">Manage customer loyalty rewards and incentives</p>
      </div>
      <button
        @click="openCreateModal"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
      >
        <i class="fas fa-plus mr-2"></i>Add Reward
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
              placeholder="Search rewards by name or description"
              class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="text-sm text-gray-500">
            {{ filteredRewards.length }} rewards
          </div>
        </div>

        <!-- Filters and Sorting -->
        <div class="flex flex-col lg:flex-row gap-4">
          <!-- Filters -->
          <div class="flex flex-wrap gap-4 items-center">
            <!-- Type Filter -->
            <div>
              <select
                v-model="filters.type"
                class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Types</option>
                <option value="percentage_discount">Percentage Discount</option>
                <option value="fixed_discount">Fixed Discount</option>
                <option value="free_product">Free Product</option>
                <option value="free_shipping">Free Shipping</option>
              </select>
            </div>

            <!-- Status Filter -->
            <div>
              <select
                v-model="filters.status"
                class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
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
                min="0"
              />
              <span class="text-gray-400">-</span>
              <input
                v-model.number="filters.maxPoints"
                type="number"
                placeholder="Max"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                min="0"
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
              <option value="name">Name</option>
              <option value="points_required">Points Required</option>
              <option value="discount_value">Discount Value</option>
              <option value="created_at">Date Created</option>
            </select>
            <button
              @click="toggleSortDirection"
              class="p-2 border rounded-lg hover:bg-gray-50"
              :title="sortDirection === 'asc' ? 'Sort descending' : 'Sort ascending'"
            >
              <i class="fas fa-arrow-up" :class="sortDirection === 'desc' ? 'fa-rotate-180' : ''"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Rewards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="reward in paginatedRewards"
        :key="reward.id"
        class="bg-white rounded-lg shadow-md p-6 border"
        :class="reward.is_active ? 'border-green-200' : 'border-gray-200'"
      >
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-800">{{ reward.name }}</h3>
          <div class="flex space-x-2">
            <button
              @click="editReward(reward)"
              class="text-blue-600 hover:text-blue-800"
            >
              <i class="fas fa-edit"></i>
            </button>
            <button
              @click="deleteReward(reward)"
              class="text-red-600 hover:text-red-800"
            >
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>

        <div class="space-y-2 text-sm text-gray-600">
          <p v-if="reward.description">{{ reward.description }}</p>
          
          <div class="flex items-center">
            <i class="fas fa-coins mr-2 text-yellow-500"></i>
            <span>{{ reward.points_required }} points required</span>
          </div>

          <div class="flex items-center">
            <i class="fas fa-tag mr-2 text-green-500"></i>
            <span>{{ formatRewardValue(reward) }}</span>
          </div>

          <div v-if="reward.max_uses" class="flex items-center">
            <i class="fas fa-limit mr-2 text-orange-500"></i>
            <span>{{ reward.uses_count || 0 }}/{{ reward.max_uses }} uses</span>
          </div>

          <div v-if="reward.valid_until" class="flex items-center">
            <i class="fas fa-calendar mr-2 text-blue-500"></i>
            <span>Valid until {{ formatDate(reward.valid_until) }}</span>
          </div>
        </div>

        <div class="mt-4 flex items-center justify-between">
          <span
            class="px-3 py-1 rounded-full text-xs font-medium"
            :class="reward.is_active 
              ? 'bg-green-100 text-green-800' 
              : 'bg-gray-100 text-gray-800'"
          >
            {{ reward.is_active ? 'Active' : 'Inactive' }}
          </span>
          
          <span class="text-xs text-gray-500 capitalize">
            {{ reward.type.replace('_', ' ') }}
          </span>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="mt-6 flex items-center justify-between">
      <div class="text-sm text-gray-500">
        Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredRewards.length) }} of {{ filteredRewards.length }} rewards
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

    <!-- Empty State -->
    <div v-if="filteredRewards.length === 0" class="text-center py-12">
      <i class="fas fa-gift text-gray-400 text-6xl mb-4"></i>
      <h3 class="text-lg font-medium text-gray-900 mb-2">
        {{ rewards.length === 0 ? 'No rewards yet' : 'No rewards match your search' }}
      </h3>
      <p class="text-gray-500 mb-4">
        {{ rewards.length === 0 ? 'Create your first loyalty reward to get started' : 'Try adjusting your search criteria' }}
      </p>
      <button
        v-if="rewards.length === 0"
        @click="openCreateModal"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
      >
        Create Reward
      </button>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">
              {{ editingReward ? 'Edit Reward' : 'Create New Reward' }}
            </h3>
            <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <form @submit.prevent="saveReward">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Name -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input
                  v-model="rewardForm.name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="e.g., 10% Off Next Purchase"
                >
              </div>

              <!-- Description -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea
                  v-model="rewardForm.description"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Describe this reward..."
                ></textarea>
              </div>

              <!-- Type -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reward Type *</label>
                <select
                  v-model="rewardForm.type"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="percentage_discount">Percentage Discount</option>
                  <option value="fixed_discount">Fixed Amount Discount</option>
                  <option value="free_product">Free Product</option>
                  <option value="free_shipping">Free Shipping</option>
                </select>
              </div>

              <!-- Discount Value -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  {{ rewardForm.type === 'percentage_discount' ? 'Discount %' : 'Discount Amount' }} *
                </label>
                <input
                  v-model.number="rewardForm.discount_value"
                  type="number"
                  :min="0"
                  :max="rewardForm.type === 'percentage_discount' ? 100 : null"
                  step="0.01"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
              </div>

              <!-- Points Required -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Points Required *</label>
                <input
                  v-model.number="rewardForm.points_required"
                  type="number"
                  min="1"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
              </div>

              <!-- Max Uses -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max Uses (optional)</label>
                <input
                  v-model.number="rewardForm.max_uses"
                  type="number"
                  min="1"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Unlimited if empty"
                >
              </div>

              <!-- Valid From -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valid From</label>
                <input
                  v-model="rewardForm.valid_from"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
              </div>

              <!-- Valid Until -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valid Until</label>
                <input
                  v-model="rewardForm.valid_until"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
              </div>

              <!-- Active Status -->
              <div class="md:col-span-2">
                <label class="flex items-center">
                  <input
                    v-model="rewardForm.is_active"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                  >
                  <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
              </div>
            </div>

            <div class="flex items-center justify-end space-x-3 mt-6 pt-6 border-t">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
              >
                {{ loading ? 'Saving...' : (editingReward ? 'Update' : 'Create') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LoyaltyRewards',
  data() {
    return {
      rewards: [],
      showModal: false,
      editingReward: null,
      loading: false,
      // Search and filter state
      searchQuery: '',
      filters: {
        type: '',
        status: '',
        minPoints: null,
        maxPoints: null
      },
      // Sorting state
      sortBy: 'name',
      sortDirection: 'asc',
      // Pagination state
      currentPage: 1,
      itemsPerPage: 12,
      rewardForm: {
        name: '',
        description: '',
        type: 'percentage_discount',
        discount_value: 0,
        points_required: 100,
        max_uses: null,
        valid_from: '',
        valid_until: '',
        is_active: true
      }
    }
  },
  computed: {
    hasActiveFilters() {
      return this.filters.type || this.filters.status || this.filters.minPoints !== null || this.filters.maxPoints !== null
    },
    
    filteredRewards() {
      let filtered = this.rewards

      // Apply search
      if (this.searchQuery.trim()) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(reward => 
          reward.name.toLowerCase().includes(query) ||
          (reward.description && reward.description.toLowerCase().includes(query))
        )
      }

      // Apply filters
      if (this.filters.type) {
        filtered = filtered.filter(reward => reward.type === this.filters.type)
      }

      if (this.filters.status) {
        const isActive = this.filters.status === 'active'
        filtered = filtered.filter(reward => reward.is_active === isActive)
      }

      if (this.filters.minPoints !== null && this.filters.minPoints !== '') {
        filtered = filtered.filter(reward => reward.points_required >= Number(this.filters.minPoints))
      }

      if (this.filters.maxPoints !== null && this.filters.maxPoints !== '') {
        filtered = filtered.filter(reward => reward.points_required <= Number(this.filters.maxPoints))
      }

      // Apply sorting
      filtered = [...filtered].sort((a, b) => {
        let aValue, bValue

        switch (this.sortBy) {
          case 'name':
            aValue = a.name.toLowerCase()
            bValue = b.name.toLowerCase()
            break
          case 'points_required':
            aValue = Number(a.points_required)
            bValue = Number(b.points_required)
            break
          case 'discount_value':
            aValue = Number(a.discount_value)
            bValue = Number(b.discount_value)
            break
          case 'created_at':
            aValue = new Date(a.created_at || 0)
            bValue = new Date(b.created_at || 0)
            break
          default:
            return 0
        }

        if (this.sortDirection === 'asc') {
          return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
        } else {
          return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
        }
      })

      return filtered
    },

    totalPages() {
      return Math.ceil(this.filteredRewards.length / this.itemsPerPage)
    },

    paginatedRewards() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredRewards.slice(start, end)
    },

    visiblePages() {
      const total = this.totalPages
      const current = this.currentPage
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
    }
  },
  
  mounted() {
    this.fetchRewards()
  },
  methods: {
    async fetchRewards() {
      try {
        const response = await fetch('/api/loyalty/rewards')
        const data = await response.json()
        if (response.ok) {
          this.rewards = data.data
        } else {
          this.$toast.error(data.error || 'Failed to fetch rewards')
        }
      } catch (error) {
        console.error('Error fetching rewards:', error)
        this.$toast.error('Failed to fetch rewards')
      }
    },
    
    openCreateModal() {
      this.editingReward = null
      this.resetForm()
      this.showModal = true
    },
    
    editReward(reward) {
      this.editingReward = reward
      this.rewardForm = { ...reward }
      this.showModal = true
    },
    
    closeModal() {
      this.showModal = false
      this.editingReward = null
      this.resetForm()
    },
    
    resetForm() {
      this.rewardForm = {
        name: '',
        description: '',
        type: 'percentage_discount',
        discount_value: 0,
        points_required: 100,
        max_uses: null,
        valid_from: '',
        valid_until: '',
        is_active: true
      }
    },
    
    async saveReward() {
      this.loading = true
      try {
        const url = this.editingReward 
          ? `/api/loyalty/rewards/${this.editingReward.id}`
          : '/api/loyalty/rewards'
        
        const method = this.editingReward ? 'PUT' : 'POST'
        
        const response = await fetch(url, {
          method,
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify(this.rewardForm)
        })
        
        const data = await response.json()
        
        if (response.ok) {
          this.$toast.success(data.message)
          this.closeModal()
          this.fetchRewards()
        } else {
          this.$toast.error(data.error || 'Failed to save reward')
        }
      } catch (error) {
        console.error('Error saving reward:', error)
        this.$toast.error('Failed to save reward')
      } finally {
        this.loading = false
      }
    },
    
    async deleteReward(reward) {
      if (!confirm(`Are you sure you want to delete "${reward.name}"?`)) {
        return
      }
      
      try {
        const response = await fetch(`/api/loyalty/rewards/${reward.id}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })
        
        const data = await response.json()
        
        if (response.ok) {
          this.$toast.success(data.message)
          this.fetchRewards()
        } else {
          this.$toast.error(data.error || 'Failed to delete reward')
        }
      } catch (error) {
        console.error('Error deleting reward:', error)
        this.$toast.error('Failed to delete reward')
      }
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
    
    formatDate(date) {
      return new Date(date).toLocaleDateString()
    },
    
    // Filter and sorting methods
    clearFilters() {
      this.filters.type = ''
      this.filters.status = ''
      this.filters.minPoints = null
      this.filters.maxPoints = null
      this.currentPage = 1
    },
    
    toggleSortDirection() {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
    }
  }
}
</script>