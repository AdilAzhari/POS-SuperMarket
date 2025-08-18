<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-2xl font-bold text-gray-900">Inventory Overview</h2>
      <p class="text-gray-600">Snapshot of current stock levels and value</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-gray-500">Total Inventory Value</p>
        <p class="text-2xl font-bold text-gray-900">${{ totalInventoryValue.toFixed(2) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-gray-500">Low Stock Items</p>
        <p class="text-2xl font-bold text-yellow-600">
          {{ lowStockItems.length }}
        </p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-gray-500">Out of Stock</p>
        <p class="text-2xl font-bold text-red-600">
          {{ outOfStockItems.length }}
        </p>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="relative flex-1">
          <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
          <input
            v-model="query"
            placeholder="Search by name, SKU, category, supplier"
            class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div class="flex gap-2">
          <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-gray-100">
            All: {{ filteredItems.length }}
          </span>
          <span
            class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800"
          >
            Low: {{ lowStockItems.length }}
          </span>
          <span
            class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-red-100 text-red-800"
          >
            Out: {{ outOfStockItems.length }}
          </span>
        </div>
      </div>

      <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-600">
            <tr>
              <th class="text-left px-4 py-2 font-medium">Product</th>
              <th class="text-left px-4 py-2 font-medium">SKU</th>
              <th class="text-left px-4 py-2 font-medium">Category</th>
              <th class="text-right px-4 py-2 font-medium">Stock</th>
              <th class="text-right px-4 py-2 font-medium">Days of Stock</th>
              <th class="text-left px-4 py-2 font-medium">Supplier</th>
              <th class="text-left px-4 py-2 font-medium">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in filteredItems" :key="item.id" class="border-t">
              <td class="px-4 py-2 font-medium text-gray-900">
                {{ item.name }}
              </td>
              <td class="px-4 py-2">{{ item.sku }}</td>
              <td class="px-4 py-2">{{ item.category }}</td>
              <td class="px-4 py-2 text-right">{{ item.currentStock }}</td>
              <td class="px-4 py-2 text-right">
                {{ item.daysOfStock.toFixed(1) }}
              </td>
              <td class="px-4 py-2">{{ item.supplier }}</td>
              <td class="px-4 py-2">
                <span :class="statusClass(item)">{{ statusText(item) }}</span>
              </td>
            </tr>
            <tr v-if="filteredItems.length === 0">
              <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                No inventory items match your search.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useInventoryStore } from '@/stores/inventory'
import { Search } from 'lucide-vue-next'
import type { InventoryItem } from '@/types'

const inventory = useInventoryStore()

const query = ref('')

const filteredItems = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return inventory.inventoryItems
  return inventory.inventoryItems.filter(
    i =>
      i.name.toLowerCase().includes(q) ||
      i.sku.toLowerCase().includes(q) ||
      i.category.toLowerCase().includes(q) ||
      i.supplier.toLowerCase().includes(q)
  )
})

const lowStockItems = inventory.lowStockItems
const outOfStockItems = inventory.outOfStockItems
const totalInventoryValue = inventory.totalInventoryValue

const statusText = (item: InventoryItem) => {
  if (item.currentStock === 0) return 'Out of Stock'
  if (item.currentStock <= item.lowStockThreshold) return 'Low Stock'
  return 'In Stock'
}

const statusClass = (item: InventoryItem) => {
  if (item.currentStock === 0)
    return 'inline-flex px-2 py-1 text-xs rounded-full bg-red-100 text-red-800'
  if (item.currentStock <= item.lowStockThreshold)
    return 'inline-flex px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800'
  return 'inline-flex px-2 py-1 text-xs rounded-full bg-green-100 text-green-800'
}
</script>
