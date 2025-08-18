export const PAYMENT_METHODS = [
  { value: 'cash', label: 'Cash' },
  { value: 'card', label: 'Card' },
  { value: 'digital', label: 'Digital Wallet' },
  { value: 'check', label: 'Check' },
]

export const SALE_STATUS = [
  { value: 'pending', label: 'Pending' },
  { value: 'completed', label: 'Completed' },
  { value: 'voided', label: 'Voided' },
  { value: 'refunded', label: 'Refunded' },
]

export const STOCK_MOVEMENT_TYPES = [
  { value: 'purchase', label: 'Purchase' },
  { value: 'sale', label: 'Sale' },
  { value: 'adjustment', label: 'Adjustment' },
  { value: 'transfer', label: 'Transfer' },
  { value: 'damage', label: 'Damage' },
  { value: 'expired', label: 'Expired' },
]

export const PRODUCT_STATUS = [
  { value: true, label: 'Active' },
  { value: false, label: 'Inactive' },
]

export const TAX_RATE = 0.08 // 8%

export const CURRENCY_SYMBOL = '$'

export const DEFAULT_PAGINATION = {
  page: 1,
  perPage: 20,
  total: 0,
  totalPages: 0,
}

export const NOTIFICATION_DURATION = {
  SUCCESS: 3000,
  ERROR: 5000,
  WARNING: 4000,
  INFO: 3000,
}

export const ROUTES = {
  DASHBOARD: '/',
  PRODUCTS: '/products',
  CATEGORIES: '/categories',
  SUPPLIERS: '/suppliers',
  CUSTOMERS: '/customers',
  SALES: '/sales',
  INVENTORY: '/inventory',
  REPORTS: '/reports',
  SETTINGS: '/settings',
}

export const API_ENDPOINTS = {
  PRODUCTS: '/api/products',
  CATEGORIES: '/api/categories',
  SUPPLIERS: '/api/suppliers',
  CUSTOMERS: '/api/customers',
  SALES: '/api/sales',
  STOCK_MOVEMENTS: '/api/stock-movements',
  STORES: '/api/stores',
  SETTINGS: '/api/settings',
}
