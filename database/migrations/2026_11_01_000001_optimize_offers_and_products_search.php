<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('bonus');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->index(['is_active', 'expires_at'], 'idx_offers_active_expiry');
            $table->index(['expires_at', 'id'], 'idx_offers_expiry_id');
        });

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                'ALTER TABLE products ADD FULLTEXT INDEX products_search_fulltext '
                .'(normalized_name, name_ar, name_en)'
            );
            DB::statement('ALTER TABLE products ADD INDEX products_name_ar_prefix (name_ar(64))');
            DB::statement('ALTER TABLE products ADD INDEX products_name_en_prefix (name_en(64))');
        } else {
            Schema::table('products', function (Blueprint $table) {
                $table->index('name_ar');
                $table->index('name_en');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE products DROP INDEX products_search_fulltext');
            DB::statement('ALTER TABLE products DROP INDEX products_name_ar_prefix');
            DB::statement('ALTER TABLE products DROP INDEX products_name_en_prefix');
        } else {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['name_ar']);
                $table->dropIndex(['name_en']);
            });
        }

        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex('idx_offers_active_expiry');
            $table->dropIndex('idx_offers_expiry_id');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
