<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - 403</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-red-50 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full mx-4 border-l-4 border-red-500">
        <div class="text-center">
            <div class="text-gray-600 mb-4">
                <svg class="mx-auto h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 0h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h1 class="text-xl font-semibold text-gray-800 mb-2">Error - 403</h1>
            <p class="text-gray-600 mb-2 font-medium">Access Forbidden</p>

            @if(isset($exception) && $exception->getMessage())
                <p class="text-gray-600 mb-4 text-sm">{{ $exception->getMessage() }}</p>
            @else
                <p class="text-gray-600 mb-4 text-sm">You do not have permission to access this resource.</p>
            @endif

            <div class="mt-6 space-y-2">
                @guest
                    <a href="{{ route('login') }}" class="inline-block w-full px-4 py-2 text-white bg-slate-600 rounded-sm hover:bg-slate-700 transition-colors">
                        Login
                    </a>
                    <a href="{{ route('home') }}" class="inline-block w-full px-4 py-2 text-gray-700 bg-gray-100 rounded-sm hover:bg-gray-200 transition-colors">
                        Go Home
                    </a>
                @else
                    <p class="text-sm text-gray-500 mb-4">
                        If you believe you should have access, please contact an administrator.
                    </p>
                    <a href="{{ route('home') }}" class="inline-block px-4 py-2 text-white bg-slate-600 rounded-sm hover:bg-slate-700 transition-colors">
                        Go Home
                    </a>
                @endguest
            </div>
        </div>
    </div>
</body>
</html>
