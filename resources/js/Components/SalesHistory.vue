<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-2xl font-bold text-gray-900">Sales History</h2>
      <p class="text-gray-600">View and manage past transactions</p>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
          <label class="text-xs text-gray-500">Start Date</label>
          <input v-model="startDate" type="date" class="w-full px-3 py-2 border rounded-lg" />
        </div>
        <div>
          <label class="text-xs text-gray-500">End Date</label>
          <input v-model="endDate" type="date" class="w-full px-3 py-2 border rounded-lg" />
        </div>
        <div class="md:col-span-2">
          <label class="text-xs text-gray-500">Customer Phone</label>
          <input
            v-model="customerPhone"
            placeholder="Filter by phone"
            class="w-full px-3 py-2 border rounded-lg"
          />
        </div>
      </div>

      <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-600">
            <tr>
              <th class="text-left px-4 py-2 font-medium">Txn ID</th>
              <th class="text-left px-4 py-2 font-medium">Customer</th>
              <th class="text-left px-4 py-2 font-medium">Items</th>
              <th class="text-right px-4 py-2 font-medium">Subtotal</th>
              <th class="text-right px-4 py-2 font-medium">Discount</th>
              <th class="text-right px-4 py-2 font-medium">Tax</th>
              <th class="text-right px-4 py-2 font-medium">Total</th>
              <th class="text-left px-4 py-2 font-medium">Payment</th>
              <th class="text-left px-4 py-2 font-medium">Date</th>
              <th class="text-left px-4 py-2 font-medium">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in filteredSales" :key="s.id" class="border-t">
              <td class="px-4 py-2 font-medium text-gray-900">
                {{ s.id }}
              </td>
              <td class="px-4 py-2">
                <div class="font-medium">
                  {{ s.customerName }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ s.customerPhone }}
                </div>
              </td>
              <td class="px-4 py-2">{{ s.items }}</td>
              <td class="px-4 py-2 text-right">${{ s.subtotal.toFixed(2) }}</td>
              <td class="px-4 py-2 text-right">-${{ s.discount.toFixed(2) }}</td>
              <td class="px-4 py-2 text-right">${{ s.tax.toFixed(2) }}</td>
              <td class="px-4 py-2 text-right font-semibold">${{ s.total.toFixed(2) }}</td>
              <td class="px-4 py-2">{{ s.paymentMethod }}</td>
              <td class="px-4 py-2">{{ s.date }} {{ s.time }}</td>
              <td class="px-4 py-2">
                <select
                  v-model="statusMap[s.id]"
                  class="text-xs border rounded px-2 py-1"
                  @change="updateStatus(s.id, statusMap[s.id])"
                >
                  <option value="completed">Completed</option>
                  <option value="refunded">Refunded</option>
                  <option value="voided">Voided</option>
                </select>
              </td>
            </tr>
            <tr v-if="filteredSales.length === 0">
              <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                No sales in this range.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watchEffect } from 'vue'
import { useSalesStore } from '@/stores/sales'

const salesStore = useSalesStore()

const today = new Date().toISOString().split('T')[0]
const startDate = ref(today)
const endDate = ref(today)
const customerPhone = ref('')

const filteredSales = computed(() => {
  let list = salesStore.getSalesByDateRange(startDate.value, endDate.value)
  if (customerPhone.value) {
    list = list.filter(s => s.customerPhone === customerPhone.value)
  }
  return list
})

const statusMap = reactive<Record<string, 'completed' | 'refunded' | 'voided'>>({})
watchEffect(() => {
  for (const s of salesStore.sales) {
    statusMap[s.id] = s.status
  }
})

const updateStatus = async (id: string, status: 'completed' | 'refunded' | 'voided') => {
  await salesStore.updateSaleStatus(id, status)
}

// initial load
salesStore.fetchSales().catch(() => {})
</script>
