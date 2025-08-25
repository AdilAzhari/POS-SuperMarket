<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Products</h2>
        <p class="text-gray-600">Add, edit, and manage your catalog</p>
      </div>
      <button
        class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
        @click="openAdd"
      >
        <Plus class="w-4 h-4 mr-2" />
        Add Product
      </button>
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
            <tr v-for="p in filtered" :key="p.id" class="border-t">
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
      <div v-if="store.pagination.total > 0" class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ store.pagination.from }} to {{ store.pagination.to }} of {{ store.pagination.total }} results
        </div>
        <div class="flex items-center space-x-2">
          <button
            :disabled="store.pagination.current_page === 1"
            @click="changePage(store.pagination.current_page - 1)"
            class="px-3 py-2 text-sm border rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
          >
            Previous
          </button>
          
          <template v-for="page in visiblePages" :key="page">
            <button
              v-if="page !== '...'"
              :class="[
                'px-3 py-2 text-sm border rounded-md',
                page === store.pagination.current_page
                  ? 'bg-blue-600 text-white border-blue-600'
                  : 'hover:bg-gray-50'
              ]"
              @click="changePage(page)"
            >
              {{ page }}
            </button>
            <span v-else class="px-3 py-2 text-sm text-gray-500">...</span>
          </template>
          
          <button
            :disabled="store.pagination.current_page === store.pagination.last_page"
            @click="changePage(store.pagination.current_page + 1)"
            class="px-3 py-2 text-sm border rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
          >
            Next
          </button>
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
import { Edit, Trash2, Plus, Search, X, ChevronUp, ChevronDown } from 'lucide-vue-next'

const store = useProductsStore()
const categoriesStore = useCategoriesStore()
const suppliersStore = useSuppliersStore()
const modal = useMessageModal()

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

// Pagination functions
const changePage = async (page) => {
  if (page >= 1 && page <= store.pagination.last_page) {
    await store.fetchProducts(page)
  }
}

const visiblePages = computed(() => {
  const current = store.pagination.current_page
  const last = store.pagination.last_page
  const pages = []
  
  if (last <= 7) {
    // Show all pages if 7 or fewer
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    // Always show first page
    pages.push(1)
    
    if (current <= 4) {
      // Show first 5 pages + ellipsis + last
      for (let i = 2; i <= 5; i++) {
        pages.push(i)
      }
      pages.push('...')
      pages.push(last)
    } else if (current >= last - 3) {
      // Show first + ellipsis + last 5 pages
      pages.push('...')
      for (let i = last - 4; i <= last; i++) {
        pages.push(i)
      }
    } else {
      // Show first + ellipsis + current-1, current, current+1 + ellipsis + last
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) {
        pages.push(i)
      }
      pages.push('...')
      pages.push(last)
    }
  }
  
  return pages
})

// initial load
store.fetchProducts().catch(() => {})
categoriesStore.fetchCategories().catch(() => {})
suppliersStore.fetchSuppliers().catch(() => {})
</script>
