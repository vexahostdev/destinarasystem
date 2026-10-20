<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });

        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('institution_name');
            $table->foreignId('institution_type_id')->nullable()->constrained('institution_types')->restrictOnDelete();
            $table->string('npsn')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('province_id')->nullable()->constrained('provinces')->restrictOnDelete();
            $table->string('city')->nullable();
            $table->string('pic_name');
            $table->string('pic_position')->nullable();
            $table->string('pic_phone');
            $table->string('pic_email');
            $table->string('letterhead_path')->nullable();
            $table->string('verification_status')->default('unverified'); // unverified, pending, verified, rejected
            $table->text('verification_note')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('institution_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('type');
            $table->string('file_path');
            $table->string('original_name');
            $table->timestamps();
        });

        Schema::create('partner_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('organization_name');
            $table->foreignId('organization_type_id')->nullable()->constrained('organization_types')->restrictOnDelete();
            $table->string('legal_document_number')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('province_id')->nullable()->constrained('provinces')->restrictOnDelete();
            $table->string('city')->nullable();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->restrictOnDelete();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->decimal('commission_percent_override', 5, 2)->nullable();
            $table->string('verification_status')->default('unverified'); // unverified, pending, verified, rejected
            $table->text('verification_note')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('partner_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_profile_id')->constrained('partner_profiles')->cascadeOnDelete();
            $table->string('type'); // sk_pengelola, ktp_pic, npwp, lainnya
            $table->string('file_path');
            $table->string('original_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_documents');
        Schema::dropIfExists('partner_profiles');
        Schema::dropIfExists('institution_documents');
        Schema::dropIfExists('institutions');
        Schema::dropIfExists('settings');
    }
};
