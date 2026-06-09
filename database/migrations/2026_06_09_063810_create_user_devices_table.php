<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // معرّف فريد للجهاز — UUID يُنشأ في المتصفح ويُخزَّن في localStorage
            $table->string('device_fingerprint', 64)->index();

            // اسم وصفي للجهاز (User-Agent مختصر أو اسم مخصص)
            $table->string('device_name', 255)->nullable();

            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            // جهاز واحد فقط لكل user لكل fingerprint
            $table->unique(['user_id', 'device_fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
