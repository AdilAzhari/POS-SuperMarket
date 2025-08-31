<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation Error - POS SuperMarket</title>
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
            <div class="inline-flex items-center justify-center w-20 h-20 bg-yellow-100 rounded-full mb-6">
                <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h1 class="text-6xl font-bold text-gray-900 mb-4">422</h1>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Validation Error</h2>
            <p class="text-gray-600 mb-8">
                The submitted data failed validation. Please check your input and try again.
            </p>
        </div>
        
        @if (isset($errors) && $errors->any())
            <div class="mb-8 p-4 bg-red-50 rounded-lg border border-red-200 text-left">
                <h3 class="text-sm font-medium text-red-800 mb-2">Validation Errors:</h3>
                <ul class="text-sm text-red-700 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <div class="space-y-4">
            <button onclick="history.back()" 
                    class="inline-flex items-center justify-center w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"/>
                </svg>
                Go Back & Fix
            </button>
            
            <a href="{{ url('/') }}" 
               class="inline-flex items-center justify-center w-full bg-gray-200 text-gray-800 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Go to Dashboard
            </a>
        </div>
        
        <div class="mt-12 text-sm text-gray-500">
            <p>Having trouble with form validation?</p>
            <p class="mt-1">Check the help documentation or contact support</p>
        </div>
    </div>
</body>
</html>