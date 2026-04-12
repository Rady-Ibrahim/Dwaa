<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_logs', function (Blueprint $table) {
            $table->string('source', 24)->default('text')->after('user_id');
            $table->uuid('bulk_session_id')->nullable()->after('source');
            $table->json('meta')->nullable()->after('had_offers');

            $table->index(['source', 'created_at']);
            $table->index('bulk_session_id');
        });

        Schema::create('comparison_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('pairs_count')->default(0);
            $table->unsignedInteger('unmatched_a_count')->default(0);
            $table->unsignedInteger('unmatched_b_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparison_logs');

        Schema::table('search_logs', function (Blueprint $table) {
            $table->dropIndex(['source', 'created_at']);
            $table->dropIndex(['bulk_session_id']);
            $table->dropColumn(['source', 'bulk_session_id', 'meta']);
        });
    }
};
