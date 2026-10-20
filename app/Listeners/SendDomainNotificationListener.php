<?php

namespace App\Listeners;

use App\Domain\Notification\Services\NotificationDispatcher;
use App\Enums\PayoutStatus;
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

class SendDomainNotificationListener
{
    public bool $afterCommit = true;

    public string $queue = 'notifications';

    public function __construct(
        protected NotificationDispatcher $dispatcher
    ) {}

    /**
     * Map domain events to multi-channel notifications (SPEC §10.2).
     */
    public function handle(mixed $event): void
    {
        if ($event instanceof BookingCreated) {
            $b = $event->booking;
            $this->dispatcher->send('booking.created', [
                'booking' => $b,
                'user' => $b->user,
                'booking_code' => $b->booking_code,
                'destination_name' => $b->destination?->name,
                'institution_name' => $b->institution?->institution_name ?? $b->user?->name,
                'pic_name' => $b->institution?->pic_name ?? $b->user?->name,
                'participant_count' => $b->participant_count,
                'planned_date_start' => $b->planned_date_start?->format('d/m/Y'),
                'planned_date_end' => $b->planned_date_end?->format('d/m/Y'),
                'total_amount' => number_format($b->total_amount, 0, ',', '.'),
                'expires_at' => $b->expires_at?->format('d/m/Y H:i') ?? '-',
                'payment_url' => route('buyer.booking.payment', $b->booking_code),
            ], ['buyer', 'admin']);
        } elseif ($event instanceof BookingPaid) {
            $b = $event->booking;
            $this->dispatcher->send('payment.paid', [
                'booking' => $b,
                'user' => $b->user,
                'booking_code' => $b->booking_code,
                'destination_name' => $b->destination?->name,
                'institution_name' => $b->institution?->institution_name ?? $b->user?->name,
                'pic_name' => $b->institution?->pic_name ?? $b->user?->name,
                'organization_name' => $b->destination?->partner?->partnerProfile?->organization_name ?? $b->destination?->partner?->name ?? 'Mitra Pengelola',
                'participant_count' => $b->participant_count,
                'planned_date_start' => $b->planned_date_start?->format('d/m/Y'),
                'total_amount' => number_format($b->total_amount, 0, ',', '.'),
                'confirm_deadline' => $b->partner_confirmation_deadline ? $b->partner_confirmation_deadline->format('d/m/Y H:i') : now()->addHours((int) setting('partner_confirm_sla_hours', 48))->format('d/m/Y H:i'),
                'partner_booking_url' => route('mitra.bookings'),
                'payment_gateway' => $event->payment?->gateway ?? 'Payment Gateway',
            ], ['buyer', 'partner', 'admin']);
        } elseif ($event instanceof BookingConfirmed) {
            $b = $event->booking;
            $this->dispatcher->send('booking.confirmed', [
                'booking' => $b,
                'user' => $b->user,
                'booking_code' => $b->booking_code,
                'destination_name' => $b->destination?->name,
                'institution_name' => $b->institution?->institution_name ?? $b->user?->name,
                'pic_name' => $b->institution?->pic_name ?? $b->user?->name,
                'organization_name' => $b->destination?->partner?->partnerProfile?->organization_name ?? $b->destination?->partner?->name ?? 'Mitra Pengelola',
                'planned_date_start' => $b->planned_date_start?->format('d/m/Y'),
                'booking_url' => route('buyer.booking.show', $b->booking_code),
            ], ['buyer', 'partner', 'admin']);
        } elseif ($event instanceof BookingRejected) {
            $b = $event->booking;
            $this->dispatcher->send('booking.rejected', [
                'booking' => $b,
                'user' => $b->user,
                'booking_code' => $b->booking_code,
                'destination_name' => $b->destination?->name,
                'institution_name' => $b->institution?->institution_name ?? $b->user?->name,
                'pic_name' => $b->institution?->pic_name ?? $b->user?->name,
                'organization_name' => $b->destination?->partner?->partnerProfile?->organization_name ?? $b->destination?->partner?->name ?? 'Mitra Pengelola',
                'cancel_reason' => $event->reason,
                'reason' => $event->reason,
                'total_amount' => number_format($b->total_amount, 0, ',', '.'),
            ], ['buyer', 'partner', 'admin']);
        } elseif ($event instanceof BookingCancelled) {
            $b = $event->booking;
            $this->dispatcher->send('booking.cancelled', [
                'booking' => $b,
                'user' => $b->user,
                'booking_code' => $b->booking_code,
                'destination_name' => $b->destination?->name,
                'institution_name' => $b->institution?->institution_name ?? $b->user?->name,
                'pic_name' => $b->institution?->pic_name ?? $b->user?->name,
                'organization_name' => $b->destination?->partner?->partnerProfile?->organization_name ?? $b->destination?->partner?->name ?? 'Mitra Pengelola',
                'cancel_reason' => $event->reason,
                'reason' => $event->reason,
                'refund_amount' => number_format($b->refund?->amount ?? 0, 0, ',', '.'),
                'refund_percent' => $b->refund?->percent ?? 0,
                'cancellation_source' => $b->cancellation_source ?? 'buyer',
            ], ['buyer', 'partner', 'admin']);
        } elseif ($event instanceof BookingRescheduled) {
            $b = $event->booking;
            $isConfirmed = $b->status === \App\Enums\BookingStatus::DIKONFIRMASI;
            $documentsNote = $isConfirmed
                ? 'Surat Konfirmasi dan E-Voucher baru telah diterbitkan.'
                : 'Jadwal menunggu konfirmasi pengelola.';
            $partnerNote = $isConfirmed
                ? 'Dokumen baru telah diterbitkan.'
                : 'Jadwal menunggu konfirmasi pengelola. Silakan segera lakukan konfirmasi penerimaan rombongan sebelum batas SLA.';

            $this->dispatcher->send('booking.rescheduled', [
                'booking' => $b,
                'user' => $b->user,
                'booking_code' => $b->booking_code,
                'destination_name' => $b->destination?->name,
                'institution_name' => $b->institution?->institution_name ?? $b->user?->name,
                'pic_name' => $b->institution?->pic_name ?? $b->user?->name,
                'organization_name' => $b->destination?->partner?->partnerProfile?->organization_name ?? $b->destination?->partner?->name ?? 'Mitra Pengelola',
                'planned_date_start' => $b->planned_date_start?->format('d/m/Y'),
                'planned_date_end' => $b->planned_date_end?->format('d/m/Y'),
                'new_start_date' => $b->planned_date_start?->format('d/m/Y'),
                'new_end_date' => $b->planned_date_end?->format('d/m/Y'),
                'documents_note' => $documentsNote,
                'partner_note' => $partnerNote,
            ], ['buyer', 'partner', 'admin']);
        } elseif ($event instanceof BookingExpired) {
            $b = $event->booking;
            $this->dispatcher->send('booking.expired', [
                'booking' => $b,
                'user' => $b->user,
                'booking_code' => $b->booking_code,
                'destination_name' => $b->destination?->name,
                'pic_name' => $b->institution?->pic_name ?? $b->user?->name,
            ], ['buyer']);
        } elseif ($event instanceof BookingCheckedIn) {
            $b = $event->booking;
            $this->dispatcher->send('booking.checked_in', [
                'booking' => $b,
                'user' => $b->user,
                'booking_code' => $b->booking_code,
                'destination_name' => $b->destination?->name,
                'institution_name' => $b->institution?->institution_name ?? $b->user?->name,
                'pic_name' => $b->institution?->pic_name ?? $b->user?->name,
                'organization_name' => $b->destination?->partner?->partnerProfile?->organization_name ?? $b->destination?->partner?->name ?? 'Mitra Pengelola',
                'actual_participant_count' => $event->actualPax,
                'participant_count' => $b->participant_count,
            ], ['buyer', 'partner', 'admin']);
        } elseif ($event instanceof BookingCompleted) {
            $b = $event->booking;
            $this->dispatcher->send('booking.completed', [
                'booking' => $b,
                'user' => $b->user,
                'booking_code' => $b->booking_code,
                'destination_name' => $b->destination?->name,
                'institution_name' => $b->institution?->institution_name ?? $b->user?->name,
                'pic_name' => $b->institution?->pic_name ?? $b->user?->name,
                'organization_name' => $b->destination?->partner?->partnerProfile?->organization_name ?? $b->destination?->partner?->name ?? 'Mitra Pengelola',
                'review_url' => route('buyer.booking.show', $b->booking_code),
            ], ['buyer', 'partner', 'admin']);
        } elseif ($event instanceof RefundCreated) {
            $r = $event->refund;
            $this->dispatcher->send('refund.created', [
                'booking' => $r->booking,
                'user' => $r->booking?->user,
                'booking_code' => $r->booking?->booking_code,
                'pic_name' => $r->booking?->institution?->pic_name ?? $r->booking?->user?->name,
                'refund_amount' => number_format($r->amount, 0, ',', '.'),
            ], ['buyer', 'admin']);
        } elseif ($event instanceof RefundCompleted) {
            $r = $event->refund;
            $this->dispatcher->send('refund.completed', [
                'booking' => $r->booking,
                'user' => $r->booking?->user,
                'booking_code' => $r->booking?->booking_code,
                'pic_name' => $r->booking?->institution?->pic_name ?? $r->booking?->user?->name,
                'refund_amount' => number_format($r->amount, 0, ',', '.'),
                'buyer_bank_name' => $r->buyer_bank?->name ?? 'Bank Rekening',
                'buyer_account_number' => $r->buyer_account_number ?? '-',
                'buyer_account_holder' => $r->buyer_account_holder ?? '-',
            ], ['buyer', 'admin']);
        } elseif ($event instanceof PayoutCreated) {
            $p = $event->payout;
            $audiences = $p->status === PayoutStatus::ON_HOLD ? ['partner', 'admin'] : ['partner'];
            $this->dispatcher->send('payout.created', [
                'partner_user' => $p->partner,
                'payout_number' => $p->payout_number,
                'organization_name' => $p->partner?->partnerProfile?->organization_name ?? $p->partner?->name,
                'booking_code' => $p->booking?->booking_code ?? '-',
                'net_amount' => number_format($p->net_amount, 0, ',', '.'),
            ], $audiences);
        } elseif ($event instanceof PayoutTransferred) {
            $p = $event->payout;
            $this->dispatcher->send('payout.transferred', [
                'partner_user' => $p->partner,
                'payout_number' => $p->payout_number,
                'organization_name' => $p->partner?->partnerProfile?->organization_name ?? $p->partner?->name,
                'booking_code' => $p->booking?->booking_code ?? '-',
                'net_amount' => number_format($p->net_amount, 0, ',', '.'),
                'bank_name' => $p->bank_name,
                'account_number' => $p->bank_account_number,
            ], ['partner']);
        } elseif ($event instanceof DestinationSubmitted) {
            $d = $event->destination;
            $this->dispatcher->send('destination.submitted', [
                'destination_name' => $d->name,
                'partner_name' => $d->partner?->name,
            ], ['admin']);
        } elseif ($event instanceof DestinationModerated) {
            $d = $event->destination;
            $eventKey = match ($event->action) {
                'approve' => 'destination.approved',
                'revision' => 'destination.revision_required',
                'reject' => 'destination.rejected',
                default => 'destination.approved',
            };
            $this->dispatcher->send($eventKey, [
                'partner_user' => $d->partner,
                'destination_name' => $d->name,
                'partner_name' => $d->partner?->name,
                'moderation_note' => $event->note ?? '-',
                'moderation_notes' => $event->note ?? '-',
            ], ['partner']);
        } elseif ($event instanceof PartnerVerified) {
            $pp = $event->partnerProfile;
            $this->dispatcher->send('partner.verified', [
                'partner_user' => $pp->user,
                'organization_name' => $pp->organization_name,
            ], ['partner']);
        } elseif ($event instanceof PartnerRejected) {
            $pp = $event->partnerProfile;
            $this->dispatcher->send('partner.rejected', [
                'partner_user' => $pp->user,
                'organization_name' => $pp->organization_name,
                'reason' => $event->reason ?? 'Berkas belum memenuhi persyaratan',
            ], ['partner']);
        } elseif ($event instanceof InstitutionVerified) {
            $i = $event->institution;
            $this->dispatcher->send('institution.verified', [
                'user' => $i->user,
                'pic_name' => $i->pic_name ?? $i->user?->name,
                'institution_name' => $i->institution_name,
            ], ['buyer']);
        } elseif ($event instanceof InstitutionRejected) {
            $i = $event->institution;
            $this->dispatcher->send('institution.rejected', [
                'user' => $i->user,
                'pic_name' => $i->pic_name ?? $i->user?->name,
                'institution_name' => $i->institution_name,
                'reason' => $event->reason ?? 'Berkas institusi belum memenuhi persyaratan',
            ], ['buyer']);
        } elseif ($event instanceof LatePaymentReceived) {
            $this->dispatcher->send('payment.late_received', [
                'booking_code' => $event->booking->booking_code,
                'booking_status' => $event->booking->status->label(),
                'amount' => number_format($event->payment->amount, 0, ',', '.'),
            ], ['admin']);
        } elseif ($event instanceof PaymentAmountMismatch) {
            $this->dispatcher->send('payment.amount_mismatch', [
                'external_id' => $event->externalId ?? $event->payment->external_id,
                'expected_amount' => number_format($event->expectedAmount, 0, ',', '.'),
                'received_amount' => number_format($event->receivedAmount, 0, ',', '.'),
                'booking_code' => $event->payment->booking?->booking_code ?? '-',
            ], ['admin']);
        } elseif ($event instanceof PaymentProofRejected) {
            $p = $event->payment;
            $b = $event->booking;
            $this->dispatcher->send('payment.proof_rejected', [
                'payment' => $p,
                'booking' => $b,
                'user' => $b->user,
                'booking_code' => $b->booking_code,
                'destination_name' => $b->destination?->name,
                'institution_name' => $b->institution?->institution_name ?? $b->user?->name,
                'pic_name' => $b->institution?->pic_name ?? $b->user?->name,
                'rejection_reason' => $event->reason,
                'upload_url' => route('buyer.booking.show', $b->booking_code),
            ], ['buyer']);
        } elseif ($event instanceof UserRegistered) {
            $u = $event->user;
            $this->dispatcher->send('auth.welcome', [
                'user' => $u,
                'user_name' => $u->name,
            ], [$u->isPartner() ? 'partner' : 'buyer']);
        }
    }
}
