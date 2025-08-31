<template>
  <div class="space-y-6">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Store Management</h2>
        <p class="text-gray-600 mt-1">Manage your store locations and information</p>
      </div>

      <div class="flex flex-col sm:flex-row gap-3">
        <!-- Search Bar -->
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search stores..."
            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 w-full sm:w-64"
          />
          <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <!-- Filter Dropdown -->
        <select
          v-model="selectedFilter"
          class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="all">All Stores</option>
          <option value="with_contact">With Contact Info</option>
          <option value="without_contact">Without Contact Info</option>
        </select>

        <!-- Add Store Button -->
        <button
          @click="openCreateForm"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 whitespace-nowrap"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          Add Store
        </button>
      </div>
    </div>

    <!-- Store Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-2 0H3m2 0h4M9 7h6m-6 4h6m-6 4h6" />
            </svg>
          </div>
        </div>
        <div class="mt-4">
          <h3 class="text-lg font-medium text-gray-900">{{ filteredStores.length }}</h3>
          <p class="text-sm text-gray-500">Total Stores</p>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-green-100 rounded-lg">
            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21L6.95 10.102C7.689 11.24 8.52 12.158 9.657 12.897l.71-3.273a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C7.82 21 2 15.18 2 8V7a2 2 0 012-2z" />
            </svg>
          </div>
        </div>
        <div class="mt-4">
          <h3 class="text-lg font-medium text-gray-900">{{ storesWithContact }}</h3>
          <p class="text-sm text-gray-500">With Contact Info</p>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-purple-100 rounded-lg">
            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
        </div>
        <div class="mt-4">
          <h3 class="text-lg font-medium text-gray-900">{{ totalProducts }}</h3>
          <p class="text-sm text-gray-500">Total Products</p>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-yellow-100 rounded-lg">
            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
          </div>
        </div>
        <div class="mt-4">
          <h3 class="text-lg font-medium text-gray-900">${{ totalSalesAmount }}</h3>
          <p class="text-sm text-gray-500">Total Sales</p>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoadingStores" class="bg-white rounded-lg shadow p-8">
      <div class="flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-gray-600">Loading stores...</span>
      </div>
    </div>

    <!-- Stores Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="store in paginatedStores" :key="store.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ store.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ store.address || 'No address' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  <div v-if="store.phone">📞 {{ store.phone }}</div>
                  <div v-if="store.email">✉️ {{ store.email }}</div>
                  <div v-if="!store.phone && !store.email" class="text-gray-400">No contact info</div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ store.total_products || 0 }} items</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center space-x-2">
                  <button
                    @click="viewStoreDetails(store)"
                    class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded text-xs"
                    title="View Details"
                  >
                    View
                  </button>
                  <button
                    @click="editStore(store)"
                    class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded text-xs"
                    title="Edit Store"
                  >
                    Edit
                  </button>
                  <button
                    @click="validateStore(store.id)"
                    class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 px-2 py-1 rounded text-xs"
                    title="Validate Store"
                  >
                    Check
                  </button>
                  <button
                    @click="confirmDelete(store)"
                    class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-2 py-1 rounded text-xs"
                    title="Delete Store"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="filteredStores.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <button
              @click="currentPage > 1 && currentPage--"
              :disabled="currentPage <= 1"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              @click="currentPage < totalPages && currentPage++"
              :disabled="currentPage >= totalPages"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div class="flex items-center space-x-4">
              <p class="text-sm text-gray-700">
                Showing
                <span class="font-medium">{{ ((currentPage - 1) * itemsPerPage) + 1 }}</span>
                to
                <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, filteredStores.length) }}</span>
                of
                <span class="font-medium">{{ filteredStores.length }}</span>
                results
              </p>
              <div class="flex items-center space-x-2">
                <label for="itemsPerPage" class="text-sm text-gray-700">Items per page:</label>
                <select
                  id="itemsPerPage"
                  v-model="itemsPerPage"
                  @change="currentPage = 1"
                  class="border border-gray-300 rounded px-2 py-1 text-sm"
                >
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <!-- First Page -->
              <button
                @click="currentPage = 1"
                :disabled="currentPage <= 1"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                title="First page"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
              </button>
              
              <!-- Previous Page -->
              <button
                @click="currentPage > 1 && currentPage--"
                :disabled="currentPage <= 1"
                class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                title="Previous page"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
              </button>
              
              <!-- Page Numbers -->
              <template v-for="page in visiblePages" :key="page">
                <button
                  @click="currentPage = page"
                  :class="[
                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                    page === currentPage
                      ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                  ]"
                >
                  {{ page }}
                </button>
              </template>
              
              <!-- Next Page -->
              <button
                @click="currentPage < totalPages && currentPage++"
                :disabled="currentPage >= totalPages"
                class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                title="Next page"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
              
              <!-- Last Page -->
              <button
                @click="currentPage = totalPages"
                :disabled="currentPage >= totalPages"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                title="Last page"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                </svg>
              </button>
              
              <!-- Jump to page -->
              <div class="flex items-center space-x-2 ml-4">
                <span class="text-sm text-gray-700">Go to:</span>
                <input
                  v-model.number="jumpToPage"
                  @keyup.enter="goToPage"
                  type="number"
                  :min="1"
                  :max="totalPages"
                  placeholder="Page"
                  class="border border-gray-300 rounded px-2 py-1 w-16 text-sm text-center"
                />
                <button
                  @click="goToPage"
                  class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700"
                >
                  Go
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredStores.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-2 0H3m2 0h4M9 7h6m-6 4h6m-6 4h6" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">
          {{ stores.length === 0 ? 'No stores' : 'No stores match your search' }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ stores.length === 0 ? 'Get started by creating a new store.' : 'Try adjusting your search criteria.' }}
        </p>

        <!-- Retry button if no stores loaded -->
        <div v-if="stores.length === 0" class="mt-4">
          <button
            @click="retryLoadStores"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"
          >
            Retry Loading Stores
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Form Modal -->
    <div v-if="showForm" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ isEditing ? 'Edit Store' : 'Add New Store' }}
          </h3>

          <form @submit.prevent="submitForm" class="space-y-4">
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700">Store Name *</label>
              <input
                v-model="form.name"
                type="text"
                id="name"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.name }"
              />
              <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
            </div>

            <div>
              <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
              <textarea
                v-model="form.address"
                id="address"
                rows="3"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.address }"
              ></textarea>
              <p v-if="errors.address" class="mt-1 text-sm text-red-600">{{ errors.address[0] }}</p>
            </div>

            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
              <input
                v-model="form.phone"
                type="tel"
                id="phone"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.phone }"
              />
              <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone[0] }}</p>
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
              <input
                v-model="form.email"
                type="email"
                id="email"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.email }"
              />
              <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email[0] }}</p>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="closeForm"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
              >
                {{ loading ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Message Modal (temporarily disabled) -->
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useStoresStore } from '@/stores/stores'

const storesStore = useStoresStore()

const stores = ref([])
const showForm = ref(false)
const isEditing = ref(false)
const loading = ref(false)
const errors = ref({})
const searchQuery = ref('')
const selectedFilter = ref('all')
const isLoadingStores = ref(false)

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(20)
const jumpToPage = ref(null)

const form = ref({
  name: '',
  address: '',
  phone: '',
  email: ''
})

// Computed properties
const filteredStores = computed(() => {
  let filtered = stores.value

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(store =>
      store.name.toLowerCase().includes(query) ||
      (store.address && store.address.toLowerCase().includes(query)) ||
      (store.phone && store.phone.includes(query)) ||
      (store.email && store.email.toLowerCase().includes(query))
    )
  }

  // Apply contact filter
  if (selectedFilter.value === 'with_contact') {
    filtered = filtered.filter(store => store.phone || store.email)
  } else if (selectedFilter.value === 'without_contact') {
    filtered = filtered.filter(store => !store.phone && !store.email)
  }

  return filtered
})

