<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - 500</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-red-50 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full mx-4 border-l-4 border-red-500">
        <div class="text-center">
            <div class="text-gray-600 mb-4">
                <svg class="mx-auto h-12 w-12 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <h1 class="text-xl font-semibold text-gray-800 mb-2">Error - 500</h1>
            <p class="text-gray-600 mb-4">Something went wrong</p>
            <p class="text-gray-600 mb-4">Please contact the administrator</p>
            <a href="{{ route('home') }}" class="inline-block px-4 py-2 bg-slate-500 hover:bg-slate-700 text-white font-bold rounded shadow transition-colors">
                Go Home
            </a>
        </div>
    </div>
</body>
</html>
