<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); // fixed, percent
            $table->unsignedBigInteger('value');
            $table->unsignedBigInteger('max_discount')->nullable();
            $table->unsignedBigInteger('min_transaction')->default(0);
            $table->integer('quota')->default(0);
            $table->integer('used_count')->default(0);
            $table->integer('per_user_limit')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('destination_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->foreignId('destination_id')->constrained('destinations')->restrictOnDelete();
            $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
            $table->string('inquiry_type'); // study_tour, penelitian
            $table->date('planned_date_start');
            $table->date('planned_date_end');
            $table->integer('participant_count');
            $table->integer('companion_count')->default(0);
            $table->text('purpose_notes')->nullable();
            $table->boolean('needs_permit_letter')->default(false);
            $table->json('permit_details')->nullable();
            $table->unsignedBigInteger('subtotal_amount');
            $table->unsignedBigInteger('addons_amount')->default(0);
            $table->unsignedBigInteger('insurance_amount')->default(0);
            $table->unsignedBigInteger('platform_fee_amount')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->unsignedInteger('commission_percent');
            $table->unsignedBigInteger('commission_amount');
            $table->unsignedBigInteger('partner_net_amount');
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->string('status')->default('menunggu_pembayaran');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('partner_confirm_deadline')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->string('cancellation_source')->nullable(); // buyer, partner, admin, system
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('destination_id');
            $table->index('user_id');
            $table->index('planned_date_start');
        });

        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('type'); // package, addon, insurance, platform_fee, discount
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('name');
            $table->integer('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('total');
            $table->timestamps();
        });

        Schema::create('booking_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('booking_slot_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('availability_slot_id')->constrained('availability_slots')->cascadeOnDelete();
            $table->integer('pax');
            $table->string('type'); // held, booked
            $table->timestamps();
        });

        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('scanned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('scanned_at');
            $table->integer('actual_participant_count');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('voucher_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->restrictOnDelete();
            $table->foreignId('destination_id')->constrained('destinations')->restrictOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->integer('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->text('partner_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->string('status')->default('published'); // pending, published, hidden
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('voucher_redemptions');
        Schema::dropIfExists('check_ins');
        Schema::dropIfExists('booking_slot_allocations');
        Schema::dropIfExists('booking_status_histories');
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('vouchers');
    }
};
