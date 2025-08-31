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
    <div id="receipt-content" class="receipt bg-white max-w-xs mx-auto p-4 border border-gray-200 font-mono text-sm">
      <!-- Store Header -->
      <div class="text-center mb-3">
        <h1 class="text-base font-bold uppercase tracking-wide">{{ settings.store?.name || 'SuperMarket POS' }}</h1>
        <div class="text-xs text-gray-700 leading-tight">
          <div>{{ settings.store?.address || '123 Main Street\nAnytown, ST 12345' }}</div>
          <div>Tel: {{ settings.store?.phone || '+1-555-0123' }}</div>
          <div v-if="settings.store?.email">{{ settings.store.email }}</div>
        </div>
        <div class="border-b border-dashed border-gray-400 my-2"></div>
      </div>

      <!-- Transaction Info -->
      <div class="text-center mb-3 text-xs">
        <div class="font-bold text-sm mb-1">SALES RECEIPT</div>
        <div class="space-y-0.5">
          <div>Receipt #: {{ sale?.code || 'R-000000' }}</div>
          <div>{{ formatDate(sale?.created_at || new Date()) }}</div>
          <div>Cashier: {{ sale?.cashier?.name || 'Staff' }}</div>
          <div v-if="sale?.customer" class="font-medium">
            Customer: {{ sale.customer.name }}
          </div>
        </div>
        <div class="border-b border-dashed border-gray-400 my-2"></div>
      </div>

      <!-- Items -->
      <div class="mb-3">
        <!-- Items List -->
        <div v-for="item in sale?.items || []" :key="item.id" class="mb-1 text-xs">
          <!-- Item Name Row -->
          <div class="flex justify-between items-start">
            <div class="flex-1 pr-2">
              <div class="font-medium truncate">{{ item.product_name || item.name }}</div>
            </div>
            <div class="text-right font-medium">
              RM {{ Number(item.line_total).toFixed(2) }}
            </div>
          </div>
          <!-- Quantity and Price Row -->
          <div class="flex justify-between text-xs text-gray-600 ml-2">
            <div>{{ item.quantity }} x RM {{ Number(item.price).toFixed(2) }}</div>
            <div v-if="item.sku" class="text-xs">SKU: {{ item.sku }}</div>
          </div>
          <!-- Item Discount -->
          <div v-if="Number(item.discount) > 0" class="flex justify-between text-xs text-red-600 ml-2">
            <div>Item Discount</div>
            <div>-RM {{ Number(item.discount).toFixed(2) }}</div>
          </div>
        </div>
        <div class="border-b border-dashed border-gray-400 my-2"></div>
      </div>

      <!-- Totals -->
      <div class="mb-3 text-xs">
        <div class="space-y-0.5">
          <div class="flex justify-between">
            <span>Subtotal:</span>
            <span>RM {{ Number(sale?.subtotal || 0).toFixed(2) }}</span>
          </div>
          <div v-if="Number(sale?.discount || 0) > 0" class="flex justify-between text-red-600">
            <span>Discount:</span>
            <span>-RM {{ Number(sale?.discount || 0).toFixed(2) }}</span>
          </div>
          <div v-if="Number(sale?.tax || 0) > 0" class="flex justify-between">
            <span>Tax ({{ ((Number(sale?.tax || 0) / Number(sale?.subtotal || 1)) * 100).toFixed(1) }}%):</span>
            <span>RM {{ Number(sale?.tax || 0).toFixed(2) }}</span>
          </div>
        </div>
        <div class="border-b border-double border-gray-600 my-1"></div>
        <div class="flex justify-between text-sm font-bold">
          <span>TOTAL:</span>
          <span>RM {{ Number(sale?.total || 0).toFixed(2) }}</span>
        </div>
        <div class="border-b border-dashed border-gray-400 my-2"></div>
      </div>

      <!-- Payment Info -->
      <div class="mb-3 text-xs">
        <div class="flex justify-between mb-1">
          <span>Payment:</span>
          <span class="font-medium">{{ formatPaymentMethod(sale?.payment_method || 'cash').toUpperCase() }}</span>
        </div>

        <!-- Cash Payment Details -->
        <div v-if="sale?.payment_method === 'cash' && sale?.payment" class="space-y-0.5">
          <div class="flex justify-between">
            <span>Amount Tendered:</span>
            <span>RM {{ Number(sale.payment.cash_received || sale.total).toFixed(2) }}</span>
          </div>
          <div v-if="Number(sale.payment.change_amount || 0) > 0" class="flex justify-between font-medium">
            <span>Change:</span>
            <span>RM {{ Number(sale.payment.change_amount || 0).toFixed(2) }}</span>
          </div>
        </div>

        <!-- Card Payment Details -->
        <div v-if="['card', 'digital'].includes(sale?.payment_method) && sale?.payment" class="space-y-0.5">
          <div v-if="sale.payment.card_last_four" class="flex justify-between">
            <span>Card:</span>
            <span>****{{ sale.payment.card_last_four }}</span>
          </div>
          <div v-if="sale.payment.card_brand" class="flex justify-between">
            <span>Type:</span>
            <span>{{ formatCardBrand(sale.payment.card_brand) }}</span>
          </div>
          <div v-if="sale.payment.gateway_transaction_id" class="flex justify-between text-xs">
            <span>Auth:</span>
            <span class="font-mono">{{ sale.payment.gateway_transaction_id.substring(0, 8) }}</span>
          </div>
          <div v-if="Number(sale.payment.fee || 0) > 0" class="flex justify-between text-gray-500">
            <span>Fee:</span>
            <span>RM {{ Number(sale.payment.fee || 0).toFixed(2) }}</span>
          </div>
        </div>
        <div class="border-b border-dashed border-gray-400 my-2"></div>
      </div>

      <!-- Footer -->
      <div class="text-center text-xs">
        <div class="mb-2">
          <div class="font-bold">{{ settings.receipt.header.split('\n')[0] || 'THANK YOU!' }}</div>
          <div class="text-gray-600" v-if="settings.receipt.header.split('\n')[1]">{{ settings.receipt.header.split('\n')[1] }}</div>
          <div class="text-gray-600" v-else>Please come again</div>
        </div>

        <div class="text-xs text-gray-500 space-y-0.5 mb-2">
          <div v-if="settings.store.email">{{ settings.store.email }}</div>
          <div v-for="line in settings.receipt.footer.split('\n')" :key="line">{{ line }}</div>
        </div>

        <!-- Simple Barcode -->
        <div class="barcode text-center mb-2">
          <div class="font-mono text-xs mb-1">{{ sale?.code || 'R-000000' }}</div>
          <div class="flex justify-center space-x-px">
            <div v-for="i in 24" :key="i"
                 :class="['bg-black', i % 4 === 0 || i % 7 === 0 ? 'w-0.5' : 'w-px', 'h-6']">
            </div>
          </div>
        </div>

        <div class="text-xs text-gray-400">
          {{ formatShortDate(sale?.created_at || new Date()) }} | {{ sale?.cashier?.name || 'Staff' }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  sale: {
    type: Object,
    default: () => ({})
  }
})

