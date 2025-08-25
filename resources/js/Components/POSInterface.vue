<template>
  <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 h-screen lg:h-auto p-4 lg:p-0">
    <!-- Mobile/Tablet: Stack vertically, Desktop: Side by side -->
    <div class="flex-1 lg:flex-none lg:w-2/3 space-y-4 lg:space-y-6 min-h-0">
      <!-- Search Bar - Larger for touch -->
      <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6">
        <div class="space-y-3">
          <div class="relative">
            <Search class="w-5 h-5 text-gray-400 absolute left-4 top-3.5" />
            <input
              v-model="productQuery"
              placeholder="Scan barcode or search products"
              class="w-full pl-12 pr-16 py-3 text-lg border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              autocomplete="off"
            />
            <button
              @click="showBarcodeScanner = !showBarcodeScanner"
              class="absolute right-2 top-2 p-2 text-gray-400 hover:text-blue-600 transition-colors"
              title="Toggle Barcode Scanner"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 16h4.01M16 12h4.01M12 8h4.01M8 8h4.01M8 12h4.01M8 16h4.01M8 4h4.01M4 4h4.01M4 8h4.01M4 12h4.01M4 16h4.01"></path>
              </svg>
            </button>
          </div>

          <!-- Barcode Scanner - Mobile optimized -->
          <div v-if="showBarcodeScanner" class="bg-gray-50 rounded-lg p-3">
            <BarcodeScanner
              @barcode-scanned="handleBarcodeScanned"
              @scan-error="handleScanError"
              :enable-camera="true"
              :auto-focus="false"
              class="w-full"
            />
          </div>
        </div>

        <!-- Inventory Alerts -->
        <div v-if="inventoryAlerts.length > 0" class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <AlertTriangle class="h-5 w-5 text-yellow-600 mr-2" />
              <span class="text-sm font-medium text-yellow-800">
                {{ inventoryAlerts.length }} Low Stock Alert{{ inventoryAlerts.length > 1 ? 's' : '' }}
              </span>
            </div>
            <button
              @click="showInventoryAlerts = !showInventoryAlerts"
              class="text-yellow-600 hover:text-yellow-800"
            >
              <ChevronDown :class="['h-4 w-4 transition-transform', { 'rotate-180': showInventoryAlerts }]" />
            </button>
          </div>

          <div v-if="showInventoryAlerts" class="mt-2 space-y-1">
            <div
              v-for="alert in inventoryAlerts.slice(0, 5)"
              :key="alert.id"
              class="flex items-center justify-between text-sm"
            >
              <span class="text-yellow-700">{{ alert.name }}</span>
              <span :class="alert.is_critical ? 'text-red-600 font-bold' : 'text-yellow-600'">
                {{ alert.current_stock }} left
              </span>
            </div>
            <div v-if="inventoryAlerts.length > 5" class="text-xs text-yellow-600 text-center pt-1">
              +{{ inventoryAlerts.length - 5 }} more items
            </div>
          </div>
        </div>

        <!-- Quick Category Filters for mobile -->
        <div class="mt-4 lg:mt-6 flex space-x-3 overflow-x-auto scrollbar-hide pb-2">
          <button
            @click="selectedCategory = ''"
            :class="selectedCategory === '' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'"
            class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors"
          >
            All
          </button>
          <button
            v-for="category in productCategories"
            :key="category"
            @click="selectedCategory = category"
            :class="selectedCategory === category ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'"
            class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors"
          >
            {{ category }}
          </button>
        </div>
      </div>

      <!-- Products Grid - Responsive for different screen sizes -->
      <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 flex-1 min-h-0 overflow-hidden">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 lg:mb-6">Products</h3>
        <div class="h-full overflow-y-auto -mx-2">
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4 gap-3 lg:gap-4 px-2">
            <button
              v-for="p in filteredProducts"
              :key="p.id"
              class="bg-gray-50 rounded-lg p-3 text-left hover:bg-blue-50 hover:ring-2 hover:ring-blue-500 transition-all active:scale-95 touch-manipulation"
              @click="pos.addToCart(p)"
            >
              <img :src="p.image" :alt="p.name" class="w-full h-24 md:h-28 object-cover rounded-md mb-2" />
              <div class="font-medium text-gray-900 text-sm md:text-base truncate">
                {{ p.name }}
              </div>
              <div class="text-xs text-gray-500 truncate">{{ p.sku }} • {{ p.category }}</div>
              <div class="mt-1 font-semibold text-blue-600 text-sm md:text-base">${{ p.price.toFixed(2) }}</div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cart Section - Mobile friendly -->
    <div class="lg:w-1/3 space-y-4 lg:space-y-6 flex flex-col min-h-0">
      <!-- Cart Items - Touch optimized -->
      <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 flex-1 min-h-0 overflow-hidden">
        <div class="flex items-center justify-between mb-4 lg:mb-6">
          <h3 class="font-semibold text-gray-900 text-lg lg:text-xl">Cart</h3>
          <button
            class="px-3 py-1.5 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded-md transition-colors"
            @click="pos.clearCart"
          >
            Clear All
          </button>
        </div>

        <div v-if="pos.cartItems.length === 0" class="flex items-center justify-center h-32 text-gray-500">
          <div class="text-center">
            <ShoppingCart class="w-12 h-12 mx-auto mb-2 text-gray-300" />
            <p>No items in cart</p>
          </div>
        </div>

        <div v-else class="h-full overflow-y-auto -mx-2 px-2 space-y-3 lg:space-y-4">
          <div
            v-for="item in pos.cartItems"
            :key="item.id"
            class="bg-gray-50 rounded-lg p-3 lg:p-4 space-y-3"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1 min-w-0 mr-2">
                <p class="font-medium text-gray-900 text-sm truncate">
                  {{ item.name }}
                </p>
                <p class="text-xs text-gray-500">${{ item.price.toFixed(2) }} each</p>
              </div>
              <div class="text-right">
                <p class="font-medium text-gray-900">${{ item.lineTotal.toFixed(2) }}</p>
              </div>
            </div>

            <!-- Touch-friendly quantity controls -->
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3 bg-white rounded-lg p-1">
                <button
                  class="w-8 h-8 flex items-center justify-center rounded border hover:bg-gray-50 active:scale-95 transition-all"
                  @click="changeQty(item.id, item.quantity - 1, item.discount)"
                >
                  <Minus class="w-4 h-4" />
                </button>
                <span class="w-8 text-center font-medium">{{ item.quantity }}</span>
                <button
                  class="w-8 h-8 flex items-center justify-center rounded border hover:bg-gray-50 active:scale-95 transition-all"
                  @click="changeQty(item.id, item.quantity + 1, item.discount)"
                >
                  <Plus class="w-4 h-4" />
                </button>
              </div>
              <button
                class="w-8 h-8 flex items-center justify-center text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg active:scale-95 transition-all"
                @click="pos.removeFromCart(item.id)"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Cart Summary -->
        <div class="mt-4 lg:mt-6 border-t pt-4 lg:pt-6 space-y-2 lg:space-y-3 text-sm lg:text-base">
          <div class="flex justify-between text-gray-600">
            <span>Items</span>
            <span>{{ pos.cartItemCount }}</span>
          </div>
          <div class="flex justify-between text-gray-600">
            <span>Subtotal</span>
            <span>${{ pos.subtotal.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between text-gray-600">
            <span>Discount</span>
            <span>-${{ pos.totalDiscount.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between text-gray-600">
            <span>Tax (8%)</span>
            <span>${{ pos.tax.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between font-semibold text-gray-900 text-base lg:text-lg pt-2 lg:pt-3 border-t">
            <span>Total</span>
            <span>${{ pos.grandTotal.toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <!-- Customer Selection -->
      <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 space-y-4 lg:space-y-5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <User class="w-4 h-4 text-gray-500" />
            <span class="text-sm">{{ pos.selectedCustomer?.name ?? 'Walk-in Customer' }}</span>
          </div>
          <button
            class="text-xs text-gray-600 hover:text-gray-900"
            @click="pos.setSelectedCustomer(null)"
          >
            Clear
          </button>
        </div>

        <div class="relative">
          <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
          <input
            v-model="customerQuery"
            placeholder="Search customers by name or phone"
            class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="max-h-40 overflow-auto border rounded-md divide-y">
          <button
            v-for="c in filteredCustomers"
            :key="c.id"
            class="w-full px-3 py-2 text-left hover:bg-gray-50"
            @click="pos.setSelectedCustomer(c)"
          >
            <div class="text-sm font-medium text-gray-900">
              {{ c.name }}
            </div>
            <div class="text-xs text-gray-500">{{ c.phone }} • {{ c.email }}</div>
          </button>
          <div v-if="filteredCustomers.length === 0" class="px-3 py-2 text-sm text-gray-500">
            No customers found
          </div>
        </div>

        <!-- Payment Section -->
        <div class="space-y-4 lg:space-y-5">
          <div>
            <label class="text-sm lg:text-base font-medium text-gray-700 mb-2 lg:mb-3 block">Payment Method</label>
            <select
              v-model="selectedPaymentMethod"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Select payment method</option>
              <option
                v-for="method in paymentMethods"
                :key="method.code"
                :value="method.code"
              >
                {{ method.icon }} {{ method.name }}
                <span v-if="method.fee_rate > 0" class="text-xs text-gray-500">
                  (+{{ method.fee_rate }}% fee)
                </span>
              </option>
            </select>
          </div>

          <!-- Card Payment Details -->
          <div v-if="isCardPayment" class="space-y-3">
            <div class="p-4 border rounded-lg bg-blue-50">
              <div class="flex items-center justify-between mb-3">
                <div class="text-sm text-blue-700">
                  💳 Secure Card Payment
                </div>
                <div v-if="detectedCardType" class="flex items-center text-xs text-gray-600">
                  <span class="mr-1">{{ getCardTypeIcon(detectedCardType) }}</span>
                  {{ detectedCardType.toUpperCase() }}
                </div>
              </div>
              
              <!-- Card Number with visual feedback -->
              <div class="mb-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">Card Number</label>
                <div class="relative">
                  <input
                    v-model="cardDetails.number"
                    placeholder="1234 5678 9012 3456"
                    :class="['w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500', 
                             cardValidation.number ? 'border-green-300 bg-green-50' : cardDetails.number && 'border-red-300 bg-red-50']"
                    maxlength="19"
                    @input="formatCardNumber"
                    @blur="validateCardNumber"
                  />
                  <div v-if="detectedCardType" class="absolute right-3 top-2.5 text-lg">
                    {{ getCardTypeIcon(detectedCardType) }}
                  </div>
                </div>
                <div v-if="cardDetails.number && !cardValidation.number" class="text-xs text-red-600 mt-1">
                  Please enter a valid card number
                </div>
              </div>

              <!-- Cardholder Name -->
              <div class="mb-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">Cardholder Name</label>
                <input
                  v-model="cardDetails.cardholder"
                  placeholder="John Doe"
                  :class="['w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500',
                           cardValidation.cardholder ? 'border-green-300 bg-green-50' : cardDetails.cardholder && 'border-red-300 bg-red-50']"
                  @input="validateCardholderName"
                />
                <div v-if="cardDetails.cardholder && !cardValidation.cardholder" class="text-xs text-red-600 mt-1">
                  Please enter the full name as shown on card
                </div>
              </div>

              <!-- Expiry and CVV -->
              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">Month</label>
                  <input
                    v-model="cardDetails.expMonth"
                    placeholder="MM"
                    :class="['w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500',
                             cardValidation.expMonth ? 'border-green-300 bg-green-50' : cardDetails.expMonth && 'border-red-300 bg-red-50']"
                    maxlength="2"
                    @input="formatExpiry"
                    @blur="validateExpiry"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">Year</label>
                  <input
                    v-model="cardDetails.expYear"
                    placeholder="YYYY"
                    :class="['w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500',
                             cardValidation.expYear ? 'border-green-300 bg-green-50' : cardDetails.expYear && 'border-red-300 bg-red-50']"
                    maxlength="4"
                    @input="formatExpiryYear"
                    @blur="validateExpiry"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">CVV</label>
                  <input
                    v-model="cardDetails.cvv"
                    placeholder="123"
                    :class="['w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500',
                             cardValidation.cvv ? 'border-green-300 bg-green-50' : cardDetails.cvv && 'border-red-300 bg-red-50']"
                    :maxlength="detectedCardType === 'amex' ? '4' : '3'"
                    type="password"
                    @input="formatCVV"
                    @blur="validateCVV"
                  />
                </div>
              </div>

              <!-- Validation summary -->
              <div v-if="cardDetails.expMonth && cardDetails.expYear && !cardValidation.expiry" class="text-xs text-red-600 mt-2">
                Please enter a valid expiry date (MM/YYYY)
              </div>

              <!-- Security notice -->
              <div class="flex items-center justify-between mt-3 pt-3 border-t border-blue-100">
                <div class="text-xs text-blue-600">
                  🔒 Your payment is secure and encrypted
                </div>
                <div class="text-xs text-gray-500">
                  Processing fee: {{ selectedMethod?.fee_rate || 0 }}%
                </div>
              </div>
            </div>
          </div>

          <!-- Cash Payment Details -->
          <div v-if="selectedPaymentMethod === 'cash'" class="space-y-3">
            <div class="p-3 border rounded-lg bg-green-50">
              <div class="text-sm text-green-700 mb-3">
                💵 Cash Payment
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount Received</label>
                <input
                  v-model.number="cashReceived"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-green-500"
                  @input="calculateChange"
                />
              </div>

              <div class="grid grid-cols-2 gap-2 mt-3 text-sm">
                <div class="bg-white p-2 rounded border">
                  <div class="text-gray-600">Total Due:</div>
                  <div class="font-semibold text-lg">RM {{ finalTotal.toFixed(2) }}</div>
                </div>
                <div class="bg-white p-2 rounded border">
                  <div class="text-gray-600">Change:</div>
                  <div :class="['font-semibold text-lg', changeAmount >= 0 ? 'text-green-600' : 'text-red-600']">
                    RM {{ Math.abs(changeAmount).toFixed(2) }}
                    <span v-if="changeAmount < 0" class="text-xs block text-red-500">(Insufficient)</span>
                  </div>
                </div>
              </div>

              <!-- Quick amount buttons -->
              <div class="mt-3">
                <div class="text-xs text-gray-600 mb-2">Quick amounts:</div>
                <div class="flex flex-wrap gap-1">
                  <button
                    v-for="amount in suggestedAmounts"
                    :key="amount"
                    @click="setCashAmount(amount)"
                    class="px-2 py-1 text-xs bg-white border rounded hover:bg-gray-50"
                  >
                    RM {{ amount.toFixed(0) }}
                  </button>
                  <button
                    @click="setCashAmount(finalTotal)"
                    class="px-2 py-1 text-xs bg-green-100 border border-green-300 rounded hover:bg-green-200 text-green-700"
                  >
                    Exact
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- TNG Payment Details -->
          <div v-if="selectedPaymentMethod === 'tng'" class="space-y-2">
            <input
              v-model="tngPhone"
              placeholder="TNG Phone Number (e.g., 01234567890)"
              class="w-full px-3 py-2 border rounded-lg text-sm"
              pattern="[0-9]{10,11}"
            />
          </div>

          <!-- Payment Summary -->
          <div v-if="selectedPaymentMethod" class="bg-gray-50 p-3 rounded-lg text-sm">
            <div class="flex justify-between">
              <span>Subtotal:</span>
              <span>RM {{ pos.grandTotal.toFixed(2) }}</span>
            </div>
            <div v-if="paymentFee > 0" class="flex justify-between text-orange-600">
              <span>Payment Fee:</span>
              <span>RM {{ paymentFee.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between font-semibold border-t pt-2">
              <span>Total:</span>
              <span>RM {{ finalTotal.toFixed(2) }}</span>
            </div>
          </div>

          <!-- Checkout Button -->
          <button
            :disabled="pos.cartItems.length === 0 || !selectedPaymentMethod || isProcessing"
            class="w-full inline-flex items-center justify-center bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-3 lg:py-4 rounded-lg hover:bg-blue-700 transition-colors text-base lg:text-lg font-medium shadow-lg"
            @click="checkout"
          >
            <ShoppingCart v-if="!isProcessing" class="w-5 h-5 mr-3" />
            <div v-if="isProcessing" class="w-5 h-5 mr-3 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            {{ isProcessing ? 'Processing...' : 'Complete Purchase' }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Receipt Modal -->
  <div v-if="showReceipt" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-4 border-b flex items-center justify-between">
        <h3 class="text-lg font-semibold">Receipt - {{ completedSale?.code }}</h3>
        <button @click="closeReceipt" class="text-gray-500 hover:text-gray-700">
          <X class="w-6 h-6" />
        </button>
      </div>
      <div class="p-4">
        <Receipt :sale="completedSale" />
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
import { computed, ref, onMounted } from 'vue'
import { usePOSStore } from '@/stores/pos.js'
import { useProductsStore } from '@/stores/products.js'
import { useCustomersStore } from '@/stores/customers.js'
import { Minus, Plus, ShoppingCart, Trash2, Search, User, X, AlertTriangle, ChevronDown } from 'lucide-vue-next'
import { useMessageModal } from '@/composables/useModal.js'
import Modal from '@/Components/Modal.vue'
import BarcodeScanner from '@/Components/BarcodeScanner.vue'
import Receipt from '@/Components/Receipt.vue'
import { useNotificationStore } from '@/stores/notifications'
import axios from 'axios'

const pos = usePOSStore()
const productsStore = useProductsStore()
const customersStore = useCustomersStore()
const notificationStore = useNotificationStore()
const modal = useMessageModal()

// Barcode scanner state
const showBarcodeScanner = ref(false)

// Receipt state
const showReceipt = ref(false)
const completedSale = ref(null)

// Inventory alerts state
const inventoryAlerts = ref([])
const showInventoryAlerts = ref(false)

// initial data
productsStore.fetchProducts().catch(() => {})
customersStore.fetchCustomers().catch(() => {})

const productQuery = ref('')
const selectedCategory = ref('')

// Get unique categories from products
const productCategories = computed(() => {
  const categories = productsStore.products.map(p => p.category).filter(Boolean)
  return [...new Set(categories)]
})

const filteredProducts = computed(() => {
  let products = productsStore.searchProducts(productQuery.value)

  if (selectedCategory.value) {
    products = products.filter(p => p.category === selectedCategory.value)
  }

  return products
})

const customerQuery = ref('')
const filteredCustomers = computed(() => {
  const q = customerQuery.value.trim()
  return customersStore.searchCustomers(q)
})

// Payment-related state
const paymentMethods = ref([])
const selectedPaymentMethod = ref('')
const isProcessing = ref(false)
const tngPhone = ref('')
const cardDetails = ref({
  number: '',
  cardholder: '',
  expMonth: '',
  expYear: '',
  cvv: ''
})

// Cash payment state
const cashReceived = ref(0)
const changeAmount = ref(0)

// Card payment state
const detectedCardType = ref('')
const cardValidation = ref({
  number: false,
  cardholder: false,
  expMonth: false,
  expYear: false,
  expiry: false,
  cvv: false
})

// Fetch inventory alerts
const fetchInventoryAlerts = async () => {
  try {
    const response = await axios.get('/api/inventory-alerts/store/1/pos-alerts')
    if (response.data.success) {
      inventoryAlerts.value = response.data.data.alerts || []
    }
  } catch (error) {
    console.error('Failed to load inventory alerts:', error)
  }
}

// Load payment methods and inventory alerts on mount
onMounted(async () => {
  try {
    const response = await axios.get('/api/payment-methods')
    paymentMethods.value = response.data.methods
  } catch (error) {
    console.error('Failed to load payment methods:', error)
  }

  // Fetch inventory alerts
  await fetchInventoryAlerts()

  // Set up periodic refresh for alerts (every 5 minutes)
  setInterval(fetchInventoryAlerts, 5 * 60 * 1000)
})

// Computed properties for payment
const isCardPayment = computed(() => {
  return ['card', 'digital'].includes(selectedPaymentMethod.value)
})

const selectedMethod = computed(() => {
  return paymentMethods.value.find(m => m.code === selectedPaymentMethod.value)
})

const paymentFee = computed(() => {
  if (!selectedMethod.value || selectedMethod.value.fee_rate === 0) return 0

  const feeRate = selectedMethod.value.fee_rate / 100
  const fee = pos.grandTotal * feeRate
  const fixedFee = selectedMethod.value.fee_fixed || 0

  return fee + fixedFee
})

const finalTotal = computed(() => {
  return pos.grandTotal + paymentFee.value
})

// Cash payment computed properties
const suggestedAmounts = computed(() => {
  const total = finalTotal.value
  const amounts = []

  // Round up to nearest 5, 10, 20, 50, 100
  const roundUps = [5, 10, 20, 50, 100]

  for (const roundUp of roundUps) {
    const rounded = Math.ceil(total / roundUp) * roundUp
    if (rounded > total && rounded <= total + 100) {
      amounts.push(rounded)
    }
  }

  // Remove duplicates and sort
  return [...new Set(amounts)].sort((a, b) => a - b).slice(0, 4)
})

// Card type detection
const detectCardType = (number) => {
  const cleanNumber = number.replace(/\D/g, '')
  
  // Card type patterns
  const patterns = {
    visa: /^4/,
    mastercard: /^5[1-5]|^2[2-7]/,
    amex: /^3[47]/,
    discover: /^6(?:011|5)/,
    diners: /^3[0689]/,
    jcb: /^35/
  }
  
  for (const [type, pattern] of Object.entries(patterns)) {
    if (pattern.test(cleanNumber)) {
      return type
    }
  }
  
  return ''
}

const getCardTypeIcon = (type) => {
  const icons = {
    visa: '💳',
    mastercard: '💳', 
    amex: '💎',
    discover: '🔍',
    diners: '🍴',
    jcb: '🏯'
  }
  return icons[type] || '💳'
}

// Enhanced card formatting helpers
const formatCardNumber = (event) => {
  let value = event.target.value.replace(/\D/g, '')
  
  // Detect card type
  detectedCardType.value = detectCardType(value)
  
  // Apply formatting based on card type
  if (detectedCardType.value === 'amex') {
    // American Express: 4-6-5 format
    value = value.replace(/(\d{4})(\d{6})(\d{5})/, '$1 $2 $3')
  } else {
    // Others: 4-4-4-4 format
    value = value.replace(/(\d{4})(?=\d)/g, '$1 ')
  }
  
  event.target.value = value
  cardDetails.value.number = value
  
  // Validate card number
  validateCardNumber()
}

const formatExpiry = (event) => {
  let value = event.target.value.replace(/\D/g, '')
  if (value.length >= 2) {
    value = value.substring(0, 2)
  }
  // Auto-add leading zero for single digit months 1-9
  if (value.length === 1 && parseInt(value) > 1) {
    value = '0' + value
  }
  event.target.value = value
  cardDetails.value.expMonth = value
  cardValidation.value.expMonth = value.length === 2 && parseInt(value) >= 1 && parseInt(value) <= 12
}

const formatExpiryYear = (event) => {
  let value = event.target.value.replace(/\D/g, '')
  if (value.length <= 4) {
    event.target.value = value
    cardDetails.value.expYear = value
    cardValidation.value.expYear = value.length === 4 && parseInt(value) >= new Date().getFullYear()
  }
}

const formatCVV = (event) => {
  let value = event.target.value.replace(/\D/g, '')
  const maxLength = detectedCardType.value === 'amex' ? 4 : 3
  if (value.length <= maxLength) {
    event.target.value = value
    cardDetails.value.cvv = value
    cardValidation.value.cvv = value.length === maxLength
  }
}

// Individual validation functions
const validateCardNumber = () => {
  const cleanNumber = cardDetails.value.number.replace(/\s/g, '')
  
  // Basic length check and Luhn algorithm
  if (cleanNumber.length < 13 || cleanNumber.length > 19) {
    cardValidation.value.number = false
    return false
  }
  
  // Luhn algorithm validation
  const isValid = luhnCheck(cleanNumber)
  cardValidation.value.number = isValid
  return isValid
}

const validateCardholderName = () => {
  const name = cardDetails.value.cardholder.trim()
  const isValid = name.length >= 2 && /^[a-zA-Z\s]+$/.test(name)
  cardValidation.value.cardholder = isValid
  return isValid
}

const validateExpiry = () => {
  const month = parseInt(cardDetails.value.expMonth)
  const year = parseInt(cardDetails.value.expYear)
  const currentYear = new Date().getFullYear()
  const currentMonth = new Date().getMonth() + 1
  
  const validMonth = month >= 1 && month <= 12
  const validYear = year >= currentYear
  const notExpired = year > currentYear || (year === currentYear && month >= currentMonth)
  
  cardValidation.value.expMonth = validMonth
  cardValidation.value.expYear = validYear
  cardValidation.value.expiry = validMonth && validYear && notExpired
  
  return cardValidation.value.expiry
}

const validateCVV = () => {
  const expectedLength = detectedCardType.value === 'amex' ? 4 : 3
  const isValid = cardDetails.value.cvv.length === expectedLength && /^\d+$/.test(cardDetails.value.cvv)
  cardValidation.value.cvv = isValid
  return isValid
}

// Luhn algorithm for card validation
const luhnCheck = (num) => {
  let arr = (num + '')
    .split('')
    .reverse()
    .map(x => parseInt(x))
  let lastDigit = arr.splice(0, 1)[0]
  let sum = arr.reduce((acc, val, i) => {
    return acc + (i % 2 !== 0 ? val : (val *= 2) > 9 ? val - 9 : val)
  }, 0)
  return (sum + lastDigit) % 10 === 0
}

const validateCardDetails = async () => {
  if (!isCardPayment.value) return true

  const validations = [
    { check: validateCardNumber(), message: 'Please enter a valid card number' },
    { check: validateCardholderName(), message: 'Please enter the cardholder name' },
    { check: validateExpiry(), message: 'Please enter a valid expiry date' },
    { check: validateCVV(), message: 'Please enter a valid CVV' }
  ]

  for (const validation of validations) {
    if (!validation.check) {
      await modal.showError(validation.message)
      return false
    }
  }

  return true
}

const changeQty = (itemId, qty, discount) => {
  pos.updateCartItem(itemId, qty, discount)
}

// Cash payment methods
const calculateChange = () => {
  changeAmount.value = cashReceived.value - finalTotal.value
}

const setCashAmount = (amount) => {
  cashReceived.value = amount
  calculateChange()
}

const checkout = async () => {
  if (pos.cartItems.length === 0 || !selectedPaymentMethod.value) return

  // Validate cash payment if needed
  if (selectedPaymentMethod.value === 'cash') {
    if (cashReceived.value <= 0) {
      await modal.showError('Please enter the amount received from customer')
      return
    }
    if (changeAmount.value < 0) {
      await modal.showError('Insufficient cash received. Please collect more money from customer.')
      return
    }
  }

  // Validate card details if needed
  if (!(await validateCardDetails())) return

  isProcessing.value = true

  try {
    // First create the sale
    const saleResponse = await pos.checkout({
      storeId: 1,
      paymentMethod: selectedPaymentMethod.value
    })

    if (!saleResponse?.id) {
      throw new Error('Failed to create sale')
    }

    // Then process the payment
    const paymentData = {
      sale_id: saleResponse.id,
      method: selectedPaymentMethod.value,
      currency: 'MYR'
    }

    // Add method-specific data
    if (isCardPayment.value) {
      const cleanCardNumber = cardDetails.value.number.replace(/\s/g, '')
      paymentData.card_number = cleanCardNumber
      paymentData.cardholder_name = cardDetails.value.cardholder
      paymentData.exp_month = parseInt(cardDetails.value.expMonth)
      paymentData.exp_year = parseInt(cardDetails.value.expYear)
      paymentData.cvv = cardDetails.value.cvv

      // For Stripe integration
      if (selectedPaymentMethod.value === 'card') {
        paymentData.payment_method_id = 'card_simulation'
      }
    }

    if (selectedPaymentMethod.value === 'tng') {
      paymentData.phone = tngPhone.value
    }

    if (selectedPaymentMethod.value === 'cash') {
      paymentData.cash_received = cashReceived.value
      paymentData.change_amount = changeAmount.value
    }

    const paymentResponse = await axios.post('/api/payments/process', paymentData)

    if (paymentResponse.data.payment.status === 'completed') {
      // Payment successful - fetch sale with payment details and show receipt
      try {
        const saleWithPaymentResponse = await axios.get(`/api/sales/${saleResponse.id}`)
        completedSale.value = saleWithPaymentResponse.data
      } catch (error) {
        console.warn('Failed to fetch sale with payment details:', error)
        // Fallback to original sale data
        completedSale.value = saleResponse
      }

      // Reset form and cart
      resetPaymentForm()
      pos.clearCart()

      // Show receipt modal
      showReceipt.value = true

    } else if (paymentResponse.data.requires_action) {
      await modal.showWarning('Payment requires additional verification. Please check your payment method.')
    } else {
      await modal.showError('Payment failed. Please try again or use a different payment method.')
    }

  } catch (error) {
    console.error('Checkout failed:', error)
    const errorMessage = error.response?.data?.error ||
                         error.response?.data?.message ||
                         error.message ||
                         'Payment processing failed'

    await modal.showError(`Checkout Failed\n\n${errorMessage}\n\nPlease try again or contact support.`)
  } finally {
    isProcessing.value = false
  }
}

const resetPaymentForm = () => {
  selectedPaymentMethod.value = ''
  tngPhone.value = ''
  cashReceived.value = 0
  changeAmount.value = 0
  cardDetails.value = {
    number: '',
    cardholder: '',
    expMonth: '',
    expYear: '',
    cvv: ''
  }
  detectedCardType.value = ''
  cardValidation.value = {
    number: false,
    cardholder: false,
    expMonth: false,
    expYear: false,
    expiry: false,
    cvv: false
  }
}

// Barcode scanner handlers
const handleBarcodeScanned = (data) => {
  const { code, product } = data

  if (product) {
    pos.addToCart(product)
    notificationStore.success('Product Added', `${product.name} added to cart`)
  } else {
    // Product not found, search by barcode
    productQuery.value = code
  }

  // Auto-hide scanner after successful scan
  showBarcodeScanner.value = false
}

const handleScanError = (error) => {
  notificationStore.error('Scan Error', error.error || 'Failed to scan barcode')
}

// Receipt functions
const closeReceipt = () => {
  showReceipt.value = false
  completedSale.value = null
}
</script>

<style scoped>
/* Mobile optimizations */
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

/* Touch optimization */
.touch-manipulation {
  touch-action: manipulation;
}

/* Mobile responsive adjustments */
@media (max-width: 1024px) {
  /* Stack layout vertically on tablets */
  .flex-col.lg\:flex-row {
    height: 100vh;
    max-height: 100vh;
  }

  /* Adjust grid columns for smaller screens */
  .grid-cols-2.sm\:grid-cols-3.md\:grid-cols-4.lg\:grid-cols-3.xl\:grid-cols-4 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  /* Better spacing on tablets */
  .space-y-4.lg\:space-y-6 {
    gap: 1.5rem;
  }
}

@media (max-width: 768px) {
  /* Mobile phone adjustments */
  .grid-cols-2.sm\:grid-cols-3.md\:grid-cols-4.lg\:grid-cols-3.xl\:grid-cols-4 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  /* Larger touch targets on mobile */
  .active\:scale-95:active {
    transform: scale(0.98);
  }

  /* Reduce padding on mobile for more space */
  .p-4.lg\:p-6 {
    padding: 1rem;
  }

  /* Improve mobile scrolling */
  .overflow-y-auto {
    -webkit-overflow-scrolling: touch;
  }
}

/* Ensure proper spacing on different screen sizes */
@media (min-width: 1280px) {
  .grid-cols-2.sm\:grid-cols-3.md\:grid-cols-4.lg\:grid-cols-3.xl\:grid-cols-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

/* Smooth transitions for better UX */
.transition-all {
  transition: all 0.15s ease-in-out;
}

/* Focus states for accessibility */
button:focus-visible,
input:focus-visible {
  outline: 2px solid #3B82F6;
  outline-offset: 2px;
}
</style>
