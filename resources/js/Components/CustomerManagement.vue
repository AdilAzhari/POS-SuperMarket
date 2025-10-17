<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Customers</h2>
        <p class="text-gray-600">Manage customer records and details</p>
      </div>
      <button
        class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
        @click="openAdd"
      >
        <UserPlus class="w-4 h-4 mr-2" />
        Add Customer
      </button>
    </div>

    <div v-if="flashMessage" class="rounded-md p-3" :class="flashClass">
      {{ flashMessage }}
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4">
      <div class="space-y-4">
        <!-- Search Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="relative flex-1">
            <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by name, phone, email"
              class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div class="text-sm text-gray-500">
            {{ filteredCustomers.length }} customers
          </div>
        </div>

        <!-- Filters and Sorting -->
        <div class="flex flex-col lg:flex-row gap-4">
          <!-- Filters -->
          <div class="flex flex-wrap gap-4 items-center">
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

            <!-- Loyalty Tier Filter -->
            <div>
              <select
                v-model="filters.loyaltyTier"
                class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Tiers</option>
                <option value="bronze">Bronze</option>
                <option value="silver">Silver</option>
                <option value="gold">Gold</option>
                <option value="platinum">Platinum</option>
              </select>
            </div>

            <!-- Purchase Count Range -->
            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-600">Purchases:</span>
              <input
                v-model.number="filters.minPurchases"
                type="number"
                placeholder="Min"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                min="0"
              />
              <span class="text-gray-400">-</span>
              <input
                v-model.number="filters.maxPurchases"
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
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Sort by:</span>
            <select
              v-model="sortBy"
              class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
            >
              <option value="name">Name</option>
              <option value="totalPurchases">Purchase Count</option>
              <option value="totalSpent">Total Spent</option>
              <option value="created_at">Date Added</option>
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

      <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-600">
            <tr>
              <th class="text-left px-4 py-2 font-medium">Name</th>
              <th class="text-left px-4 py-2 font-medium">Phone</th>
              <th class="text-left px-4 py-2 font-medium">Email</th>
              <th class="text-right px-4 py-2 font-medium">Purchases</th>
              <th class="text-right px-4 py-2 font-medium">Total Spent</th>
              <th class="text-center px-4 py-2 font-medium">Loyalty Tier</th>
              <th class="text-right px-4 py-2 font-medium">Points</th>
              <th class="text-left px-4 py-2 font-medium">Status</th>
              <th class="px-4 py-2 font-medium text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in paginatedCustomers" :key="c.id" class="border-t hover:bg-gray-50 cursor-pointer" @click="viewCustomerDetails(c)">
              <td class="px-4 py-2 font-medium text-gray-900">
                {{ c.name }}
              </td>
              <td class="px-4 py-2">{{ c.phone }}</td>
              <td class="px-4 py-2">{{ c.email }}</td>
              <td class="px-4 py-2 text-right">
                {{ c.totalPurchases ?? 0 }}
              </td>
              <td class="px-4 py-2 text-right">RM {{ (c.totalSpent ?? 0).toFixed(2) }}</td>
              <td class="px-4 py-2 text-center">
                <span
                  :class="getLoyaltyTierBadgeClass(c.loyalty_tier || c.loyaltyTier)"
                  class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full"
                >
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                  {{ formatLoyaltyTier(c.loyalty_tier || c.loyaltyTier) }}
                </span>
              </td>
              <td class="px-4 py-2 text-right font-medium text-purple-600">
                {{ c.loyalty_points || c.loyaltyPoints || 0 }} pts
              </td>
              <td class="px-4 py-2">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                    c.status === 'active'
                      ? 'bg-green-100 text-green-800'
                      : 'bg-gray-100 text-gray-700',
                  ]"
                >
                  {{ c.status ?? 'active' }}
                </span>
              </td>
              <td class="px-4 py-2" @click.stop>
                <div class="flex justify-end gap-2">
                  <button class="text-blue-600 hover:text-blue-800" @click="openEdit(c)" title="Edit">
                    <Edit class="w-4 h-4" />
                  </button>
                  <button class="text-red-600 hover:text-red-800" @click="remove(c.id)" title="Delete">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredCustomers.length === 0">
              <td colspan="7" class="px-4 py-8 text-center text-gray-500">No customers found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Enhanced Pagination -->
      <div v-if="totalPages > 1" class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
          <!-- Items Info and Per Page Selector -->
          <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
            <div class="text-sm text-gray-600">
              Showing <span class="font-medium">{{ (currentPage - 1) * itemsPerPage + 1 }}</span>
              to <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, filteredCustomers.length) }}</span>
              of <span class="font-medium">{{ filteredCustomers.length }}</span> customers
            </div>
            <div class="flex items-center space-x-2">
              <label class="text-sm text-gray-600">Show:</label>
              <select
                v-model="itemsPerPage"
                @change="currentPage = 1"
                class="px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span class="text-sm text-gray-600">per page</span>
            </div>
          </div>

          <!-- Pagination Controls -->
          <div class="flex items-center justify-center space-x-1">
            <!-- First Page -->
            <button
              @click="currentPage = 1"
              :disabled="currentPage === 1"
              class="px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              title="First page"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M21 19l-7-7 7-7"/>
              </svg>
            </button>

            <!-- Previous Page -->
            <button
              @click="currentPage = Math.max(1, currentPage - 1)"
              :disabled="currentPage === 1"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border-t border-b border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>

            <!-- Page Numbers -->
            <div class="flex space-x-0">
              <button
                v-for="page in visiblePages"
                :key="page"
                @click="currentPage = page"
                :class="[
                  'px-3 py-2 text-sm font-medium border-t border-b border-gray-300 transition-colors',
                  page === currentPage
                    ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                    : 'bg-white text-gray-700 hover:bg-gray-50'
                ]"
              >
                {{ page }}
              </button>
            </div>

            <!-- Next Page -->
            <button
              @click="currentPage = Math.min(totalPages, currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border-t border-b border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </button>

            <!-- Last Page -->
            <button
              @click="currentPage = totalPages"
              :disabled="currentPage === totalPages"
              class="px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              title="Last page"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M3 5l7 7-7 7"/>
              </svg>
            </button>
          </div>

          <!-- Jump to Page -->
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-600">Go to:</span>
            <input
              v-model.number="jumpToPage"
              @keyup.enter="goToPage"
              type="number"
              :min="1"
              :max="totalPages"
              class="w-16 px-2 py-1 text-sm border border-gray-300 rounded bg-white text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Page"
            />
            <button
              @click="goToPage"
              class="px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700 transition-colors"
            >
              Go
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">
            {{ isEditing ? 'Edit Customer' : 'Add Customer' }}
          </h3>
          <button @click="showModal = false">
            <X class="w-5 h-5" />
          </button>
        </div>
        <form class="space-y-4" @submit.prevent="submit">
          <input
            v-model="form.name"
            required
            placeholder="Full Name"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <input
            v-model="form.phone"
            required
            placeholder="Phone"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <input
            v-model="form.email"
            type="email"
            required
            placeholder="Email"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <textarea
            v-model="form.address"
            rows="3"
            placeholder="Address (optional)"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          ></textarea>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="px-4 py-2 border rounded-lg" @click="showModal = false">
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              {{ isEditing ? 'Save Changes' : 'Add Customer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Customer Details Modal -->
  <div
    v-if="showDetailsModal && selectedCustomer"
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    @click.self="closeDetailsModal"
  >
    <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-start mb-4">
        <div>
          <h3 class="text-xl font-bold text-gray-900">Customer Details</h3>
          <p class="text-sm text-gray-500 mt-1">{{ selectedCustomer.name }}</p>
        </div>
        <button
          @click="closeDetailsModal"
          class="text-gray-400 hover:text-gray-600"
        >
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Customer Profile Header -->
      <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div class="h-16 w-16 rounded-full bg-white flex items-center justify-center">
              <span class="text-purple-600 font-bold text-2xl">
                {{ getInitials(selectedCustomer.name) }}
              </span>
            </div>
            <div class="text-white">
              <h2 class="text-2xl font-bold">{{ selectedCustomer.name }}</h2>
              <p class="text-purple-100">{{ selectedCustomer.email }}</p>
              <p class="text-sm text-purple-100 mt-1">{{ selectedCustomer.phone }}</p>
            </div>
          </div>
          <div class="text-right">
            <span
              :class="getLoyaltyTierBadgeClass(selectedCustomer.loyalty_tier || selectedCustomer.loyaltyTier)"
              class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-full"
            >
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
              {{ formatLoyaltyTier(selectedCustomer.loyalty_tier || selectedCustomer.loyaltyTier) }} Member
            </span>
          </div>
        </div>
      </div>

      <!-- Loyalty Points & Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-purple-50 p-4 rounded-lg text-center">
          <div class="text-3xl font-bold text-purple-600">{{ selectedCustomer.loyalty_points || selectedCustomer.loyaltyPoints || 0 }}</div>
          <div class="text-sm text-gray-600 mt-1">Loyalty Points</div>
        </div>
        <div class="bg-blue-50 p-4 rounded-lg text-center">
          <div class="text-3xl font-bold text-blue-600">{{ selectedCustomer.totalPurchases || 0 }}</div>
          <div class="text-sm text-gray-600 mt-1">Total Purchases</div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg text-center">
          <div class="text-3xl font-bold text-green-600">RM {{ (selectedCustomer.totalSpent || 0).toFixed(2) }}</div>
          <div class="text-sm text-gray-600 mt-1">Total Spent</div>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg text-center">
          <div class="text-3xl font-bold text-yellow-600">{{ selectedCustomer.status === 'active' ? 'Active' : 'Inactive' }}</div>
          <div class="text-sm text-gray-600 mt-1">Account Status</div>
        </div>
      </div>

      <!-- Customer Information -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gray-50 p-4 rounded-lg">
          <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Contact Information</h4>
          <div class="space-y-2">
            <div>
              <span class="text-xs text-gray-500">Email:</span>
              <p class="text-sm font-medium">{{ selectedCustomer.email }}</p>
            </div>
            <div>
              <span class="text-xs text-gray-500">Phone:</span>
              <p class="text-sm font-medium">{{ selectedCustomer.phone }}</p>
            </div>
            <div v-if="selectedCustomer.address">
              <span class="text-xs text-gray-500">Address:</span>
              <p class="text-sm font-medium">{{ selectedCustomer.address }}</p>
            </div>
          </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
          <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Loyalty Benefits</h4>
          <div class="space-y-2">
            <div>
              <span class="text-xs text-gray-500">Points Multiplier:</span>
              <p class="text-sm font-medium">{{ getLoyaltyMultiplier(selectedCustomer.loyalty_tier || selectedCustomer.loyaltyTier) }}x</p>
            </div>
            <div>
              <span class="text-xs text-gray-500">Discount Rate:</span>
              <p class="text-sm font-medium">{{ getLoyaltyDiscount(selectedCustomer.loyalty_tier || selectedCustomer.loyaltyTier) }}%</p>
            </div>
            <div>
              <span class="text-xs text-gray-500">Next Tier:</span>
              <p class="text-sm font-medium">{{ getNextTier(selectedCustomer.loyalty_tier || selectedCustomer.loyaltyTier) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Loyalty Transaction History -->
      <div v-if="loyaltyTransactions.length > 0" class="mb-6">
        <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Recent Loyalty Activity</h4>
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Points</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="transaction in loyaltyTransactions.slice(0, 10)" :key="transaction.id">
                <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(transaction.created_at) }}</td>
                <td class="px-4 py-3 text-sm">
                  <span
                    :class="getTransactionTypeBadge(transaction.type)"
                    class="px-2 py-1 text-xs font-medium rounded-full"
                  >
                    {{ formatTransactionType(transaction.type) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ transaction.description }}</td>
                <td class="px-4 py-3 text-sm text-right font-semibold" :class="transaction.points >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ transaction.points >= 0 ? '+' : '' }}{{ transaction.points }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="loyaltyTransactions.length > 10" class="text-xs text-gray-500 text-center mt-2">
          Showing 10 of {{ loyaltyTransactions.length }} transactions
        </p>
      </div>
      <div v-else class="mb-6 p-8 text-center bg-gray-50 rounded-lg">
        <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-gray-500 text-sm">No loyalty transactions yet</p>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end space-x-3 pt-4 border-t">
        <button
          @click="closeDetailsModal"
          class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          Close
        </button>
        <button
          @click="openEdit(selectedCustomer); closeDetailsModal()"
          class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700"
        >
          Edit Customer
        </button>
      </div>
    </div>
  </div>

  <!-- Modal Component -->
  <Modal
    :show="modal.isVisible.value"
    :title="modal.modalData.title"
    :message="modal.modalData.message"
    :type="modal.modalData.type"
    :size="modal.modalData.size"
    :show-cancel-button="modal.modalData.showCancelButton"
    :confirm-text="modal.modalData.confirmText"
    :cancel-text="modal.modalData.cancelText"
    @close="modal.hide"
    @confirm="modal.confirm"
    @cancel="modal.cancel"
  />
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useCustomersStore } from '@/stores/customers.js'
import { useMessageModal } from '@/composables/useModal.js'
import Modal from '@/Components/Modal.vue'
import { Edit, Trash2, UserPlus, Search, X, ArrowUpDown } from 'lucide-vue-next'

const customersStore = useCustomersStore()
const modal = useMessageModal()

// Search and filters
const searchQuery = ref('')
const filters = reactive({
  status: '',
  minPurchases: null,
  maxPurchases: null,
  loyaltyTier: ''
})

// Customer details modal
const showDetailsModal = ref(false)
const selectedCustomer = ref(null)
const loyaltyTransactions = ref([])

// Sorting
const sortBy = ref('name')
const sortDirection = ref('asc')

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(20)
const jumpToPage = ref(null)

// Computed properties
const hasActiveFilters = computed(() => {
  return filters.status || filters.minPurchases !== null || filters.maxPurchases !== null || filters.loyaltyTier
})

const filteredCustomers = computed(() => {
  let customers = customersStore.searchCustomers(searchQuery.value)

  // Apply filters
  if (filters.status) {
    customers = customers.filter(c => (c.status || 'active') === filters.status)
  }

  if (filters.loyaltyTier) {
    customers = customers.filter(c => {
      const tier = (c.loyalty_tier || c.loyaltyTier || 'bronze').toLowerCase()
      return tier === filters.loyaltyTier.toLowerCase()
    })
  }

  if (filters.minPurchases !== null) {
    customers = customers.filter(c => (c.totalPurchases || 0) >= filters.minPurchases)
  }

  if (filters.maxPurchases !== null) {
    customers = customers.filter(c => (c.totalPurchases || 0) <= filters.maxPurchases)
  }
  
  // Apply sorting
  customers.sort((a, b) => {
    let aValue = a[sortBy.value]
    let bValue = b[sortBy.value]
    
    // Handle null/undefined values
    if (aValue == null) aValue = 0
    if (bValue == null) bValue = 0
    
    // Convert to strings for text comparison
    if (typeof aValue === 'string') {
      aValue = aValue.toLowerCase()
      bValue = bValue.toLowerCase()
    }
    
    if (sortDirection.value === 'asc') {
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
    } else {
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
    }
  })
  
  return customers
})

const totalPages = computed(() => Math.ceil(filteredCustomers.value.length / itemsPerPage.value))

const paginatedCustomers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredCustomers.value.slice(start, end)
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

const showModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const form = reactive({
  name: '',
  phone: '',
  email: '',
  address: '',
})

const resetForm = () => {
  form.name = ''
  form.phone = ''
  form.email = ''
  form.address = ''
}

const openAdd = () => {
  isEditing.value = false
  editingId.value = null
  resetForm()
  showModal.value = true
}

const openEdit = c => {
  isEditing.value = true
  editingId.value = c.id
  form.name = c.name
  form.phone = c.phone
  form.email = c.email
  form.address = c.address ?? ''
  showModal.value = true
}

const flashMessage = ref('')
const flashType = ref('success')
const flashClass = computed(() =>
  flashType.value === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
)
let flashTimer
const notify = (msg, type = 'success') => {
  flashMessage.value = msg
  flashType.value = type
  if (flashTimer) window.clearTimeout(flashTimer)
  flashTimer = window.setTimeout(() => (flashMessage.value = ''), 3000)
}

const submit = async () => {
  try {
    // Validate required fields
    if (!form.name?.trim()) {
      await modal.showError('Customer name is required')
      return
    }
    if (!form.phone?.trim()) {
      await modal.showError('Phone number is required')
      return
    }
    if (!form.email?.trim()) {
      await modal.showError('Email address is required')
      return
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(form.email.trim())) {
      await modal.showError('Please enter a valid email address')
      return
    }
    
    if (isEditing.value && editingId.value) {
      await customersStore.updateCustomer(editingId.value, {
        name: form.name.trim(),
        phone: form.phone.trim(),
        email: form.email.trim(),
        address: form.address?.trim() || ''
      })
      await modal.showSuccess('Customer updated successfully')
    } else {
      await customersStore.addCustomer({
        name: form.name.trim(),
        phone: form.phone.trim(),
        email: form.email.trim(),
        address: form.address?.trim() || '',
      })
      await modal.showSuccess('Customer added successfully')
    }
    showModal.value = false
    resetForm()
  } catch (e) {
    const errorMessage = e?.response?.data?.message || e?.message || 'Operation failed'
    await modal.showError(errorMessage)
  }
}

// Filter and sorting functions
const clearFilters = () => {
  filters.status = ''
  filters.loyaltyTier = ''
  filters.minPurchases = null
  filters.maxPurchases = null
  currentPage.value = 1
}

const toggleSortDirection = () => {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
}

const goToPage = () => {
  if (jumpToPage.value && jumpToPage.value >= 1 && jumpToPage.value <= totalPages.value) {
    currentPage.value = jumpToPage.value
    jumpToPage.value = null
  }
}

// Customer details modal functions
const viewCustomerDetails = async (customer) => {
  selectedCustomer.value = customer
  showDetailsModal.value = true

  // Fetch loyalty transactions for this customer
  try {
    const response = await fetch(`/api/customers/${customer.id}/loyalty-transactions`)
    if (response.ok) {
      const data = await response.json()
      loyaltyTransactions.value = data.data || []
    } else {
      loyaltyTransactions.value = []
    }
  } catch (error) {
    console.error('Failed to fetch loyalty transactions:', error)
    loyaltyTransactions.value = []
  }
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedCustomer.value = null
  loyaltyTransactions.value = []
}

// Loyalty tier utility functions
const getLoyaltyTierBadgeClass = (tier) => {
  const tierLower = (tier || 'bronze').toLowerCase()
  const classes = {
    bronze: 'bg-orange-100 text-orange-800',
    silver: 'bg-gray-100 text-gray-800',
    gold: 'bg-yellow-100 text-yellow-800',
    platinum: 'bg-purple-100 text-purple-800'
  }
  return classes[tierLower] || classes.bronze
}

const formatLoyaltyTier = (tier) => {
  if (!tier) return 'Bronze'
  return tier.charAt(0).toUpperCase() + tier.slice(1).toLowerCase()
}

const getLoyaltyMultiplier = (tier) => {
  const tierLower = (tier || 'bronze').toLowerCase()
  const multipliers = {
    bronze: 1,
    silver: 1.25,
    gold: 1.5,
    platinum: 2
  }
  return multipliers[tierLower] || 1
}

const getLoyaltyDiscount = (tier) => {
  const tierLower = (tier || 'bronze').toLowerCase()
  const discounts = {
    bronze: 0,
    silver: 5,
    gold: 10,
    platinum: 15
  }
  return discounts[tierLower] || 0
}

const getNextTier = (tier) => {
  const tierLower = (tier || 'bronze').toLowerCase()
  const nextTiers = {
    bronze: 'Silver (500+ points)',
    silver: 'Gold (1000+ points)',
    gold: 'Platinum (2000+ points)',
    platinum: 'Maximum tier reached'
  }
  return nextTiers[tierLower] || 'Silver (500+ points)'
}

const getInitials = (name) => {
  if (!name) return '?'
  return name
    .split(' ')
    .map(word => word.charAt(0).toUpperCase())
    .slice(0, 2)
    .join('')
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-MY', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getTransactionTypeBadge = (type) => {
  const typeLower = (type || 'earned').toLowerCase()
  const badges = {
    earned: 'bg-green-100 text-green-800',
    redeemed: 'bg-blue-100 text-blue-800',
    expired: 'bg-red-100 text-red-800',
    adjustment: 'bg-yellow-100 text-yellow-800'
  }
  return badges[typeLower] || badges.earned
}

const formatTransactionType = (type) => {
  if (!type) return 'Earned'
  return type.charAt(0).toUpperCase() + type.slice(1).toLowerCase()
}

const remove = async id => {
  const confirmed = await modal.showConfirm('Are you sure you want to delete this customer? This action cannot be undone.')
  if (!confirmed) return
  
  try {
    await customersStore.deleteCustomer(id)
    await modal.showSuccess('Customer deleted successfully')
  } catch (e) {
    const errorMessage = e?.response?.data?.message || e?.message || 'Failed to delete customer'
    await modal.showError(errorMessage)
  }
}

// Watch for search and filter changes to reset pagination
watch([searchQuery, () => filters.status, () => filters.loyaltyTier, () => filters.minPurchases, () => filters.maxPurchases], () => {
  currentPage.value = 1
})

// initial load
customersStore.fetchCustomers().catch(() => {})
</script>
