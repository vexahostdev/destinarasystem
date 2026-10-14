<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        // Force HTTPS in production / staging
        if (app()->environment(['production', 'staging']) || str_starts_with((string) config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        // Super Admin bypasses all permissions via Gate::before (SPEC §4.2)
        Gate::before(function (User $user, string $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        // Register Domain Event Listeners (SPEC §10.2 & §11)
        \Illuminate\Support\Facades\Event::listen([
            \App\Events\BookingCreated::class,
            \App\Events\BookingPaid::class,
            \App\Events\BookingConfirmed::class,
            \App\Events\BookingRescheduled::class,
            \App\Events\PayoutTransferred::class,
        ], \App\Listeners\GenerateBookingDocumentsListener::class);

        \Illuminate\Support\Facades\Event::listen([
            \App\Events\BookingCreated::class,
            \App\Events\BookingPaid::class,
            \App\Events\BookingConfirmed::class,
            \App\Events\BookingRejected::class,
            \App\Events\BookingCancelled::class,
            \App\Events\BookingRescheduled::class,
            \App\Events\BookingExpired::class,
            \App\Events\BookingCheckedIn::class,
            \App\Events\BookingCompleted::class,
            \App\Events\RefundCreated::class,
            \App\Events\RefundCompleted::class,
            \App\Events\PayoutCreated::class,
            \App\Events\PayoutTransferred::class,
            \App\Events\DestinationSubmitted::class,
            \App\Events\DestinationModerated::class,
            \App\Events\PartnerVerified::class,
            \App\Events\PartnerRejected::class,
            \App\Events\InstitutionVerified::class,
            \App\Events\InstitutionRejected::class,
            \App\Events\LatePaymentReceived::class,
            \App\Events\PaymentAmountMismatch::class,
            \App\Events\UserRegistered::class,
        ], \App\Listeners\SendDomainNotificationListener::class);
    }
}
