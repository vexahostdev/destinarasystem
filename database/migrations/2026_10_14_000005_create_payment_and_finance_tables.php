<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->restrictOnDelete();
            $table->string('gateway'); // xendit, midtrans, manual_transfer
            $table->string('external_id')->unique(); // {booking_code}-P{n}
            $table->string('gateway_reference')->nullable();
            $table->text('checkout_url')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('pending'); // pending, paid, expired, failed, cancelled
            $table->string('payment_method')->nullable();
            $table->string('payment_channel')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('proof_path')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->json('raw_response')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway');
            $table->string('event_id');
            $table->string('external_id')->nullable();
            $table->json('payload');
            $table->boolean('signature_valid')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->text('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'event_id']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->integer('percent');
            $table->text('reason');
            $table->string('status')->default('pending'); // pending, processing, completed, rejected
            $table->foreignId('buyer_bank_id')->nullable()->constrained('banks')->restrictOnDelete();
            $table->string('buyer_account_number')->nullable();
            $table->string('buyer_account_holder')->nullable();
            $table->string('proof_path')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('partner_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('payout_number')->unique();
            $table->foreignId('partner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->restrictOnDelete();
            $table->unsignedBigInteger('gross_amount');
            $table->unsignedBigInteger('commission_amount');
            $table->unsignedBigInteger('net_amount');
            $table->string('status')->default('pending'); // pending, scheduled, transferred, on_hold
            $table->timestamp('eligible_at');
            $table->timestamp('transferred_at')->nullable();
            $table->string('transfer_proof_path')->nullable();
            $table->string('bank_name');
            $table->string('bank_account_number');
            $table->string('bank_account_holder');
            $table->foreignId('processed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->unique(); // YYYY-MM
            $table->unsignedBigInteger('total_paid_in')->default(0);
            $table->unsignedBigInteger('total_refunded')->default(0);
            $table->unsignedBigInteger('total_platform_fee')->default(0);
            $table->unsignedBigInteger('total_insurance')->default(0);
            $table->unsignedBigInteger('total_commission')->default(0);
            $table->unsignedBigInteger('total_discount')->default(0);
            $table->unsignedBigInteger('total_payout_transferred')->default(0);
            $table->unsignedBigInteger('total_payout_pending')->default(0);
            $table->unsignedBigInteger('net_platform_revenue')->default(0);
            $table->string('status')->default('open'); // open, closed
            $table->foreignId('closed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_reconciliations');
        Schema::dropIfExists('partner_payouts');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_webhook_logs');
        Schema::dropIfExists('payments');
    }
};
