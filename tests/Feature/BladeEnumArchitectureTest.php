<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class BladeEnumArchitectureTest extends TestCase
{
    /**
     * Pastikan tidak ada field ber-cast Enum di Blade yang dibandingkan langsung dengan string literal.
     */
    public function test_no_enum_cast_fields_compared_with_string_literals_in_blade_views(): void
    {
        $viewsPath = resource_path('views');
        $directory = new RecursiveDirectoryIterator($viewsPath);
        $iterator = new RecursiveIteratorIterator($directory);

        $offendingOccurrences = [];

        // Pola regex mendeteksi perbandingan field enum dengan string literal:
        // misal: $booking->status === '...' atau $destination->status === '...'
        $patterns = [
            '/(?:\$booking|\$b|\$nextBooking)->(?:status|inquiry_type)\s*===?\s*[\'"][^\'"]+[\'"]/',
            '/(?:\$destination|\$dest|\$d)->status\s*===?\s*[\'"][^\'"]+[\'"]/',
            '/(?:\$payment|\$p)->status\s*===?\s*[\'"][^\'"]+[\'"]/',
            '/(?:\$payout)->status\s*===?\s*[\'"][^\'"]+[\'"]/',
            '/(?:\$refund|\$r)->status\s*===?\s*[\'"][^\'"]+[\'"]/',
            '/(?:\$review|\$rev)->status\s*===?\s*[\'"][^\'"]+[\'"]/',
            '/(?:\$log)->(?:status|channel)\s*===?\s*[\'"][^\'"]+[\'"]/',
            '/(?:\$institution|\$inst|\$profile|\$user)->(?:verification_status|account_type)\s*===?\s*[\'"][^\'"]+[\'"]/',
            '/->(?:verification_status|inquiry_type|account_type)\s*===?\s*[\'"][^\'"]+[\'"]/',
        ];

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $lines = file($file->getRealPath());
                foreach ($lines as $lineIndex => $line) {
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $line, $matches)) {
                            $offendingOccurrences[] = sprintf(
                                '%s:%d: %s',
                                str_replace($viewsPath, 'resources/views', $file->getRealPath()),
                                $lineIndex + 1,
                                trim($matches[0])
                            );
                        }
                    }
                }
            }
        }

        $this->assertEmpty(
            $offendingOccurrences,
            "Ditemukan perbandingan field Enum dengan string literal di Blade:\n".implode("\n", $offendingOccurrences)
        );
    }
}
