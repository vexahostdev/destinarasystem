<?php

namespace App\Domain\Notification\Clients;

use App\Domain\Notification\Exceptions\NonRetryableNotificationException;
use App\Domain\Notification\Exceptions\RateLimitedNotificationException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VexaHostClient
{
    /**
     * Normalize Indonesian phone number:
     * - Removes non-digits
     * - Converts leading 08xx to 628xx
     * - Validates regex ^62\d{8,13}$
     */
    public function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($clean, '08')) {
            $clean = '628'.substr($clean, 2);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '628'.substr($clean, 1);
        }

        if (! preg_match('/^62\d{8,13}$/', $clean)) {
            throw new NonRetryableNotificationException("Nomor telepon tidak valid: {$phone}");
        }

        return $clean;
    }

    protected function getBaseUrl(): string
    {
        return rtrim((string) setting('wa_base_url', 'https://wa.vexahostcloud.my.id/api/v1'), '/');
    }

    protected function getApiKey(): string
    {
        $key = setting('wa_api_key');
        if (empty($key)) {
            throw new NonRetryableNotificationException('VexaHost WhatsApp API Key belum dikonfigurasi di Pengaturan.');
        }

        return $key;
    }

    /**
     * Send plain text message via VexaHost.
     */
    public function sendText(string $to, string $message, ?string $sessionId = null): string
    {
        $normalizedPhone = $this->normalizePhone($to);
        $apiKey = $this->getApiKey();
        $baseUrl = $this->getBaseUrl();

        $payload = [
            'to' => $normalizedPhone,
            'message' => $message,
        ];

        if ($sessionId) {
            $payload['session_id'] = $sessionId;
        }

        $response = Http::withHeaders([
            'X-Api-Key' => $apiKey,
            'Accept' => 'application/json',
        ])->timeout(15)->post("{$baseUrl}/messages/text", $payload);

        $status = $response->status();

        if ($status === 429) {
            $retryAfter = (int) $response->header('Retry-After', 60);
            throw new RateLimitedNotificationException($retryAfter, 'VexaHost rate limit exceeded.');
        }

        if (in_array($status, [400, 401, 403, 404, 422])) {
            $errData = $response->json();
            $errMsg = $errData['message'] ?? $errData['error'] ?? $response->body();
            if (is_array($errMsg)) {
                $errMsg = json_encode($errMsg);
            }
            throw new NonRetryableNotificationException("VexaHost Client Error [{$status}]: {$errMsg}");
        }

        if ($response->failed()) {
            throw new RuntimeException("VexaHost Server Error [{$status}]: ".$response->body());
        }

        $data = $response->json();

        return (string) ($data['data']['id'] ?? uniqid('wa_msg_'));
    }

    /**
     * Send media/document file via VexaHost multipart.
     */
    public function sendMedia(string $to, string $filePath, string $caption = '', string $type = 'document'): string
    {
        $normalizedPhone = $this->normalizePhone($to);
        $apiKey = $this->getApiKey();
        $baseUrl = $this->getBaseUrl();

        if (! file_exists($filePath)) {
            throw new NonRetryableNotificationException("Berkas media tidak ditemukan: {$filePath}");
        }

        if (filesize($filePath) > 16 * 1024 * 1024) {
            throw new NonRetryableNotificationException('Ukuran berkas melebihi batas maksimal VexaHost (16MB).');
        }

        $response = Http::withHeaders([
            'X-Api-Key' => $apiKey,
            'Accept' => 'application/json',
        ])->timeout(30)
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post("{$baseUrl}/messages/media", [
                'to' => $normalizedPhone,
                'caption' => $caption,
                'type' => $type,
            ]);

        $status = $response->status();

        if ($status === 429) {
            $retryAfter = (int) $response->header('Retry-After', 60);
            throw new RateLimitedNotificationException($retryAfter, 'VexaHost rate limit exceeded.');
        }

        if (in_array($status, [400, 401, 403, 404, 422])) {
            $errData = $response->json();
            $errMsg = $errData['message'] ?? $errData['error'] ?? $response->body();
            if (is_array($errMsg)) {
                $errMsg = json_encode($errMsg);
            }
            throw new NonRetryableNotificationException("VexaHost Media Error [{$status}]: {$errMsg}");
        }

        if ($response->failed()) {
            throw new RuntimeException("VexaHost Media Server Error [{$status}]: ".$response->body());
        }

        $data = $response->json();

        return (string) ($data['data']['id'] ?? uniqid('wa_media_'));
    }

    /**
     * Check VexaHost gateway health.
     */
    public function checkHealth(): array
    {
        try {
            $apiKey = $this->getApiKey();
            $baseUrl = $this->getBaseUrl();

            $response = Http::withHeaders([
                'X-Api-Key' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(5)->get("{$baseUrl}/health");

            return [
                'status' => $response->successful() ? 'connected' : 'degraded',
                'statusCode' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'disconnected',
                'statusCode' => 500,
                'error' => $e->getMessage(),
            ];
        }
    }
}
