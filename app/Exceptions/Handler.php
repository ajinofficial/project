<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        // Unmatched URLs never reach the web group. Give HTML error pages the
        // same encrypted preferences and CSRF session without changing routing.
        if (! $request->hasSession() && ! $request->expectsJson()
            && $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
            && in_array($e->getStatusCode(), [404, 405], true)) {
            return app(\Illuminate\Pipeline\Pipeline::class)->send($request)->through([
                \App\Http\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \App\Http\Middleware\SetLocale::class,
            ])->then(fn ($request) => parent::render($request, $e));
        }

        return parent::render($request, $e);
    }
}
