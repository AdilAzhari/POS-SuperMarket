<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold text-gray-900">Payment History</h2>
      <div class="flex space-x-2">
        <select
          v-model="selectedMethod"
          class="px-3 py-2 border rounded-lg text-sm"
        >
          <option value="">All Methods</option>
          <option v-for="method in paymentMethods" :key="method.code" :value="method.code">
            {{ method.name }}
          </option>
        </select>
        <button
          @click="refresh"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
        >
          Refresh
        </button>
      </div>
    </div>

    <!-- Payment Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-green-50 p-4 rounded-lg">
        <div class="text-2xl font-bold text-green-600">{{ stats.total_payments }}</div>
        <div class="text-sm text-green-700">Total Payments</div>
      </div>
      <div class="bg-blue-50 p-4 rounded-lg">
        <div class="text-2xl font-bold text-blue-600">RM {{ stats.total_amount?.toFixed(2) || '0.00' }}</div>
        <div class="text-sm text-blue-700">Total Amount</div>
      </div>
      <div class="bg-orange-50 p-4 rounded-lg">
        <div class="text-2xl font-bold text-orange-600">RM {{ stats.total_fees?.toFixed(2) || '0.00' }}</div>
        <div class="text-sm text-orange-700">Total Fees</div>
      </div>
      <div class="bg-purple-50 p-4 rounded-lg">
        <div class="text-2xl font-bold text-purple-600">RM {{ stats.net_amount?.toFixed(2) || '0.00' }}</div>
        <div class="text-sm text-purple-700">Net Amount</div>
      </div>
    </div>

    <!-- Payment Methods Breakdown -->
    <div class="mb-6">
      <h3 class="text-lg font-medium text-gray-900 mb-3">Payment Methods Breakdown</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div 
          v-for="(methodStats, method) in stats.by_method" 
          :key="method"
          class="bg-gray-50 p-4 rounded-lg"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="font-medium">{{ getMethodName(method) }}</span>
            <span class="text-sm text-gray-500">{{ methodStats.count }} transactions</span>
          </div>
          <div class="text-lg font-bold text-gray-900">RM {{ methodStats.amount?.toFixed(2) || '0.00' }}</div>
          <div class="text-sm text-gray-600">Fees: RM {{ methodStats.fees?.toFixed(2) || '0.00' }}</div>
        </div>
      </div>
    </div>

    <!-- Payment History Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Payment Code
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Sale ID
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Method
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Amount
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Fee
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Date
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="payment in filteredPayments" :key="payment.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ payment.payment_code }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ payment.sale?.code || payment.sale_id }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getMethodBadgeClass(payment.payment_method)" class="px-2 py-1 text-xs rounded-full">
                {{ getMethodName(payment.payment_method) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              RM {{ payment.amount }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              RM {{ payment.fee || '0.00' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStatusBadgeClass(payment.status)" class="px-2 py-1 text-xs rounded-full">
                {{ payment.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(payment.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <button
                v-if="payment.status === 'completed'"
                @click="showRefundModal(payment)"
                class="text-red-600 hover:text-red-900"
              >
                Refund
              </button>
              <button
                @click="showPaymentDetails(payment)"
                class="text-blue-600 hover:text-blue-900 ml-3"
              >
                Details
              </button>
            </td>
          </tr>
          <tr v-if="payments.length === 0">
            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
              No payments found
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Payment Details Modal -->
    <div v-if="showDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Payment Details</h3>
          <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-600">
            <X class="w-5 h-5" />
          </button>
        </div>
        <div v-if="selectedPayment" class="space-y-3">
          <div>
            <label class="text-sm font-medium text-gray-500">Payment Code:</label>
            <div class="text-sm text-gray-900">{{ selectedPayment.payment_code }}</div>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Method:</label>
            <div class="text-sm text-gray-900">{{ getMethodName(selectedPayment.payment_method) }}</div>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Amount:</label>
            <div class="text-sm text-gray-900">RM {{ selectedPayment.amount }}</div>
          </div>
          <div v-if="selectedPayment.fee > 0">
            <label class="text-sm font-medium text-gray-500">Fee:</label>
            <div class="text-sm text-gray-900">RM {{ selectedPayment.fee }}</div>
          </div>
          <div v-if="selectedPayment.card_last_four">
            <label class="text-sm font-medium text-gray-500">Card:</label>
            <div class="text-sm text-gray-900">**** {{ selectedPayment.card_last_four }}</div>
          </div>
          <div v-if="selectedPayment.tng_reference">
            <label class="text-sm font-medium text-gray-500">TNG Reference:</label>
            <div class="text-sm text-gray-900">{{ selectedPayment.tng_reference }}</div>
          </div>
          <div v-if="selectedPayment.gateway_reference">
            <label class="text-sm font-medium text-gray-500">Gateway Reference:</label>
            <div class="text-sm text-gray-900">{{ selectedPayment.gateway_reference }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { X } from 'lucide-vue-next'
import axios from 'axios'

// Data
const payments = ref([])
const paymentMethods = ref([])
const stats = ref({})
const selectedMethod = ref('')
const showDetailsModal = ref(false)
const selectedPayment = ref(null)
const isLoading = ref(false)

// Computed
const filteredPayments = computed(() => {
  if (!selectedMethod.value) return payments.value
  return payments.value.filter(p => p.payment_method === selectedMethod.value)
})

// Methods
const fetchPayments = async () => {
  isLoading.value = true
  try {
    // For demo purposes, we'll fetch payments from the payments table directly
    // In a real app, you'd have a dedicated API endpoint
    const paymentsResponse = await axios.get('/api/payments/stats')
    stats.value = paymentsResponse.data

    // Fetch payment methods
    const methodsResponse = await axios.get('/api/payment-methods')
    paymentMethods.value = methodsResponse.data.methods

    // Mock some payment data for display
    payments.value = [
      {
        id: 1,
        payment_code: 'PAY-000001',
        sale_id: 2,
        sale: { code: 'TXN-000001' },
        payment_method: 'cash',
        amount: '110.00',
        fee: '0.00',
        status: 'completed',
        created_at: new Date().toISOString()
      },
      {
        id: 2,
        payment_code: 'PAY-000002',
        sale_id: 2,
        sale: { code: 'TXN-000001' },
        payment_method: 'tng',
        amount: '110.00',
        fee: '1.65',
        status: 'completed',
        tng_reference: 'TNG17554184655922',
        created_at: new Date(Date.now() - 300000).toISOString()
      },
      {
        id: 3,
        payment_code: 'PAY-000003',
        sale_id: 2,
        sale: { code: 'TXN-000001' },
        payment_method: 'visa',
        amount: '110.00',
        fee: '2.75',
        status: 'completed',
        card_last_four: '1111',
        gateway_reference: 'CARD-' + Date.now(),
        created_at: new Date(Date.now() - 600000).toISOString()
      }
    ]
  } catch (error) {
    console.error('Failed to fetch payments:', error)
  } finally {
    isLoading.value = false
  }
}

const refresh = () => {
  fetchPayments()
}

const showPaymentDetails = (payment) => {
  selectedPayment.value = payment
  showDetailsModal.value = true
}

const showRefundModal = (payment) => {
  if (confirm(`Are you sure you want to refund payment ${payment.payment_code}?`)) {
    // Handle refund logic here
    alert('Refund functionality would be implemented here')
  }
}

const getMethodName = (method) => {
  const methodMap = {
    'cash': 'Cash',
    'stripe': 'Stripe',
    'visa': 'Visa',
    'mastercard': 'Mastercard',
    'amex': 'American Express',
    'tng': 'Touch n Go'
  }
  return methodMap[method] || method
}

const getMethodBadgeClass = (method) => {
  const classMap = {
    'cash': 'bg-green-100 text-green-800',
    'stripe': 'bg-purple-100 text-purple-800',
    'visa': 'bg-blue-100 text-blue-800',
    'mastercard': 'bg-red-100 text-red-800',
    'amex': 'bg-indigo-100 text-indigo-800',
    'tng': 'bg-yellow-100 text-yellow-800'
  }
  return classMap[method] || 'bg-gray-100 text-gray-800'
}

const getStatusBadgeClass = (status) => {
  const classMap = {
    'completed': 'bg-green-100 text-green-800',
    'pending': 'bg-yellow-100 text-yellow-800',
    'failed': 'bg-red-100 text-red-800',
    'refunded': 'bg-gray-100 text-gray-800'
  }
  return classMap[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-MY', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  fetchPayments()
})
</script>