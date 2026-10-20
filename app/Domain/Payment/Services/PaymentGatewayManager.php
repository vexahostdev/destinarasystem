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

        // Fallback to default if requested gateway is not enabled
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

    /**
     * Get list of gateways that are enabled AND fully configured.
     *
     * @return array<string>
     */
    public function getAvailableGateways(): array
    {
        $rawEnabled = setting('enabled_gateways', ['xendit', 'midtrans', 'manual_transfer']);
        if (! is_array($rawEnabled)) {
            $rawEnabled = json_decode((string) $rawEnabled, true) ?? [];
        }

        $available = [];
        foreach ($rawEnabled as $gw) {
            if ($gw === 'xendit') {
                $sec = setting('xendit_secret_key');
                $token = setting('xendit_callback_token');
                if (! empty($sec) && ! empty($token)) {
                    $available[] = 'xendit';
                }
            } elseif ($gw === 'midtrans') {
                $srv = setting('midtrans_server_key');
                if (! empty($srv)) {
                    $available[] = 'midtrans';
                }
            } elseif ($gw === 'manual_transfer') {
                $manualAccounts = setting('manual_transfer_accounts', []);
                if (! is_array($manualAccounts)) {
                    $manualAccounts = json_decode((string) $manualAccounts, true) ?? [];
                }
                if (! empty($manualAccounts) && count($manualAccounts) > 0) {
                    $available[] = 'manual_transfer';
                }
            }
        }

        return $available;
    }
}
