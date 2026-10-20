<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('destination_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('province_id')->constrained('provinces')->restrictOnDelete();
            $table->string('city');
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->text('educational_highlights')->nullable();
            $table->json('suitable_for')->nullable(); // ["study_tour", "penelitian"]
            $table->boolean('has_permit_document')->default(false);
            $table->foreignId('cancellation_policy_id')->nullable()->constrained('cancellation_policies')->nullOnDelete();
            $table->integer('default_daily_capacity')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('status')->default('draft'); // draft, pending_review, revision_required, published, inactive, archived
            $table->boolean('is_featured')->default(false);
            $table->decimal('rating_avg', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('category_id');
            $table->index('province_id');
            $table->index('is_featured');
        });

        Schema::create('destination_facility', function (Blueprint $table) {
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->primary(['destination_id', 'facility_id']);
        });

        Schema::create('destination_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->string('path');
            $table->string('caption')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('destination_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->string('contact_name');
            $table->string('role')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('destination_moderations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->string('action'); // approve, revision, reject
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained('destinations')->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->json('inquiry_types'); // ["study_tour", "penelitian"]
            $table->integer('duration_days')->default(1);
            $table->unsignedBigInteger('price_per_pax');
            $table->integer('min_pax')->default(1);
            $table->integer('max_pax')->default(500);
            $table->text('description')->nullable();
            $table->json('includes')->nullable();
            $table->json('excludes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained('destinations')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('pricing_type'); // per_pax, per_group, per_day, per_pax_per_day
            $table->unsignedBigInteger('price');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->date('date');
            $table->integer('capacity');
            $table->integer('booked_count')->default(0);
            $table->integer('held_count')->default(0);
            $table->string('status')->default('open'); // open, closed
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['destination_id', 'date']);
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'destination_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('availability_slots');
        Schema::dropIfExists('addons');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('destination_moderations');
        Schema::dropIfExists('destination_contacts');
        Schema::dropIfExists('destination_media');
        Schema::dropIfExists('destination_facility');
        Schema::dropIfExists('destinations');
    }
};
