<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['supplier_id', 'normalized_name']);
        });

        Schema::table('product_aliases', function (Blueprint $table) {
            $table->dropUnique(['normalized_name']);
        });

        Schema::table('product_aliases', function (Blueprint $table) {
            $table->unique(['product_id', 'normalized_name']);
            $table->index('normalized_name');
        });
    }

    public function down(): void
    {
        Schema::table('product_aliases', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'normalized_name']);
            $table->dropIndex(['normalized_name']);
        });

        Schema::table('product_aliases', function (Blueprint $table) {
            $table->unique('normalized_name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropIndex(['supplier_id', 'normalized_name']);
            $table->dropColumn('supplier_id');
        });
    }
};
