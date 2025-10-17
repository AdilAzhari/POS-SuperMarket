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
              <th class="text-left px-4 py-2 font-medium">Status</th>
              <th class="px-4 py-2 font-medium text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in paginatedCustomers" :key="c.id" class="border-t">
              <td class="px-4 py-2 font-medium text-gray-900">
                {{ c.name }}
              </td>
              <td class="px-4 py-2">{{ c.phone }}</td>
              <td class="px-4 py-2">{{ c.email }}</td>
              <td class="px-4 py-2 text-right">
                {{ c.totalPurchases ?? 0 }}
              </td>
              <td class="px-4 py-2 text-right">${{ (c.totalSpent ?? 0).toFixed(2) }}</td>
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
              <td class="px-4 py-2">
                <div class="flex justify-end gap-2">
                  <button class="text-blue-600 hover:text-blue-800" @click="openEdit(c)">
                    <Edit class="w-4 h-4" />
                  </button>
                  <button class="text-red-600 hover:text-red-800" @click="remove(c.id)">
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
  maxPurchases: null
})

// Sorting
const sortBy = ref('name')
const sortDirection = ref('asc')

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(20)
const jumpToPage = ref(null)

// Computed properties
const hasActiveFilters = computed(() => {
  return filters.status || filters.minPurchases !== null || filters.maxPurchases !== null
})

const filteredCustomers = computed(() => {
  let customers = customersStore.searchCustomers(searchQuery.value)
  
  // Apply filters
  if (filters.status) {
    customers = customers.filter(c => (c.status || 'active') === filters.status)
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
watch([searchQuery, () => filters.status, () => filters.minPurchases, () => filters.maxPurchases], () => {
  currentPage.value = 1
})

// initial load
customersStore.fetchCustomers().catch(() => {})
</script>