const storesWithContact = computed(() => {
  return stores.value.filter(store => store.phone || store.email).length
})

const totalProducts = computed(() => {
  return stores.value.reduce((sum, store) => sum + (store.total_products || 0), 0)
})

const totalSalesAmount = computed(() => {
  const total = stores.value.reduce((sum, store) => sum + Number(store.total_sales_amount ?? 0), 0)
  return Number.isFinite(total) ? total.toFixed(2) : '0.00'
})

const totalPages = computed(() => Math.ceil(filteredStores.value.length / itemsPerPage.value))

const paginatedStores = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredStores.value.slice(start, end)
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

// Watch for search changes to provide real-time filtering
watch([searchQuery, selectedFilter], () => {
  // Real-time filtering handled by computed property
  currentPage.value = 1  // Reset to first page when filtering
})

const goToPage = () => {
  if (jumpToPage.value && jumpToPage.value >= 1 && jumpToPage.value <= totalPages.value) {
    currentPage.value = jumpToPage.value
    jumpToPage.value = null
  }
}

const openCreateForm = () => {
  resetForm()
  isEditing.value = false
  showForm.value = true
}

const editStore = (store) => {
  form.value = { ...store }
  isEditing.value = true
  showForm.value = true
  errors.value = {}
}

const closeForm = () => {
  showForm.value = false
  resetForm()
  errors.value = {}
}

