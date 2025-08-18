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
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="relative flex-1">
          <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
          <input
            v-model="query"
            placeholder="Search suppliers by name or address"
            class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
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
            <tr v-for="s in filtered" :key="s.id" class="border-t">
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
            <tr v-if="filtered.length === 0">
              <td colspan="6" class="px-4 py-8 text-center text-gray-500">No suppliers found.</td>
            </tr>
          </tbody>
        </table>
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
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useSuppliersStore } from '@/stores/suppliers.js'
import { Edit, Trash2, Plus, Search, X } from 'lucide-vue-next'

const store = useSuppliersStore()

const query = ref('')
const filtered = computed(() => store.searchSuppliers(query.value))

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
      notify('Supplier name is required', 'error')
      return
    }

    if (form.name.trim().length < 2) {
      notify('Supplier name must be at least 2 characters', 'error')
      return
    }

    // Validate email format if provided
    if (form.contactEmail && form.contactEmail.trim()) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(form.contactEmail.trim())) {
        notify('Please enter a valid email address', 'error')
        return
      }
    }

    // Validate phone format if provided
    if (form.contactPhone && form.contactPhone.trim()) {
      const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/
      if (!phoneRegex.test(form.contactPhone.trim().replace(/[\s\-\(\)]/g, ''))) {
        notify('Please enter a valid phone number', 'error')
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
      notify('Supplier updated successfully')
      showModal.value = false
    } else {
      await store.addSupplier({
        name: form.name.trim(),
        contactPhone: form.contactPhone?.trim() || '',
        contactEmail: form.contactEmail?.trim() || '',
        address: form.address?.trim() || '',
      })
      notify('Supplier added successfully')
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
  if (!confirm('Delete this supplier?')) return
  try {
    await store.deleteSupplier(id)
    notify('Supplier removed successfully')
  } catch (e) {
    const errorMessage = e?.message || 'Failed to remove supplier'
    notify(errorMessage, 'error')
  }
}

const viewProducts = async s => {
  await store.fetchSupplierDetails(s.id)
  showRelatedModal.value = true
}

store.fetchSuppliers().catch(() => {})
</script>
