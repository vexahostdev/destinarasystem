<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Drivers\ManualTransferGateway;
use App\Domain\Payment\Drivers\MidtransGateway;
use App\Domain\Payment\Drivers\XenditGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * Resolve payment gateway driver by key or default from settings.
     */
    public function driver(?string $key = null): PaymentGateway
    {
        $gatewayKey = $key ?: setting('default_gateway', 'xendit');
        $enabledGateways = setting('enabled_gateways', ['xendit', 'midtrans', 'manual_transfer']);

        if (! is_array($enabledGateways)) {
            $enabledGateways = ['xendit', 'midtrans', 'manual_transfer'];
        }

        // Validate that requested gateway is enabled (unless testing or admin override)
        if (! in_array($gatewayKey, $enabledGateways, true)) {
            $gatewayKey = setting('default_gateway', 'xendit');
        }

        return match ($gatewayKey) {
            'xendit' => app(XenditGateway::class),
            'midtrans' => app(MidtransGateway::class),
            'manual_transfer' => app(ManualTransferGateway::class),
            default => throw new InvalidArgumentException("Payment gateway driver [{$gatewayKey}] tidak didukung."),
        };
    }
}
