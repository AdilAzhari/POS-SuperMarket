import { defineStore } from 'pinia'
import { ref } from 'vue'

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

  const stores = ref([
    { id: 'store-1', name: 'Downtown Branch', address: '123 Main St' },
    { id: 'store-2', name: 'Mall Location', address: '456 Shopping Ave' },
    { id: 'store-3', name: 'Suburban Store', address: '789 Residential Blvd' },
  ])

  const setCurrentView = (view) => {
    currentView.value = view
  }

  const setSelectedStore = (storeId) => {
    selectedStore.value = storeId
  }

  const toggleDarkMode = () => {
    darkMode.value = !darkMode.value
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
  }
})
