<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->prependToGroup('api', \App\Http\Middleware\AlwaysAcceptJson::class);
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'module.access' => \App\Http\Middleware\CheckModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Object not found'], 404);
            }
        });

        $exceptions->renderable(function (QueryException $e, Request $request) {
            // Check if it's a connection issue (connection refused, server not responding)
            $message = strtolower($e->getMessage());
            $code = $e->getCode();

            // Temporary debugging - log the actual error message and code
            Log::info('Database error detected', [
                'message' => $e->getMessage(),
                'code' => $code,
                'request_url' => $request->url(),
            ]);

            // Check for connection-related error codes and messages
            $isConnectionIssue =
                // Common database connection error codes
                in_array($code, [2002, 2003, 2006, 2013, 2019, 2026, 2054, 2055, 2056, 2057, 2058, 2059]) ||
                // PostgreSQL connection error codes
                in_array($code, ['08000', '08001', '08002', '08003', '08004', '08006', '08007', '08008', '08009']) ||
                // Connection-related error messages
                str_contains($message, 'connection refused') ||
                str_contains($message, 'server not responding') ||
                str_contains($message, 'could not connect') ||
                str_contains($message, 'connection to the server') ||
                str_contains($message, 'no connection to the server') ||
                str_contains($message, 'connection failed') ||
                str_contains($message, 'server closed the connection') ||
                str_contains($message, 'connection was forcibly closed') ||
                str_contains($message, 'connection timed out') ||
                str_contains($message, 'connection lost') ||
                str_contains($message, 'unable to connect') ||
                str_contains($message, 'failed to connect') ||
                str_contains($message, 'connection error') ||
                str_contains($message, 'server is not responding') ||
                str_contains($message, 'server is down') ||
                str_contains($message, 'database server is not available') ||
                str_contains($message, 'host unreachable') ||
                str_contains($message, 'network is unreachable') ||
                str_contains($message, 'no route to host') ||
                str_contains($message, 'connection reset') ||
                str_contains($message, 'broken pipe') ||
                str_contains($message, 'server shutdown') ||
                str_contains($message, 'database is not available') ||
                str_contains($message, 'service unavailable');

            if ($isConnectionIssue) {
                return response()->view('errors.database-offline', [], 503);
            }

            // For other query errors, show 500 error page
            return response()->view('errors.500', [], 500);
        });
    })->create();
