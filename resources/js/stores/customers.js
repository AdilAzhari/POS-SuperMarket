import { defineStore } from 'pinia'
import { ref } from 'vue'
// import type { Customer } from '@/types'
import axios from 'axios'

export const useCustomersStore = defineStore('customers', () => {
  const customers = ref([])
  const isLoading = ref(false)
  const errorMessage = ref(null)

  const mapApiCustomerToUi = (c) => ({
    id: String(c.id),
    name: c.name,
    phone: c.phone,
    email: c.email,
    address: c.address ?? '',
    totalPurchases: Number(c.total_purchases ?? 0),
    totalSpent: Number(c.total_spent ?? 0),
    lastPurchase: c.last_purchase_at
      ? new Date(c.last_purchase_at).toISOString().split('T')[0]
      : undefined,
    joinDate: c.created_at ? new Date(c.created_at).toISOString().split('T')[0] : undefined,
    status: c.status ?? 'active',
  })

  const fetchCustomers = async () => {
    isLoading.value = true
    errorMessage.value = null
    try {
      const { data } = await axios.get('/api/customers')
      const list = Array.isArray(data?.data) ? data.data : data
      customers.value = list.map(mapApiCustomerToUi)
    } catch (err) {
      errorMessage.value = err?.response?.data?.message || 'Failed to load customers'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const addCustomer = async (customer) => {
    const payload = {
      name: customer.name,
      phone: customer.phone,
      email: customer.email,
      address: customer.address ?? '',
      status: customer.status ?? 'active',
    }
    const { data } = await axios.post('/api/customers', payload)
    customers.value.unshift(mapApiCustomerToUi(data))
  }

  const updateCustomer = async (id, updates) => {
    const payload = {}
    if (updates.name !== undefined) payload.name = updates.name
    if (updates.phone !== undefined) payload.phone = updates.phone
    if (updates.email !== undefined) payload.email = updates.email
    if (updates.address !== undefined) payload.address = updates.address
    if (updates.status !== undefined) payload.status = updates.status

    const { data } = await axios.put(`/api/customers/${id}`, payload)
    const updated = mapApiCustomerToUi(data)
    const index = customers.value.findIndex(c => c.id === id)
    if (index !== -1) customers.value[index] = updated
  }

  const deleteCustomer = async (id) => {
    await axios.delete(`/api/customers/${id}`)
    const index = customers.value.findIndex(c => c.id === id)
    if (index !== -1) customers.value.splice(index, 1)
  }

  const searchCustomers = (query) => {
    if (!query) return customers.value
    const lowercaseQuery = query.toLowerCase()
    return customers.value.filter(
      customer =>
        customer.name.toLowerCase().includes(lowercaseQuery) ||
        customer.phone.includes(query) ||
        customer.email.toLowerCase().includes(lowercaseQuery)
    )
  }

  return {
    customers,
    isLoading,
    errorMessage,
    fetchCustomers,
    addCustomer,
    updateCustomer,
    deleteCustomer,
    searchCustomers,
  }
})
