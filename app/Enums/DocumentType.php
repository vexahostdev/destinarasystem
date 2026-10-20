<?php

namespace App\Enums;

enum DocumentType: string
{
    case INVOICE = 'invoice';
    case RECEIPT = 'receipt';
    case CONFIRMATION_LETTER = 'confirmation_letter';
    case E_VOUCHER = 'e_voucher';
    case PERMIT_DRAFT = 'permit_draft';
    case PERMIT_SIGNED = 'permit_signed';
    case PAYOUT_RECEIPT = 'payout_receipt';

    public function label(): string
    {
        return match ($this) {
            self::INVOICE => 'Invoice Tagihan',
            self::RECEIPT => 'Kwitansi Pembayaran',
            self::CONFIRMATION_LETTER => 'Surat Konfirmasi Kunjungan',
            self::E_VOUCHER => 'E-Voucher Kunjungan',
            self::PERMIT_DRAFT => 'Draf Surat Izin',
            self::PERMIT_SIGNED => 'Surat Izin Bertanda Tangan',
            self::PAYOUT_RECEIPT => 'Bukti Transfer Payout',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INVOICE => 'amber',
            self::RECEIPT => 'emerald',
            self::CONFIRMATION_LETTER => 'blue',
            self::E_VOUCHER => 'indigo',
            self::PERMIT_DRAFT => 'purple',
            self::PERMIT_SIGNED => 'teal',
            self::PAYOUT_RECEIPT => 'slate',
        };
    }
}
