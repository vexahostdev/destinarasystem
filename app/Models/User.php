<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Traits\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'account_type',
        'phone',
        'status',
        'suspended_reason',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'account_type' => AccountType::class,
            'last_login_at' => 'datetime',
        ];
    }

    public function isBuyer(): bool
    {
        return $this->account_type === AccountType::BUYER;
    }

    public function isPartner(): bool
    {
        return $this->account_type === AccountType::PARTNER;
    }

    public function isInternal(): bool
    {
        return $this->account_type === AccountType::INTERNAL;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isInternal() && $this->hasRole('super_admin');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function institution(): HasOne
    {
        return $this->hasOne(Institution::class);
    }

    public function partnerProfile(): HasOne
    {
        return $this->hasOne(PartnerProfile::class);
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class, 'partner_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function partnerPayouts(): HasMany
    {
        return $this->hasMany(PartnerPayout::class, 'partner_id');
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ]
        );

        $audience = $this->isPartner() ? 'partner' : 'buyer';
        app(\App\Domain\Notification\Services\NotificationDispatcher::class)->send(
            'auth.verify_email',
            [
                'user' => $this,
                'user_name' => $this->name,
                'action_url' => $verificationUrl,
                'email' => $this->email,
            ],
            [$audience]
        );
    }

    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ]);

        $audience = $this->isPartner() ? 'partner' : 'buyer';
        app(\App\Domain\Notification\Services\NotificationDispatcher::class)->send(
            'auth.reset_password',
            [
                'user' => $this,
                'user_name' => $this->name,
                'action_url' => $resetUrl,
                'email' => $this->email,
            ],
            [$audience]
        );
    }
}
