<?php

namespace App\Domain\Document\Services;

use App\Enums\DocumentType;
use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class DocumentNumberGenerator
{
    /**
     * Default number formats per document type according to SPEC §11.
     */
    protected const DEFAULT_FORMATS = [
        'invoice' => 'INV/{YYYY}/{MM}/{SEQ:5}',
        'receipt' => 'KWT/{YYYY}/{MM}/{SEQ:5}',
        'confirmation_letter' => 'SK/{YYYY}/{MM}/{SEQ:5}',
        'e_voucher' => 'EV/{YYYY}/{MM}/{SEQ:5}',
        'permit_draft' => 'DRAFT-IZIN/{YYYY}/{MM}/{SEQ:5}',
        'permit_signed' => 'IZIN/{YYYY}/{MM}/{SEQ:5}',
        'payout_receipt' => 'BKT/{YYYY}/{MM}/{SEQ:5}',
    ];

    /**
     * Generate unique document number atomically using document_sequences with row lock.
     */
    public function generate(DocumentType|string $type): string
    {
        $typeStr = is_string($type) ? $type : $type->value;
        $period = now()->format('Y-m');

        return DB::transaction(function () use ($typeStr, $period) {
            $seq = DocumentSequence::where('type', $typeStr)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                $seq = DocumentSequence::create([
                    'type' => $typeStr,
                    'period' => $period,
                    'last_number' => 1,
                ]);
                $nextNum = 1;
            } else {
                $seq->last_number++;
                $seq->save();
                $nextNum = $seq->last_number;
            }

            $format = (string) setting("doc_number_format_{$typeStr}", self::DEFAULT_FORMATS[$typeStr] ?? 'DOC/{YYYY}/{MM}/{SEQ:5}');

            // Format replacements
            $formatted = str_replace(
                ['{YYYY}', '{MM}'],
                [now()->format('Y'), now()->format('m')],
                $format
            );

            // Replace {SEQ:n}
            return preg_replace_callback('/\{SEQ:(\d+)\}/', function ($matches) use ($nextNum) {
                $padding = (int) $matches[1];
                return sprintf("%0{$padding}d", $nextNum);
            }, $formatted);
        });
    }
}
