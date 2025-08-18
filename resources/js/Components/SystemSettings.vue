<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h2 class="text-2xl font-bold text-gray-900">System Settings</h2>
      <p class="text-gray-600">Configure system preferences and business settings</p>
    </div>

    <!-- Notification -->
    <div v-if="message" :class="messageType === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'" class="p-4 rounded-lg">
      {{ message }}
    </div>

    <!-- Settings Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Store Information -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Store Information</h3>
        <form class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Store Name</label>
            <input
              v-model="storeSettings.name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
            <textarea
              v-model="storeSettings.address"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            ></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
              <input
                v-model="storeSettings.phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input
                v-model="storeSettings.email"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
          </div>
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            @click.prevent="saveStoreSettings"
          >
            <span v-if="isLoading">Saving...</span>
            <span v-else>Save Store Information</span>
          </button>
        </form>
      </div>

      <!-- Tax Settings -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Tax Settings</h3>
        <form class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label>
            <input
              v-model.number="taxSettings.rate"
              type="number"
              step="0.01"
              min="0"
              max="100"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tax Name</label>
            <input
              v-model="taxSettings.name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div class="flex items-center">
            <input
              id="taxInclusive"
              v-model="taxSettings.inclusive"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label for="taxInclusive" class="ml-2 block text-sm text-gray-900">
              Tax Inclusive Pricing
            </label>
          </div>
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            @click.prevent="saveTaxSettings"
          >
            <span v-if="isLoading">Saving...</span>
            <span v-else>Save Tax Settings</span>
          </button>
        </form>
      </div>

      <!-- Receipt Settings -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Receipt Settings</h3>
        <form class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Header</label>
            <textarea
              v-model="receiptSettings.header"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            ></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Footer</label>
            <textarea
              v-model="receiptSettings.footer"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            ></textarea>
          </div>
          <div class="flex items-center">
            <input
              id="showLogo"
              v-model="receiptSettings.showLogo"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label for="showLogo" class="ml-2 block text-sm text-gray-900">
              Show Logo on Receipt
            </label>
          </div>
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            @click.prevent="saveReceiptSettings"
          >
            <span v-if="isLoading">Saving...</span>
            <span v-else>Save Receipt Settings</span>
          </button>
        </form>
      </div>

      <!-- User Management -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">User Management</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                <User class="w-4 h-4 text-blue-600" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Admin User</p>
                <p class="text-xs text-gray-500">Administrator</p>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <span
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800"
              >
                Active
              </span>
              <button class="text-blue-600 hover:text-blue-900">
                <Edit class="w-4 h-4" />
              </button>
            </div>
          </div>
          <button
            class="w-full border-2 border-dashed border-gray-300 rounded-lg p-4 text-gray-600 hover:border-gray-400 hover:text-gray-700 transition-colors"
            @click="showAddUserModal = true"
          >
            <Plus class="w-5 h-5 mx-auto mb-1" />
            <span class="text-sm">Add New User</span>
          </button>
        </div>
      </div>

      <!-- Backup & Security -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Backup & Security</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
              <p class="text-sm font-medium text-gray-900">Last Backup</p>
              <p class="text-xs text-gray-500">January 15, 2024 at 2:30 AM</p>
            </div>
            <button
              class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition-colors"
            >
              Backup Now
            </button>
          </div>
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
              <p class="text-sm font-medium text-gray-900">Auto Backup</p>
              <p class="text-xs text-gray-500">Daily at 2:00 AM</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" checked class="sr-only peer" />
              <div
                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
              ></div>
            </label>
          </div>
          <button
            class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition-colors"
          >
            Reset System Data
          </button>
        </div>
      </div>

      <!-- System Information -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">System Information</h3>
        <div class="space-y-3">
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Version:</span>
            <span class="text-sm font-medium text-gray-900">2.1.0</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Last Updated:</span>
            <span class="text-sm font-medium text-gray-900">Jan 10, 2024</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Database Size:</span>
            <span class="text-sm font-medium text-gray-900">45.2 MB</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Active Users:</span>
            <span class="text-sm font-medium text-gray-900">1</span>
          </div>
          <div class="pt-3 border-t border-gray-200">
            <button
              class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors"
            >
              Check for Updates
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add User Modal -->
    <div
      v-if="showAddUserModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">Add New User</h3>
          <button @click="showAddUserModal = false">
            <X class="w-5 h-5" />
          </button>
        </div>
        <form class="space-y-4" @submit.prevent="addUser">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
            <input
              v-model="newUser.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input
              v-model="newUser.email"
              type="email"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
            <select
              v-model="newUser.role"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Select Role</option>
              <option value="admin">Administrator</option>
              <option value="manager">Manager</option>
              <option value="cashier">Cashier</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input
              v-model="newUser.password"
              type="password"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div class="flex space-x-3 pt-4">
            <button
              type="button"
              class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
              @click="showAddUserModal = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Add User
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { User, Edit, Plus, X } from 'lucide-vue-next'
import axios from 'axios'

