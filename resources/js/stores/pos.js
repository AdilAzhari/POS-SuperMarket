import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
// import type { CartItem, Customer, Product } from '@/types'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'

export const usePOSStore = defineStore('pos', () => {
  const cartItems = ref([])
  const selectedCustomer = ref(null)

  const cartTotal = computed(() => cartItems.value.reduce((sum, item) => sum + item.lineTotal, 0))

  const cartItemCount = computed(() =>
    cartItems.value.reduce((sum, item) => sum + item.quantity, 0)
  )

  const subtotal = computed(() =>
    cartItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
  )

  const totalDiscount = computed(() =>
    cartItems.value.reduce((sum, item) => sum + item.discount, 0)
  )

  const tax = computed(() => subtotal.value * 0.08)

  const grandTotal = computed(() => subtotal.value - totalDiscount.value + tax.value)

  const addToCart = (product) => {
    const existingItem = cartItems.value.find(item => item.productId === product.id)

    if (existingItem) {
      updateCartItem(existingItem.id, existingItem.quantity + 1)
    } else {
      const newItem = {
        id: `cart-${Date.now()}-${product.id}`,
        productId: product.id,
        name: product.name,
        price: product.price,
        quantity: 1,
        discount: 0,
        lineTotal: product.price,
      }
      cartItems.value.push(newItem)
    }
  }

  const updateCartItem = (itemId, quantity, discount = 0) => {
    const itemIndex = cartItems.value.findIndex(item => item.id === itemId)
    if (itemIndex !== -1) {
      const item = cartItems.value[itemIndex]
      if (quantity <= 0) {
        cartItems.value.splice(itemIndex, 1)
      } else {
        item.quantity = quantity
        item.discount = discount
        item.lineTotal = item.price * quantity - discount
      }
    }
  }

  const removeFromCart = (itemId) => {
    const index = cartItems.value.findIndex(item => item.id === itemId)
    if (index !== -1) {
      cartItems.value.splice(index, 1)
    }
  }

  const clearCart = () => {
    cartItems.value = []
    selectedCustomer.value = null
  }

  const checkout = async (options) => {
    if (cartItems.value.length === 0) {
      throw new Error('Cart is empty')
    }
    
    const page = usePage()
    const currentUser = page.props.auth?.user
    
    if (!currentUser) {
      throw new Error('User not authenticated')
    }
    
    const storeId = Number(options?.storeId ?? 1)
    const payload = {
      store_id: storeId,
      customer_id: selectedCustomer.value ? Number(selectedCustomer.value.id) : null,
      cashier_id: Number(currentUser.id),
      payment_method: options?.paymentMethod ?? 'cash',
      items: cartItems.value.map(i => ({
        product_id: Number(i.productId),
        price: i.price,
        quantity: i.quantity,
        discount: i.discount,
      })),
      discount: totalDiscount.value,
      tax: tax.value,
      loyalty_reward_id: options?.loyaltyReward?.id || null,
      loyalty_discount: options?.loyaltyReward?.discount || 0,
    }
    
    try {
      const response = await axios.post('/api/sales', payload)
      
      if (!response.data) {
        throw new Error('Invalid response from server')
      }
      
      // Don't clear cart here - let the payment processing handle it
      return response.data
    } catch (error) {
      console.error('Failed to create sale:', error)
      throw error
    }
  }

  const setSelectedCustomer = (customer) => {
    selectedCustomer.value = customer
  }

  return {
    cartItems,
    selectedCustomer,
    cartTotal,
    cartItemCount,
    subtotal,
    totalDiscount,
    tax,
    grandTotal,
    addToCart,
    updateCartItem,
    removeFromCart,
    clearCart,
    setSelectedCustomer,
    checkout,
  }
})
