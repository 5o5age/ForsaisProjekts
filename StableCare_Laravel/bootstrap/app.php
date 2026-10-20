<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureActiveAccount;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => EnsureRole::class, 'active' => EnsureActiveAccount::class]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage() ?: 'Nav piekļuves.'], $exception->getStatusCode());
            }
            if ($exception->getStatusCode() === 403) {
                return response()->view('errors.403', [], 403);
            }
            if ($exception->getStatusCode() === 404) {
                return response()->view('errors.404', [], 404);
            }
            return null;
        });
    })->create();
