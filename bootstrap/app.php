<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Database operation failed.',
                    'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
                ], 500);
            }
        });

        $exceptions->render(function (\Exception $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'An error occurred.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Something went wrong.',
                ], 500);
            }
        });
    })->create();
