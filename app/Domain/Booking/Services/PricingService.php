<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Data\PricingResult;
use App\Enums\AddonPricingType;
use App\Models\Addon;
use App\Models\Package;
use App\Models\Voucher;

class PricingService
{
    /**
     * Calculate comprehensive booking pricing breakdown according to SPEC §8.2.
     *
     * @param  array  $selectedAddonIds  Array of addon IDs or map of [addon_id => quantity]
     * @param  int|null  $userId  (for voucher validation)
     */
    public function calculate(
        Package $package,
        int $pax,
        array $selectedAddonIds = [],
        ?Voucher $voucher = null,
        ?int $userId = null
    ): PricingResult {
        $pax = max(1, $pax);
        $durationDays = max(1, $package->duration_days);

        // 1. Package Total
        $packagePricePerPax = (int) $package->price_per_pax;
        $packageTotal = $packagePricePerPax * $pax;

        $items = [
            [
                'type' => 'package',
                'reference_id' => $package->id,
                'name' => "Paket {$package->name} ({$durationDays} Hari)",
                'quantity' => $pax,
                'unit_price' => $packagePricePerPax,
                'total' => $packageTotal,
            ],
        ];

        // 2. Add-ons Total
        $addonsTotal = 0;
        if (! empty($selectedAddonIds)) {
            // Normalize array of IDs or key => qty
            $addonIds = array_is_list($selectedAddonIds)
                ? $selectedAddonIds
                : array_keys($selectedAddonIds);

            $addons = Addon::whereIn('id', $addonIds)
                ->where('destination_id', $package->destination_id)
                ->where('is_active', true)
                ->get();

            foreach ($addons as $addon) {
                $qty = 1;
                $price = (int) $addon->price;
                $addonTotal = match ($addon->pricing_type) {
                    AddonPricingType::PER_PAX => $price * $pax,
                    AddonPricingType::PER_GROUP => $price,
                    AddonPricingType::PER_DAY => $price * $durationDays,
                    AddonPricingType::PER_PAX_PER_DAY => $price * $pax * $durationDays,
                    default => $price * $pax,
                };

                $addonsTotal += $addonTotal;

                $items[] = [
                    'type' => 'addon',
                    'reference_id' => $addon->id,
                    'name' => "Add-on: {$addon->name}",
                    'quantity' => $addon->pricing_type === AddonPricingType::PER_GROUP ? 1 : $pax,
                    'unit_price' => $price,
                    'total' => $addonTotal,
                ];
            }
        }

        // 3. Subtotal
        $subtotal = $packageTotal + $addonsTotal;

        // 4. Insurance
        $insuranceEnabled = (bool) setting('insurance_enabled', true);
        $insurancePerPax = $insuranceEnabled ? (int) setting('insurance_per_pax', 2000) : 0;
        $insuranceAmount = $insurancePerPax * $pax;

        if ($insuranceAmount > 0) {
            $items[] = [
                'type' => 'insurance',
                'reference_id' => null,
                'name' => "Asuransi Perjalanan Pelajar ({$pax} Pax)",
                'quantity' => $pax,
                'unit_price' => $insurancePerPax,
                'total' => $insuranceAmount,
            ];
        }

        // 5. Platform Fee
        $platformFeeType = setting('platform_fee_type', 'flat');
        $platformFeeValue = (int) setting('platform_fee_value', 25000);
        $platformFeeAmount = $platformFeeType === 'flat'
            ? $platformFeeValue
            : (int) round($subtotal * ($platformFeeValue / 100));

        if ($platformFeeAmount > 0) {
            $items[] = [
                'type' => 'platform_fee',
                'reference_id' => null,
                'name' => 'Biaya Layanan Platform',
                'quantity' => 1,
                'unit_price' => $platformFeeAmount,
                'total' => $platformFeeAmount,
            ];
        }

        // 6. Voucher Discount (ditanggung platform, tidak mengurangi partner_net)
        $discountAmount = 0;
        $voucherId = null;
        if ($voucher && $voucher->isValidFor($subtotal, $package->destination_id, $userId)) {
            $discountAmount = $voucher->calculateDiscount($subtotal + $platformFeeAmount);
            $voucherId = $voucher->id;

            if ($discountAmount > 0) {
                $items[] = [
                    'type' => 'discount',
                    'reference_id' => $voucher->id,
                    'name' => "Diskon Voucher ({$voucher->code})",
                    'quantity' => 1,
                    'unit_price' => $discountAmount,
                    'total' => -$discountAmount,
                ];
            }
        }

        // 7. Total Amount
        $totalAmount = max(0, $subtotal + $insuranceAmount + $platformFeeAmount - $discountAmount);

        // 8. Partner Commission & Net Amount
        $partner = $package->destination->partner;
        $commissionPercentOverride = $partner?->partnerProfile?->commission_percent_override;
        $defaultCommissionPercent = (int) setting('default_commission_percent', 10);
        $commissionPercent = $commissionPercentOverride !== null ? (int) $commissionPercentOverride : $defaultCommissionPercent;

        $commissionAmount = (int) round($subtotal * ($commissionPercent / 100));
        $partnerNetAmount = max(0, $subtotal - $commissionAmount);

        return new PricingResult(
            packagePricePerPax: $packagePricePerPax,
            packageTotal: $packageTotal,
            addonsTotal: $addonsTotal,
            subtotal: $subtotal,
            insurancePerPax: $insurancePerPax,
            insuranceAmount: $insuranceAmount,
            platformFeeAmount: $platformFeeAmount,
            discountAmount: $discountAmount,
            totalAmount: $totalAmount,
            commissionPercent: $commissionPercent,
            commissionAmount: $commissionAmount,
            partnerNetAmount: $partnerNetAmount,
            voucherId: $voucherId,
            items: $items
        );
    }
}
