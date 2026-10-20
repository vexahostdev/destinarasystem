<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Storage::fake('local');
        Storage::fake('public');

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                setting([
                    'manual_transfer_accounts' => [
                        [
                            'bank' => 'BCA',
                            'number' => '1234567890',
                            'holder' => 'PT DESTINARA EDUKASI NUSANTARA',
                        ],
                    ],
                ]);
            }
        } catch (\Throwable) {
            // Ignore if DB is not ready
        }
    }
}

