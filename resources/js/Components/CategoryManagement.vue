<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Categories</h2>
        <p class="text-gray-600">Create and manage product categories</p>
      </div>
      <button
        class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
        @click="openAdd"
      >
        <Plus class="w-4 h-4 mr-2" />
        Add Category
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
              placeholder="Search categories"
              class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <!-- Filters and Sorting -->
        <div class="flex flex-col lg:flex-row gap-4">
          <!-- Filters -->
          <div class="flex flex-wrap gap-4 items-center">
            <!-- Product Count Filter -->
            <div class="flex items-center gap-2 min-w-0 flex-shrink-0">
              <span class="text-sm text-gray-600">Products:</span>
              <input
                v-model.number="filters.minProducts"
                type="number"
                placeholder="Min"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                min="0"
              />
              <span class="text-gray-400">-</span>
              <input
                v-model.number="filters.maxProducts"
                type="number"
                placeholder="Max"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                min="0"
              />
            </div>

            <!-- Empty Categories Filter -->
            <div class="min-w-0 flex-shrink-0">
              <label class="flex items-center gap-2 text-sm">
                <input
                  v-model="filters.hideEmpty"
                  type="checkbox"
                  class="rounded border-gray-300"
                />
                Hide empty categories
              </label>
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
                <option value="products">Product Count</option>
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

      <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-600">
            <tr>
              <th class="text-left px-4 py-2 font-medium">Name</th>
              <th class="text-right px-4 py-2 font-medium">Products</th>
              <th class="px-4 py-2 font-medium text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in filtered" :key="c.id" class="border-t">
              <td class="px-4 py-2 font-medium text-gray-900">
                {{ c.name }}
              </td>
              <td class="px-4 py-2 text-right">
                {{ c.productsCount ?? 0 }}
              </td>
              <td class="px-4 py-2">
                <div class="flex justify-end gap-2">
                  <button class="text-gray-700 hover:text-gray-900" @click="viewProducts(c)">
                    View Products
                  </button>
                  <button class="text-blue-600 hover:text-blue-800" @click="openEdit(c)">
                    <Edit class="w-4 h-4" />
                  </button>
                  <button class="text-red-600 hover:text-red-800" @click="remove(c.id)">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filtered.length === 0">
              <td colspan="3" class="px-4 py-8 text-center text-gray-500">No categories found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">
            {{ isEditing ? 'Edit Category' : 'Add Category' }}
          </h3>
          <button @click="showModal = false">
            <X class="w-5 h-5" />
          </button>
        </div>
        <form class="space-y-3" @submit.prevent="submit">
          <input
            v-model="form.name"
            required
            placeholder="Name"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <input
            v-model="form.slug"
            placeholder="Slug (auto-generated from name if empty)"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
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
              <span v-else>{{ isEditing ? 'Save Changes' : 'Add Category' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <div
      v-if="showRelatedModal && selectedCategory"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">Products in {{ selectedCategory.name }}</h3>
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
              <tr v-for="p in selectedCategory.products ?? []" :key="p.id" class="border-t">
                <td class="px-4 py-2">{{ p.name }}</td>
                <td class="px-4 py-2">{{ p.sku }}</td>
              </tr>
              <tr v-if="!selectedCategory.products || selectedCategory.products.length === 0">
                <td colspan="2" class="px-4 py-8 text-center text-gray-500">No products.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useCategoriesStore } from '@/stores/categories.js'
import { useMessageModal } from '@/composables/useModal.js'
import Modal from '@/Components/Modal.vue'
import { Edit, Trash2, Plus, Search, X, ChevronUp, ChevronDown } from 'lucide-vue-next'

const store = useCategoriesStore()
const modal = useMessageModal()

// Search and Filter state
const query = ref('')
const filters = reactive({
  minProducts: null,
  maxProducts: null,
  hideEmpty: false,
})

// Sorting state
const sortBy = ref('name')
const sortOrder = ref('asc')

// Advanced filtering and sorting logic
const filtered = computed(() => {
  let results = store.categories

  // Apply search query
  if (query.value.trim()) {
    results = store.searchCategories(query.value)
  }

  // Apply filters
  if (filters.minProducts !== null && filters.minProducts !== '') {
    results = results.filter(c => (c.productsCount ?? 0) >= Number(filters.minProducts))
  }

  if (filters.maxProducts !== null && filters.maxProducts !== '') {
    results = results.filter(c => (c.productsCount ?? 0) <= Number(filters.maxProducts))
  }

  if (filters.hideEmpty) {
    results = results.filter(c => (c.productsCount ?? 0) > 0)
  }

  // Apply sorting
  results = [...results].sort((a, b) => {
    let aVal, bVal

    switch (sortBy.value) {
      case 'name':
        aVal = a.name.toLowerCase()
        bVal = b.name.toLowerCase()
        break
      case 'products':
        aVal = Number(a.productsCount ?? 0)
        bVal = Number(b.productsCount ?? 0)
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
  filters.minProducts = null
  filters.maxProducts = null
  filters.hideEmpty = false
}

const toggleSortOrder = () => {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
}

const showModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', slug: '' })

const showRelatedModal = ref(false)
const selectedCategory = computed(() => store.selectedCategory)

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

const resetForm = () => {
  form.name = ''
  form.slug = ''
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
  form.slug = c.slug ?? ''
  showModal.value = true
}

const submit = async () => {
  if (isSubmitting.value) return // Prevent multiple submissions

  try {
    isSubmitting.value = true

    // Validate required fields
    if (!form.name.trim()) {
      await modal.showError('Category name is required')
      return
    }

    if (form.name.trim().length < 2) {
      await modal.showError('Category name must be at least 2 characters')
      return
    }

    if (isEditing.value && editingId.value != null) {
      const payload = { name: form.name.trim() }
      if (form.slug.trim()) {
        payload.slug = form.slug.trim()
      }
      await store.updateCategory(editingId.value, payload)
      await modal.showSuccess('Category updated successfully')
      showModal.value = false
    } else {
      const payload = { name: form.name.trim() }
      if (form.slug.trim()) {
        payload.slug = form.slug.trim()
      }
      await store.addCategory(payload)
      await modal.showSuccess('Category added successfully')
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
  const confirmed = await modal.showConfirm('Are you sure you want to delete this category? This action cannot be undone.')
  if (!confirmed) return
  
  try {
    await store.deleteCategory(id)
    await modal.showSuccess('Category deleted successfully')
  } catch (e) {
    const errorMessage = e?.message || 'Failed to delete category'
    await modal.showError(errorMessage)
  }
}

const viewProducts = async c => {
  await store.fetchCategoryDetails(c.id)
  showRelatedModal.value = true
}

store.fetchCategories().catch(() => {})
</script>

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