const showAddUserModal = ref(false)
const isLoading = ref(false)
const message = ref('')
const messageType = ref('success')

const notify = (msg, type = 'success') => {
  message.value = msg
  messageType.value = type
  setTimeout(() => message.value = '', 3000)
}

const storeSettings = ref({
  name: 'SuperMarket POS',
  address: '123 Main Street\nAnytown, ST 12345',
  phone: '+1-555-0123',
  email: 'info@supermarketpos.com',
})

const taxSettings = ref({
  rate: 8.0,
  name: 'Sales Tax',
  inclusive: false,
})

const receiptSettings = ref({
  header: 'Thank you for shopping with us!',
  footer: 'Please come again!\nReturn policy: 30 days with receipt',
  showLogo: true,
})

const newUser = ref({
  name: '',
  email: '',
  role: '',
  password: '',
})

// Load settings from API on mount
const loadSettings = async () => {
  try {
    const response = await axios.get('/api/settings/all')
    const settings = response.data
    
    if (settings.store_info) {
      storeSettings.value = { ...storeSettings.value, ...settings.store_info }
    }
    if (settings.tax_settings) {
      taxSettings.value = { ...taxSettings.value, ...settings.tax_settings }
    }
    if (settings.receipt_settings) {
      receiptSettings.value = { ...receiptSettings.value, ...settings.receipt_settings }
    }
  } catch (error) {
    console.error('Failed to load settings:', error)
    notify('Failed to load settings', 'error')
  }
}

onMounted(() => {
  loadSettings()
})

const saveStoreSettings = async () => {
  try {
    isLoading.value = true
    const response = await axios.post('/api/settings/store', storeSettings.value)
    notify(response.data.message || 'Store settings saved successfully!')
  } catch (error) {
    console.error('Failed to save store settings:', error)
    const errorMessage = error.response?.data?.message || 'Failed to save store settings'
    notify(errorMessage, 'error')
  } finally {
    isLoading.value = false
  }
}

const saveTaxSettings = async () => {
  try {
    isLoading.value = true
    const response = await axios.post('/api/settings/tax', taxSettings.value)
    notify(response.data.message || 'Tax settings saved successfully!')
  } catch (error) {
    console.error('Failed to save tax settings:', error)
    const errorMessage = error.response?.data?.message || 'Failed to save tax settings'
    notify(errorMessage, 'error')
  } finally {
    isLoading.value = false
  }
}

const saveReceiptSettings = async () => {
  try {
    isLoading.value = true
    const response = await axios.post('/api/settings/receipt', receiptSettings.value)
    notify(response.data.message || 'Receipt settings saved successfully!')
  } catch (error) {
    console.error('Failed to save receipt settings:', error)
    const errorMessage = error.response?.data?.message || 'Failed to save receipt settings'
    notify(errorMessage, 'error')
  } finally {
    isLoading.value = false
  }
}

const addUser = async () => {
  try {
    isLoading.value = true
    const response = await axios.post('/api/users', newUser.value)
    notify(`User ${newUser.value.name} added successfully!`)
    newUser.value = { name: '', email: '', role: '', password: '' }
    showAddUserModal.value = false
  } catch (error) {
    console.error('Failed to add user:', error)
    const errorMessage = error.response?.data?.message || 'Failed to add user'
    notify(errorMessage, 'error')
  } finally {
    isLoading.value = false
  }
}
</script>
