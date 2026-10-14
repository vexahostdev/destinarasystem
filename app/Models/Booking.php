<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\InquiryType;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_code',
        'user_id',
        'institution_id',
        'destination_id',
        'package_id',
        'inquiry_type',
        'planned_date_start',
        'planned_date_end',
        'participant_count',
        'companion_count',
        'purpose_notes',
        'needs_permit_letter',
        'permit_details',
        'subtotal_amount',
        'addons_amount',
        'insurance_amount',
        'platform_fee_amount',
        'discount_amount',
        'total_amount',
        'commission_percent',
        'commission_amount',
        'partner_net_amount',
        'voucher_id',
        'status',
        'expires_at',
        'paid_at',
        'partner_confirm_deadline',
        'confirmed_at',
        'checked_in_at',
        'completed_at',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'cancellation_source',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'inquiry_type' => InquiryType::class,
            'status' => BookingStatus::class,
            'planned_date_start' => 'date',
            'planned_date_end' => 'date',
            'participant_count' => 'integer',
            'companion_count' => 'integer',
            'needs_permit_letter' => 'boolean',
            'permit_details' => 'array',
            'subtotal_amount' => 'integer',
            'addons_amount' => 'integer',
            'insurance_amount' => 'integer',
            'platform_fee_amount' => 'integer',
            'discount_amount' => 'integer',
            'total_amount' => 'integer',
            'commission_percent' => 'float',
            'commission_amount' => 'integer',
            'partner_net_amount' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'partner_confirm_deadline' => 'datetime',
            'confirmed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class)->orderBy('created_at', 'asc');
    }

    public function slotAllocations(): HasMany
    {
        return $this->hasMany(BookingSlotAllocation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function checkIn(): HasOne
    {
        return $this->hasOne(CheckIn::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class)->latestOfMany();
    }

    public function partnerPayout(): HasOne
    {
        return $this->hasOne(PartnerPayout::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function isWaitingPayment(): bool
    {
        return $this->status === BookingStatus::MENUNGGU_PEMBAYARAN;
    }

    public function isPaid(): bool
    {
        return $this->status === BookingStatus::DIBAYAR;
    }

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatus::DIKONFIRMASI;
    }

    public function isOngoing(): bool
    {
        return $this->status === BookingStatus::BERLANGSUNG;
    }

    public function isCompleted(): bool
    {
        return $this->status === BookingStatus::SELESAI;
    }

    public function isCancelled(): bool
    {
        return $this->status === BookingStatus::BATAL;
    }

    public function isExpired(): bool
    {
        return $this->status === BookingStatus::KEDALUWARSA;
    }
}
