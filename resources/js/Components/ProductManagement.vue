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

      <div class="mt-4 overflow-x-auto">
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
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useProductsStore } from '@/stores/products.js'
import { useCategoriesStore } from '@/stores/categories.js'
import { useSuppliersStore } from '@/stores/suppliers.js'
import { Edit, Trash2, Plus, Search, X } from 'lucide-vue-next'

const store = useProductsStore()
const categoriesStore = useCategoriesStore()
const suppliersStore = useSuppliersStore()

const query = ref('')
const filtered = computed(() => store.searchProducts(query.value))

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
    if (isEditing.value && editingId.value) {
      await store.updateProduct(editingId.value, { ...form })
      notify('Product updated')
    } else {
      const { id, ...payload } = form
      await store.addProduct(payload)
      notify('Product added')
    }
    showModal.value = false
  } catch (e) {
    notify('Operation failed', 'error')
  }
}

const remove = async id => {
  if (!confirm('Delete this product?')) return
  try {
    await store.deleteProduct(id)
    notify('Product removed')
  } catch (e) {
    notify('Failed to remove product', 'error')
  }
}

// initial load
store.fetchProducts().catch(() => {})
categoriesStore.fetchCategories().catch(() => {})
suppliersStore.fetchSuppliers().catch(() => {})
</script>
