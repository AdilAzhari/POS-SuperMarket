import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useAppStore = defineStore('app', () => {
  const currentView = ref('pos')
  const selectedStore = ref(1)
  const darkMode = ref(false)
  const userRole = ref('admin')
  const currentUser = ref({
    id: '1',
    name: 'Admin User',
    email: 'admin@pos.com',
    role: 'admin',
  })

  const stores = ref([])

  const setCurrentView = (view) => {
    currentView.value = view
  }

  const setSelectedStore = (storeId) => {
    selectedStore.value = storeId
  }

  const toggleDarkMode = () => {
    darkMode.value = !darkMode.value
  }

  const fetchStores = async () => {
    try {
      const { data } = await axios.get('/api/stores')
      const storeList = Array.isArray(data?.data) ? data.data : data
      stores.value = storeList.map(store => ({
        id: Number(store.id),
        name: store.name,
        address: store.address,
        phone: store.phone,
        email: store.email,
      }))
      
      // Set default selected store if none selected
      if (stores.value.length > 0 && !selectedStore.value) {
        selectedStore.value = stores.value[0].id
      }
    } catch (error) {
      console.error('Failed to fetch stores:', error)
      // Fallback to default store
      stores.value = [
        { id: 1, name: 'Main Store', address: 'Default Location' }
      ]
      selectedStore.value = 1
    }
  }

  return {
    currentView,
    selectedStore,
    darkMode,
    userRole,
    currentUser,
    stores,
    setCurrentView,
    setSelectedStore,
    toggleDarkMode,
    fetchStores,
  }
})
