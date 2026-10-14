<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destination_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10);
            $table->timestamps();
        });

        Schema::create('institution_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('organization_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cancellation_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('cancellation_policy_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancellation_policy_id')->constrained('cancellation_policies')->cascadeOnDelete();
            $table->integer('min_days_before');
            $table->integer('refund_percent');
            $table->timestamps();
        });

        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
        Schema::dropIfExists('cancellation_policy_tiers');
        Schema::dropIfExists('cancellation_policies');
        Schema::dropIfExists('banks');
        Schema::dropIfExists('organization_types');
        Schema::dropIfExists('institution_types');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('facilities');
        Schema::dropIfExists('destination_categories');
    }
};
