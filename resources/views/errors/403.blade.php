<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Forbidden - POS SuperMarket</title>
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
            <div class="inline-flex items-center justify-center w-20 h-20 bg-orange-100 rounded-full mb-6">
                <svg class="w-10 h-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-6xl font-bold text-gray-900 mb-4">403</h1>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Access Forbidden</h2>
            <p class="text-gray-600 mb-8">
                You don't have permission to access this resource. Please contact your administrator if you believe this is an error.
            </p>
        </div>
        
        <div class="mb-8 p-4 bg-orange-50 rounded-lg border border-orange-200">
            <h3 class="text-sm font-medium text-orange-800 mb-2">Possible reasons:</h3>
            <ul class="text-sm text-orange-700 text-left space-y-1">
                <li>• Insufficient user permissions</li>
                <li>• Resource requires higher access level</li>
                <li>• Account access has been restricted</li>
                <li>• Session may have expired</li>
            </ul>
        </div>
        
        <div class="space-y-4">
            <a href="{{ url('/login') }}" 
               class="inline-flex items-center justify-center w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Login Again
            </a>
            
            <a href="{{ url('/') }}" 
               class="inline-flex items-center justify-center w-full bg-gray-200 text-gray-800 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Go to Dashboard
            </a>
        </div>
        
        <div class="mt-12 text-sm text-gray-500">
            <p>Need access to this resource?</p>
            <p class="mt-1">Contact your system administrator</p>
        </div>
    </div>
</body>
</html>