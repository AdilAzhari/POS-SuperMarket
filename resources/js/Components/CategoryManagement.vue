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
import { Edit, Trash2, Plus, Search, X } from 'lucide-vue-next'

const store = useCategoriesStore()

const query = ref('')
const filtered = computed(() => store.searchCategories(query.value))

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
      notify('Category name is required', 'error')
      return
    }

    if (form.name.trim().length < 2) {
      notify('Category name must be at least 2 characters', 'error')
      return
    }

    if (isEditing.value && editingId.value != null) {
      const payload = { name: form.name.trim() }
      if (form.slug.trim()) {
        payload.slug = form.slug.trim()
      }
      await store.updateCategory(editingId.value, payload)
      notify('Category updated successfully')
      showModal.value = false
    } else {
      const payload = { name: form.name.trim() }
      if (form.slug.trim()) {
        payload.slug = form.slug.trim()
      }
      await store.addCategory(payload)
      notify('Category added successfully')
      showModal.value = false
    }
  } catch (e) {
    const errorMessage = e?.message || 'Operation failed'
    notify(errorMessage, 'error')
  } finally {
    isSubmitting.value = false
  }
}

const remove = async id => {
  if (!confirm('Delete this category?')) return
  try {
    await store.deleteCategory(id)
    notify('Category removed successfully')
  } catch (e) {
    const errorMessage = e?.message || 'Failed to remove category'
    notify(errorMessage, 'error')
  }
}

const viewProducts = async c => {
  await store.fetchCategoryDetails(c.id)
  showRelatedModal.value = true
}

store.fetchCategories().catch(() => {})
</script>
