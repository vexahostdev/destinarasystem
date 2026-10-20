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
use App\Events\PaymentProofRejected;
use App\Events\PayoutCreated;
use App\Events\PayoutTransferred;
use App\Events\RefundCompleted;
use App\Events\RefundCreated;
use App\Events\UserRegistered;
use App\Listeners\GenerateBookingDocumentsListener;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureAccountType;
use App\Http\Middleware\EnsurePartnerVerified;
use App\Listeners\SendDomainNotificationListener;
use App\Models\User;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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

        // Middleware akun ikut diterapkan ulang pada request /livewire/update (Audit v11 K-10)
        Livewire::addPersistentMiddleware([
            EnsureAccountActive::class,
            EnsureAccountType::class,
            EnsureEmailIsVerified::class,
            EnsurePartnerVerified::class,
        ]);

        // Set locale Indonesia untuk Carbon dan format tanggal (C4)
        \Carbon\Carbon::setLocale('id');
        @setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id', 'indonesian');

        // Format tanggal berbahasa Indonesia pada pesan validasi tanggal (V7-2, V8-3)
        $dateReplacer = function ($message, $attribute, $rule, $parameters, $validator) {
            $param = $parameters[0] ?? '';
            if (! empty($param) && strtotime($param)) {
                return str_replace(':date', \Carbon\Carbon::parse($param)->translatedFormat('d F Y'), $message);
            }

            return str_replace(':date', $validator->getDisplayableAttribute($param), $message);
        };

        foreach (['after_or_equal', 'after', 'before_or_equal', 'before'] as $rule) {
            Validator::replacer($rule, $dateReplacer);
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
            PaymentProofRejected::class,
            UserRegistered::class,
        ], SendDomainNotificationListener::class);
    }
}
