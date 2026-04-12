<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_logs', function (Blueprint $table) {
            $table->unsignedInteger('results_count')->nullable()->after('product_id');
            $table->boolean('had_offers')->nullable()->after('results_count');
        });

        Schema::create('saved_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_comparisons');

        Schema::table('search_logs', function (Blueprint $table) {
            $table->dropColumn(['results_count', 'had_offers']);
        });
    }
};
