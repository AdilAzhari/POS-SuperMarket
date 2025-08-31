<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - POS SuperMarket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-auto text-center px-6">
        <div class="mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-6">
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-6xl font-bold text-gray-900 mb-4">500</h1>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Server Error</h2>
            <p class="text-gray-600 mb-8">
                We're experiencing some technical difficulties. Our team has been notified and is working to fix this issue.
            </p>
        </div>
        
        <div class="space-y-4">
            <button onclick="window.location.reload()" 
                    class="inline-flex items-center justify-center w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Try Again
            </button>
            
            <a href="{{ url('/') }}" 
               class="inline-flex items-center justify-center w-full bg-gray-200 text-gray-800 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Go to Dashboard
            </a>
        </div>
        
        <div class="mt-12 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
            <p class="text-sm text-yellow-800 font-medium">Having persistent issues?</p>
            <p class="text-sm text-yellow-700 mt-1">
                Please contact our technical support team with error code: 
                <span class="font-mono bg-yellow-200 px-2 py-1 rounded">{{ uniqid('ERR-') }}</span>
            </p>
            <p class="text-sm text-blue-600 mt-2">support@possupermarket.com</p>
        </div>
    </div>
</body>
</html>