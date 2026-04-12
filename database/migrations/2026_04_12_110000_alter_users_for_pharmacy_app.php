<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['email', 'email_verified_at']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->unique()->after('name');
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('client')->after('password');
            }
            if (! Schema::hasColumn('users', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable()->after('role');
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('subscription_expires_at');
            }
            if (! Schema::hasColumn('users', 'activation_code')) {
                $table->string('activation_code')->nullable()->after('is_active');
            }
        });

        if (Schema::hasTable('password_reset_tokens')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                if (Schema::hasColumn('password_reset_tokens', 'email')) {
                    $table->dropColumn('email');
                }
            });
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                if (! Schema::hasColumn('password_reset_tokens', 'phone')) {
                    $table->string('phone')->primary();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'role',
                'subscription_expires_at',
                'is_active',
                'activation_code',
            ]);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
        });
    }
};
