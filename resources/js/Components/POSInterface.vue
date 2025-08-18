<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-4">
      <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="relative">
          <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
          <input
            v-model="productQuery"
            placeholder="Scan barcode or search products"
            class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
        <button
          v-for="p in filteredProducts"
          :key="p.id"
          class="bg-white rounded-lg shadow-sm p-3 text-left hover:ring-2 hover:ring-blue-500 transition"
          @click="pos.addToCart(p)"
        >
          <img :src="p.image" :alt="p.name" class="w-full h-28 object-cover rounded-md mb-2" />
          <div class="font-medium text-gray-900 truncate">
            {{ p.name }}
          </div>
          <div class="text-xs text-gray-500">{{ p.sku }} • {{ p.category }}</div>
          <div class="mt-1 font-semibold text-blue-600">${{ p.price.toFixed(2) }}</div>
        </button>
      </div>
    </div>

    <div class="space-y-4">
      <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-semibold text-gray-900">Cart</h3>
          <button class="text-sm text-gray-600 hover:text-gray-900" @click="pos.clearCart">
            Clear
          </button>
        </div>

        <div v-if="pos.cartItems.length === 0" class="text-sm text-gray-500">No items in cart.</div>
        <div v-else class="space-y-3">
          <div
            v-for="item in pos.cartItems"
            :key="item.id"
            class="flex items-center justify-between"
          >
            <div class="min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ item.name }}
              </p>
              <p class="text-xs text-gray-500">${{ item.price.toFixed(2) }} each</p>
            </div>
            <div class="flex items-center gap-2">
              <button
                class="p-1 rounded border hover:bg-gray-50"
                @click="changeQty(item.id, item.quantity - 1, item.discount)"
              >
                <Minus class="w-4 h-4" />
              </button>
              <span class="w-6 text-center text-sm">{{ item.quantity }}</span>
              <button
                class="p-1 rounded border hover:bg-gray-50"
                @click="changeQty(item.id, item.quantity + 1, item.discount)"
              >
                <Plus class="w-4 h-4" />
              </button>
              <button
                class="p-1 text-red-600 hover:text-red-800"
                @click="pos.removeFromCart(item.id)"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
            <div class="w-20 text-right font-medium">${{ item.lineTotal.toFixed(2) }}</div>
          </div>
        </div>

        <div class="mt-4 border-t pt-3 space-y-1 text-sm">
          <div class="flex justify-between">
            <span>Items</span>
            <span>{{ pos.cartItemCount }}</span>
          </div>
          <div class="flex justify-between">
            <span>Subtotal</span>
            <span>${{ pos.subtotal.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between">
            <span>Discount</span>
            <span>-${{ pos.totalDiscount.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between">
            <span>Tax (8%)</span>
            <span>${{ pos.tax.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between font-semibold text-gray-900 text-base pt-2">
            <span>Total</span>
            <span>${{ pos.grandTotal.toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-4 space-y-3">
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

        <div class="space-y-3">
          <div>
            <label class="text-sm font-medium text-gray-700 mb-2 block">Payment Method</label>
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
          <div v-if="isCardPayment" class="space-y-2">
            <div class="grid grid-cols-2 gap-2">
              <input
                v-model="cardDetails.number"
                placeholder="Card Number"
                class="px-3 py-2 border rounded-lg text-sm"
                maxlength="19"
              />
              <input
                v-model="cardDetails.cardholder"
                placeholder="Cardholder Name"
                class="px-3 py-2 border rounded-lg text-sm"
              />
            </div>
            <div class="grid grid-cols-3 gap-2">
              <input
                v-model="cardDetails.expMonth"
                placeholder="MM"
                class="px-3 py-2 border rounded-lg text-sm"
                maxlength="2"
              />
              <input
                v-model="cardDetails.expYear"
                placeholder="YYYY"
                class="px-3 py-2 border rounded-lg text-sm"
                maxlength="4"
              />
              <input
                v-model="cardDetails.cvv"
                placeholder="CVV"
                class="px-3 py-2 border rounded-lg text-sm"
                maxlength="3"
                type="password"
              />
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

          <button
            :disabled="pos.cartItems.length === 0 || !selectedPaymentMethod || isProcessing"
            class="w-full inline-flex items-center justify-center bg-blue-600 disabled:opacity-50 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
            @click="checkout"
          >
            <ShoppingCart v-if="!isProcessing" class="w-4 h-4 mr-2" />
            <div v-if="isProcessing" class="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            {{ isProcessing ? 'Processing...' : 'Checkout' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { usePOSStore } from '@/stores/pos.js'
import { useProductsStore } from '@/stores/products.js'
import { useCustomersStore } from '@/stores/customers.js'
import { Minus, Plus, ShoppingCart, Trash2, Search, User } from 'lucide-vue-next'
import axios from 'axios'

const pos = usePOSStore()
const productsStore = useProductsStore()
const customersStore = useCustomersStore()

// initial data
productsStore.fetchProducts().catch(() => {})
customersStore.fetchCustomers().catch(() => {})

const productQuery = ref('')
const filteredProducts = computed(() => productsStore.searchProducts(productQuery.value))

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

// Load payment methods on mount
onMounted(async () => {
  try {
    const response = await axios.get('/api/payment-methods')
    paymentMethods.value = response.data.methods
  } catch (error) {
    console.error('Failed to load payment methods:', error)
  }
})

// Computed properties for payment
const isCardPayment = computed(() => {
  return ['visa', 'mastercard', 'amex', 'stripe'].includes(selectedPaymentMethod.value)
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

const changeQty = (itemId, qty, discount) => {
  pos.updateCartItem(itemId, qty, discount)
}

const checkout = async () => {
  if (pos.cartItems.length === 0 || !selectedPaymentMethod.value) return
  
  isProcessing.value = true
  
  try {
    // First create the sale
    const saleResponse = await pos.checkout({ 
      storeId: 1, 
      paymentMethod: selectedPaymentMethod.value 
    })
    
    // Then process the payment
    const paymentData = {
      sale_id: saleResponse.id,
      method: selectedPaymentMethod.value,
      currency: 'MYR'
    }
    
    // Add method-specific data
    if (isCardPayment.value) {
      paymentData.card_number = cardDetails.value.number
      paymentData.cardholder_name = cardDetails.value.cardholder
      paymentData.exp_month = parseInt(cardDetails.value.expMonth)
      paymentData.exp_year = parseInt(cardDetails.value.expYear)
      paymentData.cvv = cardDetails.value.cvv
    }
    
    if (selectedPaymentMethod.value === 'tng') {
      paymentData.phone = tngPhone.value
    }
    
    const paymentResponse = await axios.post('/api/payments/process', paymentData)
    
    if (paymentResponse.data.payment.status === 'completed') {
      // Payment successful
      alert(`Payment successful! 
        Method: ${selectedMethod.value.name}
        Amount: RM ${finalTotal.value.toFixed(2)}
        Payment ID: ${paymentResponse.data.payment.payment_code}`)
      
      // Reset form
      resetPaymentForm()
    } else if (paymentResponse.data.requires_action) {
      alert('Payment requires additional action. Please check your payment method.')
    } else {
      alert('Payment failed. Please try again.')
    }
    
  } catch (error) {
    console.error('Checkout failed:', error)
    const errorMessage = error.response?.data?.error || error.response?.data?.message || 'Payment failed'
    alert(`Checkout failed: ${errorMessage}`)
  } finally {
    isProcessing.value = false
  }
}

const resetPaymentForm = () => {
  selectedPaymentMethod.value = ''
  tngPhone.value = ''
  cardDetails.value = {
    number: '',
    cardholder: '',
    expMonth: '',
    expYear: '',
    cvv: ''
  }
}
</script>
