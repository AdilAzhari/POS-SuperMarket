<template>
  <AuthenticatedLayout>
    <component :is="currentComponent" />
  </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useAppStore } from '@/stores/app'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

// Components
import POSInterface from '@/Components/POSInterface.vue'
import ProductManagement from '@/Components/ProductManagement.vue'
import SalesHistory from '@/Components/SalesHistory.vue'
import StockManagement from '@/Components/StockManagement.vue'
import InventoryOverview from '@/Components/InventoryOverview.vue'
import CustomerManagement from '@/Components/CustomerManagement.vue'
import CategoryManagement from '@/Components/CategoryManagement.vue'
import SupplierManagement from '@/Components/SupplierManagement.vue'
import PaymentHistory from '@/Components/PaymentHistory.vue'
import ReportsAnalytics from '@/Components/ReportsAnalytics.vue'
import SystemSettings from '@/Components/SystemSettings.vue'

const appStore = useAppStore()

const componentMap = {
  pos: POSInterface,
  products: ProductManagement,
  sales: SalesHistory,
  stock: StockManagement,
  inventory: InventoryOverview,
  customers: CustomerManagement,
  categories: CategoryManagement,
  suppliers: SupplierManagement,
  payments: PaymentHistory,
  reports: ReportsAnalytics,
  settings: SystemSettings,
}

const currentComponent = computed(() => {
  return componentMap[appStore.currentView] || POSInterface
})
</script>
