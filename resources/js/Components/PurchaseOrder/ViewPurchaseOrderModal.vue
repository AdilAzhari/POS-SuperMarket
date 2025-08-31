<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

      <div class="relative inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
              <FileText class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
              <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ purchaseOrder?.po_number }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">Purchase Order Details</p>
            </div>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <X class="w-6 h-6" />
          </button>
        </div>

        <div v-if="purchaseOrder" class="mt-6 space-y-6">
          <!-- Order Info -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
              <h4 class="font-medium text-gray-900 dark:text-white mb-3">Order Information</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">PO Number:</span>
                  <span class="font-medium">{{ purchaseOrder.po_number }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Created:</span>
                  <span class="font-medium">{{ formatDate(purchaseOrder.created_at) }}</span>
                </div>
                <div class="flex justify-between" v-if="purchaseOrder.expected_delivery_date">
                  <span class="text-gray-600 dark:text-gray-400">Expected:</span>
                  <span class="font-medium">{{ formatDate(purchaseOrder.expected_delivery_date) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Status:</span>
                  <span :class="getStatusBadgeClass(purchaseOrder.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                    {{ formatStatus(purchaseOrder.status) }}
                  </span>
                </div>
              </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
              <h4 class="font-medium text-gray-900 dark:text-white mb-3">Supplier Details</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Name:</span>
                  <span class="font-medium">{{ purchaseOrder.supplier?.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Email:</span>
                  <span class="font-medium">{{ purchaseOrder.supplier?.email }}</span>
                </div>
                <div class="flex justify-between" v-if="purchaseOrder.supplier?.phone">
                  <span class="text-gray-600 dark:text-gray-400">Phone:</span>
                  <span class="font-medium">{{ purchaseOrder.supplier.phone }}</span>
                </div>
                <div v-if="purchaseOrder.supplier?.address" class="pt-2">
                  <span class="text-gray-600 dark:text-gray-400 text-xs">Address:</span>
                  <p class="text-xs mt-1">{{ purchaseOrder.supplier.address }}</p>
                </div>
              </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
              <h4 class="font-medium text-gray-900 dark:text-white mb-3">Order Summary</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Items:</span>
                  <span class="font-medium">{{ purchaseOrder.items_count }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                  <span class="font-medium">${{ formatCurrency(purchaseOrder.subtotal || purchaseOrder.total_amount) }}</span>
                </div>
                <div class="flex justify-between" v-if="purchaseOrder.tax_amount">
                  <span class="text-gray-600 dark:text-gray-400">Tax:</span>
                  <span class="font-medium">${{ formatCurrency(purchaseOrder.tax_amount) }}</span>
                </div>
                <div class="flex justify-between border-t border-gray-200 dark:border-gray-600 pt-2 font-semibold">
                  <span>Total:</span>
                  <span>${{ formatCurrency(purchaseOrder.total_amount) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-medium text-gray-900 dark:text-white">Order Progress</h4>
              <span class="text-sm text-gray-600 dark:text-gray-400">{{ purchaseOrder.progress?.percentage || 0 }}% Complete</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-3">
              <div 
                :class="getProgressBarClass(purchaseOrder.status)"
                class="h-3 rounded-full transition-all duration-300"
                :style="{ width: `${purchaseOrder.progress?.percentage || 0}%` }"
              ></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
              <span>{{ purchaseOrder.progress?.received_items || 0 }} of {{ purchaseOrder.progress?.total_items || 0 }} items received</span>
              <span>{{ purchaseOrder.progress?.percentage || 0 }}%</span>
            </div>
          </div>

          <!-- Order Items -->
          <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
            <h4 class="font-medium text-gray-900 dark:text-white mb-4">Order Items</h4>
            <div class="overflow-x-auto">
              <table class="min-w-full">
                <thead>
                  <tr class="border-b border-gray-200 dark:border-gray-600">
                    <th class="text-left py-2 text-sm font-medium text-gray-600 dark:text-gray-400">Product</th>
                    <th class="text-right py-2 text-sm font-medium text-gray-600 dark:text-gray-400">Ordered</th>
                    <th class="text-right py-2 text-sm font-medium text-gray-600 dark:text-gray-400">Received</th>
                    <th class="text-right py-2 text-sm font-medium text-gray-600 dark:text-gray-400">Unit Cost</th>
                    <th class="text-right py-2 text-sm font-medium text-gray-600 dark:text-gray-400">Total</th>
                    <th class="text-center py-2 text-sm font-medium text-gray-600 dark:text-gray-400">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                  <tr v-for="item in purchaseOrder.items || mockItems" :key="item.id" class="text-sm">
                    <td class="py-3">
                      <div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ item.product?.name || 'Product Name' }}</div>
                        <div class="text-gray-500 dark:text-gray-400 text-xs">{{ item.product?.sku || 'SKU' }}</div>
                      </div>
                    </td>
                    <td class="text-right py-3 text-gray-900 dark:text-white">{{ item.quantity_ordered || item.quantity }}</td>
                    <td class="text-right py-3">
                      <span :class="item.quantity_received >= item.quantity_ordered ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400'">
                        {{ item.quantity_received || 0 }}
                      </span>
                    </td>
                    <td class="text-right py-3 text-gray-900 dark:text-white">${{ formatCurrency(item.unit_cost || item.price) }}</td>
                    <td class="text-right py-3 text-gray-900 dark:text-white">${{ formatCurrency((item.quantity_ordered || item.quantity) * (item.unit_cost || item.price)) }}</td>
                    <td class="text-center py-3">
                      <span :class="getItemStatusClass(item)" class="px-2 py-1 text-xs font-semibold rounded-full">
                        {{ getItemStatus(item) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="purchaseOrder.notes" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Notes</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ purchaseOrder.notes }}</p>
          </div>

          <!-- Timeline -->
          <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
            <h4 class="font-medium text-gray-900 dark:text-white mb-4">Order Timeline</h4>
            <div class="space-y-4">
              <div v-for="event in orderTimeline" :key="event.id" class="flex items-start gap-3">
                <div :class="event.iconClass" class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center">
                  <component :is="event.icon" class="w-4 h-4" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ event.title }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ event.description }}</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatDate(event.date) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 mt-8 pt-4 border-t border-gray-200 dark:border-gray-700">
          <button
            @click="printPO"
            class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
          >
            <Printer class="w-4 h-4" />
            Print
          </button>
          <button
            v-if="purchaseOrder?.status === 'draft'"
            @click="editOrder"
            class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
          >
            <Edit class="w-4 h-4" />
            Edit Order
          </button>
          <button
            v-if="['sent', 'confirmed'].includes(purchaseOrder?.status)"
            @click="markAsReceived"
            class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
          >
            <Package class="w-4 h-4" />
            Mark as Received
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import {
  FileText, X, Printer, Edit, Package, CheckCircle, Clock, Truck, AlertCircle
} from 'lucide-vue-next'
import axios from 'axios'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  purchaseOrder: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'updated'])

// Mock items for display if none provided
const mockItems = ref([
  {
    id: 1,
    product: { name: 'Coffee Beans Premium', sku: 'COF001' },
    quantity_ordered: 50,
    quantity_received: 50,
    unit_cost: 12.50,
    price: 12.50
  },
  {
    id: 2,
    product: { name: 'Organic Milk 1L', sku: 'MLK002' },
    quantity_ordered: 100,
    quantity_received: 80,
    unit_cost: 3.25,
    price: 3.25
  }
])

// Computed properties
const orderTimeline = computed(() => {
  if (!props.purchaseOrder) return []

  const events = [
    {
      id: 1,
      title: 'Order Created',
      description: 'Purchase order was created and saved as draft',
      date: props.purchaseOrder.created_at,
      icon: FileText,
      iconClass: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
    }
  ]

  if (['sent', 'confirmed', 'partially_received', 'received'].includes(props.purchaseOrder.status)) {
    events.push({
      id: 2,
      title: 'Order Sent',
      description: 'Purchase order was sent to supplier',
      date: props.purchaseOrder.sent_at || props.purchaseOrder.created_at,
      icon: Clock,
      iconClass: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400'
    })
  }

  if (['confirmed', 'partially_received', 'received'].includes(props.purchaseOrder.status)) {
    events.push({
      id: 3,
      title: 'Order Confirmed',
      description: 'Supplier confirmed the order',
      date: props.purchaseOrder.confirmed_at || props.purchaseOrder.created_at,
      icon: CheckCircle,
      iconClass: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
    })
  }

  if (['partially_received', 'received'].includes(props.purchaseOrder.status)) {
    events.push({
      id: 4,
      title: 'Items Received',
      description: props.purchaseOrder.status === 'received' ? 'All items have been received' : 'Partial shipment received',
      date: props.purchaseOrder.received_at || props.purchaseOrder.created_at,
      icon: Package,
      iconClass: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400'
    })
  }

  if (props.purchaseOrder.status === 'cancelled') {
    events.push({
      id: 5,
      title: 'Order Cancelled',
      description: 'Purchase order was cancelled',
      date: props.purchaseOrder.cancelled_at || props.purchaseOrder.created_at,
      icon: AlertCircle,
      iconClass: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
    })
  }

  return events.sort((a, b) => new Date(a.date) - new Date(b.date))
})

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatStatus = (status) => {
  return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')
}

const getStatusBadgeClass = (status) => {
  const classes = {
    'draft': 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
    'sent': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    'confirmed': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    'partially_received': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    'received': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    'cancelled': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
  }
  return classes[status] || classes.draft
}

const getProgressBarClass = (status) => {
  const classes = {
    'draft': 'bg-gray-400',
    'sent': 'bg-blue-500',
    'confirmed': 'bg-yellow-500',
    'partially_received': 'bg-orange-500',
    'received': 'bg-green-500',
    'cancelled': 'bg-red-500'
  }
  return classes[status] || classes.draft
}

const getItemStatus = (item) => {
  const ordered = item.quantity_ordered || item.quantity
  const received = item.quantity_received || 0
  
  if (received === 0) return 'Pending'
  if (received >= ordered) return 'Complete'
  return 'Partial'
}

const getItemStatusClass = (item) => {
  const status = getItemStatus(item)
  const classes = {
    'Pending': 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
    'Partial': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    'Complete': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
  }
  return classes[status] || classes.Pending
}

const printPO = () => {
  window.open(`/api/purchase-orders/${props.purchaseOrder.id}/print`, '_blank')
}

const editOrder = () => {
  emit('close')
  // This would normally open an edit modal or navigate to edit page
}

const markAsReceived = async () => {
  try {
    await axios.post(`/api/purchase-orders/${props.purchaseOrder.id}/receive`)
    emit('updated')
  } catch (error) {
    console.error('Error marking order as received:', error)
  }
}

// Handle ESC key to close modal
const handleKeydown = (event) => {
  if (event.key === 'Escape' && props.show) {
    emit('close')
  }
}

// Watch for show prop changes to add/remove event listener
watch(() => props.show, (newVal) => {
  if (newVal) {
    document.addEventListener('keydown', handleKeydown)
  } else {
    document.removeEventListener('keydown', handleKeydown)
  }
})
</script>

<style scoped>
/* Add any custom styles here */
</style>