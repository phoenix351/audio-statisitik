<?php

use App\Http\Middleware\SetDbActor;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        // api: __DIR__.'/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'log.visitor' => \App\Http\Middleware\LogVisitorMiddleware::class,
        ]);

        // Add global middleware (only for web routes, not auth pages)
        $middleware->web(append: [
            // \App\Http\Middleware\ConditionalLogVisitorMiddleware::class,
        ]);
        $middleware->append(SetDbActor::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
