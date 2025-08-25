<template>
  <div class="receipt-container">
    <!-- Print Button (hidden when printing) -->
    <div class="print-controls no-print mb-4 text-center">
      <button
        @click="printReceipt"
        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 mr-2"
      >
        🖨️ Print Receipt
      </button>
      <button
        @click="downloadReceipt"
        class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700"
      >
        📄 Download PDF
      </button>
    </div>

    <!-- Receipt Content -->
    <div id="receipt-content" class="receipt bg-white max-w-md mx-auto p-6 border border-gray-200">
      <!-- Store Header -->
      <div class="text-center mb-4">
        <h1 class="text-xl font-bold">{{ sale?.store?.name || 'SuperMarket' }}</h1>
        <p class="text-sm text-gray-600">{{ sale?.store?.address || 'Store Address' }}</p>
        <p class="text-sm text-gray-600">{{ sale?.store?.phone || 'Phone: (555) 123-4567' }}</p>
        <div class="border-b border-gray-300 my-3"></div>
      </div>

      <!-- Transaction Info -->
      <div class="text-center mb-4">
        <h2 class="font-semibold text-lg">RECEIPT</h2>
        <p class="text-sm">Transaction: {{ sale?.code || 'TXN-000000' }}</p>
        <p class="text-sm">Date: {{ formatDate(sale?.created_at || new Date()) }}</p>
        <p class="text-sm">Cashier: {{ sale?.cashier?.name || 'Cashier' }}</p>
        <div v-if="sale?.customer" class="text-sm">
          Customer: {{ sale.customer.name }}
        </div>
      </div>

      <!-- Items -->
      <div class="mb-4">
        <div class="border-b border-gray-300 mb-2 pb-1">
          <div class="flex justify-between text-sm font-semibold">
            <span>Item</span>
            <span>Amount</span>
          </div>
        </div>

        <div v-for="item in sale?.items || []" :key="item.id" class="mb-2">
          <div class="flex justify-between text-sm">
            <div class="flex-1">
              <div class="font-medium">{{ item.product_name || item.name }}</div>
              <div class="text-xs text-gray-600">
                SKU: {{ item.sku }}
              </div>
              <div class="text-xs text-gray-600">
                {{ item.quantity }}x @ ${{ Number(item.price).toFixed(2) }}
                <span v-if="Number(item.discount) > 0"> - ${{ Number(item.discount).toFixed(2) }}</span>
              </div>
            </div>
            <div class="text-right">
              ${{ Number(item.line_total).toFixed(2) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Totals -->
      <div class="border-t border-gray-300 pt-2 mb-4">
        <div class="flex justify-between text-sm mb-1">
          <span>Subtotal:</span>
          <span>${{ Number(sale?.subtotal || 0).toFixed(2) }}</span>
        </div>
        <div v-if="Number(sale?.discount || 0) > 0" class="flex justify-between text-sm mb-1">
          <span>Discount:</span>
          <span>-${{ Number(sale?.discount || 0).toFixed(2) }}</span>
        </div>
        <div v-if="Number(sale?.tax || 0) > 0" class="flex justify-between text-sm mb-1">
          <span>Tax:</span>
          <span>${{ Number(sale?.tax || 0).toFixed(2) }}</span>
        </div>
        <div class="flex justify-between text-lg font-bold border-t border-gray-300 pt-2">
          <span>TOTAL:</span>
          <span>${{ Number(sale?.total || 0).toFixed(2) }}</span>
        </div>
      </div>

      <!-- Payment Info -->
      <div class="text-center mb-4 text-sm">
        <p>Payment Method: {{ formatPaymentMethod(sale?.payment_method || 'cash') }}</p>
        <p>Status: {{ sale?.status?.toUpperCase() || 'COMPLETED' }}</p>
        
        <!-- Cash Payment Details -->
        <div v-if="sale?.payment_method === 'cash' && sale?.payment" class="mt-2 border-t pt-2">
          <div class="flex justify-between text-sm">
            <span>Amount Received:</span>
            <span>${{ Number(sale.payment.cash_received || sale.total).toFixed(2) }}</span>
          </div>
          <div v-if="Number(sale.payment.change_amount || 0) > 0" class="flex justify-between text-sm">
            <span>Change Given:</span>
            <span>${{ Number(sale.payment.change_amount || 0).toFixed(2) }}</span>
          </div>
        </div>

        <!-- Card Payment Details -->
        <div v-if="['card', 'digital'].includes(sale?.payment_method) && sale?.payment" class="mt-2 border-t pt-2">
          <div v-if="sale.payment.card_last_four" class="flex justify-between text-sm">
            <span>Card Number:</span>
            <span>**** **** **** {{ sale.payment.card_last_four }}</span>
          </div>
          <div v-if="sale.payment.card_brand" class="flex justify-between text-sm">
            <span>Card Type:</span>
            <span>{{ formatCardBrand(sale.payment.card_brand) }}</span>
          </div>
          <div v-if="sale.payment.gateway_transaction_id" class="flex justify-between text-sm">
            <span>Transaction ID:</span>
            <span class="font-mono text-xs">{{ sale.payment.gateway_transaction_id.substring(0, 16) }}...</span>
          </div>
          <div v-if="Number(sale.payment.fee || 0) > 0" class="flex justify-between text-sm text-gray-500">
            <span>Processing Fee:</span>
            <span>${{ Number(sale.payment.fee || 0).toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center text-xs text-gray-500 border-t border-gray-300 pt-3">
        <p>Thank you for shopping with us!</p>
        <p>{{ sale?.store?.email || 'info@supermarket.com' }}</p>
        <p class="mt-2">Return Policy: Items must be returned within 30 days with receipt</p>
        <div class="mt-3">
          <div class="barcode text-center">
            <!-- Simple barcode representation -->
            <div class="font-mono text-xs">{{ sale?.code || 'TXN-000000' }}</div>
            <div class="flex justify-center mt-1">
              <div v-for="i in 20" :key="i"
                   :class="['inline-block', 'bg-black', i % 3 === 0 ? 'w-px' : 'w-0.5', 'h-8', 'mr-px']">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  sale: {
    type: Object,
    default: () => ({})
  }
})

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

const formatPaymentMethod = (method) => {
  const methods = {
    'cash': 'Cash',
    'card': 'Credit/Debit Card', 
    'digital': 'Digital Payment',
    'credit_card': 'Credit Card',
    'debit_card': 'Debit Card',
    'check': 'Check',
    'mobile_payment': 'Mobile Payment',
    'tng': 'Touch \'n Go'
  }
  return methods[method] || method.charAt(0).toUpperCase() + method.slice(1)
}

const formatCardBrand = (brand) => {
  const brands = {
    'visa': 'Visa',
    'mastercard': 'Mastercard',
    'amex': 'American Express',
    'discover': 'Discover',
    'diners': 'Diners Club',
    'jcb': 'JCB',
    'card': 'Credit/Debit Card',
    'digital': 'Digital Payment'
  }
  return brands[brand] || brand.charAt(0).toUpperCase() + brand.slice(1)
}

const printReceipt = () => {
  window.print()
}

const downloadReceipt = () => {
  // Create a new window for the receipt
  const printWindow = window.open('', '_blank')
  const receiptContent = document.getElementById('receipt-content').innerHTML

  const htmlTemplate = [
    '<!DOCTYPE html>',
    '<html>',
    '<head>',
    `<title>Receipt - ${props.sale?.code || 'TXN-000000'}</title>`,
    '<style>',
    'body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: white; }',
    '.receipt { max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; }',
    '.text-center { text-align: center; }',
    '.text-right { text-align: right; }',
    '.text-sm { font-size: 14px; }',
    '.text-xs { font-size: 12px; }',
    '.text-lg { font-size: 18px; }',
    '.font-bold { font-weight: bold; }',
    '.font-semibold { font-weight: 600; }',
    '.font-medium { font-weight: 500; }',
    '.font-mono { font-family: monospace; }',
    '.text-gray-600 { color: #666; }',
    '.text-gray-500 { color: #999; }',
    '.border-b { border-bottom: 1px solid #ccc; }',
    '.border-t { border-top: 1px solid #ccc; }',
    '.mb-1 { margin-bottom: 4px; }',
    '.mb-2 { margin-bottom: 8px; }',
    '.mb-3 { margin-bottom: 12px; }',
    '.mb-4 { margin-bottom: 16px; }',
    '.mt-1 { margin-top: 4px; }',
    '.mt-2 { margin-top: 8px; }',
    '.mt-3 { margin-top: 12px; }',
    '.pb-1 { padding-bottom: 4px; }',
    '.pt-2 { padding-top: 8px; }',
    '.pt-3 { padding-top: 12px; }',
    '.flex { display: flex; }',
    '.justify-between { justify-content: space-between; }',
    '.justify-center { justify-content: center; }',
    '.flex-1 { flex: 1; }',
    '.inline-block { display: inline-block; }',
    '.bg-black { background-color: black; }',
    '.w-px { width: 1px; }',
    '.w-0\\.5 { width: 2px; }',
    '.h-8 { height: 32px; }',
    '.mr-px { margin-right: 1px; }',
    '</' + 'style>',
    '</' + 'head>',
    '<body>',
    '<div class="receipt">',
    receiptContent,
    '</' + 'div>',
    '<script>',
    'window.onload = function() { window.print(); window.onafterprint = function() { window.close(); } }',
    '</' + 'script>',
    '</' + 'body>',
    '</' + 'html>'
  ].join('\n')

  printWindow.document.write(htmlTemplate)
  printWindow.document.close()
}
</script>

<style scoped>
/* Print-specific styles */
@media print {
  .no-print {
    display: none !important;
  }

  .receipt-container {
    margin: 0;
    padding: 0;
  }

  .receipt {
    box-shadow: none !important;
    border: none !important;
    margin: 0 !important;
    max-width: none !important;
    width: 58mm; /* Thermal printer width */
    font-size: 12px !important;
  }

  body {
    margin: 0 !important;
    padding: 0 !important;
  }

  @page {
    margin: 0;
    size: 58mm auto; /* Thermal receipt size */
  }
}

/* Screen styles */
.receipt {
  font-family: 'Courier New', monospace;
  line-height: 1.4;
}

.barcode div {
  transition: all 0.2s ease;
}
</style>
