<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('payout_id')->nullable()->constrained('partner_payouts')->nullOnDelete();
            $table->string('type'); // invoice, receipt, confirmation_letter, e_voucher, permit_draft, permit_signed, payout_receipt
            $table->string('document_number')->unique();
            $table->string('title');
            $table->string('file_path');
            $table->string('verification_hash')->unique();
            $table->string('status')->default('final'); // draft, final, void
            $table->timestamp('generated_at');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('period', 7); // YYYY-MM
            $table->integer('last_number')->default(0);
            $table->timestamps();

            $table->unique(['type', 'period']);
        });

        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('title');
            $table->longText('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event_key');
            $table->string('channel'); // mail, whatsapp, database
            $table->string('audience'); // buyer, partner, admin, user
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['event_key', 'channel', 'audience']);
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('notifiable_type')->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->string('event_key');
            $table->string('channel'); // mail, whatsapp, database
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('status')->default('queued'); // queued, sent, delivered, read, failed
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
        });

        Schema::create('wa_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_id')->unique();
            $table->string('event');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['target_type', 'target_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('wa_webhook_logs');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('documents');
    }
};
