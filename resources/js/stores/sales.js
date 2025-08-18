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
      const { data } = await axios.get('/sales')
      const list = Array.isArray(data?.data) ? data.data : data
      sales.value = list.map(mapApiSaleToUi)
    } catch (err) {
      errorMessage.value = err?.response?.data?.message || 'Failed to load sales'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const updateSaleStatus = async (id, status) => {
    const { data } = await axios.put(`/sales/${id}`, { status })
    const updated = mapApiSaleToUi(data)
    const index = sales.value.findIndex(s => s.id === id)
    if (index !== -1) sales.value[index] = updated
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
