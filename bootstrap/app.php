<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render (like Heroku/Railway) terminates TLS at its own edge and
        // forwards plain HTTP to this container — without this, Laravel has
        // no way to know the original request was HTTPS, and generates
        // http:// everywhere (asset(), url(), the Vite helper), which
        // browsers then block as mixed content on an https:// page. '*' is
        // safe here because this container is never reachable except through
        // Render's own proxy.
        $middleware->trustProxies(at: '*');

        // Flask's own session cookie ("session") must pass through unencrypted —
        // Flask needs to read back exactly the value it wrote. No collision with
        // Laravel's own session cookie, which is named "laravel-session" here.
        $middleware->encryptCookies(except: ['session']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
