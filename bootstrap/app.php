<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, \Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $response;
            }

            $status = $response->getStatusCode();
            if ($status >= 400) {
                $view = view()->exists("errors.{$status}")
                    ? "errors.{$status}"
                    : ($status >= 500 && view()->exists('errors.500') ? 'errors.500' : 'errors.4xx');

                if (view()->exists($view)) {
                    return response()->view($view, [
                        'exception' => $e,
                    ], $status, $response->headers->all());
                }
            }

            return $response;
        });
    })->create();
