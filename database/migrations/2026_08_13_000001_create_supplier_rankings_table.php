<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_items_count')->default(0);
            $table->decimal('discount_quality_index', 5, 2)->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->unique('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_rankings');
    }
};
