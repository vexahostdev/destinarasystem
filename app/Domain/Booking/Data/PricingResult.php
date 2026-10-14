<?php

namespace App\Domain\Booking\Data;

class PricingResult
{
    public function __construct(
        public int $packagePricePerPax,
        public int $packageTotal,
        public int $addonsTotal,
        public int $subtotal,
        public int $insurancePerPax,
        public int $insuranceAmount,
        public int $platformFeeAmount,
        public int $discountAmount,
        public int $totalAmount,
        public int $commissionPercent,
        public int $commissionAmount,
        public int $partnerNetAmount,
        public ?int $voucherId = null,
        public array $items = []
    ) {}

    public function toArray(): array
    {
        return [
            'package_price_per_pax' => $this->packagePricePerPax,
            'package_total' => $this->packageTotal,
            'addons_total' => $this->addonsTotal,
            'subtotal' => $this->subtotal,
            'insurance_per_pax' => $this->insurancePerPax,
            'insurance_amount' => $this->insuranceAmount,
            'platform_fee_amount' => $this->platformFeeAmount,
            'discount_amount' => $this->discountAmount,
            'total_amount' => $this->totalAmount,
            'commission_percent' => $this->commissionPercent,
            'commission_amount' => $this->commissionAmount,
            'partner_net_amount' => $this->partnerNetAmount,
            'voucher_id' => $this->voucherId,
            'items' => $this->items,
        ];
    }
}
