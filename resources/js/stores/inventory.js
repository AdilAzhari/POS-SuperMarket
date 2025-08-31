import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useInventoryStore = defineStore('inventory', () => {
  const inventoryItems = ref([])

  const stockAdjustments = ref([])

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

  const fetchInventoryData = async (storeId = 1) => {
    try {
      const { data } = await axios.get('/api/products')
      const products = Array.isArray(data?.data) ? data.data : data

      inventoryItems.value = products.map(product => {
        // Find stock for the specific store
        const storeData = product.stores?.find(store => store.id === storeId || store.id === String(storeId))
        const stock = storeData?.pivot?.stock ?? 0
        const lowStockThreshold = storeData?.pivot?.low_stock_threshold ?? product.low_stock_threshold ?? 10

        return {
          id: String(product.id),
          name: product.name,
          sku: product.sku,
          category: product.category?.name || 'Uncategorized',
          currentStock: Number(stock),
          lowStockThreshold: Number(lowStockThreshold),
          averageDailySales: 5, // TODO: Calculate from actual sales data
          daysOfStock: Math.round((Number(stock) / 5) * 10) / 10,
          lastRestocked: product.updated_at?.split('T')[0] || new Date().toISOString().split('T')[0],
          cost: Number(product.cost || 0),
          price: Number(product.price || 0),
          costValue: Number(product.cost || 0) * Number(stock),
          retailValue: Number(product.price || 0) * Number(stock),
          supplier: product.supplier?.name || 'Unknown Supplier',
          unit: product.unit || 'pcs',
        }
      })
    } catch (error) {
      console.error('Failed to fetch inventory data:', error)
    }
  }

  const fetchStockMovements = async () => {
    try {
      const { data } = await axios.get('/api/stock-movements')
      // Update stockAdjustments with fetched data
      const movements = Array.isArray(data?.data) ? data.data : []
      stockAdjustments.value = movements.map(movement => ({
        id: movement.id,
        product: movement.product,
        productName: movement.product?.name || 'Unknown Product',
        sku: movement.product?.sku || '',
        type: movement.type,
        quantity: movement.quantity,
        reason: movement.reason || '',
        notes: movement.notes || '',
        createdAt: movement.created_at?.split('T')[0] || new Date().toISOString().split('T')[0],
        user: movement.user || { name: 'System' }
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
    fetchInventoryData,
    fetchStockMovements,
  }
})
