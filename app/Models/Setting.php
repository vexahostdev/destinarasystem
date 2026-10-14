<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get decrypted or parsed value.
     */
    public function getParsedValueAttribute(): mixed
    {
        $raw = $this->attributes['value'] ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        if ($this->is_encrypted) {
            try {
                $raw = Crypt::decryptString($raw);
            } catch (\Exception $e) {
                return null;
            }
        }

        return match ($this->type) {
            'bool', 'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'int', 'integer', 'money' => (int) $raw,
            'float', 'percent' => (float) $raw,
            'json' => json_decode($raw, true),
            default => $raw,
        };
    }

    /**
     * Set encrypted or serialized value.
     */
    public function setParsedValue(mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['value'] = null;
            return;
        }

        if (is_array($value)) {
            $value = json_encode($value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } else {
            $value = (string) $value;
        }

        if ($this->is_encrypted) {
            $this->attributes['value'] = Crypt::encryptString($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }
}
