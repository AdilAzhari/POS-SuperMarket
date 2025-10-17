<template>
  <div class="space-y-6">
    <!-- Header with Statistics -->
    <div>
      <h2 class="text-2xl font-bold text-gray-900">Product Returns</h2>
      <p class="text-gray-600">Process customer returns and refunds</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Total Returns</p>
            <p class="text-2xl font-bold text-gray-900">{{ statistics.total_returns }}</p>
          </div>
          <div class="p-3 bg-blue-50 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Total Refunded</p>
            <p class="text-2xl font-bold text-gray-900">RM {{ statistics.total_refunded.toFixed(2) }}</p>
          </div>
          <div class="p-3 bg-red-50 rounded-lg">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Pending Returns</p>
            <p class="text-2xl font-bold text-yellow-600">{{ statistics.pending_returns }}</p>
          </div>
          <div class="p-3 bg-yellow-50 rounded-lg">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Items Returned</p>
            <p class="text-2xl font-bold text-gray-900">{{ statistics.items_returned }}</p>
          </div>
          <div class="p-3 bg-green-50 rounded-lg">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Search for Sale -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h3 class="text-lg font-semibold mb-4">Find Sale for Return</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="text-sm font-medium text-gray-500">Sale ID / Receipt Number</label>
          <input
            v-model="searchTerm"
            type="text"
            placeholder="Enter sale ID or receipt number"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            @input="searchSales"
          />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-500">Customer Phone</label>
          <input
            v-model="customerPhone"
            type="text"
            placeholder="Enter customer phone"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            @input="searchSales"
          />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-500">Date Range</label>
          <input
            v-model="dateRange"
            type="date"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            @change="searchSales"
          />
        </div>
      </div>
    </div>

    <!-- Search Results -->
    <div v-if="searchResults.length > 0" class="bg-white rounded-lg shadow-sm p-6">
      <h3 class="text-lg font-semibold mb-4">Sales Found</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="sale in searchResults" :key="sale.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ sale.code || sale.id }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <div>{{ sale.customer?.name || 'Walk-in Customer' }}</div>
                <div class="text-xs text-gray-400">{{ sale.customer?.phone || '' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(sale.created_at) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">RM {{ parseFloat(sale.total || 0).toFixed(2) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ getMethodName(sale.payment_method) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  @click="selectSaleForReturn(sale)"
                  class="text-blue-600 hover:text-blue-900"
                >
                  Process Return
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Return Form -->
    <div v-if="selectedSale" class="bg-white rounded-lg shadow-sm p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Process Return for Sale #{{ selectedSale.code || selectedSale.id }}</h3>
        <button @click="clearSelection" class="text-gray-400 hover:text-gray-600">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Sale Items -->
      <div class="mb-6">
        <h4 class="text-md font-medium mb-3">Items in this Sale</h4>
        <div class="space-y-3">
          <div
            v-for="item in saleItems"
            :key="item.id"
            class="flex items-center justify-between p-4 border rounded-lg transition-all duration-200"
            :class="returnItems[item.id] ? 'border-blue-300 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
          >
            <div class="flex items-center space-x-4 flex-1">
              <input
                v-model="returnItems[item.id]"
                type="checkbox"
                class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer"
                @change="toggleReturnItem(item)"
              />

              <!-- Product Image Placeholder -->
              <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center overflow-hidden">
                <img
                  v-if="item.product?.image"
                  :src="item.product.image"
                  :alt="item.product.name"
                  class="w-full h-full object-cover"
                />
                <svg v-else class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
              </div>

              <div class="flex-1">
                <div class="font-semibold text-gray-900">{{ item.product?.name || 'Unknown Product' }}</div>
                <div class="text-sm text-gray-500 mt-1">
                  <span class="inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                    </svg>
                    SKU: {{ item.product?.sku || 'N/A' }}
                  </span>
                </div>
                <div class="text-sm text-gray-600 mt-1">
                  Qty sold: <span class="font-medium">{{ item.quantity }}</span> @
                  <span class="font-medium text-green-600">RM {{ parseFloat(item.price || 0).toFixed(2) }}</span>
                </div>
                <div class="text-xs text-gray-500 mt-1">
                  Subtotal: RM {{ (item.quantity * parseFloat(item.price || 0)).toFixed(2) }}
                </div>
              </div>
            </div>

            <div v-if="returnItems[item.id]" class="flex flex-col items-end space-y-2">
              <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Return Qty:</label>
                <input
                  v-model.number="returnQuantities[item.id]"
                  type="number"
                  :min="1"
                  :max="item.quantity"
                  class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  @input="validateReturnQuantity(item)"
                />
              </div>
              <div class="text-sm">
                <span class="text-gray-500">Refund:</span>
                <span class="font-semibold text-blue-600 ml-1">
                  RM {{ ((returnQuantities[item.id] || 1) * parseFloat(item.price || 0)).toFixed(2) }}
                </span>
              </div>
              <div class="text-xs text-green-600 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Will restore {{ returnQuantities[item.id] || 1 }} to stock
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Return Details -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Return Reason</label>
          <select v-model="returnReason" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" required>
            <option value="">Select reason</option>
            <option value="defective">Defective Product</option>
            <option value="wrong_item">Wrong Item</option>
            <option value="customer_change_mind">Customer Changed Mind</option>
            <option value="damaged_shipping">Damaged in Shipping</option>
            <option value="not_as_described">Not as Described</option>
            <option value="duplicate_order">Duplicate Order</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Refund Method</label>
          <select v-model="refundMethod" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" required>
            <option value="">Select refund method</option>
            <option value="original_payment">Original Payment Method</option>
            <option value="cash">Cash</option>
            <option value="store_credit">Store Credit</option>
            <option value="exchange">Exchange Only</option>
          </select>
        </div>
      </div>

      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
        <textarea
          v-model="returnNotes"
          rows="3"
          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          placeholder="Additional notes about the return..."
        ></textarea>
      </div>

      <!-- Return Summary -->
      <div class="bg-gray-50 p-4 rounded-lg mb-6">
        <h4 class="font-medium mb-2">Return Summary</h4>
        <div class="text-sm space-y-1">
          <div class="flex justify-between">
            <span>Items to return:</span>
            <span>{{ totalReturnItems }}</span>
          </div>
          <div class="flex justify-between">
            <span>Total refund amount:</span>
            <span class="font-medium">RM {{ totalRefundAmount.toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex space-x-3">
        <button
          @click="processReturn"
          :disabled="!canProcessReturn || isProcessing"
          class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
        >
          <span v-if="isProcessing">Processing Return...</span>
          <span v-else>Process Return</span>
        </button>
        <button
          @click="clearSelection"
          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
        >
          Cancel
        </button>
      </div>
    </div>

    <!-- Recent Returns with Filters -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Recent Returns</h3>
        <div class="flex items-center space-x-2">
          <select
            v-model="returnFilter"
            @change="fetchRecentReturns"
            class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
          >
            <option value="all">All Returns</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="completed">Completed</option>
            <option value="rejected">Rejected</option>
          </select>
          <button
            @click="fetchRecentReturns"
            class="p-2 text-gray-400 hover:text-gray-600 border border-gray-300 rounded-lg"
            title="Refresh"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Code</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Original Sale</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Refund Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="returnRecord in recentReturns" :key="returnRecord.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ returnRecord.id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 font-medium">
                #{{ returnRecord.original_sale_id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ returnRecord.customer_name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  {{ returnRecord.items_count }} item{{ returnRecord.items_count !== 1 ? 's' : '' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                RM {{ parseFloat(returnRecord.refund_amount || 0).toFixed(2) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="getStatusBadgeClass(returnRecord.status)"
                >
                  {{ returnRecord.status || 'pending' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatReason(returnRecord.reason) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(returnRecord.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  @click="viewReturnDetails(returnRecord)"
                  class="text-blue-600 hover:text-blue-900"
                >
                  View Details
                </button>
              </td>
            </tr>
            <tr v-if="recentReturns.length === 0">
              <td colspan="9" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                  <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <p class="text-gray-500 text-sm">No returns processed yet</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Return Details Modal -->
    <div
      v-if="showDetailsModal && selectedReturn"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="closeDetailsModal"
    >
      <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-xl font-bold text-gray-900">Return Details</h3>
            <p class="text-sm text-gray-500 mt-1">Return Code: {{ selectedReturn.id }}</p>
          </div>
          <button
            @click="closeDetailsModal"
            class="text-gray-400 hover:text-gray-600"
          >
            <X class="w-6 h-6" />
          </button>
        </div>

        <!-- Return Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Return Information</h4>
            <div class="space-y-2">
              <div>
                <span class="text-xs text-gray-500">Original Sale:</span>
                <p class="text-sm font-medium">#{{ selectedReturn.original_sale_id }}</p>
              </div>
              <div>
                <span class="text-xs text-gray-500">Customer:</span>
                <p class="text-sm font-medium">{{ selectedReturn.customer_name }}</p>
              </div>
              <div>
                <span class="text-xs text-gray-500">Return Reason:</span>
                <p class="text-sm font-medium">{{ formatReason(selectedReturn.reason) }}</p>
              </div>
              <div>
                <span class="text-xs text-gray-500">Refund Method:</span>
                <p class="text-sm font-medium">{{ formatRefundMethod(selectedReturn.refund_method) }}</p>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Financial Details</h4>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-xs text-gray-500">Subtotal:</span>
                <span class="text-sm font-medium">RM {{ parseFloat(selectedReturn.subtotal || 0).toFixed(2) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-xs text-gray-500">Tax Refund:</span>
                <span class="text-sm font-medium">RM {{ parseFloat(selectedReturn.tax_refund || 0).toFixed(2) }}</span>
              </div>
              <div class="flex justify-between border-t pt-2">
                <span class="text-sm font-semibold text-gray-700">Total Refund:</span>
                <span class="text-lg font-bold text-blue-600">RM {{ parseFloat(selectedReturn.refund_amount || 0).toFixed(2) }}</span>
              </div>
              <div class="mt-3">
                <span class="text-xs text-gray-500">Status:</span>
                <p class="mt-1">
                  <span
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="getStatusBadgeClass(selectedReturn.status)"
                  >
                    {{ selectedReturn.status || 'pending' }}
                  </span>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Returned Items -->
        <div class="mb-6">
          <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Returned Items</h4>
          <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Refund Amount</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(item, index) in selectedReturn.items" :key="index">
                  <td class="px-4 py-3 text-sm text-gray-900">{{ item.product_name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500">{{ item.quantity }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500">RM {{ parseFloat(item.unit_price || 0).toFixed(2) }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900">RM {{ (item.quantity * parseFloat(item.unit_price || 0)).toFixed(2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="selectedReturn.notes" class="mb-6">
          <h4 class="text-sm font-semibold text-gray-500 uppercase mb-2">Notes</h4>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-700">{{ selectedReturn.notes }}</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3 pt-4 border-t">
          <button
            @click="closeDetailsModal"
            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Component -->
  <Modal
    :show="modal.isVisible.value"
    :title="modal.modalData.title"
    :message="modal.modalData.message"
    :type="modal.modalData.type"
    :size="modal.modalData.size"
    :show-cancel-button="modal.modalData.showCancelButton"
    :confirm-text="modal.modalData.confirmText"
    :cancel-text="modal.modalData.cancelText"
    @close="modal.hide"
    @confirm="modal.confirm"
    @cancel="modal.cancel"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useMessageModal } from '@/composables/useModal.js'
import Modal from '@/Components/Modal.vue'
import { X } from 'lucide-vue-next'
import axios from 'axios'

// Data
const searchTerm = ref('')
const customerPhone = ref('')
const dateRange = ref('')
const searchResults = ref([])
const selectedSale = ref(null)
const saleItems = ref([])
const returnItems = ref({})
const returnQuantities = ref({})
const returnReason = ref('')
const refundMethod = ref('')
const returnNotes = ref('')
const recentReturns = ref([])
const isProcessing = ref(false)
const modal = useMessageModal()
const returnFilter = ref('all')
const showDetailsModal = ref(false)
const selectedReturn = ref(null)
const statistics = ref({
  total_returns: 0,
  total_refunded: 0,
  pending_returns: 0,
  items_returned: 0
})

// Computed
const totalReturnItems = computed(() => {
  return Object.values(returnItems.value).filter(Boolean).length
})

const totalRefundAmount = computed(() => {
  let total = 0
  for (const item of saleItems.value) {
    if (returnItems.value[item.id]) {
      const qty = returnQuantities.value[item.id] || 1
      total += qty * parseFloat(item.price || 0)
    }
  }
  return total
})

const canProcessReturn = computed(() => {
  return totalReturnItems.value > 0 && returnReason.value && refundMethod.value
})

// Methods
const searchSales = async () => {
  if (!searchTerm.value && !customerPhone.value && !dateRange.value) {
    searchResults.value = []
    return
  }

  try {
    const params = new URLSearchParams()
    if (searchTerm.value) params.append('search', searchTerm.value)
    if (customerPhone.value) params.append('customer_phone', customerPhone.value)
    if (dateRange.value) params.append('date', dateRange.value)
    
    const response = await axios.get(`/api/sales?${params.toString()}`)
    const salesData = Array.isArray(response.data?.data) ? response.data.data : response.data
    searchResults.value = salesData.filter(sale => sale.status === 'completed')
  } catch (error) {
    console.error('Failed to search sales:', error)
    searchResults.value = []
  }
}

const selectSaleForReturn = async (sale) => {
  selectedSale.value = sale
  
  try {
    // Fetch sale items
    const response = await axios.get(`/api/sales/${sale.id}`)
    saleItems.value = response.data.items || []
    
    // Reset return selections
    returnItems.value = {}
    returnQuantities.value = {}
    returnReason.value = ''
    refundMethod.value = ''
    returnNotes.value = ''
  } catch (error) {
    console.error('Failed to fetch sale items:', error)
    await modal.showError('Failed to load sale details. Please try again.')
    selectedSale.value = null
  }
}

const toggleReturnItem = (item) => {
  if (returnItems.value[item.id]) {
    returnQuantities.value[item.id] = 1
  } else {
    delete returnQuantities.value[item.id]
  }
}

const processReturn = async () => {
  if (!canProcessReturn.value) return

  const confirmed = await modal.showConfirm(
    `Are you sure you want to process this return? This will refund RM ${totalRefundAmount.value.toFixed(2)} and add the returned items back to inventory.`
  )

  if (!confirmed) return

  isProcessing.value = true

  try {
    // Prepare return data for new API
    const returnData = {
      sale_id: selectedSale.value.id,
      reason: returnReason.value,
      refund_method: refundMethod.value,
      notes: returnNotes.value,
      processed_by: window?.App?.userId ?? 1,
      items: []
    }

    // Add returned items with sale_item_id
    for (const item of saleItems.value) {
      if (returnItems.value[item.id]) {
        returnData.items.push({
          sale_item_id: item.id,
          quantity: returnQuantities.value[item.id] || 1,
          condition_notes: null
        })
      }
    }

    // Process the return using the new API endpoint
    const response = await axios.post('/api/returns', returnData)

    await modal.showSuccess(`Return processed successfully! RM ${totalRefundAmount.value.toFixed(2)} refund has been initiated.`)

    // Refresh recent returns
    fetchRecentReturns()

    // Clear the selection
    clearSelection()

  } catch (error) {
    console.error('Failed to process return:', error)
    const errorMessage = error.response?.data?.message || 'Failed to process return. Please try again.'
    await modal.showError(errorMessage)
  } finally {
    isProcessing.value = false
  }
}

const clearSelection = () => {
  selectedSale.value = null
  saleItems.value = []
  returnItems.value = {}
  returnQuantities.value = {}
  returnReason.value = ''
  refundMethod.value = ''
  returnNotes.value = ''
}

const fetchRecentReturns = async () => {
  try {
    // Build query string with filter
    const params = new URLSearchParams()
    params.append('per_page', '20')
    if (returnFilter.value !== 'all') {
      params.append('status', returnFilter.value)
    }

    const response = await axios.get(`/api/returns?${params.toString()}`)
    const returns = Array.isArray(response.data?.data) ? response.data.data : response.data

    // Transform the data for display
    recentReturns.value = returns.map(returnRecord => ({
      id: returnRecord.code || returnRecord.id,
      original_sale_id: returnRecord.sale?.code || returnRecord.sale_id,
      customer_name: returnRecord.customer?.name || 'Walk-in Customer',
      items_count: returnRecord.items?.length || 0,
      refund_amount: returnRecord.total_refund || 0,
      subtotal: returnRecord.subtotal || 0,
      tax_refund: returnRecord.tax_refund || 0,
      reason: returnRecord.reason,
      refund_method: returnRecord.refund_method,
      status: returnRecord.status || 'pending',
      notes: returnRecord.notes,
      items: returnRecord.items || [],
      created_at: returnRecord.created_at
    }))

    // Update statistics
    fetchStatistics()
  } catch (error) {
    console.error('Failed to fetch recent returns:', error)
    recentReturns.value = []
  }
}

const fetchStatistics = async () => {
  try {
    const response = await axios.get('/api/returns?per_page=999')
    const allReturns = Array.isArray(response.data?.data) ? response.data.data : response.data

    statistics.value = {
      total_returns: allReturns.length,
      total_refunded: allReturns.reduce((sum, ret) => sum + parseFloat(ret.total_refund || 0), 0),
      pending_returns: allReturns.filter(ret => ret.status === 'pending').length,
      items_returned: allReturns.reduce((sum, ret) => sum + (ret.items?.length || 0), 0)
    }
  } catch (error) {
    console.error('Failed to fetch statistics:', error)
  }
}

const validateReturnQuantity = (item) => {
  const qty = returnQuantities.value[item.id]
  if (qty < 1) {
    returnQuantities.value[item.id] = 1
  } else if (qty > item.quantity) {
    returnQuantities.value[item.id] = item.quantity
  }
}

const viewReturnDetails = async (returnRecord) => {
  try {
    // Fetch full return details
    const response = await axios.get(`/api/returns/${returnRecord.id}`)
    const fullReturn = response.data.data || response.data

    selectedReturn.value = {
      id: fullReturn.code || fullReturn.id,
      original_sale_id: fullReturn.sale?.code || fullReturn.sale_id,
      customer_name: fullReturn.customer?.name || 'Walk-in Customer',
      items_count: fullReturn.items?.length || 0,
      refund_amount: fullReturn.total_refund || 0,
      subtotal: fullReturn.subtotal || 0,
      tax_refund: fullReturn.tax_refund || 0,
      reason: fullReturn.reason,
      refund_method: fullReturn.refund_method,
      status: fullReturn.status || 'pending',
      notes: fullReturn.notes,
      items: (fullReturn.items || []).map(item => ({
        product_name: item.product?.name || 'Unknown Product',
        quantity: item.quantity,
        unit_price: item.price || 0
      })),
      created_at: fullReturn.created_at
    }

    showDetailsModal.value = true
  } catch (error) {
    console.error('Failed to fetch return details:', error)
    await modal.showError('Failed to load return details. Please try again.')
  }
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedReturn.value = null
}

const getStatusBadgeClass = (status) => {
  const statusClasses = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-blue-100 text-blue-800',
    'completed': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800'
  }
  return statusClasses[status] || statusClasses.pending
}

const formatReason = (reason) => {
  const reasonMap = {
    'defective': 'Defective Product',
    'wrong_item': 'Wrong Item',
    'customer_change_mind': 'Customer Changed Mind',
    'damaged_shipping': 'Damaged in Shipping',
    'not_as_described': 'Not as Described',
    'duplicate_order': 'Duplicate Order',
    'other': 'Other'
  }
  return reasonMap[reason] || reason
}

const formatRefundMethod = (method) => {
  const methodMap = {
    'original_payment': 'Original Payment Method',
    'cash': 'Cash',
    'store_credit': 'Store Credit',
    'exchange': 'Exchange Only'
  }
  return methodMap[method] || method
}

const getMethodName = (method) => {
  const methodMap = {
    'cash': 'Cash',
    'stripe': 'Stripe',
    'visa': 'Visa',
    'mastercard': 'Mastercard',
    'amex': 'American Express',
    'tng': 'Touch n Go'
  }
  return methodMap[method] || method
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-MY', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  fetchRecentReturns()
})
</script>