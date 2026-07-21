<?php

namespace App\Providers;

use App\Mail\Transport\GmailApiTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);

        Mail::extend('gmail', function (): GmailApiTransport {
            return new GmailApiTransport(
                config('services.gmail.client_id'),
                config('services.gmail.client_secret'),
                config('services.gmail.refresh_token'),
            );
        });
    }
}
