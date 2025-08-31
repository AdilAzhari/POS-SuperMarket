<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Products</h2>
        <p class="text-gray-600">Add, edit, and manage your catalog</p>
      </div>
      <div class="flex items-center gap-3">
        <button
          class="inline-flex items-center bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 disabled:opacity-50"
          @click="clearProductCache"
          :disabled="loading.clearCache"
        >
          <RefreshCw class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading.clearCache }" />
          Clear Cache
        </button>
        <button
          class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
          @click="openAdd"
        >
          <Plus class="w-4 h-4 mr-2" />
          Add Product
        </button>
      </div>
    </div>

    <div v-if="flashMessage" class="rounded-md p-3" :class="flashClass">
      {{ flashMessage }}
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4">
      <!-- Search and Filters -->
      <div class="space-y-4">
        <!-- Search Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="relative flex-1">
            <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
            <input
              v-model="query"
              placeholder="Search by name, SKU, barcode, category"
              class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <!-- Filters and Sorting -->
        <div class="flex flex-col lg:flex-row gap-4">
          <!-- Filters -->
          <div class="flex flex-wrap gap-4 items-center">
            <!-- Category Filter -->
            <div class="min-w-0 flex-shrink-0">
              <select
                v-model="filters.category"
                class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Categories</option>
                <option
                  v-for="category in categoriesStore.categories"
                  :key="category.id"
                  :value="category.name"
                >
                  {{ category.name }}
                </option>
              </select>
            </div>

            <!-- Price Range Filter -->
            <div class="flex items-center gap-2 min-w-0 flex-shrink-0">
              <span class="text-sm text-gray-600">Price:</span>
              <input
                v-model.number="filters.priceMin"
                type="number"
                placeholder="Min"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                min="0"
                step="0.01"
              />
              <span class="text-gray-400">-</span>
              <input
                v-model.number="filters.priceMax"
                type="number"
                placeholder="Max"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                min="0"
                step="0.01"
              />
            </div>

            <!-- Stock Range Filter -->
            <div class="flex items-center gap-2 min-w-0 flex-shrink-0">
              <span class="text-sm text-gray-600">Stock:</span>
              <input
                v-model.number="filters.stockMin"
                type="number"
                placeholder="Min"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                min="0"
              />
              <span class="text-gray-400">-</span>
              <input
                v-model.number="filters.stockMax"
                type="number"
                placeholder="Max"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                min="0"
              />
            </div>

            <!-- Active Status Filter -->
            <div class="min-w-0 flex-shrink-0">
              <select
                v-model="filters.active"
                class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Status</option>
                <option value="true">Active Only</option>
                <option value="false">Inactive Only</option>
              </select>
            </div>

            <!-- Clear Filters -->
            <button
              @click="clearFilters"
              class="px-3 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Clear Filters
            </button>
          </div>

          <!-- Sorting -->
          <div class="flex items-center gap-4 lg:ml-auto">
            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-600">Sort by:</span>
              <select
                v-model="sortBy"
                class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="name">Name</option>
                <option value="price">Price</option>
                <option value="stock">Stock</option>
                <option value="category">Category</option>
                <option value="created_at">Date Added</option>
              </select>
            </div>

            <button
              @click="toggleSortOrder"
              class="px-3 py-2 border rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500"
              :title="sortOrder === 'asc' ? 'Sort Descending' : 'Sort Ascending'"
            >
              <component
                :is="sortOrder === 'asc' ? ChevronUp : ChevronDown"
                class="w-4 h-4"
              />
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="store.isLoading" class="flex justify-center items-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-2 text-gray-600">Loading products...</span>
      </div>

      <div v-else class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-600">
            <tr>
              <th class="text-left px-4 py-2 font-medium">Product</th>
              <th class="text-left px-4 py-2 font-medium">SKU</th>
              <th class="text-left px-4 py-2 font-medium">Barcode</th>
              <th class="text-right px-4 py-2 font-medium">Price</th>
              <th class="text-right px-4 py-2 font-medium">Stock</th>
              <th class="text-left px-4 py-2 font-medium">Category</th>
              <th class="text-left px-4 py-2 font-medium">Active</th>
              <th class="px-4 py-2 font-medium text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in paginatedProducts" :key="p.id" class="border-t">
              <td class="px-4 py-2">
                <div class="flex items-center gap-3">
                  <img :src="p.image" :alt="p.name" class="w-10 h-10 rounded object-cover" />
                  <div class="font-medium text-gray-900">
                    {{ p.name }}
                  </div>
                </div>
              </td>
              <td class="px-4 py-2">{{ p.sku }}</td>
              <td class="px-4 py-2">{{ p.barcode }}</td>
              <td class="px-4 py-2 text-right">${{ p.price.toFixed(2) }}</td>
              <td class="px-4 py-2 text-right">{{ p.stock }}</td>
              <td class="px-4 py-2">{{ p.category }}</td>
              <td class="px-4 py-2">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs rounded-full',
                    p.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700',
                  ]"
                >
                  {{ p.active ? 'Yes' : 'No' }}
                </span>
              </td>
              <td class="px-4 py-2">
                <div class="flex justify-end gap-2">
                  <button class="text-blue-600 hover:text-blue-800" @click="openEdit(p)">
                    <Edit class="w-4 h-4" />
                  </button>
                  <button class="text-red-600 hover:text-red-800" @click="remove(p.id)">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filtered.length === 0">
              <td colspan="8" class="px-4 py-8 text-center text-gray-500">No products found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="filtered.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
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
                <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, filtered.length) }}</span>
                of
                <span class="font-medium">{{ filtered.length }}</span>
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
              <template v-for="page in visiblePageNumbers" :key="page">
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
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">
            {{ isEditing ? 'Edit Product' : 'Add Product' }}
          </h3>
          <button @click="showModal = false">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="submit">
          <input
            v-model="form.name"
            required
            placeholder="Product Name"
            class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <input
            v-model="form.sku"
            required
            placeholder="SKU"
            class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <input
            v-model="form.barcode"
            required
            placeholder="Barcode"
            class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <input
            v-model.number="form.price"
            type="number"
            min="0"
            step="0.01"
            required
            placeholder="Price"
            class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <input
            v-model.number="form.cost"
            type="number"
            min="0"
            step="0.01"
            required
            placeholder="Cost"
            class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <input
            v-model.number="form.stock"
            type="number"
            min="0"
            step="1"
            required
            placeholder="Stock"
            class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <select
            v-model="form.category_id"
            required
            class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select Category</option>
            <option v-for="c in categoriesStore.categories" :key="c.id" :value="c.id">
              {{ c.name }}
            </option>
          </select>
          <select
            v-model="form.supplier_id"
            required
            class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select Supplier</option>
            <option v-for="s in suppliersStore.suppliers" :key="s.id" :value="s.id">
              {{ s.name }}
            </option>
          </select>
          <input
            v-model="form.image"
            required
            placeholder="Image URL"
            class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 md:col-span-2"
          />
          <div class="flex items-center gap-2 md:col-span-2">
            <input
              id="active"
              v-model="form.active"
              type="checkbox"
              class="h-4 w-4 rounded border-gray-300"
            />
            <label for="active" class="text-sm">Active</label>
          </div>
          <input
            v-model.number="form.lowStockThreshold"
            type="number"
            min="0"
            step="1"
            required
            placeholder="Low Stock Threshold"
            class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />

          <div class="md:col-span-2 flex justify-end gap-2 pt-2">
            <button type="button" class="px-4 py-2 border rounded-lg" @click="showModal = false">
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              {{ isEditing ? 'Save Changes' : 'Add Product' }}
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
import { computed, reactive, ref } from 'vue'
import { useProductsStore } from '@/stores/products.js'
import { useCategoriesStore } from '@/stores/categories.js'
import { useSuppliersStore } from '@/stores/suppliers.js'
import { useMessageModal } from '@/composables/useModal.js'
import Modal from '@/Components/Modal.vue'
import { Edit, Trash2, Plus, Search, X, ChevronUp, ChevronDown, RefreshCw } from 'lucide-vue-next'
import axios from 'axios'

