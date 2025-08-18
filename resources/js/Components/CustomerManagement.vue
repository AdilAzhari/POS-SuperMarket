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
            <tr v-for="c in filteredCustomers" :key="c.id" class="border-t">
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
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useCustomersStore } from '@/stores/customers.js'
import { Edit, Trash2, UserPlus, Search, X } from 'lucide-vue-next'

const customersStore = useCustomersStore()

const searchQuery = ref('')
const filteredCustomers = computed(() => customersStore.searchCustomers(searchQuery.value))

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
    if (!form.name || !form.phone || !form.email) return
    if (isEditing.value && editingId.value) {
      await customersStore.updateCustomer(editingId.value, { ...form })
      notify('Customer updated')
    } else {
      await customersStore.addCustomer({
        name: form.name,
        phone: form.phone,
        email: form.email,
        address: form.address ?? '',
      })
      notify('Customer added')
    }
    showModal.value = false
    resetForm()
  } catch (e) {
    notify('Operation failed', 'error')
  }
}

const remove = async id => {
  if (!confirm('Delete this customer?')) return
  try {
    await customersStore.deleteCustomer(id)
    notify('Customer removed')
  } catch (e) {
    notify('Failed to remove customer', 'error')
  }
}

// initial load
customersStore.fetchCustomers().catch(() => {})
</script>
