<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'payout_id',
        'type',
        'document_number',
        'title',
        'file_path',
        'verification_hash',
        'status',
        'generated_at',
        'generated_by',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'generated_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(PartnerPayout::class, 'payout_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function isFinal(): bool
    {
        return $this->status === DocumentStatus::FINAL;
    }

    public function isVoid(): bool
    {
        return $this->status === DocumentStatus::VOID;
    }
}
