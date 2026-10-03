<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\Role::class,
            'user.guard' => \App\Http\Middleware\UserGuardMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\KepalaReadOnly::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // PHP membuang seluruh body bila melebihi post_max_size; form AJAX (crud.js)
        // butuh JSON berpesan jelas, bukan halaman error 413
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if (! $request->ajax() && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Ukuran data yang dikirim melebihi batas server ('.ini_get('post_max_size').'). Kecilkan file lalu coba lagi.',
            ], 413);
        });
    })->create();
