<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - 404</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-red-50 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full mx-4 border-l-4 border-red-500">
        <div class="text-center">
            <div class="text-gray-600 mb-4">
                <svg class="mx-auto h-12 w-12 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.47-.881-6.08-2.33" />
                </svg>
            </div>
            <h1 class="text-xl font-semibold text-gray-800 mb-2">Error - 404</h1>
            <p class="text-gray-600 mb-4">Page not found</p>
            <p class="text-gray-600 mb-4">Please contact the administrator</p>
            <a href="{{ route('home') }}" class="inline-block px-4 py-2 bg-slate-500 hover:bg-slate-700 text-white font-bold rounded shadow transition-colors">
                Go Home
            </a>
        </div>
    </div>
</body>
</html>