// Settings data
const settings = ref({
  store: {
    name: 'SuperMarket POS',
    address: '123 Main Street\nAnytown, ST 12345',
    phone: '+1-555-0123',
    email: 'info@supermarketpos.com',
  },
  receipt: {
    header: 'Thank you for shopping with us!',
    footer: 'Please come again!\nReturn policy: 30 days with receipt',
    showLogo: true,
  }
})

// Fetch settings on component mount
onMounted(async () => {
  try {
    const response = await axios.get('/api/settings/receipt')
    if (response.data) {
      // Merge with defaults instead of replacing
      settings.value = {
        store: { ...settings.value.store, ...response.data.store },
        receipt: { ...settings.value.receipt, ...response.data.receipt }
      }
    }
  } catch (error) {
    console.warn('Could not load receipt settings, using defaults:', error)
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

const formatShortDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: '2-digit',
    day: '2-digit',
    year: '2-digit'
  }) + ' ' + date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
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
    `<title>Receipt - ${props.sale?.code || 'R-000000'} - ${settings.value.store.name}</title>`,
    '<style>',
    'body { font-family: "Courier New", monospace; margin: 0; padding: 20px; background: white; line-height: 1.3; }',
    '.receipt { max-width: 280px; margin: 0 auto; padding: 16px; border: 1px solid #ccc; font-size: 12px; }',
    '.text-center { text-align: center; }',
    '.text-right { text-align: right; }',
    '.text-left { text-align: left; }',
    '.text-sm { font-size: 14px; }',
    '.text-xs { font-size: 10px; }',
    '.text-base { font-size: 16px; }',
    '.font-bold { font-weight: bold; }',
    '.font-medium { font-weight: 500; }',
    '.font-mono { font-family: "Courier New", monospace; }',
    '.uppercase { text-transform: uppercase; }',
    '.tracking-wide { letter-spacing: 0.5px; }',
    '.truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }',
    '.text-gray-700 { color: #374151; }',
    '.text-gray-600 { color: #4b5563; }',
    '.text-gray-500 { color: #6b7280; }',
    '.text-gray-400 { color: #9ca3af; }',
    '.text-red-600 { color: #dc2626; }',
    '.border-b { border-bottom: 1px solid #000; }',
    '.border-dashed { border-style: dashed !important; }',
    '.border-double { border-style: double !important; border-width: 3px !important; }',
    '.border-gray-400 { border-color: #9ca3af; }',
    '.border-gray-600 { border-color: #4b5563; }',
    '.mb-1 { margin-bottom: 4px; }',
    '.mb-2 { margin-bottom: 8px; }',
    '.mb-3 { margin-bottom: 12px; }',
    '.my-1 { margin-top: 4px; margin-bottom: 4px; }',
    '.my-2 { margin-top: 8px; margin-bottom: 8px; }',
    '.ml-2 { margin-left: 8px; }',
    '.pr-2 { padding-right: 8px; }',
    '.leading-tight { line-height: 1.1; }',
    '.space-y-0\\.5 > * + * { margin-top: 2px; }',
    '.space-x-px > * + * { margin-left: 1px; }',
    '.flex { display: flex; }',
    '.justify-between { justify-content: space-between; }',
    '.justify-center { justify-content: center; }',
    '.items-start { align-items: flex-start; }',
    '.flex-1 { flex: 1; }',
    '.bg-black { background-color: black; }',
    '.w-px { width: 1px; }',
    '.w-0\\.5 { width: 2px; }',
    '.h-6 { height: 24px; }',
    '@media print {',
    '  body { padding: 0; }',
    '  .receipt { border: none; margin: 0 auto; max-width: none; width: 58mm; font-size: 10px; }',
    '  .text-sm { font-size: 11px; }',
    '  .text-xs { font-size: 9px; }',
    '  .text-base { font-size: 12px; }',
    '}',
    '</style>',
    '</head>',
    '<body>',
    '<div class="receipt">',
    receiptContent,
    '</div>',
    '<script>',
    'window.onload = function() { window.print(); window.onafterprint = function() { window.close(); } }',
    '</' + 'script>',
    '</body>',
    '</html>'
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
    margin: 0 auto !important;
    max-width: none !important;
    width: 58mm; /* Thermal printer width */
    font-size: 10px !important;
    line-height: 1.2 !important;
  }

  .text-sm {
    font-size: 11px !important;
  }

  .text-xs {
    font-size: 9px !important;
  }

  .text-base {
    font-size: 12px !important;
  }

  body {
    margin: 0 !important;
    padding: 0 !important;
  }

  @page {
    margin: 0;
    size: 58mm; /* Thermal receipt width, auto height */
  }
}

/* Screen styles */
.receipt {
  font-family: 'Courier New', monospace;
  line-height: 1.3;
}

.barcode div {
  transition: all 0.2s ease;
}

/* Responsive adjustments */
@media (max-width: 320px) {
  .receipt {
    max-width: 100%;
    margin: 0;
  }
}
</style>
