<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - 419</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-red-50 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full mx-4 border-l-4 border-red-500">
        <div class="text-center">
            <div class="text-gray-600 mb-4">
                <svg class="mx-auto h-12 w-12 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h1 class="text-xl font-semibold text-gray-800 mb-2">Error - 419</h1>
            <p class="text-gray-600 mb-4">Your session has expired</p>
            <p class="text-gray-600 mb-4">Please log in again to continue</p>
            <a href="{{ route('login') }}" class="inline-block px-4 py-2 bg-slate-500 hover:bg-slate-700 text-white font-bold rounded shadow transition-colors">
                Go to Login
            </a>
        </div>
    </div>
</body>
</html>
