<template>
  <AuthenticatedLayout>
    <component :is="currentComponent" />
  </AuthenticatedLayout>
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue'
import { useAppStore } from '@/stores/app'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const appStore = useAppStore()

// Dynamic component imports to reduce bundle size
const componentMap = {
  pos: defineAsyncComponent(() => import('@/Components/POSInterface.vue')),
  'manager-dashboard': defineAsyncComponent(() => import('@/Components/ManagerDashboard.vue')),
  products: defineAsyncComponent(() => import('@/Components/ProductManagement.vue')),
  sales: defineAsyncComponent(() => import('@/Components/SalesHistory.vue')),
  stock: defineAsyncComponent(() => import('@/Components/StockManagement.vue')),
  inventory: defineAsyncComponent(() => import('@/Components/InventoryOverview.vue')),
  reorder: defineAsyncComponent(() => import('@/Components/ReorderManagement.vue')),
  customers: defineAsyncComponent(() => import('@/Components/CustomerManagement.vue')),
  employees: defineAsyncComponent(() => import('@/Components/EmployeeManagement.vue')),
  categories: defineAsyncComponent(() => import('@/Components/CategoryManagement.vue')),
  suppliers: defineAsyncComponent(() => import('@/Components/SupplierManagement.vue')),
  stores: defineAsyncComponent(() => import('@/Components/StoreManagement.vue')),
  payments: defineAsyncComponent(() => import('@/Components/PaymentHistory.vue')),
  returns: defineAsyncComponent(() => import('@/Components/ProductReturns.vue')),
  reports: defineAsyncComponent(() => import('@/Components/ReportsAnalytics.vue')),
  settings: defineAsyncComponent(() => import('@/Components/SystemSettings.vue')),
}

const currentComponent = computed(() => {
  return componentMap[appStore.currentView] || componentMap.pos
})
</script>
