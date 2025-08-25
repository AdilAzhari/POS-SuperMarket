<template>
  <div class="barcode-scanner">
    <!-- Scanner Status -->
    <div class="mb-4 p-3 rounded-lg border" :class="statusClasses">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <div class="flex items-center">
            <div :class="indicatorClasses" class="w-3 h-3 rounded-full mr-2"></div>
            <span class="text-sm font-medium">{{ statusText }}</span>
          </div>
          <span v-if="scannerType" class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
            {{ scannerType.toUpperCase() }}
          </span>
        </div>
        <div class="flex items-center space-x-2">
          <button
            v-if="!isScanning && isSupported"
            @click="startCamera"
            class="text-blue-600 hover:text-blue-800 text-sm"
            title="Start Camera Scanner"
          >
            <Camera class="h-4 w-4" />
          </button>
          <button
            v-if="isScanning"
            @click="stopCamera"
            class="text-red-600 hover:text-red-800 text-sm"
            title="Stop Camera Scanner"
          >
            <X class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Manual Input -->
    <div class="mb-4">
      <label for="manual-barcode" class="block text-sm font-medium text-gray-700 mb-2">
        Manual Barcode Entry
      </label>
      <div class="flex space-x-2">
        <input
          id="manual-barcode"
          v-model="manualInput"
          type="text"
          class="barcode-input flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
          placeholder="Enter barcode manually or use scanner"
          @keyup.enter="handleManualScan"
          @paste="handlePaste"
        />
        <button
          @click="handleManualScan"
          class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500"
          :disabled="!manualInput.trim()"
        >
          <Search class="h-4 w-4" />
        </button>
      </div>
      <p class="mt-1 text-xs text-gray-500">
        USB scanners work automatically. Camera scanning available in supported browsers.
      </p>
    </div>

    <!-- Camera View -->
    <div v-if="showCamera" class="mb-4">
      <div class="relative bg-black rounded-lg overflow-hidden">
        <video
          ref="videoElement"
          class="w-full h-64 object-cover"
          autoplay
          muted
          playsinline
        ></video>
        
        <!-- Scanning Overlay -->
        <div class="absolute inset-0 pointer-events-none">
          <!-- Scanning Line Animation -->
          <div v-if="isScanning" class="scanning-line"></div>
          
          <!-- Corner Brackets -->
          <div class="absolute top-8 left-8 w-8 h-8 border-l-2 border-t-2 border-green-400"></div>
          <div class="absolute top-8 right-8 w-8 h-8 border-r-2 border-t-2 border-green-400"></div>
          <div class="absolute bottom-8 left-8 w-8 h-8 border-l-2 border-b-2 border-green-400"></div>
          <div class="absolute bottom-8 right-8 w-8 h-8 border-r-2 border-b-2 border-green-400"></div>
          
          <!-- Instructions -->
          <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm">
            Position barcode in the center
          </div>
        </div>
      </div>
    </div>

    <!-- Last Scanned -->
    <div v-if="lastScannedCode" class="p-3 bg-green-50 border border-green-200 rounded-lg">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-green-800">Last Scanned:</p>
          <p class="text-lg font-mono text-green-900">{{ lastScannedCode }}</p>
        </div>
        <button
          @click="clearLastScanned"
          class="text-green-600 hover:text-green-800"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Recent Scans -->
    <div v-if="recentScans.length > 0" class="mt-4">
      <h4 class="text-sm font-medium text-gray-700 mb-2">Recent Scans</h4>
      <div class="space-y-1">
        <div
          v-for="scan in recentScans"
          :key="scan.id"
          class="flex items-center justify-between p-2 bg-gray-50 rounded text-sm"
        >
          <span class="font-mono">{{ scan.code }}</span>
          <div class="flex items-center space-x-2 text-xs text-gray-500">
            <span>{{ formatTime(scan.timestamp) }}</span>
            <button
              @click="rescanCode(scan.code)"
              class="text-blue-600 hover:text-blue-800"
              title="Scan again"
            >
              <RotateCcw class="h-3 w-3" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Debug Info (development only) -->
    <div v-if="showDebug" class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-600">
      <h5 class="font-medium mb-2">Debug Info:</h5>
      <ul class="space-y-1">
        <li>Scanner Type: {{ scannerType }}</li>
        <li>Camera Supported: {{ isSupported ? 'Yes' : 'No' }}</li>
        <li>Currently Scanning: {{ isScanning ? 'Yes' : 'No' }}</li>
        <li>Buffer Length: {{ keyboardBuffer?.length || 0 }}</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Camera, X, Search, RotateCcw } from 'lucide-vue-next'
