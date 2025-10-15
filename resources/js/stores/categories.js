import { defineStore } from 'pinia'
import { ref } from 'vue'
// import type { Category, RelatedProductSummary } from '@/types'
import axios from 'axios'
import { handleError } from '@/utils/errorHandler'

export const useCategoriesStore = defineStore('categories', () => {
  const categories = ref([])
  const isLoading = ref(false)
  const errorMessage = ref(null)
  const selectedCategory = ref(null)

  const mapApiCategoryToUi = (c) => ({
    id: c.id,
    name: c.name,
    slug: c.slug,
    created_at: c.created_at,
    productsCount: Number(
      c.products_count ?? c.productsCount ?? (Array.isArray(c.products) ? c.products.length : 0)
    ),
    products: Array.isArray(c.products)
      ? c.products.map(
          p => ({ id: p.id, name: p.name, sku: p.sku })
        )
      : undefined,
  })

  const fetchCategories = async () => {
    isLoading.value = true
    errorMessage.value = null
    try {
      const { data } = await axios.get('/api/categories')
      const list = Array.isArray(data?.data) ? data.data : data
      categories.value = list.map(mapApiCategoryToUi)
    } catch (err) {
      const errorResult = handleError(err, { customMessage: 'Failed to load categories' })
      errorMessage.value = errorResult.message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const fetchCategoryDetails = async (id) => {
    const { data } = await axios.get(`/api/categories/${id}`)
    const categoryData = data.data || data
    const details = mapApiCategoryToUi(categoryData)
    selectedCategory.value = details
    // also merge productsCount into list row
    const idx = categories.value.findIndex(c => String(c.id) === String(id))
    if (idx !== -1) {
      categories.value[idx] = {
        ...categories.value[idx],
        productsCount: details.products?.length ?? details.productsCount,
      }
    }
    return details
  }

  const addCategory = async (payload) => {
    try {
      const { data } = await axios.post('/api/categories', payload)
      categories.value.unshift(mapApiCategoryToUi(data))
      return { success: true, data }
    } catch (err) {
      const errorMessage =
        err?.response?.data?.message ||
        err?.response?.data?.errors?.name?.[0] ||
        err?.response?.data?.errors?.slug?.[0] ||
        'Failed to add category'
      throw new Error(errorMessage)
    }
  }

  const updateCategory = async (id, updates) => {
    try {
      const { data } = await axios.put(`/api/categories/${id}`, updates)
      const updated = mapApiCategoryToUi(data)
      const index = categories.value.findIndex(c => String(c.id) === String(id))
      if (index !== -1) categories.value[index] = updated
      return { success: true, data: updated }
    } catch (err) {
      const errorMessage =
        err?.response?.data?.message ||
        err?.response?.data?.errors?.name?.[0] ||
        err?.response?.data?.errors?.slug?.[0] ||
        'Failed to update category'
      throw new Error(errorMessage)
    }
  }

  const deleteCategory = async (id) => {
    try {
      const response = await axios.delete(`/api/categories/${id}`)
      const index = categories.value.findIndex(c => String(c.id) === String(id))
      if (index !== -1) {
        categories.value.splice(index, 1)
      }
      return { success: true, data: response.data }
    } catch (err) {
      console.error('Delete category API error:', err.response || err)
      const errorMessage = err?.response?.data?.message || err?.message || 'Failed to delete category'
      throw new Error(errorMessage)
    }
  }

  const searchCategories = (query) => {
    if (!query) return categories.value
    const q = query.toLowerCase()
    return categories.value.filter(c => c.name.toLowerCase().includes(q))
  }

  return {
    categories,
    isLoading,
    errorMessage,
    selectedCategory,
    fetchCategories,
    fetchCategoryDetails,
    addCategory,
    updateCategory,
    deleteCategory,
    searchCategories,
  }
})
