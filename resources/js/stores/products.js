import { defineStore } from 'pinia'
import { ref } from 'vue'
// import type { Product } from '@/types'
import axios from 'axios'

export const useProductsStore = defineStore('products', () => {
  const products = ref([])
  const isLoading = ref(false)
  const errorMessage = ref(null)
  const currentStoreId = ref(1) // Default to store 1
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: 0,
    to: 0
  })

  const mapApiProductToUi = (p, storeId = 1) => {
    // Find stock for the specific store
    const storeData = p.stores?.find(store => store.id === storeId || store.id === String(storeId))
    const stock = storeData?.pivot?.stock ?? 0
    const lowStockThreshold = storeData?.pivot?.low_stock_threshold ?? p.low_stock_threshold ?? 0

    return {
      id: String(p.id),
      name: p.name,
      sku: p.sku,
      barcode: p.barcode,
      price: Number(p.price ?? 0),
      cost: Number(p.cost ?? 0),
      stock: Number(stock),
      category: p.category?.name ?? '',
      supplier: p.supplier?.name ?? '',
      image: p.image_url ?? '',
      active: Boolean(p.active),
      lowStockThreshold: Number(lowStockThreshold),
    }
  }

  const fetchProducts = async (page = 1, perPage = 20) => {
    isLoading.value = true
    errorMessage.value = null
    try {
      const { data } = await axios.get('/api/products', {
        params: { page, per_page: perPage }
      })
      
      // Handle paginated response
      if (data.data && data.current_page !== undefined) {
        products.value = data.data.map(p => mapApiProductToUi(p, currentStoreId.value))
        pagination.value = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total,
          from: data.from,
          to: data.to
        }
      } else {
        // Fallback for non-paginated response
        const list = Array.isArray(data?.data) ? data.data : data
        products.value = list.map(p => mapApiProductToUi(p, currentStoreId.value))
      }
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
      low_stock_threshold: product.low_stock_threshold || product.lowStockThreshold,
      image_url: product.image_url || product.image,
      category_id: product.category_id,
      supplier_id: product.supplier_id
    }
    console.log('Adding product with payload:', payload)
    const { data } = await axios.post('/api/products', payload)
    console.log('Product created, response:', data)
    products.value.unshift(mapApiProductToUi(data, currentStoreId.value))
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
    if (updates.category_id !== undefined) payload.category_id = updates.category_id
    if (updates.supplier_id !== undefined) payload.supplier_id = updates.supplier_id

    console.log('Updating product', id, 'with payload:', payload)
    const { data } = await axios.put(`/api/products/${id}`, payload)
    console.log('Product updated, response:', data)
    const updated = mapApiProductToUi(data, currentStoreId.value)
    const index = products.value.findIndex(p => p.id === id || p.id === String(id))
    if (index !== -1) {
      products.value[index] = updated
      console.log('Updated product in store at index', index)
    } else {
      console.warn('Could not find product to update in store, ID:', id)
    }
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

  const setStoreId = (storeId) => {
    currentStoreId.value = storeId
  }

  return {
    products,
    isLoading,
    errorMessage,
    pagination,
    currentStoreId,
    fetchProducts,
    addProduct,
    updateProduct,
    deleteProduct,
    getProductById,
    searchProducts,
    setStoreId,
  }
})
