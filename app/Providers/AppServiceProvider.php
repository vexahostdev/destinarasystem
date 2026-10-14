<?php

namespace App\Providers;

use App\Events\BookingCancelled;
use App\Events\BookingCheckedIn;
use App\Events\BookingCompleted;
use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Events\BookingExpired;
use App\Events\BookingPaid;
use App\Events\BookingRejected;
use App\Events\BookingRescheduled;
use App\Events\DestinationModerated;
use App\Events\DestinationSubmitted;
use App\Events\InstitutionRejected;
use App\Events\InstitutionVerified;
use App\Events\LatePaymentReceived;
use App\Events\PartnerRejected;
use App\Events\PartnerVerified;
use App\Events\PaymentAmountMismatch;
use App\Events\PayoutCreated;
use App\Events\PayoutTransferred;
use App\Events\RefundCompleted;
use App\Events\RefundCreated;
use App\Events\UserRegistered;
use App\Listeners\GenerateBookingDocumentsListener;
use App\Listeners\SendDomainNotificationListener;
use App\Models\User;
use Illuminate\Support\Facades\Event;
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
        Event::listen([
            BookingCreated::class,
            BookingPaid::class,
            BookingConfirmed::class,
            BookingRescheduled::class,
            PayoutTransferred::class,
        ], GenerateBookingDocumentsListener::class);

        Event::listen([
            BookingCreated::class,
            BookingPaid::class,
            BookingConfirmed::class,
            BookingRejected::class,
            BookingCancelled::class,
            BookingRescheduled::class,
            BookingExpired::class,
            BookingCheckedIn::class,
            BookingCompleted::class,
            RefundCreated::class,
            RefundCompleted::class,
            PayoutCreated::class,
            PayoutTransferred::class,
            DestinationSubmitted::class,
            DestinationModerated::class,
            PartnerVerified::class,
            PartnerRejected::class,
            InstitutionVerified::class,
            InstitutionRejected::class,
            LatePaymentReceived::class,
            PaymentAmountMismatch::class,
            UserRegistered::class,
        ], SendDomainNotificationListener::class);
    }
}