const store = useProductsStore()
const categoriesStore = useCategoriesStore()
const suppliersStore = useSuppliersStore()
const modal = useMessageModal()

// Loading state
const loading = reactive({
  clearCache: false
})

// Search and Filter state
const query = ref('')
const filters = reactive({
  category: '',
  priceMin: null,
  priceMax: null,
  stockMin: null,
  stockMax: null,
  active: '',
})

// Sorting state
const sortBy = ref('name')
const sortOrder = ref('asc')

// Pagination state
const currentPage = ref(1)
const itemsPerPage = ref(20)
const jumpToPage = ref(null)

// Advanced filtering and sorting logic
const filtered = computed(() => {
  let results = store.products

  // Apply search query
  if (query.value.trim()) {
    results = store.searchProducts(query.value)
  }

  // Apply filters
  if (filters.category) {
    results = results.filter(p => p.category === filters.category)
  }

  if (filters.priceMin !== null && filters.priceMin !== '') {
    results = results.filter(p => p.price >= Number(filters.priceMin))
  }

  if (filters.priceMax !== null && filters.priceMax !== '') {
    results = results.filter(p => p.price <= Number(filters.priceMax))
  }

  if (filters.stockMin !== null && filters.stockMin !== '') {
    results = results.filter(p => p.stock >= Number(filters.stockMin))
  }

  if (filters.stockMax !== null && filters.stockMax !== '') {
    results = results.filter(p => p.stock <= Number(filters.stockMax))
  }

  if (filters.active !== '') {
    const activeFilter = filters.active === 'true'
    results = results.filter(p => p.active === activeFilter)
  }

  // Apply sorting
  results = [...results].sort((a, b) => {
    let aVal, bVal

    switch (sortBy.value) {
      case 'name':
        aVal = a.name.toLowerCase()
        bVal = b.name.toLowerCase()
        break
      case 'price':
        aVal = Number(a.price)
        bVal = Number(b.price)
        break
      case 'stock':
        aVal = Number(a.stock)
        bVal = Number(b.stock)
        break
      case 'category':
        aVal = a.category.toLowerCase()
        bVal = b.category.toLowerCase()
        break
      case 'created_at':
        aVal = new Date(a.created_at || 0)
        bVal = new Date(b.created_at || 0)
        break
      default:
        return 0
    }

    if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1
    if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1
    return 0
  })

  return results
})

