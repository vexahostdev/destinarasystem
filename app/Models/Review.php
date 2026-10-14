<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'destination_id',
        'institution_id',
        'rating',
        'comment',
        'partner_reply',
        'replied_at',
        'status',
        'moderated_by',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'replied_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Institution::class, 'id', 'id', 'institution_id', 'user_id');
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}
