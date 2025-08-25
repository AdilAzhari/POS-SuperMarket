import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useStoresStore = defineStore('stores', () => {
  const stores = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchStores = async () => {
    loading.value = true
    error.value = null
    
    try {
      const { data } = await axios.get('/api/stores')
      const storeList = Array.isArray(data?.data) ? data.data : data
      stores.value = storeList || []
      return stores.value
    } catch (err) {
      error.value = 'Failed to fetch stores'
      console.error('Error fetching stores:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const createStore = async (storeData) => {
    loading.value = true
    error.value = null

    try {
      const { data } = await axios.post('/api/stores', storeData)
      stores.value.push(data)
      return data
    } catch (err) {
      error.value = 'Failed to create store'
      console.error('Error creating store:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateStore = async (id, storeData) => {
    loading.value = true
    error.value = null

    try {
      const { data } = await axios.put(`/api/stores/${id}`, storeData)
      const index = stores.value.findIndex(store => store.id === id)
      if (index !== -1) {
        stores.value[index] = data
      }
      return data
    } catch (err) {
      error.value = 'Failed to update store'
      console.error('Error updating store:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteStore = async (id) => {
    loading.value = true
    error.value = null

    try {
      await axios.delete(`/api/stores/${id}`)
      stores.value = stores.value.filter(store => store.id !== id)
      return true
    } catch (err) {
      error.value = 'Failed to delete store'
      console.error('Error deleting store:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const getStoreById = (id) => {
    return stores.value.find(store => store.id === id)
  }

  const getStoresWithContact = () => {
    return stores.value.filter(store => store.phone || store.email)
  }

  const searchStores = (query) => {
    if (!query) return stores.value
    
    const searchTerm = query.toLowerCase()
    return stores.value.filter(store =>
      store.name.toLowerCase().includes(searchTerm) ||
      (store.address && store.address.toLowerCase().includes(searchTerm)) ||
      (store.phone && store.phone.includes(searchTerm)) ||
      (store.email && store.email.toLowerCase().includes(searchTerm))
    )
  }

  return {
    stores,
    loading,
    error,
    fetchStores,
    createStore,
    updateStore,
    deleteStore,
    getStoreById,
    getStoresWithContact,
    searchStores,
  }
})