const totalPages = computed(() => Math.ceil(filtered.value.length / itemsPerPage.value))

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filtered.value.slice(start, end)
})

const visiblePageNumbers = computed(() => {
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

// Filter and Sort Actions
const clearFilters = () => {
  filters.category = ''
  filters.priceMin = null
  filters.priceMax = null
  filters.stockMin = null
  filters.stockMax = null
  filters.active = ''
}

const toggleSortOrder = () => {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
}

const goToPage = () => {
  if (jumpToPage.value && jumpToPage.value >= 1 && jumpToPage.value <= totalPages.value) {
    currentPage.value = jumpToPage.value
    jumpToPage.value = null
  }
}

const showModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const form = reactive({
  id: '',
  name: '',
  sku: '',
  barcode: '',
  price: 0,
  cost: 0,
  stock: 0,
  category_id: '',
  supplier_id: '',
  image: '',
  active: true,
  lowStockThreshold: 0,
})

const resetForm = () => {
  Object.assign(form, {
    id: '',
    name: '',
    sku: '',
    barcode: '',
    price: 0,
    cost: 0,
    stock: 0,
    category_id: '',
    supplier_id: '',
    image: '',
    active: true,
    lowStockThreshold: 0,
  })
}

const openAdd = () => {
  isEditing.value = false
  editingId.value = null
  resetForm()
  showModal.value = true
}

const openEdit = p => {
  isEditing.value = true
  editingId.value = p.id
  Object.assign(form, {
    ...p,
    category_id: categoriesStore.categories.find(c => c.name === p.category)?.id || '',
    supplier_id: suppliersStore.suppliers.find(s => s.name === p.supplier)?.id || ''
  })
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
      await modal.showError('Product name is required')
      return
    }
    if (!form.sku?.trim()) {
      await modal.showError('SKU is required')
      return
    }
    if (!form.barcode?.trim()) {
      await modal.showError('Barcode is required')
      return
    }
    if (form.price <= 0) {
      await modal.showError('Price must be greater than 0')
      return
    }
    if (form.cost < 0) {
      await modal.showError('Cost cannot be negative')
      return
    }
    if (!form.category_id) {
      await modal.showError('Please select a category')
      return
    }
    if (!form.supplier_id) {
      await modal.showError('Please select a supplier')
      return
    }

    if (isEditing.value && editingId.value) {
      const updateData = {
        name: form.name.trim(),
        sku: form.sku.trim(),
        barcode: form.barcode.trim(),
        price: Number(form.price),
        cost: Number(form.cost),
        active: Boolean(form.active),
        lowStockThreshold: Number(form.lowStockThreshold || 0),
        image: form.image?.trim() || '',
        category_id: Number(form.category_id),
        supplier_id: Number(form.supplier_id)
      }
      await store.updateProduct(editingId.value, updateData)
      await modal.showSuccess('Product updated successfully')
    } else {
      const addData = {
        name: form.name.trim(),
        sku: form.sku.trim(),
        barcode: form.barcode.trim(),
        price: Number(form.price),
        cost: Number(form.cost),
        active: Boolean(form.active),
        low_stock_threshold: Number(form.lowStockThreshold || 0),
        image_url: form.image?.trim() || '',
        category_id: Number(form.category_id),
        supplier_id: Number(form.supplier_id)
      }
      await store.addProduct(addData)
      await modal.showSuccess('Product added successfully')
    }
    
    // Refresh the product list to ensure latest data is shown
    await store.fetchProducts()
    
    showModal.value = false
  } catch (e) {
    const errorMessage = e?.response?.data?.message || e?.message || 'Operation failed'
    await modal.showError(errorMessage)
  }
}

const remove = async id => {
  const confirmed = await modal.showConfirm('Are you sure you want to delete this product? This action cannot be undone.')
  if (!confirmed) return
  
  try {
    await store.deleteProduct(id)
    await modal.showSuccess('Product deleted successfully')
  } catch (e) {
    const errorMessage = e?.response?.data?.message || e?.message || 'Failed to delete product'
    await modal.showError(errorMessage)
  }
}

const clearProductCache = async () => {
  loading.clearCache = true
  try {
    const response = await axios.post('/api/cache/clear-products')
    
    if (response.data.success) {
      notify('Product cache cleared successfully', 'success')
      // Refresh products after clearing cache
      await store.fetchProducts()
    } else {
      notify(response.data.message || 'Failed to clear cache', 'error')
    }
  } catch (error) {
    notify('Failed to clear product cache', 'error')
  } finally {
    loading.clearCache = false
  }
}

// initial load
store.fetchProducts().catch(() => {})
categoriesStore.fetchCategories().catch(() => {})
suppliersStore.fetchSuppliers().catch(() => {})
</script>