import { useBarcodeScanner } from '@/composables/useBarcodeScanner'
import { useNotificationStore } from '@/stores/notifications'

const props = defineProps({
  showDebug: {
    type: Boolean,
    default: false
  },
  enableCamera: {
    type: Boolean,
    default: true
  },
  autoFocus: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['barcode-scanned', 'scan-error'])

const notificationStore = useNotificationStore()

// Scanner composable
const scanner = useBarcodeScanner()
const {
  isScanning,
  isSupported,
  lastScannedCode,
  scannerType,
  initScanner,
  startCameraScanning,
  stopCameraScanning,
  scanManually,
  searchProductByBarcode,
  setEventHandlers
} = scanner

// Component state
const videoElement = ref(null)
const manualInput = ref('')
const showCamera = ref(false)
const recentScans = ref([])
const keyboardBuffer = ref('')

// Computed
const statusClasses = computed(() => {
  if (isScanning.value) {
    return 'bg-green-50 border-green-200'
  } else if (isSupported.value) {
    return 'bg-blue-50 border-blue-200'
  } else {
    return 'bg-gray-50 border-gray-200'
  }
})

const indicatorClasses = computed(() => {
  if (isScanning.value) {
    return 'bg-green-500 animate-pulse'
  } else if (isSupported.value) {
    return 'bg-blue-500'
  } else {
    return 'bg-gray-400'
  }
})

const statusText = computed(() => {
  if (isScanning.value) {
    return 'Scanning Active'
  } else if (isSupported.value) {
    return 'Scanner Ready'
  } else {
    return 'USB Scanner Only'
  }
})

// Methods
const startCamera = async () => {
  try {
    showCamera.value = true
    await nextTick()
    await startCameraScanning(videoElement.value)
  } catch (error) {
    showCamera.value = false
    console.error('Failed to start camera:', error)
  }
}

const stopCamera = () => {
  stopCameraScanning()
  showCamera.value = false
}

const handleManualScan = () => {
  if (manualInput.value.trim()) {
    scanManually(manualInput.value.trim())
    manualInput.value = ''
  }
}

const handlePaste = (event) => {
  // Handle pasted barcodes
  setTimeout(() => {
    if (manualInput.value.trim()) {
      handleManualScan()
    }
  }, 10)
}

const clearLastScanned = () => {
  lastScannedCode.value = ''
}

const addToRecentScans = (code) => {
  const scan = {
    id: Date.now(),
    code,
    timestamp: new Date()
  }
  
  // Add to beginning and keep only last 5
  recentScans.value.unshift(scan)
  if (recentScans.value.length > 5) {
    recentScans.value = recentScans.value.slice(0, 5)
  }
}

const rescanCode = (code) => {
  scanManually(code)
}

const formatTime = (timestamp) => {
  return timestamp.toLocaleTimeString('en-US', {
    hour12: false,
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const handleBarcodeScanned = async (code) => {
  try {
    addToRecentScans(code)
    
    // Try to find product by barcode
    const productData = await searchProductByBarcode(code)
    
    emit('barcode-scanned', {
      code,
      product: productData?.data || null,
      timestamp: new Date()
    })
    
  } catch (error) {
    emit('scan-error', {
      code,
      error: error.message,
      timestamp: new Date()
    })
  }
}

const handleScanError = (error) => {
  emit('scan-error', {
    error: error.message,
    timestamp: new Date()
  })
}

// Initialize scanner
onMounted(async () => {
  // Setup event handlers
  setEventHandlers({
    onBarcodeScanned: handleBarcodeScanned,
    onScanError: handleScanError
  })
  
  // Initialize scanner
  initScanner({
    enableKeyboardInput: true,
    enableCameraScanning: props.enableCamera,
    keyboardTimeout: 100,
    minBarcodeLength: 4,
    maxBarcodeLength: 50
  })
  
  // Auto-focus manual input if requested
  if (props.autoFocus) {
    await nextTick()
    const input = document.getElementById('manual-barcode')
    if (input) {
      input.focus()
    }
  }
})

// Cleanup
onUnmounted(() => {
  stopCamera()
})
</script>

<style scoped>
.barcode-input {
  font-family: 'Courier New', monospace;
  letter-spacing: 0.1em;
}

.scanning-line {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, #10b981, transparent);
  animation: scanning 2s linear infinite;
}

@keyframes scanning {
  0% {
    transform: translateY(0);
  }
  100% {
    transform: translateY(250px);
  }
}

/* Hide video controls */
video::-webkit-media-controls {
  display: none !important;
}

video::-moz-media-controls {
  display: none !important;
}

video::-ms-media-controls {
  display: none !important;
}
</style>