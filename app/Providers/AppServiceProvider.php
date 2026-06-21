<?php

namespace App\Providers;

use App\Models\Notification;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.admin', function ($view) {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $baseQuery = Notification::where('tenant_id', $user->tenant_id);

            $view->with([
                'navNotifications' => (clone $baseQuery)->latest()->take(5)->get(),
                'navUnreadNotificationCount' => (clone $baseQuery)->unread()->count(),
            ]);
        });
    }
}
