import { ref, onMounted, onUnmounted } from 'vue'
import { useNotificationStore } from '@/stores/notifications'

export function useBarcodeScanner() {
  const notificationStore = useNotificationStore()
  
  const isScanning = ref(false)
  const isSupported = ref(false)
  const lastScannedCode = ref('')
  const scannerType = ref('') // 'usb', 'camera', 'manual'
  
  // Scanner configuration
  const config = ref({
    enableKeyboardInput: true,
    enableCameraScanning: true,
    keyboardTimeout: 100, // ms between keystrokes to detect scanner input
    minBarcodeLength: 4,
    maxBarcodeLength: 50,
    allowedCharacters: /^[A-Za-z0-9\-_\s]*$/
  })
  
  // Event handlers
  const onBarcodeScanned = ref(() => {})
  const onScanError = ref(() => {})
  
  // USB/Keyboard scanner variables
  let keyboardBuffer = ''
  let keyboardTimer = null
  
  // Camera scanner variables
  let videoElement = null
  let canvasElement = null
  let stream = null
  
  /**
   * Initialize barcode scanner
   */
  const initScanner = (options = {}) => {
    config.value = { ...config.value, ...options }
    
    // Check for camera support
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
      isSupported.value = true
    }
    
    // Setup keyboard listener for USB scanners
    if (config.value.enableKeyboardInput) {
      setupKeyboardScanner()
    }
    
    notificationStore.info('Scanner Ready', 'Barcode scanner initialized successfully')
  }
  
  /**
   * Setup USB/Keyboard barcode scanner
   * Most USB barcode scanners act as keyboards
   */
  const setupKeyboardScanner = () => {
    document.addEventListener('keydown', handleKeyboardInput)
    scannerType.value = 'usb'
  }
  
  /**
   * Handle keyboard input from USB scanner
   */
  const handleKeyboardInput = (event) => {
    // Check if we're in an input field (to avoid interfering with normal typing)
    const activeElement = document.activeElement
    const isInInput = activeElement && (
      activeElement.tagName === 'INPUT' || 
      activeElement.tagName === 'TEXTAREA' ||
      activeElement.contentEditable === 'true'
    )
    
    // If we're in a barcode input field specifically, handle it differently
    const isBarcodeInput = activeElement && activeElement.classList.contains('barcode-input')
    
    if (!isBarcodeInput && isInInput) {
      return // Don't interfere with normal input
    }
    
    // Prevent default if this looks like scanner input
    if (event.key === 'Enter' && keyboardBuffer.length > 0) {
      event.preventDefault()
      processBarcodeInput(keyboardBuffer)
      return
    }
    
    // Add character to buffer
    if (event.key.length === 1) {
      keyboardBuffer += event.key
      
      // Clear previous timer
      if (keyboardTimer) {
        clearTimeout(keyboardTimer)
      }
      
      // Set timer to process buffer if no more input comes
      keyboardTimer = setTimeout(() => {
        if (keyboardBuffer.length >= config.value.minBarcodeLength) {
          processBarcodeInput(keyboardBuffer)
        }
        keyboardBuffer = ''
      }, config.value.keyboardTimeout)
    }
  }
  
  /**
   * Start camera-based barcode scanning
   */
  const startCameraScanning = async (videoElementRef) => {
    if (!config.value.enableCameraScanning) {
      throw new Error('Camera scanning is disabled')
    }
    
    if (!isSupported.value) {
      throw new Error('Camera scanning not supported in this browser')
    }
    
    try {
      isScanning.value = true
      scannerType.value = 'camera'
      
      videoElement = videoElementRef
      
      // Request camera access
      stream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: 'environment', // Use back camera if available
          width: { ideal: 1280 },
          height: { ideal: 720 }
        }
      })
      
      videoElement.srcObject = stream
      await videoElement.play()
      
      // Start scanning loop
      startScanningLoop()
      
      notificationStore.success('Camera Active', 'Point camera at barcode to scan')
      
    } catch (error) {
      isScanning.value = false
      const message = error.name === 'NotAllowedError' 
        ? 'Camera access denied. Please allow camera access to scan barcodes.'
        : `Camera error: ${error.message}`
      
      notificationStore.error('Camera Error', message)
      onScanError.value(error)
      throw error
    }
  }
  
  /**
   * Stop camera scanning
   */
  const stopCameraScanning = () => {
    isScanning.value = false
    
    if (stream) {
      stream.getTracks().forEach(track => track.stop())
      stream = null
    }
    
    if (videoElement) {
      videoElement.srcObject = null
    }
    
    scannerType.value = 'usb'
  }
  
  /**
   * Scanning loop for camera-based scanning
   * Note: This is a simplified version. For production, use a library like QuaggaJS or ZXing
   */
  const startScanningLoop = () => {
    if (!videoElement || !isScanning.value) return
    
    // Create canvas for image processing
    if (!canvasElement) {
      canvasElement = document.createElement('canvas')
    }
    
    const canvas = canvasElement
    const context = canvas.getContext('2d')
    
    const scanFrame = () => {
      if (!isScanning.value) return
      
      canvas.width = videoElement.videoWidth
      canvas.height = videoElement.videoHeight
      
      if (canvas.width > 0 && canvas.height > 0) {
        context.drawImage(videoElement, 0, 0)
        
        // Here you would integrate with a barcode detection library
        // For now, we'll simulate detection
        checkForBarcode(canvas, context)
      }
      
      // Continue scanning
      requestAnimationFrame(scanFrame)
    }
    
    scanFrame()
  }
  
  /**
   * Check for barcode in canvas (placeholder for actual barcode detection)
   */
  const checkForBarcode = (canvas, context) => {
    // This is where you'd integrate with a barcode detection library
    // like QuaggaJS, ZXing-js, or @zxing/library
    
    // For demonstration, we'll detect if user presses 'Enter' while camera is active
    // In real implementation, this would be automatic barcode detection
  }
  
  /**
   * Process barcode input from any source
   */
  const processBarcodeInput = (code) => {
    // Validate barcode
    if (!isValidBarcode(code)) {
      notificationStore.warning('Invalid Barcode', `"${code}" is not a valid barcode format`)
      onScanError.value(new Error('Invalid barcode format'))
      return
    }
    
    // Clean and format barcode
    const cleanCode = code.trim().toUpperCase()
    lastScannedCode.value = cleanCode
    
    // Reset buffer
    keyboardBuffer = ''
    if (keyboardTimer) {
      clearTimeout(keyboardTimer)
      keyboardTimer = null
    }
    
    // Notify success
    notificationStore.success('Barcode Scanned', `Code: ${cleanCode}`)
    
    // Trigger callback
    onBarcodeScanned.value(cleanCode)
  }
  
  /**
   * Validate barcode format
   */
  const isValidBarcode = (code) => {
    if (!code || code.length < config.value.minBarcodeLength || code.length > config.value.maxBarcodeLength) {
      return false
    }
    
    return config.value.allowedCharacters.test(code)
  }
  
  /**
   * Manual barcode entry
   */
  const scanManually = (code) => {
    scannerType.value = 'manual'
    processBarcodeInput(code)
  }
  
  /**
   * Search product by barcode
   */
  const searchProductByBarcode = async (barcode) => {
    try {
      const response = await fetch(`/api/products/search?barcode=${encodeURIComponent(barcode)}`)
      
      if (!response.ok) {
        throw new Error('Product not found')
      }
      
      const data = await response.json()
      return data
    } catch (error) {
      notificationStore.error('Product Not Found', `No product found with barcode: ${barcode}`)
      throw error
    }
  }
  
  /**
   * Get scanner status
   */
  const getScannerStatus = () => {
    return {
      isScanning: isScanning.value,
      isSupported: isSupported.value,
      scannerType: scannerType.value,
      lastScannedCode: lastScannedCode.value
    }
  }
  
  /**
   * Set event handlers
   */
  const setEventHandlers = (handlers) => {
    if (handlers.onBarcodeScanned) {
      onBarcodeScanned.value = handlers.onBarcodeScanned
    }
    if (handlers.onScanError) {
      onScanError.value = handlers.onScanError
    }
  }
  
  // Cleanup on unmount
  onUnmounted(() => {
    // Remove keyboard listener
    document.removeEventListener('keydown', handleKeyboardInput)
    
    // Stop camera
    stopCameraScanning()
    
    // Clear timers
    if (keyboardTimer) {
      clearTimeout(keyboardTimer)
    }
  })
  
  return {
    // State
    isScanning,
    isSupported,
    lastScannedCode,
    scannerType,
    config,
    
    // Methods
    initScanner,
    startCameraScanning,
    stopCameraScanning,
    scanManually,
    searchProductByBarcode,
    getScannerStatus,
    setEventHandlers,
    
    // Utils
    isValidBarcode,
    processBarcodeInput
  }
}