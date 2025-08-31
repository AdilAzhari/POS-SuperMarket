<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Suppliers</h2>
        <p class="text-gray-600">Manage suppliers and their products</p>
      </div>
      <button
        class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
        @click="openAdd"
      >
        <Plus class="w-4 h-4 mr-2" />
        Add Supplier
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
              v-model="query"
              placeholder="Search suppliers by name or address"
              class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="text-sm text-gray-500">
            {{ paginatedSuppliers.length }} of {{ filteredSuppliers.length }} suppliers
          </div>
        </div>

        <!-- Filters and Sorting -->
        <div class="flex flex-col lg:flex-row gap-4">
          <!-- Sorting -->
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Sort by:</span>
            <select
              v-model="sortBy"
              class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
            >
              <option value="name">Name</option>
              <option value="contactPhone">Phone</option>
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
              <th class="text-left px-4 py-2 font-medium">Address</th>
              <th class="text-right px-4 py-2 font-medium">Products</th>
              <th class="px-4 py-2 font-medium text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in paginatedSuppliers" :key="s.id" class="border-t">
              <td class="px-4 py-2 font-medium text-gray-900">
                {{ s.name }}
              </td>
              <td class="px-4 py-2">{{ s.contactPhone }}</td>
              <td class="px-4 py-2">{{ s.contactEmail }}</td>
              <td class="px-4 py-2 truncate max-w-[240px]">
                {{ s.address }}
              </td>
              <td class="px-4 py-2 text-right">
                {{ s.productsCount ?? 0 }}
              </td>
              <td class="px-4 py-2">
                <div class="flex justify-end gap-2">
                  <button class="text-gray-700 hover:text-gray-900" @click="viewProducts(s)">
                    View Products
                  </button>
                  <button class="text-blue-600 hover:text-blue-800" @click="openEdit(s)">
                    <Edit class="w-4 h-4" />
                  </button>
                  <button class="text-red-600 hover:text-red-800" @click="remove(s.id)">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredSuppliers.length === 0">
              <td colspan="6" class="px-4 py-8 text-center text-gray-500">No suppliers found.</td>
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
              to <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, filteredSuppliers.length) }}</span>
              of <span class="font-medium">{{ filteredSuppliers.length }}</span> suppliers
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
      <div class="bg-white rounded-lg p-6 w-full max-w-lg">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">
            {{ isEditing ? 'Edit Supplier' : 'Add Supplier' }}
          </h3>
          <button @click="showModal = false">
            <X class="w-5 h-5" />
          </button>
        </div>
        <form class="grid grid-cols-1 gap-3" @submit.prevent="submit">
          <input
            v-model="form.name"
            required
            placeholder="Name"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <input
            v-model="form.contactPhone"
            placeholder="Phone"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <input
            v-model="form.contactEmail"
            type="email"
            placeholder="Email"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <textarea
            v-model="form.address"
            rows="3"
            placeholder="Address"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          ></textarea>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="px-4 py-2 border rounded-lg" @click="showModal = false">
              Cancel
            </button>
            <button
              type="submit"
              :disabled="isSubmitting"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed"
            >
              <span v-if="isSubmitting">{{ isEditing ? 'Saving...' : 'Adding...' }}</span>
              <span v-else>{{ isEditing ? 'Save Changes' : 'Add Supplier' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <div
      v-if="showRelatedModal && selectedSupplier"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">Products from {{ selectedSupplier.name }}</h3>
          <button @click="showRelatedModal = false">
            <X class="w-5 h-5" />
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th class="text-left px-4 py-2 font-medium">Name</th>
                <th class="text-left px-4 py-2 font-medium">SKU</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in selectedSupplier.products ?? []" :key="p.id" class="border-t">
                <td class="px-4 py-2">{{ p.name }}</td>
                <td class="px-4 py-2">{{ p.sku }}</td>
              </tr>
              <tr v-if="!selectedSupplier.products || selectedSupplier.products.length === 0">
                <td colspan="2" class="px-4 py-8 text-center text-gray-500">No products.</td>
              </tr>
            </tbody>
          </table>
        </div>
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
import { computed, reactive, ref } from 'vue'
import { useSuppliersStore } from '@/stores/suppliers.js'
import { useMessageModal } from '@/composables/useModal.js'
import Modal from '@/Components/Modal.vue'
import { Edit, Trash2, Plus, Search, X, ArrowUpDown } from 'lucide-vue-next'

const store = useSuppliersStore()
const modal = useMessageModal()

// Search and sorting
const query = ref('')
const sortBy = ref('name')
const sortDirection = ref('asc')

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(20)
const jumpToPage = ref(null)

// Computed properties
const filteredSuppliers = computed(() => {
  let suppliers = store.searchSuppliers(query.value)
  
  // Apply sorting
  suppliers.sort((a, b) => {
    let aValue = a[sortBy.value]
    let bValue = b[sortBy.value]
    
    // Handle null/undefined values
    if (aValue == null) aValue = ''
    if (bValue == null) bValue = ''
    
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
  
  return suppliers
})

const totalPages = computed(() => Math.ceil(filteredSuppliers.value.length / itemsPerPage.value))

const paginatedSuppliers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredSuppliers.value.slice(start, end)
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
  contactPhone: '',
  contactEmail: '',
  address: '',
})

const showRelatedModal = ref(false)
const selectedSupplier = computed(() => store.selectedSupplier)

// Add loading state
const isSubmitting = ref(false)

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

const goToPage = () => {
  if (jumpToPage.value && jumpToPage.value >= 1 && jumpToPage.value <= totalPages.value) {
    currentPage.value = jumpToPage.value
    jumpToPage.value = null
  }
}

const resetForm = () => {
  form.name = ''
  form.contactPhone = ''
  form.contactEmail = ''
  form.address = ''
}

const openAdd = () => {
  isEditing.value = false
  editingId.value = null
  resetForm()
  showModal.value = true
}

const openEdit = s => {
  isEditing.value = true
  editingId.value = s.id
  form.name = s.name
  form.contactPhone = s.contactPhone ?? ''
  form.contactEmail = s.contactEmail ?? ''
  form.address = s.address ?? ''
  showModal.value = true
}

const submit = async () => {
  if (isSubmitting.value) return // Prevent multiple submissions

  try {
    isSubmitting.value = true

    // Validate required fields
    if (!form.name?.trim()) {
      await modal.showError('Supplier name is required')
      return
    }

    if (form.name.trim().length < 2) {
      await modal.showError('Supplier name must be at least 2 characters')
      return
    }

    // Validate email format if provided
    if (form.contactEmail && form.contactEmail.trim()) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(form.contactEmail.trim())) {
        await modal.showError('Please enter a valid email address')
        return
      }
    }

    // Validate phone format if provided
    if (form.contactPhone && form.contactPhone.trim()) {
      const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/
      if (!phoneRegex.test(form.contactPhone.trim().replace(/[\s\-\(\)]/g, ''))) {
        await modal.showError('Please enter a valid phone number')
        return
      }
    }

    if (isEditing.value && editingId.value != null) {
      await store.updateSupplier(editingId.value, {
        name: form.name.trim(),
        contactPhone: form.contactPhone?.trim() || '',
        contactEmail: form.contactEmail?.trim() || '',
        address: form.address?.trim() || '',
      })
      await modal.showSuccess('Supplier updated successfully')
      showModal.value = false
    } else {
      await store.addSupplier({
        name: form.name.trim(),
        contactPhone: form.contactPhone?.trim() || '',
        contactEmail: form.contactEmail?.trim() || '',
        address: form.address?.trim() || '',
      })
      await modal.showSuccess('Supplier added successfully')
      showModal.value = false
    }
  } catch (e) {
    const errorMessage = e?.message || 'Operation failed'
    await modal.showError(errorMessage)
  } finally {
    isSubmitting.value = false
  }
}

const remove = async id => {
  const confirmed = await modal.showConfirm('Are you sure you want to delete this supplier? This action cannot be undone.')
  if (!confirmed) return
  
  try {
    await store.deleteSupplier(id)
    await modal.showSuccess('Supplier deleted successfully')
  } catch (e) {
    const errorMessage = e?.message || 'Failed to delete supplier'
    await modal.showError(errorMessage)
  }
}

const viewProducts = async s => {
  await store.fetchSupplierDetails(s.id)
  showRelatedModal.value = true
}

// Sorting function
const toggleSortDirection = () => {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
}

store.fetchSuppliers().catch(() => {})
</script>
