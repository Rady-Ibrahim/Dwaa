<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activation_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedInteger('duration_days');
            $table->unsignedInteger('max_uses')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone1')->nullable();
            $table->string('phone2')->nullable();
            $table->string('area')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('code')->unique();
            $table->string('normalized_name')->index();
            $table->timestamps();
        });

        Schema::create('product_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('normalized_name')->unique();
            $table->timestamps();
        });

        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->json('column_map');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('unmatched_count')->default(0);
            $table->text('error_msg')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('upload_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price', 12, 2);
            $table->decimal('discount', 8, 2)->default(0);
            $table->string('bonus')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['supplier_id', 'product_id']);
        });

        Schema::create('unmatched_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained()->cascadeOnDelete();
            $table->string('raw_name');
            $table->string('normalized_name')->index();
            $table->string('status', 20)->default('pending');
            $table->foreignId('resolved_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('query');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_favorites');
        Schema::dropIfExists('search_logs');
        Schema::dropIfExists('unmatched_products');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('uploads');
        Schema::dropIfExists('product_aliases');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('activation_codes');
    }
};
