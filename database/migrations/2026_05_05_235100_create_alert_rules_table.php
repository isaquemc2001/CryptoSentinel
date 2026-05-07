<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('monitored_coin_id')->constrained('monitored_coins')->cascadeOnDelete();
            $table->string('trigger_type', 48)->index();
            $table->decimal('threshold_price', 36, 18)->nullable()->comment('abs price target');
            $table->decimal('threshold_percent', 16, 8)->nullable();
            $table->unsignedSmallInteger('window_minutes')->nullable();
            $table->string('notify_channel', 24)->default('telegram');
            $table->json('notify_payload')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['monitored_coin_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
