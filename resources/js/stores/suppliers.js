import { defineStore } from 'pinia'
import { ref } from 'vue'
// import type { Supplier, RelatedProductSummary } from '@/types'
import axios from 'axios'

export const useSuppliersStore = defineStore('suppliers', () => {
  const suppliers = ref([])
  const isLoading = ref(false)
  const errorMessage = ref(null)
  const selectedSupplier = ref(null)

  const mapApiSupplierToUi = (s) => ({
    id: s.id,
    name: s.name,
    contactPhone: s.contact_phone ?? s.contactPhone ?? '',
    contactEmail: s.contact_email ?? s.contactEmail ?? '',
    address: s.address ?? '',
    productsCount: Number(
      s.products_count ?? s.productsCount ?? (Array.isArray(s.products) ? s.products.length : 0)
    ),
    products: Array.isArray(s.products)
      ? s.products.map(
          p => ({ id: p.id, name: p.name, sku: p.sku })
        )
      : undefined,
  })

  const fetchSuppliers = async () => {
    isLoading.value = true
    errorMessage.value = null
    try {
      const { data } = await axios.get('/suppliers')
      const list = Array.isArray(data?.data) ? data.data : data
      suppliers.value = list.map(mapApiSupplierToUi)
    } catch (err) {
      errorMessage.value = err?.response?.data?.message || 'Failed to load suppliers'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const fetchSupplierDetails = async (id) => {
    const { data } = await axios.get(`/suppliers/${id}`)
    const details = mapApiSupplierToUi(data)
    selectedSupplier.value = details
    const idx = suppliers.value.findIndex(s => String(s.id) === String(id))
    if (idx !== -1) {
      suppliers.value[idx] = {
        ...suppliers.value[idx],
        productsCount: details.products?.length ?? details.productsCount,
      }
    }
    return details
  }

  const addSupplier = async (payload) => {
    try {
      const body = {
        name: payload.name,
        contact_phone: payload.contactPhone ?? null,
        contact_email: payload.contactEmail ?? null,
        address: payload.address ?? null,
      }
      const { data } = await axios.post('/suppliers', body)
      suppliers.value.unshift(mapApiSupplierToUi(data))
      return { success: true, data }
    } catch (err) {
      const errorMessage =
        err?.response?.data?.message ||
        err?.response?.data?.errors?.name?.[0] ||
        err?.response?.data?.errors?.contact_phone?.[0] ||
        err?.response?.data?.errors?.contact_email?.[0] ||
        err?.response?.data?.errors?.address?.[0] ||
        'Failed to add supplier'
      throw new Error(errorMessage)
    }
  }

  const updateSupplier = async (id, updates) => {
    try {
      const body = {}
      if (updates.name !== undefined) body.name = updates.name
      if (updates.contactPhone !== undefined) body.contact_phone = updates.contactPhone
      if (updates.contactEmail !== undefined) body.contact_email = updates.contactEmail
      if (updates.address !== undefined) body.address = updates.address
      const { data } = await axios.put(`/suppliers/${id}`, body)
      const updated = mapApiSupplierToUi(data)
      const index = suppliers.value.findIndex(s => String(s.id) === String(id))
      if (index !== -1) suppliers.value[index] = updated
      return { success: true, data: updated }
    } catch (err) {
      const errorMessage =
        err?.response?.data?.message ||
        err?.response?.data?.errors?.name?.[0] ||
        err?.response?.data?.errors?.contact_phone?.[0] ||
        err?.response?.data?.errors?.contact_email?.[0] ||
        err?.response?.data?.errors?.address?.[0] ||
        'Failed to update supplier'
      throw new Error(errorMessage)
    }
  }

  const deleteSupplier = async (id) => {
    try {
      await axios.delete(`/suppliers/${id}`)
      const index = suppliers.value.findIndex(s => String(s.id) === String(id))
      if (index !== -1) suppliers.value.splice(index, 1)
      return { success: true }
    } catch (err) {
      const errorMessage = err?.response?.data?.message || 'Failed to delete supplier'
      throw new Error(errorMessage)
    }
  }

  const searchSuppliers = (query) => {
    if (!query) return suppliers.value
    const q = query.toLowerCase()
    return suppliers.value.filter(
      s => s.name.toLowerCase().includes(q) || (s.address ?? '').toLowerCase().includes(q)
    )
  }

  return {
    suppliers,
    isLoading,
    errorMessage,
    selectedSupplier,
    fetchSuppliers,
    fetchSupplierDetails,
    addSupplier,
    updateSupplier,
    deleteSupplier,
    searchSuppliers,
  }
})
