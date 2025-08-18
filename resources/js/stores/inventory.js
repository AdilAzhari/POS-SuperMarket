import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
// import type { InventoryItem, StockAdjustment } from '@/types'

export const useInventoryStore = defineStore('inventory', () => {
  const inventoryItems = ref([
    {
      id: '1',
      name: 'Coca Cola 500ml',
      sku: 'BEV-001',
      category: 'Beverages',
      currentStock: 150,
      lowStockThreshold: 20,
      averageDailySales: 12,
      daysOfStock: 12.5,
      lastRestocked: '2024-01-10',
      costValue: 180.0,
      retailValue: 375.0,
      supplier: 'Coca Cola Company',
    },
    {
      id: '2',
      name: 'White Bread Loaf',
      sku: 'BAK-001',
      category: 'Bakery',
      currentStock: 45,
      lowStockThreshold: 10,
      averageDailySales: 8,
      daysOfStock: 5.6,
      lastRestocked: '2024-01-12',
      costValue: 94.5,
      retailValue: 179.55,
      supplier: 'Fresh Bakery Co',
    },
    {
      id: '3',
      name: 'Organic Bananas 1kg',
      sku: 'FRU-001',
      category: 'Fruits',
      currentStock: 8,
      lowStockThreshold: 15,
      averageDailySales: 5,
      daysOfStock: 1.6,
      lastRestocked: '2024-01-08',
      costValue: 22.4,
      retailValue: 36.0,
      supplier: 'Organic Farms Ltd',
    },
  ])

  const stockAdjustments = ref([
    {
      id: 'ADJ-001',
      productName: 'Coca Cola 500ml',
      sku: 'BEV-001',
      type: 'addition',
      quantity: 50,
      reason: 'New stock delivery',
      notes: 'Weekly delivery from supplier',
      date: '2024-01-10',
      time: '09:30:00',
      user: 'Admin User',
    },
    {
      id: 'ADJ-002',
      productName: 'Organic Bananas 1kg',
      sku: 'FRU-001',
      type: 'reduction',
      quantity: 5,
      reason: 'Damaged goods',
      notes: 'Overripe bananas removed from shelf',
      date: '2024-01-12',
      time: '16:45:00',
      user: 'Admin User',
    },
  ])

  const lowStockItems = computed(() =>
    inventoryItems.value.filter(item => item.currentStock <= item.lowStockThreshold)
  )

  const outOfStockItems = computed(() =>
    inventoryItems.value.filter(item => item.currentStock === 0)
  )

  const totalInventoryValue = computed(() =>
    inventoryItems.value.reduce((sum, item) => sum + item.retailValue, 0)
  )

  const addStockAdjustment = (adjustment) => {
    const newAdjustment = {
      ...adjustment,
      id: `ADJ-${String(stockAdjustments.value.length + 1).padStart(3, '0')}`,
    }
    stockAdjustments.value.unshift(newAdjustment)

    // Update inventory item stock
    const item = inventoryItems.value.find(i => i.sku === adjustment.sku)
    if (item) {
      if (adjustment.type === 'addition' || adjustment.type === 'transfer_in') {
        item.currentStock += adjustment.quantity
      } else if (adjustment.type === 'reduction' || adjustment.type === 'transfer_out') {
        item.currentStock = Math.max(0, item.currentStock - adjustment.quantity)
      }
      // Recalculate values
      item.costValue =
        item.currentStock * (item.costValue / (item.currentStock + adjustment.quantity))
      item.retailValue =
        item.currentStock * (item.retailValue / (item.currentStock + adjustment.quantity))
    }
  }

  const fetchStockMovements = async () => {
    try {
      const { data } = await axios.get('/api/stock-movements')
      // Update stockAdjustments with fetched data
      const movements = Array.isArray(data?.data) ? data.data : []
      stockAdjustments.value = movements.map(movement => ({
        id: movement.id,
        productName: movement.product?.name || 'Unknown Product',
        sku: movement.product?.sku || '',
        type: movement.type,
        quantity: movement.quantity,
        reason: movement.reason || '',
        date: movement.created_at?.split('T')[0] || new Date().toISOString().split('T')[0],
        user: movement.user?.name || 'System'
      }))
    } catch (error) {
      console.error('Failed to fetch stock movements:', error)
    }
  }

  return {
    inventoryItems,
    stockAdjustments,
    lowStockItems,
    outOfStockItems,
    totalInventoryValue,
    addStockAdjustment,
    fetchStockMovements,
  }
})
