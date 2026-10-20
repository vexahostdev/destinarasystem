<?php

use App\Http\Controllers\Api\PublicApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — SPEC §15: Public API untuk Compro (destinara.id)
|--------------------------------------------------------------------------
| Prefix: /api/v1/public
| Throttle: 60 per menit per IP
| Cache: 5 menit (otomatis terinvalidasi saat destinasi berubah)
| Response format: { "data": ..., "meta": {...} }
*/

Route::prefix('v1/public')->middleware(['throttle:60,1'])->group(function () {
    Route::get('/destinations', [PublicApiController::class, 'destinations'])->name('api.public.destinations');
    Route::get('/destinations/{slug}', [PublicApiController::class, 'destination'])->name('api.public.destinations.show');
    Route::get('/categories', [PublicApiController::class, 'categories'])->name('api.public.categories');
    Route::get('/stats', [PublicApiController::class, 'stats'])->name('api.public.stats');
});