const resetForm = () => {
  form.value = {
    name: '',
    address: '',
    phone: '',
    email: ''
  }
}

const submitForm = async () => {
  loading.value = true
  errors.value = {}

  try {
    if (isEditing.value) {
      await storesStore.updateStore(form.value.id, form.value)
      console.log('Store updated successfully!')
    } else {
      await storesStore.createStore(form.value)
      console.log('Store created successfully!')
    }

    closeForm()
    await fetchStores()
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      console.error('Please fix the validation errors.')
    } else {
      console.error(
        isEditing.value
          ? 'Failed to update store. Please try again.'
          : 'Failed to create store. Please try again.'
      )
    }
  } finally {
    loading.value = false
  }
}

const confirmDelete = async (store) => {
  const confirmed = confirm(`Are you sure you want to delete "${store.name}"? This action cannot be undone.`)

  if (confirmed) {
    try {
      await storesStore.deleteStore(store.id)
      console.log('Store deleted successfully!')
      await fetchStores()
    } catch (error) {
      console.error('Failed to delete store. Please try again.')
    }
  }
}

const fetchStores = async () => {
  isLoadingStores.value = true
  try {
    const response = await storesStore.fetchStores()
    stores.value = response || []
  } catch (error) {
    console.error('Failed to load stores:', error)
    stores.value = []
    // Only show error in console
    console.error('Failed to load stores:', error)
  } finally {
    isLoadingStores.value = false
  }
}

// Analytics are now computed from store data directly

const validateStore = async (storeId) => {
  try {
    const response = await fetch(`/api/stores/${storeId}/validate`)
    if (response.ok) {
      const data = await response.json()
      if (!data.valid && data.issues?.length > 0) {
        console.warn('Store Validation Issues:', data.issues.join('\n'))
        alert(`Store has the following issues:\n${data.issues.join('\n')}`)
      }
    }
  } catch (error) {
    console.error('Failed to validate store:', error)
  }
}

const viewStoreDetails = async (store) => {
  try {
    const response = await fetch(`/api/stores/${store.id}`)
    if (response.ok) {
      const data = await response.json()
      const details = data.data

      let detailsText = `Store: ${store.name}\n\n`
      detailsText += `Products: ${details.store?.total_products || 0}\n`
      detailsText += `Total Stock: ${details.store?.total_stock || 0}\n`
      detailsText += `Sales Amount: $${details.store?.total_sales_amount || 0}\n`
      detailsText += `Low Stock Products: ${details.low_stock_products?.length || 0}\n`

      alert(detailsText)
    }
  } catch (error) {
    console.error('Failed to load store details.')
  }
}

const retryLoadStores = async () => {
  try {
    await fetchStores()
    console.log('Stores loaded successfully!')
  } catch (error) {
    console.error('Failed to load stores. Please check your connection.')
  }
}

onMounted(async () => {
  // Load stores silently on component mount
  try {
    await fetchStores()
  } catch (error) {
    console.log('Stores not loaded on mount:', error.message)
  }
})
</script>
