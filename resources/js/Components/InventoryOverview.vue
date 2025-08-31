<template>
  <div class="space-y-6">
    <!-- Enhanced Header Section -->
    <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-700 dark:from-emerald-700 dark:via-teal-700 dark:to-cyan-800 rounded-xl shadow-lg dark:shadow-2xl p-6 text-white">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <!-- Title Section -->
        <div class="flex items-center space-x-4 mb-4 lg:mb-0">
          <div class="p-3 bg-white/10 backdrop-blur-sm rounded-lg">
            <Package class="w-8 h-8 text-white" />
          </div>
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Inventory Overview</h1>
            <p class="text-blue-100 mt-1">Advanced inventory management and real-time analytics</p>
            <div class="flex items-center mt-2 text-sm text-blue-100">
              <span class="flex items-center">
                <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                Live tracking enabled
              </span>
              <span class="mx-3">•</span>
              <span>Last updated: {{ new Date().toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
              }) }}</span>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3">
          <button
            @click="refreshInventory"
            :disabled="isRefreshing"
            class="flex items-center px-4 py-2.5 bg-white/10 backdrop-blur-sm text-white rounded-lg hover:bg-white/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 border border-white/20"
          >
            <RefreshCw :class="['w-4 h-4 mr-2', isRefreshing ? 'animate-spin' : '']" />
            {{ isRefreshing ? 'Refreshing...' : 'Refresh Data' }}
          </button>

          <button
            @click="showBulkActions = !showBulkActions"
            :class="[
              'flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 border',
              showBulkActions
                ? 'bg-white text-teal-600 border-white shadow-lg'
                : 'bg-white/10 backdrop-blur-sm text-white border-white/20 hover:bg-white/20'
            ]"
          >
            <Settings class="w-4 h-4 mr-2" />
            Bulk Actions
            <span v-if="selectedItems.length > 0" class="ml-2 px-2 py-0.5 bg-orange-500 text-white text-xs rounded-full">
              {{ selectedItems.length }}
            </span>
          </button>

          <button
            @click="showExportModal = true"
            class="flex items-center px-4 py-2.5 bg-white/10 backdrop-blur-sm text-white rounded-lg hover:bg-white/20 transition-all duration-200 border border-white/20"
          >
            <Download class="w-4 h-4 mr-2" />
            Export Report
          </button>
        </div>
      </div>

      <!-- Quick Stats Bar -->
      <div class="mt-6 pt-6 border-t border-white/20">
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
          <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 text-center">
            <div class="text-2xl font-bold">{{ totalItems }}</div>
            <div class="text-blue-100 text-sm">Total Items</div>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-amber-200">RM{{ totalInventoryValue.toFixed(0) }}</div>
            <div class="text-emerald-100 text-sm">Total Value</div>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-orange-200">{{ lowStockItems.length }}</div>
            <div class="text-emerald-100 text-sm">Low Stock</div>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-red-200">{{ outOfStockItems.length }}</div>
            <div class="text-emerald-100 text-sm">Out of Stock</div>
          </div>
          <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-violet-200">{{ averageDaysStock.toFixed(0) }}</div>
            <div class="text-emerald-100 text-sm">Avg. Days Stock</div>
          </div>
        </div>
      </div>
    </div>


    <!-- Bulk Actions Panel -->
    <div v-if="showBulkActions" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Bulk Actions</h3>
        <button @click="showBulkActions = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
          <X class="w-5 h-5" />
        </button>
      </div>
      <div class="flex flex-wrap gap-3">
        <button
          @click="openBulkReorderModal"
          :disabled="selectedItems.length === 0"
          class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <ShoppingCart class="w-4 h-4 mr-2 inline" />
          Reorder Selected ({{ selectedItems.length }})
        </button>
        <button
          @click="openBulkAdjustModal"
          :disabled="selectedItems.length === 0"
          class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <Edit class="w-4 h-4 mr-2 inline" />
          Adjust Stock
        </button>
        <button
          @click="openBulkCategorizeModal"
          :disabled="selectedItems.length === 0"
          class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <Tag class="w-4 h-4 mr-2 inline" />
          Update Categories
        </button>
        <button
          @click="selectedItems = []"
          class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
        >
          Clear Selection
        </button>
      </div>
    </div>

    <!-- Advanced Search and Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
      <div class="space-y-4">
        <!-- Search Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="relative flex-1">
            <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
            <input
              v-model="searchQuery"
              placeholder="Search by name, SKU, category, supplier"
              class="w-full pl-9 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            />
          </div>
          <div class="flex gap-2">
            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-gray-100">
              All: {{ paginatedItems.length }}
            </span>
            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
              Low: {{ lowStockItems.length }}
            </span>
            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
              Out: {{ outOfStockItems.length }}
            </span>
          </div>
        </div>

        <!-- Filters and Sorting -->
        <div class="flex flex-col lg:flex-row gap-4">
          <!-- Filters -->
          <div class="flex flex-wrap gap-4 items-center">
            <!-- Stock Status Filter -->
            <div>
              <select
                v-model="filters.stockStatus"
                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 text-sm"
              >
                <option value="">All Stock Status</option>
                <option value="in_stock">In Stock</option>
                <option value="low_stock">Low Stock</option>
                <option value="out_of_stock">Out of Stock</option>
              </select>
            </div>

            <!-- Category Filter -->
            <div>
              <select
                v-model="filters.category"
                class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Categories</option>
                <option v-for="category in uniqueCategories" :key="category" :value="category">
                  {{ category }}
                </option>
              </select>
            </div>

            <!-- Supplier Filter -->
            <div>
              <select
                v-model="filters.supplier"
                class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
              >
                <option value="">All Suppliers</option>
                <option v-for="supplier in uniqueSuppliers" :key="supplier" :value="supplier">
                  {{ supplier }}
                </option>
              </select>
            </div>

            <!-- Value Range -->
            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-600">Value:</span>
              <input
                v-model.number="filters.minValue"
                type="number"
                placeholder="Min"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                min="0"
                step="0.01"
              />
              <span class="text-gray-400">-</span>
              <input
                v-model.number="filters.maxValue"
                type="number"
                placeholder="Max"
                class="w-20 px-2 py-1 border rounded focus:ring-1 focus:ring-blue-500 text-sm"
                min="0"
                step="0.01"
              />
            </div>

            <!-- Clear Filters -->
            <button
              v-if="hasActiveFilters"
              @click="clearFilters"
              class="text-sm text-teal-600 hover:text-teal-800"
            >
              Clear filters
            </button>
          </div>

          <!-- Sorting -->
          <div class="flex items-center gap-2 lg:ml-auto">
            <span class="text-sm text-gray-600">Sort by:</span>
            <select
              v-model="sortBy"
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 text-sm"
            >
              <option value="name">Name</option>
              <option value="sku">SKU</option>
              <option value="category">Category</option>
              <option value="currentStock">Stock Level</option>
              <option value="daysOfStock">Days of Stock</option>
              <option value="value">Value</option>
            </select>
            <button
              @click="toggleSortDirection"
              class="p-2 border rounded-lg hover:bg-gray-50"
              :title="sortDirection === 'asc' ? 'Sort descending' : 'Sort ascending'"
            >
              <ArrowUpDown class="w-4 h-4" :class="sortDirection === 'desc' ? 'rotate-180' : ''" />
            </button>
          </div>
        </div>
      </div>

      <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
            <tr>
              <th class="w-12 px-4 py-2">
                <input
                  type="checkbox"
                  :checked="selectAll"
                  @change="toggleSelectAll"
                  class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                />
              </th>
              <th class="text-left px-4 py-2 font-medium">Product</th>
              <th class="text-left px-4 py-2 font-medium">SKU</th>
              <th class="text-left px-4 py-2 font-medium">Category</th>
              <th class="text-right px-4 py-2 font-medium">Stock</th>
              <th class="text-right px-4 py-2 font-medium">Value</th>
              <th class="text-right px-4 py-2 font-medium">Days of Stock</th>
              <th class="text-left px-4 py-2 font-medium">Supplier</th>
              <th class="text-left px-4 py-2 font-medium">Status</th>
              <th class="text-left px-4 py-2 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in paginatedItems" :key="item.id" class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-4 py-2">
                <input
                  type="checkbox"
                  :value="item.id"
                  v-model="selectedItems"
                  class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                />
              </td>
              <td class="px-4 py-2 font-medium text-gray-900">
                <div class="flex items-center">
                  <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded mr-3 flex items-center justify-center">
                    <Package class="w-4 h-4 text-gray-400" />
                  </div>
                  <div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ item.name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ item.description || 'No description' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-2">
                <span class="font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-900 dark:text-gray-100">{{ item.sku }}</span>
              </td>
              <td class="px-4 py-2">
                <span class="px-2 py-1 text-xs bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 rounded-full">{{ item.category }}</span>
              </td>
              <td class="px-4 py-2 text-right">
                <div class="font-medium text-gray-900 dark:text-gray-100">{{ item.currentStock }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ item.unit || 'units' }}</div>
              </td>
              <td class="px-4 py-2 text-right">
                <div class="font-medium text-gray-900 dark:text-gray-100">RM {{ (item.currentStock * (item.price || 0)).toFixed(2) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">RM {{ (item.price || 0).toFixed(2) }}/unit</div>
              </td>
              <td class="px-4 py-2 text-right">
                <div :class="[
                  'font-medium',
                  item.daysOfStock < 7 ? 'text-red-600' :
                  item.daysOfStock < 30 ? 'text-amber-600' : 'text-emerald-600'
                ]">
                  {{ item.daysOfStock.toFixed(1) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">days</div>
              </td>
              <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ item.supplier }}</td>
              <td class="px-4 py-2">
                <span :class="statusClass(item)">{{ statusText(item) }}</span>
              </td>
              <td class="px-4 py-2">
                <div class="flex space-x-2">
                  <button
                    @click="adjustStock(item)"
                    class="p-1 text-teal-600 hover:text-teal-800"
                    title="Adjust Stock"
                  >
                    <Edit class="w-4 h-4" />
                  </button>
                  <button
                    @click="reorderItem(item)"
                    class="p-1 text-emerald-600 hover:text-emerald-800"
                    title="Reorder"
                  >
                    <ShoppingCart class="w-4 h-4" />
                  </button>
                  <button
                    @click="viewDetails(item)"
                    class="p-1 text-gray-600 hover:text-gray-800"
                    title="View Details"
                  >
                    <Eye class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredItems.length === 0">
              <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                {{ hasActiveFilters ? 'No inventory items match your search criteria.' : 'No inventory items found.' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Enhanced Pagination -->
      <div v-if="totalPages > 1" class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
          <!-- Items Info and Per Page Selector -->
          <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Showing <span class="font-medium">{{ (currentPage - 1) * itemsPerPage + 1 }}</span>
              to <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, filteredItems.length) }}</span>
              of <span class="font-medium">{{ filteredItems.length }}</span> items
            </div>
            <div class="flex items-center space-x-2">
              <label class="text-sm text-gray-600 dark:text-gray-400">Show:</label>
              <select
                v-model="itemsPerPage"
                @change="currentPage = 1"
                class="px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span class="text-sm text-gray-600 dark:text-gray-400">per page</span>
            </div>
          </div>

          <!-- Pagination Controls -->
          <div class="flex items-center justify-center space-x-1">
            <!-- First Page -->
            <button
              @click="currentPage = 1"
              :disabled="currentPage === 1"
              class="px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              title="First page"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M21 19l-7-7 7-7"/>
              </svg>
            </button>

            <!-- Previous Page -->
            <button
              @click="currentPage = Math.max(1, currentPage - 1)"
              :disabled="currentPage === 1"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border-t border-b border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>

            <!-- Page Numbers -->
            <div class="flex space-x-0">
              <button
                v-for="page in visiblePages"
                :key="page"
                @click="currentPage = page"
                :class="[
                  'px-3 py-2 text-sm font-medium border-t border-b border-gray-300 transition-colors',
                  page === currentPage
                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                    : 'bg-white text-gray-700 hover:bg-gray-50'
                ]"
              >
                {{ page }}
              </button>
            </div>

            <!-- Next Page -->
            <button
              @click="currentPage = Math.min(totalPages, currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border-t border-b border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </button>

            <!-- Last Page -->
            <button
              @click="currentPage = totalPages"
              :disabled="currentPage === totalPages"
              class="px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              title="Last page"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M3 5l7 7-7 7"/>
              </svg>
            </button>
          </div>

          <!-- Jump to Page -->
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">Go to:</span>
            <input
              v-model.number="jumpToPage"
              @keyup.enter="goToPage"
              type="number"
              :min="1"
              :max="totalPages"
              class="w-16 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              placeholder="Page"
            />
            <button
              @click="goToPage"
              class="px-3 py-1 text-sm font-medium text-white bg-emerald-600 rounded hover:bg-emerald-700 transition-colors"
            >
              Go
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Modal -->
    <Modal :show="showExportModal" @close="showExportModal = false" title="Export Inventory Report">
      <div class="space-y-4">
        <p class="text-gray-600">Choose the export format for your inventory report:</p>

        <div class="grid grid-cols-1 gap-3">
          <button
            @click="exportInventory('pdf')"
            :disabled="isExporting"
            class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <FileText class="w-5 h-5 mr-3 text-red-600" />
            <span class="font-medium">Export as PDF</span>
          </button>

          <button
            @click="exportInventory('excel')"
            :disabled="isExporting"
            class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <FileSpreadsheet class="w-5 h-5 mr-3 text-emerald-600" />
            <span class="font-medium">Export as Excel</span>
          </button>

          <button
            @click="exportInventory('csv')"
            :disabled="isExporting"
            class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <FileX class="w-5 h-5 mr-3 text-teal-600" />
            <span class="font-medium">Export as CSV</span>
          </button>
        </div>

        <div v-if="isExporting" class="flex items-center justify-center py-4">
          <RefreshCw class="w-5 h-5 animate-spin mr-2" />
          <span>Generating export...</span>
        </div>

        <div class="text-sm text-gray-500">
          <p><strong>Export includes:</strong> {{ filteredItems.length }} items</p>
          <p>Current filters and search will be applied to the export.</p>
        </div>
      </div>
    </Modal>

    <!-- Reorder Item Modal -->
    <Modal :show="showReorderModal" @close="showReorderModal = false" title="Reorder Item">
      <div v-if="selectedItem" class="space-y-4">
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="font-medium text-gray-900">{{ selectedItem.name }}</h4>
          <p class="text-sm text-gray-600">SKU: {{ selectedItem.sku }}</p>
          <p class="text-sm text-gray-600">Current Stock: {{ selectedItem.currentStock }}</p>
          <p class="text-sm text-gray-600">Low Stock Threshold: {{ selectedItem.lowStockThreshold || 'Not set' }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Quantity</label>
          <input
            v-model.number="reorderQuantity"
            type="number"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            placeholder="Enter quantity to reorder"
          />
        </div>

        <div class="flex gap-3">
          <button
            @click="confirmReorder"
            :disabled="!reorderQuantity || reorderQuantity <= 0"
            class="flex-1 bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <ShoppingCart class="w-4 h-4 mr-2 inline" />
            Add to Reorder List
          </button>
          <button
            @click="showReorderModal = false"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>

    <!-- View Details Modal -->
    <Modal :show="showViewDetailsModal" @close="showViewDetailsModal = false" title="Item Details" size="lg">
      <div v-if="selectedItem" class="space-y-6">
        <!-- Basic Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900 border-b pb-2">Basic Information</h4>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Name:</span>
                <span class="text-sm font-medium">{{ selectedItem.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">SKU:</span>
                <span class="text-sm font-medium">{{ selectedItem.sku }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Category:</span>
                <span class="text-sm font-medium">{{ selectedItem.category || 'Uncategorized' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Supplier:</span>
                <span class="text-sm font-medium">{{ selectedItem.supplier || 'N/A' }}</span>
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <h4 class="font-medium text-gray-900 border-b pb-2">Stock Information</h4>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Current Stock:</span>
                <span class="text-sm font-medium" :class="selectedItem.currentStock <= (selectedItem.lowStockThreshold || 10) ? 'text-red-600' : 'text-emerald-600'">
                  {{ selectedItem.currentStock }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Low Stock Threshold:</span>
                <span class="text-sm font-medium">{{ selectedItem.lowStockThreshold || 'Not set' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Unit Price:</span>
                <span class="text-sm font-medium">RM {{ selectedItem.price?.toFixed(2) || '0.00' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Total Value:</span>
                <span class="text-sm font-medium">RM {{ ((selectedItem.currentStock || 0) * (selectedItem.price || 0)).toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Status Badge -->
        <div>
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="statusClass(selectedItem)">
            {{ statusText(selectedItem) }}
          </span>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 pt-4 border-t">
          <button
            @click="openReorderFromDetails"
            class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700"
          >
            <ShoppingCart class="w-4 h-4 mr-2 inline" />
            Reorder Item
          </button>
          <button
            @click="showViewDetailsModal = false"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Close
          </button>
        </div>
      </div>
    </Modal>

    <!-- Bulk Reorder Modal -->
    <Modal :show="showBulkReorderModal" @close="showBulkReorderModal = false" title="Bulk Reorder">
      <div class="space-y-4">
        <div class="bg-blue-50 rounded-lg p-4">
          <p class="text-sm text-blue-800">
            <strong>{{ selectedItems.length }}</strong> items selected for reordering
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Default Reorder Quantity</label>
          <input
            v-model.number="reorderQuantity"
            type="number"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            placeholder="Enter default quantity for all items"
          />
          <p class="text-xs text-gray-500 mt-1">This quantity will be applied to all selected items</p>
        </div>

        <div class="flex gap-3">
          <button
            @click="confirmBulkReorder"
            :disabled="!reorderQuantity || reorderQuantity <= 0"
            class="flex-1 bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Add {{ selectedItems.length }} Items to Reorder
          </button>
          <button
            @click="showBulkReorderModal = false"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>

    <!-- Bulk Stock Adjustment Modal -->
    <Modal :show="showBulkAdjustModal" @close="showBulkAdjustModal = false" title="Bulk Stock Adjustment">
      <div class="space-y-4">
        <div class="bg-yellow-50 rounded-lg p-4">
          <p class="text-sm text-yellow-800">
            <strong>{{ selectedItems.length }}</strong> items selected for stock adjustment
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Adjustment Type</label>
          <div class="flex gap-4">
            <label class="flex items-center">
              <input v-model="bulkAdjustmentType" type="radio" value="add" class="mr-2" />
              <Plus class="w-4 h-4 mr-1 text-emerald-600" />
              Add Stock
            </label>
            <label class="flex items-center">
              <input v-model="bulkAdjustmentType" type="radio" value="subtract" class="mr-2" />
              <Minus class="w-4 h-4 mr-1 text-red-600" />
              Reduce Stock
            </label>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Adjustment Amount</label>
          <input
            v-model.number="bulkAdjustmentAmount"
            type="number"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
            :placeholder="`Enter amount to ${bulkAdjustmentType === 'add' ? 'add to' : 'subtract from'} all selected items`"
          />
        </div>

        <div class="flex gap-3">
          <button
            @click="confirmBulkAdjustment"
            :disabled="!bulkAdjustmentAmount || bulkAdjustmentAmount <= 0"
            class="flex-1 bg-amber-600 text-white px-4 py-2 rounded-lg hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ bulkAdjustmentType === 'add' ? 'Add' : 'Subtract' }} {{ bulkAdjustmentAmount || 0 }} from {{ selectedItems.length }} Items
          </button>
          <button
            @click="showBulkAdjustModal = false"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>

    <!-- Bulk Categorize Modal -->
    <Modal :show="showBulkCategorizeModal" @close="showBulkCategorizeModal = false" title="Bulk Update Categories">
      <div class="space-y-4">
        <div class="bg-blue-50 rounded-lg p-4">
          <p class="text-sm text-blue-800">
            <strong>{{ selectedItems.length }}</strong> items selected for category update
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">New Category</label>
          <select
            v-model="newCategoryId"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select a category</option>
            <option v-for="category in uniqueCategories" :key="category" :value="category">
              {{ category }}
            </option>
          </select>
        </div>

        <div class="flex gap-3">
          <button
            @click="confirmBulkCategorize"
            :disabled="!newCategoryId"
            class="flex-1 bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Update {{ selectedItems.length }} Items
          </button>
          <button
            @click="showBulkCategorizeModal = false"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, reactive, ref, onMounted } from 'vue'
import { useInventoryStore } from '@/stores/inventory'
import { useNotificationStore } from '@/stores/notifications'
import jsPDF from 'jspdf'
import * as XLSX from 'xlsx'
import Modal from './Modal.vue'
import {
  Search, ArrowUpDown, RefreshCw, Settings, Download, Package,
  DollarSign, AlertTriangle, XCircle, TrendingUp, X, ShoppingCart,
  Edit, Tag, Eye, FileText, FileSpreadsheet, FileX, Plus, Minus
} from 'lucide-vue-next'

const inventory = useInventoryStore()
const notificationStore = useNotificationStore()

// State
const searchQuery = ref('')
const selectedItems = ref([])
const showBulkActions = ref(false)
const isRefreshing = ref(false)

// Modal states
const showReorderModal = ref(false)
const showViewDetailsModal = ref(false)
const showBulkReorderModal = ref(false)
const showBulkAdjustModal = ref(false)
const showBulkCategorizeModal = ref(false)
const showExportModal = ref(false)

// Modal data
const selectedItem = ref(null)
const reorderQuantity = ref(0)
const bulkAdjustmentAmount = ref(0)
const bulkAdjustmentType = ref('add') // 'add' or 'subtract'
const newCategoryId = ref('')
const isExporting = ref(false)

// Filters and sorting
const filters = reactive({
  stockStatus: '',
  category: '',
  supplier: '',
  minValue: null,
  maxValue: null
})

const sortBy = ref('name')
const sortDirection = ref('asc')

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(20)
const jumpToPage = ref(null)

// Fetch inventory data on component mount
onMounted(() => {
  inventory.fetchInventoryData().catch(() => {})
})

// Computed properties
const hasActiveFilters = computed(() => {
  return searchQuery.value.trim() || filters.stockStatus || filters.category ||
         filters.supplier || filters.minValue !== null || filters.maxValue !== null
})

const uniqueCategories = computed(() => {
  const categories = new Set()
  inventory.inventoryItems.forEach(item => {
    if (item.category) categories.add(item.category)
  })
  return Array.from(categories).sort()
})

const uniqueSuppliers = computed(() => {
  const suppliers = new Set()
  inventory.inventoryItems.forEach(item => {
    if (item.supplier) suppliers.add(item.supplier)
  })
  return Array.from(suppliers).sort()
})

const filteredItems = computed(() => {
  let items = inventory.inventoryItems

  // Apply search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    items = items.filter(item =>
      item.name.toLowerCase().includes(query) ||
      item.sku.toLowerCase().includes(query) ||
      item.category.toLowerCase().includes(query) ||
      item.supplier.toLowerCase().includes(query)
    )
  }

  // Apply stock status filter
  if (filters.stockStatus) {
    items = items.filter(item => {
      if (filters.stockStatus === 'out_of_stock') return item.currentStock === 0
      if (filters.stockStatus === 'low_stock') return item.currentStock > 0 && item.currentStock <= item.lowStockThreshold
      if (filters.stockStatus === 'in_stock') return item.currentStock > item.lowStockThreshold
      return true
    })
  }

  // Apply category filter
  if (filters.category) {
    items = items.filter(item => item.category === filters.category)
  }

  // Apply supplier filter
  if (filters.supplier) {
    items = items.filter(item => item.supplier === filters.supplier)
  }

  // Apply value range filter
  if (filters.minValue !== null && filters.minValue !== '') {
    items = items.filter(item => (item.currentStock * (item.price || 0)) >= filters.minValue)
  }

  if (filters.maxValue !== null && filters.maxValue !== '') {
    items = items.filter(item => (item.currentStock * (item.price || 0)) <= filters.maxValue)
  }

  // Apply sorting
  items = [...items].sort((a, b) => {
    let aValue, bValue

    switch (sortBy.value) {
      case 'name':
        aValue = a.name.toLowerCase()
        bValue = b.name.toLowerCase()
        break
      case 'sku':
        aValue = a.sku.toLowerCase()
        bValue = b.sku.toLowerCase()
        break
      case 'category':
        aValue = a.category.toLowerCase()
        bValue = b.category.toLowerCase()
        break
      case 'currentStock':
        aValue = Number(a.currentStock)
        bValue = Number(b.currentStock)
        break
      case 'daysOfStock':
        aValue = Number(a.daysOfStock)
        bValue = Number(b.daysOfStock)
        break
      case 'value':
        aValue = Number(a.currentStock * (a.price || 0))
        bValue = Number(b.currentStock * (b.price || 0))
        break
      default:
        return 0
    }

    if (sortDirection.value === 'asc') {
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
    } else {
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
    }
  })

  return items
})

const totalPages = computed(() => Math.ceil(filteredItems.value.length / itemsPerPage.value))

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredItems.value.slice(start, end)
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const delta = 2

  let start = Math.max(1, current - delta)
  let end = Math.min(total, current + delta)

  if (end - start < 2 * delta) {
    start = Math.max(1, end - 2 * delta)
    end = Math.min(total, start + 2 * delta)
  }

  const pages = []
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

const selectAll = computed({
  get() {
    return paginatedItems.value.length > 0 &&
           paginatedItems.value.every(item => selectedItems.value.includes(item.id))
  },
  set(value) {
    if (value) {
      selectedItems.value = [...new Set([...selectedItems.value, ...paginatedItems.value.map(item => item.id)])]
    } else {
      selectedItems.value = selectedItems.value.filter(id =>
        !paginatedItems.value.some(item => item.id === id)
      )
    }
  }
})

// Enhanced computed stats
const totalItems = computed(() => inventory.inventoryItems.length)
const lowStockItems = computed(() => inventory.lowStockItems)
const outOfStockItems = computed(() => inventory.outOfStockItems)
const totalInventoryValue = computed(() => inventory.totalInventoryValue)

const averageDaysStock = computed(() => {
  const items = inventory.inventoryItems
  if (items.length === 0) return 0
  const total = items.reduce((sum, item) => sum + item.daysOfStock, 0)
  return total / items.length
})

// Methods
const toggleSelectAll = () => {
  selectAll.value = !selectAll.value
}

const clearFilters = () => {
  searchQuery.value = ''
  filters.stockStatus = ''
  filters.category = ''
  filters.supplier = ''
  filters.minValue = null
  filters.maxValue = null
  currentPage.value = 1
}

const toggleSortDirection = () => {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
}

const goToPage = () => {
  if (jumpToPage.value && jumpToPage.value >= 1 && jumpToPage.value <= totalPages.value) {
    currentPage.value = jumpToPage.value
    jumpToPage.value = null
  }
}

const refreshInventory = async () => {
  isRefreshing.value = true
  try {
    await inventory.fetchInventoryData()
    notificationStore.success('Inventory Updated', 'Inventory data has been refreshed successfully')
  } catch (error) {
    notificationStore.error('Refresh Failed', 'Failed to refresh inventory data')
  } finally {
    isRefreshing.value = false
  }
}

// Enhanced Export Functions
const exportInventory = async (format) => {
  try {
    isExporting.value = true

    const timestamp = new Date().toISOString().split('T')[0]
    const filename = `inventory-report-${timestamp}`

    // Validate data before export
    if (!filteredItems.value || filteredItems.value.length === 0) {
      throw new Error('No inventory data to export')
    }

    switch (format) {
      case 'pdf':
        await generatePDFExport(filename)
        break
      case 'excel':
        generateExcelExport(filename)
        break
      case 'csv':
        generateCSVExport(filename)
        break
      default:
        throw new Error(`Unsupported export format: ${format}`)
    }

    notificationStore.success('Export Complete', `Inventory exported as ${format.toUpperCase()} successfully`)
    showExportModal.value = false
  } catch (error) {
    console.error('Export failed:', error)
    notificationStore.error('Export Failed', error.message || 'Failed to export inventory data')
  } finally {
    isExporting.value = false
  }
}

const generatePDFExport = async (filename) => {
  try {
    const doc = new jsPDF('l', 'mm', 'a4') // Landscape for better table fit

    // Header with logo space
    doc.setFontSize(20)
    doc.setFont('helvetica', 'bold')
    doc.text('Inventory Report', 20, 20)

    // Company info
    doc.setFontSize(12)
    doc.setFont('helvetica', 'normal')
    doc.text('SuperMarket POS System', 20, 30)

    // Report info
    doc.setFontSize(10)
    doc.text(`Generated: ${new Date().toLocaleString()}`, 20, 40)
    doc.text(`Total Items: ${filteredItems.value.length}`, 20, 45)
    doc.text(`Low Stock Items: ${lowStockItems.value.length}`, 20, 50)
    doc.text(`Out of Stock Items: ${outOfStockItems.value.length}`, 20, 55)

    // Summary stats
    doc.setFontSize(12)
    doc.setFont('helvetica', 'bold')
    doc.text('Summary Statistics', 20, 70)

    doc.setFontSize(10)
    doc.setFont('helvetica', 'normal')
    doc.text(`Total Inventory Value: RM${totalInventoryValue.value.toFixed(2)}`, 25, 80)
    doc.text(`Average Days Stock: ${averageDaysStock.value.toFixed(1)} days`, 25, 85)

    // Table headers
    doc.setFontSize(9)
    doc.setFont('helvetica', 'bold')
    const startY = 100
    const colWidths = [50, 25, 30, 25, 20, 25, 25, 25]
    const headers = ['Name', 'SKU', 'Category', 'Supplier', 'Stock', 'Unit Price', 'Total Value', 'Status']
    let xPos = 20

    headers.forEach((header, index) => {
      doc.text(header, xPos, startY)
      xPos += colWidths[index]
    })

    // Draw header line
    doc.line(20, startY + 2, 270, startY + 2)

    // Items data
    doc.setFont('helvetica', 'normal')
    doc.setFontSize(8)

    const maxItems = Math.min(25, filteredItems.value.length) // Limit for PDF

    filteredItems.value.slice(0, maxItems).forEach((item, index) => {
      const y = startY + 8 + (index * 6)
      let xPos = 20

      // Ensure we have valid data
      const itemData = [
        (item.name || 'N/A').substring(0, 20),
        item.sku || 'N/A',
        (item.category || 'Uncategorized').substring(0, 12),
        (item.supplier || 'N/A').substring(0, 10),
        (item.currentStock || 0).toString(),
        `RM${(item.price || 0).toFixed(2)}`,
        `RM${((item.currentStock || 0) * (item.price || 0)).toFixed(2)}`,
        statusText(item)
      ]

      itemData.forEach((data, colIndex) => {
        doc.text(data, xPos, y)
        xPos += colWidths[colIndex]
      })
    })

    if (filteredItems.value.length > maxItems) {
      doc.text(`... and ${filteredItems.value.length - maxItems} more items`, 25, startY + 8 + (maxItems * 6) + 10)
    }

    // Footer
    doc.setFontSize(8)
    doc.text(`Report generated on ${new Date().toLocaleString()}`, 20, 280)

    doc.save(`${filename}.pdf`)
  } catch (error) {
    console.error('PDF generation failed:', error)
    throw error
  }
}

const generateExcelExport = (filename) => {
  const workbook = XLSX.utils.book_new()

  // Summary sheet
  const summaryData = [
    ['Inventory Report Summary'],
    [''],
    ['Report Details'],
    ['Generated', new Date().toLocaleString()],
    ['Total Items', filteredItems.value.length],
    ['Low Stock Items', lowStockItems.value.length],
    ['Out of Stock Items', outOfStockItems.value.length],
    ['Total Value', totalInventoryValue.value],
    ['Average Days Stock', averageDaysStock.value.toFixed(1)],
    [''],
    ['Filters Applied'],
    ['Search Query', searchQuery.value || 'None'],
    ['Stock Status Filter', filters.stockStatus || 'None'],
    ['Category Filter', filters.category || 'None'],
    ['Supplier Filter', filters.supplier || 'None']
  ]

  const summarySheet = XLSX.utils.aoa_to_sheet(summaryData)
  summarySheet['!cols'] = [{ width: 20 }, { width: 30 }]
  XLSX.utils.book_append_sheet(workbook, summarySheet, 'Summary')

  // Inventory data sheet
  const inventoryData = [
    ['Inventory Items'],
    [''],
    ['Name', 'SKU', 'Category', 'Supplier', 'Current Stock', 'Low Stock Threshold', 'Unit Price', 'Total Value', 'Days of Stock', 'Status']
  ]

  filteredItems.value.forEach(item => {
    inventoryData.push([
      item.name,
      item.sku,
      item.category || 'Uncategorized',
      item.supplier || 'N/A',
      item.currentStock,
      item.lowStockThreshold || 0,
      item.price || 0,
      ((item.currentStock || 0) * (item.price || 0)).toFixed(2),
      item.daysOfStock?.toFixed(1) || '0.0',
      statusText(item)
    ])
  })

  const inventorySheet = XLSX.utils.aoa_to_sheet(inventoryData)
  inventorySheet['!cols'] = [
    { width: 30 }, { width: 15 }, { width: 20 }, { width: 20 },
    { width: 12 }, { width: 15 }, { width: 12 }, { width: 12 },
    { width: 12 }, { width: 15 }
  ]
  XLSX.utils.book_append_sheet(workbook, inventorySheet, 'Inventory Items')

  // Low stock items sheet
  const lowStockData = [
    ['Low Stock Alert Items'],
    [''],
    ['Name', 'SKU', 'Current Stock', 'Low Stock Threshold', 'Shortage', 'Unit Price', 'Reorder Value Required']
  ]

  lowStockItems.value.forEach(item => {
    const shortage = Math.max(0, (item.lowStockThreshold || 10) - item.currentStock)
    lowStockData.push([
      item.name,
      item.sku,
      item.currentStock,
      item.lowStockThreshold || 10,
      shortage,
      item.price || 0,
      (shortage * (item.price || 0)).toFixed(2)
    ])
  })

  const lowStockSheet = XLSX.utils.aoa_to_sheet(lowStockData)
  lowStockSheet['!cols'] = [
    { width: 30 }, { width: 15 }, { width: 12 }, { width: 15 },
    { width: 12 }, { width: 12 }, { width: 15 }
  ]
  XLSX.utils.book_append_sheet(workbook, lowStockSheet, 'Low Stock Alerts')

  XLSX.writeFile(workbook, `${filename}.xlsx`)
}

const generateCSVExport = (filename) => {
  try {
    const headers = [
      'Name', 'SKU', 'Category', 'Supplier', 'Current Stock',
      'Low Stock Threshold', 'Unit Price (RM)', 'Total Value (RM)', 'Days of Stock', 'Status'
    ]

    const csvContent = [
      '# Inventory Report',
      `# Generated: ${new Date().toLocaleString()}`,
      `# Total Items: ${filteredItems.value.length}`,
      `# Low Stock: ${lowStockItems.value.length}, Out of Stock: ${outOfStockItems.value.length}`,
      `# Total Value: RM${totalInventoryValue.value.toFixed(2)}`,
      `# Filters: Search="${searchQuery.value || 'None'}", Stock="${filters.stockStatus || 'All'}", Category="${filters.category || 'All'}", Supplier="${filters.supplier || 'All'}"`,
      '',
      headers.join(','),
      ...filteredItems.value.map(item => {
        // Escape commas and quotes in text fields
        const escapeCSV = (str) => {
          if (str == null) return ''
          const stringified = String(str)
          if (stringified.includes(',') || stringified.includes('"') || stringified.includes('\n')) {
            return `"${stringified.replace(/"/g, '""')}"`
          }
          return stringified
        }

        return [
          escapeCSV(item.name || 'N/A'),
          escapeCSV(item.sku || 'N/A'),
          escapeCSV(item.category || 'Uncategorized'),
          escapeCSV(item.supplier || 'N/A'),
          item.currentStock || 0,
          item.lowStockThreshold || 0,
          (item.price || 0).toFixed(2),
          ((item.currentStock || 0) * (item.price || 0)).toFixed(2),
          item.daysOfStock?.toFixed(1) || '0.0',
          escapeCSV(statusText(item))
        ].join(',')
      })
    ].join('\n')

    // Add BOM for proper UTF-8 encoding
    const BOM = '\uFEFF'
    const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${filename}.csv`
    link.style.display = 'none'
    document.body.appendChild(link)
    link.click()

    // Cleanup
    setTimeout(() => {
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    }, 100)
  } catch (error) {
    console.error('CSV generation failed:', error)
    throw error
  }
}

// Enhanced Modal Functions
const reorderItem = (item) => {
  selectedItem.value = item
  reorderQuantity.value = Math.max(1, (item.lowStockThreshold || 10) - item.currentStock)
  showReorderModal.value = true
}

const viewDetails = (item) => {
  selectedItem.value = item
  showViewDetailsModal.value = true
}

const openReorderFromDetails = () => {
  showViewDetailsModal.value = false
  showReorderModal.value = true
  reorderQuantity.value = Math.max(1, (selectedItem.value.lowStockThreshold || 10) - selectedItem.value.currentStock)
}

const confirmReorder = () => {
  if (reorderQuantity.value > 0 && selectedItem.value) {
    notificationStore.success(
      'Reorder Added',
      `${selectedItem.value.name} - ${reorderQuantity.value} units added to reorder list`
    )
    showReorderModal.value = false
    reorderQuantity.value = 0
    selectedItem.value = null
  }
}

// Bulk Action Modal Functions
const openBulkReorderModal = () => {
  if (selectedItems.value.length === 0) {
    notificationStore.warning('No Selection', 'Please select items to perform bulk reorder')
    return
  }
  reorderQuantity.value = 10 // Default bulk quantity
  showBulkReorderModal.value = true
}

const openBulkAdjustModal = () => {
  if (selectedItems.value.length === 0) {
    notificationStore.warning('No Selection', 'Please select items to adjust stock')
    return
  }
  bulkAdjustmentAmount.value = 0
  bulkAdjustmentType.value = 'add'
  showBulkAdjustModal.value = true
}

const openBulkCategorizeModal = () => {
  if (selectedItems.value.length === 0) {
    notificationStore.warning('No Selection', 'Please select items to update categories')
    return
  }
  newCategoryId.value = ''
  showBulkCategorizeModal.value = true
}

const confirmBulkReorder = () => {
  if (reorderQuantity.value > 0) {
    notificationStore.success(
      'Bulk Reorder Added',
      `${selectedItems.value.length} items with ${reorderQuantity.value} units each added to reorder list`
    )
    showBulkReorderModal.value = false
    selectedItems.value = []
    reorderQuantity.value = 0
    showBulkActions.value = false
  }
}

const confirmBulkAdjustment = () => {
  if (bulkAdjustmentAmount.value > 0) {
    const action = bulkAdjustmentType.value === 'add' ? 'Added' : 'Reduced'
    notificationStore.success(
      'Stock Adjustment Complete',
      `${action} ${bulkAdjustmentAmount.value} units ${bulkAdjustmentType.value === 'add' ? 'to' : 'from'} ${selectedItems.value.length} items`
    )
    showBulkAdjustModal.value = false
    selectedItems.value = []
    bulkAdjustmentAmount.value = 0
    showBulkActions.value = false
  }
}

const confirmBulkCategorize = () => {
  if (newCategoryId.value) {
    notificationStore.success(
      'Categories Updated',
      `${selectedItems.value.length} items moved to "${newCategoryId.value}" category`
    )
    showBulkCategorizeModal.value = false
    selectedItems.value = []
    newCategoryId.value = ''
    showBulkActions.value = false
  }
}

// Missing adjustStock function
const adjustStock = (item) => {
  selectedItem.value = item
  bulkAdjustmentAmount.value = 0
  bulkAdjustmentType.value = 'add'
  showBulkAdjustModal.value = true
}

const statusText = (item) => {
  if (item.currentStock === 0) return 'Out of Stock'
  if (item.currentStock <= item.lowStockThreshold) return 'Low Stock'
  return 'In Stock'
}

const statusClass = (item) => {
  if (item.currentStock === 0)
    return 'inline-flex px-2 py-1 text-xs rounded-full bg-red-100 text-red-800'
  if (item.currentStock <= item.lowStockThreshold)
    return 'inline-flex px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800'
  return 'inline-flex px-2 py-1 text-xs rounded-full bg-green-100 text-green-800'
}
</script>
