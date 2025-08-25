/**
 * Global error handler for the POS application
 * Provides consistent error handling and user-friendly messages
 */

import { useNotificationStore } from '@/stores/notifications'

// Error code to user-friendly message mapping
const ERROR_MESSAGES = {
    // Validation errors
    'VALIDATION_ERROR': 'Please check your input and try again.',
    
    // Authentication/Authorization
    'AUTHENTICATION_REQUIRED': 'Please log in to continue.',
    'INSUFFICIENT_PERMISSIONS': 'You don\'t have permission to perform this action.',
    
    // Resource errors
    'RESOURCE_NOT_FOUND': 'The requested item was not found.',
    'DUPLICATE_ENTRY': 'This item already exists. Please use different values.',
    
    // Stock/Inventory errors
    'INSUFFICIENT_STOCK': 'Not enough items in stock.',
    
    // Sale processing errors
    'SALE_PROCESSING_ERROR': 'Unable to process the sale. Please try again.',
    
    // Database errors
    'DATABASE_ERROR': 'A database error occurred. Please try again.',
    'FOREIGN_KEY_CONSTRAINT': 'Cannot delete this item as it\'s being used elsewhere.',
    
    // HTTP errors
    'METHOD_NOT_ALLOWED': 'This action is not allowed.',
    'RATE_LIMIT_EXCEEDED': 'Too many requests. Please wait before trying again.',
    
    // Network/Server errors
    'NETWORK_ERROR': 'Network connection failed. Please check your internet connection.',
    'SERVER_ERROR': 'Server error. Please try again later.',
    'TIMEOUT_ERROR': 'Request timed out. Please try again.',
    
    // Default
    'UNKNOWN_ERROR': 'An unexpected error occurred. Please try again.'
}

// Critical error codes that require immediate attention
const CRITICAL_ERRORS = [
    'DATABASE_ERROR',
    'SERVER_ERROR',
    'INTERNAL_ERROR'
]

// Error codes that suggest user should retry
const RETRY_ERRORS = [
    'NETWORK_ERROR',
    'TIMEOUT_ERROR',
    'RATE_LIMIT_EXCEEDED',
    'SERVER_ERROR'
]

/**
 * Extract error information from various error formats
 */
function extractErrorInfo(error) {
    // Axios error with response
    if (error.response?.data) {
        const data = error.response.data
        return {
            message: data.message || 'An error occurred',
            errorCode: data.error_code || 'UNKNOWN_ERROR',
            statusCode: error.response.status,
            errors: data.errors || null,
            timestamp: data.timestamp || new Date().toISOString(),
            details: data.error_details || null
        }
    }
    
    // Axios network error
    if (error.code === 'NETWORK_ERR' || error.message === 'Network Error') {
        return {
            message: 'Network connection failed',
            errorCode: 'NETWORK_ERROR',
            statusCode: 0,
            errors: null,
            timestamp: new Date().toISOString(),
            details: null
        }
    }
    
    // Axios timeout error
    if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
        return {
            message: 'Request timed out',
            errorCode: 'TIMEOUT_ERROR',
            statusCode: 0,
            errors: null,
            timestamp: new Date().toISOString(),
            details: null
        }
    }
    
    // Generic error object
    if (error.message) {
        return {
            message: error.message,
            errorCode: 'UNKNOWN_ERROR',
            statusCode: 500,
            errors: null,
            timestamp: new Date().toISOString(),
            details: null
        }
    }
    
    // Fallback for unknown error format
    return {
        message: 'An unexpected error occurred',
        errorCode: 'UNKNOWN_ERROR',
        statusCode: 500,
        errors: null,
        timestamp: new Date().toISOString(),
        details: null
    }
}

/**
 * Get user-friendly error message
 */
function getUserFriendlyMessage(errorCode, originalMessage) {
    return ERROR_MESSAGES[errorCode] || originalMessage || ERROR_MESSAGES.UNKNOWN_ERROR
}

/**
 * Handle error and show appropriate notification
 */
export function handleError(error, options = {}) {
    const notificationStore = useNotificationStore()
    const errorInfo = extractErrorInfo(error)
    
    const {
        showNotification = true,
        customMessage = null,
        logError = true,
        throwError = false
    } = options
    
    // Log error for debugging (in development)
    if (logError && import.meta.env.DEV) {
        console.group('🚨 Error Handler')
        console.error('Original Error:', error)
        console.table(errorInfo)
        if (errorInfo.details) {
            console.log('Error Details:', errorInfo.details)
        }
        console.groupEnd()
    }
    
    // Determine notification type and message
    const userMessage = customMessage || getUserFriendlyMessage(errorInfo.errorCode, errorInfo.message)
    const isCritical = CRITICAL_ERRORS.includes(errorInfo.errorCode)
    const shouldRetry = RETRY_ERRORS.includes(errorInfo.errorCode)
    
    // Show notification if requested
    if (showNotification) {
        const notificationType = isCritical ? 'error' : 'warning'
        
        notificationStore[notificationType](
            getErrorTitle(errorInfo.errorCode, errorInfo.statusCode),
            userMessage
        )
        
        // Show retry suggestion for certain errors
        if (shouldRetry) {
            setTimeout(() => {
                notificationStore.info('Suggestion', 'You can try again in a few moments.')
            }, 1000)
        }
    }
    
    // Handle validation errors specially
    if (errorInfo.errorCode === 'VALIDATION_ERROR' && errorInfo.errors) {
        return {
            type: 'validation',
            message: userMessage,
            errors: errorInfo.errors,
            errorCode: errorInfo.errorCode
        }
    }
    
    // Throw error if requested (for component-specific handling)
    if (throwError) {
        const enhancedError = new Error(userMessage)
        enhancedError.errorCode = errorInfo.errorCode
        enhancedError.statusCode = errorInfo.statusCode
        enhancedError.originalError = error
        throw enhancedError
    }
    
    return {
        type: 'error',
        message: userMessage,
        errorCode: errorInfo.errorCode,
        statusCode: errorInfo.statusCode,
        isCritical,
        shouldRetry
    }
}

/**
 * Get appropriate error title based on error code and status
 */
function getErrorTitle(errorCode, statusCode) {
    switch (errorCode) {
        case 'VALIDATION_ERROR':
            return 'Validation Error'
        case 'AUTHENTICATION_REQUIRED':
            return 'Authentication Required'
        case 'INSUFFICIENT_PERMISSIONS':
            return 'Access Denied'
        case 'RESOURCE_NOT_FOUND':
            return 'Not Found'
        case 'INSUFFICIENT_STOCK':
            return 'Stock Error'
        case 'SALE_PROCESSING_ERROR':
            return 'Sale Error'
        case 'NETWORK_ERROR':
            return 'Connection Error'
        case 'RATE_LIMIT_EXCEEDED':
            return 'Rate Limit'
        case 'SERVER_ERROR':
        case 'DATABASE_ERROR':
            return 'Server Error'
        default:
            return statusCode >= 500 ? 'Server Error' : 'Error'
    }
}

/**
 * Async wrapper for error handling in try-catch blocks
 */
export async function withErrorHandling(asyncFn, options = {}) {
    try {
        return await asyncFn()
    } catch (error) {
        return handleError(error, options)
    }
}

/**
 * Create error handler for specific contexts
 */
export function createErrorHandler(defaultOptions = {}) {
    return (error, options = {}) => {
        return handleError(error, { ...defaultOptions, ...options })
    }
}

/**
 * Export for global use
 */
export default {
    handleError,
    withErrorHandling,
    createErrorHandler,
    ERROR_MESSAGES,
    CRITICAL_ERRORS,
    RETRY_ERRORS
}