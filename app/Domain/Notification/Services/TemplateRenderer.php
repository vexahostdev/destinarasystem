<?php

namespace App\Domain\Notification\Services;

use App\Models\Booking;
use App\Models\User;

class TemplateRenderer
{
    /**
     * Render message template with context variables.
     */
    public function render(string $template, array $context = []): string
    {
        $companyName = setting('company_name', 'Destinara');
        $defaults = [
            'company_name' => $companyName,
        ];

        // 1. Auto-expand Booking context if present
        if (isset($context['booking']) && $context['booking'] instanceof Booking) {
            $b = $context['booking'];
            $defaults['booking_code'] = $b->booking_code;
            $defaults['destination_name'] = $b->destination?->name ?? '';
            $defaults['destination_city'] = $b->destination?->city ?? '';
            $defaults['organization_name'] = $b->destination?->partner?->partnerProfile?->organization_name ?? $b->destination?->partner?->name ?? 'Mitra Pengelola';
            $defaults['partner_name'] = $b->destination?->partner?->name ?? '';
            $defaults['institution_name'] = $b->institution?->institution_name ?? $b->user?->name ?? 'Institusi Pemesan';
            $defaults['pic_name'] = $b->institution?->pic_name ?? $b->user?->name ?? 'Penanggung Jawab';
            $defaults['pic_phone'] = $b->institution?->pic_phone ?? $b->user?->phone ?? '-';
            $defaults['pic_email'] = $b->institution?->pic_email ?? $b->user?->email ?? '-';
            $defaults['participant_count'] = (string) $b->participant_count;
            $defaults['companion_count'] = (string) ($b->companion_count ?? 0);
            $defaults['package_name'] = $b->package?->name ?? 'Paket Edukasi';
            $defaults['planned_date_start'] = $b->planned_date_start?->format('d/m/Y') ?? '-';
            $defaults['planned_date_end'] = $b->planned_date_end?->format('d/m/Y') ?? '-';
            $defaults['new_start_date'] = $b->planned_date_start?->format('d/m/Y') ?? '-';
            $defaults['new_end_date'] = $b->planned_date_end?->format('d/m/Y') ?? '-';
            $defaults['total_amount'] = number_format($b->total_amount, 0, ',', '.');
            $defaults['subtotal_amount'] = number_format($b->subtotal_amount, 0, ',', '.');
            $defaults['expires_at'] = $b->expires_at?->format('d/m/Y H:i') ?? '-';
            $defaults['days_left'] = $b->planned_date_start ? (string) max(0, (int) round(now()->diffInDays($b->planned_date_start, false))) : '0';
            $defaults['booking_url'] = route('buyer.booking.show', $b->booking_code);
            $defaults['partner_booking_url'] = route('mitra.bookings');
            $defaults['partner_url'] = route('mitra.bookings');
        }

        // 2. Auto-expand partner_user if present
        if (isset($context['partner_user']) && $context['partner_user'] instanceof User) {
            $pu = $context['partner_user'];
            $defaults['partner_name'] = $pu->name;
            $defaults['organization_name'] = $pu->partnerProfile?->organization_name ?? $pu->name;
        }

        // 3. Auto-expand user if present
        if (isset($context['user']) && $context['user'] instanceof User) {
            $u = $context['user'];
            $defaults['user_name'] = $u->name;
            if (! isset($defaults['pic_name'])) {
                $defaults['pic_name'] = $u->name;
            }
        }

        // 4. Aliases for common synonym keys
        if (isset($context['moderation_note']) && ! isset($context['moderation_notes'])) {
            $defaults['moderation_notes'] = $context['moderation_note'];
        }
        if (isset($context['moderation_notes']) && ! isset($context['moderation_note'])) {
            $defaults['moderation_note'] = $context['moderation_notes'];
        }
        if (isset($context['cancel_reason']) && ! isset($context['reason'])) {
            $defaults['reason'] = $context['cancel_reason'];
        }
        if (isset($context['comment']) && ! isset($context['review_comment'])) {
            $defaults['review_comment'] = $context['comment'];
        }
        if (isset($context['planned_date_start']) && ! isset($context['new_start_date'])) {
            $defaults['new_start_date'] = $context['planned_date_start'];
        }
        if (isset($context['planned_date_end']) && ! isset($context['new_end_date'])) {
            $defaults['new_end_date'] = $context['planned_date_end'];
        }
        if (isset($context['buyer_bank_name']) && ! isset($context['bank_name'])) {
            $defaults['bank_name'] = $context['buyer_bank_name'];
        }
        if (isset($context['buyer_account_number']) && ! isset($context['account_number'])) {
            $defaults['account_number'] = $context['buyer_account_number'];
        }
        if (isset($context['buyer_account_holder']) && ! isset($context['account_holder'])) {
            $defaults['account_holder'] = $context['buyer_account_holder'];
        }

        $merged = array_merge($defaults, $context);

        // Single-pass replacement using regex callback to eliminate brace nesting bugs (e.g. {DST-...})
        $rendered = preg_replace_callback('/\{\{?\s*([a-zA-Z0-9_\.]+)\s*\}?\}/', function ($matches) use ($merged) {
            $key = $matches[1];
            if (array_key_exists($key, $merged)) {
                $val = $merged[$key];

                return is_scalar($val) ? (string) $val : '';
            }

            // Unknown placeholder -> remove so no raw {{key}} or {key} leaks to recipients
            return '';
        }, $template);

        // Final cleanup for any leftover bracket tokens
        return preg_replace('/\{\{?[a-zA-Z0-9_\.]+\}?\}|\{[a-zA-Z0-9_\.]+\}/', '', $rendered);
    }
}
