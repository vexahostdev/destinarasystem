<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use Auditable, HasFactory;

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

        if ($raw === null) {
            return '';
        }

        if ($this->is_encrypted) {
            if ($raw === '') {
                return '';
            }
            try {
                $raw = Crypt::decryptString($raw);
            } catch (\Exception $e) {
                return '';
            }
        }

        return match ($this->type) {
            'bool', 'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'int', 'integer', 'money' => $raw === '' ? 0 : (int) $raw,
            'float', 'percent' => $raw === '' ? 0.0 : (float) $raw,
            'json' => $raw === '' ? [] : json_decode($raw, true),
            default => (string) $raw,
        };
    }

    /**
     * Set encrypted or serialized value.
     */
    public function setParsedValue(mixed $value): void
    {
        if ($value === null) {
            $this->attributes['value'] = '';

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
            $this->attributes['value'] = $value === '' ? '' : Crypt::encryptString($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }
}
