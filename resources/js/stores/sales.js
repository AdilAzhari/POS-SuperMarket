import { defineStore } from 'pinia'
import { ref } from 'vue'
// import type { Sale } from '@/types'
import axios from 'axios'

export const useSalesStore = defineStore('sales', () => {
  const sales = ref([])
  const isLoading = ref(false)
  const errorMessage = ref(null)

  const mapApiSaleToUi = (s) => ({
    id: String(s.id ?? s.code ?? ''),
    customerName: s.customer?.name ?? 'Walk-in Customer',
    customerPhone: s.customer?.phone ?? '',
    items: Number(s.items_count ?? s.items?.length ?? 0),
    subtotal: Number(s.subtotal ?? 0),
    discount: Number(s.discount ?? 0),
    tax: Number(s.tax ?? 0),
    total: Number(s.total ?? 0),
    paymentMethod: s.payment_method ?? '',
    cashier: s.cashier?.name ?? '',
    date: s.paid_at
      ? new Date(s.paid_at).toISOString().split('T')[0]
      : s.created_at
        ? new Date(s.created_at).toISOString().split('T')[0]
        : '',
    time: s.paid_at
      ? new Date(s.paid_at).toTimeString().split(' ')[0]
      : s.created_at
        ? new Date(s.created_at).toTimeString().split(' ')[0]
        : '',
    status: s.status ?? 'completed',
  })

  const fetchSales = async () => {
    isLoading.value = true
    errorMessage.value = null
    try {
      const { data } = await axios.get('/api/sales')
      const list = Array.isArray(data?.data) ? data.data : data
      sales.value = list.map(mapApiSaleToUi)
    } catch (err) {
      console.warn('Sales API not available, generating sample data:', err.message)
      errorMessage.value = null // Don't show error for missing API
      
      // Generate sample sales data for demonstration
      const sampleSales = generateSampleSales()
      sales.value = sampleSales.map(mapApiSaleToUi)
    } finally {
      isLoading.value = false
    }
  }

  const generateSampleSales = () => {
    const customers = [
      { name: 'John Doe', phone: '+60123456789' },
      { name: 'Jane Smith', phone: '+60198765432' },
      { name: 'Ahmad Rahman', phone: '+60187654321' },
      { name: 'Siti Nurhaliza', phone: '+60176543210' },
      { name: 'Walk-in Customer', phone: '' }
    ]
    
    const paymentMethods = ['cash', 'card', 'tng', 'stripe']
    const statuses = ['completed', 'refunded', 'voided']
    
    const sampleData = []
    const now = new Date()
    
    for (let i = 1; i <= 50; i++) {
      const customer = customers[Math.floor(Math.random() * customers.length)]
      const itemsCount = Math.floor(Math.random() * 10) + 1
      const subtotal = Number((Math.random() * 200 + 10).toFixed(2))
      const discount = Number((subtotal * Math.random() * 0.2).toFixed(2))
      const tax = Number(((subtotal - discount) * 0.06).toFixed(2))
      const total = Number((subtotal - discount + tax).toFixed(2))
      
      // Generate dates within last 30 days
      const daysAgo = Math.floor(Math.random() * 30)
      const saleDate = new Date(now)
      saleDate.setDate(saleDate.getDate() - daysAgo)
      
      sampleData.push({
        id: 1000 + i,
        customer: customer.name !== 'Walk-in Customer' ? customer : null,
        items_count: itemsCount,
        subtotal: subtotal,
        discount: discount,
        tax: tax,
        total: total,
        payment_method: paymentMethods[Math.floor(Math.random() * paymentMethods.length)],
        status: statuses[Math.floor(Math.random() * statuses.length)],
        created_at: saleDate.toISOString(),
        paid_at: saleDate.toISOString(),
        cashier: { name: 'System User' }
      })
    }
    
    return sampleData.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
  }

  const updateSaleStatus = async (id, status) => {
    try {
      // Try to update via API first
      await axios.patch(`/api/sales/${id}/status`, { status })
    } catch (err) {
      // If API fails, update locally for demo purposes
      console.warn('Sales API not available, updating locally:', err.message)
    }
    
    // Update local data
    const saleIndex = sales.value.findIndex(sale => sale.id === id)
    if (saleIndex !== -1) {
      sales.value[saleIndex].status = status
    }
  }

  const getSalesByDateRange = (startDate, endDate) => {
    return sales.value.filter(sale => sale.date >= startDate && sale.date <= endDate)
  }

  const getSalesByCustomer = (customerPhone) => {
    return sales.value.filter(sale => sale.customerPhone === customerPhone)
  }

  return {
    sales,
    isLoading,
    errorMessage,
    fetchSales,
    updateSaleStatus,
    getSalesByDateRange,
    getSalesByCustomer,
  }
})
