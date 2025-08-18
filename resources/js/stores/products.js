import { defineStore } from 'pinia'
import { ref } from 'vue'
// import type { Product } from '@/types'
import axios from 'axios'

export const useProductsStore = defineStore('products', () => {
  const products = ref([])
  const isLoading = ref(false)
  const errorMessage = ref(null)

  const mapApiProductToUi = (p) => ({
    id: String(p.id),
    name: p.name,
    sku: p.sku,
    barcode: p.barcode,
    price: Number(p.price ?? 0),
    cost: Number(p.cost ?? 0),
    stock: Number(p.pivot?.stock ?? p.stock ?? 0),
    category: p.category?.name ?? '',
    supplier: p.supplier?.name ?? '',
    image: p.image_url ?? '',
    active: Boolean(p.active),
    lowStockThreshold: Number(p.pivot?.low_stock_threshold ?? p.low_stock_threshold ?? 0),
  })

  const fetchProducts = async () => {
    isLoading.value = true
    errorMessage.value = null
    try {
      const { data } = await axios.get('/api/products')
      const list = Array.isArray(data?.data) ? data.data : data
      products.value = list.map(mapApiProductToUi)
    } catch (err) {
      errorMessage.value = err?.response?.data?.message || 'Failed to load products'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const addProduct = async (product) => {
    const payload = {
      name: product.name,
      sku: product.sku,
      barcode: product.barcode,
      price: product.price,
      cost: product.cost,
      active: product.active,
      low_stock_threshold: product.lowStockThreshold,
      image_url: product.image,
      // category_id and supplier_id could be provided when UI supports selecting them
    }
    const { data } = await axios.post('/api/products', payload)
    products.value.unshift(mapApiProductToUi(data))
  }

  const updateProduct = async (id, updates) => {
    const payload = {}
    if (updates.name !== undefined) payload.name = updates.name
    if (updates.sku !== undefined) payload.sku = updates.sku
    if (updates.barcode !== undefined) payload.barcode = updates.barcode
    if (updates.price !== undefined) payload.price = updates.price
    if (updates.cost !== undefined) payload.cost = updates.cost
    if (updates.active !== undefined) payload.active = updates.active
    if (updates.lowStockThreshold !== undefined)
      payload.low_stock_threshold = updates.lowStockThreshold
    if (updates.image !== undefined) payload.image_url = updates.image

    const { data } = await axios.put(`/api/products/${id}`, payload)
    const updated = mapApiProductToUi(data)
    const index = products.value.findIndex(p => p.id === id)
    if (index !== -1) products.value[index] = updated
  }

  const deleteProduct = async (id) => {
    await axios.delete(`/api/products/${id}`)
    const index = products.value.findIndex(p => p.id === id)
    if (index !== -1) products.value.splice(index, 1)
  }

  const getProductById = (id) => products.value.find(p => p.id === id)

  const searchProducts = (query) => {
    if (!query) return products.value
    const lowercaseQuery = query.toLowerCase()
    return products.value.filter(
      product =>
        product.name.toLowerCase().includes(lowercaseQuery) ||
        product.sku.toLowerCase().includes(lowercaseQuery) ||
        product.barcode.includes(query) ||
        product.category.toLowerCase().includes(lowercaseQuery)
    )
  }

  return {
    products,
    isLoading,
    errorMessage,
    fetchProducts,
    addProduct,
    updateProduct,
    deleteProduct,
    getProductById,
    searchProducts,
  }
})
